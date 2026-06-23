<?php

# uslims_autoflow_test.php
#
# Standalone, read-only-by-default debug/monitoring/validation tool for the
# autoflow GMP submission pipeline (makeafrequest*.php -> submitctl.php ->
# submitone.php -> us3lims_common's submit_local.php -> sbatch/qsub).
#
# This tool never modifies us3lims_gridctl or us3lims_common source. It only:
#  - reads the database
#  - tails log files
#  - inspects running processes & file permissions
#  - calls existing CLI entry points (makeafrequest*.php, services.php) the
#    same way a human operator would
#
# See ~/.claude/plans/need-to-debug-https-github-com-ehb54-us3-declarative-nebula.md
# for the design this implements.

$self = __FILE__;

$notes = <<<__EOD
usage: $self {options} {db_config_file}

Debug/monitor/validate the autoflow submitctl/submitone job submission
pipeline without modifying any us3lims_gridctl/us3lims_common code.

Modes (pick one; default is --check)

--check                       : read-only validation of the pipeline's health
                                 (services running? correct user? log/lock dirs
                                 writable? any stale READY/SUBMITTED rows?)
--run                         : create a new autoflowAnalysis request via
                                 makeafrequest*.php, then watch it through to
                                 FINISHED/FAILED/timeout
--watch                       : watch (and tail logs for) an existing request,
                                 requires --reqid

Common options

--help                        : print this information and exit
--db                  name    : (required) lims database name, e.g. uslims3_Demo
--gridctl-dir         path    : path to the deployed us3lims_gridctl tree
                                 containing autoflow_util/, submitctl.php,
                                 services.php (default: ~us3/lims/bin, where
                                 it's normally deployed)
--expected-user       name    : username daemons/files are expected to run/be
                                 owned as (default: us3)
--web-user            name    : username the web server runs as, which also
                                 writes some of these log files via the
                                 browser-triggered submission flow. Default:
                                 auto-detected from the running httpd/apache2/
                                 nginx/php-fpm worker process owner (falls back
                                 to "apache" if that can't be determined)
--timeout             secs    : max seconds to watch before giving up (default: 600)
--poll-interval       secs    : seconds between DB/log polls (default: 5)

--run options

--invid               id      : investigator personID to pass to makeafrequest*.php
--rawid               id      : rawDataID to pass to makeafrequest*.php
--scenario            name    : which autoflow_util/makeafrequest*.php variant to
                                 use: 2dsa (default, makeafrequest.php), pcsa
                                 (makeafrequestPCSA.php), pcsa-onechannel
                                 (makeafrequestPCSAonechannel.php), mc-cluster
                                 (makeafrequest2DSA_MC_cluster.php), cg
                                 (makeafrequest2DSA-CG.php)
--expect-status       status  : if given, harness exits 0 only if the request
                                 ends at this status instead of the default
                                 FINISHED (e.g. --expect-status FAILED for a
                                 deliberately-faulted run, or WAIT for a
                                 scenario that includes an interactive stage
                                 like FITMEN)
--cleanup                     : opt-in. After the watch ends (FINISHED, FAILED,
                                 WAIT, or timeout), delete the autoflow/
                                 analysisprofile/autoflowAnalysis rows this
                                 --run created. Without --cleanup, the
                                 equivalent DELETE statements are printed
                                 instead so cleanup is still a copy-paste away.

--watch options

--reqid               id      : autoflowAnalysis.requestID to watch

Fault injection (opt-in, environment-only, never edits gridctl/common code)

--fake-sbatch         mode    : succeed (default, real sbatch untouched),
                                 fail-always, or fail-once
--yes-i-know-this-restarts-submitctl
                               : required to actually enable --fake-sbatch;
                                 acknowledges this restarts the live
                                 submitctl.php daemon (via the existing
                                 services.php restart) to apply a PATH
                                 override, affecting any other in-flight jobs
                                 on a shared system. submitctl.php is restarted
                                 again with the original PATH once the watch
                                 ends.

__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv );

$mode             = "check";
$db               = false;
$gridctl_dir      = false;
$expected_user    = "us3";
$web_user         = "apache";
$timeout          = 600;
$poll_interval    = 5;
$invid            = false;
$rawid            = false;
$scenario         = "2dsa";
$expect_status    = "FINISHED";
$reqid            = false;
$fake_sbatch      = "succeed";
$confirm_restart  = false;
$cleanup          = false;
$web_user_explicit = false;

$scenario_scripts = [
    "2dsa"             => "makeafrequest.php",
    "pcsa"             => "makeafrequestPCSA.php",
    "pcsa-onechannel"  => "makeafrequestPCSAonechannel.php",
    "mc-cluster"       => "makeafrequest2DSA_MC_cluster.php",
    "cg"               => "makeafrequest2DSA-CG.php",
];

while ( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $arg = $u_argv[ 0 ];
    switch ( $arg ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--check": {
            array_shift( $u_argv );
            $mode = "check";
            break;
        }
        case "--run": {
            array_shift( $u_argv );
            $mode = "run";
            break;
        }
        case "--watch": {
            array_shift( $u_argv );
            $mode = "watch";
            break;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $db = array_shift( $u_argv );
            break;
        }
        case "--gridctl-dir": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $gridctl_dir = rtrim( array_shift( $u_argv ), "/" );
            break;
        }
        case "--expected-user": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $expected_user = array_shift( $u_argv );
            break;
        }
        case "--web-user": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $web_user = array_shift( $u_argv );
            $web_user_explicit = true;
            break;
        }
        case "--timeout": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $timeout = (int) array_shift( $u_argv );
            break;
        }
        case "--poll-interval": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $poll_interval = (int) array_shift( $u_argv );
            break;
        }
        case "--invid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $invid = array_shift( $u_argv );
            break;
        }
        case "--rawid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $rawid = array_shift( $u_argv );
            break;
        }
        case "--scenario": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $scenario = array_shift( $u_argv );
            if ( !array_key_exists( $scenario, $scenario_scripts ) ) {
                error_exit( "ERROR: unknown --scenario '$scenario'. Known scenarios: " . implode( ", ", array_keys( $scenario_scripts ) ) );
            }
            break;
        }
        case "--expect-status": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $expect_status = array_shift( $u_argv );
            break;
        }
        case "--reqid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $reqid = array_shift( $u_argv );
            break;
        }
        case "--fake-sbatch": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) { error_exit( "ERROR: option '$arg' requires an argument\n$notes" ); }
            $fake_sbatch = array_shift( $u_argv );
            if ( !in_array( $fake_sbatch, [ "succeed", "fail-always", "fail-once" ] ) ) {
                error_exit( "ERROR: unknown --fake-sbatch mode '$fake_sbatch'" );
            }
            break;
        }
        case "--yes-i-know-this-restarts-submitctl": {
            array_shift( $u_argv );
            $confirm_restart = true;
            break;
        }
        case "--cleanup": {
            array_shift( $u_argv );
            $cleanup = true;
            break;
        }
        default: {
            error_exit( "\nUnknown option '$arg'\n\n$notes" );
        }
    }
}

