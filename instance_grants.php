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
function instance_grant_statements( $db, $meta, $us3phppw, $gfacpw ) {
    return [
        "GRANT ALL ON $db.* TO '$meta->dbuser'@'localhost' IDENTIFIED BY '$meta->dbpasswd'"
        ,"GRANT ALL ON $db.* TO '$meta->dbuser'@'%' IDENTIFIED BY '$meta->dbpasswd'"
        ,"GRANT EXECUTE ON $db.* TO '$meta->secure_user'@'%' IDENTIFIED BY '$meta->secure_pw' REQUIRE SSL"
        ,"GRANT ALL ON $db.* TO 'us3php'@'localhost' IDENTIFIED BY '$us3phppw'"
        ,"GRANT SELECT,INSERT,UPDATE ON $db.* TO 'gfac'@'localhost' IDENTIFIED BY '$gfacpw'"
    ];
}

## Applies instance_grant_statements() for every dbinstance in $metas (dbname => metadata row).
## Every statement is tried. Returns "" when all succeeded, otherwise one entry per failed
## statement, with its passwords masked.
function apply_instance_grants( $db_handle, $metas, $us3phppw, $gfacpw ) {
    $errors = "";
    foreach ( $metas as $db => $meta ) {
        foreach ( instance_grant_statements( $db, $meta, $us3phppw, $gfacpw ) as $q ) {
            $error = "";
            try {
                $res = @mysqli_query( $db_handle, $q );
            } catch ( mysqli_sql_exception $e ) {
                $res   = false;
                $error = $e->getMessage();
            }
            if ( !$res ) {
                if ( $error == "" ) {
                    $error = mysqli_error( $db_handle );
                }
                $errors .=
                    "$db: " . preg_replace( "/IDENTIFIED BY '[^']*'/", "IDENTIFIED BY '...'", $q ) . "\n"
                    . "  error: $error\n"
                    ;
            }
        }
    }
    return $errors;
}
