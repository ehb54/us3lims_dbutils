<?php

$self = __FILE__;
$hdir = __DIR__;

$compresswith = "gzip";
$compressext  = "gz";

# $debug = 1;

$notes = <<<__EOD
usage: $self {config_file}

ryncs to remote server

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
if ( !isset( $backup_dir ) ) {
    $errors .= "\$backup_dir is not set in $use_config_file\n";
}
if ( !isset( $rsync_user ) ) {
    $errors .= "\$rsync_user is not set in $use_config_file\n";
}
if ( !isset( $rsync_host ) ) {
    $errors .= "\$rsync_host is not set in $use_config_file\n";
}
if ( !isset( $rsync_path ) ) {
    $errors .= "\$rsync_path is not set in $use_config_file\n";
}
if ( !isset( $rsync_logs ) ) {
    $errors .= "\$rsync_logs is not set in $use_config_file\n";
}
if ( !isset( $backup_host ) ) {
    $errors .= "\$backup_host is not set in $use_config_file\n";
}
if ( strlen( $errors ) ) {
    error_exit( $errors );
}

$date = trim( run_cmd( 'date +"%y%m%d"' ) );

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
   mkdir( $backup_dir );
   run_cmd( "sudo chown $rsync_user:$rsync_user $backup_dir" );
}

if ( !is_admin( false ) ) {
   error_exit( "This program must be run by root or a sudo enabled user" );
}

if ( !chdir( $backup_dir ) ) {
   error_exit( "Could not change to directory $newfile_dir" );
}

if ( !is_dir( $rsync_logs ) ) {
    mkdir( $rsync_logs, 0700 );
    run_cmd( "sudo chown $rsync_user:$rsync_user $rsync_logs" );
}

$cmd = "runuser -l $rsync_user -c \"ssh -o StrictHostKeyChecking=no $rsync_user@$rsync_host -C 'sudo mkdir $rsync_path'\"";
run_cmd( $cmd, false );

$logf = "$rsync_logs/remote-$backup_host-$date.log";

run_cmd( "(echo -n 'rsync started: ' && date) > $logf", false );
$cmd = "sudo -u $rsync_user rsync -av -e 'ssh -l usadmin' --rsync-path='sudo rsync' --delete $backup_dir $rsync_user@$rsync_host:$rsync_path/$backup_host 2>&1 >> $logf";
run_cmd( $cmd );
run_cmd( "(echo -n 'rsync finished: ' && date) >> $logf", false );
run_cmd( "sudo chown $rsync_user:$rsync_user $logf" );

