<?php
## Creates the dispatch scheduler's shared gfac tables (controller claims,
## rotation state, turn events, decisions) from common's dispatch_schema.php,
## the one definition every installer uses. Required before the dispatcher is
## activated, so a missing privilege is a FAIL with the exact remedy.
return [
    'id'            => 'dispatch_gfac_tables',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Creates the dispatch scheduler tables in gfac.',
    'run'           => function ( $gdb, $db, $apply, $context ) {
        global $changes;

        $schema = rtrim( common_dir_default( $context[ 'wwwpath' ] ), '/' ) . '/class/dispatch_schema.php';
        if ( !is_file( $schema ) ) {
            report( 'FAIL', "$schema not found; update the common checkout before the dispatch tables can be created" );
            return;
        }
        require_once $schema;

        $ddl = dispatch_gfac_table_ddl( $db );
        $missing = dispatch_schema_problems( $gdb, $db, array_keys( $ddl ) );
        if ( !$missing ) {
            report( 'ok', 'gfac dispatch tables exist (InnoDB)' );
            return;
        }

        report( 'todo', 'create gfac dispatch tables: ' . implode( '; ', $missing ) );
        if ( !$apply ) {
            return;
        }
        if ( !gfac_runtime_table_create_privileged( mysqli_grant_rows( $gdb ) ) ) {
            report( 'FAIL', 'the gfac account cannot create tables; as an account with CREATE on gfac run:'
                          . " php -r 'require \"$schema\"; \$l = mysqli_connect(\"localhost\", \"root\", getenv(\"DBPW\"));"
                          . " print_r(dispatch_schema_ensure(\$l, dispatch_gfac_table_ddl()));'" );
            return;
        }
        $errors = dispatch_schema_ensure( $gdb, $ddl );
        if ( $errors ) {
            report( 'FAIL', 'gfac dispatch tables: ' . implode( '; ', $errors ) );
            return;
        }
        $changes++;
        report( 'done', 'created gfac dispatch tables' );
    },
];
