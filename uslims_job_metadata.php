<?php

{};

$self = __FILE__;
ini_set('memory_limit','2G');
$metadata_format_file = "uslims_metadata_format.json";

$notes = <<<__EOD
usage: $self {options} {db_config_file}

exports job data metadata

Options

--help                         : print this information and exit

--db dbname                    : specify the database name, can be specified multiple times
--reqid id                     : restrict results by HPCAnalysisRequest.HPCAnalysisRequestID
--reqid-range id id            : restrict to range of results by HPCAnalysisRequest.HPCAnalysisRequestID
--analysis-type                : restrict results by HPCAnalysisRequest.analType
--analysis-type-rlike          : restrict results by HPCAnalysisRequest.analType using mysql rlike syntax
--dataset-count                : restrict results by dataset count
--dataset-count-range          : restrict results by dataset count range
--list-analysis-type           : list 
--list-dataset-count           : list HPCAnalysisRequest.xml dataset count
--json                         : output full JSON
--squashed-json                : output squashed JSON    
--json-metadata                : output JSON metadata
--metadata                     : output training metadata
--metadata-format-file         : specify metadata format file (default: $metadata_format_file)
--limit                        : limit number of results per database
--list-string-variants         : list all found string metadata string_mapping variants


__EOD;


require "utility.php";
require "auc2obj.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs             = [];
$reqid               = 0;
$reqid_used          = false;
$reqid_start         = 0;
$reqid_end           = 0;
$reqid_range_used    = 0;
$analysistype        = "";
$analysistyperlike   = "";
$datasetcount        = 0;
$datasetcount_start  = 0;
$datasetcount_end    = 0;

$listanalysistype    = false;
$listdatasetcount    = false;
$json                = false;
$squashedjson        = false;
$jsonmetadata        = false;
$metadata            = false;
$limit               = 0;
$liststringvariants  = false;

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
        case "--limit": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $limit = array_shift( $u_argv );
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
        case "--metadata": {
            array_shift( $u_argv );
            $metadata = true;
            break;
        }
        case "--list-string-variants": {
            array_shift( $u_argv );
            $liststringvariants = true;
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
        case "--reqid-range": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --reqid-range requires two argument\n\n$notes" );
            }
            $reqid_start = array_shift( $u_argv );
            if ( !$reqid_start ) {
                error_exit( "--reqid-range requires non-zero values\n\n$notes" );
            }
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --reqid-range requires two argument\n\n$notes" );
            }
            $reqid_end = array_shift( $u_argv );
            if ( !$reqid_end ) {
                error_exit( "--reqid-range requires non-zero values\n\n$notes" );
            }
            $reqid_range_used = true;
            break;
        }
        case "--dataset-count-range": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --dataset-count-range requires two argument\n\n$notes" );
            }
            $datasetcount_start = array_shift( $u_argv );
            if ( !$datasetcount_start ) {
                error_exit( "--dataset-count-range requires non-zero values\n\n$notes" );
            }
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --dataset-count-range requires two argument\n\n$notes" );
            }
            $datasetcount_end = array_shift( $u_argv );
            if ( !$datasetcount_end ) {
                error_exit( "--dataset-count-range requires non-zero values\n\n$notes" );
            }
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
if ( !isset( $metadata_format->maximum_datasets ) ) {
    error_exit( "$metadata_format_file missing 'maximum_datasets' attribute" );
}
if ( !isset( $metadata_format->fields->input ) ) {
    error_exit( "$metadata_format_file missing 'fields->input' attribute" );
}
if ( !isset( $metadata_format->fields->target ) ) {
    error_exit( "$metadata_format_file missing 'fields->target' attribute" );
}

if ( !isset( $metadata_format->string_mapping ) ) {
    error_exit( "$metadata_format_file missing 'string_mapping' attribute" );
}

