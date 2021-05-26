<?php

$self = __FILE__;
$hdir = __DIR__;

$compresswith = "gzip";
$compressext  = "gz";

# $debug = 1;

$notes = <<<__EOD
usage: $self {config_file}

does importable mysql dumps of every uslims3_% database found
if config_file specified, it will be used instead of ../db config
produces multiple compressed files
my.cnf must exist in the current directory

__EOD;

$u_argv = $argv;
array_shift( $u_argv );

if ( count( $u_argv ) < 0 || count( $u_argv ) > 1 ) {
    echo $notes;
    exit;
}

$config_file = "db_config.php";
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
            
include "utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

$errors = "";
if ( !isset( $backup_dir ) ) {
    $errors .= "\$backup_dir is not set in $use_config_file\n";
}
if ( !isset( $backup_count ) ) {
    $errors .= "\$backup_count is not set in $use_config_file\n";
}
if ( !isset( $backup_sql_rev) ) {
    $errors .= "\$backup_sql_rev is not set in $use_config_file\n";
}
if ( strlen( $errors ) ) {
    error_exit( $errors );
}

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

$schema_file = "schema_rev$backup_sql_rev.sql";
if ( !file_exists( $schema_file ) ) {
    error_exit( "$schema_file not found" );
}

$dbnames_used = array_fill_keys( existing_dbs(), 1 );

$date = trim( run_cmd( 'date +"%y%m%d"' ) );

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
   mkdir( $backup_dir );
}

echo "hdir is $hdir\n";

if ( !chdir( $backup_dir ) ) {
   error_exit( "Could not change to directory $newfile_dir" );
}

# first check if any expected outputs exist!
$errors = "";

# get data
echoline( '=' );
echo "exporting data\n";
echoline();

$extra_files = [];
foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "$db-dump-$date.sql";
    $cmd = "mysqldump --defaults-file=$hdir/my.cnf -u root --no-create-info --complete-insert";
    $retval = trim( run_cmd( "cd $hdir && php uslims_table_diffs.php --only-extras --rev $backup_sql_rev $db" ) );
    if ( strlen( $retval ) ) {
        $ignore_tables = explode( "\n", $retval );
    } else {
        $ignore_tables = [];
    }
    if ( count( $ignore_tables ) ) {
        $tables_ignored       = implode( "\n", $ignore_tables ) . "\n";
        $tables_ignored_fname = "export-$use_dbhost-$db-tables-ignored.txt";
#        file_put_contents( $tables_ignored_fname, $tables_ignored );
#        $extra_files[] = $tables_ignored_fname;
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
 
# check for old backups
foreach ( $dbnames_used as $db => $val ) {
    $cmd = "ls -1t $db-dump*.sql.$compressext";
    $result = array_slice( explode( "\n", trim( run_cmd( $cmd ) ) ), $backup_count );

    # debug_json( "result for $db:", $result );
    foreach ( $result as $v ) {
        unlink( $v );
    }
}


echoline( '=' );
echo "completed: backup files are in $backup_dir\n";
echoline( '=' );

