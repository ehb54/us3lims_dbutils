<?php

{};

# user defines

$us3lims      = exec( "ls -d ~us3/lims" );
$us3bin       = "$us3lims/bin";
include "$us3bin/listen-config.php";
include $class_dir_p . "experiment_cancel.php";
include $class_dir_p . "experiment_status.php";
include $class_dir_p . "experiment_resource.php";

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options}

airavata job status messages

Options

--help                     : print this information and exit

--gfacid             id    : set gfacid
--all                      : list status for all Airavata jobs in gfac.analysis
--cancel                   : cancel job


__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$gfacid    = false;
$cancel    = false;
$all       = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--gfacid": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $gfacid = array_shift( $u_argv );
            break;
        }
        case "--cancel": {
            array_shift( $u_argv );
            $cancel = true;
            break;
        }
        case "--all": {
            array_shift( $u_argv );
            $all = true;
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

function gfacstatus( $id ) {
    echoline();
    echo "status for $id:\n" . json_encode( getExperimentStatus( $id ), JSON_PRETTY_PRINT ) . "\n";
    echo "compute resource for $id:\n" . json_encode( getComputeResource( $id ), JSON_PRETTY_PRINT ) . "\n";
    echoline();
}
    

if ( $all ) {
    open_db();
    $res = db_obj_result( $db_handle, "select gfacID from gfac.analysis", True, True );
    $gfacids = [];
    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            # debug_json( "row ", $row );
            if ( preg_match( '/^US3-AIRA/', $row['gfacID'] ) ) {
                $gfacids[] = $row['gfacID'];
            }
        }
    }
    
    # debug_json( "gfacids", $gfacids );
    foreach ( $gfacids as $v ) {
        gfacstatus( $v );
    }
        
    exit;
}

if ( !$gfacid ) {
    error_exit( "--gfacid must be specified" );
}

gfacstatus( $gfacid );

if ( $cancel && cancelAiravataJob( $gfacid ) ) {
    echo "canceled $gfacid\n";
    echoline();
}