$config_file = "db_config.php";
$use_config_file = count( $u_argv ) ? array_shift( $u_argv ) : $config_file;

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( !file_exists( $use_config_file ) ) {
    error_exit(
        "$self:\n$use_config_file does not exist\n\nto fix:\n\ncp ${config_file}.template $use_config_file\nand edit with appropriate values\n"
    );
}

file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !$db ) {
    error_exit( "ERROR: --db must be specified\n---\n$notes" );
}

if ( $mode == "run" && ( !$invid || !$rawid ) ) {
    error_exit( "ERROR: --run requires --invid and --rawid\n---\n$notes" );
}

if ( $mode == "watch" && !$reqid ) {
    error_exit( "ERROR: --watch requires --reqid\n---\n$notes" );
}

if ( $fake_sbatch != "succeed" && !$confirm_restart ) {
    error_exit( "ERROR: --fake-sbatch $fake_sbatch requires --yes-i-know-this-restarts-submitctl" );
}

####################################################################
# pull in the SAME config the real daemons use (read-only include,
# never modified) so paths/credentials always match production exactly
####################################################################

$us3bin = exec( "ls -d ~us3/lims/bin" );
$us3home = exec( "ls -d ~us3" );
if ( !is_dir( $us3bin ) ) {
    error_exit( "ERROR: could not resolve ~us3/lims/bin (got '$us3bin') - is this tool running on a us3lims host?" );
}
include "$us3bin/listen-config.php";

