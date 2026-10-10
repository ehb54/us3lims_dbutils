<?php

$self = __FILE__;
# user defines
$us3home         = "/home/us3";
$us3ini          = "$us3home/lims/.us3lims.ini";

# developer defines
$logging_level = 2;
# end of developer defines

# config
$limsdbpath = "/home/us3/lims/database/sql";
$htmlpath   = "/srv/www/htdocs/uslims3";
$uncompresswith = "zcat";
$compressext  = "gz";

# $debug = 1;

# end config

$cwd = getcwd();


$notes = <<<__EOD
usage: $self dbhost {config_file}

1. extracts tarfile into unique directory
2. drops databases
3. recreates databases 
4. imports data

if config_file specified, it will be used instead of ../db config
my.cnf must exist in the current directory

__EOD;

if ( count( $argv ) < 2 || count( $argv ) > 3 ) {
    echo $notes;
    exit;
}

$use_dbhost = $argv[ 1 ];

$config_file = "../db_config.php";
if ( count( $argv ) == 3 ) {
    $use_config_file = $argv[ 2 ];
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
            
require "../utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

# A 4.2.0 host's people_audit.created_at was written from the column's own
# DEFAULT CURRENT_TIMESTAMP, which is this connection's session time zone --
# not necessarily UTC. 4.3.0's write_audit_row() writes UTC_TIMESTAMP()
# explicitly instead, so a dbinstance imported here is still carrying every
# pre-upgrade row in whatever local zone the 4.2.0 host's MySQL session used.
# This import is the one point in this row's life where every single row
# in the table is known to predate that fix (the new code has not run
# against this data yet), so converting the whole table once, here, is
# exact -- unlike a live migration against a running 4.3.0 host, where some
# rows could already be correctly in UTC and must not be shifted again.
function migrate_people_audit_created_at_to_utc( $db_handle, $db ) {
    $exists = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE 'people_audit'" );
    if ( !$exists || mysqli_num_rows( $exists ) === 0 ) {
        # Predates the audit feature entirely; nothing to migrate.
        return;
    }

    $count_res = mysqli_query( $db_handle, "SELECT COUNT(*) AS n FROM $db.people_audit" );
    $count_row = $count_res ? mysqli_fetch_assoc( $count_res ) : null;
    if ( !$count_row || (int) $count_row[ 'n' ] === 0 ) {
        echo "$db.people_audit is empty; nothing to migrate to UTC\n";
        return;
    }

    # The exporting host's own session time zone, not a value this script
    # guesses or hardcodes: 'SYSTEM' (the common default) defers to the
    # OS's own tz rules via CONVERT_TZ, so DST in the historical data is
    # still handled correctly without needing mysql.time_zone_* loaded.
    $tz_res  = mysqli_query( $db_handle, "SELECT @@session.time_zone AS tz" );
    $tz_row  = $tz_res ? mysqli_fetch_assoc( $tz_res ) : null;
    $from_tz = ( $tz_row && $tz_row[ 'tz' ] !== '' ) ? $tz_row[ 'tz' ] : 'SYSTEM';

    # A named zone (e.g. 'America/Chicago') needs mysql.time_zone_name
    # loaded (mysql_tzinfo_to_sql) to resolve; 'SYSTEM' does not. Rather
    # than let CONVERT_TZ() silently return NULL into a NOT NULL column
    # (which errors the UPDATE, but only after telling the operator nothing
    # useful), check up front and skip loudly instead.
    if ( $from_tz !== 'SYSTEM' ) {
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

    $cmd = "UPDATE $db.people_audit"
         . " SET created_at = CONVERT_TZ( created_at, '$from_tz_esc', 'UTC' )";
    $res = mysqli_query( $db_handle, $cmd );
    if ( !$res ) {
        error_exit( "db query failed : $cmd\ndb query error: " . mysqli_error( $db_handle ) );
    }

    # CONVERT_TZ() returns NULL for any row it cannot resolve rather than
    # failing the query outright; the column's NOT NULL constraint would
    # catch that as an error above in practice, but affected_rows below
    # confirms every row was actually touched, not silently left alone.
    $affected = mysqli_affected_rows( $db_handle );
    if ( $affected !== (int) $count_row[ 'n' ] ) {
        echo "WARNING: $db.people_audit: expected to convert " . $count_row[ 'n' ]
           . " row(s) but $affected were reported changed -- verify by hand.\n";
    }
}

# main

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
    error_exit( 
        "create a file '$myconf' in the current directory with the following contents:\n"
        . "[client]\n"
        . "password=YOUR_ROOT_DB_PASSWORD\n"
        . "max_allowed_packet=256M\n"
        );
}
file_perms_must_be( $myconf );

$pkgname = "export-full-$use_dbhost.tar";
if ( !file_exists( $pkgname ) ) {
    error_exit( "Package file '$pkgname' not found. Terminating\n" );
}

# parse ini for us3php 
if ( file_exists( $us3ini ) ) {
    # assume lims
    $us3php = "us3php";
    $cfgs   = parse_ini_file( $us3ini, true );
    if ( !isset( $cfgs[ $us3php ] ) ) {
        error_exit( "user $us3php not found in $us3ini" );
    }
    if ( !isset( $cfgs[ 'gfac' ] ) ) {
        error_exit( "user gfac not found in $us3ini" );
    }
    $us3phppw = $cfgs[ $us3php ][ 'password' ];
    $gfacpw   = $cfgs[ 'gfac' ][ 'password' ];
} else {
    error_exit( "file $us3ini not found" );
}

$workdir = newfile_dir_init( "import-$use_dbhost" );

if ( !chdir( $workdir ) ) {
    error_exit( "could not change to directory $workdir" );
}

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

$cmd = "tar xf ../$pkgname";
echo "starting: extracting $pkgname in $workdir\n";
run_cmd( $cmd );
echo "finished: extracting $pkgname in $workdir\n";

$dbnames_used = array_fill_keys( existing_dbs(), 1 );

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# first check if any expected outputs exist!
$errors = "";

foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "export-$use_dbhost-$db.sql";
    $cdumpfile = "$dumpfile.$compressext";
    if ( !file_exists( $cdumpfile ) ) {
        $errors .= "Missing file in tar package '$cdumpfile'\n";
    }
    $autoincfile = "export-$use_dbhost-$db-autoincrements.sql";
    if ( !file_exists( $autoincfile ) ) {
        $errors .= "Missing file in tar package '$autoincfile'\n";
    }
}

