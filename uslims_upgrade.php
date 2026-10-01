<?php

# Upgrade an existing LIMS host to the Slurm submission contract.
#
# Dry run by default: every step reports what it found and what it would
# change. --apply makes the changes, backing up each file first. Safe to rerun:
# a step that is already done reports "ok" and changes nothing.

# user defines

$wwwpath = "/srv/www/htdocs";

# end user defines

$self = __FILE__;

require_once "utility.php";

const NO_STDERR   = ' 2>/dev/null';
const WITH_STDERR = ' 2>&1';

## Report failed connections and queries as values, not exceptions (PHP 8.1+)
mysqli_report( MYSQLI_REPORT_OFF );

$notes = <<<__EOD
usage: $self {options}

Upgrade this host to the Slurm submission contract (gridctl#33, common#24, dbinst#57).
Without --apply nothing is changed; each step reports what it would do.

Options

--help                       : print this information and exit
--apply                      : make the changes (each changed file is backed up first)
--yes                        : accept the proposed value wherever one can be proposed
--www path                   : web root (default $wwwpath)
--web-user name              : account the web server runs as (default: apache, else www-data)
--env cluster=lines          : env_script_lines for a cluster that lacks it ('' for none); repeatable
--local-cluster name         : cluster used for GUI requests naming 'localhost'
--single-tenant yes|no       : yes on an appliance (one institution), no on a shared host

__EOD;

$u_argv = $argv;
array_shift( $u_argv );

$apply         = false;
$assume_yes    = false;
$web_user      = '';
$env_values    = [];
$local_cluster = null;
$single_tenant = null;

while ( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $opt = array_shift( $u_argv );
    switch ( $opt ) {
        case "--help":
            echo $notes;
            exit;
        case "--apply":
            $apply = true;
            break;
        case "--yes":
            $assume_yes = true;
            break;
        case "--www":
            $wwwpath = rtrim( array_shift( $u_argv ) ?? '', '/' );
            break;
        case "--web-user":
            $web_user = array_shift( $u_argv ) ?? '';
            break;
        case "--env":
            $pair = array_shift( $u_argv ) ?? '';
            if ( strpos( $pair, '=' ) === false ) {
                error_exit( "--env needs cluster=lines" );
            }
            list( $k, $v ) = explode( '=', $pair, 2 );
            $env_values[ $k ] = $v;
            break;
        case "--local-cluster":
            $local_cluster = array_shift( $u_argv );
            break;
        case "--single-tenant":
            $single_tenant = ( array_shift( $u_argv ) ?? '' ) === 'yes';
            break;
        default:
            error_exit( "\nUnknown option '$opt'\n\n$notes" );
    }
}

if ( count( $u_argv ) ) {
    error_exit( $notes );
}

if ( !is_admin() ) {
    error_exit( "you must be root to run $self" );
}

## ------------------------------------------------------------- locations

$us3_entry = posix_getpwnam( 'us3' );
if ( !$us3_entry ) {
    error_exit( "no us3 account on this host" );
}
$us3_home  = $us3_entry[ 'dir' ];
$us3bin    = "$us3_home/lims/bin";

if ( $web_user === '' ) {
    $web_user = posix_getpwnam( 'apache' ) ? 'apache' : 'www-data';
}
$web_entry = posix_getpwnam( $web_user );
if ( !$web_entry ) {
    error_exit( "web account '$web_user' not found; use --web-user" );
}
$web_group = posix_getgrgid( $web_entry[ 'gid' ] )[ 'name' ];

$listen_config = "$us3bin/listen-config.php";
$gridctl_dir   = is_file( "$us3bin/gridctl/listen-config.php.template" ) ? "$us3bin/gridctl" : $us3bin;
$template      = "$gridctl_dir/listen-config.php.template";
$global_config = "$wwwpath/common/global_config.php";
$breaker_dir   = "$us3_home/lims/etc/circuit-breaker";

$failures = 0;
$changes  = 0;

## ------------------------------------------------------------- helpers

function step( $title ) {
    echo "\n";
    headerline( $title );
}

function report( $status, $msg ) {
    global $failures;
    if ( $status === 'FAIL' ) {
        $failures++;
    }
    printf( "  [%-5s] %s\n", $status, $msg );
}

## Variables a PHP config file defines, read in a separate process so that an
## old config's own helper functions cannot clash with utility.php's.
function config_vars( $file ) {
    $code = 'ob_start(); include ' . var_export( $file, true ) . '; ob_end_clean();'
          . ' $v = array_filter( get_defined_vars(), function ( $k ) { return $k[ 0 ] !== "_" && $k !== "GLOBALS"; },'
          . ' ARRAY_FILTER_USE_KEY ); unset( $v["argv"], $v["argc"] );'
          . ' echo json_encode( $v, JSON_PARTIAL_OUTPUT_ON_ERROR );';
    $out  = shell_exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $code ) . NO_STDERR );
    $vars = json_decode( (string) $out, true );
    return is_array( $vars ) ? $vars : null;
}

