<?php
## Row versions in an existing instance (ultrascan-tickets#1354): the read-only preflight and the
## schema migration that put an instance on us3_sql's row-version schema in place. The migration
## runs in steps, each recorded in the instance's _dbutils_row_versions table when done, so a rerun
## resumes after the last finished step. Every step is also safe to repeat.
##
## Inputs come from the us3_sql checkout: us3_row_version_tables.tsv (the manifest), us3.sql (new
## tables), us3_row_versions.sql (the operation record table and the sequence) and
## us3_counter_reset.sql. The triggers are loaded with the routines (migrations/4.3.0_instance_procedures.php).

## The migration's steps, in the order they run.
function row_version_steps() {
    return [
        'link-keys'           => 'primary keys on the link tables, after removing identical duplicate rows',
        'solution-guid'       => 'solution.solutionGUID filled where empty, then NOT NULL and unique',
        'indexes'             => 'indexes the locking predicates use',
        'buffer-component-id' => 'bufferComponent.bufferComponentID AUTO_INCREMENT',
        'report-links'        => 'autoflowReport.aprofileID, linked from each profile\'s report IDs',
        'new-tables'          => 'autoflowReportRunState, experimentRunReservation, operationRecord and the version sequence',
        'run-reservations'    => 'a run ID reservation for each investigator\'s existing run IDs',
        'history-stages'      => 'a stages row, reporting COMPLETED, for each run in history',
        'columns'             => 'rowVersion, rowUpdatedAt, rowUpdatedBy and rowUpdatedVia on every manifest table',
        'counters'            => 'the version sequence and live AUTO_INCREMENT counters above existing values',
    ];
}

## Tables and columns instance_row_versions adds in place, which the schema check leaves to it.
function row_version_added_objects() {
    return [ 'autoflowReportRunState', 'experimentRunReservation', 'operationRecord', 'autoflowReport.aprofileID' ];
}

## Link tables that get a primary key: [ table => key columns ].
function row_version_link_keys() {
    return [
        'protocolPerson'            => [ 'protocolID', 'personID' ],
        'protocolAprofile'          => [ 'protocolID', 'aprofileID' ],
        'experimentPerson'          => [ 'experimentID', 'personID' ],
        'experimentSolutionChannel' => [ 'experimentID', 'solutionID', 'channelID' ],
        'projectPerson'             => [ 'projectID', 'personID' ],
        'imagePerson'               => [ 'imageID', 'personID' ],
        'imageProject'              => [ 'imageID', 'projectID' ],
    ];
}

## Indexes added for locking: [ table => [ index name => column list ] ], named as in us3.sql.
function row_version_indexes() {
    return [
        'autoflowAnalysis'        => [ 'ndx_autoflowAnalysis_autoflowID' => 'autoflowID' ],
        'autoflowAnalysisHistory' => [ 'ndx_autoflowAnalysisHistory_autoflowID' => 'autoflowID' ],
        'pcsa_modelrecs'          => [ 'ndx_mrecs_modelID' => 'modelID' ],
        'extinctionProfile'       => [ 'ndx_component_type_ID' => 'componentType, componentID' ],
        'spectrum'                => [ 'ndx_component_type_ID' => 'componentType, componentID' ],
        'radialCalibration'       => [ 'ndx_radialCalibration_rotorCalID' => 'rotorCalID' ],
        'autoflowReportItem'      => [ 'ndx_autoflowReportItem_reportID' => 'reportID' ],
        'autoflowModelsLink'      => [ 'ndx_autoflowModelsLink_autoflowID' => 'autoflowID' ],
    ];
}

## Live tables whose copies in a history table must keep the same columns in the same order.
function row_version_history_pairs() {
    return [ 'autoflow' => 'autoflowHistory', 'autoflowAnalysis' => 'autoflowAnalysisHistory',
             'autoflowGMPReportEsign' => 'autoflowGMPReportEsignHistory' ];
}

function row_version_columns() {
    return [ 'rowVersion'    => 'BIGINT UNSIGNED NOT NULL DEFAULT 0',
             'rowUpdatedAt'  => 'TIMESTAMP NULL DEFAULT NULL',
             'rowUpdatedBy'  => 'INT NULL',
             'rowUpdatedVia' => 'VARCHAR(64) NULL' ];
}

