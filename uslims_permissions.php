<?php

### users that can not be modified
$system_user_list = [
    "gfac"
    ,"new_us3user"
    ,"us3_notice"
    ,"us3php"
    ,"root"
    ]
    ;
###

$self = __FILE__;

require "utility.php";

$notes = <<<__EOD
usage: $self {options} {db_config_file}

Utility for managing PAM authentication, USERS and GRANTS
must be run with root privileges

Options

--help                        : print this information and exit

--user username               : user (can be included multiple times)
--all-users                   : all existing users
--system-users                : include system process users (by default these are excluded; can not be modified)

--list                        : list users    
--list-grants                 : show grant information with each listed user (implies --list)
--grant-host hostname         : restrict grants to the specific host (can be included multiple times)

--pam-check                   : validate PAM is active
--pam-activate                : activate PAM
--pam-disable                 : disable PAM

--user-add                    : add user(s)
--user-delete                 : delete user(s)
--add-db dbname               : add db access for the users(s), use '*' for all dbs (for sysadmins only!)
--remove-db dbname            : remove db access for the users(s)

--grant-integrity dbname      : checks grant integrity for the specified db (can be included multiple times)
--grant-integrity-fix         : fixes grant integrity for the specified dbs (requires --grant-integrity)

--debug                       : increase debug output (can be used multiple times)


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$users                = [];
$all_users            = false;
$system_users         = false;

$list                 = false;
$list_grants          = false;
$grant_host           = [];

$pam_check            = false;
$pam_activate         = false;
$pam_disable          = false;

$user_add             = false;
$user_delete          = false;
$add_db               = [];
$remove_db            = [];

$grant_integrity      = [];
$grant_integrity_fix  = false;

$debug                = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
        case "--user": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $users[] = array_shift( $u_argv );
            break;
        }
        case "--all-users": {
            array_shift( $u_argv );
            $all_users = true;
            break;
        }
        case "--system-users": {
            array_shift( $u_argv );
            $system_users = true;
            break;
        }
        case "--list": {
            array_shift( $u_argv );
            $list = true;
            break;
        }
        case "--list-grants": {
            array_shift( $u_argv );
            $list_grants = true;
            $list = true;
            break;
        }
        case "--grant-host": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $grant_host[] = array_shift( $u_argv );
            break;
        }
        case "--pam-check": {
            array_shift( $u_argv );
            $pam_check = true;
            break;
        }
        case "--pam-activate": {
            array_shift( $u_argv );
            $pam_activate = true;
            break;
        }
        case "--pam-disable": {
            array_shift( $u_argv );
            $pam_disable = true;
            break;
        }
        case "--user-add": {
            array_shift( $u_argv );
            if ( $user_add ) {
                error_exit( "ERROR: option '$arg' can only be specified once" );
            }
            $user_add = true;
            break;
        }
        case "--user-delete": {
            array_shift( $u_argv );
            if ( $user_delete ) {
                error_exit( "ERROR: option '$arg' can only be specified once" );
            }
            $user_delete = true;
            break;
        }
        case "--add-db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $add_db[] = array_shift( $u_argv );
            break;
        }
        case "--remove-db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $remove_db[] = array_shift( $u_argv );
            break;
        }
        case "--grant-integrity": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $grant_integrity[] = array_shift( $u_argv );
            break;
        }
        case "--grant-integrity-fix": {
            array_shift( $u_argv );
            $grant_integrity_fix = true;
            break;
        }

      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }
}

$config_file = "db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
} else {
    $use_config_file = $config_file;
}

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( $all_users && count( $users ) ) {
    error_exit( "--all-users is mutually exclusive with --user\n\n$notes" );
}

if ( $list_grants && !$list ) {
    error_exit( "--list-grants requires --list\n\n$notes" );
}

if ( $list_grants && !($all_users || count( $users ) ) ) {
    error_exit( "--list-grants requires either --all-users or --user\n\n$notes" );
}

if ( count( $grant_host ) &&
     ( $user_add
       || $user_delete
       || $add_db
       || $remove_db ) ) {
    error_exit( "--grant-host is not currently supported for --user-add, --user-delete, --add-db and --remove-db\n\n$notes" );
}

if ( count( $grant_host ) && !$list_grants ) {
    error_exit( "--grant-host requires --list-grants\n\n$notes" );
}

if ( $user_add && $user_delete ) {
    error_exit( "--user-add & --user-delete are mutually exclusive\n\n$notes" );
}

