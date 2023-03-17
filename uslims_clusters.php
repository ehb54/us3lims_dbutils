<?php

# user defines

$us3lims             = exec( "ls -d ~us3/lims" );
$ll_base_dir         = "$us3lims/etc/joblog";
$us3bin              = "$us3lims/bin";
$global_config_file  = "/srv/www/htdocs/common/global_config.php";
$cluster_status_file = "$us3lims/bin/cluster_config.php";

include "$us3bin/listen-config.php";

# end user defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

information about defined clusters

Options

--help                : print this information and exit

--list                : list clusters
--only-active         : list only active entries
--db dbname           : restrict results to dbname (can be specified multiple times)


__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name
$anyargs    = false;

$use_dbs    = [];
$list       = false;
$onlyactive = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    $anyargs = true;
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            $use_dbs[] = array_shift( $u_argv );
            break;
        }
        case "--list": {
            array_shift( $u_argv );
            $list = true;
            break;
        }
        case "--only-active": {
            array_shift( $u_argv );
            $onlyactive = true;
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
            
file_perms_must_be( $use_config_file );
require $use_config_file;

### check global configuration

function check_global_config() {
    global $class_dir;
    global $errors;
    global $debug;
    global $fmt;
    global $global_config_file;
    global $global_cluster_details;
    global $onlyactive;
    
    if ( !isset( $errors ) ) {
        $errors = '';
    }

    $error_msg = function( $msg ) {
        global $errors;
        $errors .= "$msg\n";
    };

    $debug_msg = function( $msg, $debug ) {
        if ( $debug ) {
            echo "debug - $msg\n";
        }
    };

    if ( !isset( $class_dir ) ) {
        $error_msg( "\$class_dir is not set" );
        return;
    }

    if ( !is_dir( $class_dir ) ) {
        $error_msg( "\$class_dir [$class_dir] is not a directory" );
        return;
    }

    ## global configs

    if ( !file_exists( $global_config_file ) ) {
        $error_msg("\$global_config_file_dir [$global_config_file] does not exist");
        return;
    }

    ## read global config first, so dbinst overrides

    try {
        include( $global_config_file );
    } catch ( Exception $e ) {
        $error_msg( "including $global_config_file " . $e->getMessage() );
        return;
    }

    $global_cluster_details = $cluster_details;

    $reqkey = [
        'active'
        ,'airavata'
        ,'name'
        ,'submithost'
        ,'userdn'
        ,'submittype'
        ,'httpport'
        ,'workdir'
        ,'sshport'
        ,'queue'
        ,'maxtime'
        ,'ppn'
        ,'ppbj'
        ,'maxproc'
        ];

    $reqkey_metascheduler = [
        'active'
        ,'name'
        ,'airavata'
        ,'clusters'
        ];

    $msg = (object)[];
    
    ksort( $cluster_details );

    foreach ( $cluster_details as $k => $v ) {
        ## message table fields

        $msg->cluster = $k;
        $msg->active   = array_key_exists( 'active',   $v ) ? boolstr( $v['active'],   'True', 'False' ) : 'Missing';
        $msg->airavata = array_key_exists( 'airavata', $v ) ? boolstr( $v['airavata'], 'True', 'False' ) : 'Missing';
        $msg->pmg      = boolstr( array_key_exists( 'pmg',     $v ) && $v['pmg'],     'True', 'False' );
        $msg->pmgonly  = boolstr( array_key_exists( 'pmgonly', $v ) && $v['pmgonly'], 'True', 'False' );
        $msg->issues   = '';
        
        ## do all required keys exist for this cluster?
        foreach ( array_key_exists( "clusters", $v )
                  ? $reqkey_metascheduler
                  : $reqkey
                  as $key ) {
            if ( !array_key_exists( $key, $v ) ) {
                $msg->issues .= "missing '$key' ";
            }
        }
        if ( !$onlyactive ||
             $msg->active == 'True' ) {
            echo sprintf(
                $fmt
                ,$msg->cluster
                ,$msg->active
                ,$msg->airavata
                ,$msg->pmg
                ,$msg->pmgonly
                ,$msg->issues
                );
        }
    }
}

### check cluster status configuration

function check_cluster_status() {
    global $errors;
    global $debug;
    global $fmt;
    global $cluster_status_file;
    global $global_cluster_details;
    global $onlyactive;
    
    if ( !isset( $errors ) ) {
        $errors = '';
    }

    $error_msg = function( $msg ) {
        global $errors;
        $errors .= "$msg\n";
    };

    $debug_msg = function( $msg, $debug ) {
        if ( $debug ) {
            echo "debug - $msg\n";
        }
    };


    ## cluster status config file

    if ( !file_exists( $cluster_status_file ) ) {
        $error_msg("\$cluster_status_file [$cluster_status_file] does not exist");
        return;
    }

    ## read global config first, so dbinst overrides

    try {
        include( $cluster_status_file );
    } catch ( Exception $e ) {
        $error_msg( "including $cluster_status_file " . $e->getMessage() );
        return;
    }

    $msg = (object)[];
    
    ksort( $cluster_configuration );

    if ( !isset( $global_cluster_details )
         || !is_array( $global_cluster_details ) ) {
        $error_msg( "missing \$global_config_file $global_config_file" );
        return;
    }
    
    foreach ( $global_cluster_details as $k => $v ) {
        if ( array_key_exists( 'active', $v )
             && $v['active'] 
             && !array_key_exists( $k, $cluster_configuration )
             && !array_key_exists( 'clusters', $v )
            ) {
            $errors .= "WARNING: $k is active in global configuration, but not in cluster status\n";
        }
    }

    foreach ( $cluster_configuration as $k => $v ) {
        ## message table fields

        $msg->cluster = $k;
        $msg->active   = array_key_exists( 'active',   $v ) ? boolstr( $v['active'],   'True', 'False' ) : 'Missing';
        $msg->airavata = 'n/a';
        $msg->pmg      = 'n/a';
        $msg->pmgonly  = 'n/a';
        $msg->issues   = '';
        
        if ( !array_key_exists( $k, $global_cluster_details )
            || !array_key_exists( 'active', $global_cluster_details[$k] ) ) {
            if ( array_key_exists( 'active', $v )
                 && $v['active'] ) {
                $msg->issues .= "not present or incomplete in global_cluster.php. ";
            }
        } else {
            if ( array_key_exists( 'active', $v )
                 && !$v['active']
                 && $global_cluster_details[$k]['active'] ) {
                $msg->issues .= "active in global_cluster.php, but not in cluster_config.php. ";
            }
        }

        if ( !$onlyactive ||
             $msg->active == 'True' ) {
            echo sprintf(
                $fmt
                ,$msg->cluster
                ,$msg->active
                ,$msg->airavata
                ,$msg->pmg
                ,$msg->pmgonly
                ,$msg->issues
                );
        }
    }
}

function check_db_config( $use_db ) {
    global $class_dir;
    global $errors;
    global $fmt;
    global $onlyactive;
    global $debug;
    
    if ( !isset( $errors ) ) {
        $errors = '';
    }

    $full_path = "/srv/www/htdocs/uslims3/$use_db";

    $error_msg = function( $msg ) {
        global $errors;
        $errors .= "$msg\n";
    };

    $debug_msg = function( $msg, $debug ) {
        if ( $debug ) {
            echo "debug - $msg\n";
        }
    };

    if ( !isset( $class_dir ) ) {
        $error_msg( "\$class_dir is not set" );
        return;
    }

    if ( !is_dir( $class_dir ) ) {
        $error_msg( "\$class_dir [$class_dir] is not a directory" );
        return;
    }

    if ( !isset( $full_path ) ) {
        $error_msg( "\$full_path is not set" );
        return;
    }

    if ( !is_dir( $full_path ) ) {
        $error_msg( "\$full_path [$full_path] is not a directory" );
        return;
    }

    ## dbinst specific configs

    $dbinst_config_file = "$full_path/cluster_config.php";

    if ( !file_exists( $dbinst_config_file ) ) {
        $dbinst_config_file = "$full_path/../uslims3_newlims/cluster_config.php";
        if ( !file_exists( $dbinst_config_file ) ) {
            $error_msg( "no cluster_config.php file found" );
            return;
        }
    }

    echo "config file $dbinst_config_file\n";
    separator();

    ## global configs

    $global_config_file = "$class_dir/../global_config.php";

    if ( !file_exists( $global_config_file ) ) {
        $error_msg("\$global_config_file_dir [$global_config_file] does not exist");
        return;
    }

    ## read global config first, so dbinst overrides

    try {
        include( $global_config_file );
    } catch ( Exception $e ) {
        $error_msg( "including $global_config_file " . $e->getMessage() );
        return;
    }

    try {
        include( $dbinst_config_file );
    } catch ( Exception $e ) {
        $error_msg ( "including $dbinst_config_file " . $e->getMessage() );
        return;
    }

    if ( !isset( $cluster_configuration ) || !is_array( $cluster_configuration ) ) {
        $error_msg( "\$cluster_configuration not set or is not an array" );
        return;
    }

    if ( !isset( $cluster_details ) || !is_array( $cluster_details ) ) {
        $error_msg( "\$cluster_details not set or is not an array" );
        return;
    }

    $reqkey = [
        'active'
        ,'airavata'
        ,'name'
        ,'submithost'
        ,'userdn'
        ,'submittype'
        ,'httpport'
        ,'workdir'
        ,'sshport'
        ,'queue'
        ,'maxtime'
        ,'ppn'
        ,'ppbj'
        ,'maxproc'
        ];

    $reqkey_metascheduler = [
        'active'
        ,'name'
        ,'airavata'
        ,'clusters'
        ];

    $msg = (object)[];
    
    ksort( $cluster_details );

    foreach ( $cluster_details as $k => $v ) {
        ## message table fields

        $msg->cluster = $k;
        $msg->active   = array_key_exists( 'active',   $v ) ? boolstr( $v['active'],   'True', 'False' ) : 'Missing';
        $msg->airavata = array_key_exists( 'airavata', $v ) ? boolstr( $v['airavata'], 'True', 'False' ) : 'Missing';
        $msg->pmg      = boolstr( array_key_exists( 'pmg',     $v ) && $v['pmg'],     'True', 'False' );
        $msg->pmgonly  = boolstr( array_key_exists( 'pmgonly', $v ) && $v['pmgonly'], 'True', 'False' );
        $msg->issues   = '';

        ## is this present in $cluster_configuration ?
        if ( !array_key_exists( $k, $cluster_configuration ) ) {
            $msg->active = "False";
            $msg->issues = "not in \$cluster_configuration. "; 
        } else {
            if ( !is_array( $cluster_configuration[$k] ) ) {
                $msg->active = "False";
                $msg->issues = "\$cluster_configuration for this entry is corrupt (not an array). "; 
            }
            
            if ( !array_key_exists( 'active', $cluster_configuration[$k] ) ||
                 $cluster_configuration[$k]['active'] == false
                ) {
                $msg->active = "False";
            }
        }

        ## do all required keys exist for this cluster?
        foreach ( array_key_exists( "clusters", $v )
                  ? $reqkey_metascheduler
                  : $reqkey
                  as $key ) {
            if ( !array_key_exists( $key, $v ) ) {
                $msg->issues .= "missing '$key' ";
            }
        }
        if ( !$onlyactive ||
             $msg->active == 'True' ) {
            echo sprintf(
                $fmt
                ,$msg->cluster
                ,$msg->active
                ,$msg->airavata
                ,$msg->pmg
                ,$msg->pmgonly
                ,$msg->issues
                );
        }
    }
}

$fmt  = " %-18s | %-8s | %-8s | %-8s | %-8s | %s\n";
$flen = 100;
$header =
    sprintf(
        $fmt
        ,"cluster"
        ,"active"
        ,"airavata"
        ,"pmg"
        ,"pmgonly"
        ,"issues"
    );

## header

function separator() {
    global $flen;
    echoline( '-', $flen );
}

echoline( '=', $flen );
echo $header;

## global config

echoline( '=', $flen );
echo "global config : $global_config_file\n";
echoline( '-', $flen );
check_global_config();
if ( strlen( $errors ) ) {
    echo $errors;
    $errors = '';
}

## cluster status config

echoline( '=', $flen );
echo "cluster status config : $cluster_status_file\n";
echoline( '-', $flen );
check_cluster_status();
if ( strlen( $errors ) ) {
    echo $errors;
    $errors = '';
}

echoline( '=', $flen );

$existing_dbs = existing_dbs();

if ( !count( $use_dbs ) ) {
    $use_dbs = $existing_dbs;
} else {
    $db_diff = array_diff( $use_dbs, $existing_dbs );
    if ( count( $db_diff ) ) {
        error_exit( "specified --db not found in database : " . implode( ' ', $db_diff ) );
    }
}

foreach ( $use_dbs as $use_db ) {
    echoline( '-', $flen );
    echo "dbinst: $use_db\n";
    check_db_config( $use_db );
    if ( strlen( $errors ) ) {
        echo $errors;
        $errors = '';
    }
}

echoline( '=', $flen );
