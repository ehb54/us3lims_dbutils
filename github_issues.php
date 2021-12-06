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
    
--owner    string      : set owner      (default: $owner)
--repo     string      : set repository (default: $repo)
--summary  string      : add file summary list

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$debug               = 0;
$summary             = false;

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
        case "--summary": {
            array_shift( $u_argv );
            $summary = true;
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
# add a new line since the users CR didn't echo
echo "\n";

function get_data( $url ) {
    global $headers;
    global $fres;
    global $fnames;
    global $token;
    global $debug;
    $cmd = "curl -siH \"Authorization: token $token\" $url";
    if ( $debug ) {
        echo "$cmd\n";
    }
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
            $cres[ "body" ] = trim($v);
            continue;
        }
        $fields = explode( ":", $v );
        $key = array_shift( $fields );
        $cres[ $key ] = trim( implode( ":", $fields ) );
    }
    if ( !array_key_exists( "body", $cres ) ) {
        error_exit( "missing body in response from $url" );
    }
    $bres = json_decode( $cres[ "body" ] );

    foreach ( $bres as $v ) {
        if ( $debug ) {
            debug_json( "v", $v );
        }
        if ( !isset( $v->most_recent_instance )
             || !isset( $v->most_recent_instance->state ) ) {
            debug_json( "ERROR unexpected results:", $bres );
            exit(-1);
        }
        if ( !isset( $v->rule )
             || !isset( $v->rule->security_severity_level ) ) {
            debug_json( "ERROR unexpected results:", $bres );
            exit(-1);
        }
        $severity = $v->rule->security_severity_level;
        $state = $v->most_recent_instance->state;
        if ( $state == "open" ) {
            $msg   = $v->most_recent_instance->message->text;
            $file  = $v->most_recent_instance->location->path;
            $sline = $v->most_recent_instance->location->start_line;
            $eline = $v->most_recent_instance->location->end_line;
            $scol  = $v->most_recent_instance->location->start_column;
            $ecol  = $v->most_recent_instance->location->end_column;

            $akey = sprintf( "$severity $file %6d:$eline $scol:$ecol $msg\n", $sline );
            $fres  [ $akey ] = 1;
            $fnames[ $file ] = 1;
        }
    }
    return $cres;
}


$headers   = [];
$headers[] = "accept: application/vnd.github.v3+json";
$headers[] = "user-agent: php7";
$headers[] = "authorization: token $token";

$fres   = [];
$fnames = [];

$aurl = "https://api.github.com/repos/$owner/$repo/code-scanning/alerts";
$cres = get_data( $aurl );

# debug_json( "cres", $cres );
# debug_json( "sbres", $bres );

if ( array_key_exists( "link", $cres ) ) {
    # <https://api.github.com/repositories/435009938/code-scanning/alerts?page=2>; rel="next", <https://api.github.com/repositories/435009938/code-scanning/alerts?page=5>; rel="last"
    $links = explode( ",", $cres["link"] );
    if ( count( $links ) != 2 ) {
        error_exit( "unexpected link format " . $cres["link"] );
    }
    $linknext = explode( ";", $links[0] );
    $linklast = explode( ";", $links[1] );
    if (
        count( $linknext ) != 2 
        || count( $linklast ) != 2 
        ) {
        error_exit( "unexpected link sub-format " . $cres["link"] );
    }
    if ( strpos( $linknext[1], 'rel="next"' ) === false ) {
        error_exit( "unexpected link next format " . $linknext );
    }
    if ( strpos( $linklast[1], 'rel="last"' ) === false ) {
        error_exit( "unexpected link last format " . $linklast );
    }

    $linkthis = substr( $linknext[0], 1, strlen( $linknext[0] ) - 2 );
    $firstpage = preg_replace( '/^.*page=/', '', $linkthis );
    $linkbase  = preg_replace( '/\d+$/', '', $linkthis );
    $lastpage  = preg_replace( '/(^.*page=|>$)/', '', $linklast[0] );
    if ( $debug ) {
        echo "linkbase $linkbase\nfirstpage $firstpage\nlastpage $lastpage\n";
    }
    for ( $i = $firstpage; $i <= $lastpage; ++$i ) {
        get_data( "$linkbase$i" );
    }
}


ksort( $fres );

if ( $summary ) {
    echoline();
}

echo implode( "", array_keys( $fres ) );

if ( $summary ) {
    echoline();
    ksort( $fnames );
    echo implode( "\n", array_keys( $fnames ) );
    echo "\n"; 
    echoline();
}

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
