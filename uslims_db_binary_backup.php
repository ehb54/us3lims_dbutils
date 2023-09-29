<?php

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {remote_config_file}

creates a binary backup of the database
must be run as root

__EOD;

if ( count( $argv ) < 1 || count( $argv ) > 2 ) {
    echo $notes;
    exit;
}

$config_file = "db_config.php";
if ( count( $argv ) == 2 ) {
    $use_config_file = $argv[ 1 ];
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
            
require "utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !is_admin() ) {
   error_exit( "must run as root" );
}

open_db();

# get datadir

$res = db_obj_result( $db_handle, "show global variables like 'datadir'" );
$datadir = sprintf( "%s", $res->{'Value'} );

echoline( "=" );
echo "datadir is $datadir\n";

# newdir  “mariadb-binary-backup’

newfile_dir_init( "mariadb-binary-backup" );

# qyn service mariadb stop
get_yn_answer( "Stop mariadb services (make sure no processes are actively updating!)", true );
run_cmd( "service mariadb stop" );

# verify db stopped

echo "Verifying db had stopped, expect a PHP Warning\n";

$db_handle = mysqli_connect( $dbhost, $user, $passwd );
if ( $db_handle ) {
    error_exit( "Can still connect to database, somehow it didn't stop?" );
}

echo "OK: db appears to be stopped as I can not connect\n";

# deep copy of datadir to newfile-/
echoline( "=" );
echo "making copy of database datadir $datadir\n";
$debug = 1;
run_cmd( "cp -rp $datadir/* $newfile_dir" );
$debug = 0;

# qyn service mariadb start
if ( get_yn_answer( "Restart mariadb services? (processes might further update the db)" ) ) {
    run_cmd( "service mariadb start" );
    # make sure db is open
    echo "verify db is running\n";
    open_db();
    echo "OK: db is running\n";
} else {
    echo "WARNING: db is not running\n";
}

echoline('=');
echo
    "backups are in:\n"
    . "$newfile_dir\n"
    . "restore by:\n"
    . "service mariadb stop\n"
    . "rm -fr $datadir/*\n"
    . "cp -rp  $newfile_dir/* $datadir\n"
    . "chown -R mysql:mysql $datadir\n"
    . "service mariadb start\n"
    ;

