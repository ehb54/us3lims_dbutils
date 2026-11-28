<?php

## Row versions in place (ultrascan-tickets#1354): the instance_row_versions and instance_procedures
## migrations on an instance built from the SQL before row versions, against a fresh install of the
## SQL after. tests/in-container.sh runs it in a throwaway container. Never point it at a real server.
##
## env SRC_SOCK: the server's socket, root reachable without a password; it also listens on 3306
##     US3_SQL_DIR: us3_sql with row versions; US3_SQL_BASE_DIR: us3_sql from before them

$repo = dirname( __DIR__ );
require_once "$repo/utility.php";
require_once "$repo/migrations.php";
require_once "$repo/row_versions.php";

$sock     = getenv( 'SRC_SOCK' );
$sql_dir  = getenv( 'US3_SQL_DIR' );
$base_dir = getenv( 'US3_SQL_BASE_DIR' );
$failures = 0;
$changes  = 0;
$reports  = [];

function check( $ok, $what, $detail = "" ) {
    global $failures;
    echo ( $ok ? "ok   " : "FAIL " ) . $what . "\n";
    if ( !$ok ) {
        $failures++;
        if ( $detail != "" ) {
            echo "       " . str_replace( "\n", "\n       ", rtrim( $detail ) ) . "\n";
        }
    }
}

## The migrations report through these, as uslims_upgrade.php's do.
function report( $status, $msg ) {
    global $reports;
    $reports[] = [ $status, $msg ];
}
function reason( $text ) {
    return trim( (string) $text );
}
function reports_with( $status ) {
    global $reports;
    return array_values( array_filter( $reports, function ( $r ) use ( $status ) { return $r[ 0 ] === $status; } ) );
}
function report_text() {
    global $reports;
    return implode( "\n", array_map( function ( $r ) { return "[{$r[0]}] {$r[1]}"; }, $reports ) );
}

function must( $h, $sql ) {
    $r = mysqli_query( $h, $sql );
    if ( !$r ) {
        echo "SETUP FAILED: " . substr( $sql, 0, 300 ) . ": " . mysqli_error( $h ) . "\n";
        exit( 2 );
    }
    return $r;
}
function value( $h, $sql ) {
    $row = mysqli_fetch_row( must( $h, $sql ) );
    return $row ? $row[ 0 ] : null;
}
function rows( $h, $sql ) {
    return mysqli_fetch_all( must( $h, $sql ), MYSQLI_ASSOC );
}
function sh( $cmd ) {
    exec( "( $cmd ) 2>&1", $out, $rc );
    if ( $rc !== 0 ) {
        echo "SETUP FAILED: $cmd\n" . implode( "\n", $out ) . "\n";
        exit( 2 );
    }
}
function load( $dir, $db, $file ) {
    global $sock;
    sh( "cd " . escapeshellarg( $dir ) . " && mysql --no-defaults -S $sock -u root $db < $file" );
}

if ( !$sql_dir || !$base_dir || !is_file( "$sql_dir/us3_row_version_tables.tsv" ) || !is_file( "$base_dir/us3.sql" ) ) {
    echo "skipped: US3_SQL_DIR and US3_SQL_BASE_DIR must name us3_sql with and before row versions\n";
    exit( 0 );
}

$root = mysqli_connect( 'localhost', 'root', '', '', 0, $sock );
$backup_dir = '/var/tmp/rv-backups';
@mkdir( $backup_dir );

