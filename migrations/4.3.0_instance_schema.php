<?php
## Checks that an instance has every table, column and enum value us3_sql's us3.sql defines. An in-place
## upgrade adds only this release's own objects; anything else needs dbupgrade's stage1 and stage2.
return [
    'id'            => 'instance_schema',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => "Checks each instance database has the release's tables, columns and enum values (us3.sql).",
    'run'           => function ( $conn, $db, $apply, $context ) {
        $label = $context[ 'label' ];
        if ( in_array( 'us3.sql', $context[ 'sql_missing' ] ?? [], true ) ) {
            return;
        }
        $schema = release_instance_schema( $context[ 'sql_dir' ] );
        if ( $schema === null ) {
            report( 'FAIL', "$label: {$context['sql_dir']}/us3.sql (or a file it loads) cannot be read; update the us3_sql checkout first" );
            return;
        }
        ## Added in place by people_audit_utc and dispatch_instance_tables.
        $missing = instance_schema_mismatches( $conn, $db, $schema,
                                               array_merge( dispatch_tables_newer_than_schema_revs(), [ 'people_audit.created_at_utc' ] ) );
        if ( $missing === null ) {
            report( 'FAIL', "$label ($db): could not read its columns: " . reason( mysqli_error( $conn ) ) );
            return;
        }
        if ( !$missing ) {
            report( 'ok', "$label ($db): has this release's tables, columns and enum values (us3.sql)" );
            return;
        }
        $shown = array_slice( $missing, 0, 20 );
        report( 'FAIL', "$label ($db): not on this release's schema, which an in-place upgrade doesn't change; it lacks "
                      . implode( ', ', $shown ) . ( count( $missing ) > count( $shown ) ? ' and ' . ( count( $missing ) - count( $shown ) ) . ' more' : '' )
                      . ". Its stored procedures are not loaded. Rebuild every instance from us3.sql with dbupgrade's"
                      . " stage1_export_dbinsts.php and stage2_import_dbinsts.php, then rerun" );
    },
];
