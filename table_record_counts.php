<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self dbname {remote_config_file}

list record counts of all tables in db
if remote_config_file specified, it will be used instead of db config

__EOD;

if ( count( $argv ) < 2 || count( $argv ) > 3 ) {
    echo $notes;
    exit;
}

$lims_db = $argv[ 1 ];

$config_file = "db_config.php";
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
            
require "utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

# main

$db_handle = mysqli_connect( $dbhost, $user, $passwd, $lims_db );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user, $lims_db. exiting\n" );
    exit(-1);
}

$res = db_obj_result( $db_handle, "show tables from $lims_db", true );
$tables = [];
while( $row = mysqli_fetch_array($res) ) {
    $tables[] = $row[ "Tables_in_$lims_db" ];
}
# debug_json( "tables", $tables);

foreach ( $tables as $k => $v ) {
    $q = "select count(*) from $v";
    $res = db_obj_result( $db_handle, $q );
    echo "$v " . $res->{'count(*)'} . "\n";
}
