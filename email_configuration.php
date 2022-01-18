<?php

$self = __FILE__;
    
$saslpw       = "/etc/postfix/sasl/sasl_passwd";
$generic_file = "/etc/postfix/generic";

$notes = <<<__EOD
usage: $self {options}

reconfigures postfix

Options

--help               : print this information and exit

--host  string       : set host (required)
--port  string       : set port (required)
--user  string       : set user (required)
--generic            : setup generic mapping

__EOD;


require "utility.php";

if ( !is_admin() ) {
    error_exit( "you must have administrator privileges" );
}


$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

if ( !count( $u_argv ) ) {
    echo "$notes\n";
    exit();
}

$smtp_port     = 0;
$smtp_host     = "";
$smtp_user     = "";
$smtp_pw       = "";
$generic       = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--host": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --host requires an argument\n\n$notes" );
            }
            $host = array_shift( $u_argv );
            break;
        }
        case "--port": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --port requires an argument\n\n$notes" );
            }
            $port = array_shift( $u_argv );
            break;
        }
        case "--user": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --user requires an argument\n\n$notes" );
            }
            $user = array_shift( $u_argv );
            break;
        }
        case "--generic": {
            array_shift( $u_argv );
            $generic = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}

if ( empty($host) || $port == 0 || empty($user) ) {
    error_exit( "--host, --port && --user must be defined and non-empty\n\n$notes" );
}

if ( !file_exists( $saslpw ) ) {
    error_exit( "file $saslpw does not exist, is postfix properly setup?" );
}

# enter password
fwrite( STDERR, "password: " );
system('stty -echo');
$pw = trim(fgets(STDIN));
system('stty echo');
# add a new line since the users CR didn't echo
fwrite( STDERR, "\n" );

## create $saslpw

$saslpw_out = "[$host]:$port $user:$pw\n";
if ( file_put_contents( $saslpw, $saslpw_out ) === false ) {
    error_exit( "permission denied on writing $saslpw" );
}

$hostname = gethostname();

## create $generic

$generic_out = $generic ? "@$hostname $user\n@biophysics.uleth.ca $user\n" : "";
if ( file_put_contents( $generic_file, $generic_out ) === false ) {
    error_exit( "permission denied on writing $generic" );
}

## run postmap

$cmds = [
    "service postfix stop"
    ,"postconf -e relayhost=[$host]:$port"
    ,"postconf -e myhostname=$hostname"
    ,"postconf -e mydomain=$hostname"
    ,"postconf -e myorigin=$hostname"
    ,"postconf -e smtp_sasl_security_options=noanonymous"
    ];

if ( $generic ) {
    $cmds[] = "postconf -e smtp_generic_maps=hash:/etc/postfix/generic";
    $cmds[] = "postmap /etc/postfix/generic";
} else {
    $cmds[] = "postconf -X stmp_generic_maps";
}

$cmds[] = "postmap /etc/postfix/sasl/sasl_passwd";
$cmds[] = "/usr/sbin/postfix set-permissions";
$cmds[] = "postfix check";
$cmds[] = "service postfix start";

foreach ( $cmds as $v ) {
    echo "running $v\n";
    echo run_cmd( $v );
}


