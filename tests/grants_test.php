<?php

## Regression tests for the GRANTS export (grants_export.php) and the instance grants
## (instance_grants.php), ultrascan-tickets#1290. tests/in-container.sh runs it in a throwaway
## container. Never point it at a real server: it makes accounts and roles, and it installs a
## password-policy plugin.
##
## env SRC_SOCK, DST_SOCK: sockets of the source server and of a freshly initialized target, each
## with root reachable without a password.

$repo = dirname( __DIR__ );
require_once "$repo/grants_export.php";
require_once "$repo/instance_grants.php";

$src_sock = getenv( "SRC_SOCK" );
$dst_sock = getenv( "DST_SOCK" );
$failures = 0;

function check( $ok, $what, $detail = "" ) {
    global $failures;
    echo ( $ok ? "ok   " : "FAIL " ) . $what . "\n";
    if ( !$ok ) {
        $failures++;
        if ( $detail != "" ) {
            echo "       " . str_replace( "\n", "\n       ", rtrim( $detail ) ) . "\n";
        }
    }
}

## a query that may fail, under PHP 7 (false) and 8.1+ (an exception) alike:
## [ result or false, error number, error message ]
function try_query( $h, $sql ) {
    try {
        $r = @mysqli_query( $h, $sql );
    } catch ( mysqli_sql_exception $e ) {
        return [ false, $e->getCode(), $e->getMessage() ];
    }
    return $r ? [ $r, 0, "" ] : [ false, mysqli_errno( $h ), mysqli_error( $h ) ];
}

## a query the test needs to work
function must( $h, $sql ) {
    list( $r, $errno, $error ) = try_query( $h, $sql );
    if ( !$r ) {
        echo "SETUP FAILED: $sql: ($errno) $error\n";
        exit( 2 );
    }
    return $r;
}

function connect( $sock, $user = "root", $pw = "" ) {
    try {
        return @mysqli_connect( "localhost", $user, $pw, "", 0, $sock );
    } catch ( mysqli_sql_exception $e ) {
        return false;
    }
}

## every account and role, keyed by the name SHOW GRANTS takes for it
function accounts( $h ) {
    $out = [];
    $r = must( $h, "SELECT user, host, is_role FROM mysql.user ORDER BY host, user" );
    while ( $row = mysqli_fetch_row( $r ) ) {
        $u = mysqli_real_escape_string( $h, $row[ 0 ] );
        if ( $row[ 2 ] == 'Y' ) {
            $out[ strcasecmp( $row[ 0 ], 'PUBLIC' ) == 0 ? "PUBLIC" : "'$u'" ] = $row;
        } else {
            $out[ "'$u'@'" . mysqli_real_escape_string( $h, $row[ 1 ] ) . "'" ] = $row;
        }
    }
    return $out;
}

## SHOW GRANTS as a sorted set of lines, or the error number. A set, because a fresh server's
## installer has already made root's PROXY grant; the replay adds it again as a second row, which
## SHOW GRANTS shows as the same line.
function grants( $h, $name ) {
    list( $r, $errno ) = try_query( $h, "SHOW GRANTS FOR $name" );
    if ( !$r ) {
        return $errno;
    }
    $lines = [];
    while ( $row = mysqli_fetch_row( $r ) ) {
        $lines[] = $row[ 0 ];
    }
    $lines = array_values( array_unique( $lines ) );
    sort( $lines );
    return $lines;
}

$src = connect( $src_sock );
$dst = connect( $dst_sock );
if ( !$src || !$dst ) {
    echo "SETUP FAILED: cannot connect to the source ($src_sock) or the target ($dst_sock)\n";
    exit( 2 );
}

echo "-- GRANTS export, replayed unmodified into a freshly initialized server\n";

## a role granted to one that sorts before it, and a user whose default role is the parent
foreach ( [
    "CREATE ROLE zz_review_child"
    ,"CREATE ROLE aa_review_parent"
    ,"GRANT SELECT ON mysql.db TO zz_review_child"
    ,"GRANT zz_review_child TO aa_review_parent"
    ,"GRANT SELECT ON mysql.tables_priv TO aa_review_parent"
    ,"CREATE USER 'aa_review_user'@'localhost' IDENTIFIED BY 'review'"
    ,"GRANT aa_review_parent TO 'aa_review_user'@'localhost'"
    ,"SET DEFAULT ROLE aa_review_parent FOR 'aa_review_user'@'localhost'"
] as $sql ) {
    must( $src, $sql );
}
## PUBLIC (MariaDB 10.11+), like the installer's grants on the test databases
list( $has_public ) = try_query( $src, "GRANT ALL ON `test\\_%`.* TO PUBLIC" );
echo "     (" . ( $has_public ? "PUBLIC has grants on this server" : "this server has no PUBLIC" ) . ")\n";

$ignored = [];
foreach ( accounts( $src ) as $name => $row ) {
    if ( grants( $src, $name ) === 1141 ) {
        $ignored[] = $name;
    }
}
check( in_array( "'hn_user'@'db.example.org'", $ignored ),
       "(setup: the server ignores the account at a host name: " . implode( ", ", $ignored ) . ")" );