if (
    !$json
    && !$squashedjson
    && !$listanalysistype
    && !$listdatasetcount
    && !$jsonmetadata
    && !$metadata
    && !$liststringvariants
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
    if ( $reqid_range_used ) {
        $query .= " and HPCAnalysisRequest.HPCAnalysisRequestID>=$reqid_start and HPCAnalysisRequest.HPCAnalysisRequestID<=$reqid_end";
    }
    if ( !empty($analysistype ) ) {
        $query .= " and HPCAnalysisRequest.analType='$analysistype'";
    }
    if ( !empty($analysistyperlike ) ) {
        $query .= " and HPCAnalysisRequest.analType rlike '$analysistyperlike'";
    }

    $counts = (object)[];

    $counts->total                        = 0;
    $counts->ok                           = 0;
    $counts->analysis_failed              = 0;
    $counts->no_analysis_result           = 0;
    $counts->missing_raw_data             = 0;
    $counts->auc_decoding_error           = 0;
    $counts->missing_dataset_parameters   = 0;
    $counts->missing_edited_data          = 0;
    
    $string_variants = (object)[];
    foreach ( $metadata_format->string_mapping as $k => $v ) {
        $string_variants->{$k} = (object)[];
    }

    $hpcareqs = db_obj_result( $db_handle, $query , true, true );

    if ( $hpcareqs ) {
        while( $hpcareq = mysqli_fetch_array($hpcareqs) ) {
            $thisreqid = $hpcareq['HPCAnalysisRequestID'];

            $meta = (object)[];
            $meta->clusterName  = $hpcareq['clusterName'];
            $meta->method       = $hpcareq['method'];
            $meta->analType     = $hpcareq['analType'];
            $meta->experimentID = $hpcareq['experimentID'];
            $meta->xml          = explode( "\n", $hpcareq['requestXMLFile'] );
            $meta->xmlj         = json_decode( json_encode(simplexml_load_string( $hpcareq['requestXMLFile'] ) ) );
            $meta->xmls         = (object)squash( $meta->xmlj );
            
            if ( !is_object( $meta->xmlj ) ) {
                debug_json( "metadata non object", $meta );
                debug_json( "HPCAnalysisRequest : $thisreqid", $hpcareq );
                error_exit( "non-object xmlj");
            }

            if ( $datasetcount > 0
                 && $meta->xmlj->job->datasetCount->{'@attributes'}->value != $datasetcount ) {
                continue;
            }
            if ( $datasetcount_start > 0
                 && $datasetcount_end > 0
                 && (
                     $meta->xmlj->job->datasetCount->{'@attributes'}->value < $datasetcount_start                  
                     || $meta->xmlj->job->datasetCount->{'@attributes'}->value > $datasetcount_end
                 )
                ) {
                continue;
            }

            if ( $meta->xmlj->job->datasetCount->{'@attributes'}->value > $metadata_format->maximum_datasets ) {
                error_exit(
                    sprintf(
                        "number of datasets (%d) exceeds $metadata_format_file's maximum_datasets attribute (%d)"
                        ,$meta->xmlj->job->datasetCount->{'@attributes'}->value
                        ,$metadata_format->maximum_datasets
                    )
                    );
            }

            ## do we have an analysis result?
            $counts->total++;

            $query = "select * from ${db}.HPCAnalysisResult where HPCAnalysisResult.HPCAnalysisRequestID=$thisreqid";
            $hpcaress = db_obj_result( $db_handle, $query , true, true );

            if ( !$hpcaress ) {
                ## no analysis results, skipping
                # echo "HPCAnalysisRequest $thisreqid has no HPCAnalysisResult\n";
                $counts->no_analysis_result++;
                continue;
            }

            $meta->jmd         = (object)[];
            $meta->jmd->input  = (object)[];
            $meta->jmd->target = (object)[];

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
                    $counts->analysis_failed++;
                    break;
                }
                # echo "HPCAnalysisRequest $thisreqid HPCAnalysisResult $thisresid queueStatus '" . $hpcares['queueStatus'] . "'\n";

                ## additional inputs go to xmls
                $meta->xmls->CPUCount = $hpcares['CPUCount'];

                $meta->jmd->target->wallTime = $hpcares['wallTime'];
                $meta->jmd->target->CPUTime  = $hpcares['CPUTime'];
                $meta->jmd->target->max_rss  = $hpcares['max_rss'];

                # debug_json( "HPCAnalysisRequest $thisreqid HPCAnalysisResult $thisresid", $hpcares );
            }

            if ( $skip ) {
                continue;
            }

            ## collect dataset parameters

            $meta->datasets = (object)[];
            $meta->datasets->edited_radial_points = [];
            $meta->datasets->edited_data_points   = [];

            # echo "HPCAnalysisRequestID $thisreqid checking dataset\n";
            # debug_json( "--> xmlj", $meta->xmlj );
            # debug_json( "--> xmlj->dataset", $meta->xmlj->dataset );
            # error_exit( "testing" );

            foreach ( $meta->xmlj->dataset as $dataset ) {
                if ( $meta->xmlj->job->datasetCount->{'@attributes'}->value == 1 ) {
                    $dataset = $meta->xmlj->dataset;
                }
                @$file  = $dataset->files->auc->{'@attributes'}->filename;
                @$edit  = $dataset->files->edit->{'@attributes'}->filename;
                @$expID = $dataset->parameters->speedstep->{'@attributes'}->expID;
                # echo "file $file expID $expID edit $edit (HPCAnalysisRequest experimentID $meta->experimentID)\n";
                if ( !isset( $file ) || !isset($expID) || !isset($edit) ) {
                    ## error_exit( "HPCAnalysisRequestID $thisreqid missing expected dataset file & expID & edit" );
                    $counts->missing_dataset_parameters++;
                    $skip = true;
                    break;
                }
                $query = "select rawDataID, data from ${db}.editedData where filename='$edit' order by lastUpdated desc limit 1";
                $editeddata = db_obj_result( $db_handle, $query, false, true );

                if ( !$editeddata ) {
                    # error_exit( "HPCAnalysisRequestID $thisreqid missing expected editedData for edit='$edit'" );
                    $counts->missing_edited_data++;
                    $skip = true;
                    break;
                }

                $editeddata->xmlj = json_decode( json_encode(simplexml_load_string( $editeddata->data ) ) );
                $editeddata->xmls = (object)squash( $editeddata->xmlj );

                # debug_json( "edit xmlj", $editeddata->xmlj );
                # debug_json( "edit xmls", $editeddata->xmls );

                $query = "select data from ${db}.rawData where rawDataID=$editeddata->rawDataID";
                # echo "$query\n";
                $rawdata = db_obj_result( $db_handle, $query, false, true );

                if ( !$rawdata ) {
                    # error_exit( "HPCAnalysisRequestID $thisreqid missing expected rawData for rawDataID=$editeddata->rawDataID" );
                    $skip = true;
                    break;
                }

                $datastats = auc2obj( $rawdata->data );

                if (
                    !isset( $datastats->radius_delta )
                    || $datastats->radius_delta <= 0
                    || !isset( $datastats->scans )
                    || $datastats->scans <= 0
                    ) {
                    # error_exit( "HPCAnalysisRequestID $thisreqid insufficient results decoding auc longblob" );
                    $counts->auc_decoding_error++;
                    $skip = true;
                    break;
                }
                
                if (
                    isset( $editeddata->xmlj->run )
                    && isset( $editeddata->xmlj->run->excludes )
                    && isset( $editeddata->xmlj->run->excludes->exclude )
                    && is_array( $editeddata->xmlj->run->excludes->exclude )
                    ) {
                    $datastats->edited_scans         = $datastats->scans - count( $editeddata->xmlj->run->excludes->exclude );
                } else {
                    $datastats->edited_scans         = $datastats->scans;
                }
                
                if (
                    isset( $editeddata->xmlj->run )
                    && isset( $editeddata->xmlj->run->parameters )
                    && isset( $editeddata->xmlj->run->parameters->data_range )
                    && isset( $editeddata->xmlj->run->parameters->data_range->{'@attributes'} )
                    && isset( $editeddata->xmlj->run->parameters->data_range->{'@attributes'}->right )
                    && isset( $editeddata->xmlj->run->parameters->data_range->{'@attributes'}->left )
                    ) {
                    $datastats->edited_radial_points =
                        floor(
                            ( $editeddata->xmlj->run->parameters->data_range->{'@attributes'}->right
                              - $editeddata->xmlj->run->parameters->data_range->{'@attributes'}->left )
                            / $datastats->radius_delta
                        )
                        ;
                } else {
                    $datastats->edited_radial_points = $dataset->radial_points;
                }
                
                $datastats->edited_data_points = $datastats->edited_scans * $datastats->edited_radial_points;

                $meta->datasets->edited_radial_points[] = $datastats->edited_radial_points;
                $meta->datasets->edited_scans[]         = $datastats->edited_scans;

                # debug_json( "auc2obj", $datastats );

                if ( $meta->xmlj->job->datasetCount->{'@attributes'}->value == 1 ) {
                    break;
                }
            }

            if ( $skip ) {
                continue;
            }

            while( count( $meta->datasets->edited_scans ) < $metadata_format->maximum_datasets ) {
                $meta->datasets->edited_radial_points[] = 0;
                $meta->datasets->edited_scans[]         = 0;
            }

            if ( $skip ) {
                echo "HPCAnalysisRequestID $thisreqid NOT ok\n";
                $counts->missing_raw_data++;
                continue;
            }

            $counts->ok++;

            # debug_json( "meta datasets", $meta->datasets );
            
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

            if ( $liststringvariants ) {
                foreach ( $metadata_format->string_mapping as $k => $v ) {
                    if ( isset( $meta->xmls->{$k} ) ) {
                        $string_variants->{$k}->{$meta->xmls->{$k}} = true;
                    }
                }
            }
            
            if ( $jsonmetadata ) {
                foreach ( $metadata_format->fields->input as $v ) {
                    $meta->jmd->input->{$v} =
                        isset( $meta->xmls->{$v} )
                        ? (
                            is_numeric( $meta->xmls->{$v} )
                            ? floatval( $meta->xmls->{$v} )
                            : $meta->xmls->{$v}
                        )
                        : "n/a"
                        ;
                    
                }
                foreach ( $metadata_format->fields->target as $v ) {
                    $meta->jmd->target->{$v} =
                        isset( $meta->jmd->target->{$v} )
                        ? (
                            is_numeric( $meta->jmd->target->{$v} )
                            ? floatval( $meta->jmd->target->{$v} )
                            : $meta->jmd->target->{$v} 
                        )
                        : "n/a"
                        ;
                }
                ## merge scan data
                $meta->jmd->input = (object) array_merge( (array) $meta->jmd->input, (array) squash( $meta->datasets ) );
                debug_json( "HPCAnalysisRequestID $thisreqid json metadata input", $meta->jmd->input );
                debug_json( "HPCAnalysisRequestID $thisreqid json metadata target", $meta->jmd->target );
            }                

            if ( $metadata ) {
                error_exit( "--metadata : to do" );
            }

            if ( $limit && $counts->ok >= $limit ) {
                break;
            }
        }
    }
    if ( $counts->total ) {
        $counts->percent_ok = floatval( sprintf( "%.2f", 100 * $counts->ok / $counts->total ) );
    }
    debug_json( "counts", $counts );
    if ( $liststringvariants ) {
        debug_json( "string variants", $string_variants );
    }
}
