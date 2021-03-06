<?php

# user defines

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
    
--list                 : list auto
--sql                  : report in sql format
--db dbname            : restrict results to dbname (can be specified multiple times)
--sqlnodb              : sql statements will not specify the db, --db must be selected for exactly one db
--autoflow             : special handling to check autoflow & autoflowHistory requestIDs
--autoflow-repair      : special handling to repair autoflow autoincrement (requries --autoflow)


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$use_dbs             = [];
$sql                 = false;
$sqlnodb             = false;
$list                = false;
$autoflow            = false;
$autoflowrepair      = false;

$debug               = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--list": {
            array_shift( $u_argv );
            $list    = true;
            break;
        }
        case "--autoflow": {
            array_shift( $u_argv );
            $autoflow = true;
            break;
        }
        case "--autoflow-repair": {
            array_shift( $u_argv );
            $autoflowrepair = true;
            break;
        }
        case "--sql": {
            array_shift( $u_argv );
            $sql = true;
            break;
        }
        case "--sqlnodb": {
            array_shift( $u_argv );
            $sqlnodb = true;
            break;
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

if ( $sqlnodb && count( $use_dbs ) != 1 ) {
    error_exit( "--sqlnodb selected and not exactly one db selected" );
}

if ( $sqlnodb && !$sql ) {
    error_exit( "--sqlnodb requires --sql" );
}

if ( $autoflowrepair && !$autoflow ) {
    error_exit( "--autoflow-repair requires --autoflow" );
}    

if ( $autoflow && $sql ) {
    error_exit( "--autoflow and --sql are exclusive" );
}    

if ( $autoflow ) {
    $fmtlen = 136;
    echoline( '-', $fmtlen );
    $fmt = "%-20s | %-31s | %-37s | %s\n";
    echo sprintf( $fmt
                  ,"db"
                  ,"autoflowAnalysis auto_increment"
                  ,"max autoflowAnalysisHistory.requestID"
                  ,"notes"
        );
    echoline( '-', $fmtlen );

    foreach ( $use_dbs as $db ) {
        $res     = db_obj_result( $db_handle, "SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'autoflowAnalysis'" );
        $autoinc = $res->AUTO_INCREMENT;
        $res     = db_obj_result( $db_handle, "SELECT MAX(requestID) FROM $db.autoflowAnalysisHistory" );
        $maahrid = $res->{"MAX(requestID)"};

        $notes   = 'ok';
        if ( $autoinc < $maahrid - 1 ) {
            $notes = 'ERROR : auto_increment needs repair';
            if ( $autoflowrepair ) {
                $newautoinc = $maahrid + 1;
                $res        = db_obj_result( $db_handle, "ALTER TABLE $db.autoflowAnalysis AUTO_INCREMENT = $newautoinc" );
                $res        = db_obj_result( $db_handle, "SELECT `AUTO_INCREMENT` FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'autoflowAnalysis'" );
                $newautoinc = $res->AUTO_INCREMENT;
                $notes      = "auto_increment repair, new value $newautoinc";
            }
        }
            
        echo sprintf( $fmt
                      ,$db
                      ,$autoinc
                      ,$maahrid
                      ,$notes
            );
    }
    echoline( '-', $fmtlen );
    exit;
}

foreach ( $use_dbs as $db ) {
    $cmdres = run_cmd( "mysqldump --defaults-file=my.cnf -u root --no-data $db | grep -P '(CREATE TABLE|ENGINE=)'", true, true );

    for ( $i = 0; $i < count($cmdres); $i += 2 ) {
        if ( strpos( $cmdres[$i+1], 'AUTO_INCREMENT=' ) !== false ) {
            $table   =
                preg_replace(
                    '/. \(.*$/'
                    ,''
                    ,preg_replace( '/^CREATE TABLE ./'
                                   ,''
                                   ,$cmdres[$i] )
                );
            $autoinc =
                preg_replace(
                    '/ .*$/', ''
                    ,preg_replace( '/^.*AUTO_INCREMENT\=/'
                                    ,''
                                    , $cmdres[$i+1] )
                );
            if ( $sql ) {
                if ( $sqlnodb ) {
                    echo "alter table $table auto_increment=$autoinc;\n";
                } else {
                    echo "alter table $db.$table auto_increment=$autoinc;\n";
                }
            } else {
                echo "$db.$table : $autoinc\n";
            }
        }
    }
}

