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

        ## A base+overlay instance never
        ## settles here. discover_lims_instance_configs() resolves such an
        ## instance's $instance_path to its OVERLAY file (under
        ## /instances/), and reads $cfg from that overlay's own 'values'
        ## array directly -- $common_dir lives in the shared BASE, not the
        ## overlay (the overlay's own contract forbids it), so $cfg here
        ## never has it, no matter what the base actually says. Appending
        ## $common_dir = ...; to the overlay file would be silently inert
        ## too: us3_dbinst_config_load() only ever reads the overlay's
        ## 'values' array, never a loose top-level variable. Every --apply
        ## used to append another dead line and --check reported [todo]
        ## forever. The base is a single file shared by every overlay
        ## instance on this host -- migrations/4.3.0_dbinst_base_
        ## common_dir_backfill.php (host-scoped) fixes it once, not once
        ## per instance.
        if ( $instance_path !== null && preg_match( '#/instances/[^/]+\.php$#', $instance_path ) ) {
            report( 'ok', "$label: base+overlay instance; \$common_dir is the shared base's job, not this overlay's" );
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
            return;
        }

        ## php_append_statement_before_closing_tag() (utility.php) keeps
        ## this inside the file's existing PHP block even when it ends in a
        ## closing tag -- idempotent the same way the no-tag case already was:
        ## $cfg['common_dir'] being set is what skips this entirely on the
        ## next run.
        $contents     = (string) file_get_contents( $instance_path );
        $addition     = "\n\$common_dir = " . var_export( $common_dir_value, true ) . ";\n";
        $new_contents = php_append_statement_before_closing_tag( $contents, $addition );

        if ( file_put_contents( $instance_path, $new_contents ) === false ) {
            report( 'FAIL', "$label: could not append \$common_dir to $instance_path" );
        } else {
            $changes++;
            report( 'done', "$label: \$common_dir added to $instance_path (previous version in $saved)" );
        }
    },
];
