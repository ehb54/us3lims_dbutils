<?php
## THE BUG this answers (round-14 should-fix, dbutils#52 -- see the sibling
## instance migration, 4.3.0_common_dir_backfill.php, for why it cannot
## reach this). The dbinst schema-v1 base (dbinst-base.v1.php, shared by
## every base+overlay instance on this host) predates round 12's addition
## of $common_dir to the base contract on a host that migrated to base+
## overlay before that round landed. us3lims-roles' own config-lims-
## dbinst-base role only ever renders this file on first install (round-14
## fix, same PR) -- so once it exists without common_dir, nothing else
## ever adds it either, and every overlay instance on this host keeps
## losing its shared header/footer/CSS.
##
## Native signal, no ledger needed, same reasoning as the sibling
## migration: whether 'common_dir' is already in the base's own 'values'
## array is a direct, persistent answer -- no separate marker needed.
return [
    'id'            => 'dbinst_base_common_dir_backfill',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Backfills $common_dir into the shared dbinst schema-v1 base (dbinst-base.v1.php) if the v1 contract added it after this host migrated to base+overlay.',
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        global $changes;

        $us3_home  = $context[ 'us3_home' ];
        $wwwpath   = $context[ 'wwwpath' ];
        $base_path = "$us3_home/lims/etc/config/dbinst-base.v1.php";

        if ( !is_file( $base_path ) ) {
            report( 'ok', "no dbinst-base.v1.php at $base_path; this host has no base+overlay instances" );
            return;
        }

        $base_values = config_vars( $base_path, $why );
        if ( $base_values === null ) {
            report( 'FAIL', "could not read $base_path: " . reason( $why ) );
            return;
        }

        if ( !empty( $base_values[ 'common_dir' ] ) ) {
            report( 'ok', "dbinst-base.v1.php: \$common_dir is set" );
            return;
        }

        $common_dir_value = common_dir_default( $wwwpath );
        report( 'todo', "dbinst-base.v1.php: set 'common_dir' (missing -- every base+overlay instance"
                       . " on this host renders with no shared header, footer or CSS) to "
                       . var_export( $common_dir_value, true ) );

        if ( !$apply ) {
            return;
        }
        if ( !confirm( "Add 'common_dir' to $base_path?" ) ) {
            return;
        }

        $saved = backup_append_target( $base_path, 'dbinst-base.v1.php' );
        if ( $saved === '' ) {
            report( 'FAIL', "could not back up $base_path, so 'common_dir' was not added" );
            return;
        }

        ## Textual insertion, not a full rewrite: the file is a plain
        ## `return array('schema_version' => 1, 'values' => array(...))`
        ## literal (config-lims-dbinst-base's own template renders it this
        ## way), so inserting one new key right after the 'values' array
        ## opens is a minimal, structurally-safe change that preserves
        ## every other key's existing formatting exactly, rather than
        ## regenerating the whole file from the parsed values (which would
        ## also have to reverse-guess each value's original PHP literal
        ## formatting -- booleans, numbers, escaped strings -- for no
        ## benefit over leaving them untouched).
        $contents = (string) file_get_contents( $base_path );
        $addition = "    'common_dir' => " . var_export( $common_dir_value, true ) . ",\n";
        $new_contents = preg_replace(
            '/(\'values\'\s*=>\s*array\s*\(\s*\n)/', '$1' . $addition, $contents, 1, $count );

        if ( $count !== 1 ) {
            report( 'FAIL', "dbinst-base.v1.php does not match the expected"
                           . " \"'values' => array(\" shape; add 'common_dir' by hand" );
            return;
        }

        if ( file_put_contents( $base_path, $new_contents ) === false ) {
            report( 'FAIL', "could not write 'common_dir' to $base_path" );
        } else {
            $changes++;
            report( 'done', "dbinst-base.v1.php: 'common_dir' added (previous version in $saved)" );
        }
    },
];
