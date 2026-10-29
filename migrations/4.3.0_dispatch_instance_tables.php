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

        $gfac_server = $context[ 'gfac_server' ] ?? '';
        $here = dispatch_server_identity( $conn );
        if ( $gfac_server === '' || $here === '' || $here !== $gfac_server ) {
            report( 'FAIL', "$label ($db): its database is not on the gfac database server"
                          . " ($here vs $gfac_server); the dispatcher requires one server" );
            return;
        }

        $ddl = dispatch_instance_table_ddl( $db );
        $missing = dispatch_schema_problems( $conn, $db, array_keys( $ddl ) );
        if ( !$missing ) {
            report( 'ok', "$label ($db): dispatch tables exist (InnoDB)" );
            return;
        }

        report( 'todo', "$label ($db): create dispatch tables: " . implode( '; ', $missing ) );
        if ( !$apply ) {
            return;
        }
        $errors = dispatch_schema_ensure( $conn, $ddl );
        if ( $errors ) {
            report( 'FAIL', "$label ($db): " . implode( '; ', $errors ) );
            return;
        }
        $changes++;
        report( 'done', "$label ($db): created dispatch tables" );
    },
];
