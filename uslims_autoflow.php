<?php

{};

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

analyze autoflow records

Options

--help                        : print this information and exit

--db dbname                   : (required) specify the database name 
--reqid id                    : specifiy the autoflowAnalysisHistory.requestID or autoflowAnalysis.requestID
--analysis-profile            : list analysis profile info for a specified --reqid
--analysis-profile-minimal    : list analysis profile without reportMask & combinedPlots, 
--analysis-profile-xml-json   : convert xml to json in analysis profile, implies --analysis-profile & --analysis-profile-minimal
--trace-failed                : add report details for status FAILED for autoflowAnalysisHistory
--running                     : list running autoflow jobs
--recent-history count        : list count most recent autoflowAnalysisHistory records

__EOD;


require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$dbname                 = "";
$reqid                  = "";
$tracefailed            = false;
$running                = false;
$analysisprofile        = false;
$analysisprofileminimal = false;
$analysisprofilexmljson = false;
$recenthistorycount     = 0;
    
while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--trace-failed": {
            array_shift( $u_argv );
            $tracefailed = true;
            break;
        }
        case "--analysis-profile": {
            array_shift( $u_argv );
            $analysisprofile = true;
            break;
        }
        case "--analysis-profile-minimal": {
            array_shift( $u_argv );
            $analysisprofileminimal = true;
            $analysisprofile        = true;
            break;
        }
        case "--analysis-profile-xml-json": {
            array_shift( $u_argv );
            $analysisprofilexmljson = true;
            $analysisprofileminimal = true;
            $analysisprofile        = true;
            break;
        }
        case "--running": {
            array_shift( $u_argv );
            $running = true;
            break;
        }
        case "--recent-history": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --recent-history requires an argument\n\n$notes" );
            }
            $recenthistorycount = array_shift( $u_argv );
            if ( empty( $dbname ) ) {
                error_exit( "--recent-historys a non-zero value\n\n$notes" );
            }
            break;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --db requires an argument\n\n$notes" );
            }
            $dbname = array_shift( $u_argv );
            if ( empty( $dbname ) ) {
                error_exit( "--db requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--reqid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --reqid requires an argument\n\n$notes" );
            }
            $reqid = array_shift( $u_argv );
            if ( empty( $reqid ) ) {
                error_exit( "--reqid requires a non-empty value\n\n$notes" );
            }
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

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( !file_exists( $use_config_file ) ) {
   error_exit( "$self: 
$use_config_file does not exist

to fix:

cp ${config_file}.template $use_config_file
and edit with appropriate values
"
        );
}
            
file_perms_must_be( $use_config_file );
require $use_config_file;

if ( empty( $dbname ) ) {
    error_exit( "--db must be specified\n---\n$notes" );
    exit;
}

if ( empty( $reqid ) && !$running && !$recenthistorycount) {
    error_exit( "--reqid, --running or --recent-history must be specified\n---\n$notes" );
    exit;
}

if ( !empty( $reqid ) && $running ) {
    error_exit( "--reqid and --running are mututally exclusive\n---\n$notes" );
    exit;
}

if ( $analysisprofile && $running ) {
    error_exit( "--analysisprofile and --running are mututally exclusive\n---\n$notes" );
    exit;
}

open_db();

if ( $running ) {
    $query   = "select * from ${dbname}.autoflowAnalysis";
    $running = db_obj_result( $db_handle, $query, true, true );
    if ( !$running ) {
        echo "No jobs currently running\n";
        exit;
    }

    $fmt = "%-6d | %-6d | %-18s | %-19s | %-19s | %-19s | %-8s | %s\n";
    $fmtlen = 166;
    
    echoline( '-', $fmtlen );
    echo sprintf(
        $fmt
        ,'requestID'
        ,'autoflowID'
        ,'filename'
        ,'createTime'
        ,'updateTime'
        ,'stageSubmitTime'
        ,'status'
        ,'statusMsg'

        );
    echoline( '-', $fmtlen );
    
    while( $aa = mysqli_fetch_array($running) ) {
        echo sprintf(
            $fmt
            ,$aa['requestID']
            ,$aa['autoflowID']
            ,substr($aa['filename'], 0, 18 )
            ,$aa['createTime']
            ,$aa['updateTime']
            ,$aa['stageSubmitTime']
            ,$aa['status']
            ,$aa['statusMsg']
            );
    }
    echoline( '-', $fmtlen );

    exit;
}

