<?php

# user defines

$us3lims      = exec( "ls -d ~us3/lims" );
$ll_base_dir  = "$us3lims/etc/joblog";
$us3bin       = "$us3lims/bin";
$getrunbdir   = getcwd() . "/getrun";

if ( !is_file( "$us3bin/listen-config.php" ) ) {
    fwrite( STDERR, "no listen-config.php at $us3bin (is the us3 account present?)\n" );
    exit( -1 );
}

include "$us3bin/listen-config.php";

if ( !isset( $class_dir ) || !strlen( $class_dir ) ) {
    fwrite( STDERR, "$us3bin/listen-config.php does not set \$class_dir; update gridctl and rerun uslims_upgrade.php\n" );
    exit( -1 );
}

## $class_dir may or may not carry a trailing slash
$global_config_file = rtrim( $class_dir, '/' ) . "/../global_config.php";

if ( !is_file( $global_config_file ) ) {
    fwrite( STDERR, "no global_config.php at $global_config_file (from \$class_dir '$class_dir')\n" );
    exit( -1 );
}

include $global_config_file;


# end user defines

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

information about submitted jobs

Options

--help                     : print this information and exit

--db                 name  : select database to report (required for most options)
--reqid              id    : provide information on the specific HPCAnalysisRequestID
--gfacid             id    : provide information on the specific gfacID
--onlygfac                 : just return the gfacid for a request id
--full                     : display all field data (normally truncates multiline outputs to last line)
--queue-messages           : include queue message detail
--monitor                  : monitor the output (requires --gfacid)
--running                  : report on all running jobs (gfac.analysis & active jobmonitor.php)
--restart                  : restart jobmonitors if needed (e.g. after a system reboot)
--restart-only       n     : restart n jobmonitors if needed (typically for debugging purposes)
--check-log                : checks the log (requires --gfacid & exclusive of --monitor)
--getrundir                : print the remote job directory for a request (requires --reqid)
--getrun                   : collect a request's job files from the cluster into $getrunbdir/db/HPCAnalysisRequestID (requires --reqid)
--runinfo                  : display debugging info for a job collected by --getrun (requires --getrun)
--copyrun            queue : collect a request's job files and copy them to another cluster for testing (requires --reqid)
--maxrss                   : maximum memory used report for selected database
--get-prior-ids      n     : report n previously completed ids


