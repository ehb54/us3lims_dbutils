<?php
## THE BUG this answers (dbinst#57/newinst#27/webinfo#17's blocking item,
## the other half of migrations/4.3.0_common_dir_backfill.php). webinfo's
## and newinst's own config.php both carry a real database connection
## (dbname 'newus3', dbutils' own registry database), so discover_lims_
## instance_configs() finds them on the same glob pass as every real
## instance -- then discards them, by design, since they are not LIMS
## tenant databases at all (see that function's own comment). That also
## means nothing ever ran the sibling instance migration above against
## them: it only ever sees entries discover_lims_instance_configs() keeps.
## On an upgraded host, re-running us3lims-roles would eventually overwrite
## both files with the current, correct template anyway (their own ansible
## tasks have no "already exists" guard) -- but the upgrade script's own
## stated goal is a host that renders fully after --apply alone, with no
## further manual step, so this migration reaches both files directly
## instead of relying on that.
##
## Native signal, same as the sibling migration: $cfg['common_dir'] either
## is or is not already set in the config discover_lims_instance_configs()
## already parsed.
return [
    'id'            => 'webinfo_newinst_common_dir_backfill',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Backfills $common_dir into webinfo\'s and newinst\'s own config.php (the host-level, non-instance configs the sibling per-instance migration cannot reach).',
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        global $changes;

        $wwwpath        = $context[ 'wwwpath' ];
        $newus3_configs = $context[ 'newus3_configs' ];

        foreach ( $newus3_configs as $label => $entry ) {
            $path = $entry[ 'path' ];
            $cfg  = $entry[ 'cfg' ];

            if ( !empty( $cfg[ 'common_dir' ] ) ) {
                report( 'ok', "$label: \$common_dir is set" );
                continue;
            }

            $common_dir_value = common_dir_default( $wwwpath );
            report( 'todo', "$label: set \$common_dir (missing -- pages render with no shared"
                           . " header, footer or CSS) to " . var_export( $common_dir_value, true ) );

            if ( !$apply ) {
                continue;
            }
            if ( !confirm( "Append \$common_dir to $path?" ) ) {
                continue;
            }

            $saved = backup_append_target( $path, "config.$label" );
            if ( $saved === '' ) {
                report( 'FAIL', "$label: could not back up $path, so \$common_dir was not added" );
                continue;
            }

            $contents     = (string) file_get_contents( $path );
            $addition     = "\n\$common_dir = " . var_export( $common_dir_value, true ) . ";\n";
            $new_contents = php_append_statement_before_closing_tag( $contents, $addition );

            if ( file_put_contents( $path, $new_contents ) === false ) {
                report( 'FAIL', "$label: could not append \$common_dir to $path" );
            } else {
                $changes++;
                report( 'done', "$label: \$common_dir added to $path (previous version in $saved)" );
            }
        }
    },
];
