<?php

{};

$self = $argv[0];
ini_set('memory_limit','2G');
$metadata_format_file = __DIR__ . "/uslims_metadata_format.json";

$notes = <<<__EOD
usage: $self {options} {db_config_file}

exports job data metadata

Options

--help                         : print this information and exit

--db dbname                    : specify the database name, can be specified multiple times
--db-exclude dbname            : exclude a db, can be specified multiple times
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
--metadata-output-directory    : specify metadata output directory, default is defined in the metadata-format-file
--metadata-csv filename        : produce metadata in a single csv, suitable for python pandas load_csv, implies --metadata
--python-pp-code               : generate python performance prediction code suitable for docker:tensorflow/tensorflow


__EOD;


require __DIR__ . "/utility.php";
require __DIR__ . "/auc2obj.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs                  = [];
$exclude_dbs              = [];
$reqid                    = 0;
$reqid_used               = false;
$reqid_start              = 0;
$reqid_end                = 0;
$reqid_range_used         = 0;
$analysistype             = "";
$analysistyperlike        = "";
$datasetcount             = 0;
$datasetcount_start       = 0;
$datasetcount_end         = 0;

$listanalysistype         = false;
$listdatasetcount         = false;
$json                     = false;
$squashedjson             = false;
$jsonmetadata             = false;
$metadata                 = false;
$limit                    = 0;
$liststringvariants       = false;
$metadataoutputdirectory  = "";
$metadatacsv              = "";
$pythonppcode             = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
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
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--db-exclude": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $exclude_dbs[] = array_shift( $u_argv );
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
        case "--metadata-output-directory": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $metadataoutputdirectory = array_shift( $u_argv );
            break;
        }
        case "--metadata-csv": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $metadatacsv = array_shift( $u_argv );
            $metadata    = true;
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
        case "--python-pp-code": {
            array_shift( $u_argv );
            $pythonppcode = true;
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

cp {$config_file}.template $use_config_file
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

if (!is_object($metadata_format)) {
    error_exit("Invalid metadata formatter JSON: " . json_last_error_msg());
}

if ( !empty( $metadataoutputdirectory ) ) {
    $metadata_format->output_dir = $metadataoutputdirectory;
}

## echo_json( "$metadata_format_file" , $metadata_format );
if ( !isset( $metadata_format->version ) ) {
    error_exit( "$metadata_format_file missing 'version' attribute" );
}
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
if ( !isset( $metadata_format->output_dir) ) {
    error_exit( "$metadata_format_file missing 'output_dir' attribute" );
}
if ( !isset( $metadata_format->filename_format) ) {
    error_exit( "$metadata_format_file missing 'filename_format' attribute" );
}
if ( !isset( $metadata_format->filename_format->base) ) {
    error_exit( "$metadata_format_file missing 'filename_format->base' attribute" );
}
if ( !isset( $metadata_format->filename_format->extension) ) {
    error_exit( "$metadata_format_file missing 'filename_format->extension' attribute" );
}
if ( !isset( $metadata_format->filename_format->extension->input) ) {
    error_exit( "$metadata_format_file missing 'filename_format->extension->input' attribute" );
}
if ( !isset( $metadata_format->filename_format->extension->target) ) {
    error_exit( "$metadata_format_file missing 'filename_format->extension->target' attribute" );
}
if ( !isset( $metadata_format->filename_format->description) ) {
    error_exit( "$metadata_format_file missing 'filename_format->description' attribute" );
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

if ( $pythonppcode && !$metadata ) {
    error_exit( "--python-pp-code requires --metadata" );
}

function usmd_attr( $node, $name ) {
    if ( $node === null ) {
        return null;
    }
    $attrs = $node->attributes();
    return isset( $attrs[$name] ) ? trim( (string)$attrs[$name] ) : null;
}

function usmd_param( $job_parameters, $name, $type = "string" ) {
    if ( $job_parameters === null || !isset( $job_parameters->{$name} ) ) {
        return null;
    }
    $raw = usmd_attr( $job_parameters->{$name}, "value" );
    if ( $raw === null || $raw === "" ) {
        return null;
    }
    if ( $type === "int" ) {
        return filter_var( $raw, FILTER_VALIDATE_INT ) !== false ? intval( $raw ) : null;
    }
    if ( $type === "float" ) {
        return is_numeric( $raw ) && is_finite( floatval( $raw ) ) ? floatval( $raw ) : null;
    }
    return $raw;
}

function usmd_dataset_nodes( $xml ) {
    if ( !isset( $xml->dataset ) ) {
        return [];
    }
    $out = [];
    foreach ( $xml->dataset as $dataset ) {
        $out[] = $dataset;
    }
    return $out;
}

function usmd_bucket_rows( $jp ) {
    $rows = [];
    if ( $jp === null || !isset( $jp->bucket ) ) {
        return $rows;
    }
    foreach ( $jp->bucket as $bucket ) {
        $rows[] = [
            "x_min" => is_numeric( usmd_attr( $bucket, "x_min" ) ) ? floatval( usmd_attr( $bucket, "x_min" ) ) : null,
            "x_max" => is_numeric( usmd_attr( $bucket, "x_max" ) ) ? floatval( usmd_attr( $bucket, "x_max" ) ) : null,
            "y_min" => is_numeric( usmd_attr( $bucket, "y_min" ) ) ? floatval( usmd_attr( $bucket, "y_min" ) ) : null,
            "y_max" => is_numeric( usmd_attr( $bucket, "y_max" ) ) ? floatval( usmd_attr( $bucket, "y_max" ) ) : null
        ];
    }
    return $rows;
}

function usmd_parse_model( $xml_text, $kind ) {
    if ( $xml_text === null || trim( $xml_text ) === "" ) {
        return [ "status" => "missing", "reason" => "model XML unavailable" ];
    }
    $xml = @simplexml_load_string( $xml_text );
    if ( $xml === false || !isset( $xml->model ) ) {
        return [ "status" => "invalid", "reason" => "model XML is not parseable ModelData" ];
    }
    $model = $xml->model;
    $analytes = count( $model->analyte );
    if ($analytes < 1) {
        return ["status"=>"invalid","reason"=>"model has no components"];
    }
    $associations = count( $model->association );
    if ( $kind === "cg" ) {
        $subgrids = usmd_attr( $model, "subGrids" );
        $subgrids = filter_var( $subgrids, FILTER_VALIDATE_INT ) !== false ? intval( $subgrids ) : null;
        if ( $analytes < 1 || $subgrids === null || $subgrids < 1 ) {
            return [ "status" => "invalid", "reason" => "CG model needs analytes and positive subGrids" ];
        }
        return [ "status" => "available", "component_count" => $analytes, "declared_subgrids" => $subgrids ];
    }
    /* Mirror US_dmGA_Constraints::constraints_from_model(). A 000V_ analyte is
       one fixed base component; otherwise L/H records form one base component.
       F counts the selected X/Y/Z/concentration values whose bounds differ.
       Associations are always low/high pairs and may float K_d and/or k_off. */
    $component_nodes=[]; foreach($model->analyte as $node) {
        $component_nodes[]=$node;
    }
    $components=0;$floating=0;
    for($i=0;$i<count($component_nodes);$i++) {
        $low=$component_nodes[$i];$name=usmd_attr($low,"name")??"";$flag=substr($name,0,5);
        if(strpos($flag,"V")!==false) {
            $components++;continue;
        }
        if(strpos($flag,"L")===false||!isset($component_nodes[$i+1])) {
            return ["status"=>"invalid","reason"=>"DMGA component constraint pair is malformed"];
        }
        $high=$component_nodes[++$i];$high_name=usmd_attr($high,"name")??"";
        if(strpos(substr($high_name,0,5),"H")===false||substr($name,5)!==substr($high_name,5)) {
            return ["status"=>"invalid","reason"=>"DMGA component low/high names do not pair"];
        }
        $lv=usmd_dmga_component_values($low);$hv=usmd_dmga_component_values($high);
        foreach(["x","y","z","concentration"] as $key) {
            if($lv[$key]!=$hv[$key]) {
                $floating++;
            }
        }
        $components++;
    }
    $association_nodes=[];foreach($model->association as $node) {
        $association_nodes[]=$node;
    }
    if(count($association_nodes)%2) {
        return ["status"=>"invalid","reason"=>"DMGA association constraint pair is malformed"];
    }
    $base_associations=intdiv(count($association_nodes),2);
    for($i=0;$i<count($association_nodes);$i+=2) {
        foreach(["K_d","k_off"] as $key) {
            if(floatval(usmd_attr($association_nodes[$i],$key))!=floatval(usmd_attr($association_nodes[$i+1],$key))) {
                $floating++;
            }
        }
    }
    return [
        "status" => "available",
        "floating_constraints" => $floating,
        "base_components" => $components,
        "base_associations" => $base_associations
    ];
}

function usmd_dmga_component_values( $node ) {
    $values=[];foreach(["s","f_f0","mw","D","f","vbar20"] as $name) {
        $values[$name]=floatval(usmd_attr($node,$name));
    }
    $ordered=["s","f_f0","mw","D","f","vbar20"];$selected=[];
    foreach($ordered as $name) {
        if($values[$name]!=0.0) {
            $selected[]=$values[$name];
        }
    }
    return ["x"=>$selected[0]??0.0,"y"=>$selected[1]??0.0,"z"=>$selected[2]??0.0,"concentration"=>floatval(usmd_attr($node,"signal"))];
}

function usmd_parse_wall_seconds( $value ) {
    if ( preg_match( '/^(\d+):(\d{1,2}):(\d{1,2})$/', trim( $value ), $m ) ) {
        return intval( $m[1] ) * 3600 + intval( $m[2] ) * 60 + intval( $m[3] );
    }
    return null;
}

function usmd_parse_jobfile( $jobfile ) {
    $out = [
        "parser_version" => "jobfile-resources-1.0",
        "parser_status" => "missing",
        "scheduler_family" => null,
        "requested_nodes" => null,
        "requested_ranks" => null,
        "requested_cores" => null,
        "requested_memory_per_core" => null,
        "requested_memory_total" => null,
        "requested_wall_limit" => null,
        "units" => [ "requested_memory_per_core"=>"bytes/core", "requested_memory_total"=>"bytes", "requested_wall_limit"=>"seconds" ],
        "evidence" => []
    ];
    if ( $jobfile === null || trim( $jobfile ) === "" ) {
        return $out;
    }
    $out["parser_status"] = "unsupported-format";
    if ( preg_match( '/^#PBS\s+-l\s+[^\r\n]*nodes=(\d+):ppn=(\d+)/mi', $jobfile, $m ) ) {
        $out["scheduler_family"] = "PBS";
        $out["requested_nodes"] = intval( $m[1] );
        /* ppn is placement capacity, not proof that ranks equal cores. */
        $out["evidence"]["pbs_ppn"] = intval( $m[2] );
        $out["parser_status"] = "partial";
    }
    if ( preg_match( '/^#PBS\s+-l\s+[^\r\n]*walltime=([0-9:]+)/mi', $jobfile, $m ) ) {
        $out["requested_wall_limit"] = usmd_parse_wall_seconds( $m[1] );
        $out["parser_status"] = "partial";
    }
    if ( preg_match( '/^#SBATCH\s+(?:--nodes(?:=|\s+)|-N\s*)(\d+)/m', $jobfile, $m ) ) {
        $out["scheduler_family"] = "Slurm";
        $out["requested_nodes"] = intval( $m[1] );
        $out["parser_status"] = "partial";
    }
    if ( preg_match( '/^#SBATCH\s+(?:--ntasks(?:=|\s+)|-n\s*)(\d+)/m', $jobfile, $m ) ) {
        $out["scheduler_family"] = "Slurm";
        $out["requested_ranks"] = intval( $m[1] );
        $out["parser_status"] = "partial";
    }
    if ( preg_match( '/\b(?:mpirun|mpiexec|ibrun)\b[^\r\n]*(?:\s-np\s+|\s-n\s+)(\d+)/i', $jobfile, $m ) ) {
        $out["requested_ranks"] = intval( $m[1] );
        $out["parser_status"] = "partial";
    }
    if ( preg_match( '/\bus_mpi_analysis\b[^\r\n]*\s-walltime\s+(\d+)/i', $jobfile, $m ) ) {
        /* The application consumes this value as minutes (max_walltime). */
        $out["evidence"]["application_wall_minutes"] = intval( $m[1] );
        $application_seconds = intval( $m[1] ) * 60;
        if ( $out["requested_wall_limit"] === null ) {
            $out["requested_wall_limit"] = $application_seconds;
        }
        elseif ( $out["requested_wall_limit"] !== $application_seconds ) {
            $out["evidence"]["wall_limit_conflict"] = [ "scheduler_seconds"=>$out["requested_wall_limit"], "application_seconds"=>$application_seconds ];
        }
        $out["parser_status"] = "partial";
    }
    // Preserve explicit Slurm allocation directives; do not infer cores from ranks.
    if (preg_match('/^#SBATCH\s+(?:--cpus-per-task(?:=|\s+)|-c\s*)(\d+)/m', $jobfile, $m)) {
        $out["evidence"]["cpus_per_task"] = intval($m[1]);
        if ($out["requested_ranks"] !== null) {
            $out["requested_cores"] = $out["requested_ranks"] * intval($m[1]);
        }
    }
    foreach (["--mem-per-cpu"=>"requested_memory_per_core", "--mem"=>"requested_memory_total"] as $option=>$field) {
        if (preg_match('/^#SBATCH\s+'.preg_quote($option,'/').'(?:=|\s+)(\d+)([KMGT]?)(?:\s|$)/mi', $jobfile, $m)) {
            $unit = strtoupper($m[2] ?: 'M');
            $factor = ['K'=>1024, 'M'=>1048576, 'G'=>1073741824, 'T'=>1099511627776][$unit];
            // Slurm's zero means all available memory, not a zero-byte request.
            if (intval($m[1]) > 0) {
                $out[$field] = intval($m[1]) * $factor;
            }
            else {
                $out["evidence"][$field] = 'all-available';
            }
            $out["scheduler_family"] = 'Slurm';
        }
    }
    if (preg_match('/^#SBATCH\s+(?:--time(?:=|\s+)|-t\s*)([0-9:-]+)/m', $jobfile, $m)) {
        $parts = explode('-', $m[1]); $days = count($parts)===2 ? intval(array_shift($parts)) : 0;
        $clock = explode(':', $parts[0]);
        if (strpos($m[1], '-') !== false) {
            $seconds = $days*86400 + intval($clock[0])*3600 + intval($clock[1]??0)*60 + intval($clock[2]??0);
        } elseif (count($clock)===3) {
            $seconds = intval($clock[0])*3600 + intval($clock[1])*60 + intval($clock[2]);
        } else {
            $seconds = intval($clock[0])*60 + intval($clock[1]??0);
        }
        if ($out["requested_wall_limit"]!==null && $out["requested_wall_limit"]!==$seconds) {
            $out["evidence"]["wall_limit_conflict"] = ["scheduler_seconds"=>$seconds,"application_seconds"=>$out["requested_wall_limit"]];
        }
        $out["requested_wall_limit"] = $seconds > 0 ? $seconds : null;
        $out["scheduler_family"] = 'Slurm';
    }
    if ( $out["requested_nodes"] !== null || $out["requested_ranks"] !== null || $out["requested_wall_limit"] !== null ) {
        $out["parser_status"] = "parsed";
    }
    return $out;
}

function usmd_sql_string( $value ) {
    global $db_handle; return "'".mysqli_real_escape_string($db_handle,$value)."'";
}
function usmd_request_xml( $text, &$normalization ) {
    $normalization=null;
    $xml=@simplexml_load_string($text);
    if($xml!==false) {
        return $xml;
    }
    // Newer archived rows contain a JSON-escaped XML string without the outer
    // JSON quotes. Decode that representation only after ordinary XML fails.
    if(is_string($text)&&0===strpos($text,'<?xml version=\\"')) {
        $decoded=json_decode('"'.$text.'"');
        if(is_string($decoded)) {
            $xml=@simplexml_load_string($decoded);
            if($xml!==false) {
                $normalization="json-string-unescape";return $xml;
            }
        }
    }
    return false;
}
function usmd_model_from_db( $db, $node, $kind, &$cache ) {
    global $db_handle;
    if($node===null) {
        return ["status"=>"missing","reason"=>strtoupper($kind)." model reference missing"];
    }
    $id=usmd_attr($node,"id");$filename=usmd_attr($node,"filename");
    if(filter_var($id,FILTER_VALIDATE_INT)===false) {
        return ["status"=>"missing","model_id"=>$id,"filename"=>$filename,"reason"=>"model ID unavailable"];
    }
    $key="$db/$kind/".intval($id);if(isset($cache[$key])) {
        return $cache[$key];
    }
    $row=db_obj_result($db_handle,"select modelID,description,xml from {$db}.model where modelID=".intval($id)." limit 1",false,true);
    if(!$row) {
        return $cache[$key]=["status"=>"missing","model_id"=>intval($id),"filename"=>$filename,"reason"=>"model row unavailable"];
    }
    $parsed=usmd_parse_model($row->xml??null,$kind);$parsed["model_id"]=intval($id);$parsed["filename"]=$filename;$parsed["description"]=$row->description??null;
    return $cache[$key]=$parsed;
}
function usmd_speedsteps( $db ) {
    global $db_handle;$map=[];
    $rows=db_obj_result($db_handle,"select experimentID,rotorspeed,durationhrs,durationmins from {$db}.speedstep",true,true);
    if($rows) {
        while($row=mysqli_fetch_assoc($rows)) {
            $map[intval($row["experimentID"])][]=["rotor_speed_rpm"=>is_numeric($row["rotorspeed"])?intval($row["rotorspeed"]):null,"duration_seconds"=>is_numeric($row["durationhrs"])&&is_numeric($row["durationmins"])?intval(round((floatval($row["durationhrs"])*60+floatval($row["durationmins"]))*60)):null];
        }
    }
    return $map;
}
function usmd_dataset_features( $db, $xml, $speedsteps, &$issues ) {
    global $db_handle;
    $out=["scan_count_by_dataset"=>[],"point_count_by_dataset"=>[],"simulation_points_by_dataset"=>[],"radial_grid_type_by_dataset"=>[],"time_grid_type_by_dataset"=>[],"meniscus_radius_cm_by_dataset"=>[],"bottom_radius_cm_by_dataset"=>[],"speed_profile_by_dataset"=>[]];
    foreach(usmd_dataset_nodes($xml) as $index=>$dataset) {

        foreach(["simpoints"=>"simulation_points_by_dataset","radial_grid"=>"radial_grid_type_by_dataset","time_grid"=>"time_grid_type_by_dataset"] as $xmlname=>$outname) {
            $v=isset($dataset->parameters->{$xmlname})?usmd_attr($dataset->parameters->{$xmlname},"value"):null;$out[$outname][]=filter_var($v,FILTER_VALIDATE_INT)!==false?intval($v):null;
        }
        $expid=null;if(isset($dataset->parameters->speedstep)) {
            foreach($dataset->parameters->speedstep as $sn) {
                $v=usmd_attr($sn,"expID");if(filter_var($v,FILTER_VALIDATE_INT)!==false) {
                    $expid=intval($v);break;
                }
            }
        }
        $out["speed_profile_by_dataset"][]=$expid!==null&&isset($speedsteps[$expid])?$speedsteps[$expid]:null;
        $edit=isset($dataset->files->edit)?usmd_attr($dataset->files->edit,"filename"):null;
        if($edit===null) {
            $issues[]=["dataset_index"=>$index,"code"=>"missing-edit-filename"];$out["scan_count_by_dataset"][]=null;$out["point_count_by_dataset"][]=null;$out["meniscus_radius_cm_by_dataset"][]=null;$out["bottom_radius_cm_by_dataset"][]=null;continue;
        }
        $edited=db_obj_result($db_handle,"select rawDataID,data from {$db}.editedData where filename=".usmd_sql_string($edit)." order by lastUpdated desc limit 1",false,true);
        if(!$edited||($editxml=@simplexml_load_string($edited->data))===false) {
            $issues[]=["dataset_index"=>$index,"code"=>"missing-or-invalid-edited-data"];$out["scan_count_by_dataset"][]=null;$out["point_count_by_dataset"][]=null;$out["meniscus_radius_cm_by_dataset"][]=null;$out["bottom_radius_cm_by_dataset"][]=null;continue;
        }
        $men=isset($editxml->run->parameters->meniscus)?usmd_attr($editxml->run->parameters->meniscus,"radius"):null;$bot=isset($editxml->run->parameters->bottom)?usmd_attr($editxml->run->parameters->bottom,"radius"):null;
        $out["meniscus_radius_cm_by_dataset"][]=is_numeric($men)?floatval($men):null;$out["bottom_radius_cm_by_dataset"][]=is_numeric($bot)?floatval($bot):null;
        $rawrow=db_obj_result($db_handle,"select data from {$db}.rawData where rawDataID=".intval($edited->rawDataID),false,true);
        if(!$rawrow) {
            $issues[]=["dataset_index"=>$index,"code"=>"missing-raw-data"];$out["scan_count_by_dataset"][]=null;$out["point_count_by_dataset"][]=null;continue;
        }

        $auc=auc2obj($rawrow->data);$excluded=0;if(isset($editxml->run->excludes->exclude)) {
            foreach($editxml->run->excludes->exclude as $_) {
                $excluded++;
            }
        }
        $left=isset($editxml->run->parameters->data_range)?usmd_attr($editxml->run->parameters->data_range,"left"):null;$right=isset($editxml->run->parameters->data_range)?usmd_attr($editxml->run->parameters->data_range,"right"):null;
        $scans=isset($auc->scans)?$auc->scans-$excluded:null;$points=is_numeric($left)&&is_numeric($right)&&isset($auc->radius_delta)&&$auc->radius_delta>0?intval(floor((floatval($right)-floatval($left))/$auc->radius_delta)):null;
        $out["scan_count_by_dataset"][]=$scans>0?$scans:null;$out["point_count_by_dataset"][]=$points>0?$points:null;
    }
    return $out;
}
// Family classification is shared by every method, before numeric formatting.
function csv_method_family($label) {
    $label = strtoupper(trim((string)$label));
    $label = str_replace('2DSA_CG','2DSA-CG',$label);
    if (preg_match('/^2DSA-CG(?:-(?:FB|FM|FMB|IT|MC|GL))*$/',$label)) {
        return '2DSA-CG';
    }
    if (preg_match('/^2DSA(?:-(?:FB|FM|FMB|IT|MC|GL))*$/',$label)) {
        return '2DSA';
    }
    if (preg_match('/^DMGA(?:-MC)*$/',$label)) {
        return 'DMGA';
    }
    if (preg_match('/^GA(?:-(?:MC|GL))*$/',$label)) {
        return 'GA';
    }
    if (preg_match('/^PCSA(?:-(?:SL|IS|DS|HL|2O|ALL|GL|TR|MC))*$/',$label)) {
        return 'PCSA';
    }
    return null;
}
function csv_research_method($root, $db_label, $xml_label, $has_cg, $has_dc, $valid_xml) {
    if (!$valid_xml) {
        return [null,'invalid-request-xml'];
    }
    $families=[];
    foreach ([$root,$db_label,$xml_label] as $label) {
        if ($label===null || trim((string)$label)==='') {
            continue;
        }
        $family=csv_method_family($label);
        if ($family===null) {
            return [null,'unrecognized-label'];
        }
        $families[]=$family;
    }
    if (!$families) {
        return [null,'missing-label'];
    }
    $groups=array_unique(array_map(function($f) { return $f==='2DSA-CG'?'2DSA':$f; },$families));
    if (count($groups)!==1) {
        return [null,'conflicting-method-labels'];
    }
    $family=reset($groups);
    if (($has_cg && $family!=='2DSA') || ($has_dc && $family!=='DMGA')) {
        return [null,'conflicting-model-reference'];
    }
    if ($family==='2DSA') {
        if ($has_cg || in_array('2DSA-CG',$families,true)) {
            return ['2DSA-CG',$has_cg?'classified':'missing-cg-reference'];
        }
        return ['2DSA','classified'];
    }
    return [$family,($family==='DMGA' && !$has_dc)?'missing-dc-reference':'classified'];
}

function csv_number($value) {
    if (is_string($value)) {
        $value = str_replace(',', '.', trim($value));
    }
    return is_numeric($value) && is_finite((float)$value) ? (float)$value : null;
}
function csv_timestamp($value) {
    if (!$value) {
        return null;
    }
    $time = strtotime($value);
    return $time === false ? null : $time;
}
function csv_write($path, $data) {
    if (file_put_contents($path, $data) === false) {
        throw new RuntimeException("Cannot write $path");
    }
}

$input_format = $metadata_format->fields->input;
$dataset_fields = $metadata_format->dataset_fields ?? ['edited_scans','edited_radial_points','simpoints'];
foreach ($dataset_fields as $field) {
    for ($i = 0; $i < $metadata_format->maximum_datasets; ++$i) {
        $input_format[] = "$field.$i";
    }
}
sort($input_format, SORT_NATURAL);
$target_format = $metadata_format->fields->target;
sort($target_format, SORT_NATURAL);
$missing_value = $metadata_format->missing_value ?? -1;
$columns = array_merge($input_format, $target_format);
if (count($columns) !== count(array_unique($columns))) {
    throw new RuntimeException('Duplicate CSV columns');
}
$pppcolnames = "    '" . implode("',\n    '", array_merge($input_format,$target_format)) . "'\n";
$csv_handle = null;
if ($metadata) {
    if (!is_dir($metadata_format->output_dir)) {
        throw new RuntimeException('Create metadata output directory first');
    }
    $description = $metadata_format->output_dir . '/' . str_replace('__version__',$metadata_format->version,$metadata_format->filename_format->description);
    csv_write($description.'.input',json_encode($input_format,JSON_PRETTY_PRINT)."\n");
    csv_write($description.'.target',json_encode($target_format,JSON_PRETTY_PRINT)."\n");
    csv_write($description.'_metadata_format.json',json_encode($metadata_format,JSON_PRETTY_PRINT)."\n");
    if ($metadatacsv) {
        $csv_handle = fopen($metadata_format->output_dir.'/'.$metadatacsv,'wb');
        if (!$csv_handle || fwrite($csv_handle,implode(' ',$columns)."\n") === false) {
            throw new RuntimeException('Cannot write CSV header');
        }
    }
}
open_db();
$existing = existing_dbs();
if (!$use_dbs) {
    $use_dbs = $existing;
}
if (array_diff($use_dbs,$existing)) {
    throw new RuntimeException('Requested database not found');
}
$string_variants = [];
$model_cache = [];
$global_counts = ['requests'=>0,'records'=>0,'invalid_xml'=>0,'filtered_dataset_count'=>0,'missing_results'=>0,'incomplete_results'=>0];
foreach ($use_dbs as $db) {
    if (in_array($db,$exclude_dbs,true)) {
        continue;
    }
    $query = "select * from {$db}.HPCAnalysisRequest where 1=1";
    if ($reqid_used) {
        $query .= ' and HPCAnalysisRequestID='.intval($reqid);
    }
    if ($reqid_range_used) {
        $query .= ' and HPCAnalysisRequestID between '.intval($reqid_start).' and '.intval($reqid_end);
    }
    if ($analysistype !== '') {
        $query .= ' and analType='.usmd_sql_string($analysistype);
    }
    if ($analysistyperlike !== '') {
        $query .= ' and analType rlike '.usmd_sql_string($analysistyperlike);
    }
    $query .= ' order by HPCAnalysisRequestID';
    if ($limit) {
        $query .= ' limit '.intval($limit);
    }
    $requests = db_obj_result($db_handle,$query,true,true);
    if (!$requests) {
        continue;
    }
    $speedsteps = usmd_speedsteps($db);
    while ($request = mysqli_fetch_assoc($requests)) {
        ++$global_counts['requests'];
        $thisreqid = intval($request['HPCAnalysisRequestID']);
        $normalization = null;
        $xml = usmd_request_xml((string)($request['requestXMLFile'] ?? ''),$normalization);
        $valid_xml = $xml !== false;
        if (!$valid_xml) {
            ++$global_counts['invalid_xml']; $xml = simplexml_load_string('<request/>');
        }
        $flat = (array)squash(json_decode(json_encode($xml)));
        $declared = csv_number($flat['job.datasetCount.@attributes.value'] ?? null);
        $serialized = count(usmd_dataset_nodes($xml));
        // An unknown count remains auditable instead of disappearing from coverage.
        if ($declared !== null && (($datasetcount && $declared != $datasetcount)
            || ($datasetcount_start && $declared < $datasetcount_start)
            || ($datasetcount_end && $declared > $datasetcount_end))) {
            ++$global_counts['filtered_dataset_count']; continue;
        }
        if ($serialized > $metadata_format->maximum_datasets) {
            throw new RuntimeException("$db request $thisreqid has $serialized datasets: select the full formatter or increase maximum_datasets; refusing to truncate scientific fields");
        }
        $jp = $xml->job->jobParameters ?? null;
        $cg_node = $jp !== null && isset($jp->CG_model) ? $jp->CG_model : null;
        $dc_node = $jp !== null && isset($jp->DC_model) ? $jp->DC_model : null;
        $cg = usmd_model_from_db($db,$cg_node,'cg',$model_cache);
        $dmga = usmd_model_from_db($db,$dc_node,'dmga',$model_cache);
        $issues = [];
        if (!$valid_xml) {
            $issues[] = ['code'=>'missing-or-invalid-request-xml'];
        }
        if ($declared === null || $declared != $serialized) {
            $issues[] = ['code'=>'dataset-count-mismatch','declared'=>$declared,'serialized'=>$serialized];
        }
        $datasets = usmd_dataset_features($db,$xml,$speedsteps,$issues);
        $base = $flat;
        $classification_status='legacy-mapping';
        if (($metadata_format->method_classification??null)==='research-v1') {
            [$research_method,$classification_status]=csv_research_method(
                usmd_attr($xml,'method'),$request['analType']??null,
                isset($xml->job->analysis_type)?usmd_attr($xml->job->analysis_type,'value'):null,
                $cg_node!==null,$dc_node!==null,$valid_xml);
            $base['@attributes.method']=$research_method;
        }
        $base['analysis_variant']=$request['analType']??(isset($xml->job->analysis_type)?usmd_attr($xml->job->analysis_type,'value'):null);
        $base['serialized_dataset_count'] = $serialized;
        $base['request_xml_valid'] = (int)$valid_xml;
        $base['bucket_count'] = $valid_xml ? count(usmd_bucket_rows($jp)) : null;
        foreach (['cg_component_count'=>[$cg,'component_count'],'cg_declared_subgrids'=>[$cg,'declared_subgrids'],
                  'dmga_floating_constraints'=>[$dmga,'floating_constraints'],'dmga_base_components'=>[$dmga,'base_components'],
                  'dmga_base_associations'=>[$dmga,'base_associations']] as $name=>$source) {
            $base[$name]=$source[0][$source[1]]??null;
        }
        $vectors = ['edited_scans'=>'scan_count_by_dataset','edited_radial_points'=>'point_count_by_dataset',
            'simpoints'=>'simulation_points_by_dataset','radial_grid'=>'radial_grid_type_by_dataset',
            'time_grid'=>'time_grid_type_by_dataset','meniscus'=>'meniscus_radius_cm_by_dataset','bottom'=>'bottom_radius_cm_by_dataset'];
        foreach ($dataset_fields as $field) {
            for ($i=0;$i<$metadata_format->maximum_datasets;++$i) {
                $value = null;
                if ($i >= $serialized && $valid_xml) {
                    $value = 0;
                }
                elseif (isset($vectors[$field])) {
                    $value = $datasets[$vectors[$field]][$i]??null;
                }
                else {
                    $steps = $datasets['speed_profile_by_dataset'][$i]??null;
                    if ($steps) {
                        if ($field==='speedstep_count') {
                            $value=count($steps);
                        }
                        $key = $field==='rotorspeed'?'rotor_speed_rpm':($field==='duration_seconds'?'duration_seconds':null);
                        if ($key) {
                            $vals=array_column($steps,$key); if (!in_array(null,$vals,true)) {
                                $value=max($vals);
                            }
                        }
                    }
                }
                $base["$field.$i"]=$value;
            }
        }
        $results = db_obj_result($db_handle,"select * from {$db}.HPCAnalysisResult where HPCAnalysisRequestID=$thisreqid order by HPCAnalysisResultID",true,true);
        $result_rows = [];
        if ($results) {
            while ($result=mysqli_fetch_assoc($results)) {
                $result_rows[]=$result;
            }
        }
        if (!$result_rows) {
            $result_rows=[[]]; ++$global_counts['missing_results'];
        }
        foreach ($result_rows as $result) {
            $values=$base;
            $resources=usmd_parse_jobfile($result['jobfile']??null);
            foreach (['requested_nodes','requested_ranks','requested_cores','requested_memory_per_core','requested_memory_total','requested_wall_limit'] as $key) {
                $values[$key]=$resources[$key];
            }
            $values['CPUCount']=$result['CPUCount']??null;
            $values['actual_master_groups']=$result['mgroupcount']??null;
            $values['submitTime']=csv_timestamp($request['submitTime']??null);
            foreach (['startTime','endTime','updateTime'] as $key) {
                $values[$key]=csv_timestamp($result[$key]??null);
            }
            $completed=($result['queueStatus']??null)==='completed' && strpos($result['lastMessage']??'','FAILED')===false;
            $values['result_completed']=(int)$completed;
            if (!$completed) {
                ++$global_counts['incomplete_results'];
            }
            $values['method_classification_status']=$classification_status;
            $values['request_id']=$thisreqid;
            $values['result_id']=$result['HPCAnalysisResultID']??null;
            $values['experiment_id']=$request['experimentID']??null;
            $values['metadata_format_version']=$metadata_format->version;
            $values['cg_model_id']=$cg['model_id']??null;
            $values['dmga_model_id']=$dmga['model_id']??null;
            $values['cg_model_status']=$cg['status'];
            $values['dmga_model_status']=$dmga['status'];
            $values['queue_status']=$result['queueStatus']??'no-result';
            $values['resource_parser_status']=$resources['parser_status'];
            $values['scheduler']=$resources['scheduler_family'];
            $values['resource_wall_limit_conflict']=isset($resources['evidence']['wall_limit_conflict'])?1:0;
            $missing=[];$unmapped=[];$input_data=[];$target_data=[];
            foreach ($input_format as $field) {
                $value=$values[$field]??null;
                if (isset($metadata_format->string_mapping->{$field}) && $value!==null) {
                    $string_variants[$field][(string)$value]=true;
                    $mapped=$metadata_format->string_mapping->{$field}->{(string)$value}??null;
                    if ($mapped===null) {
                        $unmapped[$field]=$value;
                    }
                    $value=$mapped;
                }
                $number=csv_number($value);
                if ($number===null) {
                    $missing[]=$field;
                }
                $input_data[]=$number??$missing_value;
            }
            foreach ($target_format as $field) {
                $target_data[]=csv_number($result[$field]??null)??$missing_value;
            }
            $csv_base_name=str_replace(['__version__','__db__','__requestid__'],[$metadata_format->version,$db,$thisreqid],$metadata_format->filename_format->base);
            // Keep every result distinct, including requests with no result.
            if (($metadata_format->method_classification??null)==='research-v1') {
                $csv_base_name.='-r'.($result['HPCAnalysisResultID']??'none');
            }
            if ($metadata) {
                if ($csv_handle) {
                    $line=implode(' ',array_merge($input_data,$target_data))."\t".$csv_base_name."\n";
                    if (fwrite($csv_handle,$line)===false) {
                        throw new RuntimeException('CSV write failed');
                    }
                } else {
                    $path=$metadata_format->output_dir.'/'.$csv_base_name;
                    csv_write($path.'.input',implode("\n",$input_data)."\n");
                    csv_write($path.'.target',implode("\n",$target_data)."\n");
                }
            }
            if ($json || $squashedjson || $jsonmetadata) {
                echo_json('metadata', ['input'=>array_combine($input_format,$input_data),'target'=>array_combine($target_format,$target_data)]);
            }
            if ($listanalysistype) {
                echo "HPCAnalysisRequestID $thisreqid analType: ".($request['analType']??'')."\n";
            }
            if ($listdatasetcount) {
                echo "HPCAnalysisRequestID $thisreqid datasetCount: ".($declared??'unknown')."\n";
            }
            ++$global_counts['records'];
        }
        // Model contents are cached only within a request to bound memory use.
        $model_cache=[];
    }
}
if ($csv_handle) {
    fclose($csv_handle);
}
if ($liststringvariants) {
    echo_json('string variants',$string_variants);
}
echo_json('counts',$global_counts);
if ( $pythonppcode ) {
    $pyout = "test.py";
    $python_nonpredictors = json_encode($metadata_format->non_predictor_fields ?? []);
    $python_performance_prediction_code = <<<_PPPC
# based upon https://www.tensorflow.org/tutorials/keras/regression retrieved 2023.05.03
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
import seaborn as sns

# Make NumPy printouts easier to read.
np.set_printoptions(precision=3, suppress=True)
import tensorflow as tf

from tensorflow import keras
from tensorflow.keras import layers

print(tf.__version__)

datafile     = "summary_metadata.csv"
# column_names = [
# \$pppcolnames
#    ]

raw_dataset = pd.read_csv( datafile,
#                           names=column_names,
                           na_values='?', comment='\\t',
                           sep=' ', skipinitialspace=True, low_memory=False)
dataset = raw_dataset.copy()
dataset = dataset.drop(columns=$python_nonpredictors, errors="ignore")

# optionally find and drop n/a values
# dataset.isna().sum()
# dataset = dataset.dropna()

# drop unused targets at this time
dataset.pop( "max_rss" );
dataset.pop( "wallTime" );

# split data into trainig and test sets
train_dataset  = dataset.sample(frac=0.5, random_state=0)
test_dataset   = dataset.drop(train_dataset.index)

# optionally inspect the data
## add more columns to this, all columns takes awhile!
'''
sns.pairplot(train_dataset[['@attributes.method','CPUCount','simpoints.0']], diag_kind='kde' )
plt.show()
'''

# optionally check overall statistics
'''
train_dataset.describe().transpose()
train_dataset.describe().transpose()[['mean', 'std']]
'''

# split features (the input data) from labels (the target)

train_features = train_dataset.copy()
test_features  = test_dataset.copy()

# target CPUTime

train_labels   = train_features.pop('CPUTime')
test_labels    = test_features.pop('CPUTime')

# normalization

normalizer = tf.keras.layers.Normalization(axis=-1)
normalizer.adapt(np.array(train_features))
print(normalizer.mean.numpy())

# check normalization
'''
first = np.array(train_features[:1])

with np.printoptions(precision=2, suppress=True):
  print('First example:', first)
  print()
  print('Normalized:', normalizer(first).numpy())
'''

## multiple input linear regression

linear_model = tf.keras.Sequential([
        normalizer,
        layers.Dense(units=1)
    ])

linear_model.predict(train_features[:10])
linear_model.layers[1].kernel
linear_model.compile(
        optimizer=tf.keras.optimizers.Adam(learning_rate=0.1),
        loss='mean_absolute_error')

print("fitting linear model")

history = linear_model.fit(
        train_features,
        train_labels,
        epochs=100,
        # Suppress logging.
        verbose=0,
        # Calculate validation results on 20% of the training data.
        # could also use validation_data instead of validation_split
        validation_split = 0.2)

def plot_loss(history):
    plt.plot(history.history['loss'], label='loss')
    plt.plot(history.history['val_loss'], label='val_loss')
    # limits can be nice if the high epoch range is known
    #    plt.ylim([0, 10])
    plt.xlabel('Epoch')
    plt.ylabel('Error [CPUTime]')
    plt.legend()
    plt.grid(True)
    
plot_loss(history)
test_results['linear_model'] = linear_model.evaluate(
        test_features, test_labels, verbose=0)

## DNN
# for some reason we had to split build_and_compile_model()

## model definition is currently a the MPG example... likely needs more info

def build_model(norm):
    model = keras.Sequential([
        norm,
        layers.Dense(64, activation='relu'),
        layers.Dense(64, activation='relu'),
        layers.Dense(1)
    ])
    return model

def compile_model(model):
    model.compile(loss='mean_absolute_error',
                  optimizer=tf.keras.optimizers.Adam(0.001))

### build dnn_model

dnn_model=build_model(normalizer)
compile_model(dnn_model)
dnn_model.summary()

history = dnn_model.fit(
        train_features,
        train_labels,
        validation_split=0.2,
        verbose=0, epochs=100)

test_results['dnn_model'] = dnn_model.evaluate(test_features, test_labels, verbose=0)

## test set performance
pd.DataFrame(test_results, index=['Mean absolute error [CPUTime]']).T

## make predictions

'''
test_predictions = dnn_model.predict(test_features).flatten()

a = plt.axes(aspect='equal')
plt.scatter(test_labels, test_predictions)
plt.xlabel('True Values [CPUTime]')
plt.ylabel('Predictions [CPUTime]')
# turned off limits for now
#lims = [0, 50]
#plt.xlim(lims)
#plt.ylim(lims)
#_ = plt.plot(lims, lims)
plt.plot()

## error distribution

error = test_predictions - test_labels
plt.hist(error, bins=25)
plt.xlabel('Prediction Error [CPUTime]')
_ = plt.ylabel('Count')


## save model
dnn_model.save('dnn_model')

## reload
reloaded = tf.keras.models.load_model('dnn_model')

test_results['reloaded'] = reloaded.evaluate(
    test_features, test_labels, verbose=0)

'''

_PPPC;

    file_put_contents( $pyout , $python_performance_prediction_code );
    echo "created $pyout\n";
}
