<?php

# Upgrade an existing LIMS host to the Slurm submission contract.
#
# Host configuration only. The stack code and the database schema are upgraded
# separately; the code must already be in place, since step 2 reads gridctl's
# template and step 8 installs dbutils' policy. Step 0 refuses to run otherwise.
#
# Dry run by default: every step reports what it found and what it would
# change. --apply makes the changes, backing up each file first. Safe to rerun:
# a step that is already done reports "ok" and changes nothing.

# user defines

$wwwpath = "/srv/www/htdocs";

# end user defines

$self = __FILE__;

require_once "utility.php";

## Report failed connections and queries as values, not exceptions (PHP 8.1+)
mysqli_report( MYSQLI_REPORT_OFF );

## The release every stack checkout must be at before this host can be upgraded.
$required_version = "4.3.0";

$notes = <<<__EOD
usage: $self {options}

Upgrade this host to the Slurm submission contract (gridctl#33, common#24, dbinst#57).
Without --apply nothing is changed; each step reports what it would do.

This script changes only this host's configuration. The stack code and the database
schema are upgraded separately, and the code must be upgraded first: pull common,
every instance, gridctl and dbutils to $required_version or newer, then run this as root.
Step 0 refuses to go further while any checkout is older.

Steps

0 : every stack checkout is at $required_version or newer (read from each repository's VERSION)
1 : preflight. Refuses to run while an Airavata job is in flight, while a job is
    collecting or importing results, or while a job holds a cleanup claim
2 : rewrites listen-config.php from gridctl's template, carrying the site's values
3 : deactivates clusters the Slurm code cannot submit to, then sets the global_config.php
    settings the new code requires (queue time, tenant scope, local cluster,
    env_script_lines per cluster, single_node on one-node appliances)
4 : records each cluster's host key and checks ssh for us3 and the web account
5 : creates the shared circuit-breaker directory
6 : fixes crontabs still calling gridctl_pro.php / gridctl_dev.php
7 : replaces jobmonitors started before the upgrade, which take no cleanup claim and
    would import a job's results twice. One that is collecting or importing results is
    left alone and reported
8 : installs the Content-Security-Policy as Report-Only unless a policy is already
    configured; enforcing it is a later step
9 : verifies the result

Options

--help                       : print this information and exit
--apply                      : make the changes (each changed file is backed up first)
--yes                        : accept the proposed value wherever one can be proposed.
                               Settings with no value to propose are reported instead of
                               asked for, so --apply --yes never waits for input.
--accept-host-keys           : trust the host keys this script fetches, without review.
                               Only for a network you already trust: --yes does not imply it.
--www path                   : web root (default $wwwpath)
--web-user name              : account the web code runs as (default: the PHP-FPM pool user, else apache, else www-data)
--env cluster=lines          : env_script_lines for a cluster ('' for none); sets or changes it; repeatable
--local-cluster name         : cluster used for GUI requests naming 'localhost'; sets or changes it
--single-tenant yes|no       : yes on an appliance (one institution), no on a shared host; sets or changes it

__EOD;

$u_argv = $argv;
array_shift( $u_argv );

## An option's value, or a clear error. Without this a trailing "--www" would
## silently leave $wwwpath empty and look for /common/global_config.php.
function opt_value( &$argv, $opt ) {
    if ( !count( $argv ) ) {
        error_exit( "$opt needs a value" );
    }
    return array_shift( $argv );
}

$apply         = false;
$assume_yes    = false;
$accept_keys   = false;
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
        case "--accept-host-keys":
            $accept_keys = true;
            break;
        case "--www":
            $wwwpath = rtrim( opt_value( $u_argv, $opt ), '/' );
            if ( $wwwpath === '' ) {
                error_exit( "--www needs a path" );
            }
            break;
        case "--web-user":
            $web_user = opt_value( $u_argv, $opt );
            if ( $web_user === '' ) {
                error_exit( "--web-user needs an account name" );
            }
            break;
        case "--env":
            $pair = opt_value( $u_argv, $opt );
            if ( strpos( $pair, '=' ) === false ) {
                error_exit( "--env needs cluster=lines" );
            }
            list( $k, $v ) = explode( '=', $pair, 2 );
            if ( $k === '' ) {
                error_exit( "--env needs a cluster name before the '='" );
            }
            $env_values[ $k ] = $v;
            break;
        case "--local-cluster":
            $local_cluster = opt_value( $u_argv, $opt );
            if ( $local_cluster === '' ) {
                error_exit( "--local-cluster needs a cluster name" );
            }
            break;
        case "--single-tenant":
            ## A typo must not quietly select 'no', which is the riskier value
            ## on an appliance.
            $answer = opt_value( $u_argv, $opt );
            if ( $answer !== 'yes' && $answer !== 'no' ) {
                error_exit( "--single-tenant takes 'yes' or 'no', not '$answer'" );
            }
            $single_tenant = $answer === 'yes';
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

## The web code runs as the PHP-FPM pool user when there is one (us3 on
## Ansible-built hosts), else as the web server account.
if ( $web_user === '' ) {
    foreach ( array_merge( glob( '/etc/php-fpm.d/*.conf' ) ?: [], glob( '/etc/php/*/fpm/pool.d/*.conf' ) ?: [] ) as $pool ) {
        if ( preg_match( '/^\s*user\s*=\s*(\S+)/m', (string) @file_get_contents( $pool ), $m ) ) {
            $web_user = $m[ 1 ];
            break;
        }
    }
}
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
## Stamped when this script replaces the jobmonitors. A monitor that started
## before it is running pre-upgrade code. A file time on the monitor's own
## source would be the obvious signal but is not dependable: "cp -p", "rsync -a"
## or a checkout that preserves times would leave every monitor looking new.
$marker_file   = "$us3_home/lims/etc/uslims_upgrade-applied";

$failures = 0;
$changes  = 0;
$pending  = 0;

## Backups go to a fixed, absolute location rather than wherever root happened to
## be when the script was started. The directory itself is created on first use,
## so a dry run still creates nothing.
$backup_prefix = "$us3_home/lims/etc/uslims_upgrade-backup";

## ------------------------------------------------------------- helpers

function step( $title ) {
    echo "\n";
    headerline( $title );
}

function report( $status, $msg ) {
    global $failures, $pending;
    if ( $status === 'FAIL' ) {
        $failures++;
    } elseif ( $status === 'todo' ) {
        $pending++;
    }
    printf( "  [%-5s] %s\n", $status, $msg );
}

## A precondition that later steps write on top of. Stop before changing
## anything else, so a half-upgraded host is not left behind.
function fatal( $msg ) {
    global $changes;
    report( 'FAIL', $msg );
    error_exit( "cannot continue: $msg\n"
                . ( $changes ? "$changes change(s) were already made; see the backup directory." : "Nothing was changed." ) );
}

## Create this run's backup directory, once, in a known absolute place. Called
## before anything that backs a file up, so utility.php's relative default
## ("backup-<timestamp>" in the current directory) is never the one used.
function ensure_backup_dir() {
    global $util_backup_dir, $backup_prefix;
    if ( !isset( $util_backup_dir ) || !strlen( $util_backup_dir ) ) {
        backup_dir_init( $backup_prefix );
        echo "Backups for this run: $util_backup_dir\n";
    }
    return $util_backup_dir;
}

## Where backup_file() puts a copy of $path, and a place to save other originals.
function backup_path( $name ) {
    return ensure_backup_dir() . '/' . basename( $name );
}

## Run a command, keeping its stderr separate from its stdout. Returns
## [ stdout, stderr, exit status ] so a step can report why a command failed
## rather than only that it produced nothing.
function capture( $cmd ) {
    $errfile = tempnam( sys_get_temp_dir(), 'us3up' );
    if ( $errfile === false ) {
        error_exit( "could not create a temporary file" );
    }
    $lines = [];
    ## The subshell keeps the redirect over the whole command: "a; b 2>f" would
    ## otherwise redirect only b, letting a's stderr reach the terminal.
    exec( '( ' . $cmd . ' ) 2>' . escapeshellarg( $errfile ), $lines, $rc );
    $err = trim( (string) @file_get_contents( $errfile ) );
    @unlink( $errfile );
    return [ implode( "\n", $lines ), $err, $rc ];
}

## Variables a PHP config file defines, read in a separate process so that an
## old config's own helper functions cannot clash with utility.php's.
## $why is set to the reason when the file cannot be read.
function config_vars( $file, &$why = null ) {
    $code = 'ob_start(); include ' . var_export( $file, true ) . '; ob_end_clean();'
          . ' $v = array_filter( get_defined_vars(), function ( $k ) { return $k[ 0 ] !== "_" && $k !== "GLOBALS"; },'
          . ' ARRAY_FILTER_USE_KEY ); unset( $v["argv"], $v["argc"] );'
          . ' echo json_encode( $v, JSON_PARTIAL_OUTPUT_ON_ERROR );';
    list( $out, $err, $rc ) = capture( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $code ) );
    $vars = json_decode( $out, true );
    if ( is_array( $vars ) ) {
        $why = '';
        return $vars;
    }
    ## Distinguish a fatal in the config, a non-zero exit and unparseable output.
    $why = $err !== '' ? $err
         : ( $rc !== 0 ? "php exited $rc with no message"
                       : 'the config produced no readable variables: ' . json_last_error_msg() );
    return null;
}

## $why is set to php -l's complaint when the file does not parse.
function lint_ok( $file, &$why = null ) {
    list( $out, $err, $rc ) = capture( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $file ) );
    $why = $rc === 0 ? '' : trim( $err . "\n" . $out );
    return $rc === 0;
}

