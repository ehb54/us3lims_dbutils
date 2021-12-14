<?php

{};
## user defines

## better handled in $usage_consolidation
### $adjustClusterName =
###    [
###     "alamo.bi"  => "alamo.uthscsa.edu"
###     ,"ls5.tacc" => "lonestar5.tacc.teragrid.org"
###    ];

## N.B. usage values could (should?) be overridden in db_config.php

$usage_approvedList =
    [
     "alamo"
     ,"bcf"
     ,"bridges"
     ,"comet"
     ,"expanse"
     ,"gordon"
     ,"jetstream"
     ,"jureca"
     ,"juropa"
     ,"lonestar"
     ,"lonestar5"
     ,"ranger"
     ,"stampede"
     ,"stampede2"
#     ,"testing"
     ,"trestles"
     ,"uleth"
     ,"umontana"
    ];

$usage_consolidation =
    [
     "alamo.biochemistry.uthscsa.edu"        => "alamo"
     ,"alamo.uthscsa.edu"                    => "alamo"
     ,"bcf.uthscsa.edu"                      => "bcf"
     ,"bridges2.psc.edu"                     => "bridges"
     ,"comet.sdsc.xsede.org"                 => "comet"
     ,"comet.sdsc.edu"                       => "comet"
     ,"expanse.sdsc.edu"                     => "expanse"
     ,"gordon.sdsc.edu"                      => "gordon"
     ,"gordon.sdsc.xsede.org"                => "gordon"
     ,"jetstream.jetdomain"                  => "jetstream"
     ,"js-169-137.jetstream-cloud.org"       => "jetstream"
     ,"js-157-184.jetstream-cloud.org"       => "jetstream"
     ,"lonestar.tacc.teragrid.org"           => "lonestar"
     ,"ls5.tacc.utexas.edu"                  => "lonestar5"
     ,"lonestar5.tacc.teragrid.org"          => "lonestar5"
     ,"jureca.fz-juelich.de"                 => "jureca"
     ,"juropa.fz-juelich.de"                 => "juropa"
     ,"gatekeeper.ranger.tacc.teragrid.edu"  => "ranger"
     ,"stampede.tacc.xsede.org"              => "stampede"
     ,"stampede2.tacc.xsede.org"             => "stampede2"
     ,"js237.genapp.rocks"                   => "testing"
     ,"js237a.genapp.rocks"                  => "testing"
     ,"uslimstest.genapp.rocks"              => "testing"
     ,"trestles.sdsc.edu"                    => "trestles"
     ,"demeler9.uleth.ca"                    => "uleth"
     ,"demeler1.uleth.ca"                    => "uleth"
     ,"demeler3.uleth.ca"                    => "uleth"
     ,"uslims.uleth.ca"                      => "uleth"
     ,"login.gscc.umt.edu"                   => "umontana"
     ,"chinook.hs.umt.edu"                   => "umontana"

    ];


## end user defines    

$globaldb = "uslims3_global";

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;

$notes = <<<__EOD
  usage: $self {options} {db_config_file}

store usage statistics for this database

    Options

    --help                   : print this information and exit

    --create                 : create the global database
    --csv startyear endyear  : produce csv output
    --csv-all                : report on all clusters (ignores approved list)
    --summary-info           : list of the clusters used and the date range in the global database

    --debug                  : turn on debugging



