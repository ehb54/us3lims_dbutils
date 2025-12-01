<?php

# user defines

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;

$notes = <<<__EOD
usage: $self {db_config_file}

performs checks of the db

my.cnf must exist in the current directory

__EOD;

$notes = <<<__EOD
usage: $self {options}
lists CHARSETs, ENGINEs used by DB

Options

--help                 : print this information and exit
    
--db                   : db to check, can be repeated, otherwise all will be included

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$use_dbs             = [];
$debug               = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
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
            
file_perms_must_be( $use_config_file );
require $use_config_file;

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" .
   "[mysqldump]\n" .
   "password=YOUR_ROOT_DB_PASSWORD\n"
   );
}
file_perms_must_be( $myconf );

$existing_dbs = existing_dbs();
if ( !count( $use_dbs ) ) {
    $use_dbs = $existing_dbs;
} else {
    $db_diff = array_diff( $use_dbs, $existing_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database : " . implode( ' ', $db_diff ) );
    }
}

array_push( $use_dbs, 'gfac', 'newus3', 'uslims3_global' );

if ( !count( $use_dbs ) ) {
    error_exit( "no dbs selected" );
}

$summary = (object)[ 
    "engines"     => (object)[]
    ,"charsets"   => (object)[]
    ,"collations" => (object)[]
];


$fmtlen = 160;
$fmt = "%-7s | %-20s | %-30s | %-30s | %-15s | %-15s | %-15s\n";
echoline( '-', $fmtlen );
echo sprintf( $fmt
              ,'level'
              ,'schema_name'
              ,'table_name'
              ,'column_name'
              ,'engine'
              ,'charset'
              ,'collation' );
echoline( '-', $fmtlen );

foreach ( $use_dbs as $db ) {

    $query = <<< __EOQ
SELECT 
    'SCHEMA' AS level,
    s.SCHEMA_NAME AS schema_name,
    NULL AS table_name,
    NULL AS column_name,
    NULL AS engine,
    s.DEFAULT_CHARACTER_SET_NAME AS charset,
    s.DEFAULT_COLLATION_NAME AS collation
FROM information_schema.SCHEMATA s
WHERE s.SCHEMA_NAME = '$db'

UNION ALL

SELECT 
    'TABLE' AS level,
    t.TABLE_SCHEMA AS schema_name,
    t.TABLE_NAME AS table_name,
    NULL AS column_name,
    t.ENGINE AS engine,
    cc.CHARACTER_SET_NAME AS charset,
    t.TABLE_COLLATION AS collation
FROM information_schema.TABLES t
JOIN information_schema.COLLATION_CHARACTER_SET_APPLICABILITY cc
      ON t.TABLE_COLLATION = cc.COLLATION_NAME
WHERE t.TABLE_SCHEMA = '$db'

UNION ALL

SELECT 
    'COLUMN' AS level,
    c.TABLE_SCHEMA AS schema_name,
    c.TABLE_NAME AS table_name,
    c.COLUMN_NAME AS column_name,
    NULL AS engine,
    c.CHARACTER_SET_NAME AS charset,
    c.COLLATION_NAME AS collation
FROM information_schema.COLUMNS c
WHERE c.TABLE_SCHEMA = '$db'
  AND c.CHARACTER_SET_NAME IS NOT NULL

ORDER BY charset, level, schema_name, table_name, column_name;
__EOQ;

    $res     = db_obj_result( $db_handle, $query, true );
    while( $row = mysqli_fetch_array($res) ) {
        $orow = (object) $row;
        echo sprintf( $fmt
                      ,$orow->level
                      ,$orow->schema_name
                      ,$orow->table_name
                      ,$orow->column_name
                      ,$orow->engine
                      ,$orow->charset
                      ,$orow->collation );
        if ( strlen( $orow->engine ) ) {
            $summary->engines->{$orow->engine} = ($summary->engines->{$orow->engine} ?? 0) + 1;
        }
        $summary->charsets->{$orow->charset} = ($summary->charsets->{$orow->charset} ?? 0) + 1;
        $summary->collations->{$orow->collation} = ($summary->collations->{$orow->collation} ?? 0) + 1;
    }
}    

debug_json( "summary", $summary );
