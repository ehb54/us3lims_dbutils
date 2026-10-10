<?php

$self = __FILE__;

require_once "utility.php";

## Same defaults uslims_upgrade.php and uslims_domain_info.php use.
$wwwpath  = "/srv/www/htdocs";
$us3_home = "/home/us3";

$notes = <<<__EOD
usage: $self {options}

Runs migrate_people_audit_created_at_to_utc() (utility.php) by itself --
none of uslims_upgrade.php's other preconditions, confirmations, or
cluster/CSP machinery, and none of dbupgrade/stage2_import_dbinsts.php's
drop-and-recreate pipeline. For:

  - a host already upgraded in-place before uslims_upgrade.php step 9
    existed, which otherwise has no way to apply this fix without
    re-running the whole upgrade;
  - fixing one dbinstance by hand after a warning from either of those
    (an unresolvable time zone, a connection that failed partway through);
  - checking migration status without connecting with write intent.

Idempotent via a marker table inside each dbinstance (_dbutils_people_audit_utc_migrated),
same as both of the above -- already-migrated dbinstances are reported
'ok' and left untouched regardless of how many times this runs.

Default mode discovers every LIMS instance on this host under --www
(single-tenant and multi-tenant, same detection as uslims_upgrade.php step 9),
and connects with each instance's own config.php -- no new credentials.

--db-host/--db-name/--db-user/--db-pass together migrate one dbinstance
directly instead, with credentials of the caller's choosing: a dbinstance
not under --www (e.g. a stage1/stage2 import target), or an account other
than the one in that instance's own config.php.

Options

--help                      : print this information and exit
--check                      : report what would be migrated; the default -- never connects
                                with write intent
--apply                      : connect and actually perform the migration
--www              path      : \$wwwpath root (default $wwwpath); ignored with --db-host
--db-host          host      : migrate this one dbinstance directly instead of discovering
--db-name          name       (requires --db-user and --db-pass too)
--db-user          name
--db-pass          pass

__EOD;

## An option's value, or a clear error.
function opt_value( &$argv, $opt ) {
    if ( !count( $argv ) ) {
        error_exit( "$opt needs a value" );
    }
    return array_shift( $argv );
}

$u_argv = $argv;
array_shift( $u_argv );

$apply       = false;
$db_host     = null;
$db_name     = null;
$db_user     = null;
$db_pass     = null;

while ( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $opt = array_shift( $u_argv );
    switch ( $opt ) {
        case "--help":
            echo $notes;
            exit;
        case "--check":
            ## The default already; an operator still has to be able to ask for it.
            break;
        case "--apply":
            $apply = true;
            break;
        case "--www":
            $wwwpath = rtrim( opt_value( $u_argv, $opt ), '/' );
            if ( $wwwpath === '' ) {
                error_exit( "--www needs a path" );
            }
            break;
        case "--db-host":
            $db_host = opt_value( $u_argv, $opt );
            break;
        case "--db-name":
            $db_name = opt_value( $u_argv, $opt );
            break;
        case "--db-user":
            $db_user = opt_value( $u_argv, $opt );
            break;
        case "--db-pass":
            $db_pass = opt_value( $u_argv, $opt );
            break;
        default:
            error_exit( "unknown option '$opt'\n\n$notes" );
    }
}

if ( count( $u_argv ) ) {
    error_exit( "unexpected argument '{$u_argv[0]}'\n\n$notes" );
}

$explicit_target = [ $db_host, $db_name, $db_user, $db_pass ];
$explicit_given   = array_filter( $explicit_target, function ( $v ) { return $v !== null; } );
if ( $explicit_given && count( $explicit_given ) !== 4 ) {
    error_exit( "--db-host, --db-name, --db-user and --db-pass must be given together, not some of them\n\n$notes" );
}
if ( $explicit_given ) {
    ## Validated here too, not only inside the functions this reaches: a
    ## malformed --db-name otherwise reaches mysqli_connect() first and
    ## fails as an uncaught exception instead of this script's own error.
    sql_identifier_or_die( $db_name, '--db-name' );
}

## [ label => [ 'dbname'|'dbhost'|'dbusername'|'dbpasswd' => ... ] | [ 'error' => string ] ]
$targets = $explicit_given
         ? [ $db_name => [ 'dbhost' => $db_host, 'dbname' => $db_name,
                           'dbusername' => $db_user, 'dbpasswd' => $db_pass ] ]
         : discover_lims_instance_configs( $wwwpath, $us3_home );

if ( !$targets ) {
    echo "no LIMS instance found under $wwwpath/uslims3; nothing to migrate\n";
    exit( 0 );
}

$failures = 0;
foreach ( $targets as $label => $cfg ) {
    if ( isset( $cfg[ 'error' ] ) ) {
        echo "[note] $label: {$cfg['error']}; convert people_audit by hand\n";
        continue;
    }

    $conn = @mysqli_connect( $cfg[ 'dbhost' ], $cfg[ 'dbusername' ], $cfg[ 'dbpasswd' ], $cfg[ 'dbname' ] );
    if ( !$conn ) {
        echo "[FAIL] $label: could not connect to {$cfg['dbname']}@{$cfg['dbhost']} as"
           . " {$cfg['dbusername']}: " . mysqli_connect_error() . "\n";
        $failures++;
        continue;
    }

    if ( people_audit_utc_migration_done( $conn, $cfg[ 'dbname' ] ) ) {
        echo "[ok] $label ({$cfg['dbname']}): people_audit already migrated to UTC\n";
    } elseif ( !$apply ) {
        echo "[todo] $label ({$cfg['dbname']}): would migrate people_audit.created_at to UTC"
           . " (rerun with --apply)\n";
    } else {
        echo "[apply] $label ({$cfg['dbname']}):\n";
        migrate_people_audit_created_at_to_utc( $conn, $cfg[ 'dbname' ] );
    }
    mysqli_close( $conn );
}

exit( $failures ? 1 : 0 );
