<?php

$self = __FILE__;

$notes = <<<__EOD
usage: $self

lists dbnames, dbhosts & limshost from newus3.metadata where status=completed
__EOD;

if ( count( $argv ) < 1 || count( $argv ) > 2 ) {
    echo $notes;
    exit;
}

$config_file = "../db_config.php";
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

# utility routines

function run_cmd( $cmd ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    $res = `$cmd 2>&1`;
    return $res;
}
    
function write_logl( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo "${self}: " . $msg . "\n";
    }
}
    
function error_exit( $msg ) {
    fwrite( STDERR, "$msg\n" );
    exit(-1);
}

function debug_json( $msg, $json ) {
    fwrite( STDERR,  "$msg\n" );
    fwrite( STDERR, json_encode( $json, JSON_PRETTY_PRINT ) );
    fwrite( STDERR, "\n" );
}

function echoline( $str = "-" ) {
    $out = "";
    for ( $i = 0; $i < 80; ++$i ) {
       $out .= $str;
    }
    echo "$out\n";
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

# end utility routines

# main
$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

$res = db_obj_result( $db_handle, "select dbname, dbhost, limshost, status from newus3.metadata where status='completed'", True );
while( $row = mysqli_fetch_array($res) ) {
#    debug_json( "row", $row );
    $dbname   = $row['dbname'];
    $dbhost   = $row['dbhost'];
    $limshost = $row['limshost'];
    $status   = $row['status'];
    printf( "%-40s %-40s %-40s\n", $dbname, $dbhost, $limshost );
}

