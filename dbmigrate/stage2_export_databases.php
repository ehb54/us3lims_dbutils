<?php

$self = __FILE__;

$compresswith = "xz";
$compressext  = "xz";

# $debug = 1;

$notes = <<<__EOD

usage: $self {options} metadata_dbhost sql-git-rev# {config_file}

does importable mysql dumps of every database in the metadata file
if config_file specified, it will be used instead of ../db config
produces multiple compressed files and a tar file
my.cnf must exist in the current directory

Options

--help                           : print this information and exit

--rename from_db_name to_db_name : optionally rename specific databases, can be specified multiple times


__EOD;

require "../utility.php";

$u_argv = $argv;
array_shift( $u_argv );

$db_rename = [];

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--rename": {
            array_shift( $u_argv );
            if ( count( $u_argv ) < 2 ) {
               error_exit( "--rename requires two arguments" );
            }
            $from_name = array_shift( $u_argv );
            $to_name   = array_shift( $u_argv );
            if ( array_key_exists( $from_name, $db_rename ) ) {
                error_exit( "ERROR: --rename $from_name $to_name duplicate from name specified" );
            }
            if ( in_array( $to_name, $db_rename ) ) {
                error_exit( "ERROR: --rename $from_name $to_name duplicate to name specified" );
            }
            if ( !preg_match( '/^uslims3_/', $from_name ) || !preg_match( '/^uslims3_/', $to_name ) ) {
                error_exit( "ERROR: --rename $from_name $to_name must both begin with uslims3_" );
            }
            if ( $from_name == $to_name ) {
                error_exit( "ERROR: --rename $from_name $to_name are both the same name" );
            }    
            $db_rename[ $from_name ] = $to_name;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( count( $u_argv ) < 2 || count( $u_argv ) > 3 ) {
    echo $notes;
    exit;
}

$metadata_dbhost = array_shift( $u_argv );
$schema_rev      = array_shift( $u_argv );

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

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}    
            
file_perms_must_be( $use_config_file );
require $use_config_file;

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the current directory with the following contents:\n"
       . "[client]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "[mysqldump]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "max_allowed_packet=256M\n"
       );
}
file_perms_must_be( $myconf );

$pkgname = "export-full-$metadata_dbhost.tar";
if ( file_exists( $pkgname ) ) {
    error_exit( "You must move or remove '$pkgname'. Terminating\n" );
}

$metadata_file = "metadata-$metadata_dbhost.xml";

if ( !file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file does not exist!" );
}

$metadata = simplexml_load_string( file_get_contents( $metadata_file ) );
if ( isset( $debug ) && $debug ) {
    debug_json( "json of metadata", $metadata );
}

$extra_files = [];

if ( count( $db_rename ) ) {
    $metadata_rename = simplexml_load_string( file_get_contents( $metadata_file ) );
    $metadata_rename_file = "metadata-$metadata_dbhost-renamed.xml";
    $metadata_done   = [];
    foreach ( $metadata_rename->row as $row ) {
        $f_dbname = (string) $row->field[2];
        echo "f_dbname = $f_dbname\n";
        if ( array_key_exists( $f_dbname, $db_rename ) ) {
            $t_dbname      = $db_rename[ $f_dbname ];
            $t_inst        = preg_replace( '/^uslims3_/', '', $t_dbname );
            $t_inst_full   = preg_replace( '/\(.*$/', '', $row->field[0] ) . " ($t_inst)";
            $t_dbuser      = "${t_inst}_user";
            $t_secure_user = "${t_inst}_sec";

            $row->field[0] = $t_inst_full;
            $row->field[1] = $t_inst;
            $row->field[2] = $t_dbname;
            $row->field[3] = $t_dbuser;
            $row->field[5] = $t_secure_user;
        }
        $metadata_done[ $f_dbname ] = 1;
    }
    $notfound = [];
    foreach ( $db_rename as $k => $v ) {
        if ( !array_key_exists( $k, $metadata_done ) ) {
            $notfound[] = $k;
        }
    }
    if ( count( $notfound ) ) {
        error_exit( "missing databases for specified --rename : " . implode( ' ', $notfound ) );
    }
    if ( !file_put_contents( $metadata_rename_file, $metadata_rename->asXML() ) ) {
        error_exit( "could not create $metadata_rename_file" );
    }
    $extra_files[] = $metadata_rename_file;
}

$schema_file = "../schema_rev$schema_rev.sql";
if ( !file_exists( $schema_file ) ) {
    error_exit( "$schema_file not found" );
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
    if ( array_key_exists( $db, $db_rename ) ) {
        $to_db = $db_rename[ $db ];
    } else {
        $to_db = $db;
    }
    $dumpfile = "export-$metadata_dbhost-$to_db.sql";
    $cdumpfile = "$dumpfile.$compressext";
    if ( file_exists( $dumpfile ) ) {
        $errors .= "You must move or remove '$dumpfile'\n";
    }
    if ( file_exists( $cdumpfile ) ) {
        $errors .= "You must move or remove '$cdumpfile'\n";
    }
}