## Whether there is someone at a terminal to answer a question. Without this
## check utility.php's get_yn_answer() spins forever when readline() hits EOF,
## which is what happens under cron, Ansible or any other automation.
function interactive() {
    return function_exists( 'posix_isatty' ) && @posix_isatty( STDIN );
}

## Ask a yes/no question, or stop with an explanation if nobody can answer.
function ask_yn( $question, $hint = 'rerun with --yes to accept' ) {
    if ( !interactive() ) {
        error_exit( "$question\nThere is no terminal to answer on; $hint. Nothing further was changed." );
    }
    echoline( '=' );
    while ( true ) {
        $answer = readline( "$question (y or n) : " );
        if ( $answer === false ) {
            ## EOF: readline keeps returning false, so never loop on it.
            error_exit( "input ended while waiting for an answer; nothing further was changed" );
        }
        $answer = strtolower( trim( $answer ) );
        if ( $answer === 'y' || $answer === 'n' ) {
            return $answer === 'y';
        }
    }
}

function confirm( $question ) {
    global $assume_yes;
    return $assume_yes || ask_yn( $question );
}

## Trusting a freshly scanned host key is not something --yes should decide: it
## would turn StrictHostKeyChecking=yes back into accept-new. Unattended runs
## report the fingerprints instead, and install nothing.
function confirm_host_keys( $question ) {
    global $accept_keys;
    if ( $accept_keys ) {
        return true;
    }
    return interactive() ? ask_yn( $question, 'rerun with --accept-host-keys to trust it' ) : false;
}

## Write a file after backing up the original. Rewriting an existing file
## truncates it in place, so it keeps its owner, group and mode: a switch to
## writing a temp file and renaming it would have to restore all three.
## $verify lints the result and restores the original if it does not parse, so a
## bad rewrite never stays live: global_config.php and listen-config.php are both
## loaded by running services.
function write_file( $path, $contents, $verify = true ) {
    global $changes;
    $saved = null;
    if ( is_file( $path ) ) {
        ensure_backup_dir();
        backup_file( $path );
        $saved = backup_path( $path );
    }
    if ( file_put_contents( $path, $contents ) === false ) {
        error_exit( "could not write $path" );
    }
    if ( $verify && !lint_ok( $path, $why ) ) {
        if ( $saved !== null && is_file( $saved ) && @copy( $saved, $path ) ) {
            error_exit( "the new $path does not parse (" . reason( $why ) . ")\n"
                        . "The original has been restored from $saved. Nothing further was changed." );
        }
        error_exit( "the new $path does not parse (" . reason( $why ) . ")\n"
                    . "It could NOT be restored automatically"
                    . ( $saved === null ? " (there was no original)" : "; restore it by hand from $saved" ) . "." );
    }
    $changes++;
}

## Run a command as another account, returning its exit status. $output is its
## stdout and $errors its stderr, kept apart so a caller can both read a value
## the command printed and report why it failed. ssh writes banners and warnings
## to stderr, so merging the two would corrupt a parsed result.
function run_as( $account, $cmd, &$output = null, &$errors = null ) {
    list( $out, $err, $rc ) = capture( 'su -s /bin/sh ' . escapeshellarg( $account ) . ' -c ' . escapeshellarg( $cmd ) );
    $output = trim( $out );
    $errors = trim( $err );
    return $rc;
}

## gridctl.php replaces gridctl_pro.php and gridctl_dev.php and takes its own
## lock, so a pro and dev pair collapses to one line.
function fix_crontab( $text ) {
    $seen = [];
    $out  = [];
    foreach ( explode( "\n", $text ) as $line ) {
        $fixed = preg_replace( '/gridctl_(pro|dev)\.php/', 'gridctl.php', $line );
        if ( $fixed !== $line && isset( $seen[ $fixed ] ) ) {
            continue;
        }
        $seen[ $fixed ] = true;
        $out[] = $fixed;
    }
    ## crontab(1) rejects a file whose last line has no newline, and the text
    ## arrives without one: capture() builds it from exec()'s array form, which
    ## drops the trailing newline. Normalize to exactly one.
    return rtrim( implode( "\n", $out ), "\n" ) . "\n";
}

## fix_crontab() rewrites only the gridctl_pro/dev references and collapses the
## duplicate that leaves, so every other line must survive. /etc/crontab carries
## the nightly backup ("cronic php .../uslims_daily_backup.php"), and a line lost
## here would stop the backups without saying so.
function crontab_lines_lost( $before, $after ) {
    $expected = [];
    foreach ( explode( "\n", $before ) as $line ) {
        if ( trim( $line ) !== '' && !preg_match( '/gridctl_(pro|dev)\.php/', $line ) ) {
            $expected[] = $line;
        }
    }
    return array_values( array_diff( $expected, explode( "\n", $after ) ) );
}

## Crontabs still calling the old controllers: us3's own, then system files.
## Keys are 'us3' or a file path. $error is set when us3's crontab could not be
## read at all, which is not the same as there being none.
function old_controller_crontabs( &$error = null ) {
    $found = [];
    $error = '';
    list( $us3_tab, $err, $rc ) = capture( 'crontab -l -u us3' );
    if ( $rc !== 0 && !preg_match( '/no crontab for/i', $err ) ) {
        $error = $err;
    }
    ## capture() drops the final newline, and crontab rejects a file without one.
    $tabs  = [ 'us3' => $us3_tab === '' ? '' : rtrim( $us3_tab, "\n" ) . "\n" ];
    foreach ( array_merge( [ '/etc/crontab' ], glob( '/etc/cron.d/*' ) ?: [] ) as $file ) {
        $tabs[ $file ] = is_file( $file ) ? (string) @file_get_contents( $file ) : '';
    }
    foreach ( $tabs as $where => $text ) {
        if ( preg_match( '/gridctl_(pro|dev)\.php/', $text ) ) {
            $found[ $where ] = $text;
        }
    }
    return $found;
}

## Give an account a key (if it has none) and authorize it for a local login
## account, for SSH to the host's own cluster.
function authorize_local_key( $account, $entry, $login_user ) {
    $key = $entry[ 'dir' ] . '/.ssh/id_ed25519';
    if ( !is_file( "$key.pub" ) && run_as( $account, 'mkdir -p -m 700 ~/.ssh && ssh-keygen -q -t ed25519 -N "" -f ' . escapeshellarg( $key ) ) !== 0 ) {
        return false;
    }
    $login = posix_getpwnam( $login_user );
    if ( !$login ) {
        return false;
    }
    $pub  = trim( (string) file_get_contents( "$key.pub" ) );
    $auth = $login[ 'dir' ] . '/.ssh/authorized_keys';
    if ( strpos( (string) @file_get_contents( $auth ), $pub ) === false ) {
        @mkdir( dirname( $auth ), 0700, true );
        file_put_contents( $auth, "$pub\n", FILE_APPEND );
        chown( dirname( $auth ), $login_user );
        chown( $auth, $login_user );
        chmod( $auth, 0600 );
    }
    return true;
}

## A PHP string literal on one line, so each managed setting is one line.
function php_string( $value ) {
    return '"' . addcslashes( $value, "\\\"\$\n\r\t" ) . '"';
}

## The managed block's assignments keyed by their target, e.g. '$default_local_cluster'.
function block_settings( $block ) {
    $settings = [];
    $current  = '';
    foreach ( token_get_all( "<?php\n$block" ) as $token ) {
        if ( is_array( $token ) && $token[ 0 ] === T_OPEN_TAG ) {
            continue;
        }
        $text = is_array( $token ) ? $token[ 1 ] : $token;
        if ( $text === ';' ) {
            $statement = trim( $current ) . ';';
            $target    = preg_replace( '/\s+/', '', strstr( $statement, '=', true ) );
            $settings[ $target ] = $statement;
            $current = '';
        } else {
            $current .= $text;
        }
    }
    return $settings;
}