if ( $grant_integrity_fix && !count( $grant_integrity ) ) {
    error_exit( "--grant-integrity-fix requires --grant-integrity\n\n$notes" );
}

if ( $user_add && !count( $add_db ) ) {
    print "WARNING: no dbs specified, added users will have no access to any databases\n";
}

if ( !file_exists( $use_config_file ) ) {
    fwrite( STDERR, "$self:
            $use_config_file does not exist

            to fix:

            cp ${config_file}.template $use_config_file
            and edit with appropriate values
            ")
        ;
    exit(-1);
}

file_perms_must_be( $use_config_file );
require $use_config_file;

if ( !is_admin() ) {
    error_exit( "you must have administrator privileges" );
}

open_db();

## collect user info

if ( $all_users ) {
    $res = db_obj_result( $db_handle, "select distinct user from mysql.user order by user", true );

    while( $row = mysqli_fetch_array($res) ) {
        $orow = (object) $row;
        $users[] = $orow->user;
    }
}

function is_system_user( $user ) {
    global $system_user_list;
    return
        in_array( $user, $system_user_list )
        || preg_match( '/_(sec|user)$/', $user )
        ;
}

if ( !$system_users ) {
    foreach( $users as $k => $user ) {
        if ( is_system_user( $user ) ) {
            unset( $users[ $k ] );
        }
    }
}

# methinks we loop over people
# if ( count( $grant_integrity ) && !count( $users ) ) {
#    error_exit( "--grant-integrity requires users be defined\n\n$notes" );
# }

function do_list() {
    global $users;
    global $list_grants;
    global $db_handle;
    global $grant_host;

    if ( !count( $users ) ) {
        print "no users found\n";
        exit;
    }

    $res = db_obj_result( $db_handle, "select user,host from mysql.user order by user", true );
    $fmt = " %-30s | %-30s\n";
    $len = 128;

    $user_hosts = [];
    
    while( $row = mysqli_fetch_array($res) ) {
        $orow = (object) $row;
        if ( in_array( $orow->user, $users ) ) {
            if ( !isset( $user_hosts[ $orow->user ] ) ) {
                $user_hosts[ $orow->user ] = [];
            }
            $user_hosts[ $orow->user ][] = $orow->host;
        }
    }

    echoline( "-", $len );
    print sprintf(
        $fmt
        ,"user"
        ,"hosts"
        );
    echoline( "-", $len );
        
    ksort( $user_hosts, SORT_NATURAL );

    foreach ( $user_hosts as $k => $v ) {
        sort( $v, SORT_NATURAL );
        print sprintf(
            $fmt
            ,$k
            ,implode( ", ", $v )
            );
        if ( $list_grants ) {
            echoline( "-", $len );
            $any = false;
            foreach ( $v as $host ) {
                if ( !count( $grant_host ) || in_array( $host, $grant_host ) ) {
                    $res = db_obj_result( $db_handle, "show grants for '$k'@'$host'", true, true );
                    if ( $res ) {
                        while( $row = mysqli_fetch_array($res) ) {
                            $orow = (object) $row;
                            print $row[0] . "\n";
                            $any = true;
                        }
                    }
                }
            }
            if ( !$any ) {
                print "no matching grant information found\n";
            }
            echoline( "-", $len );
        }
    }
    
    echoline( "-", $len );
}

if ( $list ) {
    do_list();
}

function check_pam( $exitonerror = true ) {
    global $db_handle;
    $res = db_obj_result( $db_handle, "show plugins", true );
    
    $pamok = false;

    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            $orow = (object) $row;
            if ( $orow->Name == "pam" ) {
                $pamok = true;
                break;
            }
        }
    }

    if ( !$pamok ) {
        if ( $exitonerror ) {
            error_exit( "PAM plugin is not loaded" );
        } else {
            return false;
        }
    }

    # print "PAM plugin loaded\n";
    return true;
}

