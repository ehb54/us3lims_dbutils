<?php

# user defines

$limsdbpath = "/home/us3/lims/database/sql";
$ref_db     = "uslimsref";

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;

$notes = <<<__EOD
usage: $self {db_config_file}

dumps all schemas & procedures

my.cnf must exist in the current directory

__EOD;

$notes = <<<__EOD
usage: $self {options}


Options

--help                 : print this information and exit
    
--compare              : compare with this system's version of lims sql ($limsdbpath)
--compare-db dbname    : only compare named db (can be specified multiple times, default is to compare all)
--compare-keep-ref-db  : do not recreate the reference db, assume it is correct from a previous run
--show-diffs           : list the differences

--debug                : turn on debugging

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$compare             = false;
$compare_dbs         = [];
$compare_keep_ref_db = false;
$show_diffs          = false;
$debug               = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--compare": {
            array_shift( $u_argv );
            $compare = true;
            break;
        }
        case "--compare-db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --compare-db requires an argument\n\n$notes" );
            }
            $compare_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--compare-keep-ref-db": {
            array_shift( $u_argv );
            $compare_keep_ref_db = true;
            break;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
        case "--show-diffs": {
            array_shift( $u_argv );
            $show_diffs = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( count( $u_argv ) ) {
    error_exit( "Incorrect command format\n$notes" );
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

function dump_cmd( $db, $outfile = "" ) {
    global $myconf;


    if ( !strlen( $db ) ) {
        error_exit( "dump_cmd requires a non-empty argument" );
    }


    if ( !strlen( $outfile ) ) {
        $outfile = $db;
    }

    $cmd = 
        "mysqldump --defaults-file=../$myconf -u root --no-data --events --routines $db | "
        . "grep -Fv "
        . "-e 'ENGINE=' "
        . "-e 'Dumping events' "
        . "-e 'Dumping routines' "
        . "-e ' Host: localhost ' "
        . "-e ' Dump completed ' "
        . "-e ' MySQL dump ' "
        . "-e ' Server version ' "
        . "-e ' Dump completed ' "
        . "| perl -pe 's/(clusterAuthorizations.*DEFAULT).*,/\$1,/' "
        . " > $outfile";
    return $cmd;
}

if ( !$compare ) {
    # make & change to directory
    newfile_dir_init( "schemas" );
    if ( !chdir( $newfile_dir ) ) {
        error_exit( "Could not change to directory $newfile_dir" );
    }

    foreach ( $existing_dbs as $db ) {
        echoline();

        $outfile = "$db.sql";
        echo "exporting $db to $newfile_dir/$outfile\n";
       
        run_cmd( dump_cmd( $db, $outfile ) );
    }
    exit(0);
}

if ( !count( $compare_dbs ) ) {
    $compare_dbs = $existing_dbs;
} else {
    $use_dbs = array_intersect( $existing_dbs, $compare_dbs );
    if ( array_values( $use_dbs ) != $compare_dbs ) {
        error_exit( "Specified db(s) are not found:\n" . implode( "\n", array_diff( $compare_dbs, array_values( $use_dbs ) ) ) );
    }
}

if ( !count( $compare_dbs ) ) {
    error_exit( "no dbs found to compare" );
}

# create a reference db
debug_echo( echoline( '-', 80, false ) );
if ( $compare_keep_ref_db ) {
    debug_echo( "Keeping reference db $ref_db from a previous run\n" );
} else {
    ## drop previous version if exists
    debug_echo( "Dropping reference db $ref_db if it exists\n" );

    $querys = [
        "DROP DATABASE IF EXISTS $ref_db"
        ,"CREATE DATABASE $ref_db"
        ];
        foreach ( $querys as $q ) {
        # echo "query: $q\n";
            $res = mysqli_query( $db_handle, $q );
            if ( !$res ) {
                error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
            }
        }

    ## create new database
    debug_echo( "Creating reference db $ref_db\n" );

    $cmds = [
        "cd $limsdbpath && mysql --defaults-file=$cwd/my.cnf -u root $ref_db < us3.sql"
        ,"cd $limsdbpath && mysql --defaults-file=$cwd/my.cnf -u root $ref_db < us3_procedures.sql"
        ];
    foreach ( $cmds as $c ) {
        debug_echo( "running: $c\n" );
        $res = run_cmd( $c );
        if ( trim( $res ) != '' ) {
            debug_echo( "command returns: $res\n" );
        }
    }
}

# make & change to directory for comparisons
newfile_dir_init( "schemas" );
if ( !chdir( $newfile_dir ) ) {
    error_exit( "Could not change to directory $newfile_dir" );
}

## dump reference db
debug_echo( echoline( '-', 80, false ) );
debug_echo( "exporting $ref_db to $newfile_dir/$ref_db\n" );
run_cmd( dump_cmd( $ref_db ) );


# dump & compare each db, build report data
$db_diffs        = [];
$db_diff_results = [];
foreach ( $compare_dbs as $db ) {
    debug_echo( echoline( '-', 80, false ) );

    debug_echo( "exporting $db to $newfile_dir/$db\n" );
       
    run_cmd( dump_cmd( $db ) );

    debug_echo( "running diffs for $db\n" );
    $result = trim( run_cmd( "diff $ref_db $db", false ) );
    $db_diffs[ $db ]        = strlen( $result ) ? 1 : 0;
    $db_diff_results[ $db ] = $result;
}

# cleanup

## remove $newfile_dir
debug_echo( echoline( '-', 80, false ) );
if ( $debug ) {
    debug_echo( "not removiing $newfile_dir since debug is set\n" );
} else {
    debug_echo( "removing $newfile_dir\n" );
    run_cmd( "cd .. && rm -fr $newfile_dir" );
}

## if later needed drop reference db
## debug_echo( echoline( '-', 80, false ) );
## debug_echo( "dropping database $ref_db\n" );
## $querys = [
##    "DROP DATABASE IF EXISTS $ref_db"
##    ];
## foreach ( $querys as $q ) {
## # debug_echo( "query: $q\n" );
##    $res = mysqli_query( $db_handle, $q );
##    if ( !$res ) {
##        error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
##    }
##} 

$dbcount = count( $compare_dbs );
$dbdiffs = array_sum( $db_diffs );

if ( $show_diffs ) {
    foreach ( $db_diff_results as $k => $v ) {
        if ( strlen( $v ) ) {
            echoline( "=" );
            echo "< $k > reference database\n";
            echoline( "-" );
            echo "$v\n";
        }
    }
} else {
    echo "$dbcount,$dbdiffs\n";
}


