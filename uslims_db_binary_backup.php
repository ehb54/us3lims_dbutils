<?php

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {remote_config_file}

creates a binary backup of the database
must be run as root

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

if ( !is_admin() ) {
   error_exit( "must run as root" );
}

open_db();

# get datadir

$res = db_obj_result( $db_handle, "show global variables like 'datadir'" );
$datadir = rtrim( sprintf( "%s", $res->{'Value'} ), "/" );

echoline( "=" );
echo "datadir is $datadir\n";

# (#4) guard: datadir must exist and be non-empty before we take the db down
if ( $datadir === "" || !is_dir( $datadir ) ) {
    error_exit( "datadir '$datadir' is empty or not a directory - refusing to back up" );
}
$src_bytes = (int) run_cmd( "du -sb $datadir | cut -f1" );
$src_files = (int) run_cmd( "find $datadir -type f | wc -l" );
if ( $src_bytes <= 0 || $src_files <= 0 ) {
    error_exit( "datadir '$datadir' appears empty ($src_bytes bytes, $src_files files) - refusing to back up" );
}

# record mariadb version now, while the db is up, for the manifest
$verres = db_obj_result( $db_handle, "select version() as v" );
$db_version = sprintf( "%s", $verres->{'v'} );

# (#2) pre-flight free-space check on the backup target BEFORE stopping the db
$avail = (int) run_cmd( "df -B1 --output=avail . | tail -1" );
echoline( "=" );
echo "datadir size : $src_bytes bytes ($src_files files)\n";
echo "free on target: $avail bytes\n";
if ( $avail < $src_bytes * 1.05 ) {
    error_exit( "Not enough free space for backup: need ~" . (int)( $src_bytes * 1.05 ) . " bytes (incl. 5% headroom), have $avail" );
}
echo "OK: sufficient free space for backup\n";

# newdir  “mariadb-binary-backup’

newfile_dir_init( "mariadb-binary-backup" );

# qyn service mariadb stop
get_yn_answer( "Stop mariadb services (make sure no processes are actively updating!)", true );
run_cmd( "service mariadb stop" );

# verify db stopped

echo "Verifying db had stopped, expect a PHP Warning\n";

$db_handle = mysqli_connect( $dbhost, $user, $passwd );
if ( $db_handle ) {
    error_exit( "Can still connect to database, somehow it didn't stop?" );
}

echo "OK: db appears to be stopped as I can not connect\n";

# (#3) harden the stopped check: make sure no server process is still running
$procs = run_cmd( "pgrep -x mariadbd; pgrep -x mysqld", false );
if ( trim( $procs ) !== "" ) {
    error_exit( "mariadb/mysqld process still running (pids: " . trim( $procs ) . ") - refusing to copy" );
}
echo "OK: no mariadbd/mysqld process running\n";

# deep copy of datadir to newfile-/
echoline( "=" );
echo "making copy of database datadir $datadir\n";
$debug = 1;
run_cmd( "cp -rp $datadir/* $newfile_dir" );
$debug = 0;

# (#1) verify the backup matches the datadir while the db is still stopped.
# re-measure the source here (not the pre-stop numbers used for the space
# estimate above): a clean shutdown flushes/truncates InnoDB files, so the
# datadir as actually copied is what we must compare against.
echoline( "=" );
echo "verifying backup matches datadir\n";
$src_bytes = (int) run_cmd( "du -sb $datadir | cut -f1" );
$src_files = (int) run_cmd( "find $datadir -type f | wc -l" );
$dst_bytes = (int) run_cmd( "du -sb $newfile_dir | cut -f1" );
$dst_files = (int) run_cmd( "find $newfile_dir -type f | wc -l" );
echo "  datadir : $src_bytes bytes, $src_files files\n";
echo "  backup  : $dst_bytes bytes, $dst_files files\n";
if ( $src_bytes !== $dst_bytes ) {
    error_exit( "SIZE MISMATCH: datadir $src_bytes bytes vs backup $dst_bytes bytes (diff " . ( $src_bytes - $dst_bytes ) . ")" );
}
if ( $src_files !== $dst_files ) {
    error_exit( "FILE COUNT MISMATCH: datadir $src_files files vs backup $dst_files files" );
}
echo "OK: backup byte count and file count match datadir\n";

# (#4) write a manifest into the backup for later restore-time verification
$manifest =
    "backup_timestamp: " . date( "Y-m-d H:i:s" ) . "\n"
    . "datadir: $datadir\n"
    . "mariadb_version: $db_version\n"
    . "bytes: $src_bytes\n"
    . "files: $src_files\n";
if ( file_put_contents( "$newfile_dir/BACKUP_MANIFEST.txt", $manifest ) === false ) {
    error_exit( "failed to write manifest to $newfile_dir/BACKUP_MANIFEST.txt" );
}
echo "wrote $newfile_dir/BACKUP_MANIFEST.txt\n";

# qyn service mariadb start
if ( get_yn_answer( "Restart mariadb services? (processes might further update the db)" ) ) {
    run_cmd( "service mariadb start" );
    # make sure db is open
    echo "verify db is running\n";
    open_db();
    echo "OK: db is running\n";
} else {
    echo "WARNING: db is not running\n";
}

echoline('=');
echo
    "backups are in:\n"
    . "$newfile_dir\n"
    . "restore by:\n"
    . "service mariadb stop\n"
    . "rm -fr $datadir/*\n"
    . "cp -rp  $newfile_dir/* $datadir\n"
    . "chown -R mysql:mysql $datadir\n"
    . "service mariadb start\n"
    ;

