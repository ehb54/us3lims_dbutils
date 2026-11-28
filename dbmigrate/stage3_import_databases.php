<?php

$self = __FILE__;
# developer defines
$logging_level = 2;
# end of developer defines

# config
$limsdbpath     = "/home/us3/lims/database/sql";
$htmlpath       = "/srv/www/htdocs/uslims3";
$uncompresswith = "xzcat";
$compressext    = "xz";

$us3home        = "/home/us3";
$us3ini         = "$us3home/lims/.us3lims.ini";

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
when importing metadata, use php option -d mysqli.allow_local_infile=On 


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
            
require "../utility.php";
require_once "../instance_grants.php";
require_once "../row_versions.php";
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

$workdir = "import-$export_dbhost";
$pkgname = "export-full-$export_dbhost.tar";
$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );

if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

if ( !ini_get("mysqli.allow_local_infile") ) {
    echo "WARNING: metadata will not be importable since you did not specify php -d mysqli.allow_local_infile=On on the command line\n";
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

if ( get_yn_answer( "extract tar?" ) ) {
    if ( !file_exists( $pkgname ) ) {
        error_exit( "Package file '$pkgname' not found. Terminating\n" );
    }

    if ( file_exists( $workdir ) ) {
        error_exit( "$workdir exists, remove or rename" );
    }

    if ( !mkdir( $workdir ) ) {
        error_exit( "could not make directory $workdir" );
    }

    if ( !chdir( $workdir ) ) {
        error_exit( "could not change to directory $workdir" );
    }

    $cmd = "tar xf ../$pkgname";
    echo "starting: extracting $pkgname in $workdir\n";
    run_cmd( $cmd );
    echo "finished: extracting $pkgname in $workdir\n";
} else {
    if ( !chdir( $workdir ) ) {
        error_exit( "could not change to directory $workdir" );
    }
}

$metadata_file = "metadata-$export_dbhost.xml";

if ( !file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file does not exist!" );
}

$metadata_rename_file = "metadata-$export_dbhost-renamed.xml";
if ( file_exists( $metadata_rename_file ) ) {
    echo "NOTICE: using renamed dbnames from $metadata_rename_file\n";
    $metadata_file = $metadata_rename_file;
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

# load metadata

if ( get_yn_answer( "load metadata?" ) ) {
    if ( !ini_get("mysqli.allow_local_infile") ) {
        error_exit( "metadata will not be importable since you did not specify php -d mysqli.allow_local_infile=On on the command line\n" );
    }
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

if ( get_yn_answer( "create dbinstances" ) ) {
    # A dump that does not fit this host's schema would fail partway through the list.
    echo "Checking every dump against this host's us3.sql\n";
    $dumps = [];
    foreach ( $dbnames_used as $db => $val ) {
        $dumps[ $db ] = [ "export-$export_dbhost-$db.sql.$compressext", "export-$export_dbhost-$db-autoincrements.sql" ];
    }
    $errors = preflight_instance_imports( $db_handle, "mysql --defaults-file=$cwd/my.cnf -u root", $limsdbpath, $dumps );
    if ( strlen( $errors ) ) {
        error_exit( "ERRORS (nothing was created):\n" . $errors . "Terminating" );
    }
    echo "All dumps fit\n";
    ## Grants before any database is created: a grant that fails stops the run here, with
    ## nothing created (instance_grants.php).
    echo "Applying every dbinstance's grants\n";
    $metas = [];
    foreach ( $dbnames_used as $db => $val ) {
        $metas[ $db ] = db_obj_result( $db_handle, "select * from newus3.metadata where dbname='$db'" );
    }
    $errors = apply_instance_grants( $db_handle, $metas, $us3phppw, $gfacpw );
    if ( strlen( $errors ) ) {
        error_exit( "ERRORS (nothing was created):\n" . $errors . "Terminating" );
    }
    echo "All grants applied\n";
    $create_php = get_yn_answer( "clone php and makeconfig" );
    foreach ( $dbnames_used as $db => $val ) {
        echoline( '=' );
        echo "Importing $db\n";
        echoline();
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
        ## the grants were applied before this loop
        $querys = [
    "CREATE database $db"
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

        ## The data-only dump carries rows for objects us3.sql does not define.
        if ( !prepare_imported_instance_schema( $db_handle, $db ) ) {
            error_exit( "could not prepare $db for import: " . mysqli_error( $db_handle ) );
        }
        ## An older dump's data may break keys this release's us3.sql has; they are put back after it.
        list( $relaxed, $why ) = row_version_import_relax( $db_handle, $db );
        if ( !$relaxed ) {
            error_exit( "could not prepare $db for import: $why" );
        }

        $cmds = [
    "$uncompresswith $sqldata | mysql --defaults-file=$cwd/my.cnf -u root $db"
    ,"mysql --defaults-file=$cwd/my.cnf -u root $db < export-$export_dbhost-$db-autoincrements.sql"
            ];
        foreach ( $cmds as $c ) {
            echo "running: $c\n";
            $res = run_cmd( $c );
            if ( trim( $res ) != '' ) {
                echo "command returns: $res\n";
            }
        }

        ## The repairs and backfills an in-place upgrade makes, which also put the keys back
        check_db();
        list( $finished, $why ) = row_version_import_finish( $db_handle, $db, $limsdbpath );
        if ( !$finished ) {
            error_exit( "$db: the imported data needs repair before its keys can be restored ($why). Fix those rows in $db,"
                      . " then run uslims_upgrade.php, whose step 9 finishes its row versions" );
        }

        ## Then the loading order's last two steps: counters above the imported rows, then the
        ## row-version triggers, so imported versions and attribution are kept as they were.
        foreach ( [ 'us3_counter_reset.sql', 'us3_row_version_triggers.sql' ] as $file ) {
            if ( !file_exists( "$limsdbpath/$file" ) ) {
                continue;
            }
            $c = "cd $limsdbpath && mysql --defaults-file=$cwd/my.cnf -u root $db < $file";
            echo "running: $c\n";
            $res = run_cmd( $c );
            if ( trim( $res ) != '' ) {
                echo "command returns: $res\n";
            }
        }

        ## Every versioned table has its columns and both triggers before they are recorded as loaded
        $manifest = row_version_manifest( $limsdbpath );
        $problems = $manifest === null ? [ "$limsdbpath/us3_row_version_tables.tsv cannot be read" ]
                                       : row_version_problems( $db_handle, $db, $manifest, true );
        if ( $problems ) {
            error_exit( "$db: row versions incomplete after the import: " . implode( '; ', $problems ) );
        }

        ## So the next uslims_upgrade.php --apply does not load them again. Recorded as the instance
        ## account, which reads the routines as that check does (a non-definer sees no bodies).
        $inst_link = @mysqli_connect( $dbhost, $dbuser, $dbpasswd, $db );
        if ( !$inst_link || !record_instance_procedures_marker( $inst_link, $db, $limsdbpath ) ) {
            echo "note: could not record $db's stored procedures and triggers as this release's; the next uslims_upgrade.php --apply reloads them\n";
        }
        if ( $inst_link ) {
            mysqli_close( $inst_link );
        }
        if ( $create_php ) {
            $cmds = [
    "git clone https://github.com/ehb54/us3lims_dbinst.git $htmlpath/$db"
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
}