__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$db             = false;
$reqid          = false;
$gfacid         = false;
$onlygfac       = false;
$anyargs        = false;
$fullrpt        = false;
$qmesgs         = false;
$monitor        = false;
$running        = false;
$restart        = false;
$restart_only   = false;
$checklog       = false;
$getrundir      = false;
$getrun         = false;
$runinfo        = false;
$copyrun        = false;
$maxrss         = false;
$getpriorids    = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $db = array_shift( $u_argv );
            break;
        }
        case "--get-prior-ids": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $getpriorids = array_shift( $u_argv );
            break;
        }
        case "--reqid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $reqid = array_shift( $u_argv );
            ## --getrun/--copyrun put this into a runuser -c shell command below.
            if ( !preg_match( '/^\d+$/', $reqid ) ) {
                error_exit( "ERROR: --reqid must be a positive integer, got '$reqid'" );
            }
            break;
        }
        case "--gfacid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $gfacid = array_shift( $u_argv );
            break;
        }
        case "--onlygfac": {
            array_shift( $u_argv );
            $onlygfac = true;
            break;
        }
        case "--full": {
            array_shift( $u_argv );
            $fullrpt = true;
            break;
        }
        case "--queue-messages": {
            array_shift( $u_argv );
            $qmesgs = true;
            break;
        }
        case "--monitor": {
            array_shift( $u_argv );
            $monitor = true;
            break;
        }
        case "--running": {
            array_shift( $u_argv );
            $running = true;
            break;
        }
        case "--restart": {
            array_shift( $u_argv );
            $restart = true;
            break;
        }
        case "--restart-only": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $restart_only = array_shift( $u_argv );
            break;
        }
        case "--check-log": {
            array_shift( $u_argv );
            $checklog = true;
            break;
        }
        case "--getrundir": {
            array_shift( $u_argv );
            $getrundir = true;
            break;
        }
        case "--getrun": {
            array_shift( $u_argv );
            $getrun = true;
            break;
        }
        case "--runinfo": {
            array_shift( $u_argv );
            $runinfo = true;
            break;
        }
        case "--copyrun": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $copyrun = array_shift( $u_argv );
            break;
        }
        case "--maxrss": {
            array_shift( $u_argv );
            $maxrss = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( !$anyargs || count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( !file_exists( $use_config_file ) ) {
    fwrite( STDERR, "$self: 
$use_config_file does not exist

to fix:

cp {$config_file}.template $use_config_file
and edit with appropriate values
")
        ;
    exit(-1);
}
            
file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !$db && !$running && !$restart && !$restart_only && !$getpriorids ) {
    error_exit( "ERROR: no database specified" );
}

if ( $reqid && $gfacid ) {
    error_exit( "ERROR: only one id option can be specified" );
}

if ( $monitor && !$gfacid ) {
    error_exit( "ERROR: --monitor requires --gfacid" );
}

if ( $checklog && !$gfacid ) {
    error_exit( "ERROR: --checklog requires --gfacid" );
}

if ( $checklog && $monitor ) {
    error_exit( "ERROR: --checklog and --monitor can not both be specified" );
}

if (
    ( $getrundir && $getrun )
    || ( $getrundir && $copyrun )
    || ( $getrun && $copyrun )
    ) {
    error_exit( "ERROR: --getrundir --getrun --copyrun are mutually exclusive" );
}

if ( $getrundir && !$reqid ) {
    error_exit( "ERROR: --getrundir requires --reqid" );
}

if ( $getrun && !$reqid ) {
    error_exit( "ERROR: --getrun requires --reqid" );
}

if ( $copyrun && !$reqid ) {
    error_exit( "ERROR: --copyrun requires --reqid" );
}

if ( $runinfo && !$getrun ) {
    error_exit( "ERROR: --runinfo requires --getrun" );
}

## The running jobmonitors, keyed "db:gfacID" to pid.
##
## Read from "pid args" rather than fixed "ps -efww" columns. The old parse took
## fields $10 and $11, which are the db and gfacID only when the command starts
## at field 8. A monitor launched as "nice -15 php ..." (how local submission
## started them before the Slurm change) shifts them, so the key became
## Every cleanup finalizing marker under the job log tree. Mirrors
## jobmonitor/cleanup.php's cleanup_job_dir(), which is per database and job.
function finalizing_markers() {
    global $ll_base_dir;

    $base = ( isset( $ll_base_dir ) && $ll_base_dir != "" )
            ? $ll_base_dir
            : ( exec( "ls -d ~us3/lims" ) . "/etc/joblog" );

    return glob( "$base/*/*/finalizing" ) ?: [];
}

## Is a finalizing marker's own worker still alive? A marker is only evidence
## of a crash once its process is actually gone; closing out a stage while the
## worker that wrote the marker is still mid-import fails a job that was never
## unhealthy, just slow.
## $marker_started: the marker's own 'started' field (a wall-clock Unix
## timestamp), when known. A process cannot have started after the marker
## that names its pid was written, so a later start time means the pid was
## reused by something else, not that the original worker is still running.
## The real wall-clock time $pid started, or null when it cannot be
## determined. /proc/<pid>'s own ctime (the previous implementation here) is
## when the directory entry was last *looked up*, not when the process
## started, so it is not usable for this at all -- it was found to always
## read as "now" in practice, which made the reused-pid check below a no-op.
## Field 22 of /proc/<pid>/stat is the process's start time in clock ticks
## since boot (the comm field can itself contain spaces/parens, so this
## counts fields from the last ')' rather than splitting on whitespace from
## the start); combined with /proc/stat's btime (boot time, seconds since
## the epoch) that converts to an absolute start time. USER_HZ is not read
## from the kernel here -- every target this runs on is a stock x86_64 Linux
## build, where it is fixed at 100 -- consistent with gridctl's own
## cleanup_process_start() (jobmonitor/cleanup.php), which reads the same
## field for the same reason.
## USER_HZ: the unit /proc/<pid>/stat's starttime field is in. Fixed at 100 on
## stock x86_64 Linux (confirmed in the actual test container: `getconf
## CLK_TCK` => 100, and starttime/100 against /proc/uptime matches to within
## the sampling gap between the two reads) -- but read from the system
## instead of just trusting that, in case this ever runs on a target where
## it genuinely differs (ia64, mips, parisc, powerpc, s390 and sparc have
## shipped other defaults). Cached: one shell call total per process, not
## one per pid checked.
function marker_process_clk_tck() {
    static $hz = null;
    if ( $hz === null ) {
        $out = @shell_exec( 'getconf CLK_TCK 2>/dev/null' );
        $hz  = ( $out !== null && ctype_digit( trim( (string) $out ) ) && (int) trim( $out ) > 0 )
             ? (int) trim( $out ) : 100;
    }
    return $hz;
}

function marker_process_start_epoch( $pid ) {
    $stat = @file_get_contents( "/proc/$pid/stat" );
    if ( $stat !== false ) {
        $rest   = substr( $stat, (int) strrpos( $stat, ')' ) + 2 );
        $fields = preg_split( '/\s+/', trim( $rest ) );
        $sys    = @file_get_contents( '/proc/stat' );
        if ( isset( $fields[ 19 ] ) && $sys !== false && preg_match( '/^btime\s+(\d+)/m', $sys, $m ) ) {
            return (int) $m[ 1 ] + (int) ( (float) $fields[ 19 ] / marker_process_clk_tck() );
        }
    }

    ## No procfs (or unreadable): ask ps for elapsed seconds and work back to
    ## an absolute start time.
    $out = @shell_exec( 'ps -o etimes= -p ' . (int) $pid . ' 2>/dev/null' );
    if ( $out !== null && trim( $out ) !== '' ) {
        return time() - (int) trim( $out );
    }

    return null;
}

function marker_process_alive( $pid, $marker_started = 0 ) {
    $pid = (int) $pid;
    if ( $pid <= 0 ) {
        return false;
    }

    if ( function_exists( 'posix_kill' ) ) {
        if ( !@posix_kill( $pid, 0 ) ) {
            ## posix_kill() returning false does not mean "no such process":
            ## it also fails with EPERM (errno 1) when the pid exists but
            ## belongs to another account this one cannot signal, which this
            ## script running as a non-root account would otherwise misread
            ## as dead -- the opposite of the "still running" caution this
            ## check exists for.
            $alive = function_exists( 'posix_get_last_error' ) && posix_get_last_error() === 1;
            if ( !$alive ) {
                return false;
            }
        }
    } elseif ( !file_exists( "/proc/$pid" ) ) {
        return false;
    }

    if ( $marker_started > 0 ) {
        $started = marker_process_start_epoch( $pid );
        ## A few seconds of slack: writing the marker and this check are not
        ## simultaneous with the process actually starting. $started === null
        ## means it could not be determined at all (no procfs and no ps):
        ## that is not evidence of a reused pid, so it does not fail this.
        if ( $started !== null && $started > $marker_started + 5 ) {
            return false;
        }
    }

    return true;
}

## Closes out one finalizing marker whose worker is confirmed gone (--restart's
## caller already checked marker_process_alive() and the gfac.analysis row).
## $info is the marker's decoded JSON: 'gfacID', 'us3_db', and 'autoflowAnalysisID'
## (0 for a non-autoflow submission). Pulled out of the --restart loop so it can
## be exercised directly instead of only through a live database.
function close_out_finalizing_marker( $db_handle, $marker, array $info ) {
    $gfacid    = $info[ 'gfacID' ];
    ## The marker's own requestID is the HPCAnalysisRequestID (see
    ## cleanup_finalizing_begin() in gridctl's jobmonitor/cleanup.php); every
    ## close-out below matches on it together with gfacID, not gfacID alone,
    ## since gfacID gets reused and a stale marker from an older, unrelated
    ## row must not touch today's row for that gfacID.
    $requestid = (int) ( $info[ 'requestID' ] ?? 0 );

    $us3_db_esc  = mysqli_real_escape_string( $db_handle, $info[ 'us3_db' ] );
    $gfacid_esc  = mysqli_real_escape_string( $db_handle, $gfacid );
    $message     = "Results handling did not finish: the worker stopped after the job"
                  . " record was removed. Check the job log for $gfacid and resubmit if"
                  . " the results are missing.";
    $message_esc = mysqli_real_escape_string( $db_handle, $message );
    $autoflow_id = (int) ( $info[ 'autoflowAnalysisID' ] ?? 0 );

    if ( $autoflow_id > 0 ) {
        ## autoflowAnalysis's key is requestID, but the value held there is the
        ## autoflowAnalysisID -- the marker's own requestID field is the
        ## HPCAnalysisRequestID instead (see common's submit_slurm::update_db()
        ## and gridctl's job_state_machine::update_autoflow_status(), which both
        ## match this same column by autoflowAnalysisID, never by
        ## HPCAnalysisRequestID). Matching on the marker's requestID here either
        ## touched no row or the wrong one. 'CANCELED' and 'ERROR' are also
        ## excluded: a user-initiated cancel, and gridctl's own terminal
        ## error status, are not something this restart-time cleanup should
        ## overwrite as a failure.
        $query = "UPDATE $us3_db_esc.autoflowAnalysis SET status='FAILED', statusMsg='$message_esc'"
               . " WHERE requestID=$autoflow_id AND currentGfacID='$gfacid_esc'"
               . " AND status NOT IN ('FAILED','COMPLETE','CANCELED','ERROR')";
        if ( mysqli_query( $db_handle, $query ) === false ) {
            echo "could not close out autoflowAnalysisID $autoflow_id for $gfacid: "
               . mysqli_error( $db_handle ) . "\n";
            return;
        }
        if ( mysqli_affected_rows( $db_handle ) > 0 ) {
            ## gridctl's own final write (job_state_machine::update_autoflow_status())
            ## updates both autoflowAnalysis and HPCAnalysisResult; this
            ## restart-time close-out does the same, by HPCAnalysisRequestID
            ## AND gfacID together. Best-effort: the autoflowAnalysis row is
            ## already closed either way, so a failure here is logged, not
            ## treated as reason to leave the marker behind.
            if ( $requestid > 0 ) {
                $hpc_query = "UPDATE $us3_db_esc.HPCAnalysisResult SET queueStatus='failed',"
                           . " lastMessage='$message_esc' WHERE HPCAnalysisRequestID=$requestid"
                           . " AND gfacID='$gfacid_esc' AND queueStatus NOT IN ('completed','failed','aborted')";
                if ( mysqli_query( $db_handle, $hpc_query ) === false ) {
                    echo "closed out autoflowAnalysisID $autoflow_id for $gfacid, but could not"
                       . " also close out its HPCAnalysisResult: " . mysqli_error( $db_handle ) . "\n";
                }
            }
            echo "closed out autoflowAnalysisID $autoflow_id for $gfacid, whose results"
               . " handling was interrupted\n";
            @unlink( $marker );
            return;
        }
        ## Nothing changed: either the row is already terminal (nothing left to
        ## fix, safe to drop the marker) or it genuinely isn't there yet (leave
        ## the marker for the next --restart to retry, rather than delete it on
        ## a row we never actually touched).
        $found = db_obj_result( $db_handle,
            "select status from $us3_db_esc.autoflowAnalysis where requestID=$autoflow_id"
          . " and currentGfacID='$gfacid_esc'", false, true );
        if ( $found !== false ) {
            echo "autoflowAnalysisID $autoflow_id for $gfacid is already"
               . " '{$found->status}'; removing its marker\n";
            @unlink( $marker );
        } else {
            echo "no autoflowAnalysis row yet for autoflowAnalysisID $autoflow_id / $gfacid;"
               . " leaving its marker for the next --restart\n";
        }
        return;
    }

    ## Non-autoflow submission (e.g. plain GA/DMGA/2DSA): there is no
    ## autoflowAnalysis row to close. Mark the scientist-visible queue status
    ## instead, matched by HPCAnalysisRequestID AND gfacID together -- the
    ## same two keys job_state_machine's own
    ## update_hpc_analysis_result_status() resolves by -- not gfacID alone,
    ## which an old, unrelated row can share after gfacID gets reused.
    $query = "UPDATE $us3_db_esc.HPCAnalysisResult SET queueStatus='failed', lastMessage='$message_esc'"
           . " WHERE HPCAnalysisRequestID=$requestid AND gfacID='$gfacid_esc'"
           . " AND queueStatus NOT IN ('completed','failed','aborted')";
    if ( mysqli_query( $db_handle, $query ) === false ) {
        echo "could not close out HPCAnalysisResult for $gfacid: " . mysqli_error( $db_handle ) . "\n";
        return;
    }
    echo mysqli_affected_rows( $db_handle ) > 0
       ? "closed out HPCAnalysisResult for $gfacid, whose results handling was interrupted\n"
       : "HPCAnalysisResult for $gfacid already terminal or not found; removing its marker\n";
    @unlink( $marker );
}

## "php:<path>", matched no row, and the job read as unmonitored: --restart then
## started a second monitor for a job that already had one, and two monitors
## import the same results twice.
##
## The pattern also has to match the launched form rather than anything merely
## naming the script, so a grep or an editor on the file is not counted.
function active_jobmonitors() {
    $lines = [];
    exec( 'ps -eo pid=,args=', $lines );
    $active = [];
    foreach ( $lines as $line ) {
        if ( !preg_match( '#^\s*(\d+)\s+(?:\S*nice\s+-?\d+\s+)?\S*php[0-9.]*\s+'
                          . '\S*jobmonitor/jobmonitor\.php\s+(\S+)\s+(\S+)#', $line, $m ) ) {
            continue;
        }
        $active[ $m[ 2 ] . ':' . $m[ 3 ] ] = $m[ 1 ];
    }
    return $active;
}

function jm_only_report( $jm_active ) {
    $out = "";
    if ( count( $jm_active ) ) {
        foreach ( $jm_active as $k => $v ) {
            $jm_key_parts = explode( ":", $k );
            $us3_db = $jm_key_parts[0];
            $gfacid = $jm_key_parts[1];
            $out .=
                sprintf(
                    "%-20s | %-20s | %-45s | %-12s | %s\n"
                    , "*no gfac.analysis*"
                    , $us3_db
                    , $gfacid
                    , "*unknown*"
                    , $v
                )
                ;
        }
    }
    return $out;
}

if ( $getpriorids ) {
    open_db();
    if ( $db ) {
    $query =
        "SELECT r.HPCAnalysisRequestID, r.gfacID, r.queueStatus, r.endTime, a.clusterName FROM $db.HPCAnalysisResult AS r"
        . " JOIN $db.HPCAnalysisRequest AS a"
        . " ON a.HPCAnalysisRequestID = r.HPCAnalysisRequestID"
        . " ORDER BY r.endTime DESC"
        . " LIMIT $getpriorids"
        ;
    } else {
        # 1) discover schemas (single statement)
        $schema_sql =
            $schema_sql =
            "SELECT SCHEMA_NAME"
            . " FROM information_schema.SCHEMATA"
            . " WHERE SCHEMA_NAME REGEXP '^uslims3_[A-Za-z0-9_]+$'"
            . " AND SCHEMA_NAME <> 'uslims3_global'"
            ;
        
        $schemas_res = db_obj_result( $db_handle, $schema_sql, true, true );
        # debug if needed
        # if ( $schemas_res && method_exists($schemas_res, 'num_rows') ) {
        # echo "schemas found: " . $schemas_res->num_rows . "\n";
        # }
        
        if ( !$schemas_res ) {
            echo "no uslims3_% schemas (excluding uslims3_global) found\n";
            exit;
        }

        # 2) build subselects (keep each sub limited so UNION stays small)
        $subs = [];
        $per_schema_limit = max( 25, (int) $getpriorids );

        while ( $row = mysqli_fetch_array( $schemas_res ) ) {
            $schema = $row[ 'SCHEMA_NAME' ];

            # safety: only allow expected identifiers
            if ( !preg_match( '/^uslims3_\w+$/', $schema ) ) {
                continue;
            }

            $subs[] =
                "(SELECT '$schema' AS db"
                . " , r.HPCAnalysisRequestID"
                . " , r.gfacID"
                . " , r.queueStatus"
                . " , r.endTime"
                . " , a.clusterName"
                . " FROM `$schema`.HPCAnalysisResult r"
                . " JOIN `$schema`.HPCAnalysisRequest a"
                . "   ON a.HPCAnalysisRequestID = r.HPCAnalysisRequestID"
                . " ORDER BY (r.endTime IS NULL), r.endTime DESC"
                . " LIMIT $per_schema_limit)"
                ;
            
        }

        if ( empty( $subs ) ) {
            echo "no eligible schemas after validation\n";
            exit;
        }

        # 3) final single-statement UNION query (portable NULLS LAST)
        $query =
            "SELECT db, HPCAnalysisRequestID, gfacID, queueStatus, endTime, clusterName"
          . " FROM (" . implode( " UNION ALL ", $subs ) . ") x"
          . " ORDER BY (endTime IS NULL), endTime DESC"
          . " LIMIT " . (int) $getpriorids
          ;
    }

    $res = db_obj_result( $db_handle, $query, true, true );
    if ( !$res ) {
        echo "error " . mysqli_error($db_handle) . "\n";
        echo "no prior job results found for db $db\n";
        exit;
    }

    $maxclusternamelen = 30;

    $fmt = "%-{$maxclusternamelen}s | %-20s | %-6s | %-45s | %-19s | %-12s\n";
    $fmtlen = $maxclusternamelen + 3 + 20 + 3 + 6 + 3 + 45 + 3 + 19 + 3 + 12;

    echoline( "-", $fmtlen );
    echo sprintf(
        $fmt
        , 'cluster'
        , 'db'
        , 'reqid'
        , 'gfacid'
        , 'end'
        , 'status'
        );
    echoline( "-", $fmtlen );

    while( $row = mysqli_fetch_array( $res ) ) {
        $rowo = (object) $row;
        echo sprintf(
            $fmt
            , substr( $rowo->clusterName, 0, $maxclusternamelen )
            , ( isset( $rowo->db ) ? $rowo->db : $db )   # <-- use returned db for multi-schema
            , $rowo->HPCAnalysisRequestID
            , $rowo->gfacID
            , $rowo->endTime
            , $rowo->queueStatus
            );
    }
        
    echoline( "-", $fmtlen );
    exit;
}

if ( $running || $restart || $restart_only ) {
    $jm_active = active_jobmonitors();

    open_db();

    if ( $restart || $restart_only ) {
        ## A worker killed between deleting the gfac.analysis row and writing the
        ## final stage status leaves no row, so nothing below will restart a
        ## monitor for it and the stage sits at 'running'. cleanup leaves a marker
        ## across that span; a marker with no row means the worker died in it.
        ##
        ## Run before the gfac.analysis select below, not after: that select
        ## returns false, not an empty result, when the table has no rows (the
        ## usual state on a quiet host after a crash), and the code that used to
        ## follow it there exited on that before ever reaching this loop.
        foreach ( finalizing_markers() as $marker ) {
            $info = json_decode( (string) @file_get_contents( $marker ), true );
            if ( !is_array( $info ) || empty( $info[ 'us3_db' ] ) || empty( $info[ 'gfacID' ] ) ) {
                echo "ignoring unreadable finalizing marker $marker\n";
                continue;
            }
            $gfacid = $info[ 'gfacID' ];
            ## Still running is not the same as abandoned: a marker is only
            ## evidence of a crash once the process that wrote it is gone. Without
            ## this, --restart during a live import closed out a job that was
            ## simply slow, not unhealthy.
            if ( marker_process_alive( $info[ 'pid' ] ?? 0, (int) ( $info[ 'started' ] ?? 0 ) ) ) {
                echo "finalizing marker for $gfacid belongs to pid {$info['pid']}, which is"
                   . " still running; leaving it alone\n";
                continue;
            }
            $res = db_obj_result( $db_handle,
                "select count(*) as n from gfac.analysis where gfacID='"
                . mysqli_real_escape_string( $db_handle, $gfacid ) . "'", false, true );
            if ( $res !== false && (int) $res->{"n"} > 0 ) {
                ## The row is back, or was never deleted: the monitor restart below
                ## owns this job, so leave the marker to that run.
                continue;
            }
            close_out_finalizing_marker( $db_handle, $marker, $info );
        }
    }

    $res = db_obj_result( $db_handle, "select * from gfac.analysis order by cluster,us3_db,gfacid", true, true );

    $breakline = echoline( '-', 20 + 3 + 45 + 3 + 20 + 3 + 12 + 3 + 6 + 3 + 20, false );
    $out =
        $breakline
        . sprintf(
            "%-20s | %-20s | %-6s | %-45s | %-12s | %s\n"
            , 'cluster'
            , 'db'
            , 'reqid'
            , 'gfacid'
            , 'status'
            , 'job monitor pid'
        )
        . $breakline
        ;

    if ( !$res ) {
        if ( count( $jm_active ) ) {
            $out .=
                jm_only_report( $jm_active )
                . $breakline
                ;
            echo $out;
        } else {
            echo "no currently active jobs\n";
        }
        exit;
    }
    
    $jm_restart_db       = [];
    $jm_restart_gfacid   = [];
    $jm_restart_hpcreqid = [];
    
    while( $row = mysqli_fetch_array($res) ) {
        $db     = $row[ 'us3_db' ];
        $gfacid = $row[ 'gfacID' ];
        $jm_key = "$db:$gfacid";

        ## emptyok=true: gridctl can leave a gfac.analysis row with no
        ## HPCAnalysisResult behind (a deleted request or database). The
        ## us3-jobmonitors.service unit runs --restart at every boot (not
        ## services.php, which no longer duplicates this -- see its own
        ## comment in start()), exiting here on the first such row (the old
        ## emptyok=false) would skip restarting the monitor for every row
        ## after it, not just this one.
        $reshpc =
            db_obj_result(
                $db_handle
                ,"select HPCAnalysisRequestID from $db.HPCAnalysisResult where gfacID=\"$gfacid\" limit 1"
                , false
                , true
            );

        if ( $reshpc ) {
            $reqid = $reshpc->{ "HPCAnalysisRequestID" };
        } else {
            $reqid = "unknown";
        }

        if ( array_key_exists( $jm_key, $jm_active ) ) {
            $jm_pid = $jm_active[ $jm_key ];
            unset( $jm_active[ $jm_key ] );
        } elseif ( $reqid === "unknown" ) {
            ## round-5 fix: queuing this orphan row for restart anyway sent
            ## jobmonitor.php a non-numeric HPCAnalysisRequestID ("unknown"),
            ## which it rejects, exiting 255; run_cmd()'s default
            ## die_if_exit then aborted the whole --restart pass, skipping
            ## every row after this one, every single boot. Nothing can be
            ## restarted for a row with no HPCAnalysisResult to read a
            ## request id from, so it is skipped here instead, with a
            ## message, rather than queued to fail the whole pass.
            $jm_pid = "skipped (orphan row, no HPCAnalysisResult)";
        } else {
            $jm_pid                = "not running";
            $jm_restart_db      [] = $db;
            $jm_restart_gfacid  [] = $gfacid;
            $jm_restart_hpcreqid[] = $reqid;
        }

        $out .=
            sprintf(
                "%-20s | %-20s | %-6s | %-45s | %-12s | %s\n"
                , $row[ 'cluster' ]
                , $db
                , $reqid
                , $gfacid
                , $row[ 'status'  ]
                , $jm_pid
            )
            ;
    }

    $out .=
        jm_only_report( $jm_active )
        . $breakline
        ;

    if ( $running ) {
        echo $out;
    }

    if ( !$restart && !$restart_only ) {
        exit;
    }

    if ( !count( $jm_restart_db ) ) {
        echo "no gfac.analysis found that need restarting\n";
        exit;
    }

    $restarted = 0;
    foreach ( $jm_restart_db as $index => $db ) {
        $gfacid   = $jm_restart_gfacid  [ $index ];
        $hpcreqid = $jm_restart_hpcreqid[ $index ];
        $cmd = "php $us3bin/jobmonitor/jobmonitor.php $db $gfacid $hpcreqid";
        echo "restarting: $cmd\n";
        run_cmd( $cmd );
        if ( $restart_only && ++$restarted >= $restart_only ) {
            echo "Limit of $restart_only reached\n";
            exit;
        }
    }
    exit;
}

$existing_dbs = existing_dbs();

if ( !in_array( $db, $existing_dbs ) ) {
    error_exit( "ERROR: database '$db' does not exist" );
}

if ( $maxrss ) {
    open_db();
    
    $query =
"
SELECT (HPCAnalysisResult.max_rss/1024) AS 'maxrss', HPCAnalysisRequest.analType
FROM {$db}.HPCAnalysisResult
JOIN {$db}.HPCAnalysisRequest ON HPCAnalysisResult.HPCAnalysisRequestID=HPCAnalysisRequest.HPCAnalysisRequestID
WHERE HPCAnalysisResult.max_rss > 0
ORDER BY HPCAnalysisRequest.analType, HPCAnalysisResult.max_rss
";
        
    $fmt = "%12s | %s\n";
    $fmtlen = 30;

    echoline( "-", $fmtlen );
    echo sprintf(
        $fmt
        ,"MaxRSS MB"
        ,"Analysis Type"
        );
    echoline( "-", $fmtlen );

    $res = db_obj_result( $db_handle, $query, true, true );
    while( $row = mysqli_fetch_array($res) ) {
        echo sprintf(
            $fmt
            ,sprintf( "%.2f", $row['maxrss'] )
            ,$row['analType']
            );
    }
    echoline( "-", $fmtlen );
    exit;
}

if ( !$reqid && !$gfacid ) {
    # summary info for db
    $res = db_obj_result( $db_handle, "select HPCAnalysisRequestID from $db.HPCAnalysisRequest", True );
    $hpcreqs   = 0;
    $hpcress = 0;
    while( $row = mysqli_fetch_array($res) ) {
        $hpcreqid = $row['HPCAnalysisRequestID'];
        if ( $hpcreqs ) {
            if ( $hpcreqmin > $hpcreqid ) {
                $hpcreqmin = $hpcreqid;
            }
            if ( $hpcreqmax < $hpcreqid ) {
                $hpcreqmax = $hpcreqid;
            }
        } else {
            $hpcreqmin = $hpcreqid;
            $hpcreqmax = $hpcreqid;
        }
        $hpcreqs++;
    }
    

    $res = db_obj_result( $db_handle, "select HPCAnalysisResultID from $db.HPCAnalysisResult", True );
    while( $row = mysqli_fetch_array($res) ) {
        $hpcresid = $row['HPCAnalysisResultID'];
        if ( $hpcress ) {
            if ( $hpcresmin > $hpcresid ) {
                $hpcresmin = $hpcresid;
            }
            if ( $hpcresmax < $hpcresid ) {
                $hpcresmax = $hpcresid;
            }
        } else {
            $hpcresmin = $hpcresid;
            $hpcresmax = $hpcresid;
        }
        $hpcress++;
    }
    
    if ( $hpcreqs ) {
        echo "HPCAnalysisRequest count $hpcreqs id range $hpcreqmin:$hpcreqmax\n";
    } else {
        echo "HPCAnalysisRequest count $hpcreqs\n";
    }

    if ( $hpcreqs ) {
        echo "HPCAnalysisResult  count $hpcress id range $hpcresmin:$hpcresmax\n";
    } else {
        echo "HPCAnalysisResult  count $hpcress\n";
    }
    exit(0);
}

function gfacqmout( $id ) {
    global $fullrpt;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from gfac.queue_messages where analysisID=\"$id\" order by messageID", true, true );

    $out =
        echoline( '-', 80, false )
        ;

    if ( !$res ) {
        return $out . "gfac.queue_messages    [currently none]\n";
    }

    $out .= 
        "gfac.queue_messages\n"
        ;
    
    $out .= echoline( '-', 80, false );
    $fmt   = "%-9s | %-19s | %s\n";

    $out .= sprintf( $fmt
                    ,"messageID"
                    ,"time"
                    ,"message" );

    $out .= echoline( '-', 80, false );
    while( $row = mysqli_fetch_array($res) ) {
        $out .=
            sprintf(
                $fmt
                , $row[ 'messageID' ]
                , $row[ 'time' ]
                , $row[ 'message' ]
            )
            ;
    }
    return $out;
}

function gfacanalysisout( $gfacid ) {
    global $fullrpt;
    global $qmesgs;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from gfac.analysis where gfacID=\"$gfacid\"", true, true );

    $out =
        echoline( '-', 80, false )
        ;

    if ( !$res ) {
        return $out . "gfac.analysis          [currently none]\n";
    }

    $out .= 
        "gfac.analysis\n"
        ;
    
    while( $row = mysqli_fetch_array($res) ) {

        if ( !$fullrpt ) {
            $tmp = explode( "\n", trim( $row[ 'stdout'  ] ) ); $row[ 'stdout'  ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stderr'  ] ) ); $row[ 'stderr'  ] = end( $tmp );
#            $tmp = explode( "\n", trim( $row[ 'tarfile' ] ) ); $row[ 'tarfile' ] = end( $tmp );
        }

        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "id                     %s\n"
                . "gfacID                 %s\n"
                . "cluster                %s\n"
                . "us3_db                 %s\n"
                . "autoflowAnalysisID     %s\n"
                . "stdout                 %s\n"
                . "stderr                 %s\n"
#                . "tarfile                %s\n"
                . "status                 %s\n"
                . "queue_msg              %s\n"
                . "time                   %s\n"
                , $row[ 'id' ]
                , $row[ 'gfacID' ]
                , $row[ 'cluster' ]
                , $row[ 'us3_db' ]
                , $row[ 'autoflowAnalysisID' ]
                , $row[ 'stdout' ]
                , $row[ 'stderr' ]
#                , $row[ 'tarfile' ]
                , $row[ 'status' ]
                , $row[ 'queue_msg' ]
                , $row[ 'time' ]
            )
            ;

        if ( $qmesgs ) {
            $out .= gfacqmout( $row[ 'id' ] );
        }
    }
    return $out;
}

