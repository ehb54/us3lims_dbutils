<?php

# user defines

$us3lims      = exec( "ls -d ~us3/lims" );
$ll_base_dir  = "$us3lims/etc/joblog";
$us3bin       = "$us3lims/bin";

include "$us3bin/listen-config.php";
include $class_dir_p . "job_details.php";

# end user defines

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

list all uslims3_ databases in db

Options

--help                : print this information and exit

--db             name : select database to report (required for most options)
--reqid          id   : provide information on the specific HPCAnalysisRequestID
--gfacid         id   : provide information on the specific gfacID
--full                : display all field data (normally truncates multiline outputs to last line)
--queue-messages      : include queue message detail
--monitor             : monitor the output (requires --gfacid)
--running             : report on all running jobs (gfac.analysis & active jobmonitor.php)
--restart             : restart jobmonitors if needed (e.g. after a system reboot)

    
__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$db      = false;
$reqid   = false;
$gfacid  = false;
$anyargs = false;
$fullrpt = false;
$qmesgs  = false;
$monitor = false;
$running = false;
$restart = false;

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

if ( !$db && !$running && !$restart ) {
    error_exit( "ERROR: no database specified" );
}

if ( $reqid && $gfacid ) {
    error_exit( "ERROR: only one id option can be specified" );
}

if ( $monitor && !$gfacid ) {
    error_exit( "ERROR: --monitor required --gfacid" );
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
    
if ( $running || $restart ) {
    $jms = explode( "\n", trim( run_cmd( 'ps -ef | grep jobmonitor.php | grep -v grep | awk \'{ print $2 " " $10 " " $11 }\'' ) ) );
    $jm_active = [];
    foreach ( $jms as $v ) {
        $jm_row = explode( " ", $v );
        if ( count( $jm_row ) == 3 ) {
            $jm_active[ $jm_row[1] . ":" . $jm_row[2] ] = $jm_row[0];
        }
    }

    open_db();
    $res = db_obj_result( $db_handle, "select * from gfac.analysis order by cluster,us3_db,gfacid", true, true );

    $breakline = echoline( '-', 20 + 3 + 45 + 3 + 20 + 3 + 12 + 3 + 20, false );
    $out =
        $breakline
        . sprintf(
            "%-20s | %-20s | %-45s | %-12s | %s\n"
            , 'cluster'
            , 'db'
            , 'gfacid'
            , 'status'
            , 'job monitor pid'
        )
        . $breakline
        ;

    if ( !$res ) {
        if ( count( $jm_active ) ) {
            $out .=
                $jm_only_report( $jm_active )
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
        $jm_key =  $row[ 'us3_db' ] . ":" . $row[ 'gfacID' ];
        if ( array_key_exists( $jm_key, $jm_active ) ) {
            $jm_pid = $jm_active[ $jm_key ];
            unset( $jm_active[ $jm_key ] );
        } else {
            $jm_pid                = "not running";
            $db                    = $row[ 'us3_db' ];
            $gfacid                = $row[ 'gfacID' ];
            $jm_restart_db      [] = $db;
            $jm_restart_gfacid  [] = $gfacid;
            $reshpc =
                db_obj_result(
                    $db_handle
                    ,"select HPCAnalysisRequestID from $db.HPCAnalysisResult where gfacID=\"$gfacid\" limit 1"
                    , false
                    , false
                );
            if ( $reshpc ) {
                $jm_restart_hpcreqid[] = $reshpc->{ "HPCAnalysisRequestID" };
            } else {
                $jm_restart_hpcreqid[] = "unknown";
            }
        }
        $out .=
            sprintf(
                "%-20s | %-20s | %-45s | %-12s | %s\n"
                , $row[ 'cluster' ]
                , $row[ 'us3_db'  ]
                , $row[ 'gfacID'  ]
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

    if ( !$restart ) {
        exit;
    }
    
    if ( !count( $jm_restart_db ) ) {
        echo "no gfac.analysis found that need restarting\n";
        exit;
    }

    foreach ( $jm_restart_db as $index => $db ) {
        $gfacid   = $jm_restart_gfacid  [ $index ];
        $hpcreqid = $jm_restart_hpcreqid[ $index ];
        $cmd = "php $us3bin/jobmonitor/jobmonitor.php $db $gfacid $hpcreqid";
        echo "restarting: $cmd\n";
        run_cmd( $cmd );
    }
    exit;
}

$existing_dbs = existing_dbs();

if ( !in_array( $db, $existing_dbs ) ) {
    error_exit( "ERROR: database '$db' does not exist" );
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
    
    while( $row = mysqli_fetch_array($res) ) {
        $out .=
            echoline( '-', 80, false )
            . sprintf(
                "messageID              %s\n"
                . "analysisID             %s\n"
                . "message                %s\n"
                . "time                   %s\n"

                , $row[ 'messageID' ]
                , $row[ 'analysisID' ]
                , $row[ 'message' ]
                , $row[ 'time' ]
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

if ( $reqid ) {
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
