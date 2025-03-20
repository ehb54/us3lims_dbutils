<?php

# user defines

$undrop_code_dir    = "undrop-for-innodb";
$stream_parser      = "$undrop_code_dir/stream_parser";
$c_parser           = "$undrop_code_dir/c_parser";

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;
date_default_timezone_set('UTC');

$notes = <<<__EOD
usage: $self {db_config_file}

accumulates all bufferLink data from mariadb-binary-backup* and optionally inserts if needed into current

my.cnf must exist in the current directory

__EOD;

$notes = <<<__EOD
usage: $self {options}


Options

--help                 : print this information and exit
    
--db                   : db [required]
--update               : updates the current db


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs             = false;

$db                  = "";
$update              = false;

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
            $db  = array_shift( $u_argv );
            break;
        }
        case "--update": {
            array_shift( $u_argv );
            $update = true;
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
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" 
               . "[client]\n"
               . "password=YOUR_ROOT_DB_PASSWORD\n"
               . "max_allowed_packet=256M\n"
               . "[mysqldump]\n"
               . "password=YOUR_ROOT_DB_PASSWORD\n"
               . "max_allowed_packet=256M\n"
   );
}
file_perms_must_be( $myconf );

if ( !is_dir( $undrop_code_dir ) ) {
    error_exit( "Directory $undrop_code_dir is missing, this is required" );
}

if ( !file_exists( $stream_parser )
     || !file_exists( $c_parser ) ) {
    error_exit( "$stream_parser & $c_parser do not exist, please run 'make' in $undrop_code_dir and try again" );
}

## get list of bufferLink.ibd's

$bl_ibds = array_filter( explode( "\n", trim( `find . -name "bufferLink.ibd" | grep 'mariadb-binary-backup' | grep $db` ) ) );

if ( !count( $bl_ibds ) ) {
    error_exit( "no bufferLink.ibd found for database $db in mariadb-binary-backup*" );
}

debug_json( "found bufferLinks:", $bl_ibds );

## could also be extracted from ../sql/us3.sql 

$bufferLink_create_contents = <<<__EOD
CREATE TABLE bufferLink (
  bufferID int(11) NOT NULL ,
  bufferComponentID int(11) NOT NULL ,
  concentration FLOAT NULL ,
  INDEX ndx_bufferLink_bufferID (bufferID ASC) ,
  INDEX ndx_bufferLink_bufferComponentID (bufferComponentID ASC) ,
  CONSTRAINT fk_bufferLink_bufferID
    FOREIGN KEY (bufferID )
    REFERENCES buffer (bufferID )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_bufferLink_bufferComponentID
    FOREIGN KEY (bufferComponentID )
    REFERENCES bufferComponent (bufferComponentID )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;

__EOD;

foreach ( $bl_ibds as $v ) {
    echoline( "=" );
    echo "processing $v\n";
    echoline();
    newfile_dir_init( "bufferLink-recovery-tmp" );
    echo "using directory $newfile_dir\n";

    $bufferLink_create_file = "bufferLink-create.sql";
    if ( !file_put_contents( "$newfile_dir/$bufferLink_create_file", $bufferLink_create_contents ) ) {
        error_exit( "error creating $newfile_dir/$bufferLink_create_file" );
    }

    $cmd = "cd $newfile_dir && ../$stream_parser -f ../$v";
    run_cmd( $cmd );

    $cmd = "cd $newfile_dir && find pages-bufferLink.ibd/FIL_PAGE_INDEX -name '*.page'";
    $res = run_cmd( $cmd, true, true );
    debug_json( "res", $res );

    foreach ( $res as $page ) {
        $cmd = "cd $newfile_dir && ../$c_parser -6f $page -t $bufferLink_create_file";
        print "$cmd\n";
    }
    error_exit( "testing" );
}


    
