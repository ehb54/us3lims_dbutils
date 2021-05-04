<?php

$self = __FILE__;
# developer defines
$logging_level = 2;
# end of developer defines

# config
$limsdbpath = "/home/us3/lims/database/sql";
$htmlpath   = "/srv/www/htdocs/uslims3";
$uncompresswith = "xzcat";
$compressext  = "xz";

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

# main

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" .
   "[client]\n" .
   "password=YOUR_ROOT_DB_PASSWORD\n"
   );
}
file_perms_must_be( $myconf );

$pkgname = "export-full-$use_dbhost.tar";
if ( !file_exists( $pkgname ) ) {
    error_exit( "Package file '$pkgname' not found. Terminating\n" );
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
}

echo "All expected files in $pkgname extracted\n";

if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
}

error_exit( "not yet" );

## ---> VERIFY dbs in metadata, also in stage1 !

if ( get_yn_answer( "drop existing dbinsts from the database?" ) ) {
# mysql for doing this
# checked on database rename to backup old, but was reported dangerous!
}

# make sure the db's don't already exist!
$res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
$existing_dbs = [];
while( $row = mysqli_fetch_array($res) ) {
    $this_db = (string)$row[0];
    if ( $this_db != "uslims3_global" ) {
        $existing_dbs[ $this_db ] = 1;
    }
}

# debug_json( "exitsing_dbs", $existing_dbs );

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

if ( get_yn_answer( "create dbinstances?" ) ) {
    foreach ( $dbnames_used as $db => $val ) {
        $sqldata = "export-$use_dbhost-$db.sql.$compressext";
        if ( !file_exists( $sqldata ) ) {
            error_exit( "File '$sqldata' missing. Terminating" );
        }
# get metadata
        $query = "select * from newus3.metadata where dbname='$db'";
        $res = db_obj_result( $db_handle, $query );
        $dbuser      = $res->{ 'dbuser' };
        $dbpasswd    = $res->{ 'dbpasswd' };
        $secure_user = $res->{ 'secure_user' };
        $secure_pw   = $res->{ 'secure_pw' };
        $querys = [
    "CREATE database $db",
    "GRANT ALL ON $db.* TO '$dbuser'@'localhost' IDENTIFIED BY '$dbpasswd'",
    "GRANT ALL ON $db.* TO '$dbuser'@'%' IDENTIFIED BY '$dbpasswd'",
    "GRANT EXECUTE ON $db.* TO '$secure_user'@'%' IDENTIFIED BY '$secure_pw' REQUIRE SSL",
    "GRANT ALL ON $db.* TO 'us3php'@'localhost'",
    "GRANT ALL ON $db.* TO 'us3php'@'$use_dbhost'"
        ];
        foreach ( $querys as $q ) {
    # echo "query: $q\n";
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
    "delete from $db.abstractCenterpiece"
    ,"delete from $db.abstractRotor"
    ,"delete from $db.bufferComponent"
    ,"delete from $db.editedData"
    ,"delete from $db.lab"
    ,"delete from $db.rotor"
    ,"delete from $db.rotorCalibration"
        ];
        foreach ( $querys as $q ) {
    # echo "query: $q\n";
            $res = mysqli_query( $db_handle, $q );
            if ( !$res ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
        }

        $cmds = [
    "$uncompresswith $sqldata | mysql --defaults-file=$cwd/my.cnf -u root $db"
        ];
        foreach ( $cmds as $c ) {
            echo "running: $c\n";
            $res = run_cmd( $c );
            if ( trim( $res ) != '' ) {
                echo "command returns: $res\n";
            }
        }
    }
}