## The manifest's rows, or null when it can't be read.
function row_version_manifest( $sql_dir ) {
    $lines = @file( rtrim( $sql_dir, '/' ) . '/us3_row_version_tables.tsv', FILE_IGNORE_NEW_LINES );
    if ( $lines === false ) {
        return null;
    }
    $rows   = [];
    $header = null;
    foreach ( $lines as $line ) {
        if ( $line === '' || $line[ 0 ] === '#' ) {
            continue;
        }
        $cols = explode( "\t", $line );
        if ( $header === null ) {
            $header = $cols;
        } elseif ( count( $cols ) === count( $header ) ) {
            $rows[] = array_combine( $header, $cols );
        }
    }
    return $rows;
}

## The manifest's tables, each once, in manifest order.
function row_version_tables( array $manifest ) {
    $tables = [];
    foreach ( $manifest as $r ) {
        $tables[ $r[ 'table' ] ] = true;
    }
    return array_keys( $tables );
}

## Rows of a query as lists, or null on error.
function rv_rows( $conn, $sql ) {
    $res = mysqli_query( $conn, $sql );
    if ( !$res ) {
        return null;
    }
    return $res === true ? [] : mysqli_fetch_all( $res, MYSQLI_NUM );
}

function rv_value( $conn, $sql ) {
    $rows = rv_rows( $conn, $sql );
    return $rows ? $rows[ 0 ][ 0 ] : null;
}

function rv_quote( $conn, $value ) {
    return "'" . mysqli_real_escape_string( $conn, (string) $value ) . "'";
}

## The steps recorded as done in $db: [ step => true ]. Empty when the marker table is missing.
function row_version_steps_done( $conn, $db ) {
    $done = [];
    foreach ( (array) rv_rows( $conn, "SELECT step FROM `$db`._dbutils_row_versions" ) as $row ) { // NOSONAR $db is validated by the caller
        $done[ $row[ 0 ] ] = true;
    }
    return $done;
}

function row_version_mark_done( $conn, $db, $step ) {
    return mysqli_query( $conn, "CREATE TABLE IF NOT EXISTS `$db`._dbutils_row_versions" // NOSONAR $db is validated by the caller
                              . " ( step VARCHAR(64) NOT NULL PRIMARY KEY, doneAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ) ENGINE=InnoDB" )
        && mysqli_query( $conn, "INSERT IGNORE INTO `$db`._dbutils_row_versions ( step ) VALUES ( " . rv_quote( $conn, $step ) . " )" ); // NOSONAR
}

## Whether $db's link table has a primary key.
function rv_has_primary_key( $conn, $db, $table ) {
    return (int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = "
                                . rv_quote( $conn, $db ) . " AND TABLE_NAME = " . rv_quote( $conn, $table )
                                . " AND CONSTRAINT_TYPE = 'PRIMARY KEY'" ) > 0;
}

function rv_columns( $conn, $db, $table ) {
    $out = [];
    foreach ( (array) rv_rows( $conn, "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = "
                                    . rv_quote( $conn, $db ) . " AND TABLE_NAME = " . rv_quote( $conn, $table )
                                    . " ORDER BY ORDINAL_POSITION" ) as $row ) {
        $out[] = $row[ 0 ];
    }
    return $out;
}

