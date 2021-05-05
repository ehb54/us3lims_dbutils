<?php

# developer defines
$logging_level = 2;

$variables_of_interest = [
    "symbolic-links"
    ,"skip-external-locking"
    ,"key_buffer_size"
    ,"max_allowed_packet"
    ,"table_open_cache"
    ,"table_definition_cache"
    ,"sort_buffer_size"
    ,"net_buffer_length"
    ,"read_buffer_size"
    ,"read_rnd_buffer_size"
    ,"join_buffer_size"
    ,"myisam_sort_buffer_size"
    ,"connect_timeout"
    ,"expire_logs_days"
    ,"innodb_file_per_table"
    ,"max_connections"
    ,"max_user_connections"
    ,"thread_cache_size"
    ,"query_cache_type"
    ,"query_cache_size"
    ,"innodb_data_home_dir"
    ,"innodb_datafile_path"
    ,"innodb_log_group_home_dir"
    ,"innodb_buffer_pool_size"
    ,"innodb_additional_mem_pool_size"
    ,"innodb_log_file_size"
    ,"innodb_log_buffer_size"
    ,"innodb_flush_log_at_trx_commit"
    ,"innodb_lock_wait_timeout"
    ,"general_log"
    ,"general_log_file"
    ,"datadir"
    ];

$show_status =
    [
     "Threads_connected"
     ,"Max_used_connections"
    ]
    ;

# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {remote_config_file}

lists mysqld variables of interest for uslims
if remote_config_file specified, it will be used instead of db config

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

# main

open_db();

echoline();
echo "Globals:\n";

$res = db_obj_result( $db_handle, "show global variables", true );
$variables = [];
while( $row = mysqli_fetch_array($res) ) {
    $var = $row[0];
    $val = $row[1];
    if ( in_array( $var, $variables_of_interest ) ) {
        printf( "%-40s %s\n", $var, $val );
    }
}
echoline();
echo "Status:\n";

foreach ( $show_status as $v ) {
    $res = db_obj_result( $db_handle, "show status where variable_name = '$v'" );
    $val = sprintf( "%s", $res->{'Value'} );
    printf( "%-40s %s\n", $v, $val );
}

echoline();