function hpcreqout( $reqid ) {
    global $fullrpt;
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisRequest where HPCAnalysisRequestID=\"$reqid\"" );
    if ( !$fullrpt ) {
        $tmp = explode( "\n", trim( $res->{ 'requestXMLFile' } ) ); $res->{ 'requestXMLFile' } = end( $tmp );
    }
    return
        echoline( '-', 80, false )
        . "HPCAnalysisRequest\n"
        . echoline( '-', 80, false )
        . sprintf(
            "HPCAnalysisRequestID   %s\n"
            . "HPCAnalysisRequestGUID %s\n"
            . "investigatorGUID       %s\n"
            . "submitterGUID          %s\n"
            . "email                  %s\n"
            . "experimentID           %s\n"
            . "requestXMLFile         %s\n"
            . "editXMLFilename        %s\n"
            . "submitTime             %s\n"
            . "clusterName            %s\n"
            . "method                 %s\n"
            . "analType               %s\n"

            , $res->{ 'HPCAnalysisRequestID' }
            , $res->{ 'HPCAnalysisRequestGUID' }
            , $res->{ 'investigatorGUID' }
            , $res->{ 'submitterGUID' }
            , $res->{ 'email' }
            , $res->{ 'experimentID' }
            , $res->{ 'requestXMLFile' }
            , $res->{ 'editXMLFilename' }
            , $res->{ 'submitTime' }
            , $res->{ 'clusterName' }
            , $res->{ 'method' }
            , $res->{ 'analType' }
        )
        ;
}    

