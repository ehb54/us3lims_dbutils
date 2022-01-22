<?php

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} email {email ...}

sends a test message to specified email

Options

--help               : print this information and exit


__EOD;


require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( !count( $u_argv ) ) {
    echo $notes;
    exit;
}

$host = trim( `hostname` );

if ( !mail( implode( ",", $u_argv )
            ,"test email message from $host"
            ,"test email body" ) ) {
    error_exit( "email sending failed" );
} else {
    echo "mail seemed to send, now check the destination email for a message\n";
}

