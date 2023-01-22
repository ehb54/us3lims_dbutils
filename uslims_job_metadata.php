<?php

{};

$self = __FILE__;
ini_set('memory_limit','2G');
$metadata_format_file = "uslims_metadata_format.json";

$notes = <<<__EOD
usage: $self {options} {db_config_file}

exports job data metadata

Options

--help                 : print this information and exit

--db dbname            : specify the database name, can be specified multiple times
--reqid id             : restrict results by HPCAnalysisRequest.HPCAnalysisRequestID
--analysis-type        : restrict results by HPCAnalysisRequest.analType
--analysis-type-rlike  : restrict results by HPCAnalysisRequest.analType using mysql rlike syntax
--dataset-count        : restrict results by dataset count
--list-analysis-type   : list 
--list-dataset-count   : list HPCAnalysisRequest.xml dataset count
--json                 : output full JSON
--squashed-json        : output squashed JSON    
--json-metadata        : output JSON metadata
--metadata-format-file : specify metadata format file (default: $metadata_format_file)

__EOD;


require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs             = [];
$reqid               = 0;
$reqid_used          = false;
$analysistype        = "";
$analysistyperlike   = "";
$datasetcount        = 0;
$listanalysistype    = false;
$listdatasetcount    = false;
$json                = false;
$squashedjson        = false;
$jsonmetadata        = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
        case "--list-analysis-type": {
            array_shift( $u_argv );
            $listanalysistype = true;
            break;
        }
        case "--list-dataset-count": {
            array_shift( $u_argv );
            $listdatasetcount = true;
            break;
        }
        case "--json": {
            array_shift( $u_argv );
            $json = true;
            break;
        }
        case "--squashed-json": {
            array_shift( $u_argv );
            $squashedjson = true;
            break;
        }
        case "--json-metadata": {
            array_shift( $u_argv );
            $jsonmetadata = true;
            break;
        }
        case "--metadata-format-file": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --metadata-format-file requires an argument\n\n$notes" );
            }
            $metadata_format_file = array_shift( $u_argv );
            if ( empty( $metadata_format_file ) ) {
                error_exit( "--metadata-format-file requires a non-empty value\n\n$notes" );
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
            $reqid_used = true;
            break;
        }
        case "--analysis-type": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --analysis-type requires an argument\n\n$notes" );
            }
            $analysistype = array_shift( $u_argv );
            if ( empty( $analysistype ) ) {
                error_exit( "--analysis-type requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--analysis-type-rlike": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --analysis-type-rlike requires an argument\n\n$notes" );
            }
            $analysistyperlike = array_shift( $u_argv );
            if ( empty( $analysistyperlike ) ) {
                error_exit( "--analysis-type-rlike requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--dataset-count": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --dataset-count requires an argument\n\n$notes" );
            }
            $datasetcount = array_shift( $u_argv );
            if ( $datasetcount <= 0 ) {
                error_exit( "--dataset-count requires a positive numeric value\n\n$notes" );
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

if ( !file_exists( $metadata_format_file ) ) {
    error_exit( "metadata format file '$metadata_format_file' does not exist" );
}

## remove comment lines
$metadata_format = json_decode( implode( "\n",preg_grep( '/^\s*#/', explode( "\n", file_get_contents( $metadata_format_file ) ), PREG_GREP_INVERT ) ) );

## debug_json( "$metadata_format_file" , $metadata_format );
if ( !isset( $metadata_format->fields ) ) {
    error_exit( "$metadata_format_file missing 'fields' attribute" );
}

if (
    !$json
    && !$squashedjson
    && !$listanalysistype
    && !$listdatasetcount
    && !$jsonmetadata
    ) {
    error_exit( "nothing to do" );
}

open_db();

$existing_dbs = existing_dbs();
if ( !count( $use_dbs ) ) {
    $use_dbs = $existing_dbs;
} else {
    $db_diff = array_diff( $use_dbs, $existing_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database : " . implode( ' ', $db_diff ) );
    }
}

foreach ( $use_dbs as $db ) {
    headerline( "db $db" );

    $query   = "select * from ${db}.HPCAnalysisRequest where requestXMLFile is not null";
    if ( $reqid_used ) {
        $query .= " and HPCAnalysisRequest.HPCAnalysisRequestID=$reqid";
    }
    if ( !empty($analysistype ) ) {
        $query .= " and HPCAnalysisRequest.analType='$analysistype'";
    }
    if ( !empty($analysistyperlike ) ) {
        $query .= " and HPCAnalysisRequest.analType rlike '$analysistyperlike'";
    }

    $hpcareqs = db_obj_result( $db_handle, $query , true, true );

    if ( $hpcareqs ) {
        while( $hpcareq = mysqli_fetch_array($hpcareqs) ) {
            $thisreqid = $hpcareq['HPCAnalysisRequestID'];

            $meta = (object)[];
            $meta->clusterName = $hpcareq['clusterName'];
            $meta->method      = $hpcareq['method'];
            $meta->analType    = $hpcareq['analType'];
            $meta->xml         = explode( "\n", $hpcareq['requestXMLFile'] );
            $meta->xmlj        = json_decode( json_encode(simplexml_load_string( $hpcareq['requestXMLFile'] ) ) );
            $meta->xmls        = (object)squash( $meta->xmlj );
            
            if ( !is_object( $meta->xmlj ) ) {
                debug_json( "metadata non object", $meta );
                debug_json( "HPCAnalysisRequest : $thisreqid", $hpcareq );
                error_exit( "non-object xmlj");
            }

            if ( $datasetcount > 0
                 && $meta->xmlj->job->datasetCount->{'@attributes'}->value != $datasetcount ) {
                continue;
            }

            ## do we have an analysis result?

            $query = "select * from ${db}.HPCAnalysisResult where HPCAnalysisResult.HPCAnalysisRequestID=$thisreqid";
            $hpcaress = db_obj_result( $db_handle, $query , true, true );

            if ( !$hpcaress ) {
                ## no analysis results, skipping
                # echo "HPCAnalysisRequest $thisreqid has no HPCAnalysisResult\n";
                continue;
            }

            $meta->jmd = (object)[];

            $skip = false;
            while( $hpcares = mysqli_fetch_array($hpcaress) ) {
                $thisresid = $hpcares['HPCAnalysisResultID'];
                if ( isset( $meta->jmd->CPUCount ) ) {
                    echo "WARN: HPCAnalysisRequest $thisreqid has multiple HPCAnalysisResult records\n";
                    $skip = true;
                    break;
                }

                ## skip failed jobs
                if (
                    $hpcares['queueStatus'] != 'completed'
                    || $hpcares['CPUCount'] == 0
                    || $hpcares['wallTime'] == 0
                    || $hpcares['CPUTime'] == 0
                    || false !== strpos( $hpcares['lastMessage'], "FAILED" )
                    ) {
                    $skip = true;
                    break;
                }
                # echo "HPCAnalysisRequest $thisreqid HPCAnalysisResult $thisresid queueStatus '" . $hpcares['queueStatus'] . "'\n";

                $meta->jmd->CPUCount = $hpcares['CPUCount'];
                $meta->jmd->wallTime = $hpcares['wallTime'];
                $meta->jmd->CPUTime  = $hpcares['CPUTime'];
                $meta->jmd->max_rss  = $hpcares['max_rss'];

                # debug_json( "HPCAnalysisRequest $thisreqid HPCAnalysisResult $thisresid", $hpcares );
            }

            if ( $skip ) {
                continue;
            }

            if ( $listdatasetcount || $listanalysistype ) {
                $out = "HPCAnalysisRequestID $thisreqid :";
                if ( $listanalysistype ) {
                    $out .= " analType : $meta->analType";
                }
                if ( $listdatasetcount ) {
                    $out .= " datasetCount : " . $meta->xmlj->job->datasetCount->{'@attributes'}->value;
                }
                echo "$out\n";
            }

            if ( $json ) {
                ## no squashed
                $tmpmeta = json_decode( json_encode( $meta ) );
                unset( $tmpmeta->xmls );
                debug_json( "HPCAnalysisRequestID $thisreqid json", $tmpmeta );
            }

            if ( $squashedjson ) {
                debug_json( "HPCAnalysisRequestID $thisreqid squashedjson", $meta->xmls );
            }

            if ( $jsonmetadata ) {
                foreach ( $metadata_format->fields as $v ) {
                    $meta->jmd->{$v} = isset( $meta->xmls->{$v} ) ? $meta->xmls->{$v} : "n/a";
                }
                debug_json( "HPCAnalysisRequestID $thisreqid json metadata", $meta->jmd );
            }                
        }
    }
}
