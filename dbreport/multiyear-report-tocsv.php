#!/usr/bin/php
<?php
/*
 * multiyear-summary-report.php
 *
 * A command-line program to produce a montly statistics report
 *  on CPU usage by cluster. Uses information in HPC result tables
 *  across all databases
 * Be sure to run cluster-report.sql and cluster-report.sh
 *  first, to create a global database with all the information we need.
 *
 */

include 'config.php';

// Let's see if there is a start year and an end year
if ( $_SERVER['argc'] == 3 )
{
  $start_year = $_SERVER['argv'][1];
  $end_year   = $_SERVER['argv'][2];
}

// If one argument, then it's both start and end year
else if ( $_SERVER['argc'] == 2 )
{
  $start_year = $_SERVER['argv'][1];
  $end_year   = $_SERVER['argv'][1];
}

else
{
  // No parameters, so use this year 
  $start_year = date('Y');
  $end_year   = date('Y');
}

$hostname  = gethostname();
if ( preg_match( "/novalo/", $hostname ) )
  $hostname  = "uslims3.aucsolutions.com";
$tarray    = explode( ".", $hostname );
$servname  = $tarray[ 1 ];
if ( strncmp( $servname, "fz-", 3 ) == 0 )
{
  $tarray    = explode( "-", $servname );
  $servname  = $tarray[ 1 ];
}

$global_db =    'uslims3_global';
mysql_select_db($global_db, $conn2)
          or die("Could not select database $global_db on $v2_host.");

// Only if cluster is on this approved list
$approved_list = array( 'comet',
                        'js-169-137',
                        'juwels',
                        'lonestar5',
                        'stampede2',
                        'expanse',
                        'demeler3',
                        'taito',
                        'puhti',
                        'umontana',
                        'chinook',
                        'us3iab-node0' );

// Now let's get the information about clusters
$query  = "SELECT DISTINCT Cluster_Name " .
          "FROM submissions " .
          "WHERE Cluster_Name != '' " .       // These were canceled jobs
          "AND DateTime NOT LIKE '0000%' " .
          "ORDER BY Cluster_Name ";
$result = mysql_query($query)
          or die("Query failed : $query\n" . mysql_error());

$cluster_list   = array();
$shortname_list = array();
while ( list($Cluster_Name) = mysql_fetch_array($result) )
{
  $shortname        = explode(".", $Cluster_Name);

  // Combine variations of alamo, lonestar5, us3iab-node0
  if ( strncmp( $Cluster_Name, "alamo.bioch", 11 ) == 0 )
  {
    $Cluster_Name = "alamo.uthscsa.edu";
  }
  if ( strncmp( $Cluster_Name, "ls5.tacc.ut", 11 ) == 0 )
  {
    $Cluster_Name = "lonestar5.tacc.teragrid.org";
    $shortname[0] = "lonestar5";
  }
  if ( strncmp( $Cluster_Name, "us3iab-node", 11 ) == 0 )
  {
    $Cluster_Name = "us3iab-node.localhost";
    $shortname[0] = "us3iab-node";
  }
  if ( strncmp( $Cluster_Name, "js-169-137",  10 ) == 0 )
  {
    $shortname[0] = "jetstream";
  }
  if ( strncmp( $Cluster_Name, "login.gscc",  10 ) == 0 )
  {
    $shortname[0] = "umontana";
  }


  if ( in_array($shortname[0], $approved_list) )
  {
    $cluster_list[]   = $Cluster_Name;
    $shortname_list[] = $shortname[0];
  }
}

// For figuring out the starting and ending dates
$days   = array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
$super_tot = 0;
$analysis_count_tot = 0;

// Title row
//echo "\"Server\",\"Year\",\"Month\",\"Cluster\"" .
//     ",\"CPUHours\",\"#investigators\",\"#submitters\",\"#Analyses\"\n";
echo "\"Server\",\"Year\",\"Month\",";
foreach ( $approved_list as $cluster )
{
//  $shortname = explode( ".", $cluster );
  $clusname  = $cluster;
//  $cluslike  = "$cluster.%";
  if ( strncmp( $clusname, "js-169-137",  10 ) == 0 )
    $clusname = "jetstream";
  echo "\"CpuHrs-" . $clusname . "\",";
}
echo "\"CpuHrs-TOTAL\",\"#investigators\",\"#submitters\",\"#Analyses\"\n";

