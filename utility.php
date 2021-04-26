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

$warnings       = '';
$warnings_count = 0;
function flush_warnings( $msg = NULL ) {
    global $warnings;
    global $warnings_count;
    if ( strlen( $warnings ) ) {
        echo $warnings;
        echoline();
        $warnings = '';
        $warnings_count += count( explode( "\n", trim( $warnings ) ) );
        return true;
    } else {
        if ( $msg ) {
            echo "$msg\n";
        }
        return false;
    }
}

function warnings_summary( $msg = NULL ) {
    global $warnings_count;
    return "Warnings generated: $warnings_count\n";
}

$errors = '';
function flush_errors_exit() {
    global $errors;
    if ( strlen( $errors ) ) {
        error_exit( $errors );
    }
}

function get_yn_answer( $question, $quit_if_no = false ) {
    echoline( '=' );
    do {
        $answer = readline( "$question (y or n) : " );
    } while ( $answer != "y" && $answer != "n" );
    if ( $quit_if_no && $answer == "n" ) {
        fwrite( STDERR, "Terminated by user response.\n" );
        exit(-1);
    }
    return $answer == "y";
}

$backup_dir = "";

function backup_dir_init( $dir = "backup" ) {
    global $backup_dir;
    $backup_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) );
    mkdir( $backup_dir );
    if ( !is_dir( $backup_dir ) ) {
        error_exit( "Could not make backup directory $backupdir" );
    }
}

function backup_file( $filename ) {
    global $backup_dir;
    if ( !file_exists( $filename ) ) {
        error_exit( "backup_file : $filename does not exist!" );
    }
    if ( !strlen( $backup_dir ) ) {
        backup_dir_init();
    }
    run_cmd( "cp $filename $backup_dir" );
    echo "Original $filename backed up in to $backup_dir\n";
}

$newfile_dir = "";

function newfile_dir_init( $dir = "newfile" ) {
    global $newfile_dir;
    $newfile_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) );
    mkdir( $newfile_dir );
    if ( !is_dir( $newfile_dir ) ) {
        error_exit( "Could not make newfile directory $newfiledir" );
    }
}

function newfile_file( $filename, $contents ) {
    global $newfile_dir;
    if ( !strlen( $newfile_dir ) ) {
        newfile_dir_init();
    }
    $outfile = "$newfile_dir/$filename";
    if ( false === file_put_contents( $outfile, $contents ) ) {
        error_exit( "Could not write $outfile" );
    }
    echo "CREATED: New file $outfile\n";
    return $outfile;
}