if ( !$gridctl_dir ) {
    $gridctl_dir = $us3bin;
}

if ( $mode == "run" && !is_dir( "$gridctl_dir/autoflow_util" ) ) {
    error_exit( "ERROR: '$gridctl_dir/autoflow_util' not found - pass --gridctl-dir if us3lims_gridctl isn't deployed at ~us3/lims/bin" );
}

# Web server worker processes aren't always actually owned by a user named
# "apache" (e.g. some deployments run httpd workers as the lims user itself) -
# detect the real non-root owner of the running web server process and use
# that as the default unless --web-user was explicitly given.
if ( !$web_user_explicit ) {
    $detected = trim( run_cmd(
        "ps -eo user,comm 2>/dev/null | awk '\$2 ~ /^(httpd|apache2|nginx|php-fpm)\$/ && \$1 != \"root\" {print \$1; exit}'",
        false
    ) );
    if ( $detected !== "" ) {
        $web_user = $detected;
    }
}

$test_bin_dir   = __DIR__ . "/test_bin";
$test_state_dir = "$test_bin_dir/state";

$submit_log   = "$home/etc/submit.log";
$udp_log      = $logfile;                       # from listen-config.php
$dump_dir     = "$home/etc/submit";
$elog_file    = "$us3home/lims/etc/elog.txt";
$elog2_file   = "/home/us3/lims/etc/elog2.txt";  # hardcoded in submit_local.php
$gridctl_lock = "$lock_dir/submitctl.php.lock";

####################################################################
# shared helpers
####################################################################

function db_connect_lims() {
    global $dbhost, $user, $passwd;
    $h = mysqli_connect( $dbhost, $user, $passwd );
    if ( !$h ) {
        error_exit( "ERROR: could not connect to mysql $dbhost as $user" );
    }
    return $h;
}

function pid_from_lock( $lockfile ) {
    if ( !file_exists( $lockfile ) ) {
        return false;
    }
    if ( is_link( $lockfile ) ) {
        return (int) substr( readlink( $lockfile ), 6 );
    }
    return (int) trim( file_get_contents( $lockfile ) );
}

function process_owner( $pid ) {
    if ( !$pid || !file_exists( "/proc/$pid" ) ) {
        return false;
    }
    $owner = trim( run_cmd( "ps -o user= -p $pid", false ) );
    return $owner ?: false;
}

function user_groups( $user ) {
    static $cache = [];
    if ( array_key_exists( $user, $cache ) ) {
        return $cache[ $user ];
    }
    $groups = explode( " ", trim( run_cmd( "id -nG " . escapeshellarg( $user ) . " 2>/dev/null || true", false ) ) );
    $cache[ $user ] = array_filter( $groups, function( $g ) { return $g !== ""; } );
    return $cache[ $user ];
}

# Determine whether $expected_user can write $path based on the file's own
# owner/group/permission bits - NOT PHP's is_writable(), which reflects the
# privileges of whatever user is running *this* check (often root) and would
# silently report "writable" even when the real daemon user could not write.
function path_owner_writable( $path, $expected_user ) {
    if ( !file_exists( $path ) ) {
        return [ "exists" => false ];
    }
    $stat       = stat( $path );
    $ownerinfo  = posix_getpwuid( $stat[ 'uid' ] );
    $groupinfo  = posix_getgrgid( $stat[ 'gid' ] );
    $owner      = $ownerinfo ? $ownerinfo[ 'name' ] : (string) $stat[ 'uid' ];
    $group      = $groupinfo ? $groupinfo[ 'name' ] : (string) $stat[ 'gid' ];
    $perm_octal = fileperms( $path ) & 0777;

    $owner_w_bit = (bool) ( $perm_octal & 0200 );
    $group_w_bit = (bool) ( $perm_octal & 0020 );
    $other_w_bit = (bool) ( $perm_octal & 0002 );

    $owner_matches = $owner === $expected_user;
    $in_group      = in_array( $group, user_groups( $expected_user ), true );

    $effective_writable =
        ( $owner_matches && $owner_w_bit )
        || ( $in_group && $group_w_bit )
        || $other_w_bit;

    $reason = $other_w_bit
        ? "other-writable"
        : ( ( $in_group && $group_w_bit )
            ? "group-writable, $expected_user in group $group"
            : ( ( $owner_matches && $owner_w_bit )
                ? "owner-writable"
                : "NOT writable by $expected_user (owner=$owner group=$group, $expected_user " . ( $in_group ? "is" : "is NOT" ) . " in group $group)" ) );

    return [
        "exists"              => true,
        "owner"               => $owner,
        "group"               => $group,
        "owner_matches"       => $owner_matches,
        "effective_writable"  => $effective_writable,
        "reason"              => $reason,
        "perms"               => sprintf( '%04o', $perm_octal ),
    ];
}