echo "All expected files in $pkgname extracted\n";

if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
}

# verfiy existing metadata
echo "Checking for valid metadata\n";
foreach ( $dbnames_used as $db => $v ) {
    $query = "select * from newus3.metadata where dbname='$db'";
    check_db();
    db_obj_result( $db_handle, $query );
}
echo "All metadata found\n";
echoline( '=' );

get_yn_answer( "Have you made a binary backup using uslims_db_binary_backup.php?", true );
get_yn_answer( "Are you really sure the binary backup is good?", true );

## ---> VERIFY dbs in metadata, also in stage1 !

if ( get_yn_answer( "drop existing dbinstance from the database (THIS CAN NOT BE UNDONE!)?" ) ) {
    # checked on database rename to backup old, but was reported dangerous!
    foreach ( $dbnames_used as $db => $v ) {
        echoline();
        echo "Dropping dbinstance: $db\n";
        $query = "drop database $db";
        check_db();
        db_obj_result( $db_handle, $query );
    }
}

# make sure the db's don't already exist!
$existing_dbs = array_fill_keys( existing_dbs(), 1 );

$errors = "";
foreach ( $dbnames_used as $db => $val ) {
    if ( array_key_exists( $db, $existing_dbs ) ) {
        $errors .= "$db already exists in database\n";
    }
}
if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
}

# create dbinstances

$cfgs   = parse_ini_file( $us3ini, true );



