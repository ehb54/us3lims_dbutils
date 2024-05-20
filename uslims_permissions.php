<?php

$self = __FILE__;
require "utility.php";

$notes = <<<__EOD
usage: $self {options} {db_config_file}

Utility for managing USERS and GRANTS
must be run with root privileges

Options

--help                 : print this information and exit

--list-all-users       : list all users by user
--list-user name       : list specific user (can be included multiple times)

--show-grants          : show grant information with each listed user
--grant-host           : restrict grant info to the specific host (can be included multiple times)

--pam-check            : validate PAM is active
--pam-activate         : activate PAM
--pam-disable          : disable PAM

--pam-user-add name    : add a user (can be included only once)
--pam-user-delete name : delete a user (can be included only once)

--debug                : increase debug output (can be used multiple times)


__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$users                = [];
$grant_host           = [];
$debug                = 0;
$list_all_users       = false;
$show_grants          = false;

$pam_option_count     = 0;
$pam_check            = false;
$pam_activate         = false;
$pam_disable          = false;
$pam_user_add         = "";
$pam_user_delete      = "";

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
        case "--list-all-users": {
            array_shift( $u_argv );
            $list_all_users = true;
            break;
        }
        case "--list-user": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $users[] = array_shift( $u_argv );
            break;
        }
        case "--show-grants": {
            array_shift( $u_argv );
            $show_grants = true;
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
            $pam_option_count++;
            break;
        }
        case "--pam-activate": {
            array_shift( $u_argv );
            $pam_activate = true;
            $pam_option_count++;
            break;
        }
        case "--pam-disable": {
            array_shift( $u_argv );
            $pam_disable = true;
            $pam_option_count++;
            break;
        }
        case "--pam-user-add": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            if ( $pam_user_add ) {
                error_exit( "ERROR: option '$arg' can only be specified once" );
            }
            $pam_user_add = array_shift( $u_argv );
            $pam_option_count++;
            break;
        }
        case "--pam-user-delete": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            if ( $pam_user_delete ) {
                error_exit( "ERROR: option '$arg' can only be specified once" );
            }
            $pam_user_delete = array_shift( $u_argv );
            $pam_option_count++;
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

if ( $list_all_users && count( $users ) ) {
    error_exit( "--list-all-users is mutually exclusive with --list-user\n\n$notes" );
}

if ( $show_grants && !($list_all_users || count( $users ) ) ) {
    error_exit( "--show-grants requires either --list-all-users or --list-user\n\n$notes" );
}

if ( count( $grant_host ) && !$show_grants ) {
    error_exit( "--grant-host requires --show-grants\n\n$notes" );
}

if ( $pam_user_add && $pam_user_delete ) {
    error_exit( "--pam-user-add & --pam-user-delete are mutually exclusive\n\n$notes" );
}

if ( $pam_option_count > 0 && ( $list_all_users || count( $users ) ) ) {
    error_exit( "--pam-* options are mutually exclusive with --list-* options\n\n$notes" );
}

if ( $pam_option_count > 1 ) {
    error_exit( "on one --pam-* option can be specifice\n\n$notes" );
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

if ( $list_all_users || count( $users ) ) {
    $res = db_obj_result( $db_handle, "select user,host from mysql.user order by user", true );
    $fmt = " %-30s | %-30s\n";
    $len = 128;

    $user_hosts = [];
    
    while( $row = mysqli_fetch_array($res) ) {
        $orow = (object) $row;
        if ( !isset( $user_hosts[ $orow->user ] ) ) {
            $user_hosts[ $orow->user ] = [];
        }
        $user_hosts[ $orow->user ][] = $orow->host;
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
        if ( $list_all_users || in_array( $k, $users ) ) {
            sort( $v, SORT_NATURAL );
            print sprintf(
                $fmt
                ,$k
                ,implode( ", ", $v )
                );
            if ( $show_grants ) {
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
    }

    echoline( "-", $len );

    exit;
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

    print "PAM plugin loaded\n";
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

    exit;
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

## pam required functions below

check_pam();

if ( $pam_user_delete ) {
    $res = db_obj_result( $db_handle, "DROP USER '$pam_user_delete'" );
    if ( $res ) {
        print "user $pam_user_delete dropped\n";
    }
    exit;
}

if ( $pam_user_add ) {
    $res = db_obj_result( $db_handle, "CREATE USER '$pam_user_add'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
#    $res = db_obj_result( $db_handle, "CREATE USER '$pam_user_add'@'%' IDENTIFIED VIA PAM USING 'mariadb'", true );
    debug_json( "res", $res );

#    $res = db_obj_result( $db_handle, "GRANT USAGE,SELECT,INSERT,UPDATE,DELETE,EXECUTE ON *.* TO '$pam_user_add'@'%' IDENTIFIED VIA PAM USING 'mariadb'", true );
    $res = db_obj_result( $db_handle, "GRANT USAGE,SELECT,INSERT,UPDATE,DELETE,EXECUTE ON *.* TO '$pam_user_add'@'%' IDENTIFIED VIA PAM USING 'mariadb' REQUIRE SSL", true );
#    debug_json( "res", $res );
    
#    $res = db_obj_result( $db_handle, "GRANT EXECUTE ON *.* TO '$pam_user_add'@'%' IDENTIFIED VIA PAM", true );
#    debug_json( "res", $res );

    
#    CREATE USER user_1@hostname IDENTIFIED VIA pam USING 'mariadb';
#    GRANT SELECT ON db.* TO user_1@hostname IDENTIFIED VIA pam;
}
