<?php

$self = __FILE__;

$notes = <<<__EOD
usage: $self

lists dbnames, dbhosts & limshost from newus3.metadata where status=completed
__EOD;

if ( count( $argv ) < 1 || count( $argv ) > 2 ) {
    echo $notes;
    exit;
}

$config_file = "../db_config.php";
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
            
require "../utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

# main
$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

$res = db_obj_result( $db_handle, "select dbname, dbhost, limshost, status from newus3.metadata where status='completed'", True );
while( $row = mysqli_fetch_array($res) ) {
#    debug_json( "row", $row );
    $dbname   = $row['dbname'];
    $dbhost   = $row['dbhost'];
    $limshost = $row['limshost'];
    $status   = $row['status'];
    printf( "%-40s %-40s %-40s\n", $dbname, $dbhost, $limshost );
}

