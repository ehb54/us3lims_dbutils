<?php
## Loads the release's stored procedures (us3_sql's us3_procedures.sql) into an instance upgraded
## in place: only dbupgrade's stage2 reloaded them, so a fixed procedure (the GMP report's
## p_IPJson, for one) never reached such an instance. A checksum of the procedure files, kept in
## the instance as _dbutils_procedures_checksum(), says whether they are current.
return [
    'id'            => 'instance_procedures',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => "Loads the release's stored procedures into each instance database.",
    'run'           => function ( $conn, $db, $apply, $context ) {
        global $changes;

        $label = $context[ 'label' ];
        $want  = release_procedures_checksum( $context[ 'sql_dir' ] );
        if ( $want === null ) {
            report( 'FAIL', "$label: {$context['sql_dir']}/us3_procedures.sql (or a file it loads) cannot be read;"
                          . " update the us3_sql checkout first" );
            return;
        }
        if ( instance_procedures_checksum( $conn, $db ) === $want ) {
            report( 'ok', "$label ($db): stored procedures are this release's" );
            return;
        }
        report( 'todo', "$label ($db): load this release's stored procedures (us3_procedures.sql)" );
        if ( !$apply ) {
            return;
        }
        list( $ok, $why ) = load_instance_procedures( $context[ 'cfg' ], $context[ 'sql_dir' ], $want,
                                                      $context[ 'mysql_options' ] ?? '' );
        if ( !$ok ) {
            report( 'FAIL', "$label ($db): loading us3_procedures.sql failed: " . reason( $why ) );
            return;
        }
        $changes++;
        report( 'done', "$label ($db): loaded this release's stored procedures" );
    },
];
