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
Without --apply nothing is changed; each step reports what it would do. Run with --check
for that dry run; with no options at all this help is printed instead.

This script changes only this host's configuration. The stack code and the database
schema are upgraded separately, and the code must be upgraded first: pull common,
every instance, gridctl and dbutils to $required_version or newer, then run this as root.
Step 0 refuses to go further while any checkout is older.

Pull the code in two steps, not per use and not with "all" (which also pulls the
UltraScan gui and mpi checkouts, neither of which this upgrade touches):

    php uslims_git_info.php --update-pull util
    php uslims_git_info.php --update-pull lims

"--update-pull util" alone upgrades dbutils and leaves gridctl and common behind, which
is the state step 0 exists to refuse: this script would otherwise rewrite an old-code
host from its old template. Run both, then rerun this script.

The host must also be idle, and the order matters: stop the services with
"php services.php stop" BEFORE pulling the new code, while the host's own 4.2.0 copy can
still read its own listen-config.php. Once the new code is in place services.php refuses a
pre-upgrade config, and the listener has to be stopped with "systemctl stop us3-listen"
instead. Then let the queues drain and comment out the LIMS cron entries. Step 1 checks all
of this and refuses rather than work around a running system.

Steps

0 : every stack checkout is at $required_version or newer (read from each repository's VERSION),
    and gridctl carries the version-2 artifacts the later steps need
1 : preflight. The host must be idle: none of listen, manage-us3-pipe, submitctl or esign
    running (however they were started, not only via the us3-listen unit), no jobmonitor,
    nothing from /opt/ultrascan3/bin, no unfinished job in gfac.analysis, no cleanup claim,
    an empty local Slurm queue with no node running work, no other client running a
    statement on MariaDB, nobody else logged in, and the LIMS cron entries commented out
2 : rewrites listen-config.php from gridctl's template, carrying the site's values
3 : deactivates clusters the Slurm code cannot submit to, or with --convert-http converts
    the national HPC ones to SSH instead, then sets the global_config.php
    settings the new code requires (queue time, tenant scope, local cluster,
    env_script_lines per cluster, single_node on one-node appliances)
4 : records each cluster's host key and checks ssh for us3 and the web account
5 : creates the shared circuit-breaker directory, the shared ssh-control directory
    (common#24, sticky bit so one account cannot rename another's subdirectory
    under it), elog.txt and its HMAC key (dbinst#75, provisioned shared from the
    start so a split web/us3 account host does not depend on whichever account
    happens to touch them first), and gfac.runtime_prediction (the runtime
    advisory pilot table, common#31 -- optional, pilot-scoped; a missing SQL
    file or no CREATE privilege is a note here, not a failure)
6 : removes the gridctl cron entries (gridctl.php, and the gridctl_pro/dev names before it)
7 : installs the Content-Security-Policy as Report-Only unless a policy is already
    configured; enforcing it is a later step
8 : verifies the result, including that every active cluster passes the submission
    sizing gate rather than refusing every job

Undoing it

Every file this script rewrites is copied first, and so is every file it appends to;
both go to one timestamped directory, named when the run starts and again at the end.
To roll back, with the services stopped:

  1. copy global_config.php, listen-config.php and cluster_config.php back from
     that directory;
  2. copy known_hosts and authorized_keys back, or delete them where the backup is
     recorded as "(did not exist)";
  3. restore the LIMS cron entries, including the gridctl entry step 6 removed, if
     you are going back to a release that still expects the sweep;
  4. the circuit-breaker directory, the ssh-control directory, and elog.txt/
     elog_hmac_key's ownership and mode can all stay: unused or already-correct,
     none of them changes anything left alone.

Nothing else to undo in the database: this script never touches the schema or any
job row, with one exception -- step 5's gfac.runtime_prediction (see step 5 above),
which is optional, pilot-scoped and empty until the pilot is turned on. Dropping it
is a separate, deliberate operator step (the teardown statement is documented in
common's class/prediction/runtime_pilot_table.sql), not part of rolling back this
script.

Options

--help                       : print this information and exit
--check                      : dry run: report what would change, change nothing
--apply                      : make the changes (each changed file is backed up first)
--yes                        : accept the proposed value wherever one can be proposed.
                               Settings with no value to propose are reported instead of
                               asked for, so --apply --yes never waits for input.
--accept-host-keys           : trust the host keys this script fetches, without review.
                               Only for a network you already trust: --yes does not imply it.
--convert-http               : convert the national HPC entries (submittype 'http', left over
                               from Airavata, active or not) to the SSH shape in
                               global_config.php.template. Conversion never activates an entry
                               or its cluster_config.php probe, deliberately: only --activate
                               does, once its env_script_lines are supplied and ssh to it
                               succeeds -- only the cluster can say what those are.
--www path                   : web root (default $wwwpath)
--web-user name              : account the web code runs as (default: the PHP-FPM pool user, else apache, else www-data)
--env cluster=lines          : env_script_lines for a cluster ('' for none); sets or changes it; repeatable
--local-cluster name         : cluster used for GUI requests naming 'localhost'; sets or changes it
--single-tenant yes|no       : yes on an appliance (one institution), no on a shared host; sets or changes it.
                               'no' also downgrades a busy sinfo node from this run's own FAIL to a note,
                               since someone else's work on a shared cluster is not this upgrade's business
--activate cluster[,cluster] : bring a converted entry live: sets active=true in global_config.php and
                               turns on its cluster_config.php probe, together, once its env_script_lines
                               are real and ssh to it succeeds. Still 'http', already active, no usable
                               env_script_lines, or ssh failing are each reported and nothing is changed
                               for that entry.

__EOD;

$u_argv = $argv;
array_shift( $u_argv );

## A bare invocation used to start a dry run, which reads as "it did nothing" to an
## operator expecting the upgrade. Print the help and exit non-zero instead, so a
## dry run is always asked for explicitly.
if ( !count( $u_argv ) ) {
    echo $notes;
    exit( 1 );
}

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
$convert_http  = false;
$activate_want = [];

while ( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $opt = array_shift( $u_argv );
    switch ( $opt ) {
        case "--help":
            echo $notes;
            exit;
        case "--check":
            ## The default already, but an operator has to be able to ask for it:
            ## a bare invocation prints the help rather than running anything.
            break;
        case "--apply":
            $apply = true;
            break;
        case "--yes":
            $assume_yes = true;
            break;
        case "--accept-host-keys":
            $accept_keys = true;
            break;
        case "--convert-http":
            $convert_http = true;
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
        case "--activate":
            $names = opt_value( $u_argv, $opt );
            if ( trim( $names ) === '' ) {
                error_exit( "--activate needs at least one cluster name" );
            }
            foreach ( explode( ',', $names ) as $one ) {
                $one = trim( $one );
                if ( $one !== '' ) {
                    $activate_want[] = $one;
                }
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
## The default only. remote_exec reads $global_circuit_breaker_dir first, so this
## is re-resolved from the config once that file has been read, below.
$breaker_dir   = "$us3_home/lims/etc/circuit-breaker";

$failures    = 0;
$changes     = 0;
$pending     = 0;
$needs_input = 0;
## Incremented whenever an --apply run's confirm() or confirm_host_keys() is
## answered no (or, for the latter, cannot be asked at all): a todo that was
## reachable but not carried out, which an [input] item is not the only way
## to leave behind.
$declined    = 0;

## Backups go to a fixed, absolute location rather than wherever root happened to
## be when the script was started. The directory itself is created on first use,
## so a dry run still creates nothing.
$backup_prefix = "$us3_home/lims/etc/uslims_upgrade-backup";

## ------------------------------------------------------------- helpers

function step( $title ) {
    echo "\n";
    headerline( $title );
}

## 'input' is a todo this script cannot carry out itself, because the value has to
## come from an option. It counts toward the dry run's pending total like any other
## todo, and separately toward $needs_input, which is the only thing that can still
## be outstanding after --apply: every other todo is applied on the way past.
function report( $status, $msg ) {
    global $failures, $pending, $needs_input;
    if ( $status === 'FAIL' ) {
        $failures++;
    } elseif ( $status === 'todo' ) {
        $pending++;
    } elseif ( $status === 'input' ) {
        $pending++;
        $needs_input++;
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

## A copy of a file this script appends to, taken once per file, so the append can
## be undone. write_file() already backs up what it rewrites; the ssh files are
## appended to instead, and were the one change with nothing to roll back to.
## $label rather than backup_path(): two accounts each have a known_hosts, and
## keying on the basename alone would have one overwrite the other.
function backup_append_target( $path, $label ) {
    global $append_backups;

    if ( !isset( $append_backups ) ) {
        $append_backups = [];
    }
    if ( array_key_exists( $path, $append_backups ) ) {
        return $append_backups[ $path ];
    }

    ## Absent is a restorable state too: rollback means deleting the file.
    if ( !is_file( $path ) ) {
        return $append_backups[ $path ] = '(did not exist)';
    }

    $dest = ensure_backup_dir() . '/' . $label;

    return $append_backups[ $path ] = @copy( $path, $dest ) ? $dest : '';
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
    global $assume_yes, $apply, $declined;
    $answer = $assume_yes || ask_yn( $question );
    if ( !$answer && $apply ) {
        $declined++;
    }
    return $answer;
}

## Trusting a freshly scanned host key is not something --yes should decide: it
## would turn StrictHostKeyChecking=yes back into accept-new. Unattended runs
## report the fingerprints instead, and install nothing.
function confirm_host_keys( $question ) {
    global $accept_keys, $apply, $declined;
    if ( $accept_keys ) {
        return true;
    }
    $answer = interactive() ? ask_yn( $question, 'rerun with --accept-host-keys to trust it' ) : false;
    if ( !$answer && $apply ) {
        $declined++;
    }
    return $answer;
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

    ## The web account needs to run squeue/sbatch/scancel, so a forced command is
    ## not available, but nothing needs a tty, forwarding or a tunnel: remote_exec
    ## runs "ssh -n -o BatchMode=yes". Without these an unrestricted key in us3's
    ## authorized_keys is a general-purpose login for whatever runs as the web
    ## account. Spelled out rather than OpenSSH 7.2's "restrict", which an older
    ## sshd rejects outright, taking the whole key line with it.
    if ( $account !== $login_user ) {
        $pub = 'no-agent-forwarding,no-port-forwarding,no-pty,no-user-rc,no-X11-forwarding '
               . $pub;
    }

    $auth = $login[ 'dir' ] . '/.ssh/authorized_keys';
    if ( strpos( (string) @file_get_contents( $auth ), $pub ) === false ) {
        if ( backup_append_target( $auth, "authorized_keys.$login_user" ) === '' ) {
            return false;
        }
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

## A value as PHP source. Strings go through php_string() so embedded newlines stay
## readable in the managed block; everything else is var_export's own form.
function php_value( $value ) {
    return is_string( $value ) ? php_string( $value ) : var_export( $value, true );
}

## The deployed global_config.php.template's entry for one cluster, or null with a
## reason. The template is the reference shape for a national HPC cluster reached
## over SSH, and it ships with the common checkout, so it is on the host already.
function template_cluster( $name, &$why = null ) {
    static $entries = null;
    global $wwwpath;

    if ( $entries === null ) {
        $file = "$wwwpath/common/global_config.php.template";
        if ( !is_file( $file ) ) {
            $entries = [ '__why' => "no template at $file" ];
        } else {
            $vars = config_vars( $file, $read_why );
            $entries = ( $vars === null || !is_array( $vars[ 'cluster_details' ] ?? null ) )
                     ? [ '__why' => "the template could not be read: " . reason( $read_why ) ]
                     : $vars[ 'cluster_details' ];
        }
    }
    if ( isset( $entries[ '__why' ] ) ) {
        $why = $entries[ '__why' ];
        return null;
    }
    if ( !is_array( $entries[ $name ] ?? null ) ) {
        $why = "the template has no '$name' entry";
        return null;
    }
    $why = '';
    return $entries[ $name ];
}

## What still stands between a converted entry and activation. env_script_lines is
## the one the cluster alone can answer: the template carries a TODO for the clusters
## whose module names were never confirmed.
function conversion_blockers( $want, $have ) {
    $blockers = [];
    $env      = (string) ( $want[ 'env_script_lines' ] ?? ( $have[ 'env_script_lines' ] ?? '' ) );
    if ( trim( $env ) === '' ) {
        $blockers[] = "env_script_lines is empty, so no modules would be loaded";
    } elseif ( stripos( $env, 'TODO' ) !== false ) {
        $blockers[] = "env_script_lines still carries the template's TODO; ask the cluster"
                      . " for its module lines";
    }
    if ( trim( (string) ( $want[ 'login' ] ?? ( $have[ 'login' ] ?? '' ) ) ) === '' ) {
        $blockers[] = "no login account";
    }
    $blockers[] = "SSH keys and an account on the cluster stay an operator step";
    return $blockers;
}

## jobsubmit.php refuses every job on a single_node cluster whose maxproc
## exceeds ppn, or whose ppn is unusable, so that invariant still holds.
##
## 9b875a0 floored ppn/maxproc at max($cpus, 16) so GA kept 16 processes on a
## smaller appliance, as 4.2.0 did. Tested against real Slurm (20.11.9,
## cons_tres/CR_Core): that floor oversubscribes a node with fewer than 16
## CPUs. sbatch rejects --ntasks-per-node above the node's real CPU count
## outright for 2DSA/PCSA ("CPU count per node can not be satisfied"), and GA
## accepts but pends forever (PartitionConfig) -- worse than the regression
## the floor was added to avoid, since 4.2.0's 2DSA ran fine on such a node.
##
## The first fix replaced the floor with a cap: ppn/maxproc were lowered when
## above $cpus, but never raised when below it. That broke GA the other way
## on every node whose template shipped ppn/maxproc below its real CPU count
## (the stock appliance entry does, at ppn 8 / maxproc 16): GA was lowered to
## 8 processes even on a 16- or 32-CPU node, instead of using the whole node.
## ppn and maxproc are single-node whole-node counts, so they are now set to
## $cpus outright, raising or lowering as needed. ppbj is not a whole-node
## count -- it caps how much of the node one big job (2DSA/PCSA) may use --
## so it keeps the cap-only rule: never raised, only ever lowered to $cpus.
##
## Pure: no reads, no writes, no reporting. $c is the cluster's current
## config (only 'ppbj'/'ppn'/'maxproc' are read); returns the three keys this
## cluster should have.
function single_node_sizing( $c, $cpus ) {
    $want = [];
    $want[ 'ppbj' ] = isset( $c[ 'ppbj' ] ) ? (int) $c[ 'ppbj' ] : null;

    $want[ 'ppn' ]     = $cpus;
    $want[ 'maxproc' ] = $cpus;

    if ( $want[ 'ppbj' ] === null || $want[ 'ppbj' ] > $cpus ) {
        $want[ 'ppbj' ] = $cpus;
    }
    ## Still never above ppn: that part of the original invariant stands.
    if ( $want[ 'ppbj' ] > $want[ 'ppn' ] ) {
        $want[ 'ppbj' ] = $want[ 'ppn' ];
    }
    return $want;
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
    ## Keyed by realpath, not the glob path, so a symlink under /etc/cron.d
    ## pointing at a file already seen (its target, or another symlink to the
    ## same target) does not report the same live entry twice under two names.
    $seen = [];
    foreach ( array_merge( [ '/etc/crontab' ], glob( '/etc/cron.d/*' ) ?: [] ) as $file ) {
        if ( !is_file( $file ) ) {
            continue;
        }
        $real = realpath( $file ) ?: $file;
        if ( isset( $seen[ $real ] ) ) {
            continue;
        }
        $seen[ $real ] = true;
        $tabs[ $file ] = (string) @file_get_contents( $file );
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
## site's values into gridctl's template, and the web code has to be able to
## submit through Slurm. Checking first means a stale checkout is reported
## before anything on the host has been changed, rather than failing partway
## through.
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
    error_exit( "pull " . implode( ', ', $stale ) . " to $required_version or newer, then rerun:"
                . "\n    cd " . __DIR__ . " && php uslims_git_info.php --update-pull util"
                . "\n    cd " . __DIR__ . " && php uslims_git_info.php --update-pull lims"
                . "\nRun both: pulling util alone leaves gridctl and common behind, which is the"
                . " state this check refuses. 'all' also pulls the UltraScan gui and mpi"
                . " checkouts, which this upgrade does not touch."
                . "\nThe code and the database schema are upgraded separately; this script only"
                . " changes this host's configuration. Nothing was changed" );
}

## A VERSION file is a claim; these are the artifacts the rest of the script needs.
## Old gridctl ships a listen-config template too, so test the contract rather than
## trust the version string: the bootstrap and helper the version-2 config relies on,
## and a template that actually declares version 2.
$missing = [];
foreach ( [ 'gridctl_bootstrap.php', 'listen_functions.php' ] as $artifact ) {
    if ( is_file( "$gridctl_dir/$artifact" ) ) {
        report( 'ok', "gridctl has $artifact" );
    } else {
        report( 'FAIL', "gridctl ($gridctl_dir) has no $artifact" );
        $missing[] = $artifact;
    }
}
$template_version = 0;
if ( !is_file( $template ) ) {
    report( 'FAIL', "no listen-config template at $template" );
    $missing[] = basename( $template );
} else {
    if ( preg_match( '/^\s*\$listen_config_version\s*=\s*(\d+)/m',
                     (string) @file_get_contents( $template ), $m ) ) {
        $template_version = (int) $m[ 1 ];
    }
    if ( $template_version >= 2 ) {
        report( 'ok', "the listen-config template is version $template_version" );
    } else {
        report( 'FAIL', "the listen-config template at $template declares version "
                        . ( $template_version ?: 'none' ) . ", not 2" );
        $missing[] = basename( $template );
    }
}
if ( $missing ) {
    error_exit( "gridctl is not the $required_version contract: " . implode( ', ', $missing )
                . ". A VERSION file alone is not enough, so check the checkout at $gridctl_dir"
                . " is the upgraded one:"
                . "\n    cd " . __DIR__ . " && php uslims_git_info.php --update-pull util"
                . "\n    cd " . __DIR__ . " && php uslims_git_info.php --update-pull lims"
                . "\nNothing was changed" );
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

## -- the gridctl services. services.php start() launches listen, submitctl
## -- and esign directly, plus manage-us3-pipe as listen's own child; checking
## -- only listen.php let any of the other three still be running (confirmed:
## -- a container with just submitctl.php up passed this and the upgrade
## -- applied). The jobmonitors are checked separately below.
## (?:\S*/)? : the path prefix is optional, not just the slash alone, so a
## daemon started by hand from its own directory ("cd ~us3/lims/bin && php
## listen.php", with no path on the command line at all) still matches.
$listeners = processes( '#^\s*(\d+)\s+\S*php[0-9.]*\s+(?:\S*/)?(listen|manage-us3-pipe|submitctl|esign)\.php(\s|$)#' );
if ( $listeners === null ) {
    report( 'FAIL', "could not read the process list, so the host cannot be shown to be idle" );
    $busy[] = 'process list unreadable';
} elseif ( $listeners ) {
    ## services.php loads gridctl_bootstrap.php, which refuses a pre-upgrade
    ## listen-config.php. So on a host that still has the old config - which is
    ## every host reaching this point before step 2 has run - the obvious
    ## instruction does not work, and the operator has to stop the listener
    ## without it. Name the right one for the config actually on the host.
    $how = ( (int) ( $old_listen[ 'listen_config_version' ] ?? 0 ) >= 2 )
         ? "cd $us3bin && php services.php stop"
         : "systemctl stop us3-listen   (services.php cannot run yet: this host's"
           . " listen-config.php is still the pre-upgrade format, which the new"
           . " code refuses. Stopping the services before pulling the new code"
           . " avoids this entirely)";
    report( 'FAIL', "a gridctl service is still running (pid "
                    . implode( ', ', array_keys( $listeners ) ) . "); stop it with: $how" );
    $busy[] = 'gridctl service running';
} else {
    report( 'ok', "no gridctl service (listen, manage-us3-pipe, submitctl, esign) is running" );
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
    ## -u us3: this is asking whether US3'S queue is idle, not whether the whole
    ## Slurm installation is. Unfiltered, any other account's jobs on a shared
    ## local Slurm failed this check for work that has nothing to do with the
    ## upgrade.
    $rc = run_as( 'us3', 'squeue -h -u us3 -o %i', $q_out, $q_err );
    if ( $rc !== 0 ) {
        report( 'FAIL', "squeue failed, so us3's queue cannot be shown to be idle: "
                        . reason( $q_err !== '' ? $q_err : $q_out ) );
        $busy[] = 'squeue failed';
    } elseif ( trim( $q_out ) !== '' ) {
        $jobs = count( array_filter( explode( "\n", trim( $q_out ) ) ) );
        report( 'FAIL', "$jobs us3 job(s) are still in the local Slurm queue" );
        $busy[] = "$jobs job(s) queued";
    } else {
        report( 'ok', "us3's local Slurm queue is empty" );
    }
    ## squeue says nothing about work already placed on a node, so ask sinfo for the
    ## node states too. allocated/mixed/completing mean a job is on the node.
    $rc = run_as( 'us3', 'sinfo -h -o "%n %T"', $si_out, $si_err );
    if ( $rc !== 0 ) {
        report( 'note', "sinfo failed, so the node states could not be checked ("
                        . reason( $si_err !== '' ? $si_err : $si_out )
                        . "); confirm sinfo is idle before continuing" );
    } else {
        $working = [];
        $degraded = [];
        foreach ( explode( "\n", trim( $si_out ) ) as $line ) {
            if ( !preg_match( '/^(\S+)\s+(\S+)/', trim( $line ), $m ) ) {
                continue;
            }
            ## Strip the state's flag suffixes: "mixed*", "idle~", "allocated$".
            $state = strtolower( rtrim( $m[ 2 ], '*~#$@+' ) );
            if ( in_array( $state, [ 'allocated', 'mixed', 'completing' ], true ) ) {
                $working[] = "{$m[1]} ($state)";
            } elseif ( !in_array( $state, [ 'idle', 'reserved' ], true ) ) {
                $degraded[] = "{$m[1]} ($state)";
            }
        }
        if ( $working ) {
            ## sinfo has no per-user filter: a node it calls busy may be running
            ## someone else's job, not us3's. On a known-shared host that is
            ## routine and not this upgrade's business; on a single-tenant
            ## appliance there is no "someone else", so it still blocks.
            if ( $single_tenant === false ) {
                report( 'note', count( $working ) . " node(s) running work, on a shared cluster where that"
                                . " may not be us3's: " . implode( ', ', $working ) );
            } else {
                report( 'FAIL', count( $working ) . " node(s) still running work: "
                                . implode( ', ', $working )
                                . " (on a shared, multi-tenant host, pass --single-tenant no to"
                                . " downgrade this to a note)" );
                $busy[] = count( $working ) . ' node(s) busy';
            }
        } else {
            report( 'ok', "sinfo reports no node running work" );
        }
        ## Not a reason to refuse: a drained node runs nothing. Worth saying, because
        ## it changes what the host can do after the upgrade.
        if ( $degraded ) {
            report( 'note', "node(s) not available: " . implode( ', ', $degraded ) );
        }
    }
}

## -- other database clients. A sleeping connection is a pool, not a user; one
## -- running a statement means the system is in use.
##
## Without PROCESS (or SUPER), SHOW PROCESSLIST shows only this connection's
## own account's threads: on a roles host the web tier connects as a
## different account (us3php, not gfac), so this check would see nobody else
## and report 'ok' while blind to them. Checked here rather than assumed, so
## that is a 'note' to verify by hand instead of a silent false negative.
$has_process_priv = false;
$grants = mysqli_query( $gdb, 'SHOW GRANTS' );
if ( $grants ) {
    while ( $row = mysqli_fetch_row( $grants ) ) {
        $grant = (string) ( $row[ 0 ] ?? '' );
        ## PROCESS and SUPER are global-only privileges in MySQL/MariaDB: they
        ## cannot be granted on a specific database. But MariaDB always
        ## expands "ALL" to "ALL PRIVILEGES" in SHOW GRANTS output, even for a
        ## database-scoped grant like "GRANT ALL ON gfac.* TO ...", so
        ## matching the word alone without checking the scope matched a grant
        ## that gives nothing outside gfac and no real PROCESS privilege.
        if ( preg_match( '/\bON\s+\*\.\*(\s|$)/i', $grant )
           && preg_match( '/\b(PROCESS|SUPER|ALL PRIVILEGES)\b/i', $grant ) ) {
            $has_process_priv = true;
            break;
        }
    }
}

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
    } elseif ( !$has_process_priv ) {
        report( 'note', "no other '" . ( $old_listen[ 'guser' ] ?? 'gfac' ) . "' connection is running a"
                        . " statement, but this account has no PROCESS privilege, so a connection under"
                        . " a different account (the web tier's, for example) would not show up here;"
                        . " confirm nobody else is using MariaDB by hand" );
    } else {
        report( 'ok', "no other client is running a statement on MariaDB" );
    }
}

## -- other people on the host. The operator's own session is excluded, found from
## -- this process's controlling terminal, so the check cannot fail on itself.
$own_tty = function_exists( 'posix_ttyname' ) ? @posix_ttyname( STDIN ) : false;
$own_tty = is_string( $own_tty ) ? preg_replace( '#^/dev/#', '', $own_tty ) : '';
list( $who_out, $who_err, $who_rc ) = capture( 'who' );
if ( $who_rc !== 0 ) {
    report( 'note', "could not list the logged-in users ("
                    . reason( $who_err !== '' ? $who_err : $who_out )
                    . "); confirm nobody else is on this host" );
} else {
    ## Who actually invoked this: SUDO_USER under sudo (the normal invocation),
    ## else this process's own account. Used below instead of broadly trusting
    ## tmux/screen/sudo, which defeated the check entirely: "sudo php
    ## uslims_upgrade.php" is the normal invocation, so every other logged-in
    ## user's session was downgraded to a note alongside the operator's own.
    $invoking_user = getenv( 'SUDO_USER' );
    if ( $invoking_user === false || $invoking_user === '' ) {
        $invoking_user = function_exists( 'posix_getpwuid' )
                        ? (string) ( @posix_getpwuid( posix_geteuid() )[ 'name' ] ?? '' )
                        : '';
    }

    $sessions = [];
    foreach ( explode( "\n", trim( $who_out ) ) as $line ) {
        if ( !preg_match( '/^(\S+)\s+(\S+)/', trim( $line ), $m ) ) {
            continue;
        }
        if ( $own_tty !== '' && $m[ 2 ] === $own_tty ) {
            continue;
        }
        ## tmux and screen give each pane its own pty, and sudo with use_pty
        ## gives the elevated process a new one too, so $own_tty can be real
        ## and still not be the pty who(1) reports for this same login: the
        ## exclusion above then matches nothing. Exclude by the invoking
        ## user's name instead, not by downgrading every session once any of
        ## those tools is in use -- that kept FAIL for everyone else logged
        ## in as a different account. Not for 'root', though: run directly
        ## as root with no sudo, $invoking_user is 'root' same as anyone
        ## else logged in directly as root, and that account is commonly
        ## shared across several real admins/processes -- matching on it
        ## would wave through a second, genuinely different root login as
        ## if it were this same session, which $own_tty's own check above
        ## (keyed on the pty, not the account) does not have this problem.
        if ( $invoking_user !== '' && $invoking_user !== 'root' && $m[ 1 ] === $invoking_user ) {
            continue;
        }
        $sessions[] = "{$m[1]} on {$m[2]}";
    }
    ## Ambiguous only when NEITHER signal can tell this session apart from
    ## anyone else's: no controlling terminal (Ansible, `ssh host sudo php
    ## ...`, `</dev/null`) and no non-root invoking user identified either.
    ## The previous version downgraded to 'note' whenever $own_tty alone was
    ## empty, even when $invoking_user was known and had already correctly
    ## excluded its own session above -- silently waving every other
    ## session through as a mere note under Ansible or any other
    ## no-terminal invocation.
    $ambiguous = $own_tty === '' && ( $invoking_user === '' || $invoking_user === 'root' );
    if ( $sessions && $ambiguous ) {
        report( 'note', count( $sessions ) . " login session(s) found, and this session's"
                        . " terminal could not be identified, so one of them may be this one: "
                        . implode( ', ', $sessions ) );
    } elseif ( $sessions ) {
        report( 'FAIL', count( $sessions ) . " other login session(s): "
                        . implode( ', ', $sessions ) );
        $busy[] = 'other users logged in';
    } else {
        report( 'ok', "nobody else is logged in" );
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

## The breaker directory belongs to remote_exec, which reads
## $global_circuit_breaker_dir and only falls back to us3's home. Recomputing the
## default here would create one directory and verify it while the web tier and
## the daemons used another: step 5 would report it created, step 7 would report
## it writable, and neither statement would be about the directory in use.
$configured_breaker = isset( $gc[ 'global_circuit_breaker_dir' ] )
                      ? trim( (string) $gc[ 'global_circuit_breaker_dir' ] ) : '';
if ( $configured_breaker !== '' ) {
    $breaker_dir = rtrim( $configured_breaker, '/' );
    report( 'note', "global_config.php sets the breaker directory to $breaker_dir" );
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
$converted = [];
foreach ( $active as $name => $c ) {
    $retired = null;
    $is_http = isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) === 'http';
    if ( isset( $c[ 'clusters' ] ) && is_array( $c[ 'clusters' ] ) ) {
        $retired = "it is a metascheduler entry, fanning out to " . implode( ', ', $c[ 'clusters' ] );
    } elseif ( isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) !== 'slurm' ) {
        $retired = "its submittype is '" . $c[ 'submittype' ] . "', which the Slurm code does not implement";
    }
    if ( $retired === null ) {
        continue;
    }
    ## A national HPC entry is an Airavata leftover, not a dead cluster: the cluster
    ## is still there and still wanted, it just has to be reached over SSH now. With
    ## --convert-http it is rewritten into the template's SSH shape instead of being
    ## deactivated. It stays inactive either way, because only the cluster itself can
    ## say what its env_script_lines are, so going live is a later operator step.
    if ( $is_http && $convert_http ) {
        $want = template_cluster( $name, $tpl_why );
        if ( $want === null ) {
            report( 'todo', "deactivate cluster '$name': it is an 'http' entry and "
                            . reason( $tpl_why ) . ", so there is nothing to convert it to" );
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = false;';
            unset( $active[ $name ] );
            continue;
        }
        $changed = [];
        foreach ( $want as $key => $value ) {
            ## active is handled below, deliberately: conversion never activates.
            if ( $key === 'active' ) {
                continue;
            }
            if ( array_key_exists( $key, $c ) && $c[ $key ] === $value ) {
                continue;
            }
            $changed[] = $key;
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ '
                       . var_export( $key, true ) . ' ] = ' . php_value( $value ) . ';';
        }
        ## submittype is what marks it as an Airavata entry. The submission code no
        ## longer reads it, but leaving 'http' there invites the next reader to think
        ## the entry still goes through a gateway.
        if ( ( $c[ 'submittype' ] ?? '' ) !== 'slurm' ) {
            $changed[] = 'submittype';
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'submittype\' ] = \'slurm\';';
        }
        if ( !empty( $c[ 'active' ] ) ) {
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = false;';
        }
        report( 'todo', "convert cluster '$name' to SSH ("
                        . ( $changed ? implode( ', ', $changed ) : 'already matches the template' )
                        . "), left inactive" );
        $blockers = conversion_blockers( $want, $c );
        if ( $blockers ) {
            report( 'note', "'$name' cannot be activated yet: " . implode( '; ', $blockers ) );
        }
        $converted[] = $name;
        unset( $active[ $name ] );
        continue;
    }
    report( 'todo', "deactivate cluster '$name': $retired"
                    . ( $is_http ? " (pass --convert-http to convert it to SSH instead)" : '' ) );
    $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = false;';
    unset( $active[ $name ] );
}

## --convert-http above only ever walked $active: an inactive 'http' entry
## could then be neither converted (not in $active, so never reached) nor
## activated later ("convert it first with --convert-http"). Every national
## HPC entry in the 4.2.0 template starts out inactive, so this covered all
## of them. Same conversion, minus the active-entry bookkeeping (there is no
## 'active' flag to turn off, and nothing here turns one on).
if ( $convert_http ) {
    foreach ( $clusters as $name => $c ) {
        if ( isset( $active[ $name ] ) || !is_array( $c ) ) {
            continue;
        }
        $is_http = isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) === 'http';
        if ( !$is_http ) {
            continue;
        }
        $want = template_cluster( $name, $tpl_why );
        if ( $want === null ) {
            report( 'note', "inactive cluster '$name' is an 'http' entry and " . reason( $tpl_why )
                            . ", so there is nothing to convert it to" );
            continue;
        }
        $changed = [];
        foreach ( $want as $key => $value ) {
            if ( $key === 'active' ) {
                continue;
            }
            if ( array_key_exists( $key, $c ) && $c[ $key ] === $value ) {
                continue;
            }
            $changed[] = $key;
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ '
                       . var_export( $key, true ) . ' ] = ' . php_value( $value ) . ';';
            ## Mirrored into this run's own $clusters, not just queued for the
            ## managed block written at the end: --convert-http and --activate
            ## used to need separate runs, one for the file to actually carry
            ## the new value before the next step could see it. Steps below
            ## (ssh setup, --activate) read $clusters directly, so converting
            ## and activating an entry in one run needs this to be visible to
            ## them immediately, not just on the next invocation.
            $clusters[ $name ][ $key ] = $value;
        }
        if ( ( $c[ 'submittype' ] ?? '' ) !== 'slurm' ) {
            $changed[] = 'submittype';
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'submittype\' ] = \'slurm\';';
            $clusters[ $name ][ 'submittype' ] = 'slurm';
        }
        report( 'todo', "convert inactive cluster '$name' to SSH ("
                        . ( $changed ? implode( ', ', $changed ) : 'already matches the template' )
                        . "), left inactive" );
        $blockers = conversion_blockers( $want, $c );
        if ( $blockers ) {
            report( 'note', "'$name' cannot be activated yet: " . implode( '; ', $blockers ) );
        }
        $converted[] = $name;
    }
}

if ( $active ) {
    report( 'ok', count( $active ) . " active cluster(s) the Slurm code can use: " . implode( ', ', array_keys( $active ) ) );
} else {
    report( 'FAIL', "no active cluster is usable by the Slurm code; jobs would have nowhere to go" );
}

## A job is never cancelled for elapsed time alone: whether to kill one is the
## submitter's decision, and a missed status update is not evidence of a hang.
foreach ( array( 'global_max_queue_time_hours', 'global_max_run_time_hours' ) as $timer ) {
    if ( (int) ( $gc[ $timer ] ?? 24 ) !== 0 ) {
        report( 'todo', "set \$$timer = 0 (currently " . ( $gc[ $timer ] ?? 'unset' ) . "); a job is left for its submitter to cancel" );
        $managed[] = "\$$timer = 0;";
    } else {
        report( 'ok', "\$$timer is 0" );
    }
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
        report( 'input', "set \$single_tenant_deployment (pass --single-tenant yes|no)" );
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
        report( 'input', "set \$default_local_cluster (pass --local-cluster; candidates: " . implode( ', ', array_keys( $active ) ) . ")" );
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
        report( 'input', "$name lacks env_script_lines (pass --env $name=... or --env $name=)" );
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
    ## Sizing itself is single_node_sizing() (pure, unit-tested); see its
    ## docblock for why ppn/maxproc are set to the CPU count outright,
    ## raising or lowering, rather than only ever capped at it.
    $want = single_node_sizing( $c, $cpus );
    foreach ( $want as $key => $value ) {
        $now = isset( $c[ $key ] ) ? (int) $c[ $key ] : null;
        if ( $value === $now ) {
            continue;
        }
        report( 'todo', "set $key for $name to $value (currently "
                        . ( $now === null ? 'unset' : $now )
                        . "; one node with $cpus CPUs, and a single_node cluster needs"
                        . " maxproc <= ppn <= the CPU count -- real Slurm rejects or pends"
                        . " any of these oversubscribed above $cpus, so ppn/maxproc are set"
                        . " to $cpus to use the whole node)" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ ' . var_export( $key, true ) . ' ] = ' . $value . ';';
    }
}

## --activate: bring a converted entry live. Checked here, not folded into the
## conversion loop above, because activation is a separate operator decision
## that can come any number of runs later, once the cluster has actually
## supplied its env_script_lines and SSH has been set up by hand (neither of
## which this script can do for a cluster that isn't in $active yet).
##
## Converting and activating used to take three separate runs even once
## env_script_lines was supplied, because this block runs before step 4
## (SSH) below it ever gets a chance to record a host key or authorize a
## local key -- its own ssh test here still fails on an entry converted
## earlier in this very run, for lack of a key, same as before. What the
## conversion loop above now fixes is that it mirrors its changes straight
## into $clusters, not just into $managed for the eventual file write: step
## 4 below used to misread a freshly-converted entry's submittype as still
## unset (not yet 'slurm' on disk) and skip setting its keys up at all this
## run. With that read fixed, step 4 sets a fresh conversion's keys up in
## the very run it was converted, so only one re-run -- to let --activate
## see that step 4's work -- is needed before activation succeeds, down
## from two.
$activated = [];
foreach ( $activate_want as $name ) {
    $c = $clusters[ $name ] ?? null;
    if ( !is_array( $c ) ) {
        report( 'FAIL', "--activate: '$name' is not a cluster entry in $global_config" );
        continue;
    }
    if ( !empty( $c[ 'active' ] ) ) {
        report( 'ok', "--activate: '$name' is already active" );
        continue;
    }
    ## A missing submittype is Slurm, not something still needing
    ## conversion: the 4.3.0 template's own entries carry no submittype key
    ## at all, and --convert-http only ever converts 'http' entries, so a
    ## host built from that template had no way to activate anything.
    if ( strtolower( (string) ( $c[ 'submittype' ] ?? 'slurm' ) ) !== 'slurm' ) {
        report( 'FAIL', "--activate: '$name' is still submittype '"
                        . ( $c[ 'submittype' ] ?? '' ) . "'; convert it first with --convert-http" );
        continue;
    }
    ## --env on this run wins over whatever is already on disk, same as the
    ## env_script_lines check active clusters get above.
    $env = isset( $env_values[ $name ] ) ? str_replace( '\n', "\n", $env_values[ $name ] )
         : (string) ( $c[ 'env_script_lines' ] ?? '' );
    if ( trim( $env ) === '' ) {
        report( 'input', "--activate: '$name' has no env_script_lines yet (pass --env $name=...)" );
        continue;
    }
    if ( stripos( $env, 'TODO' ) !== false ) {
        report( 'input', "--activate: '$name' still carries the template's env_script_lines TODO;"
                        . " ask the cluster for its module lines (--env $name=...)" );
        continue;
    }
    $login = $c[ 'login' ] ?? ( 'us3@' . ( $c[ 'name' ] ?? '' ) );
    $port  = (int) ( $c[ 'sshport' ] ?? 22 );
    $ssh   = "ssh -n -p $port -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=yes "
           . escapeshellarg( $login ) . " true";
    $rc    = run_as( 'us3', $ssh, $ssh_out, $ssh_err );
    if ( $rc !== 0 ) {
        ## 'input', not 'todo': this ssh failure does not get an entry in
        ## $managed and so is never actually applied under --apply, unlike a
        ## real 'todo'.
        report( 'input', "--activate: '$name' does not yet ssh as us3 to $login (exit $rc): "
                        . reason( $ssh_err !== '' ? $ssh_err : $ssh_out )
                        . "; step 4 below offers to set up the key under --apply, then re-run" );
        continue;
    }
    ## round-5 should-fix: the web account's ssh used to be checked only
    ## after this wrote active = true, in step 4 further down in the old
    ## file order -- a cluster could go live while the web tier (which
    ## actually stages files and runs sbatch) still could not reach it at
    ## all. Gated here too now, before activation, not only us3's.
    if ( $web_user !== 'us3' ) {
        $web_rc = run_as( $web_user, $ssh, $web_ssh_out, $web_ssh_err );
        if ( $web_rc !== 0 ) {
            report( 'input', "--activate: '$name' does not yet ssh as $web_user to $login (exit $web_rc): "
                            . reason( $web_ssh_err !== '' ? $web_ssh_err : $web_ssh_out )
                            . "; step 4 below offers to set up the key under --apply, then re-run" );
            continue;
        }
    }
    if ( $env !== ( $c[ 'env_script_lines' ] ?? '' ) ) {
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'env_script_lines\' ] = '
                   . php_string( $env ) . ';';
    }
    $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = true;';
    report( 'todo', "activate '$name' (env_script_lines is real, ssh to $login succeeds for us3"
                    . ( $web_user !== 'us3' ? " and $web_user" : '' ) . "): sets active = true in"
                    . " $global_config and turns on its status probe in cluster_config.php" );
    $activated[] = $name;
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

## A newly-activated cluster's health probe, turned on together with the
## global_config.php change above rather than at conversion time: a probe that
## comes on while the entry is still inactive and unreachable tries an ssh
## login every 12 minutes to a centre with no key installed yet, which is
## exactly what intrusion detection there is tuned to notice. cluster_status.php
## also skips an entry whose 'active' is not true, so there is nothing to gain
## by turning the probe on first.
if ( $activated ) {
    $cluster_config = "$us3bin/cluster_config.php";
    if ( !is_file( $cluster_config ) ) {
        report( 'note', "no $cluster_config, so no status probe could be activated for "
                        . implode( ', ', $activated ) );
    } else {
        $cc_text = (string) file_get_contents( $cluster_config );
        $turned  = [];
        $absent  = [];
        foreach ( $activated as $name ) {
            ## Only this entry's own 'active' line: the match is anchored on the key
            ## and stops at the end of its array literal.
            $pattern = '/(' . preg_quote( "'$name'", '/' ) . '\s*=>\s*\[)([^\]]*?)(\x27active\x27\s*=>\s*)false/s';
            if ( !preg_match( '/' . preg_quote( "'$name'", '/' ) . '\s*=>\s*\[/', $cc_text ) ) {
                $absent[] = $name;
                continue;
            }
            $replaced = preg_replace( $pattern, '${1}${2}${3}true', $cc_text, 1, $count );
            if ( $replaced !== null && $count ) {
                $cc_text  = $replaced;
                $turned[] = $name;
            }
        }
        if ( $absent ) {
            report( 'note', "$cluster_config has no entry for " . implode( ', ', $absent )
                            . "; add one from " . basename( $cluster_config ) . ".template"
                            . " or the cluster will never be probed" );
        }
        if ( !$turned ) {
            report( 'ok', "every cluster being activated already has its status probe on" );
        } else {
            report( 'todo', "activate the status probe for " . implode( ', ', $turned )
                            . " in $cluster_config" );
            if ( $apply && confirm( "Activate those status probes in $cluster_config?" ) ) {
                write_file( $cluster_config, $cc_text );
                report( 'done', "$cluster_config updated (original in "
                                . backup_path( $cluster_config ) . ")" );
            }
        }
    }
}

## ------------------------------------------------------------- 4. SSH

step( "4. SSH host keys and access (StrictHostKeyChecking=yes is the default)" );

## --activate names clusters not in $active yet (that is the point of
## --activate), so without this they never got here: no host key was ever
## offered for them, and --activate's own ssh test above only ever ran as
## us3, leaving a separate web account with no key or known_hosts entry of
## its own on the cluster that is about to go live for it too.
$ssh_targets = $active;
foreach ( $activate_want as $name ) {
    if ( isset( $ssh_targets[ $name ] ) || !is_array( $clusters[ $name ] ?? null ) ) {
        continue;
    }
    ## Still 'http': the --activate block below is going to refuse this one
    ## with "convert it first", so there is no point offering ssh setup for
    ## it yet, and step 3's conversion loop hasn't necessarily given it a
    ## real 'name'/'login' to test against. A missing submittype is Slurm,
    ## not 'http' -- the 4.3.0 template's entries carry no submittype key at
    ## all, so this used to skip ssh setup for every one of them too.
    if ( strtolower( (string) ( $clusters[ $name ][ 'submittype' ] ?? 'slurm' ) ) !== 'slurm' ) {
        continue;
    }
    $ssh_targets[ $name ] = $clusters[ $name ];
}

foreach ( $ssh_targets as $name => $c ) {
    $host  = $c[ 'name' ] ?? '';
    $port  = (int) ( $c[ 'sshport' ] ?? 22 );
    $login = $c[ 'login' ] ?? "us3@$host";
    if ( $host === '' ) {
        report( 'FAIL', "$name has no 'name'" );
        continue;
    }
    ## The key has to be recorded for the host ssh actually connects to, which is
    ## the login's host part and not always the cluster's 'name': the template's
    ## expanse entries are name expanse.sdsc.edu, login us3@login.expanse.sdsc.edu,
    ## and the appliance nodes are name us3iab-node0.localhost, login us3@us3iab-node0.
    ## Recording 'name' leaves StrictHostKeyChecking=yes rejecting every connection.
    $ssh_host = strpos( $login, '@' ) !== false
              ? substr( $login, strrpos( $login, '@' ) + 1 )
              : $login;
    if ( $ssh_host !== $host ) {
        report( 'note', "$name: ssh connects to $ssh_host, not the entry's name $host;"
                        . " the host key is recorded for $ssh_host" );
    }
    ## On an Ansible-built host php-fpm runs as us3, so the two accounts are one and
    ## the web-to-us3 key would be a key authorizing us3 to reach itself: no access
    ## is gained and an unnecessary key is the kind of thing nobody later dares
    ## remove. array_unique keyed on the account name, so the loop runs once there.
    $accounts = [ 'us3' => $us3_entry ];
    if ( $web_user !== 'us3' ) {
        $accounts[ $web_user ] = $web_entry;
    }
    foreach ( $accounts as $account => $entry ) {
        $known  = $entry[ 'dir' ] . "/.ssh/known_hosts";
        $lookup = $port === 22 ? $ssh_host : "[$ssh_host]:$port";
        ## Changes proposed but not yet made. They explain a failing ssh test, which
        ## is why a dry run reports one rather than a FAIL. Named apart from the
        ## global $pending counter report() increments: this used to be called
        ## $pending too, which clobbered that counter with an array the moment
        ## this line ran, corrupting every report() call afterward.
        $ssh_pending = array();
        list( $o, $ferr, $found ) = capture( 'ssh-keygen -F ' . escapeshellarg( $lookup ) . ' -f ' . escapeshellarg( $known ) );
        if ( $found !== 0 && $ferr !== '' && !preg_match( '/No such file or directory/', $ferr ) ) {
            ## An unreadable known_hosts is not the same as a missing entry.
            report( 'FAIL', "$name: could not search $known: " . reason( $ferr ) );
            continue;
        }
        if ( $found !== 0 ) {
            list( $keys, $kerr, $krc ) = capture( 'ssh-keyscan -p ' . $port . ' ' . escapeshellarg( $ssh_host ) );
            if ( trim( $keys ) === '' ) {
                report( 'FAIL', "$name: no host key could be fetched from $ssh_host:$port (exit $krc): " . reason( $kerr ) );
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
                report( 'FAIL', "$name: could not fingerprint $ssh_host's host keys: " . reason( $fperr ) );
                continue;
            }
            report( 'todo', "$name: record $ssh_host's host key for $account:\n            "
                            . implode( "\n            ", explode( "\n", $fp ) ) );
            $ssh_pending[ 'host key' ] = true;
            if ( $apply && confirm_host_keys( "Do these fingerprints match $ssh_host's real host keys?" ) ) {
                $dir = dirname( $known );
                if ( !is_dir( $dir ) && !@mkdir( $dir, 0700, true ) ) {
                    report( 'FAIL', "$name: could not create $dir" );
                    continue;
                }
                $saved = backup_append_target( $known, "known_hosts.$account" );
                if ( $saved === '' ) {
                    report( 'FAIL', "$name: could not back up $known, so the append was not made" );
                    continue;
                }
                if ( file_put_contents( $known, $keys . "\n", FILE_APPEND ) === false ) {
                    report( 'FAIL', "$name: could not append $ssh_host's host key to $known" );
                    continue;
                }
                $owned = chown( $dir, $account ) && chown( $known, $account )
                         && chgrp( $known, $entry[ 'gid' ] ) && chmod( $dir, 0700 ) && chmod( $known, 0600 );
                if ( $owned ) {
                    unset( $ssh_pending[ 'host key' ] );
                }
                $changes++;
                report( $owned ? 'done' : 'FAIL',
                        $owned ? "$name: $ssh_host's host key recorded for $account (previous $known in $saved)"
                               : "$name: $known was written but its owner or mode could not be set" );            }
        }
        $ssh = "ssh -n -p $port -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=yes " . escapeshellarg( $login ) . " true";
        $rc  = run_as( $account, $ssh, $ssh_out, $ssh_err );
        if ( $rc !== 0 && $name === $host_cluster ) {
            report( 'todo', "$name: authorize $account's key for $login on this host" );
            $ssh_pending[ 'authorized key' ] = true;
            if ( $apply && confirm( "Set up $account's SSH key for $login?" ) ) {
                if ( authorize_local_key( $account, $entry, explode( '@', $login )[ 0 ] ) ) {
                    $changes++;
                    $rc = run_as( $account, $ssh, $ssh_out, $ssh_err );
                    if ( $rc === 0 ) {
                        unset( $ssh_pending[ 'authorized key' ] );
                    }
                    report( $rc === 0 ? 'done' : 'FAIL', "$name: $account's key authorized for $login" );
                } else {
                    report( 'FAIL', "$name: could not authorize $account's key for $login" );
                }            }
        }
        if ( $rc !== 0 && $ssh_pending ) {
            ## The test cannot pass before the changes just proposed are made, so a
            ## dry run on a host that has never had the key reports the consequence
            ## of its own todos. Reported as a FAIL it made every such run exit 1,
            ## which left a genuine FAIL indistinguishable.
            report( 'todo', "$name: $account can ssh to $login once the "
                            . implode( ' and ', array_keys( $ssh_pending ) ) . " above "
                            . ( count( $ssh_pending ) > 1 ? 'are' : 'is' ) . " in place" );
        } else {
            report( $rc === 0 ? 'ok' : 'FAIL', "$name: $account can ssh to $login"
                    . ( $rc === 0 ? '' : " (exit $rc: install the account's key, including for the host itself) "
                                         . reason( $ssh_err !== '' ? $ssh_err : $ssh_out ) ) );
        }
    }
}

## ------------------------------------------------------------- 5. breaker directory

step( "5. Circuit-breaker directory, and the runtime-advisory pilot table" );

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

## Where submit_slurm's reused ssh/scp connection keeps its control socket
## (common#24), mirroring remote_exec::default_control_dir()'s own default.
## Sticky bit, not just 2770 like the breaker dir: unlike the breaker, every
## account also creates its OWN subdirectory under here at runtime
## (remote_exec::controlPath()), and without the sticky bit either account
## could rename or replace the other's subdirectory out from under it.
$ssh_control_dir = isset( $gc[ 'global_ssh_control_dir' ] ) && $gc[ 'global_ssh_control_dir' ] !== ''
                  ? rtrim( (string) $gc[ 'global_ssh_control_dir' ], '/' )
                  : "$us3_home/lims/etc/ssh-control";
if ( is_dir( $ssh_control_dir ) && !is_link( $ssh_control_dir )
     && fileowner( $ssh_control_dir ) === $us3_entry[ 'uid' ] && filegroup( $ssh_control_dir ) === $want_gid
     && ( fileperms( $ssh_control_dir ) & 07777 ) === 03770 ) {
    report( 'ok', "$ssh_control_dir is 3770 us3:$web_group" );
} else {
    report( 'todo', "create $ssh_control_dir as 3770 us3:$web_group" );
    if ( $apply ) {
        if ( is_link( $ssh_control_dir ) ) {
            report( 'FAIL', "$ssh_control_dir is a symlink; remove it by hand and rerun" );
        } elseif ( !is_dir( $ssh_control_dir ) && !@mkdir( $ssh_control_dir, 03770, true ) ) {
            $last = error_get_last();
            report( 'FAIL', "could not create $ssh_control_dir: " . reason( is_array( $last ) ? $last[ 'message' ] : '' ) );
        } else {
            $changes++;
            $set = chown( $ssh_control_dir, 'us3' ) && chgrp( $ssh_control_dir, $web_group )
                 && chmod( $ssh_control_dir, 03770 );
            report( $set ? 'done' : 'FAIL',
                    $set ? "$ssh_control_dir created as 3770 us3:$web_group"
                         : "$ssh_control_dir exists but its owner, group or mode could not be set" );
        }
    }
}

## elog.php's own state (dbinst#75): elog.txt and its HMAC key, so a split
## web/us3 account host does not depend on whichever account happens to
## create them first. elog.txt is provisioned empty rather than left for
## elog() to create on first use, because elog() only narrows a *brand new*
## file to 0660 shared -- provisioning it 0660 shared from the start is what
## keeps the other account able to write it too, before its first request.
## The key, unlike the log, is generated now rather than left empty for
## elog_hmac_key()'s own first-writer-wins link() dance: an empty file
## provisioned here would otherwise trip that function's own "exists but is
## empty" warning on every single request until some account happened to
## fill it.
##
## Always $us3_home/lims/etc, not $gc['global_elog_dir']: elog.php's own
## callers (queue_setup_1/2/3.php, 2DSA_1.php) call elog() before
## global_config.php is ever loaded, so an operator-set override here would
## provision one directory while elog() itself, at actual runtime, always
## logs to this same hardcoded default regardless -- a round-6 should-fix
## found by ehb54 testing it live, not by reading the code. Removed rather
## than wired up to load earlier: nothing else needs config.php loaded
## before elog() on those pages, and moving that load order is a much
## larger change than this setting is worth.
$elog_dir = "$us3_home/lims/etc";
$elog_targets = [
    "$elog_dir/elog.txt"        => [ 0660, '' ],
    "$elog_dir/elog_hmac_key"   => [ 0640, null ],   ## null content: generate 32 random bytes if missing
];
foreach ( $elog_targets as $path => $spec ) {
    list( $want_mode, $empty_content ) = $spec;
    $want_mode_str = sprintf( '0%o', $want_mode );
    if ( is_file( $path ) && !is_link( $path )
         && fileowner( $path ) === $us3_entry[ 'uid' ] && filegroup( $path ) === $want_gid
         && ( fileperms( $path ) & 07777 ) === $want_mode ) {
        report( 'ok', "$path is $want_mode_str us3:$web_group" );
        continue;
    }
    report( 'todo', "create or fix $path as $want_mode_str us3:$web_group" );
    if ( !$apply ) {
        continue;
    }
    if ( is_link( $path ) ) {
        report( 'FAIL', "$path is a symlink; remove it by hand and rerun" );
        continue;
    }
    if ( !is_file( $path ) ) {
        $content = $empty_content !== null ? $empty_content : random_bytes( 32 );
        if ( file_put_contents( $path, $content ) === false ) {
            report( 'FAIL', "could not create $path" );
            continue;
        }
    }
    $changes++;
    $set = chown( $path, 'us3' ) && chgrp( $path, $web_group ) && chmod( $path, $want_mode );
    report( $set ? 'done' : 'FAIL',
            $set ? "$path is now $want_mode_str us3:$web_group"
                 : "$path exists but its owner, group or mode could not be set" );
}

## Classifies the outcome of actually running gfac.runtime_prediction's
## CREATE TABLE (common#31's pilot table), given facts the caller has
## already gathered -- pure, no DB access, no globals -- so the "optional,
## pilot-scoped" downgrade below (a missing-privilege error is a note, not a
## FAIL) is pinned down by a real unit test instead of only ever being
## exercised against a live gfac account's actual privileges, the same gap
## that let single_node_sizing() above regress three rounds running. This
## step FAILed outright the first time it existed (744eef1), on every
## roles-built host, for exactly the two reasons this now downgrades to a
## note.
##
## $create_sql === '' (nothing left after stripping comments) is itself a
## real bug in the shipped file, not an optional-privilege gap, so it still
## FAILs. $create_ok/$create_errno/$create_error describe mysqli_query()'s
## result on $create_sql.
##
## Returns [$status, $message] for report().
function runtime_prediction_create_outcome( $create_sql, $create_ok, $create_errno, $create_error ) {
    if ( trim( (string) $create_sql ) === '' ) {
        return [ 'FAIL', "the runtime advisory pilot table's SQL file has no SQL left after"
                        . " stripping comments" ];
    }
    if ( $create_ok ) {
        return [ 'done', "gfac.runtime_prediction created" ];
    }
    if ( in_array( (int) $create_errno, [ 1142, 1044 ], true ) ) {
        ## 1142 (command denied) / 1044 (access denied to database): the gfac
        ## account lacking CREATE is an expected, optional gap for this
        ## pilot-scoped table, not an upgrade failure. An account that does
        ## have CREATE on gfac can still create it by hand with the same file.
        return [ 'note', "gfac account lacks privilege to create the optional runtime advisory"
                        . " pilot table (common#31): " . reason( (string) $create_error )
                        . "; an account with CREATE on gfac can run it by hand:"
                        . " mysql gfac < common/class/prediction/runtime_pilot_table.sql" ];
    }
    return [ 'FAIL', "could not create gfac.runtime_prediction: " . reason( (string) $create_error ) ];
}

## Purely additive and optional: $global_runtime_advisory_enabled (common#31)
## gates whether anything ever reads or writes this table, and
## CREATE TABLE IF NOT EXISTS is safe to run on every upgrade whether or not
## the pilot is turned on for this host -- that is how an admin enables it
## later without a separate migration step.
$runtime_table_sql = "$wwwpath/common/class/prediction/runtime_pilot_table.sql";
if ( !is_file( $runtime_table_sql ) ) {
    ## The runtime advisory pilot (common#31) is optional, not a required
    ## part of this upgrade: a common checkout that predates it, or one
    ## built without the pilot, is not an upgrade failure.
    report( 'note', "$runtime_table_sql not present; skipping the optional runtime advisory"
          . " pilot table (common#31) -- not required for this upgrade" );
} else {
    $exists = mysqli_query( $gdb, "SHOW TABLES IN gfac LIKE 'runtime_prediction'" );
    if ( $exists && mysqli_num_rows( $exists ) > 0 ) {
        report( 'ok', "gfac.runtime_prediction already exists" );
    } else {
        report( 'todo', "create gfac.runtime_prediction (runtime advisory pilot, common#31)" );
        if ( $apply ) {
            ## The file is documentation plus one statement: strip the '--'
            ## comment lines (which include the teardown DROP TABLE, meant to
            ## be run by hand later, never by this upgrade) and run what's left.
            $lines  = explode( "\n", (string) file_get_contents( $runtime_table_sql ) );
            $create = trim( implode( "\n", array_filter( $lines,
                function ( $line ) { return !preg_match( '/^\s*--/', $line ); } ) ) );

            $create_ok = $create !== '' ? mysqli_query( $gdb, $create ) : false;
            $errno     = 0;
            $error     = '';
            if ( $create !== '' && !$create_ok ) {
                $errno = mysqli_errno( $gdb );
                $error = mysqli_error( $gdb );
            }
            if ( $create_ok ) {
                $changes++;
            }

            list( $status, $message ) = runtime_prediction_create_outcome( $create, $create_ok, $errno, $error );
            report( $status, $message );
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

## Does this one Apache config line actually install a Content-Security-Policy
## header (as opposed to merely mentioning the phrase)? Pure, so the
## "add/append/merge/setifempty install one too, not just set" fix and the
## "unset/comment/mention do not" exclusions are each pinned down by a real
## test instead of only ever exercised against whatever happens to be on a
## test host's live Apache config.
function csp_header_line_installs_policy( $line ) {
    return (bool) preg_match(
        '/^\s*Header\s+(always\s+)?(set|add|append|merge|setifempty)\s+Content-Security-Policy\b/i',
        $line
    );
}

## ------------------------------------------------------------- 7. Content-Security-Policy

## The pages are written for util/csp's policy. It goes in Report-Only, which
## blocks nothing and logs each violation through /csp-report.php; enforcing it is
## a later, deliberate change once that log is quiet (util/csp/README.md).
step( "7. Content-Security-Policy (Report-Only)" );

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
    ##
    ## Apache's own list of files it actually loads (httpd -t -D DUMP_INCLUDES /
    ## apache2ctl -t -D DUMP_INCLUDES), not a directory walk. A directory walk
    ## has to choose a root, and either choice is wrong: conf-enabled alone (the
    ## original fix here) misses a policy set directly in sites-enabled or
    ## apache2.conf on Debian; the whole root instead, to catch those, means
    ## recursing with -R (not -r: GNU grep's -r does not follow the symlinks
    ## conf-enabled holds, which is the bug -R alone was added to fix) --
    ## which on EL8 also follows conf.d's own logs/state symlinks straight into
    ## /var/log/httpd and /var/lib/httpd. A large log file merely mentioning the
    ## header (mod_security's audit log, a LogFormat with %{...}o) then gets
    ## read whole into PHP and silently kills the run (reproduced: a 300MB
    ## error_log, exit 255 on PHP 7.2). DUMP_INCLUDES is exactly the set Apache
    ## itself resolved and actually loads, on either distro, with none of that.
    $dump_cmd = $apache[ 'service' ] === 'apache2' ? 'apache2ctl' : 'httpd';
    list( $dump_out, $dump_err, $dump_rc ) = capture( "$dump_cmd -t -D DUMP_INCLUDES 2>&1" );
    ## A non-zero exit here now FAILs outright rather than falling back to a
    ## directory walk (round-6 should-fix). The walk used to run for this
    ## exact case too, on the theory that DUMP_INCLUDES might simply be
    ## unsupported -- but both target distros support it, so in practice
    ## this only ever means Apache's own config is already broken, which the
    ## walk then masked with a worse failure mode: recursing a whole config
    ## root with -R follows conf.d's own logs/state symlinks on EL8 straight
    ## into /var/log/httpd, and a large log file merely mentioning the
    ## header (mod_security's audit log, a LogFormat with %{...}o) silently
    ## kills the run reading it whole into PHP (reproduced: a 300MB
    ## error_log, exit 255 on PHP 7.2) -- while also reporting the wrong
    ## cause (the stale DUMP_INCLUDES file listing, not Apache's real error)
    ## and, on the same broken config, mod_headers reads as not loaded too,
    ## so the run rolls back claiming "Apache rejected the policy" when
    ## Apache was never configured correctly to begin with.
    if ( $dump_rc !== 0 ) {
        report( 'FAIL', "$dump_cmd -t -D DUMP_INCLUDES failed; fix Apache's configuration first: "
                       . reason( $dump_err !== '' ? $dump_err : $dump_out ) );
    } else {
    $csp_files = [];
    foreach ( explode( "\n", $dump_out ) as $line ) {
        if ( preg_match( '#^\s*\(\S+\)\s+(/\S+)#', $line, $m ) ) {
            $csp_files[] = $m[ 1 ];
        }
    }
    ## Only a line that isn't a comment and actually sends the header, not
    ## merely names it: grep alone also matched a site note, a commented-out
    ## directive, or "Header unset Content-Security-Policy" (which removes the
    ## header rather than setting one, so it is not a policy to leave alone).
    $csp_found = [];
    foreach ( $csp_files as $file ) {
        foreach ( explode( "\n", (string) @file_get_contents( $file ) ) as $line ) {
            if ( csp_header_line_installs_policy( $line ) ) {
                $csp_found[] = $file;
                break;
            }
        }
    }
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
}

## ------------------------------------------------------------- 8. verify

step( "8. Verify" );

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
    if ( $rc !== 0 ) {
        ## test -w prints nothing on failure, so $w_err/$w_out are usually empty
        ## and the real cause is left unsaid. A failed stat means $web_user cannot
        ## even see the directory, which is an ancestor not being traversable
        ## (missing execute permission on it), not $breaker_dir's own mode.
        $stat_rc = run_as( $web_user, 'stat ' . escapeshellarg( $breaker_dir ), $stat_out, $stat_err );
        $why     = $stat_rc !== 0
                 ? "an ancestor directory of $breaker_dir is not traversable for $web_user"
                   . " (missing execute permission), not just $breaker_dir itself"
                 : ( $w_err !== '' ? $w_err : $w_out );
        report( 'FAIL', "$web_user can write the breaker directory " . reason( $why ) );
    } else {
        report( 'ok', "$web_user can write the breaker directory" );
    }

    ## Where the web account is not us3, submit_slurm.php launches the jobmonitor
    ## through 'sudo -u us3 /usr/bin/php', so the submission succeeds and the job
    ## then goes unmonitored if no sudoers rule allows it.
    if ( $web_user !== 'us3' ) {
        $monitor = "$us3_home/lims/bin/jobmonitor/jobmonitor.php";
        ## -l asks whether the command is permitted without running it: starting a
        ## real monitor here would be a side effect, and a NOPASSWD rule is matched
        ## against the command, so a cheaper stand-in would not prove anything.
        $rc = run_as( $web_user, 'sudo -n -l -u us3 /usr/bin/php ' . escapeshellarg( $monitor ),
                      $s_out, $s_err );
        if ( $rc === 0 ) {
            report( 'ok', "$web_user may launch the jobmonitor as us3" );
        } else {
            report( 'FAIL', "$web_user is not permitted 'sudo -u us3 /usr/bin/php $monitor', so submissions"
                            . " would succeed and then go unmonitored; add a NOPASSWD sudoers rule for"
                            . " that command. " . reason( $s_err !== '' ? $s_err : $s_out ) );
        }
    }

    ## The sizing gate refuses a submission outright, so a cluster can pass every
    ## check above and still take no jobs. These are that gate's own conditions
    ## (web/common/class/jobsubmit.php resource_plan()), applied per active cluster
    ## and read back from the written config rather than from what step 3 proposed.
    $final = config_vars( $global_config, $f_why );
    if ( $final === null ) {
        report( 'FAIL', "cannot re-read global_config.php to check the sizing gate: "
                        . reason( $f_why ) );
    } else {
        $final_clusters = is_array( $final[ 'cluster_details' ] ?? null )
                        ? $final[ 'cluster_details' ] : [];
        $refused = [];
        $checked = 0;
        foreach ( $final_clusters as $name => $c ) {
            if ( !is_array( $c ) || !( $c[ 'active' ] ?? false ) ) {
                continue;
            }
            $checked++;
            $ppn     = (int) ( $c[ 'ppn' ] ?? 0 );
            $maxproc = max( 0, (int) ( $c[ 'maxproc' ] ?? 0 ) );
            if ( $ppn < 1 ) {
                $refused[] = "$name has no usable tasks-per-node capacity (ppn $ppn)";
            } elseif ( !empty( $c[ 'single_node' ] ) && $maxproc > $ppn ) {
                $refused[] = "$name is single_node with maxproc $maxproc over ppn $ppn";
            }
        }
        ## An active cluster with no active probe writes no cluster_status row, and the
        ## web tier treats a cluster with no row as down. So the two files have to
        ## agree, or submission is enabled on a cluster nobody can select. This is the
        ## state an operator lands in after activating a converted national HPC entry
        ## in global_config.php alone.
        $cc_file = "$us3bin/cluster_config.php";
        $cc_vars = is_file( $cc_file ) ? config_vars( $cc_file, $cc_read_why ) : null;
        if ( $cc_vars === null ) {
            report( 'note', is_file( $cc_file )
                            ? "could not read $cc_file (" . reason( $cc_read_why )
                              . "), so the probes could not be checked against the active clusters"
                            : "no $cc_file, so no cluster has a status probe" );
        } else {
            $probes  = is_array( $cc_vars[ 'cluster_configuration' ] ?? null )
                     ? $cc_vars[ 'cluster_configuration' ] : [];
            $unprobed = [];
            foreach ( $final_clusters as $name => $c ) {
                if ( !is_array( $c ) || !( $c[ 'active' ] ?? false ) ) {
                    continue;
                }
                if ( !is_array( $probes[ $name ] ?? null ) ) {
                    $unprobed[] = "$name has no entry in cluster_config.php";
                } elseif ( empty( $probes[ $name ][ 'active' ] ) ) {
                    $unprobed[] = "$name has an inactive status probe";
                } elseif ( trim( (string) ( $probes[ $name ][ 'status' ] ?? '' ) ) === '' ) {
                    $unprobed[] = "$name has an active probe with no status command";
                }
            }
            if ( $unprobed ) {
                report( 'FAIL', "active cluster(s) the web tier will grey out, because nothing"
                                . " writes their cluster_status row: " . implode( '; ', $unprobed ) );
            } else {
                report( 'ok', "every active cluster has an active status probe" );
            }
        }

        if ( $refused ) {
            report( 'FAIL', "cluster(s) would refuse every job: " . implode( '; ', $refused ) );
        } elseif ( $checked === 0 ) {
            report( 'note', "no active cluster to check against the submission sizing gate" );
        } else {
            report( 'ok', "all $checked active cluster(s) pass the submission sizing gate" );
        }
    }
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
} elseif ( $apply && ( $needs_input || $declined ) ) {
    echo "All checks passed, but ";
    if ( $needs_input ) {
        echo "$needs_input setting(s) still need a value; see the [input] lines above"
           . " and rerun with the option each one names.\n";
    }
    if ( $declined ) {
        echo ( $needs_input ? "Also, " : '' ) . "$declined change(s) were declined and so were not"
           . " made; see the confirmations above and rerun to decide them again.\n";
    }
} else {
    echo "All checks passed.\n";
}
if ( $apply && $changes ) {
    global $util_backup_dir;
    if ( isset( $util_backup_dir ) && strlen( $util_backup_dir ) ) {
        echo "\nOriginals of every file changed or appended to: $util_backup_dir\n"
           . "See \"Undoing it\" in --help before rolling any of it back.\n";
    }
}
## Gated on nothing outstanding (no FAIL, no [input], no decline), not on
## $changes: a clean rerun after fixing a prior FAIL by hand often makes no
## further change this time, and never printed these instructions even
## though the host was then actually ready to finish. Still never gated on
## $changes alone either way: printing "start the services" with a check
## still failing told the operator to bring the host back up as though the
## run had finished cleanly.
if ( $apply && !$failures && !$needs_input && !$declined ) {
    ## The preflight required an idle host, so this is a start, not a restart, and
    ## it runs as us3: started as root the services would leave root-owned state.
    echo "\nFinish the upgrade in this order:\n"
       . "  1. re-enable the LIMS cron entries that were commented out for the upgrade,\n"
       . "     but NOT any gridctl entry: step 6 removed those deliberately, because each\n"
       . "     job's own jobmonitor now carries it to a terminal state. Re-enabling one\n"
       . "     puts the sweep back and undoes part of this upgrade\n"
       . "  2. start the services as us3:   sudo -u us3 bash -c 'cd $us3bin && php services.php start'\n"
       . "  3. refresh the cluster health table once, so no cluster shows as stale:\n"
       . "     sudo -u us3 php $gridctl_dir/cluster_status.php\n"
       . "  4. confirm no gridctl entry came back:\n"
       . "     crontab -l -u us3 | grep gridctl ; grep -r gridctl /etc/crontab /etc/cron.d\n";
}
## A dry run with work outstanding is not a failure, so it exits 0. Under --apply,
## an [input] item is one thing that can still be outstanding; a confirm() the
## operator answered no to is another, and both leave real work undone, so both
## have to fail the exit code or a declined change looks like a clean run.
exit( $failures || ( $apply && ( $needs_input || $declined ) ) ? 1 : 0 );
