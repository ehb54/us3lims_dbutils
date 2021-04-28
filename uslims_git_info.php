<?php

# user defines

$us3home         = "/home/us3";
$wwwpath         = "/srv/www/htdocs";
$usguipath       = "/opt/ultrascan3";
$rev_cache       = ".git_info_rev_cache";
$us3ini          = "$us3home/lims/.us3lims.ini";
$db_user         = "us3php";

$reposearchpaths =
    [
     $us3home
     ,$usguipath
     ,$wwwpath
    ];

$known_repos =
    [
     "$us3home/lims/database/sql" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3_sql.git"
             ,"branch" => "master"
         ]
     ]
     ,"$us3home/lims/database/utils" => [
         "use" => "util"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_dbutils.git"
             ,"branch" => "main"
         ]
     ]
     ,"$us3home/lims/bin" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_gridctl.git"
             ,"branch" => "master"
         ]
     ]
     ,"$us3home/us3-nodb" => [
         "use" => "mpi"
         ,"buildable" => true
         ,"build_cmd" => "module swap ultrascan/mpi-build && ./make-uma build && ./make-uma install"
         ,"git" => [
             "url" => "https://github.com/ehb54/ultrascan3.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/common" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_common.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/common/class/ultrascan-airavata-bridge" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/SciGaP/ultrascan-airavata-bridge.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/uslims3" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_webinfo.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/uslims3/uslims3_newlims" => [
         "use" => "lims"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_newinst.git"
             ,"branch" => "master"
         ]
     ]
     ,"$usguipath" => [
         "use" => "gui"
         ,"buildable" => true
         ,"build_cmd" => "module swap ultrascan/gui-build && ./makeall.sh -j4 && ./makesomo.sh -j4"
         ,"git" => [
             "url" => "https://github.com/ehb54/ultrascan3.git"
             ,"branch" => "master"
         ]
     ]
    ];

# end user defines

# developer defines

$logging_level = 2;

# end of developer defines

$self = __FILE__;
$cwd  = getcwd();

require "utility.php";
    
$known_uses = [];
foreach ( $known_repos as $v ) {
    $known_uses[ $v['use'] ] = 1;
}
$known_use_list = implode( ", ", array_keys( $known_uses ));

$notes = <<<__EOD
usage: $self {options}

list all git repo info
must be run with root privileges

Options

--help               : print this information and exit
    
--clear_rev_cache    : clears the revision cache to get latest revisions
--quiet              : suppress some info messages
--update-branch      : update branch to default branch
--update-pull use    : update repos by use, currently $known_use_list or all
--update-pull-build  : recompile buildible repos. requires --update-pull also be specified

--no-db              : do not use database even if found. primarily for testing

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$clear_rev_cache   = false;
$no_db             = false;
$quiet             = false;
$update_branch     = false;
$update_pull       = false;
$update_pull_build = false;
$ansible_run       = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--clear_rev_cache": {
            array_shift( $u_argv );
            $clear_rev_cache = true;
            break;
        }
        case "--no-db": {
            array_shift( $u_argv );
            $no_db = true;
            break;
        }
        case "--update-branch": {
            array_shift( $u_argv );
            $update_branch = true;
            break;
        }
        case "--update-pull": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --update-pull requires an argument\n\n$notes" );
            }
            $update_repo_use = array_shift( $u_argv );
            if ( $update_repo_use != "all" && !isset( $known_uses[ $update_repo_use ] ) ) {
                error_exit( "\nOption --update-pull invalid use '$update_repo_use'\n\n$notes" );
            }
            $update_pull = true;
            break;
        }
        case "--update-pull-build": {
            array_shift( $u_argv );
            $update_pull_build = true;
            break;
        }
        case "--quiet": {
            array_shift( $u_argv );
            $quiet = true;
            break;
        }
        case "--ansible": {
            array_shift( $u_argv );
            $ansible_run = true;
            break;
        }
      default:
        error_exit( "\nUnknown option '$u_argv[0]'\n\n$notes" );
    }        
}

if ( $update_pull_build && !$update_pull ) {
    error_exit( "\nOption --update_pull_build requires --update_pull to be specified.\n\n$notes" );
}

if ( !$no_db && file_exists( $us3ini ) ) {
    # assume lims
    $dbhost = "localhost"; # always localhost
    $user   = $db_user;
    $cfgs   = parse_ini_file( $us3ini, true );
    if ( !isset( $cfgs[ $user ] ) ) {
        error_exit( "Ansible run: $user is not found" );
    }
    $passwd = $cfgs[ $user ][ 'password' ];
    echo "found database\n";
} else {
    $no_db = true;
    echo "running without database, existing dbinsts, if any, will report Use:unknown\n";
}

if ( !is_admin() ) {
    error_exit( "you must have administrator privileges" );
}

# utility routines

function svn_repo( $path ) {
    global $known_repos;
    $svn_repo_use_trunk = [ "master", "main" ];
    if ( in_array( $known_repos[ $path ][ 'git' ][ 'branch' ], $svn_repo_use_trunk ) ) {
        return $known_repos[ $path ][ 'git' ][ 'url' ] . "/trunk";
    } else {
        return $known_repos[ $path ][ 'git' ][ 'url' ] . "/branches/" . $known_repos[ $path ][ 'git' ][ 'branch' ];
    }
}