function hpcresbyreqout( $reqid, $reqisgfac = false ) {
    global $fullrpt;
    global $db;
    global $db_handle;
    $out =
        echoline( '-', 80, false )
        . "HPCAnalysisResult(s)\n"
        ;

    if ( $reqisgfac ) {
        $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisResult where gfacID=\"$reqid\"", True );
    } else {
        $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisResult where HPCAnalysisRequestID=\"$reqid\"", True );
    }
    while( $row = mysqli_fetch_array($res) ) {
        if ( !$fullrpt ) {
            $tmp = explode( "\n", trim( $row[ 'jobfile' ] ) ); $row[ 'jobfile' ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stderr'  ] ) ); $row[ 'stderr'  ] = end( $tmp );
            $tmp = explode( "\n", trim( $row[ 'stdout'  ] ) ); $row[ 'stdout'  ] = end( $tmp );
        }
        # debug_json( "row", $row );
        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "HPCAnalysisResultID    %s\n"
                . "HPCAnalysisRequestID   %s\n"
                . "startTime              %s\n"
                . "endTime                %s\n"
                . "queueStatus            %s\n"
                . "lastMessage            %s\n"
                . "updateTime             %s\n"
                . "gfacID                 %s\n"
                . "jobfile                %s\n"
                . "wallTime               %s\n"
                . "CPUTime                %s\n"
                . "CPUCount               %s\n"
                . "mgroupcount            %s\n"
                . "max_rss                %s\n"
                . "calculatedData         %s\n"
                . "stderr                 %s\n"
                . "stdout                 %s\n"
                
                , $row[ 'HPCAnalysisResultID' ]
                , $row[ 'HPCAnalysisRequestID' ]
                , $row[ 'startTime' ]
                , $row[ 'endTime' ]
                , $row[ 'queueStatus' ]
                , $row[ 'lastMessage' ]
                , $row[ 'updateTime' ]
                , $row[ 'gfacID' ]
                , $row[ 'jobfile' ]
                , $row[ 'wallTime' ]
                , $row[ 'CPUTime' ]
                , $row[ 'CPUCount' ]
                , $row[ 'mgroupcount' ]
                , $row[ 'max_rss' ]
                , $row[ 'calculatedData' ]
                , $row[ 'stderr' ]
                , $row[ 'stdout' ]
            )
            ;
        if ( !$reqisgfac ) {
            $out .= gfacanalysisout( $row[ 'gfacID' ] );
        } else {
            $out .= hpcreqout( $row[ 'HPCAnalysisRequestID' ] );
        }
    }
    return $out;
}

