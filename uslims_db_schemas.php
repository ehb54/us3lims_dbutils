<?php

$self = __FILE__;

# $debug = 1;

$notes = <<<__EOD
usage: $self {db_config_file}

dumps all schemas & procedures

my.cnf must exist in the current directory

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
            
require "utility.php";
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

# make & change to directory
newfile_dir_init( "schemas" );
if ( !chdir( $newfile_dir ) ) {
   error_exit( "Could not change to directory $newfile_dir" );
}

foreach ( $existing_dbs as $db ) {
    echoline();

    $outfile = "$db.sql";
    echo "exporting $db to $newfile_dir/$outfile\n";
       
    $cmd = "mysqldump --defaults-file=../$myconf -u root --no-data --events --routines $db | grep -v 'ENGINE=' | grep -v 'Dumping events' | grep -v 'Dumping routines' | grep -v ' Host: localhost ' > $outfile";
    run_cmd( $cmd );
}
