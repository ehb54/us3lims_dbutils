<?php

$self = __FILE__;

$compresswith = "xz";
$compressext  = "xz";

# $debug = 1;

$notes = <<<__EOD
usage: $self metadata_dbhost sql-git-rev# {config_file}

does importable mysql dumps of every database in the metadata file
if config_file specified, it will be used instead of ../db config
produces multiple compressed files and a tar file
my.cnf must exist in the current directory


__EOD;

$u_argv = $argv;
array_shift( $u_argv );

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
            
require "../utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the current directory with the following contents:\n"
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
    $dumpfile = "export-$metadata_dbhost-$db.sql";
    $cdumpfile = "$dumpfile.$compressext";
    if ( file_exists( $dumpfile ) ) {
        $errors .= "You must move or remove '$dumpfile'\n";
    }
    if ( file_exists( $cdumpfile ) ) {
        $errors .= "You must move or remove '$cdumpfile'\n";
    }
}

# get config.php's
$srvdir = "/srv/www/htdocs/uslims3";
foreach ( $dbnames_used as $db => $val ) {
    $configphp = "$srvdir/$db/config.php";
    $destphp   = "export-$metadata_dbhost-$db-config.php";
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
    $configphp = "$srvdir/$db/config.php";
    $destphp   = "export-$metadata_dbhost-$db-config.php";
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
    $reccount = "export-$metadata_dbhost-$db-record-counts.txt";
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

$extra_files = [];
foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "export-$metadata_dbhost-$db.sql";
    $cmd = "mysqldump --defaults-file=my.cnf -u root --no-create-info --complete-insert";
    $ignore_tables = explode( "\n", trim( run_cmd( "cd .. && php uslims_table_diffs.php --only-extras --rev $schema_rev $db" ) ) );
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
}

# package

$cmd = "tar cf $pkgname $metadata_file " . implode( ' ', $cdumped ) . ' ' . implode( ' ', $configphps ) . ' ' . implode( ' ', $reccounts ) . implode( ' ', $extra_files );
echo "starting: building complete package $pkgname\n";
run_cmd( $cmd );
if ( !file_exists( $pkgname ) ) {
    error_exit( "Error  creating '$pkgname' terminating" );
}
echo "completed: building complete package $pkgname\n";
echoline( '=' );
echo "Copy $pkgname to the new host and run stage 3 on the new host\n";


