<?php

# utility

$STDERR = STDERR;

function write_logl( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo "{$self}: $msg\n";
    }
}

function timestamp( $msg = "" ) {
    return date( "Y-m-d H:i:s " ) . $msg;
}

function write_logld( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo timestamp() . "{$self}: $msg\n";
    }
}

function db_obj_result( $db_handle, $query, $expectedMultiResult = false, $emptyok = false ) {
    $result = @mysqli_query( $db_handle, $query );

    if ( !$result || ( is_object( $result ) && !$result->num_rows ) ) {
        if ( $result ) {
            # $result->free_result();
        }
        if ( $emptyok ) {
            return false;
        }
        write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
        if ( $result ) {
            debug_json( "query result", $result );
        }
        exit;
    }

    if ( is_object( $result ) && $result->num_rows > 1 && !$expectedMultiResult ) {
        write_logl( "WARNING: db query returned " . $result->num_rows . " rows : $query" );
    }    

    if ( $expectedMultiResult ) {
        return $result;
    } else {
        if ( is_object( $result ) ) {
            return mysqli_fetch_object( $result );
        } else {
            return $result;
        }
    }
}

function debug_json( $msg, $json, $debuglevel = 0 ) {
    global $debug;
    global $STDERR;
    if ( $debuglevel > 0 && $debug < $debuglevel ) {
        return;
    }
    fwrite( $STDERR,  "$msg\n" );
    fwrite( $STDERR, json_encode( $json, JSON_PRETTY_PRINT ) );
    fwrite( $STDERR, "\n" );
}

function echo_json( $msg, $json, $debuglevel = 0 ) {
    echo  "$msg\n";
    echo json_encode( $json, JSON_PRETTY_PRINT );
    echo "\n";
}

function run_cmd( $cmd, $die_if_exit = true, $array_result = false ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    exec( "$cmd 2>&1", $res, $res_code );
    if ( $die_if_exit && $res_code ) {
        error_exit( "shell command '$cmd' returned result:\n" . implode( "\n", $res ) . "\nand with exit status '$res_code'" );
    }
    if ( !$array_result ) {
        return implode( "\n", $res ) . "\n";
    }
    return $res;
}

function error_exit( $msg ) {
    global $STDERR;
    fwrite( $STDERR, "$msg\nTerminating due to errors.\n" );
    exit(-1);
}

function echoline( $str = "-", $count = 80, $print = true ) {
    $out = "";
    for ( $i = 0; $i < $count; ++$i ) {
       $out .= $str;
    }
    if ( $print ) {
        echo "$out\n";
    }
    return "$out\n";
}

function headerline( $msg, $str = "=", $count = 80, $print = true ) {
    $out =
        echoline( $str, $count, false )
        . "$msg\n"
        . echoline( $str, $count, false )
        ;
    if ( $print ) {
        echo $out;
    }
    return $out;
}

$warnings       = '';
$warnings_count = 0;
function flush_warnings( $msg = NULL ) {
    global $warnings;
    global $warnings_count;
    if ( strlen( $warnings ) ) {
        echo $warnings;
        $warnings_count += count( explode( "\n", trim( $warnings ) ) );
        echoline();
        $warnings = '';
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
    return $warnings_count ? "Warnings generated $warnings_count\n" : "";
}

$errors = '';
function flush_errors_exit() {
    global $errors;
    if ( strlen( $errors ) ) {
        error_exit( $errors );
    }
}

function get_yn_answer( $question, $quit_if_no = false ) {
    global $STDERR;
    echoline( '=' );
    do {
        $answer = readline( "$question (y or n) : " );
    } while ( $answer != "y" && $answer != "n" );
    if ( $quit_if_no && $answer == "n" ) {
        fwrite( $STDERR, "Terminated by user response.\n" );
        exit(-1);
    }
    return $answer == "y";
}

$util_backup_dir = "";

function backup_dir_init( $dir = "backup" ) {
    global $util_backup_dir;
    ## -pid<pid> too, not just the timestamp: two runs within
    ## the same second (ran: back-to-back --activate invocations) used to
    ## collide on one directory -- mkdir() for the second run failed
    ## silently (no @ here, but nothing checks its return either), and
    ## is_dir() right after still passed because the first run's directory
    ## was already there, so the second run's backup_file() calls silently
    ## shared it. A file both runs backed up then kept only the second run's
    ## copy, overwriting the first run's original -- exactly what a rollback
    ## later needs. Appending the pid makes the directory name unique per
    ## process regardless of timing.
    $util_backup_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) ) . '-pid' . getmypid();
    if ( !mkdir( $util_backup_dir ) || !is_dir( $util_backup_dir ) ) {
        error_exit( "Could not make backup directory $util_backup_dir" );
    }
}

