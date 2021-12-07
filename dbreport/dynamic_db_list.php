<?php
/*
 * dynamic_db_list.php
 *
 * a dynamically constructed list of uslims3 databases, to be included in other files
 */

include 'config.php';

$db       = "us3";
$query    = "SHOW DATABASES LIKE 'uslims3%'";
mysql_select_db( $db, $conn1 )
            or die("Could not select database $db on $v2_host.");
mysql_query( $query )
      or die("Query failed : $query\n" . mysql_error());

$result   = mysql_query( $query, $conn1 );
$dblist   = array();
while ( list($dbname) = mysql_fetch_array($result) )
{ // Accumulate the list of db instances
  $dblist[]   = $dbname;
//      echo "dbname=$dbname \n";
}

// Exclude uslims3_global from the list
$key = array_search( "uslims3_global", $dblist );
unset( $dblist[ $key ] );

