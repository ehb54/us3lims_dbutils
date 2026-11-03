<?php
## Loads the release's stored procedures (us3_sql's us3_procedures.sql) into an instance upgraded
## in place: only dbupgrade's stage2 reloaded them, so a fixed procedure (the GMP report's
## p_IPJson, for one) never reached such an instance. The procedure files' checksum and a fingerprint
## of the routines as loaded, kept in the instance as _dbutils_procedures_checksum(), say whether
## they are current; a rollback to older procedures changes the fingerprint.
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
        $marker = instance_procedures_checksum( $conn, $db );
        if ( $marker === instance_procedures_marker( $want, instance_routines( $conn, $db ) ) ) {
            report( 'ok', "$label ($db): stored procedures are this release's" );
            return;
        }
        $vars = mysqli_query( $conn, "SELECT @@log_bin, @@log_bin_trust_function_creators" );
        $vars = $vars ? mysqli_fetch_row( $vars ) : [ 0, 1 ];
        $grants = mysqli_query( $conn, "SHOW GRANTS" );
        $super = false;
        while ( $grants && ( $g = mysqli_fetch_row( $grants ) ) ) {
            $super = $super || preg_match( '/GRANT (ALL PRIVILEGES|.*\bSUPER\b).* ON \*\.\*/', $g[ 0 ] );
        }
        $blocker = procedure_load_blocker( $vars[ 0 ], $vars[ 1 ], $super );
        if ( $blocker !== '' ) {
            report( 'FAIL', "$label ($db): this release's stored procedures cannot be loaded: $blocker" );
            return;
        }
        report( 'todo', "$label ($db): load this release's stored procedures (us3_procedures.sql); local changes to"
                      . " them are replaced" );
        if ( !$apply ) {
            return;
        }
        $before = instance_routines( $conn, $db );
        list( $ok, $why ) = load_instance_procedures( $context[ 'cfg' ], $context[ 'sql_dir' ],
                                                      function () use ( $conn, $db, $want ) {
                                                          return instance_procedures_marker( $want, instance_routines( $conn, $db ) );
                                                      },
                                                      $context[ 'mysql_options' ] ?? '' );
        if ( !$ok ) {
            $missing = array_keys( array_diff_key( $before, instance_routines( $conn, $db ) ) );
            report( 'FAIL', "$label ($db): loading us3_procedures.sql failed"
                          . ( $missing ? "; missing now: " . implode( ', ', array_map( function ( $r ) {
                                return substr( $r, strpos( $r, ' ' ) + 1 );
                            }, $missing ) ) . "; load us3_procedures.sql by hand" : '' )
                          . ": " . reason( $why ) );
            return;
        }
        $changes++;
        report( 'done', "$label ($db): loaded this release's stored procedures" );
    },
];
