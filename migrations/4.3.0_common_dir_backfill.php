<?php
## THE BUG this answers (round-13 blocking item, dbinst#57/newinst#27/
## webinfo#17): round 12 added $common_dir to the base+overlay v1 contract
## and to newinst's generator, so every NEW instance gets it. Nothing ever
## set it for an instance that already existed before that round landed --
## the upgrade migrated no instance's config.php at all before this, so
## every pre-existing instance kept rendering pages with no shared header,
## footer or CSS, forever, until someone edited the file by hand.
##
## Native signal, no ledger needed: $cfg['common_dir'] either is or is not
## already set in the instance's own, already-loaded config -- that is a
## more direct answer than a separate marker row could be, and it is what
## discover_lims_instance_configs() already parsed before this ever runs.
return [
    'id'            => 'common_dir_backfill',
    'since_version' => '4.3.0',
    'scope'         => 'instance',
    'description'   => 'Backfills $common_dir into an instance\'s config.php if the v1 base+overlay contract added it after this instance was created.',
    'run'           => function ( $conn, $db, $apply, $context ) {
        global $changes;

        $label         = $context[ 'label' ];
        $cfg           = $context[ 'cfg' ];
        $instance_path = $context[ 'instance_path' ];
        $wwwpath       = $context[ 'wwwpath' ];

        if ( !empty( $cfg[ 'common_dir' ] ) ) {
            report( 'ok', "$label: \$common_dir is set" );
            return;
        }

        $common_dir_value = common_dir_default( $wwwpath );
        report( 'todo', "$label: set \$common_dir (missing -- pages render with no shared"
                       . " header, footer or CSS) to " . var_export( $common_dir_value, true ) );

        if ( !$apply ) {
            return;
        }
        if ( $instance_path === null ) {
            report( 'FAIL', "$label: resolved no config file path to append \$common_dir to" );
            return;
        }
        if ( !confirm( "Append \$common_dir to $instance_path?" ) ) {
            return;
        }

        $saved = backup_append_target( $instance_path, "config.$label" );
        if ( $saved === '' ) {
            report( 'FAIL', "$label: could not back up $instance_path, so \$common_dir was not added" );
        } elseif ( file_put_contents( $instance_path,
                   "\n\$common_dir = " . var_export( $common_dir_value, true ) . ";\n", FILE_APPEND ) === false ) {
            report( 'FAIL', "$label: could not append \$common_dir to $instance_path" );
        } else {
            $changes++;
            report( 'done', "$label: \$common_dir added to $instance_path (previous version in $saved)" );
        }
    },
];
