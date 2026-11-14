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

## db_config.php's dbinst entry in $repo_branches, '' when it has none or an unrendered SET- one.
function dbinst_base_entry_ref( $db_config ) {
    $text = (string) @file_get_contents( $db_config );
    if ( !preg_match( '#"https://github\.com/ehb54/us3lims_dbinst\.git"\s*=>\s*"([^"]*)"#', $text, $m ) ) {
        return '';
    }
    $ref = trim( $m[ 1 ] );
    return strpos( $ref, 'SET-' ) === 0 ? '' : $ref;
}

## A host with no instance yet: a temporary clone of dbinst at db_config.php's entry, as newinst's
## LIMS script clones one, gives the contract. [ loader path, clone dir ], or [ '', '' ] after a FAIL.
function dbinst_base_contract_clone( $db_config ) {
    $ref = dbinst_base_entry_ref( $db_config );
    $fix = "set the dbinst entry in $db_config's \$repo_branches to this release's dbinst branch or tag, then rerun";
    ## A private directory (mktemp, 0700): the loader in it is run as root.
    list( $dir, $err, $rc ) = capture( 'mktemp -d ' . escapeshellarg( sys_get_temp_dir() . '/us3-dbinst.XXXXXXXX' ) );
    if ( $rc !== 0 || !is_dir( trim( $dir ) ) ) {
        report( 'FAIL', "could not make a temporary directory to read the dbinst base's contract: " . reason( $err ) );
        return [ '', '' ];
    }
    $dir = trim( $dir );
    list( , $err, $rc ) = capture( 'git clone --quiet --depth 1 ' . ( $ref === '' ? '' : '--branch ' . escapeshellarg( $ref ) . ' ' )
                                   . 'https://github.com/ehb54/us3lims_dbinst.git ' . escapeshellarg( "$dir/dbinst" ) );
    $loader = "$dir/dbinst/lib/dbinst_config_loader.php";
    $what = "no dbinst checkout under uslims3/, and dbinst '" . ( $ref === '' ? 'default branch' : $ref ) . "'";
    if ( $rc !== 0 ) {
        exec( 'rm -rf ' . escapeshellarg( $dir ) );
        report( 'FAIL', "$what could not be cloned to read the base's contract: " . reason( $err ) . "; $fix" );
        return [ '', '' ];
    }
    if ( !is_file( $loader ) || !is_file( "$dir/dbinst/config-base.v1.php.template" ) ) {
        exec( 'rm -rf ' . escapeshellarg( $dir ) );
        report( 'FAIL', "$what has no config loader; $fix" );
        return [ '', '' ];
    }
    return [ $loader, $dir ];
}

## $path as a directory with $mode, owned by $owner:$group, made when missing.
function dbinst_base_want_dir( $path, $mode, $apply, $owner, $group ) {
    global $changes;
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
}

## The base and instances/ from $loader's contract; $temporary when $loader is in a clone removed after.
function dbinst_base_install_from( $loader, $temporary, $apply, array $context, $config_root, $instances ) {
    global $changes;
    $wwwpath = $context[ 'wwwpath' ];
    $owner   = $context[ 'owner' ];
    $group   = $context[ 'web_group' ];
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

    $want_dir = function ( $path, $mode ) use ( $apply, $owner, $group ) {
        return dbinst_base_want_dir( $path, $mode, $apply, $owner, $group );
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
                      . ". Copy " . ( $temporary ? "config-base.v1.php.template from this release's dbinst"
                                                 : escapeshellarg( dirname( $loader, 2 ) . "/config-base.v1.php.template" ) )
                      . " there as " . escapeshellarg( (string) $base_name ) . ", set every value, and rerun" );
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
    record_created( $base_path );
    $changes++;
    report( 'done', "$base_path created" );
    $want_dir( $instances, 02750 );
}

return [
    'id'            => 'dbinst_base_install',
    'since_version' => '4.3.0',
    'scope'         => 'host',
    'description'   => 'Creates the dbinst base and instances/ directory, as the roles do, on a host that has neither.',
    'run'           => function ( $db_handle, $db, $apply, $context ) {
        $wwwpath     = $context[ 'wwwpath' ];
        $config_root = rtrim( $context[ 'us3_home' ], '/' ) . '/lims/etc/config';
        $instances   = "$config_root/instances";

        ## The contract comes from a dbinst checkout; README step 5 moved every one to this release.
        ## With none, a temporary clone at db_config.php's dbinst entry, as newinst's LIMS script would clone.
        $loader = '';
        foreach ( glob( rtrim( $wwwpath, '/' ) . '/uslims3/*/lib/dbinst_config_loader.php' ) ?: [] as $candidate ) {
            if ( is_file( dirname( $candidate, 2 ) . '/config-base.v1.php.template' ) ) {
                $loader = $candidate;
                break;
            }
        }
        $clone = '';
        if ( $loader === '' ) {
            ## A base already there needs no contract to be left alone.
            if ( glob( "$config_root/dbinst-base.v*.php" ) ) {
                report( 'ok', "a dbinst base exists in $config_root" );
                dbinst_base_want_dir( $instances, 02750, $apply, $context[ 'owner' ], $context[ 'web_group' ] );
                return;
            }
            list( $loader, $clone ) = dbinst_base_contract_clone( $context[ 'db_config' ] );
            if ( $loader === '' ) {
                return;
            }
        }
        try {
            dbinst_base_install_from( $loader, $clone !== '', $apply, $context, $config_root, $instances );
        } finally {
            if ( $clone !== '' ) {
                exec( 'rm -rf ' . escapeshellarg( $clone ) );
            }
        }
    },
];

