<?php

$self = __FILE__;

# $debug = 1;

$notes = <<<__EOD
usage: $self metadata_dbhost {config_file}

extracts selected metadata in xml format
metadata_dbhost is the dbhost field to match in metadata
if config_file specified, it will be used instead of ../db config
writes to metadata-'metadata_dbhost'.xml

__EOD;

if ( count( $argv ) < 2 || count( $argv ) > 3 ) {
    echo $notes;
    exit;
}

$metadata_dbhost = $argv[ 1 ];

$config_file = "../db_config.php";
if ( count( $argv ) == 3 ) {
    $use_config_file = $argv[ 2 ];
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
            
require "../utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

$metadata_file = "metadata-$metadata_dbhost.xml";

if ( file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file exists, please remove or rename before proceeding" );
}


$cmd = "mysql -u root -p --xml -e 'SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, secure_user, secure_pw, admin_fname, admin_lname, admin_email, admin_pw, lab_name, lab_contact, location, instrument_name, instrument_serial, status FROM metadata where status=\"completed\" and dbhost=\"$metadata_dbhost\" ' newus3 > $metadata_file";

run_cmd( $cmd );

if ( !file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file was not created" );
}

$metadata = simplexml_load_string( file_get_contents( $metadata_file ) );
if ( isset( $debug ) && $debug ) {
    debug_json( "json of metadata", $metadata );
}

$dbnames      = [];
$dbnames_used = [];
$dbnames_dupd = [];

foreach ( $metadata->{'row'} as $row ) {
     $this_dbname = (string)$row->field[2];
     $dbnames[]   = $this_dbname;
     if ( array_key_exists( $this_dbname, $dbnames_used ) ) {
         $dbnames_dupd[] = $this_dbname;
     }
     $dbnames_used[ $this_dbname ] = 1;
}

if ( count( $dbnames_dupd ) ) {
    echoline( '=' );
    echo "WARNING: the following duplicate dbname records exist!\n" . 
         "---> this *NEEDS* to be corrected before proceeding\n" .
         "---> edit the metadata xml before running stage2\n";
    echoline();
    echo implode( "\n", $dbnames_dupd );
    echo "\n";
    echoline();
}

    
echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );
echo "Next:\nmanually edit $metadata_file and then run stage2 to export the data\n";
echoline( '=' );

    