function tail_state_init( $files ) {
    $state = [];
    foreach ( $files as $label => $path ) {
        $state[ $label ] = [
            "path" => $path,
            "pos"  => file_exists( $path ) ? filesize( $path ) : 0,
        ];
    }
    return $state;
}

function tail_state_poll( &$state ) {
    foreach ( $state as $label => &$entry ) {
        $path = $entry[ "path" ];
        if ( !file_exists( $path ) ) {
            continue;
        }
        $size = filesize( $path );
        if ( $size < $entry[ "pos" ] ) {
            # file was rotated/truncated
            $entry[ "pos" ] = 0;
        }
        if ( $size > $entry[ "pos" ] ) {
            $fh = fopen( $path, "r" );
            fseek( $fh, $entry[ "pos" ] );
            while ( ( $line = fgets( $fh ) ) !== false ) {
                echo timestamp() . "[$label] " . rtrim( $line ) . "\n";
            }
            $entry[ "pos" ] = ftell( $fh );
            fclose( $fh );
        }
    }
}

####################################################################
# --check : read-only validation
####################################################################

function run_check( $db, $expected_user, $web_user, $lock_dir, $home, $submit_log, $udp_log, $dump_dir, $elog_file, $elog2_file, $us3bin ) {
    headerline( "autoflow pipeline health check" );

    $services_php = "$us3bin/services.php";
    echo "Service status ($services_php status):\n";
    if ( file_exists( $services_php ) ) {
        echo run_cmd( "php " . escapeshellarg( $services_php ) . " status 2>&1", false );
    } else {
        echo "  ** $services_php not found, skipping **\n";
    }
    echo "\n";

    $locks = [
        "listen"      => "$lock_dir/listen.php.lock",
        "manage"      => "$lock_dir/manage-us3-pipe.php.lock",
        "submitctl"   => "$lock_dir/submitctl.php.lock",
        "esign"       => "$lock_dir/esign.php.lock",
    ];

    foreach ( $locks as $name => $lockfile ) {
        $pid   = pid_from_lock( $lockfile );
        $owner = $pid ? process_owner( $pid ) : false;
        if ( !$pid ) {
            echo "  [$name] NOT RUNNING (no lock file or stale)\n";
            continue;
        }
        $alive = file_exists( "/proc/$pid" );
        $ok    = $alive && $owner === $expected_user;
        echo "  [$name] pid=$pid alive=" . ( $alive ? "yes" : "NO" ) .
             " owner=" . ( $owner ?: "?" ) .
             " expected=$expected_user " . ( $ok ? "OK" : "** MISMATCH/PROBLEM **" ) . "\n";
    }

    echo "\nWritable paths/files:\n";
    echo "(checking both the containing directory - for new file creation - and the file itself if it already exists,\n";
    echo " using the file's actual owner/group/permission bits, not is_writable(), so this is accurate even run as root.\n";
    echo " Paths only ever written by the submitctl/submitone daemons are checked against --expected-user ($expected_user,\n";
    echo " groups: " . implode( ",", user_groups( $expected_user ) ) . "). elog.txt/elog2.txt can ALSO be written directly\n";
    echo " by browser-triggered submissions running as --web-user ($web_user, groups: " . implode( ",", user_groups( $web_user ) ) . "),\n";
    echo " so those are checked against both.)\n";
    $paths = [
        "lock_dir"       => [ "users" => [ $expected_user ],             "candidates" => [ $lock_dir ] ],
        "submit.log"     => [ "users" => [ $expected_user ],             "candidates" => [ dirname( $submit_log ), $submit_log ] ],
        "udp.log"        => [ "users" => [ $expected_user ],             "candidates" => [ dirname( $udp_log ), $udp_log ] ],
        "dump dir"       => [ "users" => [ $expected_user ],             "candidates" => [ $dump_dir ] ],
        "elog.txt"       => [ "users" => [ $expected_user, $web_user ],  "candidates" => [ dirname( $elog_file ), $elog_file ] ],
        "elog2.txt"      => [ "users" => [ $expected_user, $web_user ],  "candidates" => [ dirname( $elog2_file ), $elog2_file ] ],
    ];
    foreach ( $paths as $label => $spec ) {
        foreach ( $spec[ "candidates" ] as $path ) {
            $kind = is_dir( $path ) ? "dir " : "file";
            foreach ( $spec[ "users" ] as $for_user ) {
                $info = path_owner_writable( $path, $for_user );
                if ( !$info[ "exists" ] ) {
                    echo "  [$label] $kind $path : DOES NOT EXIST\n";
                    continue 2; # no point checking a 2nd user against a nonexistent path
                }
                echo "  [$label] $kind $path : owner={$info['owner']} group={$info['group']} perms={$info['perms']} writable-by-$for_user=" .
                     ( $info[ "effective_writable" ] ? "yes" : "** NO **" ) .
                     " (" . $info[ "reason" ] . ")\n";
            }
        }
    }

    echo "\nStale autoflowAnalysis rows (status not FINISHED/FAILED, updateTime > 1 hour old):\n";
    $h = db_connect_lims();
    $res = mysqli_query( $h, "SELECT requestID, status, statusMsg, updateTime FROM ${db}.autoflowAnalysis WHERE status NOT IN ('FINISHED','FAILED') AND updateTime < ( NOW() - INTERVAL 1 HOUR )" );
    if ( $res && $res->num_rows ) {
        while ( $row = mysqli_fetch_assoc( $res ) ) {
            echo "  ** requestID={$row['requestID']} status={$row['status']} updateTime={$row['updateTime']} statusMsg={$row['statusMsg']}\n";
        }
    } else {
        echo "  none found\n";
    }
}

