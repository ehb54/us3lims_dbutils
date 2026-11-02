<?php
## Creates the dispatch scheduler's shared gfac tables (controller claims,
## rotation state, turn events, decisions) from us3_sql's gfac/gfac_dispatch.sql,
## their one definition, with common's dispatch_schema.php loader. Required before the dispatcher is
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
        $sql_file = $context[ 'sql_dir' ] . '/gfac/gfac_dispatch.sql';
        if ( !is_file( $sql_file ) ) {
            report( 'FAIL', "$sql_file not found; update the us3_sql checkout before the dispatch tables can be created" );
            return;
        }

        $missing = dispatch_schema_problems( $gdb, $db, dispatch_gfac_tables() );
        if ( !$missing ) {
            report( 'ok', 'gfac dispatch tables exist (InnoDB)' );
            return;
        }

        ## Checked in --check too, so --check reports what --apply would fail on.
        if ( !gfac_runtime_table_create_privileged( mysqli_grant_rows( $gdb ) ) ) {
            report( 'FAIL', 'the gfac account cannot create tables; as an account with CREATE on gfac run:'
                          . " php -r 'require \"$schema\"; \$l = mysqli_connect(\"localhost\", \"root\", getenv(\"DBPW\"));"
                          . " print_r(dispatch_schema_ensure(\$l, \"gfac\", \"$sql_file\"));'" );
            return;
        }
        report( 'todo', 'create gfac dispatch tables: ' . implode( '; ', $missing ) );
        if ( !$apply ) {
            return;
        }
        $errors = dispatch_schema_ensure( $gdb, $db, $sql_file );
        if ( $errors ) {
            report( 'FAIL', 'gfac dispatch tables: ' . implode( '; ', $errors ) );
            return;
        }
        $changes++;
        report( 'done', 'created gfac dispatch tables' );
    },
];
