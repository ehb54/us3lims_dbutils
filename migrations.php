<?php
## One-time migration registry and runner. uslims_upgrade.php calls into
## this instead of containing each one-time migration's logic inline, so
## adding a migration for a future release means dropping a new file into
## migrations/ -- never editing this file or uslims_upgrade.php itself.
##
## Each file in migrations/ returns one definition:
##   'id'            => a short, permanent name. Never reused or renamed
##                       once shipped -- it is the ledger key where one is
##                       used (see utility.php's create_migration_ledger_
##                       table() for when a migration needs its own table
##                       instead).
##   'since_version' => the release that introduced it, "x.y.z".
##   'scope'         => 'host' (runs once per host, against gfac) or
##                       'instance' (runs once per LIMS instance, against
##                       that instance's own database).
##   'description'   => one line, for --check/--list output.
##   'run'           => function( $db_handle, $db, $apply, $context ): void.
##                       Owns its whole flow: decide whether it is already
##                       done, report() 'ok' or 'todo' either way, and
##                       when $apply is true and it is not yet done,
##                       actually do the work and report 'done'/'FAIL'
##                       (incrementing the global $changes on success --
##                       same convention every other step in
##                       uslims_upgrade.php already uses). A single
##                       function rather than separate check()/apply()
##                       callbacks because that is the shape the reporting
##                       this replaces already has: report a dry-run
##                       'todo' regardless of $apply, only act when it is
##                       true -- splitting that in two would either report
##                       twice or need the runner to know report() detail
##                       that belongs to the migration, not the runner.
##
## $context is whatever instance-scope migrations need beyond the raw
## connection (e.g. $wwwpath, the instance's label and own config path) --
## opaque to this runner, passed through unchanged.

function discover_migrations() {
    static $migrations = null;
    if ( $migrations !== null ) {
        return $migrations;
    }

    $migrations = [];
    foreach ( glob( __DIR__ . '/migrations/*.php' ) as $file ) {
        $def = require $file;
        foreach ( [ 'id', 'since_version', 'scope', 'run' ] as $key ) {
            if ( !array_key_exists( $key, $def ) ) {
                fatal( "migration file $file is missing '$key'" );
            }
        }
        $migrations[] = $def;
    }

    ## Stable execution order: by release first (so an earlier release's
    ## migration never runs after a later one's), then by id as a
    ## tiebreaker within the same release, not filesystem/glob order,
    ## which is not guaranteed across OSes or filesystems.
    usort( $migrations, function ( $a, $b ) {
        $v = version_compare( $a[ 'since_version' ], $b[ 'since_version' ] );
        return $v !== 0 ? $v : strcmp( $a[ 'id' ], $b[ 'id' ] );
    } );

    return $migrations;
}

## Migrations whose since_version falls in ($from, $to] -- exclusive of
## $from (already there before this upgrade), inclusive of $to (the
## release being upgraded to). $from = null means "the host's starting
## point is unknown or irrelevant": every migration up to $to is a
## candidate, and each one's own run() still decides for itself whether
## it has already happened, so $from is never the only thing standing
## between a migration and being skipped -- a host that somehow still
## needs an old migration gets it even if $from claims otherwise.
function migrations_for_range( $scope, $from, $to, $only_ids = null ) {
    $out = [];
    foreach ( discover_migrations() as $m ) {
        if ( $m[ 'scope' ] !== $scope ) {
            continue;
        }
        if ( $only_ids !== null && !in_array( $m[ 'id' ], $only_ids, true ) ) {
            continue;
        }
        if ( $from !== null && version_compare( $m[ 'since_version' ], $from ) <= 0 ) {
            continue;
        }
        if ( version_compare( $m[ 'since_version' ], $to ) > 0 ) {
            continue;
        }
        $out[] = $m;
    }
    return $out;
}

## Runs every host-scope migration due for $to, in order, against $gdb
## (gfac's own connection -- already what uslims_upgrade.php holds
## throughout).
function run_host_migrations( $gdb, $to, $apply, $from = null ) {
    foreach ( migrations_for_range( 'host', $from, $to ) as $m ) {
        $m[ 'run' ]( $gdb, 'gfac', $apply, [] );
    }
}

## Same, for one LIMS instance's own connection/database. $context is
## passed through to run() unchanged -- an instance migration that needs
## more than the connection (common_dir_backfill needs the instance's own
## config file path) reads it from there. $only_ids restricts which
## instance migrations run in this call -- needed because not every
## instance migration needs the same prerequisites (common_dir_backfill
## works from already-parsed config alone and must run even when this
## instance's own database connection fails; people_audit_utc needs a
## live connection and is meaningless without one), so the caller connects
## only once it actually needs to and calls this twice: once before
## connecting, restricted to migrations that do not need a connection, and
## once after, for the rest.
function run_instance_migrations( $conn, $db, $to, $apply, $context = [], $only_ids = null, $from = null ) {
    foreach ( migrations_for_range( 'instance', $from, $to, $only_ids ) as $m ) {
        $m[ 'run' ]( $conn, $db, $apply, $context );
    }
}
