<?php

{};

# user defines

$us3lims      = exec( "ls -d ~us3/lims" );
$us3bin       = "$us3lims/bin";
include "$us3bin/listen-config.php";
include $class_dir_p . "experiment_cancel.php";
include $class_dir_p . "experiment_status.php";

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options}

airavata job status messages

Options

--help                     : print this information and exit

--gfacid             id    : set gfacid
--cancel                   : cancel job

__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$gfacid    = false;
$cancel    = false;

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
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( !$gfacid ) {
    error_exit( "--gfacid must be specified" );
}

echoline();
echo "status for $gfacid:\n" . json_encode( getExperimentStatus( $gfacid ), JSON_PRETTY_PRINT ) . "\n";
echoline();

if ( $cancel && cancelAiravataJob( $gfacid ) ) {
    echo "canceled $gfacid\n";
    echoline();
}