if ( $pam_activate ) {
    if ( check_pam( false ) ) {
        error_exit( "PAM is already active" );
    }
    
    {
        $f = "/etc/my.cnf.d/server_pam.cnf";

        print "checking $f\n";
        if ( file_exists( $f ) ) {
            error_exit( "$f already exists : backup or remove" );
        }

        if ( false ===
             file_put_contents( $f
                                ,"[mariadb]\n"
                                . "plugin_load_add = auth_pam\n"
             ) ) {
            error_exit( "error creating $f" );
        }
    }

    {
        $f = "/etc/pam.d/mariadb";

        print "checking $f\n";
        if ( file_exists( $f ) ) {
            error_exit( "$f already exists : backup or remove" );
        }

        if ( false ===
             file_put_contents( $f
                                ,"auth required pam_unix.so audit\n"
                                ."account required pam_unix.so audit\n"
             ) ) {
            error_exit( "error creating $f" );
        }
    }
    
    $cmds = [
        "groupadd shadow"
        ,"usermod -a -G shadow mysql"
        ,"chown root:shadow /etc/shadow"
        ,"chmod g+r /etc/shadow"
        ];

    foreach ( $cmds as $c ) {
        echo "running: $c\n";
        $res = run_cmd( $c, false );
        if ( trim( $res ) != '' ) {
            echo "command returns: $res\n";
        }
    }

    print "Restarting the db is required to activate PAM\n";
    get_yn_answer( "Restart mariadb services (make sure no processes are actively updating!)", true );
    run_cmd( "service mariadb stop" );

    # verify db stopped

    echo "Verifying db had stopped, expect a PHP Warning\n";

    $db_handle = mysqli_connect( $dbhost, $user, $passwd );
    if ( $db_handle ) {
        error_exit( "Can still connect to database, somehow it didn't stop?" );
    }

    run_cmd( "service mariadb start" );

    run_cmd( "service mariadb start" );
    # make sure db is open
    echo "verify db is running\n";
    open_db();
    echo "OK: db is running\n";
    exit();
}
        
if ( $pam_disable ) {
    error_exit(
        "Disabling PAM is not currently implemented\n\n"
        . "Steps to manually disable:\n"
        . "1. remove /etc/my.cnf.d/server_pam.cnf & /etc/pam.d/mariadb\n"
        . "2. restart the mariadb server\n"
        . "N.B. how this effects existing USERs IDENTIFIED VIA PAM has not been tested\n"
        );
}

if ( $pam_check ) {
    if ( check_pam( false ) ) {
        print "The PAM module is loaded\n";
        exit;
    } else {
        print  "The PAM module is NOT loaded\n";
        exit(-1);
    }
}

## pam required functions below

check_pam();

function do_user_delete( $user ) {
    global $db_handle;
    return db_obj_result( $db_handle, "DROP USER '$user'" );
}
 
