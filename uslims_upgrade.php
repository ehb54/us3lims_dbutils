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
require_once __DIR__ . "/migrations.php";

## Report failed connections and queries as values, not exceptions (PHP 8.1+)
mysqli_report( MYSQLI_REPORT_OFF );

## The release every stack checkout must be at before this host can be upgraded.
$required_version = "4.3.0";

$notes = <<<__EOD
usage: $self {options}

Upgrade this host to the Slurm submission contract (gridctl#33, common#24, dbinst#57).
Without --apply nothing is changed; each step reports what it would do. Run with --check
for that dry run; with no options at all this help is printed instead.

--apply first runs that dry run itself and refuses, before changing anything, while any
[input] line is open (a value it would otherwise ask for and stop at). --check exits 3 when
an [input] is open and nothing failed, 1 when a check failed, and 0 otherwise; its last line
is "SUMMARY failures=N pending=N input=N changes=N".

This script changes this host's configuration, plus one schema fix step 9 runs inside
each dbinstance (see below). Otherwise, the stack code and the database schema are
upgraded separately, and the code must be upgraded first: pull common,
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

Also stop the web server (or otherwise block access) before pulling the new code, so
nothing can write to any LIMS database while this runs -- README.md's "Upgrading a host"
documents why that is what makes step 9's UTC migration and every config rewrite below
safe. Step 1 cannot tell this host's first --apply after a pull apart from a routine
re-run on an already-upgraded host, so it only notes the web server's state rather than
refusing on it; for that first run specifically, stop it.

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
4 : records the host key for each active cluster and any cluster named with
    --activate that isn't active yet (and, for the host's own cluster, authorizes
    the key) and checks ssh for us3 and the web account
5 : creates the shared circuit-breaker directory, the shared ssh-control directory
    (common#24, sticky bit so one account cannot rename another's subdirectory
    under it), elog.txt and its HMAC key (dbinst#75, provisioned shared from the
    start so a split web/us3 account host does not depend on whichever account
    happens to touch them first), and gfac.runtime_prediction (the runtime
    advisory pilot table, common#31 -- optional, pilot-scoped; a missing SQL
    file or no CREATE privilege is a note here, not a failure)
6 : removes the gridctl cron entries (gridctl.php, and the gridctl_pro/dev names before it)
7 : installs the Content-Security-Policy as Report-Only unless a policy is already
    configured (enforcing it is a later step), and installs the /common/{css,images,js}
    Apache alias us3lims-roles' config-lims-apache role normally provides, unless one is
    already configured -- without it, every page's CSS/images/JS 404 after a hand upgrade
8 : verifies the result, including that every active cluster passes the submission
    sizing gate rather than refusing every job
