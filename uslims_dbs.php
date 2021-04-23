<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {remote_config_file}

list all uslims3_ databases in db
__EOD;

if ( count( $argv ) < 1 || count( $argv ) > 2 ) {
    echo $notes;
    exit;
}

$config_file = "db_config.php";
if ( count( $argv ) == 2 ) {
    $use_config_file = $argv[ 1 ];
} else {
    $use_config_file = $config_file;
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
            
require $use_config_file;

# utility

function write_logl( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo "${self}: " . $msg . "\n";
    }
}

function db_obj_result( $db_handle, $query, $expectedMultiResult = false ) {
    $result = mysqli_query( $db_handle, $query );

    if ( !$result || !$result->num_rows ) {
        if ( $result ) {
            # $result->free_result();
        }
        write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
        if ( $result ) {
            debug_json( "query result", $result );
        }
        exit;
    }

    if ( $result->num_rows > 1 && !$expectedMultiResult ) {
        write_logl( "WARNING: db query returned " . $result->num_rows . " rows : $query" );
    }    

    if ( $expectedMultiResult ) {
        return $result;
    } else {
        return mysqli_fetch_object( $result );
    }
}

function debug_json( $msg, $json ) {
    fwrite( STDERR,  "$msg\n" );
    fwrite( STDERR, json_encode( $json, JSON_PRETTY_PRINT ) );
    fwrite( STDERR, "\n" );
}

# main

$db_handle = mysqli_connect( $dbhost, $user, $passwd );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user, $lims_db. exiting\n" );
    exit(-1);
}

# make sure the db's don't already exist!
$res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
$existing_dbs = [];
while( $row = mysqli_fetch_array($res) ) {
    $this_db = (string)$row[0];
    if ( $this_db != "uslims3_global" ) {
        $existing_dbs[] = $this_db;
    }
}

echo implode( "\n", $existing_dbs ) . "\n";