function do_user_add( $user ) {
    global $db_handle;
    return db_obj_result( $db_handle, "CREATE USER IF NOT EXISTS '$user'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
}

function do_db_add( $db, $user ) {
    global $db_handle;
    return db_obj_result( $db_handle, "GRANT USAGE,SELECT,INSERT,UPDATE,DELETE,EXECUTE ON $db.* TO '$user'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
}

function do_db_remove( $db, $user ) {
    global $db_handle;
    return db_obj_result( $db_handle, "REVOKE SELECT,INSERT,UPDATE,DELETE,EXECUTE ON $db.* from '$user'@'%'", true );
}

if ( $user_delete ) {
    foreach ( $users as $user ) {
        if ( is_system_user( $user ) ) {
            print "WARNING: not deleting system user '$user'\n";
        } else {
            print "Deleting user '$user'...";
            if ( do_user_delete( $user ) ) {
                print "user '$user' dropped\n";
            } else {
                print "WARNING: dropping user '$user' did not succeed\n";
            }
        }
    }
}

if ( $user_add ) {
    foreach ( $users as $user ) {
        print "Adding user '$user' ... ";
        if ( do_user_add( $user ) ) {
            print "user '$user' added\n";
        } else {
            print "WARNING: add user '$user' did not succeed\n";
        }
    }
}

if ( $add_db ) {
    foreach ( $users as $user ) {
        foreach ( $add_db as $db ) {
            print "adding access for user '$user' to db '$db'... ";
            if ( do_db_add( $db, $user ) ) {
                print "success\n";
            } else {
                print "WARNING: did not succeed\n";
            }

        }
    }
}

if ( $remove_db ) {
    foreach ( $users as $user ) {
        foreach ( $remove_db as $db ) {
            print "removing access for user '$user' to db '$db'... ";
            if ( do_db_remove( $db, $user ) ) {
                print "success\n";
            } else {
                print "WARNING: did not succeed\n";
            }

        }
    }
}

if ( $add_db || $remove_db ) {
    echoline( "=" );
    print "Grants after modifications\n";
    echoline( "=" );
    $list_grants = true;
    do_list();
}

if ( count( $grant_integrity ) ) {


    $fmt = " %-30s | %-30s | %-9s | %-3s | %-30s\n";
    $len = 128;

    echoline( "-", $len );
    print sprintf(
        $fmt
        ,"db"
        ,"userNamePAM"
        ,"userlevel"
        ,"PAM"
        ,"issues"
        );
    echoline( "-", $len );

    foreach ( $grant_integrity as $db ) {
        $res = db_obj_result( $db_handle, "select * from $db.people", true );
        while( $row = mysqli_fetch_array($res) ) {
            $orow = (object) $row;
            
            $grants_ok = false;

            $host = '%';

            $res2 = db_obj_result( $db_handle, "show grants for '$orow->userNamePAM'@'$host'", true, true );

            # build grant info tables for user
            $grants                         = (object)[];

            $grants->usage                  = (object)[];
            $grants->usage->exists          = false;
            $grants->usage->pam             = false;
            $grants->usage->ssl             = false;
            $grants->usage->expected_format = false;

            $grants->dbs                    = (object)[];

            $grants->unknown_lines          = 0;

            if ( $res2 ) {
                while( $row2 = mysqli_fetch_array($res2) ) {
                    foreach ( $row2 as $v ) {
                        if ( $debug ) {
                            print "debug1: $v\n";
                        }
                        if ( preg_match( '/^GRANT USAGE/', $v ) ) {
                            $grants->usage->exists = true;
                            if ( preg_match( '/IDENTIFIED VIA PAM/', $v ) ) {
                                $grants->usage->pam = true;
                            }
                            if ( preg_match( '/IDENTIFIED VIA PAM/', $v ) ) {
                                $grants->usage->ssl = true;
                            }
                            if ( preg_match( '/REQUIRE SSL/', $v ) ) {
                                $grants->usage->ssl = true;
                            }
                            if ( strstr( $v, "USAGE ON *.* TO ", $v ) ) {
                                $grants->usage->expected_format = true;
                            }
                        }
                        else if ( preg_match( '/^GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON `([^`]+)`/', $v, $matches ) ) {
                            $grants->dbs->{$matches[1]} = (object)[];
                        } else {
                            ++$grants->unknown_lines;
                        }
                    }
                }
            }


            $errors = "";

            $tofix = (object)[];
            
            if ( $orow->authenticatePAM ) {
                if ( !$grants->usage->exists ) {
                    $errors .= " Missing USAGE;";
                    $tofix->add_usage = true;
                }
                if ( !isset( $grants->dbs->{$db} ) ) {
                    $errors .= " Missing GRANTS;";
                    $tofix->add_grants = true;
                }
            } else {
                if ( $grants->usage->exists ) {
                    $errors .= " Has USAGE;";
                    $tofix->remove_usage = true;
                }
                if ( isset( $grants->dbs->{$db} ) ) {
                    $errors .= " Has GRANTS;";
                    $tofix->remove_grants = true;
                }
            }

            if ( $grants->unknown_lines ) {
                $errors .= sprintf( " %3 unknown lines;", $grants->unknown_lines );
            }

            $errors = trim( $errors, "; " );

            print sprintf(
                $fmt
                ,$db
                ,$orow->userNamePAM
                ,$orow->userlevel
                ,$orow->authenticatePAM ? "Yes" : "No"
                ,empty( $errors ) ? "" : $errors
                );

            if ( $debug ) {
                debug_json( "grants", $grants );
            }

            if ( $grant_integrity_fix ) {
                if ( isset( $tofix->remove_usage ) ) {
                    if ( !do_user_delete( $orow->userNamePAM ) ) {
                        print "ERRORS encounterd when trying to delete user '$user'\n";
                    }
                } else if ( isset( $tofix->remove_grants ) ) {
                    if ( !do_db_remove( $db, $orow->userNamePAM ) ) {
                        print "ERRORS encounterd when trying to remove user '$user' from db '$db'\n";
                    }
                }

                if ( isset( $tofix->add_usage ) ) {
                    if ( !do_user_add( $orow->userNamePAM ) ) {
                        print "ERRORS encounterd when trying to add user '$user'\n";
                    }
                }
                if ( isset( $tofix->add_grants ) ) {
                    if ( !do_db_add( $db, $orow->userNamePAM ) ) {
                        print "ERRORS encounterd when trying to add user '$user' to db '$db'\n";
                    }
                }
            }
        }
    }
    echoline( "-", $len );
}        
    