if ( $reqid && $onlygfac ) {
    $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisResult where HPCAnalysisRequestID=\"$reqid\"", True );
    while( $row = mysqli_fetch_array($res) ) {
        echo $row[ 'gfacID' ] . "\n";
    }
    exit;
}

if ( $reqid && !$getrundir && !$getrun && !$copyrun ) {
    $out = "";
    $out .= hpcreqout( $reqid );
    $out .= hpcresbyreqout( $reqid );

    echo $out;

    exit(0);
}

if ( $getrundir || $getrun || $copyrun ) {
    ## get full info
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisRequest where HPCAnalysisRequestID=\"$reqid\"" );

    $cluster = $res->{ 'clusterName' };
    $method  = $res->{ 'method' };

    ## we don't have the queue name :( look it up

    $queue   = false;
    $login   = false;
    $workdir = false;

    foreach ( $cluster_details as $k => $v ) {
        if (
            $v['name'] == $cluster &&
            isset( $v['workdir'] )
            ) {
            $queue   = $k;
            ## Same fallback as remote_exec::login(): an entry with no explicit
            ## 'login' still has an ssh identity, just the default one.
            $login   = $v['login'] ?? ( 'us3@' . $v['name'] );
            $workdir = $v['workdir'];
            break;
        }
    }

    if ( !$queue ) {
        error_exit( "could not find any queue with workdir defined in $global_config_file for cluster $cluster" );
    }

    $padreqid = str_pad( $reqid, 5, '0', STR_PAD_LEFT );

    ## Matches dbinst's lib/file_writer.php, which names these from the
    ## instance's own $job['database']['host'], not a literal "localhost".
    $inputfile = "hpcinput-$dbhost-$db-$padreqid.tar";

    ## submit_slurm's direct-ssh layout: one deterministic directory per
    ## request, not the Airavata PROCESS_*/ directory this used to glob for.
    $rundir = rtrim( $workdir, '/' ) . '/' . $db . sprintf( "-%06d", $reqid );

    echoline();
    echo "$login:$rundir\n";
    if ( $getrundir ) {
        exit(0);
    }

    ## mkdir

    $tdir = "$getrunbdir/$db/$reqid";

    if ( !is_dir( $tdir ) ) {
        mkdir( $tdir, 0777, true );
        if ( !is_dir( $tdir ) ) {
            error_exit( "Could not make directory $tdir" );
        }
    }

    ## make sure directory is owned by us3
    $cmd = "chown -R us3:us3 $getrunbdir";
    run_cmd( $cmd );

    ## get info

    ## submit_slurm.php's own layout (class/submit_slurm.php:253-254,267): the
    ## Airavata-era names (job_*.slurm, Ultrascan.stdout/stderr) are gone.
    ## The request XML is never one of them: submit_slurm::stage_files()
    ## only ever scp's the input tar and us3.slurm to the run directory (see
    ## common's class/submit_slurm.php:131), so it is not requested here --
    ## asking rsync for a file that can never exist used to end every
    ## --getrun with exit code 23 (partial transfer).
    ##
    ## us_mpi_analysis writes the results tar at the top of the run
    ## directory; output/analysis-results.tar is a fallback for older
    ## layouts, same order gridctl's own fetch tries them in
    ## (jobmonitor/cleanup.php's $tar_candidates).
    $getfiles = [
        "us3.slurm"
        ,$inputfile
        ,"stdout"
        ,"stderr"
        ,"analysis-results.tar"
        ,"output/analysis-results.tar"
        ];

    ## $login and $rundir are quoted; the resulting runuser -c argument is
    ## quoted as a whole too, instead of the previous hand-rolled double
    ## quotes, which broke if any of these values (ultimately config- and
    ## instance-derived) ever contained one. The curly-brace file list stays
    ## bare: it is bash's own brace expansion, not something escapeshellarg()
    ## can be applied to without disabling it.
    $remote_spec = escapeshellarg( $login ) . ':' . escapeshellarg( $rundir )
                 . '/{' . implode( ",", $getfiles ) . '}';
    $inner_cmd   = "rsync -avz $remote_spec " . escapeshellarg( $tdir );
    $cmd         = "runuser -l us3 -c " . escapeshellarg( $inner_cmd );

    echoline();
    echo "$cmd\n";
    echoline();

    echo run_cmd( $cmd, false );

    echo "results in:\n$tdir\n";
    echoline();
    echo run_cmd( "cd $tdir && ls -ltr" );
    echoline();
    echo "results in:\n$tdir\n";

    if ( $runinfo ) {
        if ( file_exists( "$tdir/us3.slurm" ) ) {
            echoline();
            echo run_cmd( "grep 'SBATCH' $tdir/us3.slurm" );
        }
        ## The request XML was never fetched above (it is never staged to
        ## the run directory in the first place), so there is nothing here
        ## to grep it from.
        echoline();
        echo run_cmd( "cd $tdir && tail -25 stderr" );
    }

    if ( !$copyrun ) {
        exit(0);
    }

    ## get target info

    if ( !isset( $cluster_details[ $copyrun ] ) ) {
        error_exit( "could not find queue $copyrun in $global_config_file" );
    }

    if ( !isset( $cluster_details[ $copyrun ]['login'] ) ) {
        error_exit( "$global_config_file \$cluster_details['$copyrun'] does not have 'login' set" );
    }
    if ( !isset( $cluster_details[ $copyrun ]['workdir'] ) ) {
        error_exit( "$global_config_file \$cluster_details['$copyrun'] does not have 'workdir' set" );
    }

    $dworkdir = $cluster_details[ $copyrun ]['workdir'] . "/test/$db/$reqid";
    $dlogin   = $cluster_details[ $copyrun ]['login'];

    ## mkdir workdir/../test/db/reqid
    $cmd = "runuser -l us3 -c \"ssh $dlogin mkdir -p $dworkdir\"";
    echoline();
    echo "$cmd\n";
    echoline();
    echo run_cmd( $cmd, false );

    ## rsync to workdir/../test/db/reqid
    $cmd = "runuser -l us3 -c \"rsync -avz $tdir/* $dlogin:$dworkdir\"";
    echoline();
    echo "$cmd\n";
    echoline();
    echo run_cmd( $cmd, false );
    echoline();
    echo "results now in:\n$dlogin:$dworkdir\n";

    exit(0);
}