## Problems that stop the migration before it changes anything, as messages; [] when it may run.
## $context: 'backup_dir' (uslims_daily_backup.php's; null skips the backup check).
## Read-only.
function row_version_preflight( $conn, $db, array $manifest, array $context ) {
    $problems = [];
    $q = function ( $v ) use ( $conn ) { return rv_quote( $conn, $v ); };

    ## The server
    $version = (string) rv_value( $conn, 'SELECT VERSION()' );
    if ( !preg_match( '/^(\d+)\.(\d+)\.(\d+)/', $version, $v ) || version_compare( "$v[1].$v[2].$v[3]", '10.3.2', '<' )
         || stripos( $version, 'mariadb' ) === false ) {
        $problems[] = "the server is $version; row versions need MariaDB 10.3.2 or later (sequences)";
    }

    ## The loading account's rights on the instance
    $grants = implode( "\n", array_map( function ( $r ) { return $r[ 0 ]; }, (array) rv_rows( $conn, 'SHOW GRANTS' ) ) );
    $all    = preg_match( '/GRANT ALL PRIVILEGES ON (\*|`' . preg_quote( str_replace( '_', '\\_', $db ), '/' ) . '`|`'
                          . preg_quote( $db, '/' ) . '`)\.\*/', $grants );
    foreach ( [ 'TRIGGER', 'CREATE', 'ALTER' ] as $priv ) {
        if ( !$all && !preg_match( "/\\b$priv\\b/", $grants ) ) {
            $problems[] = "the instance account lacks $priv on $db";
        }
    }

    ## Binary logging: creating triggers then needs log_bin_trust_function_creators or SUPER
    $vars  = rv_rows( $conn, 'SELECT @@log_bin, @@log_bin_trust_function_creators' );
    $super = (bool) preg_match( '/GRANT (ALL PRIVILEGES|.*\bSUPER\b).* ON \*\.\*/', $grants );
    if ( $vars ) {
        $blocker = procedure_load_blocker( $vars[ 0 ][ 0 ], $vars[ 0 ][ 1 ], $super );
        if ( $blocker !== '' ) {
            $problems[] = $blocker;
        }
    }

    ## Each manifest table: present, InnoDB, a row format and indexes that allow an instant column
    ## add, and no trigger of its own on the events the version triggers use
    $tables = row_version_tables( $manifest );
    $info   = [];
    foreach ( (array) rv_rows( $conn, "SELECT TABLE_NAME, ENGINE, ROW_FORMAT, DATA_LENGTH + INDEX_LENGTH FROM information_schema.TABLES"
                                    . " WHERE TABLE_SCHEMA = " . $q( $db ) ) as $row ) {
        $info[ $row[ 0 ] ] = $row;
    }
    $fulltext = [];
    foreach ( (array) rv_rows( $conn, "SELECT DISTINCT TABLE_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = "
                                    . $q( $db ) . " AND INDEX_TYPE = 'FULLTEXT'" ) as $row ) {
        $fulltext[ $row[ 0 ] ] = true;
    }
    $own_triggers = [];
    foreach ( $tables as $t ) {
        $own_triggers[ "{$t}_bi_version" ] = true;
        $own_triggers[ "{$t}_bu_version" ] = true;
    }
    foreach ( (array) rv_rows( $conn, "SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION FROM information_schema.TRIGGERS"
                                    . " WHERE TRIGGER_SCHEMA = " . $q( $db ) ) as $row ) {
        if ( !isset( $own_triggers[ $row[ 0 ] ] ) && in_array( $row[ 1 ], $tables, true )
             && $row[ 2 ] === 'BEFORE' && in_array( $row[ 3 ], [ 'INSERT', 'UPDATE' ], true ) ) {
            $problems[] = "$db.{$row[1]} already has trigger {$row[0]} ({$row[2]} {$row[3]}); remove it or merge it into the version trigger first";
        }
    }
    $new_tables = [ 'autoflowReportRunState' => true ];
    $copy_bytes = 0;
    foreach ( $tables as $t ) {
        if ( !isset( $info[ $t ] ) ) {
            if ( !isset( $new_tables[ $t ] ) ) {
                $problems[] = "$db.$t is missing; put the instance on the release's schema first";
            }
            continue;
        }
        if ( strcasecmp( (string) $info[ $t ][ 1 ], 'InnoDB' ) !== 0 ) {
            $problems[] = "$db.$t is {$info[$t][1]}, not InnoDB";
        }
        if ( strcasecmp( (string) $info[ $t ][ 2 ], 'Compressed' ) === 0 || isset( $fulltext[ $t ] ) ) {
            $copy_bytes += 2 * (int) $info[ $t ][ 3 ];
        }
    }
    if ( $copy_bytes > 0 ) {
        $datadir = (string) rv_value( $conn, 'SELECT @@datadir' );
        $free    = @disk_free_space( $datadir );
        if ( $free === false ) {
            $problems[] = "some tables need a copying ALTER ($copy_bytes bytes) and the free space in $datadir cannot be read from here";
        } elseif ( $free < $copy_bytes ) {
            $problems[] = "some tables need a copying ALTER: $copy_bytes bytes needed, $free free in $datadir";
        }
    }

    ## Data the migration cannot repair by itself
    foreach ( row_version_link_keys() as $t => $key ) {
        if ( !isset( $info[ $t ] ) || rv_has_primary_key( $conn, $db, $t ) ) {
            continue;
        }
        $k   = implode( ', ', $key );
        $all = implode( ', ', rv_columns( $conn, $db, $t ) );
        $differ = rv_value( $conn, "SELECT COUNT(*) FROM ( SELECT $k FROM ( SELECT DISTINCT $all FROM `$db`.`$t` ) d"
                                 . " GROUP BY $k HAVING COUNT(*) > 1 ) x" ); // NOSONAR names come from this file
        if ( (int) $differ > 0 ) {
            $problems[] = "$db.$t has $differ key(s) shared by rows that differ in other columns; decide which row to keep";
        }
    }
    if ( isset( $info[ 'solution' ] ) ) {
        $dups = rv_rows( $conn, "SELECT solutionGUID, GROUP_CONCAT( solutionID ORDER BY solutionID ) FROM `$db`.solution"
                              . " WHERE solutionGUID IS NOT NULL AND solutionGUID <> '' GROUP BY solutionGUID HAVING COUNT(*) > 1 LIMIT 20" );
        foreach ( (array) $dups as $d ) {
            $problems[] = "$db.solution: solutions {$d[1]} share GUID {$d[0]}; merge them or give one a new GUID";
        }
    }

    ## Nobody else connected to the instance
    $others = rv_value( $conn, "SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB = " . $q( $db )
                             . " AND ID <> CONNECTION_ID()" );
    if ( (int) $others > 0 ) {
        $problems[] = "$others other session(s) are connected to $db; stop every writer first";
    }

    ## A backup taken after the last write; a resumed migration passes backup_dir null to skip it
    if ( !array_key_exists( 'backup_dir', $context ) || $context[ 'backup_dir' ] !== null ) {
        $problems = array_merge( $problems, row_version_backup_problems( $conn, $db, (string) ( $context[ 'backup_dir' ] ?? '' ) ) );
    }
    return $problems;
}

