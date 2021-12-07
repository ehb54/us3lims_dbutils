#!/usr/bin/php
<?php
/*
 * Script to copy selected db information from different individual lims
 *  tables into a temporary global database
 */

//include 'config.php';
//include 'current_db_list.php';
include 'dynamic_db_list.php';

$global_db =    'uslims3_global';

// Clear the global database
mysql_select_db($global_db, $conn2)
            or die("Could not select database $global_db on $v2_host.");
$query  = "delete from investigators";
mysql_query($query)
      or die("Query failed : $query\n" . mysql_error());
$query  = "delete from submissions";
mysql_query($query)
      or die("Query failed : $query\n" . mysql_error());

// Build global tables from each db instance's HPC Analysis tables
foreach ( $dblist as $db )
{
  echo "Using $db.";

  $query  = "SELECT q.HPCAnalysisRequestID, startTime, endTime, " .
            "CPUTime, clusterName, CPUCount, " .
            "r.HPCAnalysisResultID as resultID, " .
            "GREATEST(1,(SELECT COUNT(*) from HPCAnalysisResultData d " .
            "WHERE d.HPCAnalysisResultID = r.HPCAnalysisResultID " .
            "AND d.HPCAnalysisResultType = 'model')) as ModelCount, " .
            "GREATEST(1,(SELECT COUNT(*) from HPCAnalysisResultData d " .
            "WHERE d.HPCAnalysisResultID = r.HPCAnalysisResultID " .
            "AND d.HPCAnalysisResultType = 'noise')) as NoiseCount, " .
            "requestXMLFile as RequestXml, " .
            "i.personID AS investigatorID, s.personID AS submitterID, " .
            "CONCAT(i.lname, ', ', i.fname) AS investigatorName, " .
            "CONCAT(s.lname, ', ', s.fname) AS submitterName " .
            "FROM HPCAnalysisRequest q, HPCAnalysisResult r, " .
            "people i, people s " .
            "WHERE q.HPCAnalysisRequestID = r.HPCAnalysisRequestID " .
            "AND investigatorGUID = i.personGUID " .
            "AND submitterGUID = s.personGUID " .
            "AND queueStatus = 'completed' ";          // These are completed jobs
  mysql_select_db($db, $conn1)
            or die("Could not select database $db on $v1_host.");
  $result = mysql_query($query, $conn1)
            or die("Query failed : $query\n" . mysql_error());
  echo ".";

  // Now the global database
  mysql_select_db($global_db, $conn2)
            or die("Could not select database $global_db on $v2_host.");
  while ( $row = mysql_fetch_array($result) )
  {
//echo "get row keys\n";
    // Make variables
    foreach ($row as $key => $value )
    {
      $$key = $value;
    }

    if ( strncmp( $clusterName, "alamo.bi", 8 ) == 0 )
      $clusterName = "alamo.uthscsa.edu";
    if ( strncmp( $clusterName, "ls5.tacc", 8 ) == 0 )
      $clusterName = "lonestar5.tacc.teragrid.org";
echo "investigatorName=$investigatorName\n";
    if ( strncmp( $investigatorName, "D'souza", 7 ) == 0 )
      $investigatorName = "DSouza, Simmone";
echo "  investigatorName=$investigatorName\n";
    if ( strncmp( $submitterName, "D'souza", 7 ) == 0 )
      $submitterName = "DSouza, Simmone";
//echo "clusterName=$clusterName\n";
    $dsetCount  = 1;
    $global_fit = 0;
    $MCiters    = 1;
//echo "db=$db reqID=$HPCAnalysisRequestID\n";
//echo "db=$db\n";
//echo " reqID=$HPCAnalysisRequestID\n";

    // Parse the HPC Request XML
    $parser = new XMLReader();
    $xok = $parser->xml( $RequestXml );
//echo "  xok=$xok\n";

    while( $parser->read() )
    {
//echo " XREAD\n";
      $type = $parser->nodeType;
//echo "  type=$type\n";
      if ( $type == XMLReader::ELEMENT )
      {
        $name  = $parser->name;
        $value = $parser->getAttribute( 'value' );
        if ( $name == 'datasetCount' )
        {
          $dsetCount  = $value;
echo "    name=$name  value=$value\n";
        }
        else if ( $name == 'global_fit' )
        {
          $global_fit = $value;
echo "    name=$name  value=$value\n";
        }
        else if ( $name == 'mc_iterations' )
        {
          $MCiters    = $value;
echo "    name=$name  value=$value\n";
        }
      }
    }
    $parser->close();

    // Modify model count where appropriate
    $oModelCount = $ModelCount;
    if ( $MCiters > 1 )
    {  // Count one model per iteration
      $testModelCount = $dsetCount * $MCiters;
      //if ( $testModelCount > $ModelCount )
      if ( $oModelCount < $MCiters )
      {  // If newer composite MC models, multiply by iterations
        $ModelCount *= $MCiters;
      }
    }

    $ResultCount = $ModelCount + $NoiseCount;
echo "      dsCnt=$dsetCount gfit=$global_fit mcit=$MCiters mCount=$ModelCount (old: $oModelCount)\n";
    $dsetCount  = 1;
    $global_fit = 0;
    $MCiters    = 1;

    $query  = "INSERT INTO submissions " .
              "SET HPCAnalysis_ID = $HPCAnalysisRequestID, " .
              "db = '$db', " .
              "DateTime = '$startTime', " .
              "EndDateTime = '$endTime', " .
              "CPUTime = '$CPUTime', " .
              "Cluster_Name = '$clusterName', " .
              "CPU_Number = $CPUCount, " .
              "Result_Count = $ResultCount, " .
              "InvestigatorID = $investigatorID, " .
              "Investigator_Name = '$investigatorName', " .
              "SubmitterID = $submitterID, " .
              "Submitter_Name = '$submitterName' ";
    mysql_query($query)
          or die("Query failed : $query\n" . mysql_error());
  }
  echo ".";

  // Now a table of investigator statistics
  $query  = "SELECT personID, " .
            "CONCAT(lname, ', ', fname) AS investigatorName, " .
            "email, signup, lastLogin, userlevel " .
            "FROM people " .
            "ORDER BY lname, fname ";
  mysql_select_db($db, $conn1)
            or die("Could not select database $db on $v1_host.");
  $result = mysql_query($query, $conn1)
            or die("Query failed : $query\n" . mysql_error());
  echo ".";

  // Now the global database
  mysql_select_db($global_db, $conn2)
            or die("Could not select database $global_db on $v2_host.");
echo "I)investigatorName=$investigatorName\n";
  if ( strncmp( $investigatorName, "D'souza", 7 ) == 0 )
    $investigatorName = "DSouza, Simmone";
echo "  I)investigatorName=$investigatorName\n";
  while ( $row = mysql_fetch_array($result) )
  {
    // Make variables
    foreach ($row as $key => $value )
    {
      $$key = $value;
    }
if ( strncmp( $investigatorName, "D'souza", 7 ) == 0 )
 $investigatorName = "DSouza, Simmone";
echo "    I)investigatorName=$investigatorName\n";

    $query  = "INSERT INTO investigators " .
              "SET InvestigatorID = $personID, " .
              "Investigator_Name = '$investigatorName', " .
              "db = '$db', " .
              "Email = '$email', " .
              "Signup = '$signup', " .
              "LastLogin = '$lastLogin', " .
              "Userlevel = '$userlevel' ";
    mysql_query($query)
          or die("Query failed : $query\n" . mysql_error());

    echo ".";
  }

  echo "\n";
}