## Replace a top-level "$name = ...;" assignment in $text with $value.
## The replacement is built in a callback: an exported value put straight into a
## preg_replace() replacement string would have its backslashes and $n
## sequences reinterpreted. $replaced says whether the assignment was found.
function substitute( $text, $name, $value, &$replaced = null ) {
    $count  = 0;
    $result = preg_replace_callback( '/^(\$' . preg_quote( $name, '/' ) . '\s*=\s*)[^;]*;/m',
        function ( $m ) use ( $value ) {
            return $m[ 1 ] . var_export( $value, true ) . ';';
        }, $text, 1, $count );
    if ( $result === null ) {
        ## A pattern failure would otherwise leave $text null and write an empty file.
        error_exit( "could not substitute \$$name: preg error " . preg_last_error() );
    }
    $replaced = $count > 0;
    return $result;
}

## "user:group mode" from a stat() array, for a report() message.
function owner_of( $stat ) {
    $pw = posix_getpwuid( $stat[ 'uid' ] );
    $gr = posix_getgrgid( $stat[ 'gid' ] );
    return ( $pw ? $pw[ 'name' ] : $stat[ 'uid' ] ) . ':' . ( $gr ? $gr[ 'name' ] : $stat[ 'gid' ] )
           . ' ' . decoct( $stat[ 'mode' ] & 07777 );
}

## Jobs that must not have their monitor taken away. In gfac.analysis anything
## past SUBMITTED/RUNNING is at or past job_cleanup()'s import, since "DATA"
## means finished and waiting for its data to be collected; a cleanup.claim
## directory is a worker inside job_cleanup() right now. 'now' and 'claims'
## block, 'stalled' is only worth reporting. An hour is the window
## cleanup_claim_acquire() itself treats as abandoned.
##
## This cannot see a pre-upgrade import that is already under way: 4.2.0 deletes
## the row before it writes the results and takes no claim, so by then there is
## nothing here to find. Do not rely on it for that. Step 7's per-monitor check
## is the real guard.
function import_blockers( $gdb, $us3_home ) {
    $res = mysqli_query( $gdb, <<<'SQL'
SELECT SUM( status IS NULL OR status NOT IN ( 'SUBMITTED', 'RUNNING' ) )            AS importing_total,
       SUM( ( status IS NULL OR status NOT IN ( 'SUBMITTED', 'RUNNING' ) )
            AND time >= NOW() - INTERVAL 1 HOUR )                                     AS importing_now
  FROM analysis
 WHERE gfacID IS NOT NULL AND gfacID <> '' AND gfacID REGEXP '^[0-9]+$'
SQL
    );
    if ( !$res ) {
        return [ 'error' => mysqli_error( $gdb ) ?: 'the query failed' ];
    }
    $row    = mysqli_fetch_assoc( $res );
    $claims = [];
    foreach ( glob( "$us3_home/lims/etc/joblog/*/*/cleanup.claim" ) ?: [] as $claim ) {
        $owner = (int) @file_get_contents( "$claim/owner" );
        $age   = time() - (int) @filemtime( $claim );
        if ( $age < 3600 || ( $owner && @posix_kill( $owner, 0 ) ) ) {
            $claims[] = basename( dirname( $claim ) ) . ( $owner ? " (pid $owner)" : '' );
        }
    }
    return [ 'error'   => '',
             'now'     => (int) $row[ 'importing_now' ],
             'stalled' => (int) $row[ 'importing_total' ] - (int) $row[ 'importing_now' ],
             'claims'  => $claims ];
}

## The blocking half of import_blockers(), reported the same way in both places.
function report_import_blockers( $b ) {
    if ( $b[ 'now' ] ) {
        report( 'FAIL', $b[ 'now' ] . " job(s) are collecting or importing results now"
                        . " (gfac.analysis past SUBMITTED/RUNNING, touched within the hour)" );
    }
    if ( $b[ 'claims' ] ) {
        report( 'FAIL', count( $b[ 'claims' ] ) . " job(s) hold a cleanup claim: "
                        . implode( ', ', $b[ 'claims' ] ) );
    }
}

## When this script last replaced the jobmonitors, or null if it never has.
function marker_time( $path ) {
    $t = is_file( $path ) ? (int) trim( (string) @file_get_contents( $path ) ) : 0;
    return $t > 0 ? $t : null;
}

## Stamp the marker. Written before the monitors are replaced, so the ones
## restarted afterwards count as new and a rerun has nothing to do.
function write_marker( $path ) {
    if ( @file_put_contents( $path, time() . "\n" ) === false ) {
        return false;
    }
    ## Only this script, run as root, reads it.
    @chmod( $path, 0600 );
    return true;
}

## The running jobmonitors, each as [ pid, db, gfacID, started, old ]. A monitor
## is "old" when it started before this script last replaced the monitors, which
## is what tells a pre-upgrade monitor from one the new code started: the old one
## has no cleanup claim, the new one does. With no marker the upgrade has never
## applied here, so every monitor predates it. $error is set when the process
## list could not be read.
function jobmonitors( $marker, &$error = null ) {
    $error = '';
    ## etimes is the process age in seconds, so now minus it is its start.
    list( $out, $err, $rc ) = capture( "ps -eo pid=,etimes=,args=" );
    if ( $rc !== 0 ) {
        $error = $err !== '' ? $err : "ps exited $rc";
        return [];
    }
    $now  = time();
    $mine = [ getmypid(), posix_getppid() ];
    $jms  = [];
    foreach ( explode( "\n", $out ) as $line ) {
        ## Match the launched form only: a php binary followed by the monitor's
        ## own path, then its three arguments. 4.2.0 starts it under "nice -15",
        ## which execs php, so the process still shows php as argv[0]. A looser
        ## match would catch anything merely naming the script, and this step
        ## kills as root.
        if ( !preg_match( '#^\s*(\d+)\s+(\d+)\s+\S*php[0-9.]*\s+\S*jobmonitor/jobmonitor\.php\s+(\S+)\s+(\S+)#',
                          $line, $m ) ) {
            continue;
        }
        $pid = (int) $m[ 1 ];
        if ( in_array( $pid, $mine, true ) ) {
            continue;
        }
        $started = $now - (int) $m[ 2 ];
        $jms[ $pid ] = [ 'pid'     => $pid,
                         'db'      => $m[ 3 ],
                         'gfacID'  => $m[ 4 ],
                         'started' => $started,
                         'old'     => $marker === null ? true : $started < $marker ];
    }
    return $jms;
}

## The gfac.analysis status of one monitor's job, '' when the row is gone (a
## finished cleanup deletes it) and null when the query failed.
function job_status( $gdb, $db, $gfacID ) {
    $q = "SELECT status FROM analysis WHERE gfacID = '" . mysqli_real_escape_string( $gdb, $gfacID ) . "'"
         . " AND us3_db = '" . mysqli_real_escape_string( $gdb, $db ) . "'";
    $res = mysqli_query( $gdb, $q );
    if ( !$res ) {
        return null;
    }
    $row = mysqli_fetch_row( $res );
    return $row === null ? '' : (string) $row[ 0 ];
}

## One line of a command's complaint, for a report() message.
function reason( $text ) {
    $text = trim( preg_replace( '/\s+/', ' ', (string) $text ) );
    return $text === '' ? '' : ( strlen( $text ) > 200 ? substr( $text, 0, 197 ) . '...' : $text );
}

## ------------------------------------------------------------- 0. stack code

## Every later step assumes the new code is already deployed: step 2 carries the
## site's values into gridctl's template, step 8 installs dbutils' policy, and the
## web code has to be able to submit through Slurm. Checking first means a stale
## checkout is reported before anything on the host has been changed, rather than
## failing partway through.
step( "0. Stack code is $required_version or newer" );

## Each repository records its release in a VERSION file at its root. 4.2.0
## carried one too, so a missing file is an unrecognized checkout, not an old one.
function repo_version( $dir ) {
    $file = "$dir/VERSION";
    if ( !is_file( $file ) ) {
        return null;
    }
    $text = trim( (string) @file_get_contents( $file ) );
    return $text === '' ? null : $text;
}

## "4.3.0-dev" is the 4.3.0 contract, but version_compare() ranks a -dev suffix
## below the release, so compare the numeric part only.
function version_at_least( $version, $minimum ) {
    return version_compare( rtrim( preg_replace( '/[^0-9.].*$/', '', trim( $version ) ), '.' ),
                            $minimum, '>=' );
}

$stack = [ 'gridctl' => $gridctl_dir,
           'dbutils' => __DIR__,
           'common'  => "$wwwpath/common",
           'webinfo' => "$wwwpath/uslims3" ];