## Whether a uslims_daily_backup.php dump of $db exists from after its last write: the latest
## table update the server recorded, or the server's start when it recorded none.
function row_version_backup_problems( $conn, $db, $backup_dir ) {
    if ( $backup_dir === '' || !is_dir( $backup_dir ) ) {
        return [ "no backup directory (\$backup_dir in db_config.php) to check for a backup of $db; take one with"
               . " uslims_daily_backup.php after stopping the writers, then rerun" ];
    }
    $newest = 0;
    foreach ( (array) glob( rtrim( $backup_dir, '/' ) . "/$db-dump-*.sql*" ) as $f ) {
        $newest = max( $newest, (int) @filemtime( $f ) );
    }
    $last = rv_value( $conn, "SELECT UNIX_TIMESTAMP( GREATEST( COALESCE( MAX( UPDATE_TIME ), '1970-01-02' ),"
                           . " NOW() - INTERVAL ( SELECT VARIABLE_VALUE FROM information_schema.GLOBAL_STATUS"
                           . " WHERE VARIABLE_NAME = 'UPTIME' ) SECOND ) )"
                           . " FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . rv_quote( $conn, $db ) );
    if ( $newest === 0 ) {
        return [ "no backup of $db in $backup_dir; take one with uslims_daily_backup.php after stopping the writers, then rerun" ];
    }
    ## Within the same second as the last change, the backup may predate it
    if ( $last !== null && $newest <= (int) $last ) {
        return [ "the newest backup of $db in $backup_dir (" . date( 'Y-m-d H:i:s', $newest ) . ") is older than its last"
               . " change or the server's start (" . date( 'Y-m-d H:i:s', (int) $last ) . "); take a new one with"
               . " uslims_daily_backup.php, then rerun" ];
    }
    return [];
}

## Notes about data the migration keeps as it is: reports with no single profile, existing duplicate run IDs.
function row_version_notes( $conn, $db ) {
    $notes = [];
    $dup = rv_value( $conn, "SELECT COUNT(*) FROM ( SELECT ep.personID, e.runID FROM `$db`.experiment e"
                          . " JOIN `$db`.experimentPerson ep ON ep.experimentID = e.experimentID"
                          . " GROUP BY ep.personID, e.runID HAVING COUNT(*) > 1 ) d" );
    if ( (int) $dup > 0 ) {
        $notes[] = "$db: $dup run ID(s) are used by more than one experiment of the same investigator; they share one reservation";
    }
    $unlinked = rv_value( $conn, "SELECT COUNT(*) FROM `$db`.autoflowReport WHERE aprofileID IS NULL" );
    if ( (int) $unlinked > 0 ) {
        $notes[] = "$db: $unlinked report(s) are named by no profile or by several, and stay unlinked;"
                 . " coordinated writes refuse them until an operator links them";
    }
    return $notes;
}

## The CREATE statement for $name in a us3_sql file, as CREATE ... IF NOT EXISTS; null when absent.
function rv_create_statement( $sql_dir, $file, $kind, $name ) {
    $text = @file_get_contents( rtrim( $sql_dir, '/' ) . "/$file" );
    if ( $text === false
         || !preg_match( '/CREATE\s+' . $kind . '\s+(?:IF\s+NOT\s+EXISTS\s+)?`?' . preg_quote( $name, '/' ) . '`?\b.*?;/is', $text, $m ) ) {
        return null;
    }
    return preg_replace( '/^CREATE\s+' . $kind . '\s+(?:IF\s+NOT\s+EXISTS\s+)?/i', "CREATE $kind IF NOT EXISTS ", $m[ 0 ] );
}

## Runs one step. [ ok, message ]; the message is the error, or for ok a note (may be '').
## $run_file( $file ) loads a us3_sql file into $db as the instance account: [ ok, error ].
function row_version_run_step( $conn, $db, $step, array $manifest, $sql_dir, callable $run_file ) {
    $run = function ( $sql ) use ( $conn ) {
        if ( !mysqli_query( $conn, $sql ) ) {
            throw new RuntimeException( mysqli_error( $conn ) . " in: " . substr( $sql, 0, 200 ) );
        }
    };
    try {
        switch ( $step ) {
        case 'link-keys':
            foreach ( row_version_link_keys() as $t => $key ) {
                if ( rv_has_primary_key( $conn, $db, $t ) ) {
                    continue;
                }
                $k = implode( ', ', $key );
                $dup_keys = rv_rows( $conn, "SELECT $k FROM `$db`.`$t` GROUP BY $k HAVING COUNT(*) > 1" );
                if ( $dup_keys === null ) {
                    throw new RuntimeException( mysqli_error( $conn ) );
                }
                if ( $dup_keys ) {
                    ## Every column of these rows is the same, which the preflight checked: keep one of each.
                    ## They are put back as they were, so a reference older data left dangling does not stop it.
                    $run( 'SET SESSION foreign_key_checks = 0' );
                    $run( 'START TRANSACTION' );
                    $run( "CREATE TEMPORARY TABLE rv_keep AS SELECT DISTINCT * FROM `$db`.`$t` WHERE ( $k ) IN"
                        . " ( SELECT $k FROM `$db`.`$t` GROUP BY $k HAVING COUNT(*) > 1 )" );
                    $run( "DELETE t FROM `$db`.`$t` t JOIN rv_keep USING ( $k )" );
                    $run( "INSERT INTO `$db`.`$t` SELECT * FROM rv_keep" );
                    $run( 'DROP TEMPORARY TABLE rv_keep' );
                    $run( 'COMMIT' );
                    $run( 'SET SESSION foreign_key_checks = 1' );
                }
                $run( "ALTER TABLE `$db`.`$t` ADD PRIMARY KEY ( $k )" );
            }
            return [ true, '' ];

        case 'solution-guid':
            $run( "UPDATE `$db`.solution SET solutionGUID = UUID() WHERE solutionGUID IS NULL OR solutionGUID = ''" );
            $unique = (int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = "
                                           . rv_quote( $conn, $db ) . " AND TABLE_NAME = 'solution' AND COLUMN_NAME = 'solutionGUID'"
                                           . " AND NON_UNIQUE = 0" );
            $run( "ALTER TABLE `$db`.solution MODIFY solutionGUID CHAR(36) NOT NULL" . ( $unique ? '' : ', ADD UNIQUE ( solutionGUID )' ) );
            return [ true, '' ];

        case 'indexes':
            foreach ( row_version_indexes() as $t => $indexes ) {
                foreach ( $indexes as $name => $cols ) {
                    $run( "ALTER TABLE `$db`.`$t` ADD INDEX IF NOT EXISTS $name ( $cols )" );
                }
            }
            return [ true, '' ];

        case 'buffer-component-id':
            ## The column type stays the same, so the foreign key from bufferLink stays valid; MariaDB
            ## still refuses to change a referenced column while foreign-key checks are on.
            $run( 'SET SESSION foreign_key_checks = 0' );
            try {
                $run( "ALTER TABLE `$db`.bufferComponent MODIFY bufferComponentID INT(11) NOT NULL AUTO_INCREMENT" );
                ## The primary key makes the old unique index on the same column redundant; us3.sql no longer has it
                $run( "ALTER TABLE `$db`.bufferComponent DROP INDEX IF EXISTS bufferComponentID" );
            } finally {
                mysqli_query( $conn, 'SET SESSION foreign_key_checks = 1' );
            }
            return [ true, '' ];

        case 'report-links':
            $run( "ALTER TABLE `$db`.autoflowReport ADD COLUMN IF NOT EXISTS aprofileID INT(11) NULL AFTER reportMaskJson,"
                . " ADD INDEX IF NOT EXISTS ndx_autoflowReport_aprofileID ( aprofileID )" );
            ## Each profile's XML lists its report IDs in report_id attributes, comma-separated.
            ## A report named by exactly one profile is linked to it; any other stays null.
            $run( "UPDATE `$db`.autoflowReport r JOIN (
                     WITH RECURSIVE ids AS (
                       SELECT aprofileID, TRIM( REPLACE( ExtractValue( xml, '//@report_id' ), ',', ' ' ) ) AS rest
                       FROM `$db`.analysisprofile
                       UNION ALL
                       SELECT aprofileID, TRIM( SUBSTRING( rest, LOCATE( ' ', CONCAT( rest, ' ' ) ) + 1 ) )
                       FROM ids WHERE rest <> ''
                     )
                     SELECT CAST( SUBSTRING_INDEX( rest, ' ', 1 ) AS UNSIGNED ) AS reportID, MIN( aprofileID ) AS aprofileID
                     FROM ids WHERE rest <> '' GROUP BY 1 HAVING COUNT( DISTINCT aprofileID ) = 1
                   ) l ON l.reportID = r.reportID
                   SET r.aprofileID = l.aprofileID
                   WHERE r.aprofileID IS NULL" );
            return [ true, '' ];

        case 'new-tables':
            foreach ( [ [ 'us3.sql', 'TABLE', 'autoflowReportRunState' ], [ 'us3.sql', 'TABLE', 'experimentRunReservation' ],
                        [ 'us3_row_versions.sql', 'TABLE', 'operationRecord' ], [ 'us3_row_versions.sql', 'SEQUENCE', 'us3_row_version_seq' ] ] as $o ) {
                $sql = rv_create_statement( $sql_dir, $o[ 0 ], $o[ 1 ], $o[ 2 ] );
                if ( $sql === null ) {
                    throw new RuntimeException( "{$o[0]} in $sql_dir does not define {$o[2]}; update the us3_sql checkout" );
                }
                $run( "USE `$db`" );
                $run( rtrim( $sql, ';' ) );
            }
            return [ true, '' ];

        case 'run-reservations':
            $run( "INSERT IGNORE INTO `$db`.experimentRunReservation ( personID, runID )"
                . " SELECT DISTINCT ep.personID, e.runID FROM `$db`.experiment e"
                . " JOIN `$db`.experimentPerson ep ON ep.experimentID = e.experimentID" );
            return [ true, '' ];

        case 'history-stages':
            $run( "INSERT INTO `$db`.autoflowStages ( autoflowID, reporting )"
                . " SELECT h.ID, 'COMPLETED' FROM `$db`.autoflowHistory h"
                . " LEFT JOIN `$db`.autoflowStages s ON s.autoflowID = h.ID WHERE s.autoflowID IS NULL" );
            return [ true, '' ];

        case 'columns':
            ## In manifest order, so each live table and its history table are altered one after the other
            $copy = [];
            foreach ( row_version_tables( $manifest ) as $t ) {
                $add = [];
                foreach ( row_version_columns() as $c => $type ) {
                    $add[] = "ADD COLUMN IF NOT EXISTS $c $type";
                }
                $sql = "ALTER TABLE `$db`.`$t` " . implode( ', ', $add );
                if ( !mysqli_query( $conn, "$sql, ALGORITHM=INSTANT" ) ) {
                    ## The preflight planned for this: a row format or index that rules out an instant add
                    $run( $sql );
                    $copy[] = $t;
                }
            }
            return [ true, $copy ? 'altered with a copy: ' . implode( ', ', $copy ) : '' ];

        case 'counters':
            list( $ok, $why ) = $run_file( 'us3_counter_reset.sql' );
            return [ $ok, $ok ? '' : $why ];
        }
    } catch ( RuntimeException $e ) {
        mysqli_query( $conn, 'ROLLBACK' );
        mysqli_query( $conn, 'SET SESSION foreign_key_checks = 1' );
        return [ false, $e->getMessage() ];
    }
    return [ false, "unknown step $step" ];
}

## Whether $db already has everything the steps add, as an instance built from this release's us3.sql does.
function row_version_schema_complete( $conn, $db, array $manifest ) {
    if ( row_version_problems( $conn, $db, $manifest, false ) ) {
        return false;
    }
    foreach ( array_keys( row_version_link_keys() ) as $t ) {
        if ( !rv_has_primary_key( $conn, $db, $t ) ) {
            return false;
        }
    }
    $q = rv_quote( $conn, $db );
    foreach ( row_version_indexes() as $t => $indexes ) {
        foreach ( array_keys( $indexes ) as $name ) {
            if ( !(int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = $q"
                                       . " AND TABLE_NAME = " . rv_quote( $conn, $t ) . " AND INDEX_NAME = " . rv_quote( $conn, $name ) ) ) {
                return false;
            }
        }
    }
    return (int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = $q AND TABLE_NAME = 'autoflowReport'"
                                . " AND COLUMN_NAME = 'aprofileID'" ) === 1
        && (int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = $q AND TABLE_NAME = 'bufferComponent'"
                                . " AND COLUMN_NAME = 'bufferComponentID' AND EXTRA LIKE '%auto_increment%'" ) === 1
        && (int) rv_value( $conn, "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = $q AND TABLE_NAME = 'solution'"
                                . " AND COLUMN_NAME = 'solutionGUID' AND IS_NULLABLE = 'NO'" ) === 1;
}

## What $db lacks of the row-version schema once migrated and loaded, as messages; [] when complete.
## $with_triggers also checks both version triggers on every manifest table.
function row_version_problems( $conn, $db, array $manifest, $with_triggers ) {
    $problems = [];
    $want = array_keys( row_version_columns() );
    foreach ( row_version_tables( $manifest ) as $t ) {
        $cols = rv_columns( $conn, $db, $t );
        if ( !$cols ) {
            $problems[] = "$db.$t is missing";
        } elseif ( array_slice( $cols, -4 ) !== $want ) {
            $problems[] = "$db.$t lacks the row-version columns as its last columns";
        }
    }
    foreach ( row_version_history_pairs() as $live => $history ) {
        if ( rv_columns( $conn, $db, $live ) !== rv_columns( $conn, $db, $history ) ) {
            $problems[] = "$db.$live and $db.$history do not have the same columns in the same order";
        }
    }
    foreach ( [ 'operationRecord', 'autoflowReportRunState', 'experimentRunReservation' ] as $t ) {
        if ( !rv_columns( $conn, $db, $t ) ) {
            $problems[] = "$db.$t is missing";
        }
    }
    if ( rv_value( $conn, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . rv_quote( $conn, $db )
                        . " AND TABLE_NAME = 'us3_row_version_seq' AND TABLE_TYPE = 'SEQUENCE'" ) != 1 ) {
        $problems[] = "$db has no us3_row_version_seq sequence";
    }
    if ( $with_triggers ) {
        $have = [];
        foreach ( (array) rv_rows( $conn, "SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = "
                                        . rv_quote( $conn, $db ) ) as $row ) {
            $have[ $row[ 0 ] ] = true;
        }
        $missing = [];
        foreach ( row_version_tables( $manifest ) as $t ) {
            foreach ( [ 'bi', 'bu' ] as $e ) {
                if ( !isset( $have[ "{$t}_{$e}_version" ] ) ) {
                    $missing[] = "{$t}_{$e}_version";
                }
            }
        }
        if ( $missing ) {
            $problems[] = "$db lacks version trigger(s) " . implode( ', ', array_slice( $missing, 0, 10 ) )
                        . ( count( $missing ) > 10 ? ' and ' . ( count( $missing ) - 10 ) . ' more' : '' );
        }
    }
    return $problems;
}
