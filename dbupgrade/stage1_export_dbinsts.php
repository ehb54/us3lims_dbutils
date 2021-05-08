<?php

$self = __FILE__;

$compresswith = "xz -0";
$compressext  = "xz";

# $debug = 1;

$notes = <<<__EOD
usage: $self dbhost {config_file}

does importable mysql dumps of every uslims3_% database found
if config_file specified, it will be used instead of ../db config
produces multiple compressed files and a tar file
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
            
include "../utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the current directory with the following contents:\n"
       . "[mysqldump]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "max_allowed_packet=128M\n"
       );
}
file_perms_must_be( $myconf );

$pkgname = "export-full-$use_dbhost.tar";
if ( file_exists( $pkgname ) ) {
    error_exit( "You must move or remove '$pkgname'. Terminating\n" );
}

$dbnames_used = array_fill_keys( existing_dbs(), 1 );

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# verfiy existing metadata
echo "Checking for valid metadata\n";
foreach ( $dbnames_used as $k => $v ) {
    $query = "select * from newus3.metadata where dbname='$k'";
    db_obj_result( $db_handle, $query );
}
echo "All metadata found\n";
echoline( '=' );

# make & change to directory
newfile_dir_init( "export-$use_dbhost" );
if ( !chdir( $newfile_dir ) ) {
   error_exit( "Could not change to directory $newfile_dir" );
}

# first check if any expected outputs exist!
$errors = "";

foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "export-$use_dbhost-$db.sql";
    $cdumpfile = "$dumpfile.$compressext";
    if ( file_exists( $dumpfile ) ) {
        $errors .= "You must move or remove '$dumpfile'\n";
    }
    if ( file_exists( $cdumpfile ) ) {
        $errors .= "You must move or remove '$cdumpfile'\n";
    }
}

if ( strlen( $errors ) ) {
    error_exit( "ERRORS:\n" . $errors . "Terminating" );
}

# get record counts

$reccounts = [];
foreach ( $dbnames_used as $db => $val ) {
    $reccount = "export-$use_dbhost-$db-record-counts.txt";
    echo "starting: table record counts from $db to $reccount\n";
    $cmd = "php ../../table_record_counts.php $db ../../db_config.php > $reccount";
    run_cmd( $cmd );
    if ( !file_exists( $reccount ) ) {
        error_exit( "missing '$reccount'\n" );
    }
    echo "finished: table record counts from $db to $reccount\n";
    $reccounts[] = $reccount;
}

# get data

foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "export-$use_dbhost-$db.sql";
    $cmd = "mysqldump --defaults-file=../my.cnf -u root --no-create-info --complete-insert $db > $dumpfile";
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

$cmd = "tar cf ../$pkgname " . implode( ' ', $cdumped ) . ' ' . ' ' . implode( ' ', $reccounts );
echo "starting: building complete package $pkgname\n";
run_cmd( $cmd );
if ( !file_exists( "../$pkgname" ) ) {
    error_exit( "Error  creating '$pkgname' terminating" );
}
echo "completed: building complete package $pkgname\n";
echoline( '=' );
echo "Now run # php stage2_import_dbinsts.php $use_dbhost\n";