if ( $recenthistorycount ) {
    $query   = "select * from ${dbname}.autoflowAnalysisHistory order by createTime desc limit $recenthistorycount";
    $running = db_obj_result( $db_handle, $query, true, true );
    if ( !$running ) {
        echo "No jobs currently running\n";
        exit;
    }

    $fmt = "%-6d | %-6d | %-18s | %-19s | %-19s | %-19s | %-8s | %s\n";
    $fmtlen = 166;
    
    echoline( '-', $fmtlen );
    echo sprintf(
        $fmt
        ,'requestID'
        ,'autoflowID'
        ,'filename'
        ,'createTime'
        ,'updateTime'
        ,'stageSubmitTime'
        ,'status'
        ,'statusMsg'

        );
    echoline( '-', $fmtlen );
    
    while( $aa = mysqli_fetch_array($running) ) {
        echo sprintf(
            $fmt
            ,$aa['requestID']
            ,$aa['autoflowID']
            ,substr($aa['filename'], 0, 18 )
            ,$aa['createTime']
            ,$aa['updateTime']
            ,$aa['stageSubmitTime']
            ,$aa['status']
            ,trim($aa['statusMsg'])
            );
    }
    echoline( '-', $fmtlen );

    exit;
}


$history = true;
$query   = "select * from ${dbname}.autoflowAnalysisHistory where requestID=$reqid";
$aa      = db_obj_result( $db_handle, $query, false, true );

if ( !$aa ) {
    $query = "select * from ${dbname}.autoflowAnalysis where requestID=$reqid";
    $aa    = db_obj_result( $db_handle, $query, false, true );
    $history = false;
    
    if ( !$aa ) {
        error_exit( "record not found : \"$query\"\n" . mysqli_error( $db_handle ) );
    }
}

$aa->statusJson = json_decode( $aa->statusJson );
debug_json( $history ? "autoflowAnalysHistory:" : "autoflowAnalysis:", $aa );
if ( $analysisprofile ) {
    $query   = "select * from ${dbname}.analysisprofile where aprofileGUID='$aa->aprofileGUID'";
    $ap      = db_obj_result( $db_handle, $query, false, true );
    if ( $ap ) {
        if ( $analysisprofileminimal ) {
            unset( $ap->reportMask );
            unset( $ap->combinedPlots );
        } else {
            $ap->reportMask    = json_decode( $ap->reportMask );
            $ap->combinedPlots = json_decode( $ap->combinedPlots );
        }
            
        if ( $analysisprofilexmljson ) {
            $ap->xml = json_decode( json_encode(simplexml_load_string( $ap->xml ) ) );
        } else {
            $ap->xml           = explode( "\n", $ap->xml );
        }
            
        debug_json( "analysisProfile", $ap );
    } else {
        echo "analysisProfile with aprofileGUID $aa->aprofileGUID missing!\n";
    }
}

if ( $history && $tracefailed ) {
    foreach ( $aa->statusJson as $v ) {
        foreach ( $v as $v2 ) {
            foreach ( $v2 as $k3 => $v3 ) {
                if ( $v3->status == "FAILED" ) {
                    debug_json( "FAILED $k3 stage", $v3 );
                    $hpcarid = $v3->HPCAnalysisRequestID;
                    $query   = "select * from ${dbname}.HPCAnalysisRequest where HPCAnalysisRequestID=$v3->HPCAnalysisRequestID";
                    $hpcareq = db_obj_result( $db_handle, $query , false, true );
                    if ( !$hpcareq ) {
                        echo "HPCAnalysisRequest with HPCAnalysisRequestID $v3->HPCAnalysisRequestID missing\n";
                    } else {
                        $hpcareq->requestXMLFile = explode( "\n", $hpcareq->requestXMLFile );
                        debug_json( "HPCAnalysisRequest : ", $hpcareq );
                        $query   = "select * from ${dbname}.HPCAnalysisResult where HPCAnalysisRequestID=$v3->HPCAnalysisRequestID";
                        $hpcares = db_obj_result( $db_handle, $query , false, true );
                        if ( !$hpcares ) {
                            echo "HPCAnalysisResult with HPCAnalysisRequestID $v3->HPCAnalysisRequestID missing\n";
                        } else {
                            $hpcares->jobfile = explode( "\n", $hpcares->jobfile );
                            $hpcares->stderr  = explode( "\n", $hpcares->stderr );
                            $hpcares->stdout  = explode( "\n", $hpcares->stdout );
                            debug_json( "HPCAnalysisResult : ", $hpcares );
                        }                        
                    }
                }
            }
        }
    }
}