$rev_info = (object)[];
function get_rev( $url ) {
    global $rev_cache;
    global $rev_info;
    global $quiet;
    if ( isset( $rev_info->{$url} ) ) {
        return $rev_info->{$url};
    }

    if ( file_exists( $rev_cache ) ) {
        $rev_info = json_decode( file_get_contents( $rev_cache ), false );
        if ( isset( $rev_info->{$url} ) ) {
            return $rev_info->{$url};
        }
    }

    $tdir = tempdir( NULL, $rev_cache );
    if ( !$quiet ) {
        echo "cloning repo $url into $tdir to get revision information\n";
    }
    run_cmd( "cd $tdir && git clone $url repo" );
    $hash = trim( run_cmd( "cd $tdir/repo && git log -1 --oneline .| cut -d' ' -f1" ) );
    $rev  = trim( run_cmd( "cd $tdir/repo && git log --oneline | sed -n '/$hash/,99999p' | wc -l" ) );
    $debug = 1;
    run_cmd( "rm -fr $tdir/repo" );
    $debug = 0;
    $rev_info->{ $url } = $rev;
    file_put_contents( $rev_cache, json_encode( $rev_info ) );
    
    return $rev_info->{ $url };
}        

# main

if ( $clear_rev_cache ) {
    if ( file_exists( $rev_cache ) ) {
        unlink( $rev_cache );
    }
} else {
    if ( !$quiet && file_exists( $rev_cache ) ) {
        echoline();
        echo "Rev# cache last updated " . `stat -c "%y" $rev_cache`;
        echoline();
    }        
}
if ( !$quiet && !file_exists( $rev_cache ) ) {
    echoline();
    echo "Rev# cache will be created by cloning each of the known repos into " . sys_get_temp_dir() . "/${rev_cache}XXXXXX\n";
}

# update known repos

if ( !$no_db ) {
    $existing_dbs = existing_dbs();
    foreach ( $existing_dbs as $v ) {
        $known_repos[ "$wwwpath/uslims3/$v" ] = [
            "use" => "lims"
            ,"git" =>
            [
             "url" => "https://github.com/ehb54/us3lims_dbinst.git"
             ,"branch" => "master"
            ]
            ];
    }
}

$repodirs = [];
foreach ( $reposearchpaths as $v ) {
    $repodirs = array_merge( $repodirs, array_filter( explode( "\n", trim( run_cmd( "find $v -name '*.git' 2>/dev/null | sed 's/.git\$//' | sed 's/\/\$//'" ) ) ) ) );
}

$repos = (object)[];

foreach ( $repodirs as $v ) {
    $repos->{ $v } = (object)[];
    $repos->{ $v }->{ 'revision' } = (object)[];
    $repos->{ $v }->{ 'revision' }->{ 'date' }   = trim( run_cmd( "cd $v && git log -1 --shortstat .|grep Date:|sed -e 's/Date:   //'" ) );
    $repos->{ $v }->{ 'revision' }->{ 'hash' }   = trim( run_cmd( "cd $v && git log -1 --oneline .| cut -d' ' -f1" ) );
    $repos->{ $v }->{ 'revision' }->{ 'number' } = trim( run_cmd( "cd $v && git log --oneline | sed -n '/" . $repos->{ $v }->{ 'revision' }->{ 'hash' } . "/,99999p' | wc -l" ) );
    $repos->{ $v }->{ 'local_changes' }          = trim( run_cmd( "cd $v && git status -s | grep '^ M' | wc -l" ) );
    $repos->{ $v }->{ 'remote' }                 = trim( run_cmd( "cd $v && git remote -v | grep '\(fetch\)' | awk '{ print \$2 }'" ) );
    $repos->{ $v }->{ 'branch' }                 = trim( run_cmd( "cd $v && git branch --show-current" ) );
    $repos->{ $v }->{ 'branchdiffers' }          = false;
    $repos->{ $v }->{ 'urldiffers' }             = false;
    $repos->{ $v }->{ 'revdiffers' }             = false;
    $repos->{ $v }->{ 'revision' }->{ 'remote' } = NULL;
    if ( isset( $known_repos[ $v ] ) ) {
        $repos->{ $v }->{ 'use' } = $known_repos[ $v ][ 'use' ];
        $repos->{ $v }->{ 'branchdiffers' }          = $repos->{ $v }->{ 'branch' } != $known_repos[ $v ][ 'git' ][ 'branch' ];
        $repos->{ $v }->{ 'urldiffers' }             = $repos->{ $v }->{ 'remote' } != $known_repos[ $v ][ 'git' ][ 'url' ];
        $repos->{ $v }->{ 'revision' }->{ 'remote' } = get_rev( $known_repos[ $v ][ 'git' ][ 'url' ] );
        $repos->{ $v }->{ 'revdiffers' }             = $repos->{ $v }->{ 'revision' }->{ 'remote' } != $repos->{ $v }->{ 'revision' }->{ 'number' };
    } else {
        $repos->{ $v }->{ 'use' } = "unknown";
        $repos->{ $v }->{ 'revision' }->{ 'remote' } = get_rev( $repos->{ $v }->{ 'remote' } );
        $repos->{ $v }->{ 'revdiffers' }             = $repos->{ $v }->{ 'revision' }->{ 'remote' } != $repos->{ $v }->{ 'revision' }->{ 'number' };
    }
}

