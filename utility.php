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

function run_cmd( $cmd, $die_if_exit = true ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    exec( "$cmd 2>&1", $res, $res_code );
    if ( $die_if_exit && $res_code ) {
        error_exit( "shell command '$cmd' returned with exit status '$res_code'" );
    }
    return implode( "\n", $res ) . "\n";
}

function error_exit( $msg ) {
    fwrite( STDERR, "$msg\nTerminating due to errors.\n" );
    exit(-1);
}

function echoline( $str = "-", $count = 80 ) {
    $out = "";
    for ( $i = 0; $i < $count; ++$i ) {
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
        $warnings_count += count( explode( "\n", $warnings ) );
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
    return "Warnings generated\n"; # : $warnings_count\n";
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
    return $newfile_dir;
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

function is_admin() {
    $user = posix_getpwuid(posix_geteuid())['name'];
    if ( $user == 'root' ) {
        return true;
    }

    $groupInfo = posix_getgrnam('wheel');
    if ($groupInfo === false) {
        return false;
    }

    return in_array( $user, $groupInfo['members'] );
}

$db_handle = NULL;

function open_db() {
    global $db_handle;
    global $dbhost;
    global $user;
    global $passwd;
    $db_handle = mysqli_connect( $dbhost, $user, $passwd );
    if ( !$db_handle ) {
        write_logl( "could not connect to mysql: $dbhost, $user. exiting\n" );
        exit(-1);
    }
}    
    
function existing_dbs() {
    global $db_handle;
    if ( $db_handle === NULL ) {
        open_db();
    }
    $res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
    $existing_dbs = [];
    while( $row = mysqli_fetch_array($res) ) {
        $this_db = (string)$row[0];
        if ( $this_db != "uslims3_global" ) {
            $existing_dbs[] = $this_db;
        }
    }
    return $existing_dbs;
}

function boolstr( $val, $truestr = "True", $falsestr = "" ) {
    return $val ? $truestr : $falsestr;
}

function tempdir( $dir = NULL, $prefix = NULL ) {
    $template = "{$prefix}XXXXXX";
    if ( $dir && is_dir($dir) ) {
        $tmpdir = "--tmpdir=$dir";
    } else {
        $tmpdir = '--tmpdir=' . sys_get_temp_dir();
    }
    return exec( "mktemp -d $tmpdir $template" );
}

function file_perms_must_be( $file, $least_restrictive = "600" ) {
    $least_restrictive = octdec( $least_restrictive );
    if ( !file_exists( $file ) ) {
        error_exit( "file permissions check: file '$file' does not exist" );
    }
    $perms = fileperms( $file ) & octdec( "777" );
    $remainder = ( $perms | $least_restrictive ) - $least_restrictive;
    if ( $remainder ) {
        error_exit( sprintf( "Permissions on '$file' are too lenient, fix with:\nchmod %o $file", $least_restrictive ) );
    }
    return;
}
