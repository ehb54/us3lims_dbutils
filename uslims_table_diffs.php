<?php

# user defines

$us3home         = "/home/us3";
$us3sql          = "$us3home/lims/database/sql";

# end of user defines
require "utility.php";

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} dbname {db_config_file}

compares dbname's tables to schema_rev determined from git version of $us3sql
requires a schema_rev#.sql file present

Options

--help               : print this information and exit
--only-extras        : only report extra tables present in dbname not present in schema_rev#.sql
--rev #              : specify revision # for sql instead of autodetecting

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$only_extras = false;
$user_rev    = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
       case "--help": {
            echo $notes;
            exit;
        }
       case "--only-extras": {
            array_shift( $u_argv );
            $only_extras = true;
            break;
        }
       case "--rev": {
            array_shift( $u_argv );
            $user_rev = true;
            $rev = array_shift( $u_argv );
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( !count( $u_argv ) ) {
    echo $notes;
    exit;
}    

$use_dbname = array_shift( $u_argv );

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
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
            
file_perms_must_be( $use_config_file );
require $use_config_file;

# get rev #

if ( !$user_rev ) {
    $hash = trim( run_cmd( "cd $us3sql && git log -1 --oneline .| cut -d' ' -f1" ) );
    $rev  = trim( run_cmd( "cd $us3sql && git log --oneline | sed -n '/$hash/,99999p' | wc -l" ) );
}

$schema_file = "schema_rev$rev.sql";
if ( !file_exists( $schema_file ) ) {
    error_exit( "$schema_file not found" );
}

# get tables from schema

$schema_tables = array_fill_keys( explode( "\n", trim( run_cmd( "grep 'CREATE TABLE' $schema_file | awk '{ print \$3 }' | sed 's/`//g'" ) ) ), 1 );

# get tables from db

open_db();

$res = db_obj_result( $db_handle, "show tables from $use_dbname", true );
$db_tables = [];
while( $row = mysqli_fetch_array($res) ) {
    $db_tables[ $row[ "Tables_in_$use_dbname" ] ] = 1;
}

$in_schema_not_db = [];
$in_db_not_schema = [];

foreach ( $db_tables as $table => $v ) {
    if ( !array_key_exists( $table, $schema_tables ) ) {
        $in_db_not_schema[ $table ] = 1;
    }
}

foreach ( $schema_tables as $table => $v ) {
    if ( !array_key_exists( $table, $db_tables ) ) {
        $in_schema_not_db[ $table ] = 1;
    }
}

if ( $only_extras ) {
    if ( count( $in_db_not_schema ) ) {
        echo implode( "\n", $in_db_not_schema ) . "\n";
    }
    exit;
}

echoline();
echo "Tables in $use_dbname not in $schema_file: " . count( $in_db_not_schema ) . "\n";
if ( count( $in_db_not_schema ) ) {
    echo implode( "\n", array_keys($in_db_not_schema ) ) . "\n";
}
echoline();
echo "Tables in $schema_file not in $use_dbname: " . count( $in_schema_not_db ) . "\n";
if ( count( $in_schema_not_db ) ) {
    echo implode( "\n", array_keys( $in_schema_not_db ) ) . "\n";
}
echoline();






