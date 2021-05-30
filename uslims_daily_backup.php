<?php

$self = __FILE__;
$hdir = __DIR__;

$compresswith = "gzip -f";
$compressext  = "gz";

$us3bin    = exec( "ls -d ~us3/lims/bin" );
include_once "$us3bin/listen-config.php";
$rsync_php  = "uslims_daily_rsync.php";

# $debug = 1;
date_default_timezone_set('UTC');

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
if ( !isset( $backup_rsync ) ) {
    $errors .= "\$backup_rsync is not set in $use_config_file\n";
}
if ( !isset( $backup_user ) ) {
    $errors .= "\$backup_user is not set in $use_config_file\n";
}
if ( !isset( $backup_email_reports ) ) {
    $errors .= "\$backup_email_reports is not set in $use_config_file\n";
}
if ( $backup_email_reports && !isset( $backup_email_address ) ) {
    $errors .= "\$backup_email_address is not set in $use_config_file\n";
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

$date = trim( run_cmd( 'date +"%y%m%d%H"' ) );
dt_store_now( "backup start" );

if ( !is_admin( false ) ) {
    error_exit( "This program must be run by root or a sudo enabled user" );
}

if ( is_locked( $rsync_php ) ) {
    backup_rsync_failure( "$rsync_php is currently running" );
}

// lock
if ( isset( $lock_dir ) ) {
   $lock_main_script_name  = __FILE__;
   require "$us3bin/lock.php";
} 

$dbnames_used = array_fill_keys( existing_dbs(), 1 );

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
    mkdir( $backup_dir );
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $backup_dir" );
}

echo "hdir is $hdir\n";

if ( !chdir( $backup_dir ) ) {
    backup_rsync_failure( "Could not change to directory $newfile_dir" );
}

$logf = "$backup_logs/$backup_host-$date.log";
if ( !is_dir( $backup_logs ) ) {
    mkdir( $backup_logs, 0700 );
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $backup_logs" );
}

backup_rsync_run_cmd( "echo $backup_host Mysqldump Error Log : `date` > $logf" );

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
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $dumpfile ) ) {
        backup_rsync_failure( "Error exporting '$dumpfile' terminating" );
    }
    echo "complete: exporting $db to $dumpfile\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $dumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $dumpfile" );

    $dumped[] = $dumpfile;

    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        backup_rsync_failure( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $cdumpfile" );
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
    backup_rsync_run_cmd( $cmd );
    echo "complete: exporting $db to $dumpfile\n";
    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        backup_rsync_failure( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $cdumpfile" );
    $cdumped[] = $cdumpfile;
}
 
backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $logf" );
backup_rsync_run_cmd( "sudo chmod 400 $logf" );
# check for old backups
foreach ( $dbnames_used as $db => $val ) {
    $cmd = "ls -1t $db-dump*.sql.$compressext";
    $result = array_slice( explode( "\n", trim( backup_rsync_run_cmd( $cmd ) ) ), $backup_count );

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
dt_store_now( "backup end" );
if ( $backup_rsync ) {
    dt_store_now( "rsync start" );
    echo "starting: run $rsync_php\n";
    backup_rsync_run_cmd( "cd $hdir && php $rsync_php" );
    echo "completed: run $rsync_php\n";
    echoline( '=' );
    dt_store_now( "rsync end" );
}

$summary_report = 
    sprintf( "Backup summary
%s
Host             %s

Backup start     %s
Backup end       %s
Backup duration  %s minutes

"
    ,echoline( '=', 80, false )
    ,$backup_host
    ,dt_store_get_printable( "backup start" )
    ,dt_store_get_printable( "backup end" )
    ,dt_store_duration( "backup start", "backup end" )
);
if ( $backup_rsync ) {
    $summary_report .=
    sprintf( "Rsync start      %s
Rsync end        %s
Rsync duration   %s minutes

"
    ,dt_store_get_printable( "rsync start" )
    ,dt_store_get_printable( "rsync end" )
    ,dt_store_duration( "rsync start", "rsync end" )
    );
}

# echo $summary_report;

if ( $backup_email_reports ) {
    $summary_report .= 
        echoline( '=', 80, false )
        . "processed " . count( $dbnames_used ) . " unique dbname records as follows\n"
        . echoline( '-', 80, false )
        . implode( "\n", array_keys( $dbnames_used ) )
        .  "\n"
        . echoline( '=', 80, false )
        ;

    $summary_report .= 
        sprintf( "Settings

Backup logs        %s:%s
Backup user        %s
Backup number kept %s

"
            ,$backup_host
            ,$backup_logs
            ,$backup_user
            ,$backup_count

            );
    if ( $backup_rsync ) {
    $summary_report .= 
        sprintf( "Rsync logs         %s:%s
Rsync user         %s
Rsync destination  %s:%s

"
            ,$backup_host
            ,$rsync_logs
            ,$rsync_user
            ,$rsync_host
            ,$rsync_path
            );
    }
    $summary_report .= echoline( '=', 80, false );
    
    if ( !mail( 
               $backup_email_address
               ,"backup summary for $backup_host"
               ,$summary_report
               ,backup_rsync_email_headers()
              )
       ) {
        error_exit( "email of reports failed" );
    } else {
        echo "reports emailed to $backup_email_address\n";
    }
}