####################################################################
# --run / --watch : drive and/or watch a request through the pipeline
####################################################################

function fetch_autoflow_row( $h, $db, $reqid ) {
    $res = mysqli_query( $h, "SELECT requestID, status, statusMsg, statusJson, updateTime FROM ${db}.autoflowAnalysis WHERE requestID=$reqid" );
    if ( !$res || !$res->num_rows ) {
        return false;
    }
    return mysqli_fetch_assoc( $res );
}

# Removes the autoflow/analysisprofile/autoflowAnalysis rows created by a
# --run invocation, regardless of how the watch ended (FINISHED/FAILED/WAIT/
# timeout) - they're synthetic test data either way. Opt-in only: a WAIT row
# in particular can be a legitimate request a human still wants to finish by
# hand, so this is never run unless --cleanup was explicitly given. Without
# --cleanup, prints the equivalent DELETE statements so cleanup is still a
# one-line copy-paste away.
function cleanup_request( $db, $reqid, $do_cleanup ) {
    $h = db_connect_lims();
    $res = mysqli_query( $h, "SELECT autoflowID, aprofileGUID FROM ${db}.autoflowAnalysis WHERE requestID=$reqid" );
    $row = $res ? mysqli_fetch_assoc( $res ) : false;
    if ( !$row ) {
        echo "[cleanup] requestID $reqid not found (already removed?) - nothing to do\n";
        return;
    }

    $aprofileguid_esc = mysqli_real_escape_string( $h, $row[ "aprofileGUID" ] );
    $autoflowid        = (int) $row[ "autoflowID" ];
    $deletes = [
        "DELETE FROM ${db}.autoflowAnalysis WHERE requestID=$reqid",
        "DELETE FROM ${db}.analysisprofile WHERE aprofileGUID='$aprofileguid_esc'",
        "DELETE FROM ${db}.autoflow WHERE ID=$autoflowid",
    ];

    if ( $do_cleanup ) {
        echo "[cleanup] --cleanup given: removing test data for requestID=$reqid (autoflow ID=$autoflowid, analysisprofile aprofileGUID={$row['aprofileGUID']})\n";
        foreach ( $deletes as $sql ) {
            if ( !mysqli_query( $h, $sql ) ) {
                echo "[cleanup] ERROR running '$sql': " . mysqli_error( $h ) . "\n";
            }
        }
    } else {
        echo "[cleanup] test data left in place (pass --cleanup to remove it). To clean up manually:\n";
        foreach ( $deletes as $sql ) {
            echo "  $sql;\n";
        }
    }
}

