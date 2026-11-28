<?php
## Puts an instance on us3_sql's row-version schema in place: the keys, indexes, links and tables
## coordinated writes need, then the row-version columns and counters (row_versions.php). A
## read-only preflight runs first and stops it before any change. Each step is recorded in the
## instance when done, so a rerun resumes after the last one. The triggers are loaded with the
## routines afterwards (instance_procedures).
require_once dirname( __DIR__ ) . '/row_versions.php';

return [
    'id'            => 'instance_row_versions',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => 'Adds row versions and the schema fixes coordinated writes need to each instance database.',
    'run'           => function ( $conn, $db, $apply, $context ) {
        global $changes;

        $label    = $context[ 'label' ];
        $sql_dir  = $context[ 'sql_dir' ];
        foreach ( [ 'us3_row_version_tables.tsv', 'us3_row_versions.sql', 'us3_counter_reset.sql' ] as $file ) {
            if ( in_array( $file, $context[ 'sql_missing' ] ?? [], true ) ) {
                return;
            }
        }
        $manifest = row_version_manifest( $sql_dir );
        if ( $manifest === null ) {
            report( 'FAIL', "$label: $sql_dir/us3_row_version_tables.tsv cannot be read; update the us3_sql checkout first" );
            return;
        }
        sql_identifier_or_die( $db );

        $done    = row_version_steps_done( $conn, $db );
        $pending = array_diff_key( row_version_steps(), $done );
        ## An instance built from this release's us3.sql (newinst, dbupgrade, dbmigrate) needs none of the steps
        if ( !$done && row_version_schema_complete( $conn, $db, $manifest ) ) {
            if ( $apply ) {
                ## Its rows may still need the backfills, which only add links and rows
                foreach ( row_version_data_steps() as $step ) {
                    list( $ok, $message ) = row_version_run_step( $conn, $db, $step, $manifest, $sql_dir, function () { return [ true, '' ]; } );
                    if ( !$ok ) {
                        report( 'FAIL', "$label ($db): row versions, step $step: " . reason( $message ) );
                        return;
                    }
                }
                foreach ( array_keys( row_version_steps() ) as $step ) {
                    row_version_mark_done( $conn, $db, $step );
                }
            }
            report( 'ok', "$label ($db): has the row-version schema" );
            return;
        }
        if ( !$pending ) {
            if ( !row_version_problems( $conn, $db, $manifest, false ) ) {
                report( 'ok', "$label ($db): has the row-version schema" );
                return;
            }
            ## A later release's manifest names tables this one did not: they get their columns,
            ## and the counters are raised again, after the whole preflight, backup included.
            ## Anything else still missing is reported below.
            $pending = array_intersect_key( row_version_steps(), [ 'columns' => true, 'counters' => true ] );
            $later_tables = true;
        }

        ## Before the first change, the whole preflight; on a rerun the backup from before the first
        ## change is still the way back, and the steps already done changed the instance since.
        $preflight_context = $context;
        if ( $done && empty( $later_tables ) ) {
            $preflight_context[ 'backup_dir' ] = null;
        }
        $problems = row_version_preflight( $conn, $db, $manifest, $preflight_context );
        if ( $problems ) {
            foreach ( $problems as $p ) {
                report( 'FAIL', "$label ($db): row versions: $p" );
            }
            report( 'FAIL', "$label ($db): row versions not added; nothing was changed by this step" );
            return;
        }

        report( 'todo', "$label ($db): " . ( $done ? 'continue adding' : 'add' ) . " row versions: "
                      . implode( '; ', array_values( $pending ) ) );
        if ( !$apply ) {
            return;
        }

        $run_file = function ( $file ) use ( $context, $sql_dir ) {
            return load_instance_sql_file( $context[ 'cfg' ], $sql_dir, $file, $context[ 'mysql_options' ] ?? '' );
        };
        foreach ( array_keys( $pending ) as $step ) {
            list( $ok, $message ) = row_version_run_step( $conn, $db, $step, $manifest, $sql_dir, $run_file );
            if ( !$ok || !row_version_mark_done( $conn, $db, $step ) ) {
                report( 'FAIL', "$label ($db): row versions, step $step: " . reason( $ok ? mysqli_error( $conn ) : $message )
                              . ". Rerun to resume from this step, or restore the backup" );
                return;
            }
            $changes++;
            report( 'done', "$label ($db): " . row_version_steps()[ $step ] . ( $message !== '' ? " ($message)" : '' ) );
        }

        foreach ( row_version_notes( $conn, $db ) as $note ) {
            report( 'note', "$label: $note" );
        }
        $problems = row_version_problems( $conn, $db, $manifest, false );
        if ( $problems ) {
            report( 'FAIL', "$label ($db): row-version schema incomplete after the migration: " . implode( '; ', $problems ) );
        }
    },
];