if ( $gfacid ) {
    $out = "";
    $out .= gfacanalysisout( $gfacid );
    $out .= hpcresbyreqout( $gfacid, true );

    echo $out;

    $lock_dir = "$ll_base_dir/$db/$gfacid";
    $logfile  = "$lock_dir/log.txt";
    echoline();
    echo "logfile                $logfile\n";
    
    if ( $checklog ) {
       check_log( $logfile );
       exit(0);
    }

    if ( $monitor ) {
        if ( !file_exists( $logfile ) ) {
            error_exit( "can not monitor, $logfile does not exist\n" );
        }

        $lockfile = "$ll_base_dir/$db/$gfacid/jobmonitor.php.lock";
        if ( !file_exists( $lockfile ) ) {
            echoline();
            echo "jobmonitor.php is not running for this job\n";
            echo "logfile contents:\n";
            echoline();
            echo `cat $logfile`;
            echoline();
            exit(0);
        }
        echo "output of              tail -f $logfile\n";
        echo "*** use control-C to quit ***\n";
        echoline();
        passthru( "tail -f $logfile" );
    }            
    exit(0);
}

## check log functions

function log_str_date( $l ) {
    $date = substr( $l, 0, 19 );
    return $date;
}

function log_date( $l ) {
    return date_create_from_format('Y-m-d H:i:s', log_str_date( $l ) );
}