# check config.php's
$srvdir = "/srv/www/htdocs/uslims3";
foreach ( $dbnames_used as $db => $val ) {
    if ( array_key_exists( $db, $db_rename ) ) {
        $to_db = $db_rename[ $db ];
    } else {
        $to_db = $db;
    }
    $configphp = "$srvdir/$db/config.php";
    $destphp   = "export-$metadata_dbhost-$to_db-config.php";
    if ( !file_exists( $configphp ) ) {
        $errors = "file missing: $configphp\n";
    }
    if ( file_exists( $destphp ) ) {
        $errors = "You must move or remove '$destphp'\n";
    }
}

if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
}

# get config.php's

$configphps = [];
foreach ( $dbnames_used as $db => $val ) {
    if ( array_key_exists( $db, $db_rename ) ) {
        $to_db = $db_rename[ $db ];
    } else {
        $to_db = $db;
    }
    $configphp = "$srvdir/$db/config.php";
    $destphp   = "export-$metadata_dbhost-$to_db-config.php";
    $cmd = "cp $configphp $destphp";
    run_cmd( $cmd );
    if ( !file_exists( $destphp ) ) {
        error_exit( "missing '$destphp'\n" );
    }
    $configphps[] = $destphp;
}

# get record counts

$reccounts = [];
foreach ( $dbnames_used as $db => $val ) {
    if ( array_key_exists( $db, $db_rename ) ) {
        $to_db = $db_rename[ $db ];
    } else {
        $to_db = $db;
    }
    $reccount = "export-$metadata_dbhost-$to_db-record-counts.txt";
    echo "starting: table record counts from $db to $reccount\n";
    $cmd = "php ../table_record_counts.php $db ../db_config.php > $reccount";
    run_cmd( $cmd );
    if ( !file_exists( $reccount ) ) {
        error_exit( "missing '$reccount'\n" );
    }
    echo "finished: table record counts from $db to $reccount\n";
    $reccounts[] = $reccount;
}
# get data

echoline( '=' );
echo "exporting data\n";
echoline();

foreach ( $dbnames_used as $db => $val ) {
    if ( array_key_exists( $db, $db_rename ) ) {
        $to_db = $db_rename[ $db ];
    } else {
        $to_db = $db;
    }
    $dumpfile = "export-$metadata_dbhost-$to_db.sql";
    $cmd = "mysqldump --defaults-file=my.cnf -u root --no-create-info --complete-insert";
    $retval = trim( run_cmd( "cd .. && php uslims_table_diffs.php --only-extras --rev $schema_rev $db" ) );
    if ( strlen( $retval ) ) {
        $ignore_tables = explode( "\n", $retval );
    } else {
        $ignore_tables = [];
    }
    if ( count( $ignore_tables ) ) {
        $tables_ignored       = implode( "\n", $ignore_tables ) . "\n";
        $tables_ignored_fname = "export-$metadata_dbhost-$db-tables-ignored.txt";
        file_put_contents( $tables_ignored_fname, $tables_ignored );
        $extra_files[] = $tables_ignored_fname;
        echo "WARNING : " . count( $ignore_tables ) . " tables ignored in $db : " . implode( ' ', $ignore_tables ) . "\n";
    }
    foreach ( $ignore_tables as $ignore_table ) {
        $cmd .= " --ignore-table=$db.$ignore_table";
    }
    $cmd .= " $db > $dumpfile";
    echo "starting: exporting $db to $dumpfile\n";
    run_cmd( $cmd );
    if ( !file_exists( $dumpfile ) ) {
        error_exit( "Error exporting '$dumpfile' terminating" );
    }
    echo "complete: exporting $db to $dumpfile\n";
    $dumped[] = $dumpfile;

    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        error_exit( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    $cdumped[] = $cdumpfile;

    $autoincfile = "export-$metadata_dbhost-$to_db-autoincrements.sql";
    $cmd = "php ../uslims_autoincrements.php --list --db $db --sql --sqlnodb $config_file > $autoincfile";
    echo "starting: exporting $db autoincrements to $autoincfile\n";
    run_cmd( $cmd );
    if ( !file_exists( $autoincfile ) ) {
        error_exit( "Error creating '$autoincfile' terminating" );
    }
    echo "completed: exporting $db autoincrements to $autoincfile\n";
    $extra_files[] = $autoincfile;

}

# package

$cmd = "tar cf $pkgname $metadata_file " . implode( ' ', $cdumped ) . ' ' . implode( ' ', $configphps ) . ' ' . implode( ' ', $reccounts ) . ' ' . implode( ' ', $extra_files );
echo "starting: building complete package $pkgname\n";
run_cmd( $cmd );
if ( !file_exists( $pkgname ) ) {
    error_exit( "Error  creating '$pkgname' terminating" );
}
echo "completed: building complete package $pkgname\n";
echoline( '=' );
echo "Copy $pkgname to the new host and run stage 3 on the new host\n";


