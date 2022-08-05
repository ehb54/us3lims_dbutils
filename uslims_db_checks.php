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


Options

--help                 : print this information and exit
    
--db                   : db to check, can be repeated
--check-data-owner     : checks models for experiment person model person consistency
--list-all             : list all data even if it is ok

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$use_dbs             = [];
$check_data_owner    = 0;
$list_all            = 0;

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
        case "--check-data-owner": {
            array_shift( $u_argv );
            $check_data_owner++;
            break;
        }
        case "--list-all": {
            array_shift( $u_argv );
            $list_all++;
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

if ( $check_data_owner && !count( $use_dbs ) ) {
    error_exit( "--check_data_owner selected and no dbs selected" );
}

if ( $check_data_owner ) {
    $fmtlen = 136;

    echoline( '-', $fmtlen );
    echo "check_data_owner\n";
    echoline( '-', $fmtlen );
    $fmt = "%-20s | %-20s | %-12s | %-20s | %-12s | %-12s | %-12s\n";
    $header = sprintf( $fmt
                       ,"runID"
                       ,"experimentID"
                       ,"exp personID"
                       ,"lname"
                       ,"fname"
                       ,"modelID"
                       ,"model_person"
        );
    
    foreach ( $use_dbs as $db ) {
        echo "checking db $db\n";
        echoline( '-', $fmtlen );

        ### get people

        $people = (object)[];

        $res     = db_obj_result( $db_handle, "select personID, fname, lname from $db.people", true );
        while( $row = mysqli_fetch_array($res) ) {
            $people->{$row["personID"]} = (object)[];
            $people->{$row["personID"]}->name = $row["lname"] . ", " . $row["fname"];
        }
        ## debug_json( "people people", $people );

        ### check all models to find differences between modelPerson & experiment


        
        $query = 
            "SELECT experiment.experimentID,\n"
            . "       experiment.runID,\n"
            . "       model.modelID,\n"
            . "       experimentPerson.personID AS experiment_personID,\n"
            . "       people.lname,\n"
            . "       people.fname,\n"
            . "       modelPerson.personID AS model_personID\n"
            . " FROM $db.model\n"
            . " JOIN $db.modelPerson ON model.modelID=modelPerson.modelID\n"
            . " JOIN $db.editedData ON model.editedDataID=editedData.editedDataID\n"
            . " JOIN $db.rawData ON rawData.rawDataID=editedData.rawDataID\n"
            . " JOIN $db.experimentPerson ON rawData.experimentID=experimentPerson.experimentID\n"
            . " JOIN $db.people ON experimentPerson.personID=people.personID\n"
            . " JOIN $db.experiment ON experiment.experimentID=rawData.experimentID\n"
            . ( $list_all ? "" : " WHERE experimentPerson.personID != modelPerson.personID\n" )
            ;

        if ( $debug ) {
            echo "$query ;\n";
        }

        $res = db_obj_result( $db_handle, $query, true, true );

        if ( $res ) {
            ### check each experiment and their related tables
            echo $header;
            echoline( '-', $fmtlen );
            while( $row = mysqli_fetch_array($res) ) {
                echo sprintf( $fmt
                              ,$row['runID']
                              ,$row['experimentID']
                              ,$row['experiment_personID']
                              ,$row['lname']
                              ,$row['fname']
                              ,$row['modelID']
                              ,$row['model_personID']
                    );
            }
        } else {
            echo "nothing to report\n";
        }
        echoline( '-', $fmtlen );
    }
    exit;
}

echo $notes;