function lint_ok( $file ) {
    exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) . WITH_STDERR, $out, $rc );
    return $rc === 0;
}

function confirm( $question ) {
    global $assume_yes;
    return $assume_yes || get_yn_answer( $question );
}

## Write a file after backing up the original, keeping its owner, group and mode.
function write_file( $path, $contents ) {
    global $changes;
    $stat = @stat( $path );
    if ( $stat ) {
        backup_file( $path );
    }
    if ( file_put_contents( $path, $contents ) === false ) {
        error_exit( "could not write $path" );
    }
    if ( $stat ) {
        chown( $path, $stat[ 'uid' ] );
        chgrp( $path, $stat[ 'gid' ] );
        chmod( $path, $stat[ 'mode' ] & 07777 );
    }
    $changes++;
}

## Run a command as another account, returning its exit status.
function run_as( $account, $cmd ) {
    exec( 'su -s /bin/sh ' . escapeshellarg( $account ) . ' -c ' . escapeshellarg( $cmd ) . WITH_STDERR, $out, $rc );
    return $rc;
}

## ------------------------------------------------------------- 1. preflight

## After the upgrade nothing can monitor, fetch or finalize an Airavata job,
## so every one must finish (or be cancelled) on the old code first.
step( "1. Preflight: Airavata jobs must finish before the upgrade" );

$old_listen = is_file( $listen_config ) ? config_vars( $listen_config ) : null;
if ( $old_listen === null ) {
    error_exit( "cannot read $listen_config" );
}

$gdb = @mysqli_connect( $old_listen[ 'dbhost' ] ?? 'localhost',
                        $old_listen[ 'guser' ] ?? 'gfac',
                        $old_listen[ 'gpasswd' ] ?? '',
                        $old_listen[ 'gDB' ] ?? 'gfac' );
if ( !$gdb ) {
    error_exit( "cannot connect to the gfac database: " . mysqli_connect_error() );
}
$res = mysqli_query( $gdb, "SELECT COUNT(*) FROM analysis WHERE gfacID NOT REGEXP '^[0-9]+\$'" );
$airavata = $res ? (int) mysqli_fetch_row( $res )[ 0 ] : -1;
if ( $airavata !== 0 ) {
    report( 'FAIL', $airavata < 0 ? "could not query gfac.analysis" : "$airavata non-Slurm (Airavata) job(s) still in gfac.analysis" );
    error_exit( "let these jobs finish (or cancel them) on the current code, then rerun; nothing was changed" );
}
report( 'ok', "no Airavata jobs in gfac.analysis" );

## ------------------------------------------------------------- 2. listen-config.php

step( "2. listen-config.php (values only, version 2)" );

## Site values carried from the old file; everything else comes from the template.
$carried = [ 'submit_dir', 'listen_port', 'dbhost', 'servhost', 'guser', 'gDB', 'user',
             'org_name', 'org_domain', 'admin_email', 'logging_level' ];

