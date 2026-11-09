<?php
## Creates the dbinst base and instances/ directory a 4.3.0 host needs for newinst to make an
## instance. us3lims-roles' config-lims-dbinst-base creates them; a host upgraded by the README
## alone had neither. Values come from the host's own configs; the contract (file name, keys,
## types) from an instance's dbinst checkout, which also validates the result.

## Each base key's value: the one every legacy instance agrees on, else newinst's, else a host default.
function dbinst_base_values_from_host( array $types, array $template, array $instances, array $newinst,
                                       $wwwpath, &$problems, &$notes ) {
    $www = rtrim( $wwwpath, '/' );
    $defaults = [
        'dbinst_root' => isset( $newinst[ 'dest_path' ] ) ? rtrim( $newinst[ 'dest_path' ], '/' ) . '/' : "$www/uslims3/",
        'common_dir'  => common_dir_default( $wwwpath ),
        'class_dir'   => "$www/common/class/",
        'submit_dir'  => "$www/uslims3/uslims3_data/",
        'timezone'    => date_default_timezone_get(),
    ];
    ## The template's values that stand for a real host's, so never a default.
    $host_only = [ 'org_name', 'site_author', 'ipaddr', 'ipa_ext' ];

    $values = [];
    foreach ( $types as $key => $type ) {
        $seen = [];
        foreach ( $instances as $cfg ) {
            if ( is_array( $cfg ) && !isset( $cfg[ 'error' ] ) && array_key_exists( $key, $cfg ) ) {
                $seen[ var_export( $cfg[ $key ], true ) ] = $cfg[ $key ];
            }
        }
        if ( $key === 'dbinst_root' ) {
            $value = $defaults[ $key ];
        } elseif ( count( $seen ) === 1 ) {
            $value = reset( $seen );
        } elseif ( array_key_exists( $key, $newinst ) ) {
            $value = $newinst[ $key ];
        } elseif ( $key === 'ipa_ext' && isset( $values[ 'ipaddr' ] ) ) {
            $value = $values[ 'ipaddr' ];
        } elseif ( array_key_exists( $key, $defaults ) ) {
            $value = $defaults[ $key ];
        } elseif ( $type === 'boolean' ) {
            $value = false;
            if ( $seen ) {
                $notes[] = "the instances differ on $key, so new instances get false"
                         . " (dbinst's CONFIGURATION.md turns it on for one instance)";
            }
        } elseif ( array_key_exists( $key, $template ) && !in_array( $key, $host_only, true ) ) {
            $value = $template[ $key ];
        } else {
            $problems[] = $seen ? "the instances differ on $key" : "no instance or newinst config sets $key";
            continue;
        }
        if ( $type === 'boolean' ) {
            $value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
        } elseif ( $type === 'integer' ) {
            $value = (int) $value;
        } else {
            $value = (string) $value;
        }
        $values[ $key ] = $value;
    }
    return $values;
}