## newinst's create_instance.php clones dbinst once per instance, so every
## instance docroot is its own checkout and is upgraded on its own.
foreach ( glob( "$wwwpath/uslims3/*/VERSION" ) ?: [] as $version_file ) {
    $dir = dirname( $version_file );
    $stack[ basename( $dir ) ] = $dir;
}

$stale = [];
foreach ( $stack as $label => $dir ) {
    $version = repo_version( $dir );
    if ( $version === null ) {
        report( 'FAIL', "$label ($dir) has no readable VERSION; cannot tell which release is deployed" );
        $stale[] = $label;
    } elseif ( !version_at_least( $version, $required_version ) ) {
        report( 'FAIL', "$label ($dir) is $version" );
        $stale[] = $label;
    } else {
        report( 'ok', "$label is $version" );
    }
}
if ( $stale ) {
    error_exit( "pull " . implode( ', ', $stale ) . " to $required_version or newer, then rerun."
                . "\nThe code and the database schema are upgraded separately; this script only"
                . " changes this host's configuration. Nothing was changed" );
}

## ------------------------------------------------------------- 1. preflight

## After the upgrade nothing can monitor, fetch or finalize an Airavata job,
## so every one must finish (or be cancelled) on the old code first.
step( "1. Preflight: no job may be mid-import before the upgrade" );

if ( !is_file( $listen_config ) ) {
    error_exit( "cannot read $listen_config: no such file" );
}
$old_listen = config_vars( $listen_config, $why );
if ( $old_listen === null ) {
    error_exit( "cannot read $listen_config: " . reason( $why ) );
}

$gdb = @mysqli_connect( $old_listen[ 'dbhost' ] ?? 'localhost',
                        $old_listen[ 'guser' ] ?? 'gfac',
                        $old_listen[ 'gpasswd' ] ?? '',
                        $old_listen[ 'gDB' ] ?? 'gfac' );
if ( !$gdb ) {
    error_exit( "cannot connect to the gfac database: " . mysqli_connect_error() );
}
## job_cleanup() deletes the analysis row once it has imported, so a row that is
## still here is a job no cleanup has finished. Counting every Airavata row would
## therefore refuse the upgrade over jobs that failed years ago and can never be
## finished, since the new code cannot run them. Terminal statuses are listed
## rather than active ones, so a status this script does not know about counts as
## in flight and errs toward refusing.
## A NULL or empty gfacID is a job that has no cluster id yet: it cannot be told
## apart from an Airavata job, so it is reported rather than assumed harmless.
## A fixed query: "active" is any status outside the terminal list.
$res = mysqli_query( $gdb, <<<'SQL'
SELECT SUM( ( status IS NULL OR status NOT IN ( 'COMPLETE', 'CANCELLED', 'CANCELED', 'FAILED', 'FAILED_DATA',
                                                 'ERROR', 'SUBMIT_TIMEOUT', 'RUN_TIMEOUT', 'DATA_TIMEOUT' ) )
            AND gfacID IS NOT NULL AND gfacID <> '' AND gfacID NOT REGEXP '^[0-9]+$' ) AS airavata_active,
       SUM( ( status IS NULL OR status NOT IN ( 'COMPLETE', 'CANCELLED', 'CANCELED', 'FAILED', 'FAILED_DATA',
                                                 'ERROR', 'SUBMIT_TIMEOUT', 'RUN_TIMEOUT', 'DATA_TIMEOUT' ) )
            AND ( gfacID IS NULL OR gfacID = '' ) )                                  AS unidentified_active,
       SUM( gfacID IS NOT NULL AND gfacID <> '' AND gfacID NOT REGEXP '^[0-9]+$' )   AS airavata_total
  FROM analysis
SQL
);
if ( !$res ) {
    report( 'FAIL', "could not query gfac.analysis: " . mysqli_error( $gdb ) );
    error_exit( "the preflight check could not run; nothing was changed" );
}
$row       = mysqli_fetch_assoc( $res );
$in_flight = (int) $row[ 'airavata_active' ];
$unknown   = (int) $row[ 'unidentified_active' ];
$history   = (int) $row[ 'airavata_total' ];

if ( $in_flight || $unknown ) {
    if ( $in_flight ) {
        report( 'FAIL', "$in_flight Airavata job(s) still in flight in gfac.analysis" );
    }
    if ( $unknown ) {
        report( 'FAIL', "$unknown unfinished job(s) with no gfacID; they cannot be told apart from Airavata jobs" );
    }
    error_exit( "let these jobs finish (or cancel them) on the current code, then rerun; nothing was changed" );
}
report( 'ok', "no Airavata jobs in flight"
              . ( $history ? " ($history unfinished Airavata job(s) remain in gfac.analysis; the new code cannot run them, so they are left alone)" : "" ) );

## Only a monitor from before the upgrade is a double-import risk: it holds no
## cleanup claim. Once the host is upgraded its monitors do, so an import in
## progress is ordinary operation and must not fail a rerun.
##
## This check cannot see a pre-upgrade import that is already under way: the old
## cleanup deletes the gfac.analysis row before it writes the results and takes
## no claim, so there is nothing left to find. Step 7's per-monitor check is the
## real guard; this one is an early warning only.
$monitors     = jobmonitors( marker_time( $marker_file ), $jm_error );
$old_monitors = array_filter( $monitors, function ( $j ) { return $j[ 'old' ]; } );
if ( $jm_error !== '' ) {
    report( 'FAIL', "could not list the running jobmonitors: " . reason( $jm_error ) );
    error_exit( "the preflight check could not run; nothing was changed" );
}
if ( !$old_monitors ) {
    report( 'ok', count( $monitors ) . " jobmonitor(s) running, none from before the upgrade" );
}

$blockers = import_blockers( $gdb, $us3_home );
if ( $blockers[ 'error' ] !== '' ) {
    report( 'FAIL', "could not check for jobs mid-import: " . reason( $blockers[ 'error' ] ) );
    error_exit( "the preflight check could not run; nothing was changed" );
}
## Step 7 enforces this per monitor; here it is an early warning, and it only
## applies while a claimless monitor is still running.
if ( $old_monitors && ( $blockers[ 'now' ] || $blockers[ 'claims' ] ) ) {
    report_import_blockers( $blockers );
    error_exit( "replacing the jobmonitors now could leave a half-imported result that is then"
                . " imported again, and a monitor from before the upgrade takes no cleanup claim to"
                . " prevent it.\n"
                . "Wait for these to finish, then rerun; nothing was changed" );
}
if ( $old_monitors ) {
    report( 'ok', "no job is collecting or importing results" );
}
if ( $old_monitors && $blockers[ 'stalled' ] ) {
    ## Not blocking: these have not moved in over an hour, so waiting will not
    ## clear them, but they are the rows to check first if an import looks wrong.
    report( 'note', $blockers[ 'stalled' ] . " job(s) are past SUBMITTED/RUNNING but have not been"
                    . " touched for over an hour; they look stalled rather than active, so they are not"
                    . " blocking. Review them before trusting an import" );
}

## ------------------------------------------------------------- 2. listen-config.php

step( "2. listen-config.php (values only, version 2)" );

## Site values carried from the old file; everything else comes from the template.
$carried = [ 'submit_dir', 'listen_port', 'dbhost', 'servhost', 'host_name', 'guser', 'gDB', 'user',
             'org_name', 'org_domain', 'admin_email', 'logging_level' ];

