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
usage: $self export_dbhost this_dbhost ipaddr {config_file}

1. extracts tarfile (warning: could clobber)
2. verifies metadata dbinst uniqueness
3. creates databases
4. imports data

if config_file specified, it will be used instead of ../db config
writes to ...
my.cnf must exist in the current directory

__EOD;

if ( count( $argv ) < 4 || count( $argv ) > 6 ) {
    echo $notes;
    exit;
}

$export_dbhost = $argv[ 1 ];
$this_dbhost   = $argv[ 2 ];
$this_ipaddr   = $argv[ 3 ];

$config_file = "../db_config.php";
if ( count( $argv ) == 5 ) {
    $use_config_file = $argv[ 4 ];
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

# end utility routines
# main

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" .
   "[client]\n" .
   "password=YOUR_ROOT_DB_PASSWORD\n"
   );
}

$pkgname = "export-full-$export_dbhost.tar";
if ( !file_exists( $pkgname ) ) {
    error_exit( "Package file '$pkgname' not found. Terminating\n" );
}

$workdir = "import-$export_dbhost";
if ( file_exists( $workdir ) ) {
    error_exit( "$workdir exists, remove or rename" );
}

if ( !mkdir( $workdir ) ) {
    error_exit( "could not make directory $workdir" );
}

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

$metadata_file = "metadata-$export_dbhost.xml";

if ( !file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file does not exist!" );
}


$metadata = simplexml_load_string( file_get_contents( $metadata_file ) );
if ( isset( $debug ) && $debug ) {
    debug_json( "json of metadata", $metadata );
}

$dbnames      = [];
$dbnames_used = [];
$dbnames_dupd = [];

foreach ( $metadata->{'row'} as $row ) {
     $this_dbname = (string)$row->field[2];
     $dbnames[]   = $this_dbname;
     if ( array_key_exists( $this_dbname, $dbnames_used ) ) {
         $dbnames_dupd[] = $this_dbname;
     }
     $dbnames_used[ $this_dbname ] = 1;
}

if ( count( $dbnames_dupd ) ) {
    echoline( '=' );
    echo "WARNING: the following duplicate dbname records exist!\n" . 
         "---> this *NEEDS* to be corrected before proceeding\n" .
         "---> edit the metadata xml before running stage2\n";
    echoline();
    echo implode( "\n", $dbnames_dupd );
    echo "\n";
    echoline();
    error_exit( "Terminating" );
}

    
echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# first check if any expected outputs exist!
$errors = "";

foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "export-$export_dbhost-$db.sql";
    $cdumpfile = "$dumpfile.$compressext";
    if ( !file_exists( $cdumpfile ) ) {
        $errors .= "Missing file in tar package '$cdumpfile'\n";
    }
}

if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
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

do {
    $answer = readline( "load metadata? (y or n) : " );
} while ( $answer != "y" && $answer != "n" );

# load metadata

if ( $answer == "y" ) {
    echo "loading metadata from $metadata_file\n";
    $query = "LOAD XML LOCAL INFILE '$metadata_file' INTO TABLE newus3.metadata";
    $res = mysqli_query( $db_handle, $query );
    if ( !$res ) {
        write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
    };
    foreach ( $dbnames_used as $db => $val ) {
        $query = "update newus3.metadata set limshost='$this_dbhost', dbhost='$this_dbhost' where dbname='$db'";
        $res = mysqli_query( $db_handle, $query );
        if ( !$res ) {
            write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
        };
    }
}

# create dbinstances

do {
    $answer = readline( "create dbinstances? (y or n) : " );
} while ( $answer != "y" && $answer != "n" );

if ( $answer == "y" ) {
    foreach ( $dbnames_used as $db => $val ) {
        $sqldata = "export-$export_dbhost-$db.sql.$compressext";
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
    "GRANT ALL ON $db.* TO 'us3php'@'$this_dbhost'"
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
    ,"git clone https://github.com/ehb54/us3lims_dbinst.git $htmlpath/$db"
    ,"mkdir $htmlpath/$db/data"
    ,"chmod g+w $htmlpath/$db/data"
    ,"chown -R us3:us3 $htmlpath/$db"
    ,"php /srv/www/htdocs/uslims3/uslims3_newlims/makeconfig.php $db $this_dbhost $this_ipaddr"
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

