<?php

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self [--no-checksum] {remote_config_file}

creates a binary backup of the database
must be run as root

by default every file is verified by sha256 content hash (paranoid): this reads
the entire datadir and the backup. pass --no-checksum to verify by name+size
only (much faster, no content read).

__EOD;

# parse flags (may appear anywhere) and the optional positional config file
$checksum   = true;
$positional = array();
foreach ( array_slice( $argv, 1 ) as $arg ) {
    if ( $arg === "--no-checksum" ) {
        $checksum = false;
    } else if ( $arg === "-h" || $arg === "--help" ) {
        echo $notes;
        exit;
    } else if ( substr( $arg, 0, 1 ) === "-" ) {
        fwrite( STDERR, "unknown option: $arg\n\n$notes" );
        exit( -1 );
    } else {
        $positional[] = $arg;
    }
}
if ( count( $positional ) > 1 ) {
    echo $notes;
    exit;
}

$config_file     = "db_config.php";
$use_config_file = count( $positional ) ? $positional[ 0 ] : $config_file;

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

# (#4) guard: datadir must exist and be non-empty before we take the db down.
# datadir is often a symlink (e.g. /var/lib/mysql -> /data/mysql); resolve it
# so du/find descend into the real tree instead of stopping at the link (a bare
# symlink path reports 0 bytes / 0 files). The copy below uses $datadir/*, whose
# glob already resolves through the symlink.
if ( $datadir === "" || !is_dir( $datadir ) ) {
    error_exit( "datadir '$datadir' is empty or not a directory - refusing to back up" );
}
$realdatadir = realpath( $datadir );
if ( $realdatadir === false ) {
    error_exit( "could not resolve datadir '$datadir' - refusing to back up" );
}
$src_bytes = (int) run_cmd( "du -sb $realdatadir | cut -f1" );
$src_files = (int) run_cmd( "find $realdatadir -type f | wc -l" );
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

# deep copy of datadir to newfile-/
echoline( "=" );
echo "making copy of database datadir $datadir\n";
$debug = 1;
run_cmd( "cp -rp $datadir/* $newfile_dir" );
$debug = 0;

# (#1) verify the backup matches the datadir while the db is still stopped.
# Per-file comparison: build a sorted manifest for each tree and require the two
# to be identical. This catches any renamed, missing, extra, or (checksum mode)
# corrupted file, and reports exactly which differ.
#
#   default (paranoid): "<sha256>  ./<relative-path>" -- content hash, reads
#                       every byte of the datadir AND the backup.
#   --no-checksum:      "<relative-path>\t<size>"     -- metadata only, fast.
#
# Sizes come from find -printf, not du: cp -rp preserves each file's bytes exactly
# but not directory inode sizes, so du would false-mismatch. Manifests are built
# now, before FILE_MANIFEST/SHA256SUMS/BACKUP_MANIFEST are written into the backup,
# so those artifacts don't appear as spurious extras.
echoline( "=" );

# stats + audit file list are always metadata-only (cheap), regardless of mode
$src_list  = run_cmd( "find $realdatadir -type f -printf '%P\\t%s\\n' | LC_ALL=C sort" );
$src_lines = array_values( array_filter( explode( "\n", trim( $src_list ) ), 'strlen' ) );
$src_files = count( $src_lines );
$src_bytes = 0;
foreach ( $src_lines as $l ) { $c = explode( "\t", $l ); $src_bytes += (int) end( $c ); }

if ( $checksum ) {
    $vlabel  = "name + sha256 content";
    echo "verifying backup matches datadir (per-file $vlabel)\n";
    echo "  hashing $src_files files (" . number_format( $src_bytes ) . " bytes) in datadir and backup -- this reads every byte, be patient\n";
    # cd into each tree so the paths (and thus sha256sum -c later) are relative
    $ck      = "-type f -print0 | LC_ALL=C sort -z | xargs -0 -r sha256sum";
    $src_ver = run_cmd( "cd $realdatadir && find . $ck" );
    $dst_ver = run_cmd( "cd $newfile_dir && find . $ck" );
} else {
    $vlabel  = "name + byte size";
    echo "verifying backup matches datadir (per-file $vlabel)\n";
    $src_ver = $src_list;
    $dst_ver = run_cmd( "find $newfile_dir -type f -printf '%P\\t%s\\n' | LC_ALL=C sort" );
}

