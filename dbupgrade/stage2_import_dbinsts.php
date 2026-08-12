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

require "../utility.php";

$notes = <<<__EOD
usage: $self {options} dbhost {config_file}

1. extracts tarfile into unique directory
2. drops databases
3. recreates databases 
4. imports data

the databases processed are those contained in the export package, not those
currently present in the server, so a run interrupted partway can be resumed
without silently skipping the databases it had already dropped

if config_file specified, it will be used instead of ../db config
my.cnf must exist in the current directory

Options

--help                 : print this information and exit
--db                   : limit to this db (can be specified multiple times)
--only-missing         : limit to dbs in the package not currently in the server
--workdir              : reuse an already extracted package directory instead of
                         extracting the tarfile again

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs             = [];
$only_missing        = false;
$use_workdir         = "";

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--only-missing": {
            array_shift( $u_argv );
            $only_missing = true;
            break;
        }
        case "--workdir": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $use_workdir = array_shift( $u_argv );
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }
}

if ( count( $u_argv ) < 1 || count( $u_argv ) > 2 ) {
    echo $notes;
    exit;
}

$use_dbhost = array_shift( $u_argv );

$config_file = "../db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
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
            
file_perms_must_be( $use_config_file );
require $use_config_file;

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
if ( !strlen( $use_workdir ) && !file_exists( $pkgname ) ) {
    error_exit( "Package file '$pkgname' not found. Terminating\n" );
}

if ( strlen( $use_workdir ) && !is_dir( $use_workdir ) ) {
    error_exit( "--workdir '$use_workdir' is not a directory. Terminating\n" );
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

if ( strlen( $use_workdir ) ) {
    $workdir = $use_workdir;
} else {
    $workdir = newfile_dir_init( "import-$use_dbhost" );
}

if ( !chdir( $workdir ) ) {
    error_exit( "could not change to directory $workdir" );
}

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

if ( strlen( $use_workdir ) ) {
    echo "reusing already extracted package directory $workdir\n";
} else {
    $cmd = "tar xf ../$pkgname";
    echo "starting: extracting $pkgname in $workdir\n";
    run_cmd( $cmd );
    echo "finished: extracting $pkgname in $workdir\n";
}

# the databases to process come from the package, not from the server: a run that
# failed partway has already dropped databases it never got around to recreating,
# and those must not be silently left out of the retry

$package_dbs = [];
foreach ( glob( "export-$use_dbhost-*.sql.$compressext" ) as $cdumpfile ) {
    $re = '/^export-' . preg_quote( $use_dbhost, '/' ) . '-(.+)\.sql\.' . preg_quote( $compressext, '/' ) . '$/';
    if ( preg_match( $re, $cdumpfile, $matches ) ) {
        $package_dbs[ $matches[ 1 ] ] = 1;
    }
}

if ( !count( $package_dbs ) ) {
    error_exit( "no exported databases found in $workdir. Terminating\n" );
}

$server_dbs = array_fill_keys( existing_dbs(), 1 );

if ( count( $use_dbs ) ) {
    $db_diff = array_diff( $use_dbs, array_keys( $package_dbs ) );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in the export package : " . implode( ' ', $db_diff ) );
    }
}

if ( !count( $use_dbs ) && !$only_missing ) {
    $dbnames_used = $package_dbs;
} else {
    $dbnames_used = [];
    if ( $only_missing ) {
        foreach ( $package_dbs as $db => $val ) {
            if ( !array_key_exists( $db, $server_dbs ) ) {
                $dbnames_used[ $db ] = 1;
            }
        }
    }
    foreach ( $use_dbs as $db ) {
        $dbnames_used[ $db ] = 1;
    }
    if ( !count( $dbnames_used ) ) {
        error_exit( "no databases selected. Terminating\n" );
    }
}

echoline( '=' );
echo "the export package contains " . count( $package_dbs ) . " databases\n";
echo "processing " . count( $dbnames_used ) . " of them as follows\n";
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
        if ( !array_key_exists( $db, $server_dbs ) ) {
            echo "Not in the server, nothing to drop: $db\n";
            continue;
        }
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

# link tables whose composite primary key exists in us3.sql but was never added to
# already deployed databases, which are therefore free to hold duplicate rows

$link_table_pks = [
    "solutionAnalyte"     => "solutionID, analyteID"
    ,"bufferLink"         => "bufferID, bufferComponentID"
    ,"experimentProtocol" => "experimentID, protocolID"
];

$dups_removed = [];

function table_has_primary_key( $db, $table ) {
    global $db_handle;
    check_db();
    $query =
        "select count(*) from information_schema.statistics"
        . " where table_schema='$db' and table_name='$table' and index_name='PRIMARY'";
    $res = db_obj_result( $db_handle, $query );
    return $res->{'count(*)'} > 0;
}

function table_row_count( $db, $table ) {
    global $db_handle;
    check_db();
    $res = db_obj_result( $db_handle, "select count(*) from $db.$table" );
    return (int) $res->{'count(*)'};
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

        # the dump is data only, so old data is loaded into the current us3.sql schema.
        # the link tables listed in $link_table_pks only gained their composite primary
        # key in us3.sql, with no matching alter for databases created before that, so
        # any duplicate rows they accumulated would abort the import. load them without
        # the key and put it back afterwards, collapsing the duplicates.

        $pk_dropped = [];
        foreach ( $link_table_pks as $link_table => $link_pk ) {
            if ( !table_has_primary_key( $db, $link_table ) ) {
                continue;
            }
            check_db();
            $q = "ALTER TABLE $db.$link_table DROP PRIMARY KEY";
            if ( !mysqli_query( $db_handle, $q ) ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
            $pk_dropped[ $link_table ] = $link_pk;
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

        foreach ( $pk_dropped as $link_table => $link_pk ) {
            $before = table_row_count( $db, $link_table );
            check_db();
            $q = "ALTER IGNORE TABLE $db.$link_table ADD PRIMARY KEY ($link_pk)";
            if ( !mysqli_query( $db_handle, $q ) ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
            $after = table_row_count( $db, $link_table );
            if ( $before != $after ) {
                $removed = $before - $after;
                echo "NOTICE: $db.$link_table : $removed duplicate row(s) removed to restore PRIMARY KEY ($link_pk)\n";
                $dups_removed[] = "$db.$link_table : $removed of $before row(s)";
            }
        }
    }
}

if ( count( $dups_removed ) ) {
    echoline( '=' );
    echo "duplicate link table rows removed during import:\n";
    echoline();
    echo implode( "\n", $dups_removed );
    echo "\nthese account for the corresponding record count differences reported below\n";
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