function watch_request( $db, $reqid, $timeout, $poll_interval, $log_files ) {
    headerline( "watching autoflowAnalysis requestID=$reqid (db=$db)" );

    $h = db_connect_lims();
    $tail_state = tail_state_init( $log_files );
    $last_status_json = null;
    $start = time();
    $final = false;

    while ( true ) {
        $row = fetch_autoflow_row( $h, $db, $reqid );
        if ( !$row ) {
            echo timestamp() . "[watch] requestID $reqid no longer found\n";
            break;
        }
        if ( $row[ "statusJson" ] !== $last_status_json ) {
            echo timestamp() . "[watch] status={$row['status']} statusMsg={$row['statusMsg']} statusJson={$row['statusJson']}\n";
            $last_status_json = $row[ "statusJson" ];
        }

        tail_state_poll( $tail_state );

        if ( in_array( $row[ "status" ], [ "FINISHED", "FAILED" ] ) ) {
            $final = $row;
            break;
        }
        if ( strtoupper( $row[ "status" ] ) === "WAIT" ) {
            $statusJson = json_decode( $row[ "statusJson" ] );
            $waiting_on = $statusJson && isset( $statusJson->submitted ) ? $statusJson->submitted : "(unknown stage)";
            echo timestamp() . "[watch] requestID $reqid is WAITing on stage '$waiting_on' - this is expected for interactive\n";
            echo timestamp() . "[watch] stages (e.g. FITMEN requires a human to run interactive fitting in the desktop client\n";
            echo timestamp() . "[watch] and post results back). This is NOT a failure or a hang; stopping the watch here.\n";
            $final = $row;
            break;
        }
        if ( time() - $start > $timeout ) {
            echo timestamp() . "[watch] timeout after ${timeout}s, last status={$row['status']}\n";
            $final = $row;
            break;
        }
        sleep( $poll_interval );
    }

    # final drain in case anything landed between the last poll and exit
    tail_state_poll( $tail_state );
    return $final;
}

####################################################################
# fault injection helpers
####################################################################

function write_fake_sbatch( $test_bin_dir, $test_state_dir, $mode ) {
    if ( !is_dir( $test_bin_dir ) ) {
        mkdir( $test_bin_dir, 0755, true );
    }
    if ( !is_dir( $test_state_dir ) ) {
        mkdir( $test_state_dir, 0755, true );
    }
    $counter_file = "$test_state_dir/sbatch_fail_once_counter";
    if ( file_exists( $counter_file ) ) {
        unlink( $counter_file );
    }

    $real_sbatch = trim( run_cmd( "command -v sbatch || true", false ) );

    $script = "#!/bin/bash\n";
    $script .= "# generated by uslims_autoflow_test.php --fake-sbatch=$mode - safe to delete\n";
    if ( $mode === "fail-always" ) {
        $script .= "echo 'sbatch: error: Socket timed out on send/recv operation (fake-sbatch fail-always)' >&2\n";
        $script .= "exit 1\n";
    } elseif ( $mode === "fail-once" ) {
        $script .= "COUNTER_FILE='$counter_file'\n";
        $script .= "count=0\n";
        $script .= "if [ -f \"\$COUNTER_FILE\" ]; then count=\$(cat \"\$COUNTER_FILE\"); fi\n";
        $script .= "count=\$((count+1))\n";
        $script .= "echo \$count > \"\$COUNTER_FILE\"\n";
        $script .= "if [ \"\$count\" -le 1 ]; then\n";
        $script .= "  echo 'sbatch: error: Socket timed out on send/recv operation (fake-sbatch fail-once, attempt '\$count')' >&2\n";
        $script .= "  exit 1\n";
        $script .= "fi\n";
        $script .= "exec '$real_sbatch' \"\$@\"\n";
    } else { # succeed - thin passthrough to the real sbatch
        $script .= "exec '$real_sbatch' \"\$@\"\n";
    }

    $path = "$test_bin_dir/sbatch";
    file_put_contents( $path, $script );
    chmod( $path, 0755 );
    return $path;
}