if ( ( $old_listen[ 'listen_config_version' ] ?? 0 ) >= 2 ) {
    report( 'ok', "$listen_config is already version 2" );
} elseif ( !is_file( $template ) ) {
    ## Later steps assume the new contract is in place, so this cannot be a
    ## warning the script walks past.
    fatal( "template not found: $template (update gridctl first)" );
} else {
    $text = file_get_contents( $template );
    if ( $text === false ) {
        fatal( "could not read the template $template" );
    }
    if ( !isset( $old_listen[ 'host_name' ] ) && isset( $old_listen[ 'servhost' ] ) ) {
        $old_listen[ 'host_name' ] = $old_listen[ 'servhost' ];
    }
    $carried_keys = [];
    foreach ( $carried as $key ) {
        if ( !array_key_exists( $key, $old_listen ) ) {
            continue;
        }
        $text = substitute( $text, $key, $old_listen[ $key ], $replaced );
        if ( $replaced ) {
            $carried_keys[] = $key;
        } else {
            ## The template has no such setting, so the old value has nowhere to go.
            report( 'note', "old setting \$$key has no place in the new template and will not be carried; review it" );
        }
    }

    $tz = null;
    ## The time zone is a call, not a variable, so config_vars() does not see it.
    if ( preg_match( '/^\s*date_default_timezone_set\(\s*([\'"])([^\'"]+)\1\s*\)/m',
                     (string) file_get_contents( $listen_config ), $tz ) ) {
        ## Callback form for the same reason substitute() uses one: an exported
        ## value in a replacement string would have $n and backslashes reread.
        $zone   = $tz[ 2 ];
        $result = preg_replace_callback( '/^(\s*date_default_timezone_set\(\s*)[\'"][^\'"]+[\'"]/m',
            function ( $m ) use ( $zone ) {
                return $m[ 1 ] . var_export( $zone, true );
            }, $text, 1 );
        if ( $result === null ) {
            fatal( "could not carry the time zone: preg error " . preg_last_error() );
        }
        $text = $result;
    }

    ## class_local/ was removed with the Slurm change; the classes are in class/.
    $class_dir = $old_listen[ 'class_dir' ] ?? "$wwwpath/common/class/";
    $class_dir = preg_replace( '~/class_local/?$~', '/class/', rtrim( $class_dir, '/' ) . '/' );
    if ( $class_dir === null ) {
        fatal( "could not normalize class_dir '" . ( $old_listen[ 'class_dir' ] ?? '' ) . "'" );
    }
    $text = substitute( $text, 'class_dir', $class_dir, $replaced );
    if ( !$replaced ) {
        fatal( "the template $template does not set \$class_dir" );
    }

    $template_vars = [];
    preg_match_all( '/^\$(\w+)\s*=/m', $text, $m );
    $template_vars = $m[ 1 ];
    ## Derived at run time by the template, or retired with class_local/.
    $runtime = [ 'home', 'home_remote', 'work', 'work_remote', 'pipe', 'logfile', 'lock_dir', 'cfgfile',
                 'configs', 'gpasswd', 'passwd', 'self', 'errors', 'db_handle', 'us3pwentry',
                 'class_dir_p', 'class_dir_d', 'class_dir_l' ];
    ## Settings the old file had that the new contract has no home for. Values
    ## already carried are excluded, so each one is reported once.
    $unreviewed = array_diff( array_keys( $old_listen ), $template_vars, $runtime, $carried_keys );

    report( 'todo', "rewrite from the template, carrying " . implode( ', ', $carried_keys )
                    . ( $tz ? ", time zone $tz[2]" : '' ) . "; class_dir $class_dir" );
    foreach ( $unreviewed as $key ) {
        report( 'note', "old setting \$$key is not in the new contract and will not be carried; review it" );
    }

    if ( $apply && confirm( "Rewrite $listen_config from the template?" ) ) {
        ## write_file lints the result and restores the original if it is broken.
        write_file( $listen_config, $text );
        report( 'done', "$listen_config rewritten (original in " . backup_path( $listen_config ) . ")" );
    }
}

## ------------------------------------------------------------- 3. global_config.php

step( "3. global_config.php settings" );

if ( !is_file( $global_config ) ) {
    error_exit( "cannot read $global_config: no such file" );
}
$gc = config_vars( $global_config, $why );
if ( $gc === null ) {
    error_exit( "cannot read $global_config: " . reason( $why ) );
}

$managed = [];     ## PHP assignments for the managed block, in order
$clusters = is_array( $gc[ 'cluster_details' ] ?? null ) ? $gc[ 'cluster_details' ] : [];
$active   = array_filter( $clusters, function ( $c ) { return is_array( $c ) && ( $c[ 'active' ] ?? false ); } );

## Entries the Slurm code cannot submit to, left active from an older host: a
## metascheduler entry (one that fans out to a list of clusters) or one named
## for another scheduler. 'submittype' no longer exists in the submission code,
## so an active 'pbs' or 'http' entry is now treated as though it were Slurm.
## They are dropped from $active as well, so the checks below do not go looking
## for env_script_lines, nodes or SSH on a cluster that is being retired.
foreach ( $active as $name => $c ) {
    $retired = null;
    if ( isset( $c[ 'clusters' ] ) && is_array( $c[ 'clusters' ] ) ) {
        $retired = "it is a metascheduler entry, fanning out to " . implode( ', ', $c[ 'clusters' ] );
    } elseif ( isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) !== 'slurm' ) {
        $retired = "its submittype is '" . $c[ 'submittype' ] . "', which the Slurm code does not implement";
    }
    if ( $retired === null ) {
        continue;
    }
    report( 'todo', "deactivate cluster '$name': $retired" );
    $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = false;';
    unset( $active[ $name ] );
}
if ( $active ) {
    report( 'ok', count( $active ) . " active cluster(s) the Slurm code can use: " . implode( ', ', array_keys( $active ) ) );
} else {
    report( 'FAIL', "no active cluster is usable by the Slurm code; jobs would have nowhere to go" );
}

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
    ## There is no safe value to assume here, so --yes cannot answer it and an
    ## unattended run reports it rather than prompting.
    if ( $value === null && $apply && interactive() ) {
        $value = ask_yn( "Is this a single-institution appliance (single tenant)?",
                         'pass --single-tenant yes|no' );
    }
    if ( $value === null ) {
        report( 'todo', "set \$single_tenant_deployment (pass --single-tenant yes|no)" );
    } else {
        report( 'todo', "set \$single_tenant_deployment = " . var_export( $value, true ) );
        $managed[] = '$single_tenant_deployment = ' . var_export( $value, true ) . ';';
    }
} elseif ( $single_tenant !== null && (bool) $gc[ 'single_tenant_deployment' ] !== $single_tenant ) {
    report( 'todo', "change \$single_tenant_deployment to " . var_export( $single_tenant, true ) . " (--single-tenant)" );
    $managed[] = '$single_tenant_deployment = ' . var_export( $single_tenant, true ) . ';';
} else {
    report( 'ok', "\$single_tenant_deployment is " . var_export( (bool) $gc[ 'single_tenant_deployment' ], true ) );
}

## GUI requests name the host's own cluster 'localhost'
if ( !isset( $gc[ 'default_local_cluster' ] ) || !isset( $active[ $gc[ 'default_local_cluster' ] ] ) ) {
    $marked   = array_keys( array_filter( $active, function ( $c ) { return !empty( $c[ 'localhost' ] ); } ) );
    $proposal = $local_cluster ?? ( count( $marked ) === 1 ? $marked[ 0 ] : null );
    if ( $proposal !== null && !isset( $active[ $proposal ] ) ) {
        ## Writing the rest of the block around a bad cluster name would leave
        ## global_config.php naming a cluster that does not exist.
        fatal( "--local-cluster '$proposal' is not an active cluster (candidates: "
               . implode( ', ', array_keys( $active ) ) . ")" );
    } elseif ( $proposal === null ) {
        report( 'todo', "set \$default_local_cluster (pass --local-cluster; candidates: " . implode( ', ', array_keys( $active ) ) . ")" );
    } else {
        report( 'todo', "set \$default_local_cluster = '$proposal'" );
        $managed[] = '$default_local_cluster = ' . var_export( $proposal, true ) . ';';
    }
} elseif ( $local_cluster !== null && $local_cluster !== $gc[ 'default_local_cluster' ] ) {
    if ( !isset( $active[ $local_cluster ] ) ) {
        report( 'FAIL', "--local-cluster '$local_cluster' is not an active cluster" );
    } else {
        report( 'todo', "change \$default_local_cluster from '{$gc['default_local_cluster']}' to '$local_cluster' (--local-cluster)" );
        $managed[] = '$default_local_cluster = ' . var_export( $local_cluster, true ) . ';';
        $proposal  = $local_cluster;
    }
} else {
    report( 'ok', "\$default_local_cluster is '{$gc['default_local_cluster']}'" );
}
$host_cluster = $proposal ?? $gc[ 'default_local_cluster' ] ?? null;

