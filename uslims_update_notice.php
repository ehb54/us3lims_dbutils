<?php

$self                 = __FILE__;
$hdir                 = __DIR__;

$us3bin               = exec( "ls -d ~us3/lims/bin" );
include_once          "$us3bin/listen-config.php";

# to enable new remote database
# grant select, show view on us3_notice.* to us3_notice@'%' identified by 'us3_notice'

$notes = <<<__EOD
usage: $self {config_file}

syncs us3_notice from localhost to remote host
// needs keys setup
1. mysqldump of us3_notice
2 scp? (or can we use a local file into an ssh command as input?
3. ssh mysql --defaults-file=\$remote... -u ? import

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

$myconf = "$hdir/my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the $hdir directory with the following contents:\n"
       . "[mysqldump]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "max_allowed_packet=256M\n"
       );
}
file_perms_must_be( $myconf );

$errors = "";
if ( !isset( $notice_remote_host ) ) {
    $errors .= "\$notice_remote_host is not set in $use_config_file\n";
}
if ( !isset( $notice_remote_user ) ) {
    $errors .= "\$notice_remote_user is not set in $use_config_file\n";
}
if ( !isset( $notice_remote_db_user ) ) {
    $errors .= "\$notice_remote_user is not set in $use_config_file\n";
}
if ( strlen( $errors ) ) {
    error_exit( $errors );
}

$cmd = "mysqldump --defaults-file=$myconf -u us3_notice us3_notice notice | ssh $notice_remote_user@$notice_remote_host mysql --defaults-file=$notice_remote_mycnf -u $notice_remote_db_user us3_notice";
run_cmd( $cmd );
