<?php

$self = __FILE__;
$hdir = __DIR__;

$compresswith = "gzip -f";
$compressext  = "gz";

$us3bin    = exec( "ls -d ~us3/lims/bin" );
include_once "$us3bin/listen-config.php";
$rsync_php  = "uslims_daily_rsync.php";

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

$config_file = "$hdir/db_config.php";
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
if ( !isset( $backup_logs ) ) {
    $errors .= "\$backup_logs is not set in $use_config_file\n";
}
if ( !isset( $backup_dir ) ) {
    $errors .= "\$backup_dir is not set in $use_config_file\n";
}
if ( !isset( $backup_count ) ) {
    $errors .= "\$backup_count is not set in $use_config_file\n";
}
if ( !isset( $backup_host ) ) {
    $errors .= "\$backup_host is not set in $use_config_file\n";
}
if ( !isset( $backup_rsync) ) {
    $errors .= "\$backup_rsync is not set in $use_config_file\n";
}
if ( !isset( $backup_user) ) {
    $errors .= "\$backup_user is not set in $use_config_file\n";
}
if ( strlen( $errors ) ) {
    error_exit( $errors );
}

$myconf = "$hdir/my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the current directory with the following contents:\n"
       . "[mysqldump]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "max_allowed_packet=256M\n"
       );
}
file_perms_must_be( $myconf );

if ( !is_admin( false ) ) {
   error_exit( "This program must be run by root or a sudo enabled user" );
}

if ( is_locked( $rsync_php ) ) {
    error_exit( "$rsync_php is currently running" );
} else {
#    echo "$rsync_php is not running\n";
}

// lock
if ( isset( $lock_dir ) ) {
   $lock_main_script_name  = __FILE__;
   require "$us3bin/lock.php";
} 

$dbnames_used = array_fill_keys( existing_dbs(), 1 );

$date = trim( run_cmd( 'date +"%y%m%d%H"' ) );

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
    mkdir( $backup_dir );
    run_cmd( "sudo chown $backup_user:$backup_user $backup_dir" );
}

echo "hdir is $hdir\n";

if ( !chdir( $backup_dir ) ) {
    error_exit( "Could not change to directory $newfile_dir" );
}

$logf = "$backup_logs/$backup_host-$date.log";
if ( !is_dir( $backup_logs ) ) {
    mkdir( $backup_logs, 0700 );
    run_cmd( "sudo chown $backup_user:$backup_user $backup_logs" );
}

run_cmd( "echo $backup_host Mysqldump Error Log : `date` > $logf" );

$errors = "";

# get data
echoline( '=' );
echo "exporting data\n";
echoline();

$extra_files = [];
foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "$db-dump-$date.sql";
    $cmd = "mysqldump --defaults-file=$myconf -u root $db > $dumpfile 2>> $logf";
    echo "starting: exporting $db to $dumpfile\n";
    run_cmd( $cmd );
    if ( !file_exists( $dumpfile ) ) {
        error_exit( "Error exporting '$dumpfile' terminating" );
    }
    echo "complete: exporting $db to $dumpfile\n";
    run_cmd( "sudo chown $backup_user:$backup_user $dumpfile" );
    run_cmd( "sudo chmod 400 $dumpfile" );

    $dumped[] = $dumpfile;

    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        error_exit( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    run_cmd( "sudo chmod 400 $cdumpfile" );
    $cdumped[] = $cdumpfile;
}

## export all GRANTS
# also dump the grant table in the form of a script to re-establish
# be sure to edit and remove problematic ones, i.e., root
{
    $db = "GRANTS";
    $dumpfile = "uslims3_$db-dump-$date.sql";
    echo "starting: exporting GRANTS to $dumpfile\n";
$cmd = "(mysql --defaults-file=$myconf -B -N -u root -e \"SELECT DISTINCT CONCAT( 'SHOW GRANTS FOR ''', user, '''@''', host, ''';') AS query FROM mysql.user\" | mysql --defaults-file=$myconf -u root | sed 's/\(GRANT .*\)/\\1;/;s/^\(Grants for .*\)/## \\1 ##/;/##/{x;p;x;}' > $dumpfile) 2>> $logf";
    run_cmd( $cmd );
    echo "complete: exporting $db to $dumpfile\n";
    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        error_exit( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    run_cmd( "sudo chmod 400 $cdumpfile" );
    $cdumped[] = $cdumpfile;
}
 
run_cmd( "sudo chown $backup_user:$backup_user $logf" );
run_cmd( "sudo chmod 400 $logf" );
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
echo "completed:
backup files are in: $backup_dir
          log is in: $logf\n";
echoline( '=' );
if ( $backup_rsync ) {
    echo "starting: run $rsync_php\n";
    run_cmd( "cd $hdir && php $rsync_php" );
    echo "completed: run $rsync_php\n";
    echoline( '=' );
}

