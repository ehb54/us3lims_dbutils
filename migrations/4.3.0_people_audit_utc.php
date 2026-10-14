<?php
## Converts an instance's people_audit.created_at from local time to UTC
## (dbutils#52). Idempotent via a marker table inside the instance itself
## (people_audit_utc_migration_done()/migrate_people_audit_created_at_to_utc(),
## utility.php) -- a host dbupgrade/stage1+stage2 already migrated reports
## 'ok' here, not migrated again. Shared with the standalone
## migrate_people_audit_utc.php and dbupgrade/stage2_import_dbinsts.php, so
## all three paths agree on what "already migrated" means; this file is
## only the uslims_upgrade.php-specific wiring (reporting, $changes), not a
## second implementation of the migration itself.
##
## Native signal, no generic ledger needed: people_audit_utc_migration_done()
## already answers this more precisely than a plain applied/not-applied row
## could (it also knows about the writer's time zone and the per-instance
## boundary), and is the same check two other call sites already depend on.
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

        ## A skip (false -- zone tables not loaded, the marker table could
        ## not be created, or the UPDATE itself failed) used to still count
        ## as "1 change(s) made" here, right next to its own WARNING above
        ## saying nothing actually happened. Reported as a failure instead,
        ## so it shows up in the run's own summary, not just buried in the
        ## WARNING text.
        if ( $migrated ) {
            $changes++;
        } else {
            report( 'FAIL', "$label ({$cfg['dbname']}): people_audit.created_at was not migrated;"
                           . " see the WARNING above" );
        }
    },
];
