<?php

# user defines

$owner = "ehb54";
$repo  = "ultrascan3";

# end user defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;

$notes = <<<__EOD
usage: $self

gets issues from github repo

__EOD;

$notes = <<<__EOD
usage: $self {options}

collects all code-scanning issues from github repo

Options

--help                 : print this information and exit
    
--owner string         : set owner      (default: $owner)
--repo  string         : set repository (default: $repo)

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$debug               = 0;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--owner": {
            array_shift( $u_argv );
            $owner = array_shift( $u_argv );
            break;
        }
        case "--repo": {
            array_shift( $u_argv );
            $repo = array_shift( $u_argv );
            break;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( count( $u_argv ) ) {
    error_exit( "Incorrect command format\n$notes" );
}

# enter token
echo "token: ";
system('stty -echo');
$token = trim(fgets(STDIN));
system('stty echo');
// add a new line since the users CR didn't echo
echo "\n";

$headers   = [];
$headers[] = "accept: application/vnd.github.v3+json";
$headers[] = "user-agent: php7";
$headers[] = "authorization: token $token";

$aurl = "https://api.github.com/repos/$owner/$repo/code-scanning/alerts";

$cmd = "curl -siH \"Authorization: token $token\" $aurl";
echo "$cmd\n";
$res = run_cmd( $cmd, false, true );

$cres = [];
$injson = false;

foreach ( $res as $v ) {
    if ( $injson ) {
        $cres[ "body" ] .= trim($v);
        continue;
    }
    if ( $v == "[" || $v == "{" ) {
        $injson = true;
        $cres[ "body" ] .= trim($v);
        continue;
    }
    $fields = explode( ":", $v );
    $key = array_shift( $fields );
    $cres[ $key ] = trim( implode( ":", $fields ) );
}
$bres = json_decode( $cres[ "body" ] );

# debug_json( "cres", $cres );
# debug_json( "sbres", $bres );

$fres = [];

foreach ( $bres as $v ) {
    if ( $debug ) {
        debug_json( "v", $v );
    }
    $state = $v->most_recent_instance->state;
    if ( $state == "open" ) {
        $msg   = $v->most_recent_instance->message->text;
        $file  = $v->most_recent_instance->location->path;
        $sline = $v->most_recent_instance->location->start_line;
        $eline = $v->most_recent_instance->location->end_line;
        $scol  = $v->most_recent_instance->location->start_column;
        $ecol  = $v->most_recent_instance->location->end_column;

        $akey = sprintf( "$file %6d:$eline $scol:$ecol $msg\n", $sline );
        $fres[ $akey ] = 1;
    }
}

ksort( $fres );

echo implode( "", array_keys( $fres ) );

if ( 0 ) { # not worth the effort... 
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_URL, $aurl );
    curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
    curl_setopt( $ch, CURLOPT_HEADER, TRUE );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $result = curl_exec( $ch );
    curl_close( $ch );
    
    debug_json( "result", $result );
}