function restart_submitctl_with_path( $gridctl_dir, $prepend_path ) {
    $path_env = $prepend_path ? "PATH=$prepend_path:\$PATH " : "";
    echo run_cmd( "cd " . escapeshellarg( $gridctl_dir ) . " && $path_env php services.php restart 2>&1", false );
}

####################################################################
# main
####################################################################

switch ( $mode ) {

    case "check": {
        run_check( $db, $expected_user, $web_user, $lock_dir, $home, $submit_log, $udp_log, $dump_dir, $elog_file, $elog2_file, $us3bin );
        exit;
    }

    case "run": {
        if ( !is_dir( $gridctl_dir ) ) {
            error_exit( "ERROR: --gridctl-dir '$gridctl_dir' is not a directory" );
        }
        $script = $gridctl_dir . "/autoflow_util/" . $scenario_scripts[ $scenario ];
        if ( !file_exists( $script ) ) {
            error_exit( "ERROR: scenario script not found: $script" );
        }

        if ( $fake_sbatch != "succeed" ) {
            echo "*** --fake-sbatch=$fake_sbatch enabled: restarting submitctl.php with a PATH override (this affects ANY in-flight jobs on this system) ***\n";
            $fake_path = write_fake_sbatch( $test_bin_dir, $test_state_dir, $fake_sbatch );
            restart_submitctl_with_path( $gridctl_dir, $test_bin_dir );
        }

        echo "Creating request via " . basename( $script ) . " $db $invid $rawid ...\n";
        $output = run_cmd( "cd " . escapeshellarg( $gridctl_dir . "/autoflow_util" ) . " && php " . escapeshellarg( basename( $script ) ) . " " . escapeshellarg( $db ) . " " . escapeshellarg( $invid ) . " " . escapeshellarg( $rawid ) . " 2>&1", false, true );
        foreach ( $output as $line ) {
            echo "[makeafrequest] $line\n";
        }

        $found_reqid = false;
        foreach ( $output as $line ) {
            if ( preg_match( '/inserted autoflowAnalysis requestID is (\d+)/', $line, $m ) ) {
                $found_reqid = (int) $m[ 1 ];
            }
        }
        if ( !$found_reqid ) {
            error_exit( "ERROR: could not determine requestID from makeafrequest output above" );
        }

        $log_files = [
            "submit.log" => $submit_log,
            "udp.log"    => $udp_log,
            "elog.txt"   => $elog_file,
            "elog2.txt"  => $elog2_file,
            "dump"       => "$dump_dir/$db-$found_reqid.txt",
        ];

        $final = watch_request( $db, $found_reqid, $timeout, $poll_interval, $log_files );

        if ( $fake_sbatch != "succeed" ) {
            echo "*** restoring submitctl.php to its normal PATH ***\n";
            restart_submitctl_with_path( $gridctl_dir, false );
        }

        if ( !$final ) {
            echo "RESULT: requestID $found_reqid disappeared mid-watch - FAIL\n";
            cleanup_request( $db, $found_reqid, $cleanup );
            exit( 1 );
        }

        headerline( "final result" );
        echo "requestID={$final['requestID']} status={$final['status']} statusMsg={$final['statusMsg']}\n";
        echo "statusJson={$final['statusJson']}\n";

        $passed = strcasecmp( $final[ "status" ], $expect_status ) === 0;
        echo $passed
            ? "RESULT: PASS (status matches expected '$expect_status')\n"
            : "RESULT: FAIL (status '{$final['status']}' != expected '$expect_status')\n";

        cleanup_request( $db, $found_reqid, $cleanup );
        exit( $passed ? 0 : 1 );
    }

    case "watch": {
        $log_files = [
            "submit.log" => $submit_log,
            "udp.log"    => $udp_log,
            "elog.txt"   => $elog_file,
            "elog2.txt"  => $elog2_file,
            "dump"       => "$dump_dir/$db-$reqid.txt",
        ];
        $final = watch_request( $db, $reqid, $timeout, $poll_interval, $log_files );
        if ( !$final ) {
            exit( 1 );
        }
        headerline( "final result" );
        echo "requestID={$final['requestID']} status={$final['status']} statusMsg={$final['statusMsg']}\n";
        exit( strcasecmp( $final[ "status" ], $expect_status ) === 0 ? 0 : 1 );
    }
}