## Every active cluster needs the env_script_lines key ('' when it needs no setup)
foreach ( $active as $name => $c ) {
    $given = isset( $env_values[ $name ] ) ? str_replace( '\n', "\n", $env_values[ $name ] ) : null;
    if ( array_key_exists( 'env_script_lines', $c ) ) {
        if ( $given !== null && $given !== $c[ 'env_script_lines' ] ) {
            report( 'todo', "change env_script_lines for $name (--env)" );
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'env_script_lines\' ] = ' . php_string( $given ) . ';';
        } else {
            report( 'ok', "$name has env_script_lines" );
        }
        continue;
    }
    $value = $given;
    if ( $value === null && $apply && interactive() ) {
        $value = readline( "env_script_lines for $name (modules/PATH setup; empty for none): " );
        ## At EOF readline() returns false, which must not be written as ''.
        if ( $value === false ) {
            error_exit( "input ended while waiting for $name's env_script_lines; nothing further was changed" );
        }
    }
    if ( $value === null ) {
        report( 'todo', "$name lacks env_script_lines (pass --env $name=... or --env $name=)" );
    } else {
        report( 'todo', "set env_script_lines for $name" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'env_script_lines\' ] = ' . php_string( $value ) . ';';
    }
}

## One-node appliances: resource sizing needs single_node to keep a job on one node,
## and no more tasks per node or per job than the node has CPUs.
foreach ( $active as $name => $c ) {
    if ( empty( $c[ 'single_node' ] ) && empty( $c[ 'localhost' ] ) && empty( $c[ 'fixed_capacity' ] ) ) {
        continue;
    }
    $login = $c[ 'login' ] ?? ( 'us3@' . ( $c[ 'name' ] ?? '' ) );
    $queue = $c[ 'queue' ] ?? '';
    $sinfo = 'sinfo -h -N -o "%N %c"' . ( $queue !== '' ? ' -p ' . escapeshellarg( $queue ) : '' ) . ' | sort -u';
    ## The host's own Slurm is queried locally: SSH to it is only set up in step 4.
    if ( $name !== $host_cluster ) {
        $sinfo = 'ssh -n -o BatchMode=yes -o ConnectTimeout=15 ' . escapeshellarg( $login ) . ' ' . escapeshellarg( $sinfo );
    }
    $rc    = run_as( 'us3', $sinfo, $sinfo_out, $sinfo_err );
    $nodes = [];
    foreach ( explode( "\n", $sinfo_out ) as $line ) {
        if ( preg_match( '/^(\S+)\s+(\d+)$/', trim( $line ), $m ) ) {
            $nodes[ $m[ 1 ] ] = (int) $m[ 2 ];
        }
    }
    if ( !$nodes ) {
        report( 'note', "$name: could not query its nodes with sinfo (exit $rc): "
                        . reason( $sinfo_err !== '' ? $sinfo_err : $sinfo_out )
                        . "; check single_node, ppn, ppbj and maxproc by hand" );
        continue;
    }
    if ( count( $nodes ) > 1 ) {
        report( 'ok', "$name has " . count( $nodes ) . " nodes; single_node not needed" );
        continue;
    }
    $cpus = reset( $nodes );
    if ( !array_key_exists( 'single_node', $c ) ) {
        report( 'todo', "set single_node for $name (its queue has one node)" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'single_node\' ] = true;';
    }
    foreach ( [ 'ppn', 'ppbj', 'maxproc' ] as $key ) {
        if ( isset( $c[ $key ] ) && (int) $c[ $key ] > $cpus ) {
            report( 'todo', "set $key for $name to $cpus, the CPUs on its one node (currently {$c[$key]})" );
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ ' . var_export( $key, true ) . ' ] = ' . $cpus . ';';
        }
    }
}

if ( $managed && $apply && confirm( "Write these settings to $global_config?" ) ) {
    $begin = "## BEGIN uslims_upgrade.php settings (rerun the script rather than editing by hand)";
    $end   = "## END uslims_upgrade.php settings";
    $text  = file_get_contents( $global_config );
    if ( $text === false ) {
        error_exit( "could not read $global_config" );
    }
    $settings = [];
    if ( preg_match( '/' . preg_quote( $begin, '/' ) . '\n(.*?)' . preg_quote( $end, '/' ) . '\n?/s', $text, $m ) ) {
        $settings = block_settings( $m[ 1 ] );
        $text     = str_replace( $m[ 0 ], '', $text );
    }
    ## A new value replaces the earlier line for the same setting.
    $settings = array_merge( $settings, block_settings( implode( "\n", $managed ) ) );
    $stripped = preg_replace( '/\?>\s*$/', '', rtrim( $text ) );
    if ( $stripped === null ) {
        error_exit( "could not rewrite $global_config: preg error " . preg_last_error() );
    }
    $text = $stripped . "\n\n$begin\n" . implode( "\n", $settings ) . "\n$end\n";
    ## write_file lints the result and restores the original if it is broken.
    write_file( $global_config, $text );
    report( 'done', "$global_config updated (original in " . backup_path( $global_config ) . ")" );
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
        $known  = $entry[ 'dir' ] . "/.ssh/known_hosts";
        $lookup = $port === 22 ? $host : "[$host]:$port";
        list( $o, $ferr, $found ) = capture( 'ssh-keygen -F ' . escapeshellarg( $lookup ) . ' -f ' . escapeshellarg( $known ) );
        if ( $found !== 0 && $ferr !== '' && !preg_match( '/No such file or directory/', $ferr ) ) {
            ## An unreadable known_hosts is not the same as a missing entry.
            report( 'FAIL', "$name: could not search $known: " . reason( $ferr ) );
            continue;
        }
        if ( $found !== 0 ) {
            list( $keys, $kerr, $krc ) = capture( 'ssh-keyscan -p ' . $port . ' ' . escapeshellarg( $host ) );
            if ( trim( $keys ) === '' ) {
                report( 'FAIL', "$name: no host key could be fetched from $host:$port (exit $krc): " . reason( $kerr ) );
                continue;
            }
            ## Fingerprint the keys just fetched, rather than fetching a second
            ## time, so the operator approves exactly what gets installed.
            $keyfile = tempnam( sys_get_temp_dir(), 'us3keys' );
            if ( $keyfile === false || file_put_contents( $keyfile, $keys . "\n" ) === false ) {
                report( 'FAIL', "$name: could not stage the fetched host keys for review" );
                @unlink( $keyfile );
                continue;
            }
            list( $fp, $fperr, $fprc ) = capture( 'ssh-keygen -lf ' . escapeshellarg( $keyfile ) );
            @unlink( $keyfile );
            if ( $fprc !== 0 ) {
                report( 'FAIL', "$name: could not fingerprint $host's host keys: " . reason( $fperr ) );
                continue;
            }
            report( 'todo', "$name: record $host's host key for $account:\n            "
                            . implode( "\n            ", explode( "\n", $fp ) ) );
            if ( $apply && confirm_host_keys( "Do these fingerprints match $host's real host keys?" ) ) {
                $dir = dirname( $known );
                if ( !is_dir( $dir ) && !@mkdir( $dir, 0700, true ) ) {
                    report( 'FAIL', "$name: could not create $dir" );
                    continue;
                }
                if ( file_put_contents( $known, $keys . "\n", FILE_APPEND ) === false ) {
                    report( 'FAIL', "$name: could not append $host's host key to $known" );
                    continue;
                }
                $owned = chown( $dir, $account ) && chown( $known, $account )
                         && chgrp( $known, $entry[ 'gid' ] ) && chmod( $dir, 0700 ) && chmod( $known, 0600 );
                $changes++;
                report( $owned ? 'done' : 'FAIL',
                        $owned ? "$name: $host's host key recorded for $account"
                               : "$name: $known was written but its owner or mode could not be set" );            }
        }
        $ssh = "ssh -n -p $port -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=yes " . escapeshellarg( $login ) . " true";
        $rc  = run_as( $account, $ssh, $ssh_out, $ssh_err );
        if ( $rc !== 0 && $name === $host_cluster ) {
            report( 'todo', "$name: authorize $account's key for $login on this host" );
            if ( $apply && confirm( "Set up $account's SSH key for $login?" ) ) {
                if ( authorize_local_key( $account, $entry, explode( '@', $login )[ 0 ] ) ) {
                    $changes++;
                    $rc = run_as( $account, $ssh, $ssh_out, $ssh_err );
                    report( $rc === 0 ? 'done' : 'FAIL', "$name: $account's key authorized for $login" );
                } else {
                    report( 'FAIL', "$name: could not authorize $account's key for $login" );
                }            }
        }
        report( $rc === 0 ? 'ok' : 'FAIL', "$name: $account can ssh to $login"
                . ( $rc === 0 ? '' : " (exit $rc: install the account's key, including for the host itself) "
                                     . reason( $ssh_err !== '' ? $ssh_err : $ssh_out ) ) );
    }
}

## ------------------------------------------------------------- 5. breaker directory

step( "5. Circuit-breaker directory" );

