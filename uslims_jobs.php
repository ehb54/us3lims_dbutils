<?php

# user defines

$us3lims      = exec( "ls -d ~us3/lims" );
$ll_base_dir  = "$us3lims/etc/joblog";
$udplogf      = "$us3lims/etc/udp.log";
$us3bin       = "$us3lims/bin";
$cwd          = getcwd();
$getrunbdir   = "$cwd/getrun";

include "$us3bin/listen-config.php";
if ( file_exists( $class_dir_p . "job_details.php" ) ) {
   include $class_dir_p . "job_details.php";
}

$global_config_file = $class_dir_p . "../global_config.php";

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
--restart-only       n     : restart n jobmonitors if needed (e.g. after a system reboot)
--check-log                : checks the log (requires --gfacid & exclusive of --monitor)
--getrundir                : gets running directory for airavata jobs 
--getrun                   : collects running info from rundir into $getrunbdir/db/HPCAnalysisRequestID
--runinfo                  : displays various debugging info for airavata jobs
--copyrun            queue : gets running info and sends to remote cluster for testing
--ga-times                 : reports timings for ga mc jobs (experimental)
--pmg                n     : report timings for specific pmg # (default 0), use 'all' do show all, 'most-recent' for just most recent, 'max-gen' for details about pmg with maximum generation
--airavata-details         : get current airavata job details (requires --gfacid)
--maxrss                   : maximum memory used report for selected database

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
$gatimes        = false;
$adetails       = false;
$pmg            = 0;
$maxrss         = false;

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
        case "--pmg": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $pmg = array_shift( $u_argv );
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
        case "--ga-times": {
            array_shift( $u_argv );
            $gatimes = true;
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
        case "--reqid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $reqid = array_shift( $u_argv );
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
        case "--airavata-details": {
            array_shift( $u_argv );
            $adetails = true;
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

cp ${config_file}.template $use_config_file
and edit with appropriate values
")
        ;
    exit(-1);
}
            
file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !$db && !$running && !$restart && !$restart_only) {
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

if ( $gatimes && !$getrun ) {
    error_exit( "ERROR: --ga-times requires --getrun" );
}

if ( $adetails && ( !$gfacid || !$db ) ) {
    error_exit( "ERROR: --airavata-details requires --gfacid and --db" );
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

if ( $adetails ) {
    if ( !is_aira_job( $gfacid ) ) {
        error_exit( "$gfacid does not appear to be a valid id for an Airavata managed job" );
    }
    $jobDetails = getJobDetails( $gfacid );
    if ( $jobDetails ) {
        if ( $jobDetails === ' No Job Details ' ) {
            $jdstdout = $jobDetails;
            $jdstderr = $jobDetails;
            
        } else {
            $jdstdout = isset( $jobDetails->stdOut ) ? trim( $jobDetails->stdOut ) : "n/a";
            $jdstderr = isset( $jobDetails->stdErr ) ? trim( $jobDetails->stdErr ) : "n/a";
        }
    } else {
        $jdstdout = "failed to get job details";
        $jdstderr = "failed to get job details";
    }
    $aira_details =
        sprintf(   "   Airavata stdout : %s\n", $jdstdout )
        . sprintf( "   Airavata stderr : %s\n", $jdstderr )
        ;
    echo $aira_details;
    exit;
}

if ( $running || $restart || $restart_only ) {
    $jms = explode( "\n", trim( run_cmd( 'ps -efww | grep jobmonitor.php | grep -v grep | awk \'{ print $2 " " $10 " " $11 }\'' ) ) );
    $jm_active = [];
    foreach ( $jms as $v ) {
        $jm_row = explode( " ", $v );
        if ( count( $jm_row ) == 3 ) {
            $jm_active[ $jm_row[1] . ":" . $jm_row[2] ] = $jm_row[0];
        }
    }

    open_db();
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

        $reshpc =
            db_obj_result(
                $db_handle
                ,"select HPCAnalysisRequestID from $db.HPCAnalysisResult where gfacID=\"$gfacid\" limit 1"
                , false
                , false
            );

        if ( $reshpc ) {
            $reqid = $reshpc->{ "HPCAnalysisRequestID" };
        } else {
            $reqid = "unknown";
        }

        if ( array_key_exists( $jm_key, $jm_active ) ) {
            $jm_pid = $jm_active[ $jm_key ];
            unset( $jm_active[ $jm_key ] );
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
FROM ${db}.HPCAnalysisResult
JOIN ${db}.HPCAnalysisRequest ON HPCAnalysisResult.HPCAnalysisRequestID=HPCAnalysisRequest.HPCAnalysisRequestID
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

if ( $reqid && !$getrundir && !$getrun && !$copyrun) {
    $out = "";
    $out .= hpcreqout( $reqid );
    $out .= hpcresbyreqout( $reqid );
        
    echo $out;

    exit(0);
}

function is_aira_job( $gfacID ) {
    return preg_match( "/US3-A/i", $gfacID ) ? true : false;
}

if ( $gfacid ) {
    $out = "";
    $out .= gfacanalysisout( $gfacid );
    if ( is_aira_job( $gfacid ) ) {
        $out .= echoline( '-', 80, false );
        $jobDetails = getJobDetails( $gfacid );
        if ( $jobDetails ) {
            if ( $jobDetails === ' No Job Details ' ) {
                $jdstdout = $jobDetails;
                $jdstderr = $jobDetails;
                
            } else {
                $jdstdout = isset( $jobDetails->stdOut ) ? trim( $jobDetails->stdOut ) : "n/a";
                $jdstderr = isset( $jobDetails->stdErr ) ? trim( $jobDetails->stdErr ) : "n/a";
            }
        } else {
            $jdstdout = "failed to get job details";
            $jdstderr = "failed to get job details";
        }
        $out .=
            sprintf( "Airavata job stdout    %s\n", $jdstdout )
            . sprintf( "Airavata job stderr    %s\n", $jdstderr )
            ;
    }

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

if ( $getrundir || $getrun || $copyrun ) {
    ## get run dir
    ## possibly a better way via airavata call

    ## get full info
    global $db;
    global $db_handle;

    $res = db_obj_result( $db_handle, "select *  from $db.HPCAnalysisRequest where HPCAnalysisRequestID=\"$reqid\"" );

    $cluster  = $res->{ 'clusterName' };
    $method   = $res->{ 'method' };
    $analType = $res->{ 'analType' };

    # echo "cluster $cluster\n";

    ## we don't have the queue name :( look it up

    $queue   = false;
    $login   = false;
    $workdir = false;

    foreach ( $cluster_details as $k => $v ) {
        if (
            $v['name'] == $cluster &&
            isset( $v['login'] ) &&
            isset( $v['workdir'] )
            ) {
            $queue   = $k;
            $login   = $v['login'];
            $workdir = $v['workdir'];
            break;
        }
    }
    
    if ( !$queue ) {
        error_exit( "could not find any queue with login defined in $global_config_file for cluster $cluster" );
    }
    
    if ( !isset( $cluster_details[$queue]['airavata'] ) ||
         !$cluster_details[$queue]['airavata'] ) {
        error_exit( "cluster $queue not marked as airavata in $global_config_file" );
    }        

    # echo "queue   $queue\n";
    # echo "login   $login\n";
    # echo "workdir $workdir\n";

    $padreqid = str_pad( $reqid, 5, '0', STR_PAD_LEFT );

    $inputfile = "hpcinput-localhost-$db-$padreqid.tar";

    $cmd = "runuser -l us3 -c \"ssh $login ls $workdir/PROCESS_*/$inputfile\" | grep PROCESS";
    $res = run_cmd( $cmd, false, true );

    # echo "cmd     $cmd\n";
    if ( count( $res ) != 1 ) {
        error_exit( "error attempting to retrieve rundir, multiple results:\n" . implode( "\n", $res ) );
    }
    $rundir = preg_replace( '/\/hpcinput.*tar/', '', $res[0] );
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

    $reqxmlf = "hpcrequest-localhost-$db-$padreqid.xml";

    $getfiles = [
        "job_*.slurm"
        ,$inputfile
        ,"Ultrascan.stdout"
        ,"Ultrascan.stderr"
        ,$reqxmlf
        ,"output/analysis-results.tar"
        ];

    $cmd = "runuser -l us3 -c \"rsync -avz $login:$rundir/{" . implode(",",$getfiles) . "} " . $tdir . "\"";

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
        $slurms = glob( "$tdir/job*slurm" );
        if ( count( $slurms ) ) {
            echoline();
            echo run_cmd( "grep 'SBATCH' $slurms[0]" );
        }
        if ( file_exists( "$tdir/$reqxmlf" ) ) {
            echoline();
            echo run_cmd( "grep -P '(datasetCount|iterations|groupcount|method)' $tdir/$reqxmlf | sed 's/^ *<//;s/> *$//;s/\/$//'" );
        }
        echoline();
        echo run_cmd( "cd $tdir && tail -25 Ultrascan.stderr" );
    }

    if ( $gatimes ) {
        ## this should be rewritten in a cleaner way with objects

        echoline();
        echo "ga-times:\n";
        echoline();
        
        if ( $method != "GA" ) {
            error_exit( "--ga-times selected but HPCRequestMethod is not GA" );
        }
        ## if ( strpos( $analType, "-MC" ) === false ) {
        ## error_exit( "--ga-times selected but HPCRequestMethod analysis type does not contain -MC" );
        ## }
        if ( !file_exists( "$tdir/Ultrascan.stderr" ) ) {
            error_exit( "--ga-times : $tdir/Ultrascan.stderr does not exist" );
        }
        if ( !file_exists( $udplogf ) ) {
            error_exit( "--ga-times : $udplogf does not exist" );
        }

        $udpstats = run_cmd( "grep manage-us3-pipe $udplogf | grep -P '$db-0*$reqid:'" , false, true );

        if ( $pmg == 'all' || strpos( $pmg, 'most-recent' ) !== false || strpos( $pmg, 'max-gen' ) !== false  ) {
            $mgcount = trim( run_cmd( "grep -P 'groupcount' $tdir/$reqxmlf | sed 's/^.*value=\"//;s/\".*$//'" ) );
            echo "mgroupcount is '$mgcount'\n";
            $pmg_start = 0;
            $pmg_end   = $mgcount;
        } else {
            $pmg_start = $pmg;
            $pmg_end   = $pmg + 1;
        }            
        
        $pmg_iterations       = [];
        $pmg_iter_generations = [];
        $pmg_iter_start       = [];
        $pmg_iter_end         = [];
        $last_update_time     = new DateTime('1970-01-01');
        $max_gen              = 0;
        $max_gen_pmg          = 0;

        $pmg_msgs = [];
        
        for ( $this_pmg = $pmg_start; $this_pmg < $pmg_end; ++$this_pmg ) {
            $this_udpstats = $udpstats;

            $pmg_msgs[ $this_pmg ] = "";

            if ( preg_grep( '/\(pmg \d+\)/', $this_udpstats ) ) {
                $this_udpstats = preg_replace( "/\(pmg $this_pmg\) /", '', preg_grep( "/\(pmg $this_pmg\)/", $this_udpstats ) );
            }
            
            if ( !count( $this_udpstats ) ) {
                error_exit( "--ga-times : no status found for pmg $this_pmg" );
            }

            ## echo implode( "\n", $this_udpstats ) . "\n";

            $times       = [];
            $generations = [];
            $mc          = [];

            foreach ( $this_udpstats as $v ) {
                if ( strpos( $v, "Avg. Generation" ) === false ) {
                    continue;
                }

                $vf = explode( " ", $v );
                $times[]       = new DateTime( "$vf[0] $vf[1]" );
                $generations[] = rtrim( $vf[6], ';' );
                $mc[]          = $vf[10];
            }

            $last_mci                 = 0;
            $mc_iteration_start       = [];
            $mc_iteration_end         = [];
            $mc_iteration_generations = [];
            $mc_pos                   = 0;

            for ( $i = 0; $i < count($times); ++$i ) {
                if ( $debug ) {
                    echo sprintf(
                        "%s %s %s\n"
                        ,$times[$i]->format( 'r' )
                        ,$generations[$i]
                        ,$mc[$i]
                        );
                }

                if ( !isset( $pmg_iter_generations[$this_pmg] ) ) {
                    $pmg_iter_generations[$this_pmg] = [];
                    $pmg_iter_start      [$this_pmg] = [];
                    $pmg_iter_end        [$this_pmg] = [];
                }

                if ( $last_mci != $mc[$i] ) {
                    ++$mc_pos;
                    $last_mci                                    = $mc[$i];
                    $mc_iteration_start[$mc_pos]                 = $times[$i];
                    $pmg_iter_start         [$this_pmg][$mc_pos] = $times[$i];
                }

                $mc_iteration_generations[$mc_pos]   = $generations[$i];
                $mc_iteration_end        [$mc_pos]   = $times      [$i];

                $pmg_iter_generations[$this_pmg][$mc_pos] = $generations[$i];
                $pmg_iter_end        [$this_pmg][$mc_pos] = $times      [$i];

                if ( $last_update_time->getTimestamp() < $times[$i]->getTimestamp() ) {
                    $last_update_time = $times[$i];
                    $last_update_pmg  = $this_pmg;
                }
                if ( $max_gen < $generations[$i] ) {
                    $max_gen     = $generations[$i];
                    $max_gen_pmg = $this_pmg;
                }
            }

            $tot_generations = 0;

            foreach ( $mc_iteration_start as $k => $v ) {
                $iteration_duration = dt_duration_minutes( $mc_iteration_start[$k], $mc_iteration_end[$k] );
                $tot_generations += $mc_iteration_generations[$k];
                $pmg_msgs[ $this_pmg ] .= sprintf(
                    "mc iteration %s last generation %s duration %s avg duration per generation %s\n"
                    ,$k
                    ,$mc_iteration_generations[$k]
                    ,dhms_from_minutes( $iteration_duration )
                    ,dhms_from_minutes( $iteration_duration / $mc_iteration_generations[$k] )
                    )
                    ;
            }
            
            ## total duration
            $tot_dur_min = dt_duration_minutes( $times[0], end($times) );
            $pmg_msgs[ $this_pmg ] .= sprintf( "total duration %s\n", dhms_from_minutes( $tot_dur_min ) );

            $mc_count = count( $mc_iteration_start );
            $pmg_msgs[ $this_pmg ] .= sprintf(
                "average time per mc iteration %s\n"
                ,dhms_from_minutes( $tot_dur_min / $mc_count )
                );
            $pmg_msgs[ $this_pmg ] .= sprintf(
                "average time per generation %s\n"
                ,dhms_from_minutes( $tot_dur_min / $tot_generations )
                );
            if ( $pmg == "all" ) {
                echoline();
                echo "pmg $this_pmg\n";
                echoline();
                echo $pmg_msgs[ $this_pmg ];
            }
        }
        if ( $pmg_start != $pmg_end ) {
            echoline();
            echo "most recent pmg with update $last_update_pmg\n";
            echoline();
            if ( strpos( $pmg, "most-recent" ) !== false ) {
                echo $pmg_msgs[ $last_update_pmg ];
            }
            echoline();
            echo "max generations pmg $max_gen_pmg\n";
            echoline();
            if ( strpos( $pmg, "max-gen" ) !== false ) {
                echo $pmg_msgs[ $max_gen_pmg ];
            }                
        } else {
            echo $pmg_msgs[ $this_pmg ];
        }
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
}
