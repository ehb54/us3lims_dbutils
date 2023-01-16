<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

rename table column name

Options

--help               : print this information and exit

--db dbname          : (required) specify the database name 
--table table        : (required) table name
--change from to     : column name from to [N.B. requires mariaDB >= 10.5.2]
--drop name          : column name to drop


__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$dbname              = "";
$table               = "";
$change_from         = "";
$change_to           = "";
$drop                = "";

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
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
        case "--table": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --table requires an argument\n\n$notes" );
            }
            $table = array_shift( $u_argv );
            if ( empty( $table ) ) {
                error_exit( "--table requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--drop": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --drop requires an argument\n\n$notes" );
            }
            $drop = array_shift( $u_argv );
            if ( empty( $drop ) ) {
                error_exit( "--drop requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--change": {
            array_shift( $u_argv );
            if ( count( $u_argv ) < 2 ) {
                error_exit( "\nOption --change requires two arguments\n\n$notes" );
            }
            $change_from = array_shift( $u_argv );
            $change_to   = array_shift( $u_argv );
            if ( empty( $change_from ) ||
                 empty( $change_from ) ) {
                error_exit( "--change requires to non-empty values\n\n$notes" );
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

if ( empty( $dbname ) ||
     empty( $table ) ) {
    echo $notes;
    exit;
}

if ( empty( $change_from ) && empty( $change_to ) && empty( $drop ) ) {
   error_exit( "--change or --drop must be specified\n\n$notes" );     
}

if ( !empty( $change_from ) && !empty( $drop ) ) {
   error_exit( "--change and --drop can not both be specified\n\n$notes" );     
}

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

if ( !empty( $change_from ) && !empty( $change_to ) ) {
    $q = "ALTER TABLE $dbname.$table RENAME COLUMN $change_from TO $change_to";
}
if ( !empty( $drop ) ) {
    $q = "ALTER TABLE $dbname.$table DROP COLUMN $drop";
}

$res = mysqli_query( $db_handle, $q );
if ( !$res ) {
    error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
}