if ( ( $old_listen[ 'listen_config_version' ] ?? 0 ) >= 2 ) {
    report( 'ok', "$listen_config is already version 2" );
} elseif ( !is_file( $template ) ) {
    report( 'FAIL', "template not found: $template (update gridctl first)" );
} else {
    $text = file_get_contents( $template );
    foreach ( $carried as $key ) {
        if ( !array_key_exists( $key, $old_listen ) ) {
            continue;
        }
        $text = preg_replace_callback( '/^(\$' . $key . '\s*=\s*)[^;]*;/m',
            function ( $m ) use ( $old_listen, $key ) {
                return $m[ 1 ] . var_export( $old_listen[ $key ], true ) . ';';
            }, $text, 1 );
    }

    ## class_local/ was removed with the Slurm change; the classes are in class/.
    $class_dir = $old_listen[ 'class_dir' ] ?? "$wwwpath/common/class/";
    $class_dir = preg_replace( '~/class_local/?$~', '/class/', rtrim( $class_dir, '/' ) . '/' );
    $text = preg_replace( '/^(\$class_dir\s*=\s*)[^;]*;/m', '${1}' . var_export( $class_dir, true ) . ';', $text, 1 );

    $template_vars = [];
    preg_match_all( '/^\$(\w+)\s*=/m', $text, $m );
    $template_vars = $m[ 1 ];
    ## Derived at run time by the template, or retired with class_local/.
    $runtime = [ 'home', 'home_remote', 'work', 'work_remote', 'pipe', 'logfile', 'lock_dir', 'cfgfile',
                 'configs', 'gpasswd', 'passwd', 'self', 'errors', 'db_handle', 'us3pwentry',
                 'class_dir_p', 'class_dir_d', 'class_dir_l' ];
    $unreviewed = array_diff( array_keys( $old_listen ), $template_vars, $runtime );

    report( 'todo', "rewrite from the template, carrying " . implode( ', ', array_intersect( $carried, array_keys( $old_listen ) ) )
                    . "; class_dir $class_dir" );
    foreach ( $unreviewed as $key ) {
        report( 'note', "old setting \$$key is not in the new contract and will not be carried; review it" );
    }

    if ( $apply && confirm( "Rewrite $listen_config from the template?" ) ) {
        write_file( $listen_config, $text );
        report( 'done', "$listen_config rewritten (original backed up)" );
    }
}

## ------------------------------------------------------------- 3. global_config.php

step( "3. global_config.php settings" );

$gc = is_file( $global_config ) ? config_vars( $global_config ) : null;
if ( $gc === null ) {
    error_exit( "cannot read $global_config" );
}

$managed = [];     ## PHP assignments for the managed block, in order
$clusters = is_array( $gc[ 'cluster_details' ] ?? null ) ? $gc[ 'cluster_details' ] : [];
$active   = array_filter( $clusters, function ( $c ) { return is_array( $c ) && ( $c[ 'active' ] ?? false ); } );

## #864: queued jobs are never cancelled for waiting
if ( (int) ( $gc[ 'global_max_queue_time_hours' ] ?? 24 ) !== 0 ) {
    report( 'todo', "set \$global_max_queue_time_hours = 0 (#864; currently " . ( $gc[ 'global_max_queue_time_hours' ] ?? 'unset' ) . ")" );
    $managed[] = '$global_max_queue_time_hours = 0;';
} else {
    report( 'ok', "\$global_max_queue_time_hours is 0" );
}

## Tenant scope: unset reads as single tenant; set it explicitly either way
if ( !array_key_exists( 'single_tenant_deployment', $gc ) ) {
    $value = $single_tenant;
    if ( $value === null && $apply ) {
        $value = get_yn_answer( "Is this a single-institution appliance (single tenant)?" );
    }
    if ( $value === null ) {
        report( 'todo', "set \$single_tenant_deployment (pass --single-tenant yes|no)" );
    } else {
        report( 'todo', "set \$single_tenant_deployment = " . var_export( $value, true ) );
        $managed[] = '$single_tenant_deployment = ' . var_export( $value, true ) . ';';
    }
} else {
    report( 'ok', "\$single_tenant_deployment is " . var_export( (bool) $gc[ 'single_tenant_deployment' ], true ) );
}

## GUI requests name the host's own cluster 'localhost'
if ( !isset( $gc[ 'default_local_cluster' ] ) || !isset( $active[ $gc[ 'default_local_cluster' ] ] ) ) {
    $marked   = array_keys( array_filter( $active, function ( $c ) { return !empty( $c[ 'localhost' ] ); } ) );
    $proposal = $local_cluster ?? ( count( $marked ) === 1 ? $marked[ 0 ] : null );
    if ( $proposal !== null && !isset( $active[ $proposal ] ) ) {
        report( 'FAIL', "--local-cluster '$proposal' is not an active cluster" );
    } elseif ( $proposal === null ) {
        report( 'todo', "set \$default_local_cluster (pass --local-cluster; candidates: " . implode( ', ', array_keys( $active ) ) . ")" );
    } else {
        report( 'todo', "set \$default_local_cluster = '$proposal'" );
        $managed[] = '$default_local_cluster = ' . var_export( $proposal, true ) . ';';
    }
} else {
    report( 'ok', "\$default_local_cluster is '{$gc['default_local_cluster']}'" );
}