function check_log( $fname ) {
    $find = [ "terminated", "error", "exit" ];
    $findmsg = "\t" . implode( "\n\t", $find ) . "\n";
    $max_duration = 1;
    $notes = "checks logs for lines matching:\n$findmsg\nand time gaps greater than $max_duration\n";
    
    if ( !file_exists( $fname ) ) {
        error_exit( "file $fname does not exist" );
    }
    $ls = explode( "\n", file_get_contents( $fname ) );

    $lc = count( $ls );
    echo "log file opened with $lc lines\n";

    $out = [];
    
    # check for keywords

    foreach ( $find as $v ) {
        $found = preg_grep( "/$v/i", $ls );
        if ( count( $found ) ) {
            foreach ( $found as $vf ) {
                $out[] = $vf;
            }
        }
    }

    # check for time gaps

    $start = true;

    foreach ( $ls as $l ) {
        if ( $start ) {
            $start = false;
            $sdate = log_date( $l );
            $sline = $l;
            continue;
        }
        if ( ! ( $edate = log_date( $l ) ) ) {
            $edate = $sdate;
        }
        if ( dt_duration_minutes( $sdate, $edate ) > $max_duration ) {
            $out[] = $sline;
            $out[] = sprintf( "%s->%s : time gap of %d minutes", log_str_date( $sline), log_str_date( $l ), dt_duration_minutes( $sdate, $edate ) );
            $out[] = $l;
        }
        $sdate = $edate;
        $sline = $l;
    }

    sort( $out );
    echo implode( "\n", $out ) . "\n";
}
