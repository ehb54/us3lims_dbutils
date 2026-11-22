<?php

## The grants a LIMS dbinstance needs, defined once for the dbutils paths that create or
## rebuild an instance: dbmigrate/stage3_import_databases.php and
## dbupgrade/stage2_import_dbinsts.php.
##
## Accounts are granted only at hosts the server matches under skip-name-resolve, which
## config-lims-database sets: localhost, and '%' for the accounts US3 clients use. A grant to
## an account at the server's host name only warned on MariaDB 10.3, but 10.11 refuses it once
## the server has restarted (ERROR 1133), so none is made.
##
## A database-level GRANT does not need the database to exist, and DROP DATABASE keeps it. So
## both scripts apply these before they drop or create anything, and stop there if one fails.

## $meta: the instance's newus3.metadata row (dbuser, dbpasswd, secure_user, secure_pw).
## Per grant: the statement, with {db}, {user} and {pw} standing for the database, the account's
## user name and its password, then that user name and password. apply_instance_grants() fills
## them in, quoting the user name and password with the connection's escaping.
function instance_grants( $meta, $us3phppw, $gfacpw ) {
    return [
        [ "GRANT ALL ON {db}.* TO {user}@'localhost' IDENTIFIED BY {pw}", $meta->dbuser, $meta->dbpasswd ]
        ,[ "GRANT ALL ON {db}.* TO {user}@'%' IDENTIFIED BY {pw}", $meta->dbuser, $meta->dbpasswd ]
        ,[ "GRANT EXECUTE ON {db}.* TO {user}@'%' IDENTIFIED BY {pw} REQUIRE SSL", $meta->secure_user, $meta->secure_pw ]
        ,[ "GRANT ALL ON {db}.* TO {user}@'localhost' IDENTIFIED BY {pw}", 'us3php', $us3phppw ]
        ,[ "GRANT SELECT,INSERT,UPDATE ON {db}.* TO {user}@'localhost' IDENTIFIED BY {pw}", 'gfac', $gfacpw ]
    ];
}

## Applies instance_grants() for every dbinstance in $metas (dbname => metadata row). Every
## statement is tried. Returns "" when all succeeded, otherwise one entry per failed statement:
## the statement with its password shown as '...', and the server's error number and SQLSTATE.
## The server's own message is left out, because it can quote the statement, password included.
function apply_instance_grants( $db_handle, $metas, $us3phppw, $gfacpw ) {
    $errors = "";
    foreach ( $metas as $db => $meta ) {
        foreach ( instance_grants( $meta, $us3phppw, $gfacpw ) as list( $statement, $user, $pw ) ) {
            $user  = "'" . mysqli_real_escape_string( $db_handle, $user ) . "'";
            $q     = strtr( $statement, [ '{db}' => $db, '{user}' => $user, '{pw}' => "'" . mysqli_real_escape_string( $db_handle, $pw ) . "'" ] );
            $shown = strtr( $statement, [ '{db}' => $db, '{user}' => $user, '{pw}' => "'...'" ] );
            $errno = 0;
            try {
                if ( !@mysqli_query( $db_handle, $q ) ) {
                    $errno = mysqli_errno( $db_handle );
                }
            } catch ( mysqli_sql_exception $e ) {
                $errno = $e->getCode();
            }
            if ( $errno ) {
                $errors .=
                    "$db: $shown\n"
                    . "  error $errno (SQLSTATE " . mysqli_sqlstate( $db_handle ) . ")" . instance_grant_error_hint( $errno ) . "\n"
                    ;
            }
        }
    }
    if ( $errors != "" ) {
        $errors .= "(The server's messages are not shown, since they can quote a password.)\n";
    }
    return $errors;
}

## A short description, in place of the server's message, of the errors a grant is likely to hit.
function instance_grant_error_hint( $errno ) {
    $hints = [
        1044 => "access denied to the database"
        ,1045 => "access denied"
        ,1064 => "SQL syntax error"
        ,1133 => "no account matches"
        ,1227 => "this login lacks the privilege"
        ,1819 => "the password does not meet the server's password policy"
    ];
    return isset( $hints[ $errno ] ) ? ": " . $hints[ $errno ] : "";
}