## An instance as the release before row versions built it, with its account, and data the
## migration must repair or keep: identical duplicate link rows, an empty solution GUID, report
## links from profile XML (one report named twice), history runs with no stages row, and two
## experiments of one investigator sharing a run ID.
function old_instance( $db ) {
    global $root, $base_dir;
    must( $root, "DROP DATABASE IF EXISTS $db" );
    must( $root, "CREATE DATABASE $db" );
    load( $base_dir, $db, 'us3.sql' );
    load( $base_dir, $db, 'us3_procedures.sql' );
    must( $root, "CREATE USER IF NOT EXISTS '{$db}_user'@'%' IDENTIFIED BY 'pw'" );
    must( $root, "GRANT ALL ON $db.* TO '{$db}_user'@'%'" );
    $d = $db;
    foreach ( [
        "INSERT INTO $d.people ( personID, personGUID, email, password, activated, userlevel, gmpReviewerRole, userNamePAM )
         VALUES ( 2, UUID(), 'a@example.org', MD5( 'pw' ), 1, 2, 'NONE', 'a' )",
        "SET FOREIGN_KEY_CHECKS = 0",
        "SET SESSION sql_mode = ''",
        "INSERT INTO $d.protocolPerson VALUES ( 1, 2 ), ( 1, 2 ), ( 1, 2 ), ( 2, 2 )",
        "INSERT INTO $d.projectPerson VALUES ( 5, 2 ), ( 5, 2 )",
        "INSERT INTO $d.solution ( solutionGUID, description ) VALUES ( NULL, 's-null' ), ( '', 's-empty' ), ( UUID(), 's-ok' )",
        "INSERT INTO $d.autoflowReport ( reportID, reportGUID ) VALUES ( 11, UUID() ), ( 12, UUID() ), ( 13, UUID() ), ( 14, UUID() )",
        "INSERT INTO $d.analysisprofile ( aprofileID, aprofileGUID, name, xml ) VALUES
           ( 1, UUID(), 'p1', '<AnalysisProfileData><analysis_profile><channel_parms report_id=\"11,12\"/><channel_parms report_id=\"13\"/></analysis_profile></AnalysisProfileData>' ),
           ( 2, UUID(), 'p2', '<AnalysisProfileData><analysis_profile><channel_parms report_id=\"13\"/></analysis_profile></AnalysisProfileData>' )",
        "INSERT INTO $d.autoflowHistory ( ID, protName, runName, status, corrRadii, expAborted, gmpRun, devRecord, expType )
         VALUES ( 40, 'p', 'r', 'E-SIGNATURES', 'NO', 'NO', 'YES', 'NO', 'VELOCITY' ),
                ( 41, 'p', 'r', 'E-SIGNATURES', 'NO', 'NO', 'YES', 'NO', 'VELOCITY' )",
        "INSERT INTO $d.experiment ( experimentID, instrumentID, experimentGUID, runID, type, runType, runTemp, label, comment, centrifugeProtocol )
         VALUES ( 7, 1, UUID(), 'dup-run', 'velocity', 'RA', 20, 'l', '', '' ), ( 8, 1, UUID(), 'dup-run', 'velocity', 'RA', 20, 'l', '', '' ),
                ( 9, 1, UUID(), 'one-run', 'velocity', 'RA', 20, 'l', '', '' )",
        "INSERT INTO $d.experimentPerson VALUES ( 7, 2 ), ( 8, 2 ), ( 9, 2 )",
        "SET FOREIGN_KEY_CHECKS = 1",
        "SET SESSION sql_mode = DEFAULT",
    ] as $sql ) {
        must( $root, $sql );
    }
}

function instance_link( $db ) {
    return mysqli_connect( '127.0.0.1', "{$db}_user", 'pw', $db, 3306 );
}

function context( $db, $backup_dir ) {
    global $sql_dir;
    return [ 'label' => $db, 'sql_dir' => $sql_dir, 'sql_missing' => [], 'backup_dir' => $backup_dir,
             'cfg' => [ 'dbname' => $db, 'dbhost' => '127.0.0.1', 'dbusername' => "{$db}_user", 'dbpasswd' => 'pw' ],
             'mysql_options' => '--port=3306' ];
}

function migration( $id ) {
    foreach ( discover_migrations() as $m ) {
        if ( $m[ 'id' ] === $id ) {
            return $m[ 'run' ];
        }
    }
    echo "SETUP FAILED: no migration $id\n";
    exit( 2 );
}

function take_backup( $db ) {
    global $backup_dir;
    sleep( 1 );
    touch( "$backup_dir/$db-dump-" . date( 'Ymd-His' ) . ".sql.gz" );
}

function upgrade( $db, $apply ) {
    global $reports, $root;
    $reports = [];
    $conn = instance_link( $db );
    migration( 'instance_row_versions' )( $conn, $db, $apply, context( $db, $GLOBALS[ 'backup_dir' ] ) );
    if ( !reports_with( 'FAIL' ) ) {
        migration( 'instance_procedures' )( $conn, $db, $apply, context( $db, $GLOBALS[ 'backup_dir' ] ) );
    }
    mysqli_close( $conn );
}

