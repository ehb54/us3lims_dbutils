<?php
## Converts an instance's people_audit.created_at from local time to UTC.
## Done when no dated row lacks its created_at_utc flag (people_audit_utc_migration_done(),
## utility.php), so a host dbupgrade/stage1+stage2 already migrated reports 'ok' here. The
## conversion itself is migrate_people_audit_created_at_to_utc(), shared with
## migrate_people_audit_utc.php and dbupgrade/stage2_import_dbinsts.php.
return [
    'id'            => 'people_audit_utc',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => 'Converts people_audit.created_at from local time to UTC.',
    'run'           => function ( $conn, $db, $apply, $context ) {
        global $changes;

        $label = $context[ 'label' ];
        $cfg   = $context[ 'cfg' ];

        if ( !is_sql_identifier( $cfg[ 'dbname' ] ) ) {
            report( 'FAIL', "$label: '{$cfg['dbname']}' is not a usable database name; convert people_audit by hand" );
            return;
        }

        if ( people_audit_utc_migration_done( $conn, $cfg[ 'dbname' ] ) ) {
            report( 'ok', "$label ({$cfg['dbname']}): people_audit already migrated to UTC" );
            return;
        }

        report( 'todo', "$label ({$cfg['dbname']}): migrate people_audit.created_at to UTC" );
        if ( !$apply ) {
            return;
        }

        ob_start();
        $migrated = migrate_people_audit_created_at_to_utc( $conn, $cfg[ 'dbname' ] );
        $out = trim( ob_get_clean() );
        foreach ( $out !== '' ? explode( "\n", $out ) : [] as $line ) {
            echo "    $line\n";
        }

        ## A skip (false: the zone cannot be resolved, or the UPDATE failed) is a failure, not a change.
        if ( $migrated ) {
            $changes++;
        } else {
            report( 'FAIL', "$label ({$cfg['dbname']}): people_audit.created_at was not migrated;"
                           . " see the WARNING above" );
        }
    },
];
