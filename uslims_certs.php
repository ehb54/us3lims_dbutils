<?php

### user defines

$scigap_host = "api.scigap.org:9930";
$httpdconfigdir = "/etc/httpd/conf.d";

### end user defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

displays info about certificates

Options

--help                : print this information and exit
--expiry              : displays expiry info about certs


__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$anyargs = false;
$expiry     = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--expiry": {
            array_shift( $u_argv );
            $expiry = true;
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

if ( !$anyargs || count( $u_argv ) ) {
    echo $notes;
    exit;
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

function extractdates( $str ) {
    $dates = [];
    if ( preg_match( "/^notBefore=(.*)$/m", $str, $matches ) && count( $matches ) > 1 ) {
        $dates[ "notbefore" ] = $matches[ 1 ];
    } else {
        $dates[ "notbefore" ] = "not found";
    }
    if ( preg_match( "/^notAfter=(.*)$/m", $str, $matches ) && count( $matches ) > 1 ) {
        $dates[ "notafter"  ] = $matches[ 1 ];
    } else {
        $dates[ "notafter"  ] = "not found";
    }
    return $dates;
}
        
if ( $expiry ) {
## header
    $breakline = echoline( '-', 40 + 3 + 25 + 3 + 25 + 12, false );
    $fmt = "%-40s | %-25s | %-25s | %-12s\n";
    $out =
        $breakline
        . sprintf(
            $fmt
            , 'certificate source'
            , 'valid not before'
            , 'valid not after'
            , 'status'
        )
        . $breakline
        ;

## $scigap_host
    $status = '';

    $dates = extractdates( trim( run_cmd( "echo | openssl s_client -connect $scigap_host 2>&1 | openssl x509 -noout -dates 2>&1 | grep -P '^not'" ) ) );
    $out .=
        sprintf(
            $fmt
            , $scigap_host
            , $dates[ "notbefore" ]
            , $dates[ "notafter" ]
            , $status
        );
    
## https
# check httpd configs

    $httpd_sslcerts = explode( "\n", trim( run_cmd( "cd $httpdconfigdir && grep -P '^\s*SSLCertificateFile' *conf" ) ) );

    foreach ( $httpd_sslcerts as $v ) {
        if ( $cert = preg_split( "/(:|\s+)/", $v ) ) {
            if ( count( $cert ) > 2 ) {
                debug_json( "certs", $cert );
                $csource = $cert[ 0 ];
                $cfile   = $cert[ 2 ];
                $debug = true;
                $dates = extractdates( trim( run_cmd( "openssl x509 --dates -noout -in $cfile" ) ) );
                $out .=
                    sprintf(
                        $fmt
                        , $csource
                        , $dates[ "notbefore" ]
                        , $dates[ "notafter" ]
                        , $status
                    );

            }
        }
    }
        

## close up
    $out .= $breakline;
    echo $out;
    exit;
}
