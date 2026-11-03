<?php
## Creates one instance's dispatch request, attempt and event tables, after
## confirming its database is on the same server as gfac: the dispatcher
## couples instance and gfac changes in one transaction, which a split
## server (or two RDS instances) cannot provide.
return [
    'id'            => 'dispatch_instance_tables',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => 'Creates the dispatch scheduler tables in each instance database.',
    'run'           => function ( $conn, $db, $apply, $context ) {
        global $changes;

        $label  = $context[ 'label' ];
        $schema = rtrim( common_dir_default( $context[ 'wwwpath' ] ), '/' ) . '/class/dispatch_schema.php';
        if ( !is_file( $schema ) ) {
            report( 'FAIL', "$label: $schema not found; update the common checkout first" );
            return;
        }
        require_once $schema;
        if ( in_array( 'us3_dispatch.sql', $context[ 'sql_missing' ] ?? [], true ) ) {
            return;
        }
        $sql_file = $context[ 'sql_dir' ] . '/us3_dispatch.sql';
        if ( !is_file( $sql_file ) ) {
            report( 'FAIL', "$label: $sql_file not found; update the us3_sql checkout first" );
            return;
        }

        $gfac_server = $context[ 'gfac_server' ] ?? '';
        $here = dispatch_server_identity( $conn );
        if ( $gfac_server === '' || $here === '' || $here !== $gfac_server ) {
            report( 'FAIL', "$label ($db): its database is not on the gfac database server"
                          . " ($here vs $gfac_server); the dispatcher requires one server" );
            return;
        }

        $missing = dispatch_schema_problems( $conn, $db, dispatch_instance_tables() );
        if ( !$missing ) {
            report( 'ok', "$label ($db): dispatch tables exist (InnoDB)" );
            ## Job numbers restart after a controller reset, so the job key includes the request.
            $key = schema_query( $conn, "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS "
                               . "WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dispatch_attempt' AND INDEX_NAME = 'schedulerJob'", $db );
            $cols = $key ? (string) ( mysqli_fetch_row( $key )[ 0 ] ?? '' ) : '';
            if ( $cols === 'cluster,schedulerJobID,dispatchRequestID' ) {
                return;
            }
            report( 'todo', "$label ($db): add the request to dispatch_attempt's job key" );
            if ( !$apply ) {
                return;
            }
            $alter = "ALTER TABLE `$db`.dispatch_attempt " . ( $cols !== '' ? 'DROP INDEX schedulerJob, ' : '' )
                   . 'ADD UNIQUE KEY schedulerJob (cluster, schedulerJobID, dispatchRequestID)';
            if ( !mysqli_query( $conn, $alter ) ) {
                report( 'FAIL', "$label ($db): " . mysqli_error( $conn ) );
                return;
            }
            $changes++;
            report( 'done', "$label ($db): added the request to dispatch_attempt's job key" );
            return;
        }

        report( 'todo', "$label ($db): create dispatch tables: " . implode( '; ', $missing ) );
        if ( !$apply ) {
            return;
        }
        $errors = dispatch_schema_ensure( $conn, $db, $sql_file );
        if ( $errors ) {
            report( 'FAIL', "$label ($db): " . implode( '; ', $errors ) );
            return;
        }
        $changes++;
        report( 'done', "$label ($db): created dispatch tables" );
    },
];
