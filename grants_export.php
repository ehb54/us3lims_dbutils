<?php

## The GRANTS export of uslims_daily_backup.php, kept apart so that tests/ can load it without
## running a backup (ultrascan-tickets#1290).

## Writes $dumpfile as a script that re-establishes every account in mysql.user. It comes in three
## parts, so that a replay never names an account or role before it exists:
##   1. "## Accounts and roles ##": CREATE USER IF NOT EXISTS for every account (so a restore also
##      makes the accounts that have no password) and CREATE ROLE IF NOT EXISTS for every role;
##   2. per account or role, a "## Grants for user@host ##" header and its SHOW GRANTS lines: its
##      privileges, and the roles granted to it;
##   3. "## Default roles ##": the SET DEFAULT ROLE lines from those SHOW GRANTS.
## PUBLIC (MariaDB 10.11+) holds the grants given to every account. Every server has it and no
## CREATE ROLE can make it, so only its grants are written.
## An account that cannot be read does not stop the others; it is named at the top of the file.
## Returns one line per account (or step) that could not be exported, so an empty array means
## every account is in the file.
function export_grants( $db_handle, $dumpfile ) {
    $failed   = [];
    $creates  = [];
    $blocks   = "";
    $defaults = [];
    $res = export_grants_query( $db_handle, "SELECT user, host, is_role FROM mysql.user ORDER BY host, user", $error );
    if ( !$res ) {
        $failed[] = "could not list the accounts in mysql.user: $error";
    } else {
        $accounts = [];
        while ( $row = mysqli_fetch_row( $res ) ) {
            $accounts[] = $row;
        }
        foreach ( $accounts as list( $acct_user, $acct_host, $is_role ) ) {
            $u = mysqli_real_escape_string( $db_handle, $acct_user );
            $h = mysqli_real_escape_string( $db_handle, $acct_host );
            if ( $is_role == 'Y' && strcasecmp( $acct_user, 'PUBLIC' ) == 0 ) {
                ## no role can be named PUBLIC, so this is the built-in one
                $acct   = "PUBLIC";
                $create = "";
            } elseif ( $is_role == 'Y' ) {
                $acct   = "'$u'";
                $create = "CREATE ROLE IF NOT EXISTS $acct";
            } else {
                $acct = "'$u'@'$h'";
                $cres = export_grants_query( $db_handle, "SHOW CREATE USER $acct", $error );
                if ( !$cres || !( $crow = mysqli_fetch_row( $cres ) ) ) {
                    $failed[] = "$acct: SHOW CREATE USER: $error";
                    continue;
                }
                $create = preg_replace( '/^CREATE USER /', 'CREATE USER IF NOT EXISTS ', $crow[ 0 ] );
            }
            $gres = export_grants_query( $db_handle, "SHOW GRANTS FOR $acct", $error );
            if ( !$gres ) {
                $failed[] = "$acct: SHOW GRANTS: $error";
                continue;
            }
            $header = mysqli_fetch_field( $gres )->name;
            $lines  = [];
            while ( $grow = mysqli_fetch_row( $gres ) ) {
                if ( preg_match( '/^SET DEFAULT ROLE /', $grow[ 0 ] ) ) {
                    $defaults[] = $grow[ 0 ];
                } else {
                    $lines[] = $grow[ 0 ];
                }
            }
            if ( $create != "" ) {
                $creates[] = $create;
            }
            if ( count( $lines ) ) {
                $blocks .= "\n## $header ##\n" . implode( ";\n", $lines ) . ";\n";
            }
        }
    }
    $head = "";
    foreach ( $failed as $f ) {
        $head .= "## NOT EXPORTED: $f\n";
    }
    $out = $head . "\n## Accounts and roles ##\n";
    if ( count( $creates ) ) {
        $out .= implode( ";\n", $creates ) . ";\n";
    }
    $out .= $blocks;
    if ( count( $defaults ) ) {
        $out .= "\n## Default roles ##\n" . implode( ";\n", $defaults ) . ";\n";
    }
    if ( file_put_contents( $dumpfile, $out ) === false ) {
        $failed[] = "could not write $dumpfile";
    }
    return $failed;
}

## mysqli_query that reports an error as false plus $error, under PHP 7 (a warning, suppressed
## here since the caller reports it) and PHP 8.1+ (an exception by default) alike.
function export_grants_query( $db_handle, $query, &$error ) {
    $error = "";
    try {
        $res = @mysqli_query( $db_handle, $query );
    } catch ( mysqli_sql_exception $e ) {
        $error = "(" . $e->getCode() . ") " . $e->getMessage();
        return false;
    }
    if ( !$res ) {
        $error = "(" . mysqli_errno( $db_handle ) . ") " . mysqli_error( $db_handle );
    }
    return $res;
}
