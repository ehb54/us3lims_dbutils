<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {remote_config_file}

list all domain name info
__EOD;

if ( count( $argv ) < 1 || count( $argv ) > 2 ) {
    echo $notes;
    exit;
}

$config_file = "db_config.php";
if ( count( $argv ) == 2 ) {
    $use_config_file = $argv[ 1 ];
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
            
require $use_config_file;
require "utility.php";

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

# main

# hostname check

$hostname = trim( run_cmd( 'hostname' ) );

echoline();
echo "system hostname '$hostname'\n";
echoline();


# metadata hosts check

$uniqhosts = [];
$meta_dbs  = [];

$res = db_obj_result( $db_handle, "select dbname, dbhost, limshost, status from newus3.metadata where status='completed'", True );
while( $row = mysqli_fetch_array($res) ) {
    #    debug_json( "row", $row );
    $dbname   = $row['dbname'];
    $dbhost   = $row['dbhost'];
    $limshost = $row['limshost'];
    if ( !isset( $uniqhosts[ $dbhost ] ) ) {
        $uniqhosts[ $dbhost ] = 1;
    }
    if ( $dbhost != $limshost ) {
        $warnings .= "Warning: dbhost/limshost mismatch in metadata: dbname '$dbname' dbhost '$dbhost' limshost '$limshost'\n";
        if ( !isset( $uniqhosts[ $limshost ] ) ) {
            $uniqhosts[ $limshost ] = 1;
        }
    }
    $meta_dbs[ $dbname ] = 1;
}

flush_warnings();

echo count( $uniqhosts ) . " unique host name(s) from completed metadata: " . implode( ' ', array_keys( $uniqhosts ) ) . "\n";
echoline();

$host_accepted = $hostname;
if ( count( $uniqhosts ) != 1 ) {
    $host_accepted = array_keys( $uniqhosts )[0];
    $warnings .= "Warning: more than one host defined in completed metadata, assuming the first found is correct\n";
} else {
    $host_accepted = array_keys( $uniqhosts )[0];
    if ( !isset( $uniqhosts[ $hostname ] ) ) {
        $warnings .= "Warning: metadata hostname does not match system hostname\n";
    }
}


flush_warnings();
# check dbs

$res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
$mysql_dbs = [];
while( $row = mysqli_fetch_array($res) ) {
    $this_db = (string)$row[0];
    if ( $this_db != "uslims3_global" ) {
        $mysql_dbs[ $this_db ] = 1;
    }
}

# make sure they match
foreach ( $meta_dbs as $k => $v ) {
    if ( !isset( $mysql_dbs[ $k ] ) ) {
        $warnings .= "Warning: db $k is present in completed metadata but does not exist as a mysql db\n";
    }
}

foreach ( $mysql_dbs as $k => $v ) {
    if ( !isset( $meta_dbs[ $k ] ) ) {
        $warnings .= "Warning: db $k is a mysql db but not present in completed metadata\n";
    }
}

flush_warnings( "All completed metadata dbs match existing mysql dbs" );

# check names in variables

## listen-config hostname check
$listenconfig = "/home/us3/lims/bin/listen-config.php";

$check_names[ "$listenconfig: \$servhost"  ] = trim( run_cmd( "grep -e '^\s*\$servhost\s*=' $listenconfig | tail -1 | awk -F\\\" '{ print \$2 }'" ) );
$check_names[ "$listenconfig: \$host_name" ] = trim( run_cmd( "grep -e '^\s*\$host_name\s*=' $listenconfig  | tail -1 | awk -F\\\" '{ print \$2 }'" ) );

## check config.php

$wwwpath   = "/srv/www/htdocs";
$srvconfig = "$wwwpath/uslims3/config.php";

$check_names[ "$srvconfig: \$org_site"     ] = trim( run_cmd( "grep -e '^\s*\$org_site\s*=' $srvconfig  | tail -1 | awk -F\\' '{ print \$2 }'" ) );

foreach ( $mysql_dbs as $k => $v ) {
    $instpath = "$wwwpath/uslims3/$k/config.php";
    $tmp_key  = "$instpath: \$org_site";
    $check_names[ $tmp_key ] = trim( run_cmd( "grep -e '^\s*\$org_site\s*=' $instpath  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
    if ( !preg_match( "/\/$k\$/", $check_names[ $tmp_key ] ) ) {
        $warnings .= "Warning: $tmp_key does not end with the expected extension '\/$k'\n";
    }
    $check_names[ $tmp_key ] = preg_replace( "/\/$k\$/", "", $check_names[ $tmp_key ] );
}    

## validate $check_names

foreach ( $check_names as $k => $v ) {
    if ( $v != $host_accepted ) {
        $warnings .= "Warning: $k value '$v' is not equal to our accepted host '$host_accepted'\n";
    }
}
flush_warnings( "all config variables match our accepted host '$host_accepted'" );

# check httpd configs

$httpdconfigdir = "/etc/httpd";

$httpdconfigs = run_cmd( "cd $httpdconfigdir && find . -type f | xargs grep $host_accepted" );
echo "$httpdconfigs" . "\n";