## Everything about a schema that the migration must make match a fresh install
function schema_shape( $db ) {
    global $root;
    $shape = [];
    foreach ( rows( $root, "SELECT TABLE_NAME t, COLUMN_NAME c, ORDINAL_POSITION p, COLUMN_TYPE ty, IS_NULLABLE n, COLUMN_DEFAULT d, EXTRA e
                            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME NOT LIKE '\\_dbutils%'
                            ORDER BY t, p" ) as $r ) {
        $shape[] = "column {$r['t']}.{$r['p']} {$r['c']} {$r['ty']} null={$r['n']} default={$r['d']} {$r['e']}";
    }
    foreach ( rows( $root, "SELECT TABLE_NAME t, INDEX_NAME i, NON_UNIQUE u, GROUP_CONCAT( COLUMN_NAME ORDER BY SEQ_IN_INDEX ) c
                            FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME NOT LIKE '\\_dbutils%'
                            GROUP BY t, i, u ORDER BY t, i" ) as $r ) {
        $shape[] = "index {$r['t']}.{$r['i']} unique=" . ( $r[ 'u' ] ? 0 : 1 ) . " ({$r['c']})";
    }
    foreach ( rows( $root, "SELECT TRIGGER_NAME n, EVENT_OBJECT_TABLE t, ACTION_TIMING a, EVENT_MANIPULATION e, ACTION_STATEMENT s
                            FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '$db' ORDER BY n" ) as $r ) {
        $shape[] = "trigger {$r['n']} {$r['t']} {$r['a']} {$r['e']} " . md5( $r[ 's' ] );
    }
    foreach ( rows( $root, "SELECT TABLE_NAME t FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db' AND TABLE_TYPE = 'SEQUENCE'" ) as $r ) {
        $shape[] = "sequence {$r['t']}";
    }
    return $shape;
}

## ------------------------------------------------------------------ fresh install, for comparison
must( $root, "DROP DATABASE IF EXISTS rv_fresh" );
must( $root, "CREATE DATABASE rv_fresh" );
foreach ( [ 'us3.sql', 'us3_procedures.sql', 'us3_counter_reset.sql', 'us3_row_version_triggers.sql' ] as $f ) {
    load( $sql_dir, 'rv_fresh', $f );
}

## ------------------------------------------------------------------ preflight refusals
old_instance( 'rv_pre' );
$manifest = row_version_manifest( $sql_dir );
$conn = instance_link( 'rv_pre' );
$problems = row_version_preflight( $conn, 'rv_pre', $manifest, [ 'backup_dir' => $backup_dir ] );
check( count( preg_grep( '/no backup of rv_pre/', $problems ) ) === 1, "preflight: no backup refuses", implode( "\n", $problems ) );
take_backup( 'rv_pre' );
must( $root, "INSERT INTO rv_pre.lab ( labGUID, name ) VALUES ( UUID(), 'after the backup' )" );
$problems = row_version_preflight( $conn, 'rv_pre', $manifest, [ 'backup_dir' => $backup_dir ] );
check( count( preg_grep( '/older than its last change/', $problems ) ) === 1, "preflight: a backup older than the last write refuses", implode( "\n", $problems ) );
take_backup( 'rv_pre' );
must( $root, "CREATE TRIGGER rv_pre.site_trigger BEFORE UPDATE ON rv_pre.lab FOR EACH ROW SET NEW.name = NEW.name" );
must( $root, "INSERT INTO rv_pre.solution ( solutionGUID, description ) SELECT solutionGUID, 'copy' FROM rv_pre.solution WHERE description = 's-ok'" );
$other = instance_link( 'rv_pre' );
take_backup( 'rv_pre' );
$problems = row_version_preflight( $conn, 'rv_pre', $manifest, [ 'backup_dir' => $backup_dir ] );
check( count( preg_grep( '/already has trigger site_trigger/', $problems ) ) === 1, "preflight: a site trigger on a versioned table refuses", implode( "\n", $problems ) );
check( count( preg_grep( '/share GUID/', $problems ) ) === 1, "preflight: duplicate solution GUIDs refuse", implode( "\n", $problems ) );
check( count( preg_grep( '/other session/', $problems ) ) === 1, "preflight: another session refuses", implode( "\n", $problems ) );
mysqli_close( $other );
$before = schema_shape( 'rv_pre' );
$reports = [];
migration( 'instance_row_versions' )( $conn, 'rv_pre', true, context( 'rv_pre', $backup_dir ) );
check( reports_with( 'FAIL' ) && !reports_with( 'done' ), "a failed preflight reports FAIL and changes nothing", report_text() );
check( schema_shape( 'rv_pre' ) === $before, "the schema is untouched after a failed preflight" );
mysqli_close( $conn );

## ------------------------------------------------------------------ the migration
old_instance( 'rv_up' );
take_backup( 'rv_up' );
$changes = 0;
upgrade( 'rv_up', false );
check( !reports_with( 'FAIL' ) && reports_with( 'todo' ) && $changes === 0, "dry run: todo, no change", report_text() );
upgrade( 'rv_up', true );
check( !reports_with( 'FAIL' ), "apply: no failure", report_text() );
$fresh = schema_shape( 'rv_fresh' );
$up    = schema_shape( 'rv_up' );
check( $up === $fresh, "the upgraded instance has the fresh install's columns, indexes, triggers and sequence",
       "only upgraded:\n  " . implode( "\n  ", array_diff( $up, $fresh ) ) . "\nonly fresh:\n  " . implode( "\n  ", array_diff( $fresh, $up ) ) );

check( value( $root, "SELECT COUNT(*) FROM rv_up.protocolPerson" ) == 2, "identical duplicate link rows reduced to one" );
check( value( $root, "SELECT COUNT(*) FROM rv_up.solution WHERE solutionGUID = '' OR solutionGUID IS NULL" ) == 0, "empty solution GUIDs filled" );
check( value( $root, "SELECT GROUP_CONCAT( reportID, ':', COALESCE( aprofileID, '-' ) ORDER BY reportID ) FROM rv_up.autoflowReport" ) === '11:1,12:1,13:-,14:-',
       "reports linked to their one profile; a report named twice or never stays unlinked" );
check( value( $root, "SELECT GROUP_CONCAT( autoflowID, ':', reporting ORDER BY autoflowID ) FROM rv_up.autoflowStages" ) === '40:COMPLETED,41:COMPLETED',
       "history runs got a stages row with reporting COMPLETED" );
check( value( $root, "SELECT GROUP_CONCAT( runID ORDER BY runID ) FROM rv_up.experimentRunReservation WHERE personID = 2" ) === 'dup-run,one-run',
       "one reservation per investigator and run ID, the duplicate sharing one" );
check( count( preg_grep( '/share one reservation/', array_column( reports_with( 'note' ), 1 ) ) ) === 1, "the duplicate run ID is noted" );
check( value( $root, "SELECT COUNT(*) FROM rv_up.lab WHERE rowVersion <> 0 OR rowUpdatedAt IS NOT NULL" ) == 0, "existing rows read version 0 with no time" );
$conn = instance_link( 'rv_up' );
must( $conn, "SET @US3_ACTING_ID = 2, @US3_CLIENT = 'test/rv'" );
must( $conn, "UPDATE lab SET name = 'changed' WHERE labID = 1" );
$row = mysqli_fetch_assoc( must( $conn, "SELECT rowVersion, rowUpdatedBy, rowUpdatedVia FROM lab WHERE labID = 1" ) );
check( $row[ 'rowVersion' ] > 0 && $row[ 'rowUpdatedBy' ] == 2 && $row[ 'rowUpdatedVia' ] === 'test/rv', "the triggers version and attribute a change", json_encode( $row ) );
check( value( $root, "SELECT DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'rv_up' LIMIT 1" ) === 'rv_up_user@%',
       "the triggers' definer is the instance account" );
must( $conn, "INSERT INTO autoflow ( protName, runName, status, corrRadii, expAborted, gmpRun, devRecord, expType )
              VALUES ( 'p', 'r', 'LIVE_UPDATE', 'NO', 'NO', 'YES', 'NO', 'VELOCITY' )" );
check( mysqli_insert_id( $conn ) > 41, "a new run's ID is above the history runs" );
mysqli_close( $conn );

## A rerun changes nothing
$changes = 0;
upgrade( 'rv_up', true );
check( !reports_with( 'FAIL' ) && !reports_with( 'todo' ) && $changes === 0, "rerun: ok, nothing to change", report_text() );

## ------------------------------------------------------------------ resume after a failed step
old_instance( 'rv_resume' );
take_backup( 'rv_resume' );
$conn = instance_link( 'rv_resume' );
$steps = array_keys( row_version_steps() );
foreach ( array_slice( $steps, 0, 4 ) as $step ) {
    row_version_run_step( $conn, 'rv_resume', $step, $manifest, $sql_dir, function () { return [ true, '' ]; } );
    row_version_mark_done( $conn, 'rv_resume', $step );
}
mysqli_close( $conn );
## The steps done wrote to the instance after the backup; the resumed run still goes ahead
upgrade( 'rv_resume', true );
check( !reports_with( 'FAIL' ), "resumed from the recorded step", report_text() );
$resumed = array_column( reports_with( 'done' ), 1 );
check( !preg_grep( '/primary keys on the link tables/', $resumed ) && preg_grep( '/rowVersion, rowUpdatedAt/', $resumed ),
       "only the steps not yet recorded ran", implode( "\n", $resumed ) );
check( schema_shape( 'rv_resume' ) === $fresh, "the resumed instance matches the fresh install" );

## ------------------------------------------------------------------ an instance built from the new us3.sql
must( $root, "CREATE USER IF NOT EXISTS 'rv_fresh_user'@'%' IDENTIFIED BY 'pw'" );
must( $root, "GRANT ALL ON rv_fresh.* TO 'rv_fresh_user'@'%'" );
$reports = [];
$changes = 0;
$conn = instance_link( 'rv_fresh' );
migration( 'instance_row_versions' )( $conn, 'rv_fresh', true, context( 'rv_fresh', '/nonexistent' ) );
check( !reports_with( 'FAIL' ) && !reports_with( 'todo' ) && $changes === 0, "a fresh instance needs no steps and no backup", report_text() );
check( count( row_version_steps_done( $conn, 'rv_fresh' ) ) === count( $steps ), "and is recorded as done" );
mysqli_close( $conn );

## ------------------------------------------------------------------ import: data, counter reset, triggers
## A data-only export of the upgraded instance, as dbupgrade's stage1 makes it, into an instance built
## from us3.sql as stage2 builds one. Versions and attribution are kept; the sequence ends above them.
must( $root, "DROP DATABASE IF EXISTS rv_import" );
must( $root, "CREATE DATABASE rv_import" );
sh( "mysqldump --no-defaults -S $sock -u root --no-create-info --complete-insert --skip-triggers rv_up > /var/tmp/rv_up.sql" );
check( strpos( file_get_contents( '/var/tmp/rv_up.sql' ), 'CREATE TRIGGER' ) === false, "the export carries no triggers" );
load( $sql_dir, 'rv_import', 'us3.sql' );
load( $sql_dir, 'rv_import', 'us3_procedures.sql' );
check( prepare_imported_instance_schema( $root, 'rv_import' ), "prepare_imported_instance_schema" );
foreach ( [ 'abstractCenterpiece', 'abstractRotor', 'bufferComponent', 'editedData', 'lab', 'rotor', 'rotorCalibration' ] as $t ) {
    must( $root, "DELETE FROM rv_import.$t" );
}
sh( "mysql --no-defaults -S $sock -u root rv_import < /var/tmp/rv_up.sql" );
load( $sql_dir, 'rv_import', 'us3_counter_reset.sql' );
load( $sql_dir, 'rv_import', 'us3_row_version_triggers.sql' );
$imported = mysqli_fetch_assoc( must( $root, "SELECT rowVersion, rowUpdatedBy, rowUpdatedVia FROM rv_import.lab WHERE labID = 1" ) );
check( $imported == $row, "imported versions and attribution kept", json_encode( $imported ) . ' vs ' . json_encode( $row ) );
must( $root, "INSERT INTO rv_import.lab ( labGUID, name ) VALUES ( UUID(), 'after import' )" );
check( value( $root, "SELECT rowVersion FROM rv_import.lab WHERE name = 'after import'" ) > $row[ 'rowVersion' ], "new versions above the imported ones" );

foreach ( [ 'rv_fresh', 'rv_pre', 'rv_up', 'rv_resume', 'rv_import' ] as $db ) {
    must( $root, "DROP DATABASE IF EXISTS $db" );
}
echo $failures ? "$failures FAILED\n" : "all passed\n";
exit( $failures ? 1 : 0 );
