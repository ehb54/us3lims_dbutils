#!/usr/bin/php
<?php

$self = __FILE__;
$selfdir = dirname( $self );

require "utility.php";

$cssfile = "new.css";

$notes = <<<__EOD
  usage: $self {options} files

  tool to remove inline styles & convert to css

    --list    : list unique style= tags
    --help    : print this information and exit
    --update  : updates the files with class, appends to $cssfile

__EOD;

$targz = false;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--list": {
            array_shift( $u_argv );
            $list = true;
            break;
        }
        case "--update": {
            array_shift( $u_argv );
            $update = true;
            break;
        }

      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }
}
if ( !isset( $list ) && !isset( $update ) ) {
    error_exit( "nothing to do, specify --list or --update" );
}

if ( !count( $u_argv ) ) {
    error_exit( "at least one file must be specified" );
}

## object which will contain all relevant info
$rplc = (object)[];

function double_quoted( $style ) {
    if ( preg_match( '/^style="/', $style ) ) {
        return true;
    }
    return false;
}

function style2css( $style ) {
    global $rplc;

    $dq = double_quoted( $style );

    if ( $dq ) {
        preg_match( '/^style="([^"]*)"/', $style, $matches );
    } else {
        preg_match( '/^style=\'([^\']*)\'/', $style, $matches );
    }

    # debug_json( "style2css( $style ) matches", $matches );

    $classcontent = $matches[1];

    $classname =
        preg_replace(
            [
             '/%/'
             ,'/#/'
             ,'/-/'
             ,'/:/'
             ,'/;/'
             ,'/ /'
            ]
            ,
            [
             '_p_'
             ,'_h_'
             ,'_d_'
             ,'_c_'
             ,'_s_'
             ,'_'
            ]
            , trim( $classcontent )
        );

    ## perhaps custom renames for better looking html ?

    if ( !isset( $rplc->$classname ) ) {
        $rplc->$classname = (object)[];
        $rplc->$classname->css = ".$classname { $classcontent };";
        $rplc->$classname->rplc = "class='$classname'";
    }

    if ( $dq ) {
        $rplc->$classname->double_quote_rplc = "/$style/";
    } else {
        $rplc->$classname->single_quote_rplc = "/$style/";
    }

    return $classname;
}

## there may already be a class= in the attributes, so need to merge them 

function merge_multiple_class( $contents ) {
    $lines = explode( "\n", $contents );
    foreach ( $lines as &$line ) {
        if ( !preg_match( '/class=\'([^\']+)\'.*class=\'([^\']+)\'/', $line, $matches ) ) {
            continue;
        }
        # debug_json( "merge_multiple_class matches", $matches );
        ## remove new class from line
        $line = str_replace(
            [
             "class='$matches[1]'"
             ,"class='$matches[2]'"
            ]
            ,[
                "class='$matches[1] $matches[2]'"
                ,""
            ]
            ,$line
            );
        # echo "new line : $line\n";
    }
    return implode( "\n", $lines );
}

function replace_in_file( $file, $style, $classname ) {
    global $rplc;

    $dq = double_quoted( $style );

    $contents = file_get_contents( $file );
    $contents = preg_replace(
        double_quoted( $style )
        ? $rplc->$classname->double_quote_rplc
        : $rplc->$classname->single_quote_rplc
        , $rplc->$classname->rplc
        , $contents
        );

    $contents = merge_multiple_class( $contents );

    file_put_contents( $file, $contents );
}


## process files


foreach ( $u_argv as $f ) {
    if ( !file_exists( $f ) ) {
        error_exit( "file '$f' does not exist" );
    }

    $l = `grep style= $f`;

    if ( strlen( $l ) ) {
        preg_match_all( '/style="[^"]*"/', $l, $double_quoted );
        foreach ( $double_quoted[0] as $style ) {
            $classname = style2css( $style );
            if ( isset( $update ) ) {
                replace_in_file( $f, $style, $classname );
            }
        }

        preg_match_all( '/style=\'[^\']*\'/', $l, $single_quoted );
        foreach ( $single_quoted[0] as $style ) {
            $classname = style2css( $style );
            if ( isset( $update ) ) {
                replace_in_file( $f, $style, $classname );
            }
        }
    }        
}

# debug_json( "rplc", $rplc );

$css = "";

foreach ( $rplc as $k => $v ) {
    $css .= "$v->css\n";
}

if ( isset( $update ) ) {
    file_put_contents( $cssfile, $css, FILE_APPEND );
    echo "> $cssfile\n";
} else {
    echo $css;
}