__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$debug               = 0;
$create              = false;
$csv                 = false;
$startyear           = 0;
$endyear             = 9999;
$csvall              = false;
$summaryinfo         = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--create": {
            array_shift( $u_argv );
            $create = true;
            break;
        }
        case "--csv": {
            array_shift( $u_argv );
            $csv = true;
            if ( count( $u_argv ) < 2 ) {
                error_exit( "\nOption --csv requires two arguments\n\n$notes" );
            }
            $startyear = array_shift( $u_argv );
            $endyear   = array_shift( $u_argv );
            break;
        }
        case "--csv-all": {
            array_shift( $u_argv );
            $csvall = true;
            break;
        }
        case "--summary-info": {
            array_shift( $u_argv );
            $summaryinfo = true;
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

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( !$create && !$csv && !$summaryinfo ) {
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

# main

function array_flip_safe(array $array) : array {
    return array_reduce(array_keys($array), function ($carry, $key) use (&$array) {
        $carry[$array[$key]] = $carry[$array[$key]] ?? [];
        $carry[$array[$key]][] = $key;
        return $carry;
                        }, []);
}

$existing_dbs = existing_dbs();

debug_json( "existing_dbs", $existing_dbs, 1 );

if ( $create ) {
    ## clean up uslims3_global
    debug_echo( "clean up $globaldb" );
    
    $querys = [
        "delete from $globaldb.investigators"
        ,"delete from $globaldb.submissions"
        ];

    foreach ( $querys as $q ) {
        $res = mysqli_query( $db_handle, $q );
        if ( !$res ) {
            error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
        }
    }

    ## Build global tables from each db instance's HPC Analysis tables
    debug_echo( "Building global tables from each db instance's HPC Analysis tables" );

    foreach ( $existing_dbs as $db ) {
        echoline("=");
        echo "Using $db.";

        $query  =
            "SELECT q.HPCAnalysisRequestID, startTime, endTime, "
            . "CPUTime, clusterName, CPUCount, "
            . "r.HPCAnalysisResultID as resultID, "
            . "GREATEST(1,(SELECT COUNT(*) from $db.HPCAnalysisResultData d "
            . "WHERE d.HPCAnalysisResultID = r.HPCAnalysisResultID "
            . "AND d.HPCAnalysisResultType = 'model')) as ModelCount, "
            . "GREATEST(1,(SELECT COUNT(*) from $db.HPCAnalysisResultData d "
            . "WHERE d.HPCAnalysisResultID = r.HPCAnalysisResultID "
            . "AND d.HPCAnalysisResultType = 'noise')) as NoiseCount, "
            . "requestXMLFile as RequestXml, "
            . "i.personID AS investigatorID, s.personID AS submitterID, "
            . "CONCAT(i.lname, ', ', i.fname) AS investigatorName, "
            . "CONCAT(s.lname, ', ', s.fname) AS submitterName "
            . "FROM $db.HPCAnalysisRequest q, $db.HPCAnalysisResult r, "
            . "$db.people i, $db.people s "
            . "WHERE q.HPCAnalysisRequestID = r.HPCAnalysisRequestID "
            . "AND investigatorGUID = i.personGUID "
            . "AND submitterGUID = s.personGUID "
            . "AND queueStatus = 'completed' "     ## These are completed jobs
            ;
        
        $res = mysqli_query( $db_handle, $query );
        if ( !$res ) {
            error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
        }
        echo ".";

        while ( $row = mysqli_fetch_array( $res ) ) {
            debug_echo( echoline( '-', 80, false ) );
            debug_echo( "get row keys" );
            ## Make variables
            foreach ($row as $key => $value ) {
                $$key = $value;
            }

            ## remove single quotes
            $submitterName    = fix_single_quote( $submitterName );
            $investigatorName = fix_single_quote( $investigatorName );

            debug_echo( "clusterName      : $clusterName" );
            debug_echo( "submitterName    : $submitterName" );
            debug_echo( "investigatorName : $investigatorName" );

            ## better handled in $usage_consolidation, just leave as is for now
            ###  possibly fixup clusterNames
            ###  $clusterName8 = substr( $clusterName, 0, 8 );
            ###  if ( array_key_exists( $clusterName8, $adjustClusterName ) ) {
            ###      $clusterName = $adjustClusterName[ $clusterName8 ];
            ### }

            $dsetCount  = 1;
            $global_fit = 0;
            $MCiters    = 1;
            debug_echo( "db=$db reqID=$HPCAnalysisRequestID" );

            ## Parse the HPC Request XML
            $parser = new XMLReader();
            $xok = $parser->xml( $RequestXml );

            debug_echo( "  xok=$xok", 2 );

            while( $parser->read() ) {
                debug_echo( " XREAD", 2 );
                $type = $parser->nodeType;
                debug_echo( "  type=$type ", 2 );
                if ( $type == XMLReader::ELEMENT ) {
                    $name  = $parser->name;
                    $value = $parser->getAttribute( 'value' );
                    if ( $name == 'datasetCount' ) {
                        $dsetCount  = $value;
                        debug_echo( "    name=$name  value=$value", 2 );
                    } else if ( $name == 'global_fit' ) {
                        $global_fit = $value;
                        debug_echo( "    name=$name  value=$value", 2 );
                    } else if ( $name == 'mc_iterations' ) {
                        $MCiters    = $value;
                        debug_echo( "    name=$name  value=$value", 2 );
                    }
                }
            }
            $parser->close();

            ## Modify model count where appropriate
            $oModelCount = $ModelCount;
            if ( $MCiters > 1 ) {
                ## Count one model per iteration
                $testModelCount = $dsetCount * $MCiters;

                ##if ( $testModelCount > $ModelCount )
                if ( $oModelCount < $MCiters ) {
                    ## If newer composite MC models, multiply by iterations
                    $ModelCount *= $MCiters;
                }
            }

            $ResultCount = $ModelCount + $NoiseCount;
            debug_echo( "      dsCnt=$dsetCount gfit=$global_fit mcit=$MCiters mCount=$ModelCount (old: $oModelCount)", 2 );
            $dsetCount  = 1;
            $global_fit = 0;
            $MCiters    = 1;

            if ( !strlen( $endTime ) ) {
                $endTime = '0000-00-00 00:00:00';
            }

            $query  =
                "INSERT INTO $globaldb.submissions "
                . "SET HPCAnalysis_ID = $HPCAnalysisRequestID, "
                . "db = '$db', "
                . "DateTime = '$startTime', "
                . "EndDateTime = '$endTime', "
                . "CPUTime = '$CPUTime', "
                . "Cluster_Name = '$clusterName', "
                . "CPU_Number = $CPUCount, "
                . "Result_Count = $ResultCount, "
                . "InvestigatorID = $investigatorID, "
                . "Investigator_Name = '$investigatorName', "
                . "SubmitterID = $submitterID, "
                . "Submitter_Name = '$submitterName' "
                ;

            $res2 = mysqli_query( $db_handle, $query );
            if ( !$res2 ) {
                error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
            }
        }
        echo ".";

        ## Now a table of investigator statistics
        $query  =
            "SELECT personID, "
            . "CONCAT(lname, ', ', fname) AS investigatorName, "
            . "email, signup, lastLogin, userlevel " 
            . "FROM $db.people "
            . "ORDER BY lname, fname "
            ;

        $res = mysqli_query( $db_handle, $query );
        if ( !$res ) {
            error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
        }
        echo ".";

        ## Now the global database
        while ( $row = mysqli_fetch_array( $res ) ) {
            ## Make variables
            foreach ($row as $key => $value ) {
                $$key = $value;
            }

            $investigatorName = fix_single_quote( $investigatorName );

            if ( !strlen( $lastLogin ) ) {
                $lastLogin = '0000-00-00 00:00:00';
            }

            $query  =
                "INSERT INTO $globaldb.investigators "
                . "SET InvestigatorID = $personID, "
                . "Investigator_Name = '$investigatorName', "
                . "db = '$db', "
                . "Email = '$email', "
                . "Signup = '$signup', "
                . "LastLogin = '$lastLogin', "
                . "Userlevel = '$userlevel' "
                ;
            
            $res2 = mysqli_query( $db_handle, $query );
            if ( !$res2 ) {
                error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
            }
        }
        echo ".\n";
    }
}

if ( $summaryinfo ) {

    ## get min/max date info

    $query  =
        "SELECT MIN( DateTime ), MAX( EndDateTime ) "
        . "FROM $globaldb.submissions "
        . "WHERE Cluster_Name != '' "       ## These were canceled jobs
        . "AND DateTime NOT LIKE '0000%' "
        ;

    echo "$query\n";
    
    $res = mysqli_query($db_handle, $query);
    if ( !$res ) {
        error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
    }

    while ( list( $min, $max ) = mysqli_fetch_array( $res ) ) {
        $dateinfo = "Earliest usage recorded : $min\nLatest usage recorded   : $max\n";
    }


    ## list clusters used in the created db
    ## get the information about clusters
    $query  =
        "SELECT DISTINCT Cluster_Name "
        . "FROM $globaldb.submissions "
        . "WHERE Cluster_Name != '' "       ## These were canceled jobs
        . "AND DateTime NOT LIKE '0000%' "
        . "ORDER BY Cluster_Name "
        ;
    
    $res = mysqli_query($db_handle, $query);
    if ( !$res ) {
        error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
    }

    $usedlist = [];
    $noconsol = [];
    while ( list( $Cluster_Name ) = mysqli_fetch_array( $res ) ) {
        $usedlist[] = $Cluster_Name;
        if ( !array_key_exists( $Cluster_Name, $usage_consolidation ) ) {
            $noconsol[] = $Cluster_Name;
        }
    }
    echoline("=");
    echo "$globaldb summary information\n";
    echoline();
    echo $dateinfo;
    echoline();
    echo "Clusters used\n";
    echoline();
    sort( $usedlist );
    echo implode( "\n", $usedlist ) . "\n";
    if ( count( $noconsol ) ) {
        echoline();
        echo "Clusters used but not consolidated in \$usage_consolidation\n";
        echoline();
        echo implode( "\n", $noconsol ) . "\n";
    }
    echoline("=");
}

if ( $csv ) {
    ## make csv
    $hostname  = gethostname();
    $tarray    = explode( ".", $hostname );
    $servname  = $tarray[ 1 ];

    $usage_consolidation_map = array_flip_safe( $usage_consolidation );

    ## get the information about clusters
    $query  =
        "SELECT DISTINCT Cluster_Name "
        . "FROM $globaldb.submissions "
        . "WHERE Cluster_Name != '' "       ## These were canceled jobs
        . "AND DateTime NOT LIKE '0000%' "
        . "ORDER BY Cluster_Name "
        ;
    $res = mysqli_query($db_handle, $query);
    if ( !$res ) {
        error_exit( "db query failed : $q\ndb query error: " . mysqli_error($db_handle) );
    }

    $cluster_list   = [];
    $cluster_where  = [];
    
    while ( list( $Cluster_Name ) = mysqli_fetch_array( $res ) ) {
        if ( array_key_exists( $Cluster_Name, $usage_consolidation ) ) {
            $usename = $usage_consolidation[ $Cluster_Name ];
        } else {
            $usename = $Cluster_Name;
        }

        if ( $csvall || in_array( $usename, $usage_approvedList ) ) {
            if ( !in_array( $usename, $cluster_list ) ) {
                $cluster_list[]   = $usename;
            }
        }
    }

    sort( $cluster_list );

    ## build search keys
    $cluster_all = [];

    foreach ( $cluster_list as $cluster ) {

        ## group all consolidated

        if ( array_key_exists( $cluster, $usage_consolidation_map ) ) {
            $cluster_where[ $cluster ] = "( Cluster_Name = '" . implode( "' OR Cluster_Name ='", $usage_consolidation_map[ $cluster ] ) . "') ";
            $cluster_all = array_merge( $cluster_all, $usage_consolidation_map[ $cluster ] );
        } else {
            $cluster_where[ $cluster ] = "Cluster_Name = '$cluster' ";
            $cluster_all[] = $cluster;
        }
    }
    $cluster_all_where = "( Cluster_Name = '" . implode( "' OR Cluster_Name ='", $cluster_all ) . "') ";

    debug_json( "cluster_list", $cluster_list, 1 );
    debug_json( "cluster_all", $cluster_all, 1 );
    debug_json( "cluster_where", $cluster_where, 1 );
    debug_json( "cluster_all_where", $cluster_all_where, 1 );

    ## For figuring out the starting and ending dates
    $days   = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

    # leap year calculation if desired
    # $leap = date('L', mktime(0, 0, 0, 1, 1, $year));
    # echo $year . ' ' . ($leap ? 'is' : 'is not') . ' a leap year.';

    $super_tot = 0;
    $analysis_count_tot = 0;

    ## Title row
    ##echo "\"Server\",\"Year\",\"Month\",\"Cluster\"" .
    ##     ",\"CPUHours\",\"#investigators\",\"#submitters\",\"#Analyses\"\n";
    echo "\"Server\",\"Year\",\"Month\",";
    foreach ( $cluster_list as $cluster ) {
        echo "\"CpuHrs-" . $cluster . "\",";
    }

    echo "\"CpuHrs-TOTAL\",\"#investigators\",\"#submitters\",\"#Analyses\"\n";

    for ( $year = $startyear; $year <= $endyear; $year++ ) {

        ## Title row
        $grand_tot = 0;
        $analysis_count_month = 0;
        $analysis_count_year = 0;

        for ( $month = 1; $month <= 12; $month++ ) {
            $month2d = ( $month < 10 ) ? "0$month" : $month;
            $start   = "$year-$month2d-01";
            $end     = "$year-$month2d-{$days[$month]}";
            $month_sum = 0;
            printf( "\"%s\",\"%s\",\"%s\",",
                    $servname, $year, $months[$month] );

            foreach ( $cluster_list as $cluster ) {

                ## CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
                $query  =
                    "SELECT SUM(CPUTime*CPU_Number)/3600.0 "
                    . "FROM $globaldb.submissions "
                    . "WHERE " . $cluster_where[ $cluster ]
                    . "AND DateTime >= '$start 00:00:00' "
                    . "AND DateTime <= '$end 23:59:59' "
                    ;

                debug_echo( $query );
                $res = mysqli_query( $db_handle, $query );
                if ( !$res ) {
                    error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
                }

                list( $time ) = mysqli_fetch_array( $res );
                $time = ( $time == NULL ) ? 0 : $time;
                $month_sum += $time;
                
                printf( "\"%.2f\",", $time );
            }
            
            ## Now let's get investigator and submitter stats
            $query  =
                "SELECT COUNT(DISTINCT Investigator_Name), "
                . "COUNT(DISTINCT Submitter_Name), "
                . "SUM(GREATEST(1,Result_Count)) "
                . "FROM $globaldb.submissions "
                . "WHERE DateTime >= '$start 00:00:00' "
                . "AND DateTime <= '$end 23:59:59' "
                . "AND $cluster_all_where "
                ;
            $res = mysqli_query($db_handle, $query);
            if ( !$res ) {
                error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
            }
            
            $grand_tot += $month_sum;
            
            list( $inv_count, $sub_count, $result_sum ) = mysqli_fetch_array( $res );
            
            ## Now let's get investigator and submitter stats
            $query  =
                "SELECT HPCAnalysis_ID "
                . "FROM $globaldb.submissions "
                . "WHERE DateTime >= '$start 00:00:00' "
                . "AND DateTime <= '$end 23:59:59' "
                . "AND $cluster_all_where "
                ;
            $res = mysqli_query($db_handle, $query);
            if ( !$res ) {
                error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
            }

            ##$analysis_count_month = mysql_num_rows( $res );
            $analysis_count_month = $result_sum;
            $analysis_count_year += $analysis_count_month;

            printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
                    $month_sum, $inv_count, $sub_count, $analysis_count_month );
        }

        ## In this case it's probably easier just to do another query for totals
        $start   = "$year-01-01";
        $end     = "$year-12-31";
        ##  $start   = "$year-06-01";
        ##  $end     = "$year-09-30";
        printf( "\"%s\",\"%s\",\"Total\",", $servname, $year );

        foreach ( $cluster_list as $cluster ) {
            $cluslike  = "$cluster.%";
            ## CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
            $query  =
                "SELECT SUM(CPUTime*CPU_Number)/3600.0 "
                . "FROM $globaldb.submissions "
                . "WHERE " . $cluster_where[ $cluster ]
                . "AND DateTime >= '$start 00:00:00' "
                . "AND DateTime <= '$end 23:59:59' "
                ;
            $res = mysqli_query($db_handle, $query);
            if ( !$res ) {
                error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
            }
            
            list( $time ) = mysqli_fetch_array( $res );
            $time = ( $time == NULL ) ? 0 : $time;

            printf( "\"%.2f\",", $time );
        }

        ## Now let's get investigator and submitter stats
        $query  =
            "SELECT COUNT(DISTINCT Investigator_Name), "
            . "COUNT(DISTINCT Submitter_Name) "
            . "FROM $globaldb.submissions "
            . "WHERE DateTime >= '$start 00:00:00' "
            . "AND DateTime <= '$end 23:59:59' "
            . "AND $cluster_all_where "
            ;

        $res = mysqli_query($db_handle, $query);
        if ( !$res ) {
            error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
        }

        list($inv_count, $sub_count) = mysqli_fetch_array( $res );

        $super_tot += $grand_tot;
        $analysis_count_tot += $analysis_count_year;
        printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
                $grand_tot, $inv_count, $sub_count, $analysis_count_year );
    }

    ## Now let's get a super-grant total from all years
    $start   = "$startyear-01-01";
    $end     = "$endyear-12-31";
    ##$start   = "$startyear-06-01";
    ##$end     = "$endyear-09-30";

    printf( "\"%s\",\"All\",\"Total\",", $servname );

    foreach ( $cluster_list as $cluster ) {
        $cluslike  = "$cluster.%";
        ## CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
        $query  =
            "SELECT SUM(CPUTime*CPU_Number)/3600.0 "
            . "FROM $globaldb.submissions "
            . "WHERE " . $cluster_where[ $cluster ]
            . "AND DateTime >= '$start 00:00:00' "
            . "AND DateTime <= '$end 23:59:59' "
            ;

        $res = mysqli_query($db_handle, $query);
        if ( !$res ) {
            error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
        }

        list( $time ) = mysqli_fetch_array( $res );
        $time = ( $time == NULL ) ? 0 : $time;

        printf( "\"%.2f\",", $time );
    }

    ## Now let's get investigator and submitter stats
    $query  =
        "SELECT COUNT(DISTINCT Investigator_Name), "
        . "COUNT(DISTINCT Submitter_Name) "
        . "FROM $globaldb.submissions "
        . "WHERE DateTime >= '$start 00:00:00' "
        . "AND DateTime <= '$end 23:59:59' "
        . "AND $cluster_all_where "
        ;

    $res = mysqli_query($db_handle, $query);
    if ( !$res ) {
        error_exit( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) );
    }

    list( $inv_count, $sub_count ) = mysqli_fetch_array( $res );

    printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
            $super_tot, $inv_count, $sub_count, $analysis_count_tot );

}