foreach ( $known_repos as $k => $v ) {
    if ( !isset( $repos->{$k} ) ) {
        $repos->{ $k } = (object)[];
        $repos->{ $k }->{ 'revision' } = (object)[];
        $repos->{ $k }->{ 'revision' }->{ 'date' }   = "";
        $repos->{ $k }->{ 'revision' }->{ 'hash' }   = "";
        $repos->{ $k }->{ 'revision' }->{ 'number' } = "";
        $repos->{ $k }->{ 'local_changes' }          = "";
        $repos->{ $k }->{ 'remote' }                 = "missing";
        $repos->{ $k }->{ 'branch' }                 = "missing";
        $repos->{ $k }->{ 'use' }                    = $v['use'];
        $repos->{ $k }->{ 'branchdiffers' }          = false;
        $repos->{ $k }->{ 'urldiffers' }             = false;
        $repos->{ $k }->{ 'revision' }->{ 'remote' } = get_rev( $known_repos[ $k ][ 'git' ][ 'url' ] );
        $repos->{ $k }->{ 'revdiffers' }             = true;
    }
}
        
printf( "%-60s| %-60s %1s | %-8s %1s | %-13s | %-5s %1s | %-31s| %12s\n", 
        "Path"
        ,"Git remote url"
        ,""
        ,"Branch"
        ,""
        ,"Use"
        ,"Rev#"
        ,""
        ,"Revision date"
        ,"Local changes"
        ,""
    );
echoline( "-", 212 );
foreach ( $repos as $k => $v ) {
    printf( "%-60s| %-60s %1s | %-8s %1s | %-13s | %5d %1s | %-31s| %10d %1s\n", 
            $k
            ,$v->{'remote'}
            ,boolstr( $v->{'urldiffers'}, "Δ" )
            ,$v->{'branch'}
            ,boolstr( $v->{'branchdiffers'}, "Δ" )
            ,$v->{'use'}
            ,$v->{'revision'}->{'number'}
            ,boolstr( $v->{'revdiffers'}, "Δ" )
            ,$v->{'revision'}->{'date'}
            ,$v->{'local_changes'}
            ,boolstr( $v->{'local_changes'} > 0, "Δ" )
        );
}
echoline( "-", 212 );

if ( $update_branch ) {
    $updated     = 0;
    $tot_differs = 0;
    foreach ( $repos as $k => $v ) {
        if ( $v->{'branchdiffers'} ) {
            $tot_differs++;
            if ( $v->{'local_changes'} ) {
                $warnings .= "WARNING: repo in $k branch not updated, local changes exist. Clear them manually\n";
            } else {
                $newbranch = $known_repos[ $k ][ 'git' ][ 'branch' ];                
                run_cmd( "cd $k && git checkout $newbranch" );
                echo "UPDATED: $k branch from $v->branch to $newbranch \n";
                $updated++;
            }
        }
    }
    if ( !$tot_differs ) {
        $warnings .= "NOTICE: No branches needed updating\n";
    }

    if ( $updated != $tot_differs ) {
        if ( $updated ) {
            $warnings .= "WARNING: Only $updated of $tot_differs branches updated\n";
        } else {
            $warnings .= "WARNING: None of $tot_differs branches updated\n";
        }
    } 
    flush_warnings( "All branches updated" );
}

$notes = "";

if ( $update_pull ) {
    echo "Checking repos for update-pull $update_repo_use\n";
    $local_changes_repos = 0;
    $repos_to_update = [];
    foreach ( $repos as $k => $v ) {
        if ( $update_repo_use == "all" ||
             $v->{'use'} == $update_repo_use ) {
            if ( $v->{'local_changes'} ) {
                $local_changes_repos++;
            } else {
                $repos_to_update[] = $k;
            }
        }
    }
    if ( $local_changes_repos ) {
        error_exit( "$local_changes_repos contain local changes, please correct before continuing" );
    }

    foreach ( $repos_to_update as $k ) {
        $v = $repos->{ $k };
        echo "Updating: pull in $k\n";
        $branch = $repos->{$k}->{'branch'};
        run_cmd( "cd $k && git pull" );
        if ( $update_pull_build &&
             isset( $known_repos[ $k ][ 'buildable' ] ) ) {
            echo "Updating: build in $k, this may take a while\n";
            $logfile = newfile_file( 'build' . str_replace( '/', '_', $k ) . '.log', '' );
            $cmd = "(cd $k && " . $known_repos[ $k ][ 'build_cmd' ] . ") >> $logfile";
            run_cmd( $cmd );
            echo "Updating: build finished\n";
            $notes .= "NOTE: check output for build of $k in $logfile\n";
        }
    }
}

if ( strlen( $notes ) ) {
    echoline();
    echo $notes;
}