if ( get_yn_answer( "create dbinstances?" ) ) {
    foreach ( $dbnames_used as $db => $val ) {
        echoline();
        echo "Creating dbinstance: $db\n";
        $sqldata = "export-$use_dbhost-$db.sql.$compressext";
        if ( !file_exists( $sqldata ) ) {
            error_exit( "File '$sqldata' missing." );
        }
# get metadata
        $query = "select * from newus3.metadata where dbname='$db'";
        check_db();
        $res = db_obj_result( $db_handle, $query );
        $dbuser      = $res->{ 'dbuser' };
        $dbpasswd    = $res->{ 'dbpasswd' };
        $secure_user = $res->{ 'secure_user' };
        $secure_pw   = $res->{ 'secure_pw' };
        $querys = [
    "CREATE database $db",
        ];
        foreach ( $querys as $q ) {
            # echo "query: $q\n";
            check_db();
            $res = mysqli_query( $db_handle, $q );
            if ( !$res ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
        }
        $cmds = [
    "cd $limsdbpath && mysql --defaults-file=$cwd/my.cnf -u root $db < us3.sql"
    ,"cd $limsdbpath && mysql --defaults-file=$cwd/my.cnf -u root $db < us3_procedures.sql"
        ];
        foreach ( $cmds as $c ) {
            echo "running: $c\n";
            $res = run_cmd( $c );
            if ( trim( $res ) != '' ) {
                echo "command returns: $res\n";
            }
        }
        $querys = [
    "GRANT ALL ON $db.* TO '$dbuser'@'localhost' IDENTIFIED BY '$dbpasswd'"
    ,"GRANT ALL ON $db.* TO '$dbuser'@'%' IDENTIFIED BY '$dbpasswd'"
    ,"GRANT EXECUTE ON $db.* TO '$secure_user'@'%' IDENTIFIED BY '$secure_pw' REQUIRE SSL"
    ,"GRANT ALL ON $db.* TO 'us3php'@'localhost' IDENTIFIED by '$us3phppw'"
    ,"GRANT ALL ON $db.* TO 'us3php'@'$use_dbhost' IDENTIFIED by '$us3phppw'"
    ,"GRANT SELECT,INSERT,UPDATE ON $db.* TO 'gfac'@'localhost' IDENTIFIED by '$gfacpw'"
    ,"delete from $db.abstractCenterpiece"
    ,"delete from $db.abstractRotor"
    ,"delete from $db.bufferComponent"
    ,"delete from $db.editedData"
    ,"delete from $db.lab"
    ,"delete from $db.rotor"
    ,"delete from $db.rotorCalibration"
        ];
        foreach ( $querys as $q ) {
    # echo "query: $q\n";
            check_db();
            $res = mysqli_query( $db_handle, $q );
            if ( !$res ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
        }

        $cmds = [
    "$uncompresswith $sqldata | mysql --defaults-file=$cwd/my.cnf -u root $db"
    ,"mysql --defaults-file=$cwd/my.cnf -u root $db < export-$use_dbhost-$db-autoincrements.sql"
        ];
        foreach ( $cmds as $c ) {
            echo "running: $c\n";
            $res = run_cmd( $c );
            if ( trim( $res ) != '' ) {
                echo "command returns: $res\n";
            }
        }

        check_db();
        migrate_people_audit_created_at_to_utc( $db_handle, $db );
    }
}

# verify table record counts

echoline();
echo "Verifying record counts\n";
foreach ( $dbnames_used as $db => $val ) {
    echoline();
    echo "Verifying record counts for $db\n";
    $e_reccount = "export-$use_dbhost-$db-record-counts.txt";
    $i_reccount = "import-$use_dbhost-$db-record-counts.txt";
    $cmd = "php ../../table_record_counts.php $db ../../db_config.php > $i_reccount";
    run_cmd( $cmd );
    if ( !file_exists( $i_reccount ) ) {
        echo  "ERROR: could not create '$i_reccount'\n";
    } else {
        echoline();
        $cmd = "diff $e_reccount $i_reccount";
        echo "$cmd :\n";
        echo run_cmd( $cmd, false );
    }
}