$dumpfile = "/var/tmp/grants-export.sql";
$failed   = export_grants( $src, $dumpfile );
$named    = 0;
foreach ( $ignored as $name ) {
    foreach ( $failed as $f ) {
        if ( strpos( $f, "$name:" ) === 0 ) {
            $named++;
            break;
        }
    }
}
check( count( $failed ) == count( $ignored ) && $named == count( $ignored ),
       "the export names exactly the accounts the server ignores", implode( "\n", $failed ) );

$lines = file( $dumpfile, FILE_IGNORE_NEW_LINES );
$last_create = $first_grant = $last_grant = $first_default = -1;
foreach ( $lines as $i => $l ) {
    if ( preg_match( '/^CREATE (USER|ROLE) /', $l ) ) {
        $last_create = $i;
    } elseif ( preg_match( '/^GRANT /', $l ) ) {
        $first_grant = $first_grant < 0 ? $i : $first_grant;
        $last_grant  = $i;
    } elseif ( preg_match( '/^SET DEFAULT ROLE /', $l ) && $first_default < 0 ) {
        $first_default = $i;
    }
}
check( $last_create >= 0 && $first_grant > $last_create, "every CREATE USER and CREATE ROLE comes before the first GRANT" );
check( $first_default > $last_grant, "the SET DEFAULT ROLE lines come after every GRANT" );
check( count( preg_grep( '/^CREATE ROLE .*PUBLIC/i', $lines ) ) == 0, "no CREATE ROLE for PUBLIC" );

exec( "mysql --no-defaults -S " . escapeshellarg( $dst_sock ) . " -u root < " . escapeshellarg( $dumpfile ) . " 2>&1", $out, $rc );
check( $rc == 0, "the unmodified export loads into the fresh target without errors, without --force", implode( "\n", $out ) );

$dst  = connect( $dst_sock );
$same = 0;
$diff = [];
foreach ( accounts( $src ) as $name => $row ) {
    if ( in_array( $name, $ignored ) ) {
        continue;
    }
    if ( grants( $dst, $name ) === grants( $src, $name ) ) {
        $same++;
    } else {
        $diff[] = $name;
    }
}
check( count( $diff ) == 0 && $same > 0,
       "every exported account and role ($same, the review roles" . ( $has_public ? " and PUBLIC" : "" ) . " among them) has the same grants on the target",
       "differ: " . implode( ", ", $diff ) );

echo "-- instance grants with credentials that hold a quote\n";

$secret = "prefix'EXPOSED_SUFFIX";
$meta   = (object) [
    'dbuser'      => 'review_user'
    ,'dbpasswd'    => $secret
    ,'secure_user' => "review'sec"
    ,'secure_pw'   => $secret . "2"
];
$us3php_secret = $secret . "3";
$gfac_secret   = $secret . "4";

$errors = apply_instance_grants( $src, [ 'uslims3_review' => $meta ], $us3php_secret, $gfac_secret );
check( $errors === "", "every grant applies", $errors );
foreach ( [ [ 'review_user', $secret ], [ 'us3php', $us3php_secret ], [ 'gfac', $gfac_secret ] ] as list( $u, $pw ) ) {
    $h = connect( $src_sock, $u, $pw );
    check( $h !== false, "$u logs in with that password" );
    if ( $h ) {
        mysqli_close( $h );
    }
}
## REQUIRE SSL, so its hash is compared instead of a login
$hash   = mysqli_fetch_row( must( $src, "SELECT PASSWORD('" . mysqli_real_escape_string( $src, $secret . "2" ) . "')" ) )[ 0 ];
list( $r ) = try_query( $src, "SHOW CREATE USER 'review\\'sec'@'%'" );
$create = $r ? mysqli_fetch_row( $r )[ 0 ] : "";
check( $create != "" && strpos( $create, $hash ) !== false, "review'sec@% has its password's hash" );

## A dash in an unquoted database name is a syntax error, and the server's message for it quotes
## the rest of the statement, password included.
## (MariaDB 10.3 first wants a default database, or it stops at error 1046.)
mysqli_select_db( $src, "mysql" );
list( , $errno, $error ) = try_query( $src, "GRANT ALL ON x-y.* TO 'review_user'@'localhost' IDENTIFIED BY '"
                                            . mysqli_real_escape_string( $src, $secret ) . "'" );
check( $errno == 1064 && strpos( $error, "EXPOSED_SUFFIX" ) !== false,
       "(setup: the server's own message for such a statement quotes the password)", "($errno) $error" );
$errors = apply_instance_grants( $src, [ 'x-y' => $meta ], $us3php_secret, $gfac_secret );
check( substr_count( $errors, "error 1064" ) == 5, "a syntax error in every grant is reported by its number", $errors );
check( strpos( $errors, "EXPOSED_SUFFIX" ) === false, "the report holds no part of a password", $errors );

must( $src, "INSTALL SONAME 'simple_password_check'" );
must( $src, "SET GLOBAL simple_password_check_minimal_length = 40" );
$errors = apply_instance_grants( $src, [ 'uslims3_review' => $meta ], $us3php_secret, $gfac_secret );
must( $src, "UNINSTALL SONAME 'simple_password_check'" );
check( substr_count( $errors, "error 1819" ) == 5, "a password policy that rejects every password: all 5 grants reported as error 1819", $errors );
check( strpos( $errors, "EXPOSED_SUFFIX" ) === false && strpos( $errors, "prefix'" ) === false,
       "the report holds no part of a password", $errors );

echo $failures ? "$failures FAILED\n" : "all passed\n";
exit( $failures ? 1 : 0 );