function backup_file( $filename ) {
    global $util_backup_dir;
    if ( !file_exists( $filename ) ) {
        error_exit( "backup_file : $filename does not exist!" );
    }
    if ( !strlen( $util_backup_dir ) ) {
        backup_dir_init();
    }
    run_cmd( "cp $filename $util_backup_dir" );
    echo "Original $filename backed up in to $util_backup_dir\n";
}

$newfile_dir = "";

function newfile_dir_init( $dir = "newfile" ) {
    global $newfile_dir;
    $newfile_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) );
    mkdir( $newfile_dir );
    if ( !is_dir( $newfile_dir ) ) {
        error_exit( "Could not make newfile directory $newfile_dir" );
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

function is_admin( $must_be_root = true, $as_user = "" ) {
    $user = posix_getpwuid(posix_geteuid())['name'];
    if ( strlen( $as_user ) ) {
        return $user == $as_user;
    }
        
    if ( $user == 'root' ) {
        return true;
    }
    if ( $must_be_root ) {
        return false;
    }

    $groupInfo = posix_getgrnam('wheel');
    if ($groupInfo === false) {
        return false;
    }

    return in_array( $user, $groupInfo['members'] );
}

$db_handle = NULL;

function check_db( $sleep_seconds = 30 ) {
    global $db_handle;
        
    while ( !mysqli_ping( $db_handle ) ) {
        write_logld( "mysql server has gone away" );
        sleep( $sleep_seconds );
        write_logl( "attempting to reconnect" );
        open_db();
        if ( mysqli_ping( $db_handle ) ) {
            write_logl( "reconnected - success" );
        }            
    }
}

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
    
function existing_dbs( $include_global = false ) {
    global $db_handle;
    if ( $db_handle === NULL ) {
        open_db();
    }
    $res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
    $existing_dbs = [];
    while( $row = mysqli_fetch_array($res) ) {
        $this_db = (string)$row[0];
        if ( $this_db != "uslims3_global" || $include_global ) {
            $existing_dbs[] = $this_db;
        }
    }
    return $existing_dbs;
}

function existing_stash_dbs() {
    global $db_handle;
    if ( $db_handle === NULL ) {
        open_db();
    }
    $res = db_obj_result( $db_handle, "show databases like 'stash_%'", true, true );
    $existing_dbs = [];
    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            $this_db = (string)$row[0];
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

function get_slurm_cores( $cores = 4, $slurmconf = "/etc/slurm/slurm.conf" ) {
    if ( !file_exists( $slurmconf ) ) {
        return $cores;
    }
    $res = run_cmd( 'grep -e "^\s*NodeName\s*=\\s*localhost" ' . $slurmconf, false );
    preg_match( '/Procs=(\d+)\s/', $res, $matches );
    if ( count( $matches ) != 2 ) {
        return $cores;
    }
    
    return intval( $matches[1] * 2 );
}

/**
 * Generates a Universally Unique IDentifier, version 4.
 *
 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
 * Unique IDentifiers (GUID), as well as several methods for producing them. One
 * such method, described in section 4.4, is based on truly random or pseudo-random
 * number generators, and is therefore implementable in a language like PHP.
 *
 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
 * are random.
 *
 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
 * be 01. We try to conform to that definition as efficiently as possible, generating
 * smaller values where possible, and minimizing the number of base conversions.
 *
 * @copyright   Copyright (c) CFD Labs, 2006. This function may be used freely for
 *              any purpose ; it is distributed without any form of warranty whatsoever.
 * @author      David Holmes <dholmes@cfdsoftware.net>
 *
 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
 */

function uuid() {
   
    // The field names refer to RFC 4122 section 4.1.2

    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
    ); 
}

function is_locked( $php ) {
    global $lock_dir;
    if ( !isset( $lock_dir ) ) {
        error_exit( "is_locked(): \$lock_dir is not set" );
    }
    $lock_file        = "$lock_dir/" . basename( $php ) . ".lock";
    $expected_cmdline = basename( $php );
    $isstale = false;

    if ( !file_exists($lock_file) ) {
        # echo "file $lock_file does not exist\n";
        return false;
    }

    if ( is_link($lock_file) ) {
        # echo "is_link(" . $lock_file . ") true\n";
        if ( ( $link = readlink( $lock_file ) ) === FALSE ) {
            $isstale = true;
            # echo "is stale 1\n";
        }
    } else {
        $isstale = true;
        # echo "is stale 2\n";
    }
    #    echo "tryLock() 2\n";

    if ( !$isstale && is_dir( $link ) ) {
        # make sure the cmdline exists & matches expected
        $cmdline_file = $link . "/cmdline";
        echo "cmdline_file = $cmdline_file\n";
        if ( ($cmdline = file_get_contents( $cmdline_file )) === FALSE ) {
            # echo "could not get contents of $cmdline_file\n";
            $isstale = true;
            # echo "is stale 3\n";
        } else {
            # remove nulls
            $cmdline = str_replace("\0", "", $cmdline);
            if ( strpos( $cmdline, $expected_cmdline ) === false ) {
                # echo "unexpected contents of $cmdline_file\n";
                $isstale = true;
                # echo "is stale 4 \n";
            }
        }
    }
    #    echo "tryLock() 3\n";

    if (is_link($lock_file) && !is_dir($lock_file)) {
        $isstale = true;
    }
    
    return !$isstale;
}

$dt_store_array = [];

function dt_duration_minutes ( $datetime_start, $datetime_end ) {
    return ($datetime_end->getTimestamp() - $datetime_start->getTimestamp()) / 60;
}

function dt_now () {
    return new DateTime( "now" );
}

function dt_store_now( $name ) {
    global $dt_store_array;
    $dt_store_array[ $name ] = dt_now();
}

function dt_store_get( $name ) {
    global $dt_store_array;
    if ( !array_key_exists( $name, $dt_store_array ) ) {
        error_exit( "dt_store_get() : \$dt_array does not contain key '$name'" );
    }
    return $dt_store_array[ $name ];
}

function dt_store_get_printable( $name ) {
    $dt = dt_store_get( $name );
    return $dt->format( DATE_ATOM );
}

function dt_store_duration( $name_start, $name_end ) {
    return sprintf( "%.2f", dt_duration_minutes( dt_store_get( $name_start ), dt_store_get( $name_end ) ) );
}

function dhms_from_minutes( $time ) {
    $res = '';
    $days  =  floor( $time / (24 * 60) );
    $time  -= $days * 24 * 60;
    $hours =  floor( $time / 60 );
    $time  -= $hours * 60;

    if ( $days ) {
        return sprintf( "%sd %sh %.2fm", $days, $hours, $time );
    }
    if ( $hours ) {
        return sprintf( "%sh %.2fm", $hours, $time );
    }
    return sprintf( "%.2fm", $time );
}

function backup_rsync_email_headers() {
    global $backup_host;
    global $backup_user;
    global $backup_email_address;

    $headers  = 
        "From: backups $backup_host<$backup_user@$backup_host>\r\n"
        . "Reply-To: $backup_email_address\r\n"
        ;

    return $headers;
}

function backup_rsync_failure( $msg ) {
    global $backup_email_reports;
    global $backup_email_address;
    global $backup_host;
    global $backup_logs;
    global $date;
    if ( !$backup_email_reports ) {
        error_exit( $msg );
    }
    $emaillog = "$backup_logs/summary-ERRORS-$date.txt";
    file_put_contents( $emaillog, $msg );
    if ( !mail( 
               $backup_email_address
               ,"BACKUP ERRORS for $backup_host"
               ,$msg
               ,backup_rsync_email_headers()
              )
       ) {
        error_exit( $msg . "\nemail notification also failed" );
    }
    error_exit( $msg );
}
    
function backup_rsync_run_cmd( $cmd, $die_if_exit = true ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    exec( "$cmd 2>&1", $res, $res_code );
    if ( $die_if_exit && $res_code ) {
        backup_rsync_failure( "shell command '$cmd' returned result:\n" . implode( "\n", $res ) . "\nand with exit status '$res_code'" );
    }
    return implode( "\n", $res ) . "\n";
}

function debug_echo ( $s, $debuglevel = 1 ) {
    global $debug;
    if ( !$debug || $debug < $debuglevel ) {
        return;
    }
    echo "$s\n";
}
    
function fix_single_quote( $str, $rplc = "" ) {
    return str_replace( "'", $rplc, $str );
}

## squash an object
## credit https://gist.github.com/woganmay/9a98dda059246bca664c

function squash($array, $prefix = '') {
    $flat = array();
    $sep = ".";
    
    if (!is_array($array)) $array = (array)$array;
    
    foreach($array as $key => $value)
    {
        $_key = ltrim($prefix.$sep.$key, ".");
        
        if (is_array($value) || is_object($value))
        {
            // Iterate this one too
            $flat = array_merge($flat, squash($value, $_key));
        }
        else
        {
            $flat[$_key] = $value;
        }
    }

    return $flat;
}

# A 4.2.0 host's people_audit.created_at was written from the column's own
# DEFAULT CURRENT_TIMESTAMP, which is the writing connection's session time
# zone -- not necessarily UTC. 4.3.0's write_audit_row() writes
# UTC_TIMESTAMP() explicitly instead, so any dbinstance that predates that
# fix is still carrying some or all of its people_audit rows in whatever
# local zone the writing host's MySQL session used.
#
# Shared by dbupgrade/stage2_import_dbinsts.php (a 4.2.0 dbinstance
# imported wholesale onto a new host -- every row predates the fix the
# first time this runs) and uslims_upgrade.php's in-place path (the same
# dbinstance, upgraded where it already runs -- some rows may already be
# correct UTC, written by 4.3.0 itself, if the fix has already deployed
# here before this script has). Idempotent via a marker table written
# inside the dbinstance itself (not metadata, not a file) so it is correct
# either way and travels automatically with any future export/import of
# this same dbinstance.
function migrate_people_audit_created_at_to_utc( $db_handle, $db ) {
    $exists = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE 'people_audit'" );
    if ( !$exists || mysqli_num_rows( $exists ) === 0 ) {
        # Predates the audit feature entirely; nothing to migrate.
        return;
    }

    $marker = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE '_dbutils_people_audit_utc_migrated'" );
    if ( $marker && mysqli_num_rows( $marker ) > 0 ) {
        echo "$db.people_audit was already migrated to UTC previously; skipping\n";
        return;
    }

    $count_res = mysqli_query( $db_handle, "SELECT COUNT(*) AS n FROM $db.people_audit" );
    $count_row = $count_res ? mysqli_fetch_assoc( $count_res ) : null;
    if ( !$count_row || (int) $count_row[ 'n' ] === 0 ) {
        echo "$db.people_audit is empty; nothing to migrate to UTC\n";
        return;
    }

    # $db_handle's own session time zone, not a value this function guesses
    # or hardcodes, and not necessarily the zone the rows were actually
    # written in if $db_handle is not the same connection/host that wrote
    # them (stage2: that information does not survive the export/import
    # round trip, so this reads whatever zone the server running stage2
    # itself is in -- the caller is responsible for that being the right
    # zone for its own scenario). 'SYSTEM' (the common default) defers to
    # the OS's own tz rules via CONVERT_TZ, so DST in the historical data
    # is still handled correctly without needing mysql.time_zone_* loaded.
    $tz_res  = mysqli_query( $db_handle, "SELECT @@session.time_zone AS tz" );
    $tz_row  = $tz_res ? mysqli_fetch_assoc( $tz_res ) : null;
    $from_tz = ( $tz_row && $tz_row[ 'tz' ] !== '' ) ? $tz_row[ 'tz' ] : 'SYSTEM';

    # A named zone (e.g. 'America/Chicago') needs mysql.time_zone_name
    # loaded (mysql_tzinfo_to_sql) to resolve; 'SYSTEM' and a numeric offset
    # (e.g. '+05:00', a valid session.time_zone value on its own, never a
    # named zone) do not -- checking "!== 'SYSTEM'" alone treated every
    # numeric-offset host as if it needed zone tables too, and skipped the
    # migration entirely for one even when the tables were loaded fine
    # (confirmed: SELECT CONVERT_TZ(..., '+05:00', '+00:00') resolves with
    # no zone tables present at all). Rather than let CONVERT_TZ() silently
    # return NULL into a NOT NULL column, check up front and skip loudly
    # instead -- but only when $from_tz is actually a named zone.
    if ( $from_tz !== 'SYSTEM' && !preg_match( '/^[+-]\d{2}:\d{2}$/', $from_tz ) ) {
        $loaded = mysqli_query( $db_handle, "SELECT 1 FROM mysql.time_zone_name LIMIT 1" );
        if ( !$loaded || mysqli_num_rows( $loaded ) === 0 ) {
            echo "WARNING: $db.people_audit: session time_zone is '$from_tz' but"
               . " mysql.time_zone_name is not loaded on this server (run"
               . " mysql_tzinfo_to_sql); skipping the UTC migration for $db --"
               . " convert it by hand once the time zone tables are loaded.\n";
            return;
        }
    }

    $from_tz_esc = mysqli_real_escape_string( $db_handle, $from_tz );
    echo "Migrating $db.people_audit.created_at from '$from_tz' to UTC"
       . " (" . $count_row[ 'n' ] . " row(s))\n";

    # '+00:00', not the named zone 'UTC': a named zone on EITHER side of
    # CONVERT_TZ() needs mysql.time_zone_name loaded, same as checked above
    # for $from_tz -- but that check only ever covered the source side, so
    # an account with SYSTEM (or any numeric-offset) $from_tz and no zone
    # tables loaded still hit this as the destination and got NULL into a
    # NOT NULL column. A numeric offset needs no zone tables at all, on
    # either side, so this cannot happen here any more for a real
    # timestamp -- but the WHERE clause below still excludes any row where
    # it would, rather than trust that: a NULL written to this NOT NULL
    # column does not necessarily raise an error at all. Tested directly
    # (MariaDB 10.3, no zone tables loaded): assigning CONVERT_TZ()'s NULL
    # to this column -- which carries DEFAULT CURRENT_TIMESTAMP -- silently
    # replaced the row's created_at with the UPDATE's own run time instead
    # of erroring OR zeroing it, under STRICT_ALL_TABLES. Excluding the row
    # from the UPDATE instead of writing to it and checking afterward is
    # exact regardless of which silent substitution a given server version
    # and sql_mode happens to make.
    $cmd = "UPDATE $db.people_audit"
         . " SET created_at = CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' )"
         . " WHERE created_at != '0000-00-00 00:00:00'"
         . "   AND CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' ) IS NOT NULL";
    $res = mysqli_query( $db_handle, $cmd );
    if ( !$res ) {
        # Not error_exit(): stage2's caller has already dropped and
        # recreated this (and every other) dbinstance by the time this
        # runs, so aborting the whole import here would leave every
        # dbinstance after this one in the list missing entirely, over a
        # problem confined to one table in one of them. uslims_upgrade.php
        # does not touch other dbinstances at all, so the same non-fatal
        # return is simply the right call there too.
        echo "WARNING: $db.people_audit: could not convert created_at to UTC ("
           . mysqli_error( $db_handle ) . "); this dbinstance's data is otherwise fully"
           . " imported -- convert people_audit by hand once the time zone is resolvable.\n";
        return;
    }

    # Rows excluded above (zero dates, and any CONVERT_TZ() genuinely
    # cannot resolve) are untouched, not corrupted -- still worth a warning
    # so an operator knows to look, since a non-zero row landing here would
    # mean $from_tz is wrong or unresolvable in some way the guard above
    # did not anticipate.
    $unresolved_res = mysqli_query( $db_handle,
        "SELECT COUNT(*) AS n FROM $db.people_audit WHERE created_at != '0000-00-00 00:00:00'"
      . "   AND CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' ) IS NULL" );
    $unresolved_row = $unresolved_res ? mysqli_fetch_assoc( $unresolved_res ) : null;
    $unresolved = $unresolved_row ? (int) $unresolved_row[ 'n' ] : 0;
    if ( $unresolved > 0 ) {
        echo "WARNING: $db.people_audit: $unresolved row(s) could not be resolved to UTC from"
           . " '$from_tz' and were left unchanged -- verify by hand.\n";
    }

    # Marker travels with the dbinstance itself (see the function-level
    # comment above), so a later migration attempt against this same
    # dbinstance -- by either caller -- skips it instead of shifting
    # already-correct rows again.
    mysqli_query( $db_handle, "CREATE TABLE IF NOT EXISTS $db._dbutils_people_audit_utc_migrated ("
                             . " migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                             . " from_time_zone VARCHAR(64) NOT NULL )" );
    mysqli_query( $db_handle, "INSERT INTO $db._dbutils_people_audit_utc_migrated ( from_time_zone )"
                             . " VALUES ( '$from_tz_esc' )" );
}