$web_group_entry = posix_getgrnam( $web_group );
if ( $web_group_entry === false ) {
    error_exit( "no '$web_group' group on this host" );
}
$want_gid = $web_group_entry[ 'gid' ];
if ( is_dir( $breaker_dir ) && !is_link( $breaker_dir )
     && fileowner( $breaker_dir ) === $us3_entry[ 'uid' ] && filegroup( $breaker_dir ) === $want_gid
     && ( fileperms( $breaker_dir ) & 07777 ) === 02770 ) {
    report( 'ok', "$breaker_dir is 2770 us3:$web_group" );
} else {
    report( 'todo', "create $breaker_dir as 2770 us3:$web_group" );
    if ( $apply ) {
        if ( is_link( $breaker_dir ) ) {
            report( 'FAIL', "$breaker_dir is a symlink; remove it by hand and rerun" );
        } elseif ( !is_dir( $breaker_dir ) && !@mkdir( $breaker_dir, 02770, true ) ) {
            $last = error_get_last();
            report( 'FAIL', "could not create $breaker_dir: " . reason( is_array( $last ) ? $last[ 'message' ] : '' ) );
        } else {
            $changes++;
            ## mkdir's mode is masked by the umask, so set owner and mode explicitly.
            $set = chown( $breaker_dir, 'us3' ) && chgrp( $breaker_dir, $web_group ) && chmod( $breaker_dir, 02770 );
            report( $set ? 'done' : 'FAIL',
                    $set ? "$breaker_dir created as 2770 us3:$web_group"
                         : "$breaker_dir exists but its owner, group or mode could not be set" );
        }
    }
}

## ------------------------------------------------------------- 6. crontabs

step( "6. Crontabs" );

$old_crons = old_controller_crontabs( $cron_error );
if ( $cron_error !== '' ) {
    report( 'FAIL', "could not read the us3 crontab: " . reason( $cron_error ) );
}
foreach ( $old_crons as $where => $text ) {
    $label = $where === 'us3' ? 'us3 crontab' : $where;
    report( 'todo', "$label: replace gridctl_pro.php / gridctl_dev.php with gridctl.php" );
    if ( !$apply || !confirm( "Update the $label?" ) ) {
        continue;
    }
    if ( $where === 'us3' ) {
        ## Keep the old crontab under a name that says what it is.
        $saved = backup_path( 'us3.crontab' );
        ## Same missing trailing newline: keep the backup loadable by "crontab -".
        if ( file_put_contents( $saved, rtrim( $text, "\n" ) . "\n" ) === false ) {
            error_exit( "could not save the current us3 crontab to $saved" );
        }
        echo "Original us3 crontab backed up in to $saved\n";
        $tmp = tempnam( sys_get_temp_dir(), 'us3cron' );
        if ( $tmp === false || file_put_contents( $tmp, fix_crontab( $text ) ) === false ) {
            error_exit( "could not stage the new us3 crontab" );
        }
        list( $o, $cwerr, $rc ) = capture( 'crontab -u us3 ' . escapeshellarg( $tmp ) );
        unlink( $tmp );
        if ( $rc === 0 ) {
            $changes++;
            list( $now_tab, $rb_err, $rb_rc ) = capture( 'crontab -l -u us3' );
            $lost = $rb_rc === 0 ? crontab_lines_lost( $text, $now_tab ) : [];
            if ( $rb_rc !== 0 ) {
                report( 'FAIL', "us3 crontab was written but could not be read back: " . reason( $rb_err ) );
            } elseif ( $lost ) {
                report( 'FAIL', "us3 crontab lost " . count( $lost ) . " unrelated line(s); the original is in"
                                . " $saved: " . reason( implode( ' | ', $lost ) ) );
            } else {
                report( 'done', "us3 crontab updated, its other entries intact" );
            }
        } else {
            report( 'FAIL', "us3 crontab was not updated (exit $rc): " . reason( $cwerr . ' ' . $o )
                            . "; the original is in $saved" );
        }
    } else {
        ## A crontab is not PHP, so this write must not be lint-checked.
        write_file( $where, fix_crontab( $text ), false );
        $lost = crontab_lines_lost( $text, (string) @file_get_contents( $where ) );
        report( $lost ? 'FAIL' : 'done',
                $lost ? "$where lost " . count( $lost ) . " unrelated line(s); the original is in "
                        . backup_path( $where ) . ": " . reason( implode( ' | ', $lost ) )
                      : "$where updated, its other entries intact (original in " . backup_path( $where ) . ")" );
    }
}
if ( !$old_crons ) {
    report( 'ok', "no crontab entry calls gridctl_pro.php or gridctl_dev.php" );
}

## ------------------------------------------------------------- 7. jobmonitors

## A jobmonitor started before the upgrade runs the old code, which has no
## cleanup claim: cleanup_claim_acquire() arrived with the Slurm change. The
## claim therefore excludes nothing for it, and job_cleanup() imports with plain
## INSERTs, so it can import a job's results a second time alongside the new
## sweep. Restarting the services does not reach these processes, because
## services.php manages listen only.
step( "7. Jobmonitors from before the upgrade" );

## Re-read the list: steps 2-6 stop for confirmations, so this is minutes after
## the preflight and a job can have moved on since.
$old_jms    = array_filter( jobmonitors( marker_time( $marker_file ), $jm_error ),
                            function ( $j ) { return $j[ 'old' ]; } );
$killed_jms = [];

if ( $jm_error !== '' ) {
    report( 'FAIL', "could not list the running jobmonitors: " . reason( $jm_error ) );
} elseif ( !$old_jms ) {
    ## The usual case on a rerun: nothing predates the upgrade, so nothing to do.
    report( 'ok', "no jobmonitor from before the upgrade" );
    if ( $apply && marker_time( $marker_file ) === null ) {
        ## Nothing to replace, so stamp the marker now: without it a later run
        ## would read an unstamped host as having only pre-upgrade monitors.
        report( write_marker( $marker_file ) ? 'done' : 'FAIL', "recorded the upgrade in $marker_file" );
        $changes++;
    }
} else {
    report( 'todo', count( $old_jms ) . " jobmonitor(s) predate the upgrade and hold no cleanup claim; "
                    . "replace them (pid " . implode( ', ', array_keys( $old_jms ) ) . ")" );
    if ( $apply && confirm( "Replace " . count( $old_jms ) . " old jobmonitor(s)?" ) ) {
        ## Stamp the marker before anything is replaced, so the monitors started
        ## by the restart below count as new and a rerun has nothing to do. Any
        ## monitor left running here started earlier, so it still reads as old.
        if ( !write_marker( $marker_file ) ) {
            fatal( "could not record the upgrade in $marker_file; no jobmonitor was touched" );
        }
        $changes++;

        ## Check each monitor's own job, not the host as a whole: a monitor
        ## still polling the cluster has imported nothing and is safe to
        ## replace, while one that has moved on is mid-import and must be left
        ## alone. Killing that one is the double import this guards against.
        $safe = [];
        foreach ( $old_jms as $pid => $jm ) {
            $status = job_status( $gdb, $jm[ 'db' ], $jm[ 'gfacID' ] );
            if ( $status === null ) {
                report( 'FAIL', "could not read gfac.analysis for job {$jm['gfacID']}: "
                                . reason( mysqli_error( $gdb ) ) . "; pid $pid left alone" );
            } elseif ( $status === 'SUBMITTED' || $status === 'RUNNING' ) {
                ## Still polling the cluster, so it has imported nothing yet.
                $safe[ $pid ] = $jm;
            } elseif ( $status === '' ) {
                ## The row is gone but the monitor is alive, so it is importing
                ## right now: the code it is running, 4.2.0's
                ## jobmonitor/cleanup.php, deletes the gfac.analysis row (lines
                ## 305/344) before it writes the results (455-571: noise,
                ## pcsa_modelrecs, model, modelPerson, HPCAnalysisResultData)
                ## and emails the user. The new code does the same in
                ## jobmonitor/cleanup_job.php. Leaving it is
                ## both safe and necessary. Safe because with no row neither the
                ## sweep nor a respawned monitor will touch the job, so it cannot
                ## double-import; necessary because killing it would leave a
                ## partial import that nothing repairs: --restart respawns
                ## nothing without a row, and the user is never emailed.
                report( 'note', "job {$jm['gfacID']}: pre-upgrade monitor pid $pid is finishing its"
                                . " import; left running" );
            } else {
                report( 'FAIL', "job {$jm['gfacID']} is collecting or importing results (status $status);"
                                . " pid $pid was left running. Rerun when it finishes" );
            }
        }

        foreach ( array_keys( $safe ) as $pid ) {
            @posix_kill( $pid, SIGTERM );
        }
        ## jobmonitor installs no SIGTERM handler, so it ends at once; the wait
        ## and the SIGKILL only cover a process that somehow ignores it.
        if ( $safe ) {
            sleep( 2 );
            foreach ( array_keys( $safe ) as $pid ) {
                if ( @posix_kill( $pid, 0 ) ) {
                    @posix_kill( $pid, SIGKILL );
                }
            }
            $killed_jms = array_keys( $safe );
            $changes++;
            report( 'done', count( $safe ) . " polling jobmonitor(s) stopped (pid "
                            . implode( ', ', $killed_jms ) . ")" );
            ## uslims_jobs.php respawns a monitor only for a gfac.analysis row
            ## with no live monitor, and it must run as us3, which owns them.
            ## jobmonitor double-forks and closes stdio, so this returns.
            $jobs = __DIR__ . '/uslims_jobs.php';
            if ( !is_file( $jobs ) ) {
                report( 'FAIL', "cannot restart jobmonitors: $jobs not found" );
            } else {
                $rc = run_as( 'us3', escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $jobs ) . ' --restart',
                              $jm_out, $jm_err );
                report( $rc === 0 ? 'done' : 'FAIL', "jobmonitors restarted under the new code"
                        . ( $rc === 0 ? '' : " (exit $rc): " . reason( $jm_err !== '' ? $jm_err : $jm_out ) ) );
            }
        }
    }
}

