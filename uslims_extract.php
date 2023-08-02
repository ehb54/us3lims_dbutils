<?php

# user defines

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;

$notes = <<<__EOD

usage: $self {options}

Options

--help                 : print this information and exit
    
--db name              : select db
--list                 : list tables & related record counts
--list-detail          : detailed list of tables
--export               : export data as compressed tar dump
--pattern pattern      : regexp pattern to match rawData filename (required)


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$use_dbs             = [];
$list                = false;
$detail              = false;
$export              = false;
$pattern             = "";

$debug               = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            if ( count( $use_dbs ) ) {
                error_exit( "ERROR: option '$arg' can only be specified once\n$notes" );
            }
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--pattern": {
            if ( strlen( $pattern ) ) {
                error_exit( "ERROR: option '$arg' can only be specified once\n$notes" );
            }
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $pattern = array_shift( $u_argv );
            break;
        }
        case "--list" : {
            array_shift( $u_argv );
            $list = true;
            break;
        }
        case "--list-detail": {
            array_shift( $u_argv );
            $detail = true;
            $list = true;
            break;
        }
        case "--export": {
            array_shift( $u_argv );
            $export = true;
            break;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
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

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" .
   "[mysqldump]\n" .
   "password=YOUR_ROOT_DB_PASSWORD\n"
   );
}
file_perms_must_be( $myconf );

$existing_dbs = existing_dbs();
if ( !count( $use_dbs ) ) {
    $use_dbs = $existing_dbs;
} else {
    $db_diff = array_diff( $use_dbs, $existing_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database : " . implode( ' ', $db_diff ) );
    }
}

if ( !strlen( $pattern ) ) {
    error_exit( "a non-empty --pattern must be provided" );
}

if ( !count( $use_dbs ) ) {
    error_exit( "--db must be specified" );
}

$db = $use_dbs[0];

if ( $list ) {
    open_db();
    $query = "show keys from $db.rawData where Key_name = 'PRIMARY'";
    $res = db_obj_result( $db_handle, $query, true, true );
    if ( !$res ) {
        error_exit( "found no records with query: $query" );
    }


    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            debug_json( "row", $row );
        }
    }
    
    $query = "select filename, experimentID from $db.rawData where filename rlike '$pattern'";
    $res = db_obj_result( $db_handle, $query, true, true );
    if ( !$res ) {
        error_exit( "found no records with query: $query" );
    }
    
    # build up table keys object
    # e.g. table primarykey
    ## can we get primary key from schema
    
    $expIDs = [];
    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            debug_json( "row", $row );
            $expIDs[] = $row['experimentID'];
        }
    }

    foreach ( $expIDs as $eID ) {
        $query = "select HPCAnalysisRequestID from $db.HPCAnalysisRequest where experimentID = '$eID'";
        $res = db_obj_result( $db_handle, $query, true, true );
        if ( $res ) {
            while( $row = mysqli_fetch_array($res) ) {
                debug_json( "row", $row );
            }
        }
    }
    exit;
}

echo "Nothing to do\n\n";
echo $notes;