if ( $src_ver !== $dst_ver ) {
    $sv = array_values( array_filter( explode( "\n", trim( $src_ver ) ), 'strlen' ) );
    $dv = array_values( array_filter( explode( "\n", trim( $dst_ver ) ), 'strlen' ) );
    $only_src = array_values( array_diff( $sv, $dv ) );
    $only_dst = array_values( array_diff( $dv, $sv ) );
    $show = 40;
    echoline( "!" );
    echo "BACKUP VERIFICATION: FAILED -- datadir and backup differ\n";
    if ( count( $only_src ) ) {
        echo "  in datadir but not matching ($vlabel) in backup (" . count( $only_src ) . "):\n";
        echo "    " . implode( "\n    ", array_slice( $only_src, 0, $show ) ) . "\n";
        if ( count( $only_src ) > $show ) { echo "    ... and " . ( count( $only_src ) - $show ) . " more\n"; }
    }
    if ( count( $only_dst ) ) {
        echo "  in backup but not matching ($vlabel) in datadir (" . count( $only_dst ) . "):\n";
        echo "    " . implode( "\n    ", array_slice( $only_dst, 0, $show ) ) . "\n";
        if ( count( $only_dst ) > $show ) { echo "    ... and " . ( count( $only_dst ) - $show ) . " more\n"; }
    }
    echoline( "!" );
    error_exit( "backup verification FAILED: datadir and backup differ (see above); the backup in '$newfile_dir' is NOT trustworthy" );
}

$verified_at = date( "Y-m-d H:i:s" );
echoline( "=" );
echo "BACKUP VERIFICATION: PASSED\n";
echo "  all $src_files files matched between datadir and backup by relative path AND " . ( $checksum ? "sha256 content hash" : "byte size" ) . "\n";
echo "  datadir  : $realdatadir\n";
echo "  backup   : $newfile_dir\n";
echo "  files    : $src_files\n";
echo "  bytes    : " . number_format( $src_bytes ) . "\n";
echo "  method   : per-file $vlabel" . ( $checksum ? "" : " (--no-checksum: content NOT hashed)" ) . "\n";
echo "  verified : $verified_at\n";
echoline( "=" );

# (#4) write manifests into the backup for later restore-time / audit verification.
# FILE_MANIFEST.txt is the per-file name+size list; in checksum mode SHA256SUMS.txt
# is the verified hash list, re-checkable with: cd <restored datadir> && sha256sum -c SHA256SUMS.txt
if ( file_put_contents( "$newfile_dir/FILE_MANIFEST.txt", $src_list ) === false ) {
    error_exit( "failed to write manifest to $newfile_dir/FILE_MANIFEST.txt" );
}
$verification_line = $checksum
    ? "per-file name+sha256 content match PASSED at $verified_at"
    : "per-file name+size match PASSED at $verified_at (--no-checksum: content NOT hashed)";
$manifest =
    "backup_timestamp: " . date( "Y-m-d H:i:s" ) . "\n"
    . "datadir: $datadir\n"
    . "mariadb_version: $db_version\n"
    . "bytes: $src_bytes\n"
    . "files: $src_files\n"
    . "verification: $verification_line\n"
    . "file_manifest: FILE_MANIFEST.txt (sorted <relative-path>\\t<size>)\n";
if ( $checksum ) {
    if ( file_put_contents( "$newfile_dir/SHA256SUMS.txt", $src_ver ) === false ) {
        error_exit( "failed to write checksums to $newfile_dir/SHA256SUMS.txt" );
    }
    $manifest .= "sha256sums: SHA256SUMS.txt (re-check: cd <restored datadir> && sha256sum -c SHA256SUMS.txt)\n";
}
if ( file_put_contents( "$newfile_dir/BACKUP_MANIFEST.txt", $manifest ) === false ) {
    error_exit( "failed to write manifest to $newfile_dir/BACKUP_MANIFEST.txt" );
}
$written = array( "BACKUP_MANIFEST.txt", "FILE_MANIFEST.txt" );
if ( $checksum ) { $written[] = "SHA256SUMS.txt"; }
echo "wrote into $newfile_dir/: " . implode( ", ", $written ) . "\n";

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

