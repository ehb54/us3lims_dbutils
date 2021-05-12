<?php

$self = __FILE__;

# $debug = 1;

$notes = <<<__EOD
usage: $self {options} metadata_dbhost {config_file}

extracts selected metadata in xml format
metadata_dbhost is the dbhost field to match in metadata
if config_file specified, it will be used instead of ../db config
writes to metadata-'metadata_dbhost'.xml

Options

--help               : print this information and exit

--db name            : export for dbinst name. can be specified multiple times


__EOD;

require "../utility.php";

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs = [];

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( !count( $u_argv ) ) {
    echo $notes;
    exit;
}

$metadata_dbhost = array_shift( $u_argv );

$config_file = "../db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
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
            
if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

file_perms_must_be( $use_config_file );
require $use_config_file;

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( 
       "create a file '$myconf' in the current directory with the following contents:\n"
       . "[client]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "[mysqldump]\n"
       . "password=YOUR_ROOT_DB_PASSWORD\n"
       . "max_allowed_packet=256M\n"
       );
}
file_perms_must_be( $myconf );

$metadata_file = "metadata-$metadata_dbhost.xml";

if ( file_exists( $metadata_file ) ) {
    error_exit( "ERROR: file $metadata_file exists, please remove or rename before proceeding" );
}

$cmd = "mysql --defaults-file=$myconf -u root --xml -e 'SELECT institution, inst_abbrev, dbname, dbuser, dbpasswd, secure_user, secure_pw, admin_fname, admin_lname, admin_email, admin_pw, lab_name, lab_contact, location, instrument_name, instrument_serial, status FROM metadata where status=\"completed\" and dbhost=\"$metadata_dbhost\"";
if ( count( $use_dbs ) ) {
    $cmd .= " and (";
    $dbpos = 0;
    foreach ( $use_dbs as $db ) {
        if ( $dbpos ) {
            $cmd .= " or";
        }
        $dbpos++;
        $cmd .= " dbname=\"$db\"";
    }
    $cmd .= ")";
}

$cmd .=  "' newus3 > $metadata_file";

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

if ( count( $use_dbs ) &&
    $use_dbs != array_keys( $dbnames_used ) ) {
    error_exit( 
    "--db sepecified but found dbname records " 
    . implode( " ", array_keys( $dbnames_used ) )
    . " does not match requested --db(s) "
    . implode( " ", $use_dbs )
    . ". Perhaps you misspelled one?"
    );
}

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );
echo "Next:\nmanually edit $metadata_file if you wish to further subselect and then run stage2 to export the data\n";
echoline( '=' );

    