## ------------------------------------------------------------- 8. Content-Security-Policy

## The pages are written for util/csp's policy. It goes in Report-Only, which
## blocks nothing and logs each violation through /csp-report.php; enforcing it is
## a later, deliberate change once that log is quiet (util/csp/README.md).
step( "8. Content-Security-Policy (Report-Only)" );

$csp_policy = __DIR__ . '/util/csp/csp-report-only.conf';
if ( is_dir( '/etc/httpd/conf.d' ) ) {
    $apache = [ 'root' => '/etc/httpd', 'conf' => '/etc/httpd/conf.d/lims-csp.conf',
                'enable' => '', 'disable' => '', 'service' => 'httpd' ];
} elseif ( is_dir( '/etc/apache2/conf-available' ) ) {
    $apache = [ 'root' => '/etc/apache2', 'conf' => '/etc/apache2/conf-available/lims-csp.conf',
                'enable' => 'a2enconf -q lims-csp', 'disable' => 'a2disconf -q lims-csp', 'service' => 'apache2' ];
} else {
    $apache = null;
}

if ( $apache === null ) {
    report( 'FAIL', "no Apache configuration directory (/etc/httpd/conf.d or /etc/apache2/conf-available)" );
} elseif ( !is_file( $csp_policy ) ) {
    report( 'FAIL', "policy not found: $csp_policy (update dbutils first)" );
} else {
    ## Any existing policy, Report-Only or enforced (an Ansible install enforces), is left as is.
    list( $csp_found ) = capture( 'grep -rlI Content-Security-Policy ' . escapeshellarg( $apache[ 'root' ] ) );
    $csp_found = array_filter( explode( "\n", trim( $csp_found ) ) );
    if ( $csp_found ) {
        report( 'ok', "a Content-Security-Policy is already configured (" . implode( ', ', $csp_found ) . "); left as is" );
    } else {
        report( 'todo', "install the Report-Only policy for $wwwpath as {$apache['conf']}" );
        if ( $apply && confirm( "Install the Report-Only Content-Security-Policy and reload {$apache['service']}?" ) ) {
            write_file( $apache[ 'conf' ], "## Installed by uslims_upgrade.php from util/csp/csp-report-only.conf.\n"
                        . "<Directory \"$wwwpath\">\n" . file_get_contents( $csp_policy ) . "\n</Directory>\n", false );
            if ( $apache[ 'enable' ] !== '' ) {
                capture( $apache[ 'enable' ] );
            }
            list( $t_out, $t_err, $t_rc ) = capture( 'apachectl configtest' );
            if ( $t_rc !== 0 ) {
                if ( $apache[ 'disable' ] !== '' ) {
                    capture( $apache[ 'disable' ] );
                }
                @unlink( $apache[ 'conf' ] );
                report( 'FAIL', "Apache rejected the policy, so it was removed: " . reason( $t_err !== '' ? $t_err : $t_out ) );
            } else {
                list( $r_out, $r_err, $r_rc ) = capture( 'systemctl reload ' . $apache[ 'service' ] );
                report( $r_rc === 0 ? 'done' : 'FAIL', "Report-Only policy installed"
                        . ( $r_rc === 0 ? "; {$apache['service']} reloaded" : "; reload {$apache['service']} by hand: " . reason( $r_err ) ) );
            }
        }
    }
    list( $mods ) = capture( 'apachectl -M' );
    if ( strpos( $mods, 'headers_module' ) === false ) {
        report( 'note', "mod_headers is not loaded, so Apache sends no CSP header; enable it" );
    }
}

## ------------------------------------------------------------- 9. verify

step( "9. Verify" );

## These checks describe the upgraded host, so on a dry run they would all fail
## by construction. Their outcome is only meaningful once the changes are in.
if ( !$apply ) {
    report( 'skip', "verification runs with --apply (a dry run has not changed anything yet)" );
} else {
    $new_listen = config_vars( $listen_config, $why );
    $version    = $new_listen[ 'listen_config_version' ] ?? 0;
    if ( !lint_ok( $listen_config, $lint_why ) ) {
        report( 'FAIL', "listen-config.php does not parse: " . reason( $lint_why ) );
    } elseif ( $new_listen === null ) {
        report( 'FAIL', "listen-config.php parses but could not be read: " . reason( $why ) );
    } else {
        report( $version >= 2 ? 'ok' : 'FAIL', "listen-config.php parses and is version 2"
                . ( $version >= 2 ? '' : " (it is version $version)" ) );
    }

    report( lint_ok( $global_config, $gc_why ) ? 'ok' : 'FAIL',
            "global_config.php parses" . ( isset( $gc_why ) && $gc_why !== '' ? ': ' . reason( $gc_why ) : '' ) );

    $probe = '$us3bin = ' . var_export( $us3bin, true ) . '; require ' . var_export( "$gridctl_dir/gridctl_bootstrap.php", true )
           . '; echo function_exists( "write_log" ) ? "ok" : "missing";';
    run_as( 'us3', escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $probe ), $probe_out, $probe_err );
    report( $probe_out === 'ok' ? 'ok' : 'FAIL', "gridctl loads its configuration as us3"
            . ( $probe_out === 'ok' ? '' : ": " . reason( $probe_err !== '' ? $probe_err : $probe_out ) ) );

    ## Nothing should still reach the old controllers after step 6.
    $left = array_keys( old_controller_crontabs() );
    report( $left ? 'FAIL' : 'ok', "no crontab calls gridctl_pro.php or gridctl_dev.php"
            . ( $left ? ': ' . implode( ', ', $left ) : '' ) );

    ## The gridctl probe above covers us3 reading listen-config.php; the web
    ## tier reading global_config.php is the path nothing else exercises.
    $rc = run_as( $web_user, 'test -r ' . escapeshellarg( $global_config ), $g_out, $g_err );
    $gc_stat = @stat( $global_config );
    report( $rc === 0 ? 'ok' : 'FAIL', "$web_user can read global_config.php"
            . ( $rc === 0 ? '' : ( $gc_stat ? ' (' . owner_of( $gc_stat ) . ')' : '' )
                                . ' ' . reason( $g_err !== '' ? $g_err : $g_out ) ) );

    ## Any pre-upgrade monitor still alive can still double-import.
    $survivors = array_values( array_filter( $killed_jms, function ( $pid ) { return @posix_kill( $pid, 0 ); } ) );
    report( $survivors ? 'FAIL' : 'ok', "no jobmonitor from before the upgrade is still running"
            . ( $survivors ? ': pid ' . implode( ', ', $survivors ) : '' ) );

    $rc = run_as( $web_user, 'test -w ' . escapeshellarg( $breaker_dir ), $w_out, $w_err );
    report( $rc === 0 ? 'ok' : 'FAIL', "$web_user can write the breaker directory"
            . ( $rc === 0 ? '' : ' ' . reason( $w_err !== '' ? $w_err : $w_out ) ) );
}

echo "\n";
echoline( '=' );
if ( !$apply ) {
    echo "Dry run: nothing was changed. "
       . ( $pending ? "$pending change(s) would be made; rerun with --apply.\n" : "Nothing to change.\n" );
} else {
    echo "$changes change(s) made.\n";
}
echo $failures ? "$failures check(s) FAILED.\n" : "All checks passed.\n";
if ( $apply && $changes ) {
    echo "Restart the gridctl services: cd $us3bin && php services.php restart\n";
    if ( !$killed_jms ) {
        ## services.php does not manage jobmonitors, so say so rather than let
        ## the line above read as covering them.
        echo "Jobmonitors are separate processes: if any were running from before"
           . " the upgrade, kill them and run " . __DIR__ . "/uslims_jobs.php --restart as us3.\n";
    }
}
## A dry run with work outstanding is not a failure, so it exits 0; only a real
## problem exits non-zero, which is what automation reads.
exit( $failures ? 1 : 0 );
