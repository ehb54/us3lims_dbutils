<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

list all uslims3_ databases in db

Options

--help               : print this information and exit

--times              : display last update time information
__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$times               = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--times": {
            array_shift( $u_argv );
            $times = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( count( $u_argv ) ) {
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
            
require "utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

# main

$existing_dbs = existing_dbs();
if ( !$times ) {
    echo implode( "\n", $existing_dbs ) . "\n";
    exit;
}

if ( $times ) {
    if ( !is_admin() ) {
        error_exit( "you must have administrator privileges to use the --times option" );
    }

    $query   = "show variables where variable_name = 'datadir'";
    $res     = db_obj_result( $db_handle, $query );
    $datadir = $res->{'Value'};

    $data = [];
    fwrite( STDERR, "processing:\n" );
    $proccount = 0;
    foreach ( $existing_dbs as $db ) {
        fwrite( STDERR, " $db" );
        if ( !(++$proccount % 6 ) ) {
            fwrite( STDERR, "\n" );
        }
            
        $query     = "select max(update_time) from information_schema.tables where table_schema='$db'";
        $res       = db_obj_result( $db_handle, $query );
        $dbtimes[] = $res->{'max(update_time)'};
        $mtime     = preg_replace( '/\.\d+ \+\d{4} /', ' ', trim( run_cmd( "cd $datadir/$db && stat -c \$'%y %n' *{ibd,frm} | sort -rn 2>/dev/null | head -1" ) ) );
        $mtimes[]  = $mtime;
        $data[ $mtime . ":$db" ] =
            sprintf(
                "%-30s | %19s | %s\n"
                ,$db
                ,$res->{'max(update_time)'}
                ,$mtime
            );
    }

    fwrite( STDERR, "\n" );
    $dashlen = 130;
    echoline( '-', $dashlen );
    echo "Note: Last update time in DB will be empty after service restart\n";
    echoline( '-', $dashlen );

    printf(
        "%-30s | %19s | %s\n"
        ,"DB"
        ,"Last update time DB"
        ,"Last modification time of .frm & .ibd in $datadir"
        );
    echoline( '-', $dashlen );
    ksort( $data );
    echo implode( "", $data );
    echoline( '-', $dashlen );
}