return [
    'id'            => 'dbinst_base_install',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Creates the dbinst base and instances/ directory, as the roles do, on a host that has neither.',
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        global $changes;

        $wwwpath     = $context[ 'wwwpath' ];
        $config_root = rtrim( $context[ 'us3_home' ], '/' ) . '/lims/etc/config';
        $instances   = "$config_root/instances";
        $owner       = $context[ 'owner' ];
        $group       = $context[ 'web_group' ];

        ## The contract comes from a dbinst checkout; README step 5 moved every one to this release.
        $loader = '';
        foreach ( glob( rtrim( $wwwpath, '/' ) . '/uslims3/*/lib/dbinst_config_loader.php' ) ?: [] as $candidate ) {
            if ( is_file( dirname( $candidate, 2 ) . '/config-base.v1.php.template' ) ) {
                $loader = $candidate;
                break;
            }
        }
        if ( $loader === '' ) {
            report( 'note', "no dbinst checkout under uslims3/; the dbinst base is checked once an instance exists" );
            return;
        }
        $php = escapeshellarg( PHP_BINARY ) . ' -r ';
        list( $out, $err, $rc ) = capture( $php . escapeshellarg(
            'require ' . var_export( $loader, true ) . '; $t = include ' . var_export( dirname( $loader, 2 ) . '/config-base.v1.php.template', true )
            . '; echo json_encode( [ us3_dbinst_config_base_filename(), us3_dbinst_config_base_types(), $t[ "values" ] ] );' ) );
        $contract = json_decode( $out, true );
        if ( $rc !== 0 || !is_array( $contract ) || count( $contract ) !== 3 ) {
            report( 'FAIL', "could not read the dbinst base contract from $loader: " . reason( trim( $err ) ) );
            return;
        }
        list( $base_name, $types, $template ) = $contract;
        $base_path = "$config_root/$base_name";

        $want_dir = function ( $path, $mode ) use ( $apply, $owner, $group, &$changes ) {
            $gid = posix_getgrnam( $group )[ 'gid' ] ?? null;
            $uid = posix_getpwnam( $owner )[ 'uid' ] ?? null;
            if ( is_dir( $path ) && !is_link( $path ) && fileowner( $path ) === $uid && filegroup( $path ) === $gid
                 && ( fileperms( $path ) & 07777 ) === $mode ) {
                report( 'ok', sprintf( "%s is %o %s:%s", $path, $mode, $owner, $group ) );
                return true;
            }
            report( 'todo', sprintf( "create %s as %o %s:%s", $path, $mode, $owner, $group ) );
            if ( !$apply ) {
                return false;
            }
            if ( is_link( $path ) || ( !is_dir( $path ) && !@mkdir( $path, $mode, true ) )
                 || !chown( $path, $owner ) || !chgrp( $path, $group ) || !chmod( $path, $mode ) ) {
                report( 'FAIL', "could not create $path with that owner and mode" );
                return false;
            }
            $changes++;
            report( 'done', sprintf( "%s created as %o %s:%s", $path, $mode, $owner, $group ) );
            return true;
        };

        if ( is_file( $base_path ) ) {
            report( 'ok', "$base_path exists" );
            $want_dir( $instances, 02750 );
            return;
        }

        $problems = [];
        $notes    = [];
        $newinst  = [];
        foreach ( $context[ 'newus3_configs' ] as $entry ) {
            if ( array_key_exists( 'dest_path', $entry[ 'cfg' ] ) ) {
                $newinst = $entry[ 'cfg' ];
            }
        }
        $values = dbinst_base_values_from_host( $types, $template, $context[ 'instance_configs' ], $newinst,
                                                $wwwpath, $problems, $notes );
        if ( $problems ) {
            report( 'FAIL', "$base_path is missing and can't be made from this host's configs: " . implode( '; ', $problems )
                          . ". Copy " . dirname( $loader, 2 ) . "/config-base.v1.php.template there, set every value,"
                          . " and rerun" );
            return;
        }
        report( 'todo', "create $base_path (0640 $owner:$group) from this host's instance and newinst configs, as the roles do" );
        foreach ( $notes as $note ) {
            report( 'note', $note );
        }
        if ( !$apply || !confirm( "Create $base_path?" ) ) {
            return;
        }
        if ( !$want_dir( $config_root, 0750 ) ) {
            return;
        }

        ## Written and validated beside the base, then moved into place, so a bad base is never left behind.
        $stage = "$config_root/.base-" . getmypid();
        $source = "<?php\n## Shared dbinst configuration. Made by uslims_upgrade.php from this host's configs;"
                . " edit here to change a value.\nreturn " . var_export( [ 'schema_version' => 1, 'values' => $values ], true ) . ";\n";
        $staged = @mkdir( $stage, 0700 ) && file_put_contents( "$stage/$base_name", $source ) !== false;
        if ( $staged ) {
            list( , $err, $rc ) = capture( $php . escapeshellarg(
                'require ' . var_export( $loader, true ) . '; us3_dbinst_config_load_base( ' . var_export( $stage, true ) . ' );' ) );
        }
        if ( !$staged || $rc !== 0 ) {
            @unlink( "$stage/$base_name" );
            @rmdir( $stage );
            report( 'FAIL', "$base_path was not created: " . reason( $staged ? trim( $err ) : 'could not write it' ) );
            return;
        }
        $placed = chown( "$stage/$base_name", $owner ) && chgrp( "$stage/$base_name", $group )
               && chmod( "$stage/$base_name", 0640 ) && rename( "$stage/$base_name", $base_path );
        @unlink( "$stage/$base_name" );
        @rmdir( $stage );
        if ( !$placed ) {
            report( 'FAIL', "$base_path was not created: could not set its owner or move it into place" );
            return;
        }
        $changes++;
        report( 'done', "$base_path created" );
        $want_dir( $instances, 02750 );
    },
];
