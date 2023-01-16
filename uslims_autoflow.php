<?php

{};

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

analyze autoflow records

Options

--help               : print this information and exit

--db dbname          : (required) specify the database name 
--reqid id           : specifiy the autoflowAnalysisHistory.requestID
--trace-failed       : add report details for status FAILED

__EOD;


require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$dbname              = "";
$reqid               = "";
$tracefailed         = false;

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

if ( empty( $dbname ) ||
     empty( $reqid ) ) {
    error_exit( "both --db and --reqid must be specified\n---\n$notes" );
    exit;
}

open_db();

$query = "select * from ${dbname}.autoflowAnalysisHistory where requestID=$reqid";
$aah   = db_obj_result( $db_handle, $query, false, true );

if ( !$aah ) {
    error_exit( "record not found : \"$query\"\n" . mysqli_error( $db_handle ) );
}

$aah->statusJson = json_decode( $aah->statusJson );
debug_json( "autoflowAnalysHistory:", $aah );

if ( $tracefailed ) {
    foreach ( $aah->statusJson as $v ) {
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

