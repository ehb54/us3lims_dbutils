<?php

# Upgrade an existing LIMS host to the Slurm submission contract.
#
# Host configuration only. The stack code and the database schema are upgraded
# separately; the code must already be in place, since step 2 reads gridctl's
# template and step 7 installs dbutils' policy. Step 0 refuses to run otherwise.
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

The host must also be idle. Stop the services with "php services.php stop", let the
queues drain, and comment out the LIMS cron entries first; step 1 checks all of this and
refuses rather than work around a running system.

Steps

0 : every stack checkout is at $required_version or newer (read from each repository's VERSION)
1 : preflight. The host must be idle: us3-listen stopped, no jobmonitor, nothing from
    /opt/ultrascan3/bin, no unfinished job in gfac.analysis, no cleanup claim, an empty
    local Slurm queue, no other client running a statement on MariaDB, and the LIMS cron
    entries commented out
2 : rewrites listen-config.php from gridctl's template, carrying the site's values
3 : deactivates clusters the Slurm code cannot submit to, then sets the global_config.php
    settings the new code requires (queue time, tenant scope, local cluster,
    env_script_lines per cluster, single_node on one-node appliances)
4 : records each cluster's host key and checks ssh for us3 and the web account
5 : creates the shared circuit-breaker directory
6 : removes the gridctl cron entries (gridctl.php, and the gridctl_pro/dev names before it)
7 : installs the Content-Security-Policy as Report-Only unless a policy is already
    configured; enforcing it is a later step
8 : verifies the result

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

## A cron line driving gridctl, under any of its names. A commented one counts
## too: step 1 has the operator comment the LIMS entries out for the upgrade, so
## skipping comments here would leave the entry behind for them to re-enable
## afterwards, putting the sweep back.
function gridctl_cron_line( $line ) {
    return (bool) preg_match( '/gridctl(_pro|_dev)?\.php/', $line );
}

## gridctl is no longer driven from cron at all: each job's own jobmonitor carries
## it to a terminal state, so a periodic controller has nothing left to sweep.
## The entries are removed rather than renamed to gridctl.php.
function fix_crontab( $text ) {
    $out = [];
    foreach ( explode( "\n", $text ) as $line ) {
        if ( gridctl_cron_line( $line ) ) {
            continue;
        }
        $out[] = $line;
    }
    ## crontab(1) rejects a file whose last line has no newline. Normalize to one,
    ## whatever the source did: capture()'s array form drops it, and an /etc file
    ## may or may not have it.
    return rtrim( implode( "\n", $out ), "\n" ) . "\n";
}

## fix_crontab() removes only the gridctl cron entries, so every other line must
## survive. /etc/crontab carries
## the nightly backup ("cronic php .../uslims_daily_backup.php"), and a line lost
## here would stop the backups without saying so.
function crontab_lines_lost( $before, $after ) {
    $expected = [];
    foreach ( explode( "\n", $before ) as $line ) {
        if ( trim( $line ) !== '' && !gridctl_cron_line( $line ) ) {
            $expected[] = $line;
        }
    }
    return array_values( array_diff( $expected, explode( "\n", $after ) ) );
}

## Crontabs that still carry a gridctl entry, active or commented: us3's own,
## then system files.
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
        foreach ( explode( "\n", $text ) as $line ) {
            if ( gridctl_cron_line( $line ) ) {
                $found[ $where ] = $text;
                break;
            }
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
## Running processes whose "pid args" line matches $regex, as [ pid => line ].
## Returns null when the process list could not be read, which is not the same as
## nothing matching. This script's own process and its parent are never included.
function processes( $regex ) {
    list( $out, $err, $rc ) = capture( 'ps -eo pid=,args=' );
    if ( $rc !== 0 ) {
        return null;
    }
    $mine  = [ getmypid(), posix_getppid() ];
    $found = [];
    foreach ( explode( "\n", $out ) as $line ) {
        if ( !preg_match( $regex, $line, $m ) ) {
            continue;
        }
        $pid = (int) $m[ 1 ];
        if ( in_array( $pid, $mine, true ) ) {
            continue;
        }
        $found[ $pid ] = trim( $line );
    }
    return $found;
}

## The running jobmonitors, each as [ pid, db, gfacID ]. $error is set when the
## process list could not be read, which must not read as "none running".
function jobmonitors( &$error = null ) {
    $error = '';
    ## Match the launched form only: a php binary followed by the monitor's own
    ## path, then its two arguments. 4.2.0 starts it under "nice -15", which
    ## execs php, so the process still shows php as argv[0]. A looser match would
    ## catch anything merely naming the script.
    $found = processes( '#^\s*(\d+)\s+\S*php[0-9.]*\s+\S*jobmonitor/jobmonitor\.php\s+(\S+)\s+(\S+)#' );
    if ( $found === null ) {
        $error = 'ps failed';
        return [];
    }
    $jms = [];
    foreach ( $found as $pid => $line ) {
        preg_match( '#jobmonitor/jobmonitor\.php\s+(\S+)\s+(\S+)#', $line, $m );
        $jms[ $pid ] = [ 'pid' => $pid, 'db' => $m[ 1 ], 'gfacID' => $m[ 2 ] ];
    }
    return $jms;
}

## Cleanup claims that are still live: taken within the hour, or whose owning
## process is still alive. An older claim with a dead owner was abandoned.
function cleanup_claims( $us3_home ) {
    $claims = [];
    foreach ( glob( "$us3_home/lims/etc/joblog/*/*/cleanup.claim" ) ?: [] as $claim ) {
        $owner = (int) @file_get_contents( "$claim/owner" );
        $age   = time() - (int) @filemtime( $claim );
        if ( $age < 3600 || ( $owner && @posix_kill( $owner, 0 ) ) ) {
            $claims[] = basename( dirname( $claim ) ) . ( $owner ? " (pid $owner)" : '' );
        }
    }
    return $claims;
}

## LIMS cron entries that are still live, as [ where => lines ]. Keys are 'us3'
## or a file path. An upgrade needs these commented out: left running, cron
## restarts the listener or a controller partway through.
function live_lims_crontabs() {
    ## The LIMS scripts cron drives. gridctl_pro/dev are the pre-upgrade names.
    $lims = '#(gridctl(_pro|_dev)?\.php|cluster_status\.php|update_notice\.php|listen\.php'
            . '|jobmonitor\.php|uslims_daily_backup\.php|uslims_daily_rsync\.php|save-jobstats\.sh)#';
    list( $us3_tab, $err, $rc ) = capture( 'crontab -l -u us3' );
    $tabs = [ 'us3' => $rc === 0 ? $us3_tab : '' ];
    foreach ( array_merge( [ '/etc/crontab' ], glob( '/etc/cron.d/*' ) ?: [] ) as $file ) {
        $tabs[ $file ] = is_file( $file ) ? (string) @file_get_contents( $file ) : '';
    }
    $live = [];
    foreach ( $tabs as $where => $text ) {
        foreach ( explode( "\n", $text ) as $line ) {
            ## A commented entry is exactly what the operator is asked to leave.
            if ( preg_match( '/^\s*#/', $line ) || trim( $line ) === '' ) {
                continue;
            }
            if ( preg_match( $lims, $line ) ) {
                $live[ $where ][] = trim( $line );
            }
        }
    }
    return $live;
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

## An upgrade runs on an idle host, so every check below has to agree that
## nothing is running. Handling live jobs was the earlier design; with the
## gridctl.php sweep gone and no claimless pre-upgrade monitor left to guard
## against, an idle host is the only state worth reasoning about, and it is the
## state an operator can actually confirm. Nothing is changed until all of these
## pass.
step( "1. Preflight: the host must be idle" );

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

## Each failing check adds a line here; they are all reported before the script
## stops, so one run tells the operator everything to quiet down.
$busy = [];

## -- the gridctl services. services.php manages listen only, so that is what
## -- "stopped" means; the jobmonitors are checked separately below.
$listeners = processes( '#^\s*(\d+)\s+\S*php[0-9.]*\s+\S*/listen\.php(\s|$)#' );
if ( $listeners === null ) {
    report( 'FAIL', "could not read the process list, so the host cannot be shown to be idle" );
    $busy[] = 'process list unreadable';
} elseif ( $listeners ) {
    report( 'FAIL', "us3-listen is still running (pid " . implode( ', ', array_keys( $listeners ) )
                    . "); stop it with: cd $us3bin && php services.php stop" );
    $busy[] = 'us3-listen running';
} else {
    report( 'ok', "us3-listen is stopped" );
}

## -- jobmonitors. One per job on the new contract, none on an idle host.
$monitors = jobmonitors( $jm_error );
if ( $jm_error !== '' ) {
    report( 'FAIL', "could not list the running jobmonitors: " . reason( $jm_error ) );
    $busy[] = 'jobmonitors unreadable';
} elseif ( $monitors ) {
    foreach ( $monitors as $pid => $jm ) {
        report( 'FAIL', "jobmonitor pid $pid is still running for job {$jm['gfacID']} ({$jm['db']})" );
    }
    $busy[] = count( $monitors ) . ' jobmonitor(s) running';
} else {
    report( 'ok', "no jobmonitor is running" );
}

## -- analysis binaries. A job running outside Slurm's view still writes results.
$analysis = processes( '#^\s*(\d+)\s+/opt/ultrascan3/bin/#' );
if ( $analysis ) {
    report( 'FAIL', "an UltraScan binary is running from /opt/ultrascan3/bin (pid "
                    . implode( ', ', array_keys( $analysis ) ) . ")" );
    $busy[] = 'analysis binary running';
} elseif ( $analysis !== null ) {
    report( 'ok', "nothing is running from /opt/ultrascan3/bin" );
}

## -- gfac.analysis. A finished cleanup deletes the row, so any row outside the
## -- terminal statuses is a job still in play. Terminal statuses are listed
## -- rather than active ones, so a status this script does not know about counts
## -- as busy and errs toward refusing.
$res = mysqli_query( $gdb, <<<'SQL'
SELECT SUM( status IS NULL
            OR status NOT IN ( 'COMPLETE', 'CANCELLED', 'CANCELED', 'FAILED', 'FAILED_DATA',
                               'ERROR', 'SUBMIT_TIMEOUT', 'RUN_TIMEOUT', 'DATA_TIMEOUT' ) ) AS unfinished,
       SUM( status IN ( 'SUBMIT_TIMEOUT', 'RUN_TIMEOUT', 'DATA_TIMEOUT' ) )                  AS timed_out
  FROM analysis
SQL
);
if ( !$res ) {
    report( 'FAIL', "could not query gfac.analysis: " . mysqli_error( $gdb ) );
    $busy[] = 'gfac.analysis unreadable';
} else {
    $row        = mysqli_fetch_assoc( $res );
    $unfinished = (int) $row[ 'unfinished' ];
    ## A *_TIMEOUT row is counted terminal here on purpose, but it is not a
    ## finished job: its monitor is meant to re-enter the second window and
    ## escalate it. On an idle host no monitor is running, and the old code could
    ## not progress one anyway, so blocking on these would stop any host that has
    ## ever stranded a job. They are reported instead, to be picked up afterwards.
    if ( (int) $row[ 'timed_out' ] ) {
        report( 'note', $row[ 'timed_out' ] . " job(s) are parked at SUBMIT_TIMEOUT, RUN_TIMEOUT or"
                        . " DATA_TIMEOUT with no monitor. They do not block the upgrade; once it is"
                        . " done, restart their monitors with uslims_jobs.php --restart as us3 so the"
                        . " new code can finish or fail them" );
    }
    if ( $unfinished ) {
        report( 'FAIL', "$unfinished job(s) in gfac.analysis are not in a terminal status;"
                        . " let them finish or cancel them, then rerun" );
        $busy[] = "$unfinished unfinished job(s)";
    } else {
        report( 'ok', "no unfinished job in gfac.analysis" );
    }
}

## -- cleanup claims. A live claim means a cleanup is mid-import.
$claims = cleanup_claims( $us3_home );
if ( $claims ) {
    report( 'FAIL', count( $claims ) . " job(s) hold a cleanup claim: " . implode( ', ', $claims ) );
    $busy[] = 'cleanup claim held';
} else {
    report( 'ok', "no job holds a cleanup claim" );
}

## -- the cluster's own queue. Only the local Slurm can be asked here: SSH to a
## -- remote cluster is not set up until step 4, so a host whose clusters are all
## -- remote is told to check them by hand rather than given a false pass.
list( $sq_out, $sq_err, $sq_rc ) = capture( 'command -v squeue' );
if ( $sq_rc !== 0 ) {
    report( 'note', "squeue is not on this host, so its clusters could not be checked here;"
                    . " confirm sinfo and squeue are idle on each cluster before continuing" );
} else {
    $rc = run_as( 'us3', 'squeue -h -o %i', $q_out, $q_err );
    if ( $rc !== 0 ) {
        report( 'FAIL', "squeue failed, so the local queue cannot be shown to be idle: "
                        . reason( $q_err !== '' ? $q_err : $q_out ) );
        $busy[] = 'squeue failed';
    } elseif ( trim( $q_out ) !== '' ) {
        $jobs = count( array_filter( explode( "\n", trim( $q_out ) ) ) );
        report( 'FAIL', "$jobs job(s) are still in the local Slurm queue" );
        $busy[] = "$jobs job(s) queued";
    } else {
        report( 'ok', "the local Slurm queue is empty" );
    }
}

## -- other database clients. A sleeping connection is a pool, not a user; one
## -- running a statement means the system is in use.
$res = mysqli_query( $gdb, 'SHOW PROCESSLIST' );
if ( !$res ) {
    report( 'note', "could not list the database connections (" . reason( mysqli_error( $gdb ) )
                    . "); confirm nobody is using MariaDB" );
} else {
    $others = [];
    while ( $row = mysqli_fetch_assoc( $res ) ) {
        if ( strcasecmp( (string) $row[ 'Command' ], 'Sleep' ) === 0 ) {
            continue;
        }
        ## This script's own connection is running SHOW PROCESSLIST right now.
        if ( stripos( (string) $row[ 'Info' ], 'PROCESSLIST' ) !== false ) {
            continue;
        }
        $others[] = $row[ 'User' ] . '@' . preg_replace( '/:\d+$/', '', (string) $row[ 'Host' ] );
    }
    if ( $others ) {
        report( 'FAIL', count( $others ) . " active database connection(s): "
                        . implode( ', ', array_unique( $others ) ) );
        $busy[] = 'database in use';
    } else {
        report( 'ok', "no other client is running a statement on MariaDB" );
    }
}

## -- the LIMS cron entries. Left live, cron restarts the very things above
## -- partway through the upgrade.
$live_crons = live_lims_crontabs();
if ( $live_crons ) {
    foreach ( $live_crons as $where => $lines ) {
        report( 'FAIL', "$where still has " . count( $lines ) . " active LIMS cron entr"
                        . ( count( $lines ) === 1 ? 'y' : 'ies' ) . ": " . reason( implode( ' | ', $lines ) ) );
    }
    $busy[] = 'LIMS cron entries active';
} else {
    report( 'ok', "no LIMS cron entry is active" );
}

if ( $busy ) {
    error_exit( "the host is not idle (" . implode( '; ', $busy ) . ").\n"
                . "Quiet it down and rerun. Nothing was changed" );
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
    report( 'todo', "$label: remove the gridctl cron entries; each job's jobmonitor finishes its own job now" );
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
    report( 'ok', "no crontab drives gridctl" );
}

## ------------------------------------------------------------- 7. verify

step( "7. Verify" );

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
    report( $left ? 'FAIL' : 'ok', "no crontab drives gridctl"
            . ( $left ? ': ' . implode( ', ', $left ) : '' ) );

    ## The gridctl probe above covers us3 reading listen-config.php; the web
    ## tier reading global_config.php is the path nothing else exercises.
    $rc = run_as( $web_user, 'test -r ' . escapeshellarg( $global_config ), $g_out, $g_err );
    $gc_stat = @stat( $global_config );
    report( $rc === 0 ? 'ok' : 'FAIL', "$web_user can read global_config.php"
            . ( $rc === 0 ? '' : ( $gc_stat ? ' (' . owner_of( $gc_stat ) . ')' : '' )
                                . ' ' . reason( $g_err !== '' ? $g_err : $g_out ) ) );

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
if ( $failures ) {
    echo "$failures check(s) FAILED.\n";
} elseif ( $apply && $pending ) {
    echo "All checks passed, but $pending item(s) still need attention above.\n";
} else {
    echo "All checks passed.\n";
}
if ( $apply && $changes ) {
    ## The preflight required an idle host, so this is a start, not a restart, and
    ## it runs as us3: started as root the services would leave root-owned state.
    echo "\nFinish the upgrade in this order:\n"
       . "  1. re-enable the LIMS cron entries that were commented out for the upgrade\n"
       . "  2. start the services as us3:   sudo -u us3 bash -c 'cd $us3bin && php services.php start'\n"
       . "  3. refresh the cluster health table once, so no cluster shows as stale:\n"
       . "     sudo -u us3 php $gridctl_dir/cluster_status.php\n";
}
## A dry run with work outstanding is not a failure, so it exits 0. Under --apply
## an outstanding item is work that was asked for and not done, so it exits
## non-zero along with any real failure, which is what automation reads.
exit( $failures || ( $apply && $pending ) ? 1 : 0 );
