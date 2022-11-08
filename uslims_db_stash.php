<?php

# user defines

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;
date_default_timezone_set('UTC');

$notes = <<<__EOD
usage: $self {db_config_file}

stashes & unstashes dbs

my.cnf must exist in the current directory

__EOD;

$notes = <<<__EOD
usage: $self {options}


Options

--help                 : print this information and exit
    
--db                   : db to to stash/unstash (can be specified multiple times)
--list                 : list stashed dbs
--stash                : stash a db (requries --db)
--unstash              : unstash a db (requries --db)


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$use_dbs             = [];
$list                = false;
$stash               = false;
$unstash             = false;

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
        case "--list": {
            array_shift( $u_argv );
            $list = true;
            break;
        }
        case "--stash": {
            array_shift( $u_argv );
            $stash = true;
            break;
        }
        case "--unstash": {
            array_shift( $u_argv );
            $unstash = true;
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

if ( !$anyargs || count( $u_argv ) ) {
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

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" 
               . "[client]\n"
               . "password=YOUR_ROOT_DB_PASSWORD\n"
               . "max_allowed_packet=256M\n"
               . "[mysqldump]\n"
               . "password=YOUR_ROOT_DB_PASSWORD\n"
               . "max_allowed_packet=256M\n"
   );
}
file_perms_must_be( $myconf );

if ( $stash && $unstash ) {
   error_exit( "--stash and --unstash are mutually exclusive" );
}

if ( ( $stash || $unstash ) && $list ) {
    error_exit( "--list and ( --stash or --unstash ) are mutually exclusive" );
}

if ( $stash && !count( $use_dbs ) ) {
    error_exit( "--stash requires --db" );
}    
    
if ( $unstash && !count( $use_dbs ) ) {
    error_exit( "--unstash requires --db" );
}    
    
$existing_dbs       = existing_dbs();
$existing_stash_dbs = existing_stash_dbs();

if ( $list ) {
    foreach ( $existing_stash_dbs as $stashdb ) {
        echo "$stashdb\n";
    }
    exit;
}

if ( $stash ) {
    ## do dbs exist?
    if ( !count( $existing_dbs ) ) {
        error_exit( "no dbs exist!" );
    }

    $db_diff = array_diff( $use_dbs, $existing_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database : " . implode( ' ', $db_diff ) );
    }

    ## date string for db names
    $date = trim( run_cmd( 'date +"%y%m%d%H%M%S"' ) );

    echoline();
    echo "Stashing dbs\n";
    echoline();
    foreach ( $use_dbs as $db ) {
        ## does metadata exist?
        $query = "select status from newus3.metadata where dbname='$db'";
        $res = db_obj_result( $db_handle, $query );
        if ( $res->{"status"} != "completed" ) {
            error_exit( "db $db has incorrect newus3.metadata status of " . $res->{"status"} );
        }
        echo "status " . $res->{"status"} . "\n";
        $basedb  = preg_replace( "/^uslims3_/", "", $db );
        $stashdb = "stash_${date}_${basedb}";
        echo "db $db will be stashed as $stashdb\n";
        $cmd = "echo 'create database $stashdb;' | mysql --defaults-file=my.cnf -u root && mysqldump --defaults-file=my.cnf -u root $db | mysql --defaults-file=my.cnf -u root $stashdb";
        echo "$cmd\n";
        run_cmd( $cmd );
        echo "stash of $db complete as $stashdb\n";
        echo "updating newus3.metadata.status for $db to pending\n";
        $query = "update newus3.metadata set status='pending' where dbname='$db'";
        db_obj_result( $db_handle, $query );
    }

    if ( get_yn_answer( "drop existing dbinstance from the database (THIS CAN NOT BE UNDONE!)?" ) ) {
        foreach ( $use_dbs as $db ) {
            echoline();
            echo "Dropping dbinstance: $db\n";
            $query = "drop database $db";
            db_obj_result( $db_handle, $query );
        }
    }

    exit;
}

    
if ( $unstash ) {
    ## do dbs exist?
    if ( !count( $existing_stash_dbs ) ) {
        error_exit( "no dbs exist!" );
    }

    $db_diff = array_diff( $use_dbs, $existing_stash_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database stash : " . implode( ' ', $db_diff ) );
    }

    ## date string for db names
    $date = trim( run_cmd( 'date +"%y%m%d%H%M%S"' ) );


    echoline();
    echo "Unstash dbs\n";
    echoline();
    foreach ( $use_dbs as $db ) {
        $basedb  = preg_replace( "/^stash_\\d+_/", "", $db );
        $unstashdb = "uslims3_${basedb}";
        ## does metadata exist?
        echo "checking metadata for $unstashdb";
        $query = "select status from newus3.metadata where dbname='$unstashdb'";
        $res = db_obj_result( $db_handle, $query );
        if ( $res->{"status"} != "pending" ) {
            error_exit( "db $db has incorrect newus3.metadata status of " . $res->{"status"} );
        }
        echo "db $db will be unstashed as $unstashdb\n";
        $cmd = "echo 'create database $unstashdb;' | mysql --defaults-file=my.cnf -u root && mysqldump --defaults-file=my.cnf -u root $db | mysql --defaults-file=my.cnf -u root $unstashdb";
        echo "$cmd\n";
        run_cmd( $cmd );
        echo "unstash of $db complete as $unstashdb\n";
        echo "updating newus3.metadata.status for $unstashdb to completed\n";
        $query = "update newus3.metadata set status='completed' where dbname='$unstashdb'";
        db_obj_result( $db_handle, $query );
    }

    if ( get_yn_answer( "drop unstashed dbs from the database (THIS CAN NOT BE UNDONE!)?" ) ) {
        foreach ( $use_dbs as $db ) {
            echoline();
            echo "Dropping dbinstance: $db\n";
            $query = "drop database $db";
            db_obj_result( $db_handle, $query );
        }
    }
}

    
