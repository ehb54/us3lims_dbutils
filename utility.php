<?php

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

function run_cmd( $cmd ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    $res = `$cmd 2>&1`;
    return $res;
}

function error_exit( $msg ) {
    fwrite( STDERR, "$msg\nTerminating due to errors." );
    exit(-1);
}

function echoline( $str = "-" ) {
    $out = "";
    for ( $i = 0; $i < 80; ++$i ) {
       $out .= $str;
    }
    echo "$out\n";
}

$warnings = '';
function flush_warnings( $msg = NULL ) {
    global $warnings;
    if ( strlen( $warnings ) ) {
        echo $warnings;
        echoline();
        $warnings = '';
    } else {
        if ( $msg ) {
            echo "$msg\n";
        }
    }
}

$errors = '';
function flush_errors_exit() {
    global $errors;
    if ( strlen( $errors ) ) {
        error_exit( $errors );
    }
}