## Every active cluster needs the env_script_lines key ('' when it needs no setup)
foreach ( $active as $name => $c ) {
    if ( array_key_exists( 'env_script_lines', $c ) ) {
        report( 'ok', "$name has env_script_lines" );
        continue;
    }
    $value = $env_values[ $name ] ?? null;
    if ( $value === null && $apply ) {
        $value = readline( "env_script_lines for $name (modules/PATH setup; empty for none): " );
    }
    if ( $value === null ) {
        report( 'todo', "$name lacks env_script_lines (pass --env $name=... or --env $name=)" );
    } else {
        report( 'todo', "set env_script_lines for $name" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'env_script_lines\' ] = '
                   . var_export( str_replace( '\n', "\n", $value ), true ) . ';';
    }
}

## One-node appliances: resource sizing needs single_node to keep a job on one node
foreach ( $active as $name => $c ) {
    if ( array_key_exists( 'single_node', $c ) || ( empty( $c[ 'localhost' ] ) && empty( $c[ 'fixed_capacity' ] ) ) ) {
        continue;
    }
    $login = $c[ 'login' ] ?? ( 'us3@' . ( $c[ 'name' ] ?? '' ) );
    $queue = $c[ 'queue' ] ?? '';
    $count = trim( (string) shell_exec( 'su -s /bin/sh us3 -c ' . escapeshellarg(
        'ssh -n -o BatchMode=yes -o ConnectTimeout=15 ' . escapeshellarg( $login ) . ' '
        . escapeshellarg( 'sinfo -h -N -o %N' . ( $queue !== '' ? ' -p ' . escapeshellarg( $queue ) : '' ) . ' | sort -u | wc -l' ) ) . NO_STDERR ) );
    if ( $count === '1' ) {
        report( 'todo', "set single_node for $name (its queue has one node)" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'single_node\' ] = true;';
    } elseif ( ctype_digit( $count ) ) {
        report( 'ok', "$name has $count nodes; single_node not needed" );
    } else {
        report( 'note', "$name: could not count its nodes (sinfo over ssh); set single_node by hand if it is one node" );
    }
}

if ( $managed && $apply && confirm( "Write these settings to $global_config?" ) ) {
    $begin = "## BEGIN uslims_upgrade.php settings (rerun the script rather than editing by hand)";
    $end   = "## END uslims_upgrade.php settings";
    $text  = file_get_contents( $global_config );
    $prior = [];
    if ( preg_match( '/' . preg_quote( $begin, '/' ) . '\n(.*?)' . preg_quote( $end, '/' ) . '\n?/s', $text, $m ) ) {
        $prior = array_filter( explode( "\n", trim( $m[ 1 ] ) ) );
        $text  = str_replace( $m[ 0 ], '', $text );
    }
    $text  = preg_replace( '/\?>\s*$/', '', rtrim( $text ) ) . "\n\n$begin\n"
           . implode( "\n", array_unique( array_merge( $prior, $managed ) ) ) . "\n$end\n";
    write_file( $global_config, $text );
    report( lint_ok( $global_config ) ? 'done' : 'FAIL', "$global_config updated" );
}

## ------------------------------------------------------------- 4. SSH

step( "4. SSH host keys and access (StrictHostKeyChecking=yes is the default)" );

foreach ( $active as $name => $c ) {
    $host  = $c[ 'name' ] ?? '';
    $port  = (int) ( $c[ 'sshport' ] ?? 22 );
    $login = $c[ 'login' ] ?? "us3@$host";
    if ( $host === '' ) {
        report( 'FAIL', "$name has no 'name'" );
        continue;
    }
    foreach ( [ 'us3' => $us3_entry, $web_user => $web_entry ] as $account => $entry ) {
        $known = $entry[ 'dir' ] . "/.ssh/known_hosts";
        $o = $keys = $fp = [];
        $lookup = $port === 22 ? $host : "[$host]:$port";
        exec( 'ssh-keygen -F ' . escapeshellarg( $lookup ) . ' -f ' . escapeshellarg( $known ) . NO_STDERR, $o, $found );
        if ( $found !== 0 ) {
            exec( "ssh-keyscan -p $port " . escapeshellarg( $host ) . NO_STDERR, $keys );
            if ( !$keys ) {
                report( 'FAIL', "$name: no host key could be fetched from $host:$port" );
                continue;
            }
            exec( 'ssh-keyscan -p ' . $port . ' ' . escapeshellarg( $host ) . ' 2>/dev/null | ssh-keygen -lf - 2>/dev/null', $fp );
            report( 'todo', "$name: record $host's host key for $account:\n            " . implode( "\n            ", $fp ) );
            if ( $apply && confirm( "Do these fingerprints match $host's real host keys?" ) ) {
                @mkdir( dirname( $known ), 0700, true );
                file_put_contents( $known, implode( "\n", $keys ) . "\n", FILE_APPEND );
                chown( dirname( $known ), $account );
                chown( $known, $account );
                chmod( dirname( $known ), 0700 );
                $changes++;
            }
        }
        $rc = run_as( $account, "ssh -n -p $port -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=yes "
                                . escapeshellarg( $login ) . " true" );
        report( $rc === 0 ? 'ok' : 'FAIL', "$name: $account can ssh to $login" . ( $rc === 0 ? '' : " (exit $rc: install the account's key, including for the host itself)" ) );
    }
}

## ------------------------------------------------------------- 5. breaker directory

step( "5. Circuit-breaker directory" );

$want_gid = posix_getgrnam( $web_group )[ 'gid' ];
if ( is_dir( $breaker_dir ) && !is_link( $breaker_dir )
     && fileowner( $breaker_dir ) === $us3_entry[ 'uid' ] && filegroup( $breaker_dir ) === $want_gid
     && ( fileperms( $breaker_dir ) & 07777 ) === 02770 ) {
    report( 'ok', "$breaker_dir is 2770 us3:$web_group" );
} else {
    report( 'todo', "create $breaker_dir as 2770 us3:$web_group" );
    if ( $apply ) {
        @mkdir( $breaker_dir, 02770, true );
        chown( $breaker_dir, 'us3' );
        chgrp( $breaker_dir, $web_group );
        chmod( $breaker_dir, 02770 );
        $changes++;
        report( 'done', "$breaker_dir created" );
    }
}

## ------------------------------------------------------------- 6. crontabs

step( "6. us3 crontab" );

$crontab = (string) shell_exec( 'crontab -l -u us3 2>/dev/null' );
if ( preg_match( '/gridctl_(pro|dev)\.php/', $crontab ) ) {
    $fixed = preg_replace( '/gridctl_(pro|dev)\.php/', 'gridctl.php', $crontab );
    report( 'todo', "replace gridctl_pro.php / gridctl_dev.php with gridctl.php (it takes its own lock)" );
    if ( $apply && confirm( "Update the us3 crontab?" ) ) {
        $tmp = tempnam( sys_get_temp_dir(), 'us3cron' );
        file_put_contents( $tmp, $crontab );
        backup_file( $tmp );
        file_put_contents( $tmp, $fixed );
        exec( 'crontab -u us3 ' . escapeshellarg( $tmp ) . WITH_STDERR, $o, $rc );
        unlink( $tmp );
        report( $rc === 0 ? 'done' : 'FAIL', "us3 crontab updated" );
        $changes++;
    }
} else {
    report( 'ok', "no crontab entry calls gridctl_pro.php or gridctl_dev.php" );
}

## ------------------------------------------------------------- 7. verify

step( "7. Verify" );

$new_listen = config_vars( $listen_config );
report( lint_ok( $listen_config ) && ( $new_listen[ 'listen_config_version' ] ?? 0 ) >= 2 ? 'ok' : 'FAIL',
        "listen-config.php parses and is version 2" );
report( lint_ok( $global_config ) ? 'ok' : 'FAIL', "global_config.php parses" );

$probe = '$us3bin = ' . var_export( $us3bin, true ) . '; require ' . var_export( "$gridctl_dir/gridctl_bootstrap.php", true )
       . '; echo function_exists( "write_log" ) ? "ok" : "missing";';
$out = trim( (string) shell_exec( 'su -s /bin/sh us3 -c ' . escapeshellarg( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $probe ) ) . WITH_STDERR ) );
report( $out === 'ok' ? 'ok' : 'FAIL', "gridctl loads its configuration as us3" . ( $out === 'ok' ? '' : ": $out" ) );

report( run_as( $web_user, 'test -w ' . escapeshellarg( $breaker_dir ) ) === 0 ? 'ok' : 'FAIL',
        "$web_user can write the breaker directory" );

echo "\n";
echoline( '=' );
if ( !$apply ) {
    echo "Dry run: nothing was changed. Rerun with --apply to make the 'todo' changes.\n";
} else {
    echo "$changes change(s) made.\n";
}
echo $failures ? "$failures check(s) FAILED.\n" : "All checks passed.\n";
if ( $apply && $changes ) {
    echo "Restart the gridctl services: cd $us3bin && php services.php restart\n";
}
exit( $failures ? 1 : 0 );