9 : migrates each dbinstance's people_audit.created_at to UTC if not already done
    (dbutils#52), using the running config.php (or overlay) each instance already
    connects with -- idempotent, so a host dbupgrade/stage1+stage2 already migrated
    is reported 'ok' here, not migrated again

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
     none of them changes anything left alone;
  5. remove step 7's installed policy, /etc/httpd/conf.d/lims-csp.conf or
     /etc/apache2/conf-available/lims-csp.conf (Debian: "a2disconf lims-csp"
     first), and reload Apache -- step 7 never backs this one up, since it is
     new, not rewritten.
  6. remove step 7's installed /common alias the same way, /etc/httpd/conf.d/
     lims-common-alias.conf or /etc/apache2/conf-available/lims-common-alias.conf
     (Debian: "a2disconf lims-common-alias" first), and reload Apache -- also
     new, not rewritten, and also not backed up.

Nothing else to undo in the database, with three exceptions. Step 5's gfac.runtime_prediction
(see step 5 above) is optional, pilot-scoped and empty until the pilot is turned on;
dropping it is a separate, deliberate operator step (the teardown statement is documented
in common's class/prediction/runtime_pilot_table.sql), not part of rolling back this script.
Step 5 also creates gfac.metascheduler_decision and gfac.cluster_status_nudge (gridctl's
pick_cluster.php tables) the same optional, purely-additive way -- empty and unused unless
a metascheduler pseudo-cluster is actually configured; dropping them, if ever wanted, is
also a separate operator step, not part of rolling back this script.
Step 9 (see step 9 above) rewrites people_audit.created_at in every dbinstance it migrates,
setting a created_at_utc flag on each row it converts (and on every row dbinst's own
write_audit_row() has written UTC from the start); it also adds a marker table there
(_dbutils_people_audit_utc_migrated) recording when and from which zone, kept for history.
This is a one-time, one-direction conversion with no undo step of its own -- a pre-migration
backup of each dbinstance's own database is the way back if a conversion turns out to be
wrong for some instance. Restoring one is self-correcting rather than something to verify by
hand: the "is this row already UTC" question reads from the row's own
created_at_utc flag, not from the marker table or a boundary snapshot, so a restored row (which
carries no such flag, win or lose, since the column postdates any pre-migration backup) reads as
unconverted again on the very next run and gets converted correctly, with no dependency on
whether the marker table or boundary table happened to survive the restore too.

Step 0 (run before step 9, before steps 1-8 do anything else) records a boundary -- the highest
people_audit.auditID already written at the moment this run started -- in its own table
(_dbutils_people_audit_utc_boundary). This is informational only now (which rows
step 9 actually converts no longer depends on it at all); it remains for migrate_people_audit_
utc.php's own standalone refuse-without-a-boundary check and for step 0's report. Rolling back to
a release that writes local time again, then running this instance for a while before ever
retrying the upgrade, is likewise self-correcting now: a local-time row written during that
rollback window has no created_at_utc flag set (old code never sets it) and so correctly reads
as still needing conversion on the next run, regardless of its auditID or how long the rollback
window was open.

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
                               --activate checks ssh before step 4 (above, in the numbered steps), which
                               records the host key (and, for the host's own cluster, authorizes the
                               key). So the first --apply --activate for a cluster records its host
                               key, and the next one activates it -- not only for a cluster converted
                               in this very run: any cluster whose host key is not recorded yet needs
                               the same two runs. For a remote cluster, authorize the account's own
                               key on the cluster by hand first; step 4 only ever records that
                               cluster's host key, never authorizes this account's key on the far end.

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

## --apply first runs the same checks without --apply and refuses, before any change, while an
## [input] it cannot resolve is open. The check pass itself has USLIMS_UPGRADE_PREFLIGHT set.
if ( $apply && getenv( 'USLIMS_UPGRADE_PREFLIGHT' ) === false ) {
    echo "Checking for settings this run cannot resolve before changing anything...\n";
    $check   = capture( apply_check_command( PHP_BINARY, $self, array_slice( $argv, 1 ), interactive() ) );
    $refusal = apply_refusal( $check[ 0 ] );
    if ( $refusal !== null ) {
        error_exit( $refusal );
    }
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

## An [input] that --apply asks for at a terminal ($asked_on_apply) is a todo in the check pass an
## interactive --apply runs first, since that apply resolves it; anything else stays [input].
function report_input( $msg, $asked_on_apply ) {
    if ( $asked_on_apply && getenv( 'USLIMS_UPGRADE_PREFLIGHT' ) === 'interactive' ) {
        report( 'todo', "$msg; asked for during --apply" );
    } else {
        report( 'input', $msg );
    }
}

## The check pass --apply runs before changing anything: the same options without --apply, told
## whether the apply after it can ask at a terminal.
function apply_check_command( $php, $self, array $args, $interactive ) {
    $args = array_values( array_filter( $args, function ( $a ) { return $a !== '--apply'; } ) );
    return 'USLIMS_UPGRADE_PREFLIGHT=' . ( $interactive ? 'interactive' : 'batch' ) . ' '
         . escapeshellarg( $php ) . ' ' . escapeshellarg( $self )
         . ( count( $args ) ? ' ' . implode( ' ', array_map( 'escapeshellarg', $args ) ) : '' );
}

## Null when the check pass left no [input] open, else why --apply refuses. Without this an apply
## carried out every step before the first [input] and left the host half-converted.
function apply_refusal( $output ) {
    $open = preg_grep( '/^\s*\[input\]/', explode( "\n", (string) $output ) );
    if ( !count( $open ) ) {
        return null;
    }
    return "--apply would stop at these, after changing everything before them:\n"
         . implode( "\n", $open ) . "\nRerun with the option each one names. Nothing was changed.";
}

## 1 for a FAIL, or for work an --apply left open; 3 for a dry run with an open [input], so a
## caller can refuse before pulling new code onto the host; otherwise 0.
function upgrade_exit_code( $apply, $failures, $needs_input, $declined ) {
    if ( $failures ) {
        return 1;
    }
    if ( $apply ) {
        return ( $needs_input || $declined ) ? 1 : 0;
    }
    return $needs_input ? 3 : 0;
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
## ("backup-<timestamp>-pid<pid>" in the current directory) is never the
## one used.
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

## The $common_dir value every other on-disk default already assumes for a
## us3lims-roles-laid-out host: webinfo's and newinst's own config templates
## both hardcode '/srv/www/htdocs/common/' for exactly this reason (the
## real roles layout, not the dev Docker container's /var/www/html/common/).
## Pure so step 9's instance catch-up can be tested without a live host.
function common_dir_default( $wwwpath ) {
    return rtrim( $wwwpath, '/' ) . '/common/';
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

## capture() and config_vars() now live in utility.php: migrate_people_audit_utc.php
## (dbutils#52) needs config_vars() too, to read an instance's config.php without
## this script's own $gdb/$old_listen/global_config.php setup.

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

## Parses 'systemctl show -p A -p B ... unit' output into an associative
## array, independent of line order or which properties were asked for.
## Deliberately NOT using --value (which prints only the bare values, one per
## line, in whatever order systemd feels like -- see the bug note below):
## the labeled 'Property=value' form this parses is the only shape systemd
## actually documents a contract for, so a caller can pick a property out by
## name and never again depend on an ordering systemd doesn't promise.
function systemctl_show_properties( $output ) {
    $props = [];
    foreach ( explode( "\n", (string) $output ) as $line ) {
        if ( preg_match( '/^([A-Za-z0-9_]+)=(.*)$/', $line, $m ) ) {
            $props[ $m[ 1 ] ] = $m[ 2 ];
        }
    }
    return $props;
}

## Classify the us3-listen systemd unit's configured account from
## systemctl's own raw output, pure so the decision is testable without a
## live systemd. $rc is systemctl show's own exit status; $loadState and
## $user are the unit's LoadState/User property values, already picked out
## by name (systemctl_show_properties() above) -- never assumed to be in any
## particular line order.
##
## $rc stays 0 and $user stays '' for a unit that was never installed at
## all (a dev/test host, or one not built by us3lims-roles, or a fresh
## host before roles has run yet) -- systemctl show still succeeds and
## reports default/empty property values rather than failing outright, so
## $loadState ('not-found' instead of 'loaded') is what actually
## distinguishes "not installed" from "installed with no User= line",
## which $user alone cannot tell apart (both read '').
##
## THE BUG this answers: --check
## reported "All checks passed" on a host whose us3-listen unit had no
## User= line at all. gridctl#33's services.php now refuses to start as
## root, so that host could never actually start the gridctl services --
## not a style nit, a host that can never come up after a reboot.
##
## THE FOLLOW-UP BUG: an earlier fix read $loadState/$user
## positionally off 'systemctl show -p LoadState -p User --value''s two
## output lines, assuming systemd always prints them in the -p flag order.
## On systemd 239 it prints User before LoadState -- the reverse -- so
## every host on that version read $user into $loadState's slot and vice
## versa, and the check passed on every one of 12 variants tried, including
## a unit with no User= line at all: the exact case this was written to
## catch. Fixed by dropping --value for the labeled 'Property=value' form
## (systemctl_show_properties() above) and reading each property by name,
## which cannot be thrown off by any ordering systemd chooses on any
## version, past or future.
function us3_listen_unit_user_outcome( $rc, $loadState, $user ) {
    if ( $rc !== 0 ) {
        return [ 'note', "could not query the us3-listen systemd unit (systemctl exited $rc);"
                        . " skipping the User= check" ];
    }

    if ( $loadState !== 'loaded' ) {
        return [ 'note', "us3-listen.service is not installed on this host ($loadState);"
                        . " skipping the User= check" ];
    }

    if ( $user === '' || $user === 'root' ) {
        return [ 'FAIL', "the us3-listen systemd unit has no User=us3 ("
                        . ( $user === '' ? 'unset, defaults to root' : 'explicitly set to root' )
                        . "); services.php now refuses to start as root, so gridctl can never actually"
                        . " start on this host until the unit is fixed: systemctl edit us3-listen"
                        . " and add 'User=us3' under [Service], then systemctl daemon-reload" ];
    }

    if ( $user !== 'us3' ) {
        return [ 'FAIL', "the us3-listen systemd unit runs as '$user', not us3; services.php refuses"
                        . " to start as anything but us3" ];
    }

    return [ 'ok', "the us3-listen systemd unit runs as us3" ];
}

## A gridctl daemon script's basename, matched against an ExecStart=/cron/
## rc.local line.
## services.php start is the documented launcher (uslims_upgrade.php --help's
## own procedure, step 4: "Restart services") for all four daemons at once --
## an entry naming it directly (a systemd
## unit, a root @reboot cron line, rc.local) started every gridctl daemon
## exactly as much as one naming listen.php or esign.php by name, but none
## of those checks ever matched it, since it names none of the four scripts
## literally. \bstart\b distinguishes it from "services.php stop", which
## starts nothing.
function gridctl_daemon_script_pattern() {
    return '#\b(listen|manage-us3-pipe|submitctl|esign)\.php\b|\bservices\.php\b[^\n]*\bstart\b#';
}

## us3_listen_unit_user_outcome() above
## only ever checked the one unit us3lims-roles itself installs
## (us3-listen.service) -- a DIFFERENT systemd unit someone else added
## with no User= line, a root crontab's @reboot entry, or an /etc/rc.local
## line, could each just as easily start a gridctl daemon as root on the
## next reboot, with nothing here to catch any of them.
##
## Pure (no systemctl/cron/file-read calls of its own), so the decision is
## testable without a live system -- same reasoning as every other
## process/unit check in this file. Each input is already-gathered data:
## $service_units is [ unit_name => [ 'ExecStart' => ..., 'User' => ... ] ]
## for every OTHER installed .service unit (us3-listen.service itself is
## excluded by the caller, already checked above); $cron_lines is every
## line from /etc/crontab and /etc/cron.d/*, concatenated; $rc_local is
## /etc/rc.local's contents, or '' if it does not exist.
##
## $root_crontab: root's own personal crontab (`crontab -l -u root`), round
## 15 should-fix, dbutils#52 -- $cron_lines (the SYSTEM crontab, /etc/crontab
## and /etc/cron.d/*) was the only cron source ever checked here, but a
## line in root's own personal crontab runs as root unconditionally, with
## no user column of its own to read (cron already knows whose crontab it
## is), and nothing read that file at all. Checked separately, every
## matching line counts, not just @reboot ones: unlike the system crontab,
## there is no OTHER user a personal crontab's line could belong to for
## this one to rule out.
## @return list<string> one message per risk found; empty when none are.
function other_root_start_risks( array $service_units, array $cron_lines, $rc_local, $root_crontab = '' ) {
    $pattern = gridctl_daemon_script_pattern();
    $risks   = [];

    foreach ( $service_units as $unit => $props ) {
        if ( !preg_match( $pattern, $props[ 'ExecStart' ] ?? '' ) ) {
            continue;
        }
        $user = $props[ 'User' ] ?? '';
        if ( $user === '' || $user === 'root' ) {
            $risks[] = "$unit starts a gridctl daemon with no User=us3 ("
                     . ( $user === '' ? 'unset, defaults to root' : 'explicitly set to root' ) . ')';
        }
    }

    ## /etc/crontab's own column layout names the user directly (minute hour
    ## dom month dow user command); a @reboot line keeps the same shape, just
    ## with the time fields replaced by the one word "@reboot". /etc/cron.d/*
    ## files share this system-crontab shape (unlike a per-user crontab,
    ## which has no user column at all -- cron already runs those as their
    ## owner, never root, unless the owner itself is root).
    foreach ( $cron_lines as $line ) {
        if ( preg_match( '/^\s*@reboot\s+(\S+)\s+(.*)$/', $line, $m )
             && preg_match( $pattern, $m[ 2 ] )
             && $m[ 1 ] === 'root' ) {
            ## Round 15 nit, dbutils#52: \@ is not a recognized escape in a
            ## double-quoted PHP string, so the backslash printed literally
            ## ("a root \@reboot cron entry...") instead of being dropped.
            $risks[] = "a root @reboot cron entry starts a gridctl daemon: " . trim( $line );
        }
    }

    foreach ( explode( "\n", (string) $root_crontab ) as $line ) {
        if ( preg_match( '/^\s*#/', $line ) || trim( $line ) === '' ) {
            continue;
        }
        if ( preg_match( $pattern, $line ) ) {
            $risks[] = "an entry in root's own crontab starts a gridctl daemon: " . trim( $line );
        }
    }

    if ( $rc_local !== '' && preg_match( $pattern, $rc_local ) ) {
        $risks[] = "/etc/rc.local starts a gridctl daemon, and rc.local always runs as root";
    }

    return $risks;
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
## Pulled out so a test can pin down the pattern itself (which process
## names it matches, how it avoids matching an unrelated one) without
## needing a real httpd/php-fpm running -- same reasoning as every other
## pure-string-building function in this file.
function web_server_process_regex() {
    return '#^\s*(\d+)\s+(?:\S*/)?(httpd|apache2|nginx|php-fpm)(\s|:|$)#';
}

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
        ## Round 15 nit, dbutils#52: "under an hour old" used to block on
        ## its own, even for a claim whose own recorded owner is
        ## confirmed dead (ran: a 67-second-old claim, from a monitor that
        ## crashed moments after creating it, still blocked --check for
        ## up to an hour). A recorded owner's liveness is authoritative
        ## when it is checkable at all: ESRCH (errno 3, "No such
        ## process") means genuinely dead, regardless of age; any other
        ## posix_kill() failure (most commonly EPERM, a different account)
        ## proves nothing either way, so the age window still decides, as
        ## it always did when there is no owner recorded to check at all.
        $owner_alive = false;
        if ( $owner ) {
            $owner_alive = @posix_kill( $owner, 0 ) || posix_get_last_error() !== 3;
        }
        if ( $owner_alive || ( !$owner && $age < 3600 ) ) {
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

## What used to be here:
## this step used to branch on instance_is_new_under_required_version(),
## which read $label's own dbinst checkout VERSION and treated
## "already at $required_version" as proof the instance was created fresh
## by newinst under current code -- true for a newinst-created instance,
## but also true for a genuinely pre-existing instance, because the
## documented upgrade procedure (see this script's own --help) pulls
## *every existing instance's* dbinst checkout to $required_version too,
## before step 0 ever runs. Both cases read the identical VERSION by the
## time this code sees them, so that branch could not actually tell them
## apart -- it marked every pre-existing instance "done" with nothing
## converted. (This replaced a flat, host-level "known instance
## names" file, which had the same class of problem from the other
## direction.)
##
## record_people_audit_utc_boundary() below does not need this
## classification at all: it reads $db.people_audit's own MAX(auditID)
## directly, which is 0 for an instance with no table or no rows yet (a
## fresh newinst-created one) and the real high-water mark for a
## pre-existing one -- the data itself already distinguishes the two
## cases, with no VERSION-based guess required.

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

## dbinst#75's "in-place window": a row dbinst's already-
## upgraded code writes between code going live and step 9 actually
## running gets shifted a second time by step 9 itself, since step 9
## otherwise has no way to tell it apart from a genuinely pre-upgrade row.
## Recording each instance's current max people_audit.auditID here --
## the earliest point this run can reach it, before steps 1-8 do anything
## else -- narrows that window to this run's own duration instead of
## however long the code was already live before this run even started
## (closing it fully needs dbinst itself to keep writing local time until
## marked, which is dbinst#75's own half of this item). Only on --apply:
## a --check run changes nothing in any dbinstance, including this, and
## recording it during a --check that is not immediately followed by
## --apply would not narrow anything anyway. Errors here are left for
## step 9 to report by name; this only ever records a boundary for an
## instance step 9 can also reach.
##
## Pulled into its own function so
## it can be driven directly in a test with a constructed instance tree,
## the same way migrations.php's functions already are -- the bug this
## replaced (branching on instance_is_new_under_required_version(), which
## read VERSION and could not distinguish a pre-existing instance the
## mandated pull also brings to $required_version from a newinst-created
## one) was never actually exercised against a real discover_lims_
## instance_configs() tree before now.
function run_people_audit_utc_boundaries( $wwwpath, $us3_home ) {
    $changes = 0;
    foreach ( discover_lims_instance_configs( $wwwpath, $us3_home ) as $label => $cfg ) {
        if ( isset( $cfg[ 'error' ] ) || !is_sql_identifier( $cfg[ 'dbname' ] ) ) {
            continue;
        }
        $conn = @mysqli_connect( $cfg[ 'dbhost' ], $cfg[ 'dbusername' ], $cfg[ 'dbpasswd' ], $cfg[ 'dbname' ] );
        if ( !$conn ) {
            continue;
        }

        ## Already resolved in an earlier run -- checked against this
        ## instance's own persistent, per-database markers (set by this
        ## same block, or by step 9), never against step-0's run history on
        ## this host. An instance that failed to connect on every earlier
        ## run (a stale password, a restored backup) is still correctly
        ## recognized here the first time it finally does.
        if ( people_audit_utc_migration_done( $conn, $cfg[ 'dbname' ] )
             || people_audit_utc_boundary( $conn, $cfg[ 'dbname' ] ) !== null ) {
            mysqli_close( $conn );
            continue;
        }

        ## Round 15 nit, dbutils#52: only a real, successful INSERT counts
        ## -- this used to increment unconditionally, so a failed one (a
        ## stale password that connects but can't write, say) was still
        ## reported as "recorded ... boundary for N instance(s)."
        if ( record_people_audit_utc_boundary( $conn, $cfg[ 'dbname' ] ) ) {
            $changes++;
        }

        mysqli_close( $conn );
    }
    return $changes;
}

## Step 8's two sizing/probe checks, pulled into their own functions for the
## same reason run_people_audit_utc_boundaries() above was: so they can be driven directly in a test against
## a constructed $cluster_details array, including a metascheduler pool
## entry, rather than only through the full script's own output. Both skip
## a pool entry via is_metascheduler_pool_entry() -- step 3's own existing
## detector (defined further up this file), not reimplemented here, so this
## and step 3's retirement carve-out can never disagree on the shape.
function sizing_gate_refusals( $final_clusters ) {
    $refused = [];
    $checked = 0;
    foreach ( $final_clusters as $name => $c ) {
        if ( !is_array( $c ) || !( $c[ 'active' ] ?? false ) || is_metascheduler_pool_entry( $c ) ) {
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
    return [ 'refused' => $refused, 'checked' => $checked ];
}

function unprobed_active_clusters( $final_clusters, $probes ) {
    $unprobed = [];
    foreach ( $final_clusters as $name => $c ) {
        if ( !is_array( $c ) || !( $c[ 'active' ] ?? false ) || is_metascheduler_pool_entry( $c ) ) {
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
    return $unprobed;
}

if ( $apply ) {
    ## Counted before step 1's idle
    ## check below, which used to tell an operator "Nothing was changed"
    ## on refusing a busy host -- not true once a real write already
    ## landed moments earlier.
    $boundaries_recorded = run_people_audit_utc_boundaries( $wwwpath, $us3_home );
    $changes += $boundaries_recorded;
    ## This used to run and change the count
    ## silently -- every other action in this script reports what it did,
    ## so a run that only did this (every instance already migrated or
    ## connecting) looked identical to one that skipped it entirely.
    if ( $boundaries_recorded > 0 ) {
        report( 'ok', "recorded this run's UTC-migration boundary for $boundaries_recorded instance(s)" );
    }
}

## ------------------------------------------------------------- 1. preflight

## An upgrade runs on an idle host, so every check below has to agree that
## nothing is running. Handling live jobs was the earlier design; with the
## gridctl.php sweep gone and no claimless pre-upgrade monitor left to guard
## against, an idle host is the only state worth reasoning about, and it is the
## state an operator can actually confirm. Nothing else below this point is
## changed until all of these pass -- the UTC-boundary recording immediately
## above this step is the one exception, deliberately run first so every
## reachable instance's boundary is drawn as early as this script can reach
## it (see that block's own comment); its own $changes is accounted for
## below, not assumed away.
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

## -- the us3-listen unit's own account: gridctl#33
## now refuses to start as root, so a unit with no User=us3 means this host
## can never actually bring the gridctl services up at all. Not an idle-host
## precondition (so it does not add to $busy[]) -- a mistake here is a
## standing problem on every future reboot, not something to retry once a
## current run finishes.
[ $unit_show_out, , $unit_show_rc ] = capture(
    'systemctl show -p LoadState -p User us3-listen.service' );
$unit_show_props = systemctl_show_properties( $unit_show_out );
list( $unit_status, $unit_msg ) = us3_listen_unit_user_outcome(
    $unit_show_rc, $unit_show_props[ 'LoadState' ] ?? '', $unit_show_props[ 'User' ] ?? '' );
report( $unit_status, $unit_msg );

## -- every OTHER way a gridctl daemon could start as root: a different
## systemd unit, a root @reboot cron
## entry, or /etc/rc.local. Same "not an idle-host precondition" reasoning
## as the us3-listen check just above -- a standing boot-time problem, not
## something retrying this run fixes.
[ $unit_list_out ] = capture( "systemctl list-units --type=service --all --no-legend --plain"
                             . " | awk '{print \$1}'" );
$other_service_units = [];
foreach ( explode( "\n", (string) $unit_list_out ) as $unit ) {
    $unit = trim( $unit );
    if ( $unit === '' || $unit === 'us3-listen.service' ) {
        continue;
    }
    [ $props_out ] = capture( 'systemctl show -p ExecStart -p User ' . escapeshellarg( $unit ) );
    $other_service_units[ $unit ] = systemctl_show_properties( $props_out );
}
$cron_lines = [];
foreach ( array_merge( [ '/etc/crontab' ], glob( '/etc/cron.d/*' ) ?: [] ) as $cron_file ) {
    $contents = @file_get_contents( $cron_file );
    if ( $contents !== false ) {
        $cron_lines = array_merge( $cron_lines, explode( "\n", $contents ) );
    }
}
$rc_local = (string) ( @file_get_contents( '/etc/rc.local' ) ?: '' );
list( $root_crontab_out, , $root_crontab_rc ) = capture( 'crontab -l -u root' );
$other_risks = other_root_start_risks( $other_service_units, $cron_lines, $rc_local,
                                        $root_crontab_rc === 0 ? $root_crontab_out : '' );
if ( $other_risks ) {
    foreach ( $other_risks as $risk ) {
        report( 'FAIL', $risk );
    }
} else {
    report( 'ok', "no other systemd unit, root cron entry, or rc.local starts a gridctl daemon" );
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

## The README's own step 1 ("Stop the web
## server... so nothing can write to any LIMS database while the upgrade
## runs") is what makes the FIRST --apply after a genuine 4.2.0->4.3.0 pull
## safe: step 9's UTC migration and every config rewrite assume no web
## request can read or write anything during that one-time transition.
## --help never said so, only naming the gridctl services above, so an
## operator following --help alone for that first transition could leave
## the web server up with nothing here to catch it.
##
## Not added to $busy[], unlike the gridctl-service check above: a FAIL
## here would also stop every ROUTINE --apply on an already-transitioned,
## steady-state host (us3lims-roles calls --apply on every playbook
## run), where the web server running is normal and
## expected -- nothing left for it to unsafely race once every instance
## is already past this host's one-time transition. This script has no
## persistent "has this host's transition already happened" signal to
## gate a hard failure on (deliberately not built, since newinst-created
## instances never go through this transition at all and a flag keyed to
## the host alone cannot tell those apart from a genuinely pre-existing
## one), so this is informational, matching the
## us3-listen unit-account check just above it in spirit: tell the
## operator plainly, let them judge whether this run is that first
## transition or routine convergence.
$webservers = processes( web_server_process_regex() );
if ( $webservers === null ) {
    report( 'note', "could not read the process list, so the web server's state is unknown" );
} elseif ( $webservers ) {
    report( 'note', "a web server or php-fpm is running (pid " . implode( ', ', array_keys( $webservers ) )
                   . "). If this is this host's first --apply after pulling 4.3.0 code, stop it"
                   . " first (README: \"Upgrading a host\") -- nothing here can tell a first"
                   . " transition apart from a routine re-run, so this is a note, not a failure." );
} else {
    report( 'ok', "no web server or php-fpm (httpd, apache2, nginx) is running" );
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
$has_process_priv = has_global_process_or_super_privilege( mysqli_grant_rows( $gdb ) );

$res = mysqli_query( $gdb, 'SHOW PROCESSLIST' );
if ( !$res ) {
    report( 'note', "could not list the database connections (" . reason( mysqli_error( $gdb ) )
                    . "); confirm nobody is using MariaDB" );
} else {
    $processlist_rows = [];
    while ( $row = mysqli_fetch_assoc( $res ) ) {
        $processlist_rows[] = $row;
    }
    $others = other_db_connections( $processlist_rows );
    if ( $others ) {
        report( 'FAIL', count( $others ) . " active database connection(s): "
                        . implode( ', ', $others ) );
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

## Whether $grants (one string per SHOW GRANTS row for the connected
## account) includes the global-only PROCESS or SUPER privilege (or ALL
## PRIVILEGES at global scope, which implies both). Pure, no DB access, so
## it is pinned down by a real unit test (in the lims aggregator's test
## suite, not this repo) instead of only ever being exercised against
## whatever privileges the account running this script happens to have.
function has_global_process_or_super_privilege( array $grants ) {
    foreach ( $grants as $grant ) {
        $grant = (string) $grant;
        ## PROCESS and SUPER are global-only privileges in MySQL/MariaDB: they
        ## cannot be granted on a specific database. But MariaDB always
        ## expands "ALL" to "ALL PRIVILEGES" in SHOW GRANTS output, even for a
        ## database-scoped grant like "GRANT ALL ON gfac.* TO ...", so
        ## matching the word alone without checking the scope matched a grant
        ## that gives nothing outside gfac and no real PROCESS privilege.
        if ( preg_match( '/\bON\s+\*\.\*(\s|$)/i', $grant )
           && preg_match( '/\b(PROCESS|SUPER|ALL PRIVILEGES)\b/i', $grant ) ) {
            return true;
        }
    }
    return false;
}

## Reduces SHOW PROCESSLIST's rows (each an assoc array with at least
## Command/Info/User/Host) to the other connections that mean the database
## is actually in use: excludes this account's idle ('Sleep') threads,
## MariaDB's own background threads (InnoDB purge coordinator/workers, the
## shutdown handler -- Command = 'Daemon', not a client connection at all,
## and always visible to an account with PROCESS/SUPER, which is exactly the
## account this check most wants to trust), and this script's own
## SHOW PROCESSLIST call. Pure, no DB access, so the false FAIL this
## produced for any gfac account with PROCESS privilege -- MariaDB's own
## system threads always being present and always reported as "system
## user"@"" -- is pinned down by a real unit test (in the lims aggregator's test
## suite, not this repo) instead of only ever being exercised against a
## live account's actual threads.
function other_db_connections( array $rows ) {
    $others = [];
    foreach ( $rows as $row ) {
        $command = (string) ( $row[ 'Command' ] ?? '' );
        if ( strcasecmp( $command, 'Sleep' ) === 0 || strcasecmp( $command, 'Daemon' ) === 0 ) {
            continue;
        }
        if ( stripos( (string) ( $row[ 'Info' ] ?? '' ), 'PROCESSLIST' ) !== false ) {
            continue;
        }
        $others[] = ( $row[ 'User' ] ?? '' ) . '@'
                  . preg_replace( '/:\d+$/', '', (string) ( $row[ 'Host' ] ?? '' ) );
    }
    return array_unique( $others );
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
                . "Quiet it down and rerun. "
                . ( $changes
                  ? "$changes change(s) were already made (the UTC-boundary recording above this"
                    . " step); nothing past that point was"
                  : "Nothing was" )
                . " changed" );
}

## ------------------------------------------------------------- 2. listen-config.php

step( "2. listen-config.php (values only, version 2)" );

run_host_migrations( $gdb, $required_version, $apply, [
    'listen_config' => $listen_config,
    'template'      => $template,
    'old_listen'    => $old_listen,
    'wwwpath'       => $wwwpath,
], [ 'listen_config_v2' ] );

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
## the daemons used another: step 5 would report it created, step 8 (Verify)
## would report it writable, and neither statement would be about the
## directory in use. Step 8, not step 7: Verify is step 8, after the CSP
## step (7).
$configured_breaker = isset( $gc[ 'global_circuit_breaker_dir' ] )
                      ? trim( (string) $gc[ 'global_circuit_breaker_dir' ] ) : '';
if ( $configured_breaker !== '' ) {
    $breaker_dir = rtrim( $configured_breaker, '/' );
    report( 'note', "global_config.php sets the breaker directory to $breaker_dir" );
}

$managed = [];     ## PHP assignments for the managed block, in order
$clusters = is_array( $gc[ 'cluster_details' ] ?? null ) ? $gc[ 'cluster_details' ] : [];
$active   = array_filter( $clusters, function ( $c ) { return is_array( $c ) && ( $c[ 'active' ] ?? false ); } );

## A metascheduler pool (pick_cluster.php, gridctl#35) is recognized the one
## and only way anything in this stack recognizes it -- a 'clusters' list --
## which is this function's exact copy of pick_cluster_is_metascheduler_entry()
## (gridctl's pick_cluster.php) and require_cluster.php's own copy of the same
## check. It has no host of its own to run env_script_lines/node-sizing/SSH
## checks against, so it is set aside from $active before any of those run,
## but -- unlike the submittype-based retirement below -- it is never
## deactivated or reported as retired: a pool IS the Slurm-submittable path
## now (pick_cluster_reserve() resolves it to a real cluster before Slurm
## ever sees the submission), not something the Slurm code cannot submit to.
function is_metascheduler_pool_entry( $c ) {
    return is_array( $c ) && isset( $c[ 'clusters' ] ) && is_array( $c[ 'clusters' ] );
}
$pools = array_filter( $active, 'is_metascheduler_pool_entry' );
foreach ( $pools as $name => $c ) {
    report( 'ok', "'$name' is a metascheduler pool (fans out to " . implode( ', ', $c[ 'clusters' ] )
                 . "); left active, excluded from the per-host checks below" );
}

## Entries the Slurm code cannot submit to, left active from an older host:
## one named for another scheduler. 'submittype' no longer exists in the
## submission code, so an active 'pbs' or 'http' entry is now treated as
## though it were Slurm. They are dropped from $active as well, so the
## checks below do not go looking for env_script_lines, nodes or SSH on a
## cluster that is being retired. (THE BUG this answers: this loop
## used to also retire anything with a 'clusters' list, back when that shape
## only ever meant a dead Airavata-era fan-out entry. gridctl#35 made
## 'clusters' a first-class, currently-supported pool shape, so that
## retirement now wrongly deactivated every pool the operator had
## configured on every upgrade. Pools are filtered out into $pools above,
## before this loop runs, and this loop no longer looks at 'clusters' at
## all -- only submittype decides retirement here now.)
$converted = [];
foreach ( $active as $name => $c ) {
    if ( isset( $pools[ $name ] ) ) {
        continue;
    }
    $retired = null;
    $is_http = isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) === 'http';
    if ( isset( $c[ 'submittype' ] ) && strtolower( (string) $c[ 'submittype' ] ) !== 'slurm' ) {
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
            ## Mirrored into this run's own $clusters (same
            ## reasoning as the inactive-entry loop below, which already
            ## does this): --activate, later in this same run, reports
            ## "already active" from $clusters, not from $managed, so an
            ## active entry converted and named in --activate together
            ## otherwise still reads as active here even though this run is
            ## about to write active = false for it below.
            $clusters[ $name ][ $key ] = $value;
        }
        ## submittype is what marks it as an Airavata entry. The submission code no
        ## longer reads it, but leaving 'http' there invites the next reader to think
        ## the entry still goes through a gateway.
        if ( ( $c[ 'submittype' ] ?? '' ) !== 'slurm' ) {
            $changed[] = 'submittype';
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'submittype\' ] = \'slurm\';';
            $clusters[ $name ][ 'submittype' ] = 'slurm';
        }
        if ( !empty( $c[ 'active' ] ) ) {
            $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'active\' ] = false;';
            $clusters[ $name ][ 'active' ] = false;
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
        report_input( "set \$single_tenant_deployment (pass --single-tenant yes|no)", true );
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
## -- excluding pools, which have no host of their own to set this on.
foreach ( array_diff_key( $active, $pools ) as $name => $c ) {
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
        report_input( "$name lacks env_script_lines (pass --env $name=... or --env $name=)", true );
    } else {
        report( 'todo', "set env_script_lines for $name" );
        $managed[] = '$cluster_details[ ' . var_export( $name, true ) . ' ][ \'env_script_lines\' ] = ' . php_string( $value ) . ';';
    }
}

## One-node appliances: resource sizing needs single_node to keep a job on one node,
## and no more tasks per node or per job than the node has CPUs. Excluding
## pools here too -- sinfo is queried against a real host below, and a pool
## has none.
foreach ( array_diff_key( $active, $pools ) as $name => $c ) {
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
        ##
        ## Step 4's own --apply only closes this gap for the local cluster
        ## (authorize_local_key() is gated on $name === $host_cluster): for
        ## a remote one it records the host's key but cannot authorize this
        ## account's key on the far end, which needs the account's public
        ## key created if missing and added to that account's
        ## authorized_keys on the remote host by hand.
        $advice = $name === $host_cluster
            ? "step 4 below offers to set up the key under --apply, then re-run"
            : "step 4 below only records $login's host key; create us3's"
              . " key if missing and have it authorized for $login by hand, then re-run";
        report( 'input', "--activate: '$name' does not yet ssh as us3 to $login (exit $rc): "
                        . reason( $ssh_err !== '' ? $ssh_err : $ssh_out )
                        . "; $advice" );
        continue;
    }
    ## The web account's ssh is checked here too, before activation, not
    ## only us3's: a cluster could otherwise go live while the web tier
    ## (which actually stages files and runs sbatch) still could not
    ## reach it at all.
    if ( $web_user !== 'us3' ) {
        $web_rc = run_as( $web_user, $ssh, $web_ssh_out, $web_ssh_err );
        if ( $web_rc !== 0 ) {
            $web_advice = $name === $host_cluster
                ? "step 4 below offers to set up the key under --apply, then re-run"
                : "step 4 below only records $login's host key; create $web_user's"
                  . " key if missing and have it authorized for $login by hand, then re-run";
            report( 'input', "--activate: '$name' does not yet ssh as $web_user to $login (exit $web_rc): "
                            . reason( $web_ssh_err !== '' ? $web_ssh_err : $web_ssh_out )
                            . "; $web_advice" );
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
## its own on the cluster that is about to go live for it too. Pools are
## excluded (same reasoning as the env_script_lines/single_node checks
## above): a pool has no login/host of its own for ssh to reach. A
## 'localhost' => true entry is NOT excluded wholesale -- remote_exec's
## is_local() only skips ssh for the account that actually matches the
## entry's login user (on a fresh
## build the appliance's own account reaches itself via cp/exec and never
## needs a key, but php-fpm commonly runs as a separate web account that
## does not match the login user, and that account still falls back to
## ssh for the same entry). Which accounts get skipped is decided per
## entry, per account, below -- not here.
$ssh_targets = array_diff_key( $active, $pools );
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
    ## 'name'/'login' may be a bare host with no account part (the default
    ## is "us3@$host" above, but a config can override 'login' to just the
    ## host) -- in that case the login user is implicitly 'us3'.
    $login_user = strpos( $login, '@' ) !== false
                ? substr( $login, 0, strpos( $login, '@' ) )
                : 'us3';
    foreach ( $accounts as $account => $entry ) {
        ## is_local() in remote_exec.php only takes the local cp/exec path
        ## when the calling account matches the entry's login user; every
        ## other account still falls back to ssh for this same entry, so
        ## only the matching account's key setup is skipped here.
        if ( !empty( $c[ 'localhost' ] ) && $account === $login_user ) {
            continue;
        }
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

step( "5. Circuit-breaker, ssh-control and elog directories, the runtime-advisory pilot table,"
    . " and the metascheduler pick_cluster() tables" );

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
## logs to this same hardcoded default regardless. Removed rather than
## wired up to load earlier: nothing else needs config.php loaded before
## elog() on those pages, and moving that load order is a much larger
## change than this setting is worth.
$elog_dir = "$us3_home/lims/etc";
## elog.txt.1 is deliberately not provisioned here: dbinst#75 rotates by
## rename(), not by writing .1 in place, and a rename() needs write access
## to $elog_dir itself, not just to the file being provisioned -- a
## provisioned .1 does not give the web account that on a split-account
## host (0755 us3:us3 $elog_dir), so this never actually let the web
## account rotate -- there, .1 stays us3:us3 whenever us3 itself rotates,
## and apache's own rotation attempt simply fails outright (no write
## access to $elog_dir at all), so it never leaves a wrongly-owned file
## behind either. Worse on a roles host instead (a single account, not a
## split one, where PHP-FPM runs as us3 with secondary group apache):
## every rotation there leaves .1 owned us3:apache, so a provisioned 0660
## us3:<web group> .1 would never stay that way and --check would never
## converge.
## See dbinst#75's elog.php for the accepted limitation this leaves: the
## web account cannot rotate elog.txt on a split-account host today.
$elog_targets = [
    "$elog_dir/elog.txt"        => [ 0660, '' ],
    "$elog_dir/elog_hmac_key"   => [ 0640, null ],   ## null content: generate 32 random bytes if missing
];
foreach ( $elog_targets as $path => $spec ) {
    list( $want_mode, $empty_content ) = $spec;
    $want_mode_str = sprintf( '0%o', $want_mode );
    ## A key is never meant to be 0 bytes: elog_hmac_key()
    ## reads it with file_get_contents(), and treats empty exactly the same
    ## as missing -- an empty key already right owner/mode/group would
    ## otherwise report 'ok' and get left empty forever, with every account
    ## warning on every request and digests changing per call. elog.txt
    ## itself is legitimately empty when freshly provisioned, so this only
    ## applies when $empty_content is null (the key).
    $size_ok = $empty_content !== null || !is_file( $path ) || filesize( $path ) > 0;
    if ( $size_ok && is_file( $path ) && !is_link( $path )
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
    if ( !is_file( $path ) || ( $empty_content === null && filesize( $path ) === 0 ) ) {
        $content = $empty_content !== null ? $empty_content : random_bytes( 32 );
        ## umask(0077) for the window between creating the file and the
        ## chmod()/chown() below: file_put_contents()
        ## otherwise creates it under this process's own umask (commonly
        ## 0644 root:root for uslims_upgrade.php, run as root), leaving the
        ## HMAC key itself world-readable until those calls run. An
        ## unrelated account polling the directory in the meantime read it
        ## every time. Matches dbinst#75's own elog_hmac_key().
        $old_umask = umask( 0077 );
        $written = file_put_contents( $path, $content );
        umask( $old_umask );
        if ( $written === false ) {
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
## FAIL) is pinned down by a real unit test (in the lims aggregator's test
## suite, not this repo) instead of only ever being exercised against a
## live gfac account's actual privileges, the same gap
## that let single_node_sizing() above regress repeatedly. This
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
function runtime_prediction_create_outcome( $create_sql, $create_ok, $create_errno, $create_error,
                                             $runtime_table_sql = 'common/class/prediction/runtime_pilot_table.sql' ) {
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
        ## Absolute path: a relative path here fails for an
        ## operator running the suggested command from anywhere but the
        ## dbutils checkout itself.
        return [ 'note', "gfac account lacks privilege to create the optional runtime advisory"
                        . " pilot table (common#31): " . reason( (string) $create_error )
                        . "; an account with CREATE on gfac can run it by hand:"
                        . " mysql gfac < $runtime_table_sql" ];
    }
    return [ 'FAIL', "could not create gfac.runtime_prediction: " . reason( (string) $create_error ) ];
}

## Same classification as runtime_prediction_create_outcome(), generalized to
## either of the metascheduler pick_cluster() tables (metascheduler_decision,
## cluster_status_nudge), which share the exact same "purely additive, a
## missing-privilege error is a note, not a FAIL" reasoning: neither is read
## or written at all on a host that never configures a metascheduler
## pseudo-cluster entry.
## $host_has_pool distinguishes "this
## table is purely additive, nothing on this host needs it yet" (the
## runtime-advisory pilot table's own reasoning, genuinely true for a host
## with no metascheduler pseudo-cluster configured) from "this host
## actively uses pick_cluster() and is missing the table that keeps two
## reservations from picking the same cluster" -- a real, silent
## correctness gap, not an optional one, that the old unconditional 'note'
## treated identically to the harmless case on every roles-built host.
function metascheduler_table_create_outcome( $table, $sql_file, $create_sql, $create_ok,
                                              $create_errno, $create_error, $host_has_pool = false ) {
    if ( trim( (string) $create_sql ) === '' ) {
        return [ 'FAIL', "gfac.$table's SQL file has no SQL left after stripping comments" ];
    }
    if ( $create_ok ) {
        return [ 'done', "gfac.$table created" ];
    }
    if ( in_array( (int) $create_errno, [ 1142, 1044 ], true ) ) {
        ## 1142 (command denied) / 1044 (access denied to database): see
        ## runtime_prediction_create_outcome()'s own comment -- the gfac
        ## account lacking CREATE is an expected, optional gap when nothing
        ## on this host uses pick_cluster() yet. Once a pool is configured,
        ## the same gap means every pick runs with no audit trail and no
        ## protection against two reservations taking the same cluster --
        ## this needs doing, not a note an operator can reasonably ignore.
        if ( $host_has_pool ) {
            return [ 'FAIL', "gfac.$table could not be created (gfac account lacks privilege: "
                            . reason( (string) $create_error ) . "), and this host has a"
                            . " metascheduler pool configured -- pick_cluster() will run with no"
                            . " audit trail and no protection against two reservations choosing the"
                            . " same cluster. Grant CREATE on gfac to the account this ran as, or run"
                            . " by hand: mysql gfac < $sql_file" ];
        }
        return [ 'note', "gfac account lacks privilege to create gfac.$table (metascheduler"
                        . " pick_cluster()): " . reason( (string) $create_error )
                        . "; an account with CREATE on gfac can run it by hand:"
                        . " mysql gfac < $sql_file" ];
    }
    return [ 'FAIL', "could not create gfac.$table: " . reason( (string) $create_error ) ];
}

## Same classification as runtime_prediction_create_outcome(), for the
## additive ALTER an existing (pre-common#31) table needs instead of a
## CREATE. Without this, an existing pilot table silently lost every
## insert (0 rows, including requests that never set prediction_cluster_name)
## the moment common#31 started writing those two columns, and step 5 reported
## "already exists" regardless since it only ever checked the table, never
## its columns.
function runtime_prediction_alter_outcome( $alter_sql, $alter_ok, $alter_errno, $alter_error,
                                            $runtime_table_alter_sql ) {
    if ( trim( (string) $alter_sql ) === '' ) {
        return [ 'FAIL', "the runtime advisory pilot table's column-migration SQL file has no SQL"
                        . " left after stripping comments" ];
    }
    if ( $alter_ok ) {
        return [ 'done', "gfac.runtime_prediction.prediction_cluster[_source] columns added" ];
    }
    if ( in_array( (int) $alter_errno, [ 1142, 1044 ], true ) ) {
        return [ 'note', "gfac account lacks privilege to add the optional runtime advisory"
                        . " pilot table's prediction_cluster columns (common#31): "
                        . reason( (string) $alter_error )
                        . "; an account with ALTER on gfac can run it by hand:"
                        . " mysql gfac < $runtime_table_alter_sql" ];
    }
    return [ 'FAIL', "could not add gfac.runtime_prediction's prediction_cluster columns: "
                    . reason( (string) $alter_error ) ];
}

## Whether $grants (one string per SHOW GRANTS row for the connected
## account) includes CREATE on gfac, directly or via ALL PRIVILEGES, at
## either schema or global scope. Pure, no DB access, so the roles-host
## false positive below (--check always claiming this table is a pending
## change, forever, on an account that --apply could never actually create
## it for either) is pinned down by a real unit test (in the lims
## aggregator's test suite, not this repo) instead of only ever being
## exercised against a live account's actual grants.
function gfac_runtime_table_create_privileged( array $grants ) {
    foreach ( $grants as $line ) {
        if ( ! preg_match( '/^GRANT\s+(.+?)\s+ON\s+(\S+)\s+TO\s/i', $line, $m ) ) {
            continue;
        }
        $privs = $m[ 1 ];
        $scope = $m[ 2 ];
        $on_gfac_or_global = (bool) preg_match( '/^(\*\.\*|`?gfac`?\.\*)$/i', $scope );
        ## CREATE as its own comma-separated item, not a prefix: SHOW GRANTS
        ## also lists "CREATE TEMPORARY TABLES", "CREATE VIEW", "CREATE
        ## ROUTINE", etc. as separate, unrelated privileges, each starting
        ## with the same word. \bCREATE\b matched those too, so an account
        ## granted only one of them (never CREATE TABLE itself) looked
        ## privileged to --check forever -- it would report a pending
        ## change --apply could never actually make either.
        if ( $on_gfac_or_global
             && ( preg_match( '/\bALL PRIVILEGES\b/i', $privs )
                || preg_match( '/(?:^|,)\s*CREATE\s*(?:,|$)/i', $privs ) ) ) {
            return true;
        }
    }
    return false;
}

## Same reasoning as gfac_runtime_table_create_privileged(), for ALTER: an
## existing gfac.runtime_prediction created before prediction_cluster/
## prediction_cluster_source existed needs its own privilege (ALTER, not
## CREATE) to pick up the columns via runtime_pilot_table_add_prediction_cluster.sql.
## A roles-built gfac account (SELECT/INSERT/UPDATE/DELETE only) has neither,
## so this stays its own check rather than reusing the CREATE one and getting
## the wrong answer for this statement.
function gfac_runtime_table_alter_privileged( array $grants ) {
    foreach ( $grants as $line ) {
        if ( ! preg_match( '/^GRANT\s+(.+?)\s+ON\s+(\S+)\s+TO\s/i', $line, $m ) ) {
            continue;
        }
        $privs = $m[ 1 ];
        $scope = $m[ 2 ];
        $on_gfac_or_global = (bool) preg_match( '/^(\*\.\*|`?gfac`?\.\*)$/i', $scope );
        ## ALTER as its own comma-separated item, not a prefix match, for the
        ## same reason as CREATE above (no "ALTER ..." multi-word privilege
        ## exists in MariaDB's grant vocabulary today, but matching exactly
        ## costs nothing and stays correct if one ever does).
        if ( $on_gfac_or_global
             && ( preg_match( '/\bALL PRIVILEGES\b/i', $privs )
                || preg_match( '/(?:^|,)\s*ALTER\s*(?:,|$)/i', $privs ) ) ) {
            return true;
        }
    }
    return false;
}

## SHOW GRANTS rows for the connected account, as plain strings. Separated
## from gfac_runtime_table_create_privileged() so that function stays pure
## and testable without a database.
function mysqli_grant_rows( $link ) {
    $grants = [];
    $result = mysqli_query( $link, 'SHOW GRANTS' );
    if ( $result ) {
        while ( $row = mysqli_fetch_row( $result ) ) {
            $grants[] = $row[ 0 ];
        }
    }
    return $grants;
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
    ## The additive migration for a table created before prediction_cluster/
    ## prediction_cluster_source existed. Checked beside $runtime_table_sql
    ## for the same optional-checkout reason; absent, the column check below
    ## is simply skipped, same as "already exists" used to report
    ## unconditionally before this existed.
    $runtime_table_alter_sql = "$wwwpath/common/class/prediction/runtime_pilot_table_add_prediction_cluster.sql";

    $exists = mysqli_query( $gdb, "SHOW TABLES IN gfac LIKE 'runtime_prediction'" );
    if ( $exists && mysqli_num_rows( $exists ) > 0 ) {
        ## The table existing says nothing about whether it has these two
        ## columns: a table created from the pre-common#31 runtime_pilot_table.sql
        ## never gets them just by existing, and every insert then failed (0
        ## rows, including requests that never set prediction_cluster_name)
        ## the moment common#31 started writing them -- silently here, since
        ## this used to report "already exists" and stop looking.
        ## 'prediction_cluster%', not the exact name: the bare LIKE only ever
        ## matched prediction_cluster itself, so a table with that column but
        ## not prediction_cluster_source (a half-migrated state -- the same
        ## kind of state this whole ALTER exists to repair) still reported
        ## 'ok' and skipped it. >= 2, not > 0, for the same reason: one
        ## column matching is exactly the half-migrated case, not "done".
        $has_columns = is_file( $runtime_table_alter_sql )
            ? mysqli_query( $gdb, "SHOW COLUMNS FROM gfac.runtime_prediction LIKE 'prediction_cluster%'" )
            : null;

        if ( $has_columns === null ) {
            report( 'ok', "gfac.runtime_prediction already exists"
                  . " ($runtime_table_alter_sql not present; skipping the column check)" );
        } elseif ( $has_columns && mysqli_num_rows( $has_columns ) >= 2 ) {
            report( 'ok', "gfac.runtime_prediction already exists, with prediction_cluster columns" );
        } elseif ( ! $apply && ! gfac_runtime_table_alter_privileged( mysqli_grant_rows( $gdb ) ) ) {
            ## Same --check/--apply privilege-probe reasoning as the CREATE
            ## case below, for ALTER instead: a roles-built gfac account has
            ## neither, so without this, --check would report this as a
            ## pending change forever that --apply could never actually make.
            report( 'note', "gfac account lacks privilege to add the optional runtime advisory"
                  . " pilot table's prediction_cluster columns (common#31); an account with ALTER"
                  . " on gfac can run it by hand: mysql gfac < $runtime_table_alter_sql" );
        } else {
            report( 'todo', "add gfac.runtime_prediction.prediction_cluster[_source] columns"
                  . " (runtime advisory pilot, common#31): $runtime_table_alter_sql" );
            if ( $apply ) {
                $lines = explode( "\n", (string) file_get_contents( $runtime_table_alter_sql ) );
                $alter = trim( implode( "\n", array_filter( $lines,
                    function ( $line ) { return !preg_match( '/^\s*--/', $line ); } ) ) );

                $alter_ok = $alter !== '' ? mysqli_query( $gdb, $alter ) : false;
                $errno    = 0;
                $error    = '';
                if ( $alter !== '' && !$alter_ok ) {
                    $errno = mysqli_errno( $gdb );
                    $error = mysqli_error( $gdb );
                }
                if ( $alter_ok ) {
                    $changes++;
                }

                list( $status, $message ) = runtime_prediction_alter_outcome(
                    $alter, $alter_ok, $errno, $error, $runtime_table_alter_sql );
                report( $status, $message );
            }
        }
    } elseif ( ! gfac_runtime_table_create_privileged( mysqli_grant_rows( $gdb ) ) ) {
        ## --check never runs CREATE, so without this probe a roles-built
        ## gfac account (SELECT/INSERT/UPDATE/DELETE only) reports "1
        ## change(s) would be made" on every single --check, forever.
        ## Read-only (SHOW GRANTS), so --check still never touches the
        ## database. Round 15 nit, dbutils#52: this used to gate on
        ## "!$apply" too, so --apply skipped this short-circuit, printed a
        ## "[todo] create gfac.runtime_prediction" --check never showed for
        ## the same host, attempted the CREATE anyway, and downgraded to
        ## this exact same 'note' once the attempt failed for the same
        ## reason this probe already knew about -- a redundant extra line,
        ## not a different outcome. Unconditional now: the probe is
        ## reliable enough for --check alone to depend on, so it is
        ## reliable enough for --apply to skip the doomed attempt on too.
        report( 'note', "gfac account lacks privilege to create the optional runtime advisory"
              . " pilot table (common#31); an account with CREATE on gfac can run it by hand:"
              . " mysql gfac < $runtime_table_sql" );
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

            list( $status, $message ) = runtime_prediction_create_outcome(
                $create, $create_ok, $errno, $error, $runtime_table_sql );
            report( $status, $message );
        }
    }
}

## -- metascheduler pick_cluster() tables (gridctl/pick_cluster.php,
## -- pick_cluster_reserve.php). Both purely
## -- additive, same reasoning as the runtime advisory pilot table above: a
## -- host that never configures a metascheduler pseudo-cluster entry in
## -- global_config.php never writes to either, and CREATE TABLE IF NOT
## -- EXISTS is safe to run on every upgrade regardless of whether it does.
## -- Without this, the first metascheduler pseudo-cluster submission on an
## -- upgraded host would fail outright (metascheduler_decision) or silently
## -- lose its double-booking protection (cluster_status_nudge), neither of
## -- which gridctl's own code can create for itself at runtime.
foreach ( [
    'metascheduler_decision' => "$gridctl_dir/metascheduler_decision_table.sql",
    'cluster_status_nudge'   => "$gridctl_dir/cluster_status_nudge_table.sql",
] as $metascheduler_table => $metascheduler_table_sql ) {
    if ( !is_file( $metascheduler_table_sql ) ) {
        ## An old gridctl checkout that predates the metascheduler feature:
        ## not required for this upgrade, same reasoning as the runtime
        ## advisory pilot table's own missing-file case above.
        report( 'note', "$metascheduler_table_sql not present; skipping the optional metascheduler"
              . " gfac.$metascheduler_table table -- not required for this upgrade" );
        continue;
    }

    $exists = mysqli_query( $gdb, "SHOW TABLES IN gfac LIKE '$metascheduler_table'" ); // NOSONAR $metascheduler_table is a hardcoded array key above, not user input
    if ( $exists && mysqli_num_rows( $exists ) > 0 ) {
        report( 'ok', "gfac.$metascheduler_table already exists" );
        continue;
    }

    ## Reuses the runtime-advisory pilot table's own CREATE-on-gfac probe:
    ## its check (ALL PRIVILEGES or CREATE, at gfac or global scope) has
    ## nothing runtime-prediction-specific in it, and a roles-built gfac
    ## account (SELECT/INSERT/UPDATE/DELETE only) is exactly the same account
    ## either table would be missing CREATE on.
    if ( ! gfac_runtime_table_create_privileged( mysqli_grant_rows( $gdb ) ) ) {
        ## Same escalation as metascheduler_table_create_outcome()'s own
        ## CREATE-denied branch: this
        ## path never attempts the CREATE at all (it infers
        ## the privilege gap from SHOW GRANTS instead), so it has to
        ## duplicate that one escalation decision rather than call through
        ## a function shaped around an actual attempt's errno/error.
        ## Round 15 nit, dbutils#52: this used to gate on "!$apply" too
        ## (same bug, same fix, as the runtime_prediction block above) --
        ## --apply printed a "[todo] create gfac.$metascheduler_table"
        ## --check never showed, attempted a CREATE already known to fail,
        ## and reported the exact same FAIL/note below anyway.
        if ( !empty( $pools ) ) {
            report( 'FAIL', "gfac.$metascheduler_table cannot be created (gfac account lacks"
                  . " privilege), and this host has a metascheduler pool configured --"
                  . " pick_cluster() will run with no audit trail and no protection against two"
                  . " reservations choosing the same cluster. Grant CREATE on gfac to the account"
                  . " this will run as, or run by hand: mysql gfac < $metascheduler_table_sql" );
        } else {
            report( 'note', "gfac account lacks privilege to create gfac.$metascheduler_table"
                  . " (metascheduler pick_cluster()); an account with CREATE on gfac can run it by"
                  . " hand: mysql gfac < $metascheduler_table_sql" );
        }
        continue;
    }

    report( 'todo', "create gfac.$metascheduler_table (metascheduler pick_cluster())" );
    if ( $apply ) {
        ## Unlike the runtime-advisory pilot table's file, these two start with
        ## a `USE gfac;` statement (added so a human running `mysql gfac <
        ## file.sql` by hand doesn't have to remember to select the database
        ## first). mysqli_query() can only run one statement, so that line has
        ## to be stripped here same as the `--` comments -- $gdb is already
        ## connected to gfac (the mysqli_connect() call above), so dropping it
        ## loses nothing.
        $lines  = explode( "\n", (string) file_get_contents( $metascheduler_table_sql ) );
        $create = trim( implode( "\n", array_filter( $lines,
            function ( $line ) { return !preg_match( '/^\s*--/', $line ) && !preg_match( '/^\s*USE\s+\S+\s*;?\s*$/i', $line ); } ) ) );

        $create_ok = $create !== '' ? mysqli_query( $gdb, $create ) : false;
        $errno = 0;
        $error = '';
        if ( $create !== '' && !$create_ok ) {
            $errno = mysqli_errno( $gdb );
            $error = mysqli_error( $gdb );
        }
        if ( $create_ok ) {
            $changes++;
        }

        list( $status, $message ) = metascheduler_table_create_outcome(
            $metascheduler_table, $metascheduler_table_sql, $create, $create_ok, $errno, $error,
            !empty( $pools ) );
        report( $status, $message );
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

## Joins a backslash-continued directive ("Header set \\\nContent-Security-
## Policy ...") onto one line, so a single-line match can see both halves.
## \r?\n, not just \n: a file with CRLF line endings (which Apache accepts)
## left the \r stuck to the end of the first half otherwise, so the join
## never matched and a continued directive already installed was reported
## as a fresh one to install. Pure, so this is testable without a real file.
function csp_join_continued_directive( $contents ) {
    return preg_replace( '/\\\\\r?\n/', ' ', (string) $contents );
}

## Does this one Apache config line actually install a Content-Security-Policy
## header (as opposed to merely mentioning the phrase)? Pure, so the
## "add/append/merge/setifempty install one too, not just set" fix and the
## "unset/comment/mention do not" exclusions are each pinned down by a real
## test instead of only ever exercised against whatever happens to be on a
## test host's live Apache config.
function csp_header_line_installs_policy( $line ) {
    ## (always|onsuccess)?: Header's own optional condition keyword. ["']? before the
    ## header name: a quoted header name ('Header set "Content-Security-
    ## Policy" ...') wasn't recognised either. The caller joins a backslash-
    ## continued directive onto one line first, so that case needs no change
    ## here.
    return (bool) preg_match(
        '/^\s*Header\s+(always\s+|onsuccess\s+)?(set|add|append|merge|setifempty)\s+["\']?Content-Security-Policy\b/i',
        $line
    );
}

## Does this one Apache config FILE's contents (already joined across any
## backslash-continued lines, same as csp_header_line_installs_policy()'s
## caller does) already alias at least one of /common/css, /common/images
## or /common/js? Pure, so the detection is pinned down by a real test
## instead of only ever exercised against whatever a live test host's
## Apache config happens to contain. One match is enough: a host that
## templated its own vhost (us3lims-roles' config-lims-apache role) has
## all three, from the same template, in the same file.
function common_alias_conf_installs_alias( $contents ) {
    return (bool) preg_match( '#^\s*Alias\s+/common/(css|images|js)\b#mi', (string) $contents );
}

## ------------------------------------------------------------- 7. Content-Security-Policy

## The pages are written for util/csp's policy. It goes in Report-Only, which
## blocks nothing and logs each violation through /csp-report.php; enforcing it is
## a later, deliberate change once that log is quiet (util/csp/README.md).
step( "7. Apache: Content-Security-Policy (Report-Only) and the /common static-asset alias" );

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
    ## No 2>&1: capture() already separates the streams, and merging them
    ## here buried a real "Syntax error on line..." after DUMP_INCLUDES'
    ## own (successful) stdout listing, which reason() then truncated to
    ## its first 200 characters -- showing the include list, not the error.
    list( $dump_out, $dump_err, $dump_rc ) = capture( "$dump_cmd -t -D DUMP_INCLUDES" );
    ## A non-zero exit here now FAILs outright rather than falling back to a
    ## directory walk. The walk used to run for this
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
            $joined = csp_join_continued_directive( @file_get_contents( $file ) );
            foreach ( explode( "\n", $joined ) as $line ) {
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
                    ## THE BUG this answers:
                    ## 'systemctl reload' only ever works on an already-
                    ## running unit -- on a stopped httpd (a fresh host
                    ## before its first start, or one deliberately down for
                    ## other maintenance) it fails every time, regardless of
                    ## whether the config itself is fine, and this used to
                    ## report that as a FAIL even though apachectl configtest
                    ## just above already confirmed the installed policy is
                    ## valid and needs nothing further once httpd does start.
                    list( , , $active_rc ) = capture( 'systemctl is-active ' . $apache[ 'service' ] );
                    if ( $active_rc !== 0 ) {
                        report( 'ok', "Report-Only policy installed; {$apache['service']} is not"
                                . " running, so it will take effect the next time it starts" );
                    } else {
                        list( $r_out, $r_err, $r_rc ) = capture( 'systemctl reload ' . $apache[ 'service' ] );
                        report( $r_rc === 0 ? 'done' : 'FAIL', "Report-Only policy installed"
                                . ( $r_rc === 0 ? "; {$apache['service']} reloaded" : "; reload {$apache['service']} by hand: " . reason( $r_err ) ) );
                    }
                }
            }
        }
        list( $mods ) = capture( 'apachectl -M' );
        if ( strpos( $mods, 'headers_module' ) === false ) {
            report( 'note', "mod_headers is not loaded, so Apache sends no CSP header; enable it" );
        }
    }
}

## THE BUG this answers. A hand upgrade
## (the procedure above) pulls dbinst's code but never touches Apache's
## own config -- only us3lims-roles' config-lims-apache role templates the
## /common/{css,images,js} Alias newinst/webinfo's shared static assets
## need. A host whose vhost predates that role, or that this script
## upgrades without the roles ever having run on it, has no such Alias at
## all: every page's CSS/images/JS 404. Installed the same idempotent,
## conf.d-snippet way step 7's CSP policy already is just above -- a
## separate file Apache includes automatically, so this never has to parse
## or rewrite whatever the host's actual vhost file looks like. $apache
## (which conf.d/conf-available directory exists) was already resolved
## above; this runs whether or not the CSP install itself needed anything
## done, including when $apache was null (no Apache config directory at
## all) or the CSP policy file was missing, each handled the same way the
## CSP block does.
if ( $apache === null ) {
    report( 'FAIL', "no Apache configuration directory (/etc/httpd/conf.d or /etc/apache2/conf-available)" );
} else {
    $common_alias_conf = dirname( $apache[ 'conf' ] ) . '/lims-common-alias.conf';
    $alias_dump_cmd     = $apache[ 'service' ] === 'apache2' ? 'apache2ctl' : 'httpd';
    list( $alias_dump_out, $alias_dump_err, $alias_dump_rc ) = capture( "$alias_dump_cmd -t -D DUMP_INCLUDES" );

    if ( $alias_dump_rc !== 0 ) {
        report( 'FAIL', "$alias_dump_cmd -t -D DUMP_INCLUDES failed; fix Apache's configuration first: "
                       . reason( $alias_dump_err !== '' ? $alias_dump_err : $alias_dump_out ) );
    } else {
        $alias_found = false;
        foreach ( explode( "\n", $alias_dump_out ) as $line ) {
            if ( preg_match( '#^\s*\(\S+\)\s+(/\S+)#', $line, $m )
                 && common_alias_conf_installs_alias( csp_join_continued_directive( @file_get_contents( $m[ 1 ] ) ) ) ) {
                $alias_found = $m[ 1 ];
                break;
            }
        }
        if ( $alias_found !== false ) {
            report( 'ok', "a /common static-asset alias is already configured ($alias_found); left as is" );
        } else {
            $common_dir_path = rtrim( common_dir_default( $wwwpath ), '/' );
            report( 'todo', "install the /common/{css,images,js} alias for $common_dir_path as $common_alias_conf" );
            if ( $apply && confirm( "Install the /common/{css,images,js} alias and reload {$apache['service']}?" ) ) {
                $conf = "## Installed by uslims_upgrade.php: serves newinst/webinfo's shared\n"
                      . "## css/images/js the same way us3lims-roles' config-lims-apache role does.\n";
                foreach ( [ 'css', 'images', 'js' ] as $subdir ) {
                    $conf .= "Alias /common/$subdir \"$common_dir_path/$subdir\"\n"
                           . "<Directory \"$common_dir_path/$subdir\">\n"
                           . "  Options +FollowSymLinks\n  AllowOverride None\n  Require all granted\n</Directory>\n";
                }
                write_file( $common_alias_conf, $conf, false );
                if ( $apache[ 'enable' ] !== '' ) {
                    capture( str_replace( 'lims-csp', 'lims-common-alias', $apache[ 'enable' ] ) );
                }
                list( $t_out, $t_err, $t_rc ) = capture( 'apachectl configtest' );
                if ( $t_rc !== 0 ) {
                    if ( $apache[ 'disable' ] !== '' ) {
                        capture( str_replace( 'lims-csp', 'lims-common-alias', $apache[ 'disable' ] ) );
                    }
                    @unlink( $common_alias_conf );
                    report( 'FAIL', "Apache rejected the alias, so it was removed: " . reason( $t_err !== '' ? $t_err : $t_out ) );
                } else {
                    list( , , $active_rc ) = capture( 'systemctl is-active ' . $apache[ 'service' ] );
                    if ( $active_rc !== 0 ) {
                        report( 'ok', "/common alias installed; {$apache['service']} is not"
                                . " running, so it will take effect the next time it starts" );
                    } else {
                        list( $r_out, $r_err, $r_rc ) = capture( 'systemctl reload ' . $apache[ 'service' ] );
                        report( $r_rc === 0 ? 'done' : 'FAIL', "/common alias installed"
                                . ( $r_rc === 0 ? "; {$apache['service']} reloaded" : "; reload {$apache['service']} by hand: " . reason( $r_err ) ) );
                    }
                }
            }
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
    ## A
    ## metascheduler pool entry (the same "clusters"-list shape step 3
    ## above already carves out of $active via is_metascheduler_pool_entry()
    ## -- identical to gridctl's own pick_cluster_is_metascheduler_entry())
    ## lives in $cluster_details the same as every real cluster, but is
    ## never itself a schedulable target: it has no ppn and no
    ## cluster_config.php probe of its own, because pick_cluster() resolves
    ## it to a separate, ordinary active entry that already has both. Both
    ## checks below used to treat the pool entry as if it were that real
    ## cluster and fail it on both counts, so --apply exited rc 1 on any
    ## host with a pool while --check (which does not run this block)
    ## passed -- failing the roles playbook on every pool-enabled
    ## host, every run.
    $final = config_vars( $global_config, $f_why );
    if ( $final === null ) {
        report( 'FAIL', "cannot re-read global_config.php to check the sizing gate: "
                        . reason( $f_why ) );
    } else {
        $final_clusters = is_array( $final[ 'cluster_details' ] ?? null )
                        ? $final[ 'cluster_details' ] : [];
        $sizing  = sizing_gate_refusals( $final_clusters );
        $refused = $sizing[ 'refused' ];
        $checked = $sizing[ 'checked' ];
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
            $probes   = is_array( $cc_vars[ 'cluster_configuration' ] ?? null )
                      ? $cc_vars[ 'cluster_configuration' ] : [];
            $unprobed = unprobed_active_clusters( $final_clusters, $probes );
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

step( "9. Per-instance catch-up (\$common_dir, people_audit UTC migration)" );

## Unlike dbupgrade/stage2_import_dbinsts.php (which imports a 4.2.0
## dbinstance wholesale onto a new host), this upgrade runs against the
## dbinstance(s) already serving pages on THIS host -- so this reuses the
## same migrate_people_audit_created_at_to_utc() (utility.php), idempotent
## via a marker table inside each dbinstance, safe to run here whether or
## not stage1/stage2 already ran against it.
##
## No new credentials: each instance's own already-running config.php (or,
## for one migrated under the per-instance config layout, its overlay --
## same detection uslims_domain_info.php's instance_overlay() uses) already
## has everything needed. A dbinstance that could not connect with its own
## config would not be serving pages, so this connects with exactly what
## the site itself depends on, never root or a separate db_config.php.
## discover_lims_instance_configs() (utility.php) is shared with the
## standalone migrate_people_audit_utc.php, so both agree on what "every
## instance on this host" means. $instance_paths (by reference) gets the
## real file each entry's config was actually read from -- needed below to
## write $common_dir back to the right file, not guessed from $wwwpath.
## $newus3_configs carries
## webinfo's and newinst's own host-level configs -- the entries this
## function finds on the same glob pass but discards, since they are not
## LIMS tenant databases; run once below, outside the per-instance loop,
## since there is exactly one webinfo and one newinst config per host, not
## one per instance.
$instance_configs = discover_lims_instance_configs( $wwwpath, $us3_home, $instance_paths, $newus3_configs );

run_host_migrations( null, $required_version, $apply,
                      [ 'wwwpath' => $wwwpath, 'newus3_configs' => $newus3_configs ],
                      [ 'webinfo_newinst_common_dir_backfill' ] );

## dbinst_base_common_dir_backfill: the
## shared base+overlay base file, fixed once per host -- see the sibling
## instance migration above, which defers to this one for a base+overlay
## instance instead of trying (and failing) to patch its own overlay.
run_host_migrations( null, $required_version, $apply,
                      [ 'wwwpath' => $wwwpath, 'us3_home' => $us3_home ],
                      [ 'dbinst_base_common_dir_backfill' ] );

if ( !$instance_configs ) {
    report( 'note', "no $wwwpath/uslims3 config.php found; nothing to migrate" );
} else {
    foreach ( $instance_configs as $label => $cfg ) {
        if ( isset( $cfg[ 'error' ] ) ) {
            report( 'note', "$label: " . reason( $cfg[ 'error' ] ) . "; convert people_audit by hand" );
            continue;
        }

        $context = [
            'label'         => $label,
            'cfg'           => $cfg,
            'instance_path' => $instance_paths[ $label ] ?? null,
            'wwwpath'       => $wwwpath,
        ];

        ## common_dir_backfill (migrations/4.3.0_common_dir_backfill.php)
        ## needs no live connection -- it only reads $cfg, already parsed
        ## by discover_lims_instance_configs() above -- and must run even
        ## when this instance's own connection, checked next, fails: a
        ## misconfigured dbname should not also block the config fix.
        run_instance_migrations( null, null, $required_version, $apply, $context,
                                  [ 'common_dir_backfill' ] );

        ## people_audit_utc (migrations/4.3.0_people_audit_utc.php) needs
        ## its own connection to this one instance's database, same as the
        ## inline code it replaces always opened here. Checked here, not
        ## left to migrate_people_audit_created_at_to_utc()'s own
        ## sql_identifier_or_die(): that one exits the whole script, which
        ## would skip every later instance in this loop over one malformed
        ## name -- after steps 1-8 have already applied. report()+continue
        ## instead, same as every other per-instance failure in this loop.
        if ( !is_sql_identifier( $cfg[ 'dbname' ] ) ) {
            report( 'FAIL', "$label: '{$cfg['dbname']}' is not a usable database name; convert people_audit by hand" );
            continue;
        }

        $conn = @mysqli_connect( $cfg[ 'dbhost' ], $cfg[ 'dbusername' ], $cfg[ 'dbpasswd' ], $cfg[ 'dbname' ] );
        if ( !$conn ) {
            report( 'FAIL', "$label: could not connect to {$cfg['dbname']}@{$cfg['dbhost']} as"
                  . " {$cfg['dbusername']}: " . reason( mysqli_connect_error() ) );
            continue;
        }

        run_instance_migrations( $conn, $cfg[ 'dbname' ], $required_version, $apply, $context,
                                  [ 'people_audit_utc' ] );

        mysqli_close( $conn );
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
## See upgrade_exit_code(). The summary line is for callers that read --check's outcome.
echo "SUMMARY failures=$failures pending=$pending input=$needs_input changes=$changes\n";
exit( upgrade_exit_code( $apply, $failures, $needs_input, $declined ) );