for ( $year = $start_year; $year <= $end_year; $year++ )
{
  // Title row
  $grand_tot = 0;
  $analysis_count_month = 0;
  $analysis_count_year = 0;

  for ( $month = 1; $month <= 12; $month++)
//  for ( $month = 6; $month <= 9; $month++)
  {
    $month2d = ( $month < 10 ) ? "0$month" : $month;
    $start   = "$year-$month2d-01";
    $end     = "$year-$month2d-{$days[$month]}";
    $month_sum = 0;
    printf( "\"%s\",\"%s\",\"%s\",",
      $servname, $year, $months[$month] );

    foreach ( $approved_list as $cluster )
    {
      $cluslike  = "$cluster.%";

      // CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
      $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
                "FROM submissions " .
                "WHERE Cluster_Name LIKE '$cluslike' " .
                "AND DateTime >= '$start 00:00:00' " .
                "AND DateTime <= '$end 23:59:59' ";
      $result = mysql_query($query)
                or die("Query failed : $query\n" . mysql_error());
 
      list($time) = mysql_fetch_array($result);
      $time = ( $time == NULL ) ? 0 : $time;
      $month_sum += $time;
 
      printf( "\"%.2f\",", $time );
    }
 
    // Now let's get investigator and submitter stats
    $query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
              "COUNT(DISTINCT Submitter_Name), " .
              "SUM(GREATEST(1,Result_Count)) " .
              "FROM submissions " .
              "WHERE DateTime >= '$start 00:00:00' " .
              "AND DateTime <= '$end 23:59:59' ";
    $result = mysql_query($query)
              or die("Query failed : $query\n" . mysql_error());
 
    $grand_tot += $month_sum;
 
    list($inv_count, $sub_count, $result_sum) = mysql_fetch_array($result);
 
    // Now let's get investigator and submitter stats
    $query  = "SELECT HPCAnalysis_ID FROM submissions WHERE DateTime >= '$start 00:00:00' AND DateTime <= '$end 23:59:59' ";
    $result = mysql_query($query)
              or die("Query failed : $query\n" . mysql_error());
    //$analysis_count_month = mysql_num_rows($result);
    $analysis_count_month = $result_sum;
    $analysis_count_year += $analysis_count_month;

    printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
      $month_sum, $inv_count, $sub_count, $analysis_count_month );
  }

  // In this case it's probably easier just to do another query for totals
  $start   = "$year-01-01";
  $end     = "$year-12-31";
//  $start   = "$year-06-01";
//  $end     = "$year-09-30";
  printf( "\"%s\",\"%s\",\"Total\",", $servname, $year );

  foreach ( $approved_list as $cluster )
  {
    $cluslike  = "$cluster.%";
    // CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
    $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
              "FROM submissions " .
              "WHERE Cluster_Name LIKE '$cluslike' " .
              "AND DateTime >= '$start 00:00:00' " .
              "AND DateTime <= '$end 23:59:59' ";
    $result = mysql_query($query)
              or die("Query failed : $query\n" . mysql_error());
 
    list($time) = mysql_fetch_array($result);
    $time = ( $time == NULL ) ? 0 : $time;

    printf( "\"%.2f\",", $time );
  }

  // Now let's get investigator and submitter stats
  $query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
            "COUNT(DISTINCT Submitter_Name) " .
            "FROM submissions " .
            "WHERE DateTime >= '$start 00:00:00' " .
            "AND DateTime <= '$end 23:59:59' ";
  $result = mysql_query($query)
            or die("Query failed : $query\n" . mysql_error());

  list($inv_count, $sub_count) = mysql_fetch_array($result);

  $super_tot += $grand_tot;
  $analysis_count_tot += $analysis_count_year;
  printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
    $grand_tot, $inv_count, $sub_count, $analysis_count_year );
}

// Now let's get a super-grant total from all years
$start   = "$start_year-01-01";
$end     = "$end_year-12-31";
//$start   = "$start_year-06-01";
//$end     = "$end_year-09-30";

printf( "\"%s\",\"All\",\"Total\",", $servname );

foreach ( $approved_list as $cluster )
{
  $cluslike  = "$cluster.%";
  // CPUTime is in seconds, so divide by 3600 so output will be in CPU Hours
  $query  = "SELECT SUM(CPUTime*CPU_Number)/3600.0 " .
            "FROM submissions " .
            "WHERE Cluster_Name LIKE '$cluslike' " .
            "AND DateTime >= '$start 00:00:00' " .
            "AND DateTime <= '$end 23:59:59' ";
  $result = mysql_query($query)
            or die("Query failed : $query\n" . mysql_error());

  list($time) = mysql_fetch_array($result);
  $time = ( $time == NULL ) ? 0 : $time;

  printf( "\"%.2f\",", $time );
}

// Now let's get investigator and submitter stats
$query  = "SELECT COUNT(DISTINCT Investigator_Name), " .
          "COUNT(DISTINCT Submitter_Name) " .
          "FROM submissions " .
          "WHERE DateTime >= '$start 00:00:00' " .
          "AND DateTime <= '$end 23:59:59' ";
$result = mysql_query($query)
          or die("Query failed : $query\n" . mysql_error());

list($inv_count, $sub_count) = mysql_fetch_array($result);

printf( "\"%.2f\",\"%d\",\"%d\",\"%d\"\n",
  $super_tot, $inv_count, $sub_count, $analysis_count_tot );

exit();

