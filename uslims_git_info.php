<?php

require "utility.php";

# user defines

$us3home         = exec( "ls -d ~us3" );
$wwwpath         = "/srv/www/htdocs";
$usguipath       = "/opt/ultrascan3";
$rev_cache       = ".git_info_rev_cache";
$us3ini          = "$us3home/lims/.us3lims.ini";
$db_user         = "us3php";
$slurmconf       = "/etc/slurm/slurm.conf"; 
$coresparallel   = get_slurm_cores( 4, $slurmconf );

$reposearchpaths =
    [
     "$us3home/lims/bin"
     ,"$us3home/lims/database"
     ,"$us3home/us3-nodb"
     ,$usguipath
     ,$wwwpath
    ];

# user defines continued
$known_repos =
    [
     "$us3home/lims/database/sql" => [
         "use"      => "lims"
         ,"summary" => true
         ,"own"     => "us3:us3"
         ,"git"     => [
             "url" => "https://github.com/ehb54/us3_sql.git"
             ,"branch" => "master"
         ]
     ]
     ,"$us3home/lims/database/utils" => [
         "use" => "util"
         ,"own" => "us3:us3"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_dbutils.git"
             ,"branch" => "main"
         ]
     ]
     ,"$us3home/lims/bin" => [
         "use" => "lims"
         ,"own" => "us3:us3"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_gridctl.git"
             ,"branch" => "master"
         ]
     ]
     ,"$us3home/us3-nodb" => [
         "use"        => "mpi"
         ,"summary"   => true
         ,"own"       => "us3:us3"
         ,"buildable" => true
         ,"build_cmd" => "module swap ultrascan/mpi-build && ./make-uma build && ./make-uma install"
         ,"git" => [
             "url" => "https://github.com/ehb54/ultrascan3.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/common" => [
         "use" => "lims"
         ,"own" => "us3:apache"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_common.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/common/class/ultrascan-airavata-bridge" => [
         "use" => "lims"
         ,"own" => "us3:apache"
         ,"git" => [
             "url" => "https://github.com/SciGaP/ultrascan-airavata-bridge.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/uslims3" => [
         "use" => "lims"
         ,"own" => "us3:apache"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_webinfo.git"
             ,"branch" => "master"
         ]
     ]
     ,"$wwwpath/uslims3/uslims3_newlims" => [
         "use" => "lims"
         ,"own" => "us3:apache"
         ,"git" => [
             "url" => "https://github.com/ehb54/us3lims_newinst.git"
             ,"branch" => "master"
         ]
     ]
     ,"$usguipath" => [
         "use"        => "gui"
         ,"summary"   => true
         ,"own"       => "usadmin:usadmin"
         ,"buildable" => true
         ,"build_cmd" => "module swap ultrascan/gui-build && ./makeall.sh -j__coresparallel__ && ./makesomo.sh -j__coresparallel__"
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

    
$known_uses = [];
foreach ( $known_repos as $v ) {
    $known_uses[ $v['use'] ] = 1;
}
$known_use_list = implode( ", ", array_keys( $known_uses ));

$notes = <<<__EOD
usage: $self {options} {db_config_file}

list all git repo info
must be run with root privileges

Options

--help                : print this information and exit
    
--clear-rev-cache     : clears the revision cache to get latest revisions
--diff-report         : shows git diff details for local changes
--quiet               : suppress some info messages
--skip-unknown        : suppress reporting of discovered Use:unknown repos
--update-branch       : update branch to default branch (when changing to a new branch might require --update-pull first)
--update-pull use     : update repos by use, currently $known_use_list or all
--update-pull-build   : recompile buildible repos. requires --update-pull also be specified
--cores #             : use core count instead of discoverd count for --update-pull-build if supported

--summary             : summary report for spreadsheet inclusion
--summary-keep-ref-db : keep scheam reference db when running summary report

--no-db               : do not use database even if found. primarily for testing

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$clear_rev_cache   = false;
$diff_report       = false;
$no_db             = false;
$quiet             = false;
$skip_unknown      = false;
$update_branch     = false;
$update_pull       = false;
$update_pull_build = false;
$ansible_run       = false;
$summary           = false;
$summary_keep_ref  = '';

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--clear-rev-cache": {
            array_shift( $u_argv );
            $clear_rev_cache = true;
            break;
        }
        case "--cores": {
            array_shift( $u_argv );
            $coresparallel = array_shift( $u_argv );
            if ( !is_numeric( $coresparallel ) || false !== strpos( $coresparallel, '.' ) || intval( $coresparallel ) < 1 ) {
                error_exit( "--cores requires an integer value argument of at least 1, '$coresparallel' given" );
            }
            break;
        }
        case "--diff-report": {
            array_shift( $u_argv );
            $diff_report = true;
            break;
        }
        case "--skip-unknown": {
            array_shift( $u_argv );
            $skip_unknown = true;
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
        case "--summary": {
            array_shift( $u_argv );
            $summary = true;
            break;
        }
        case "--summary-keep-ref-db": {
            array_shift( $u_argv );
            $summary_keep_ref = "--compare-keep-ref-db";
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

# check for repo_branches

if ( !isset( $repo_branches ) ) {
    error_exit( "\$repo_branches must now be defined in $use_config_file. Template is in db_config.php.template" );
}

if ( !is_array( $repo_branches ) ) {
    error_exit( "\$repo_branches is improperly defined, check $use_config_file. Template is in db_config.php.template" );
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
    global $repo_branches;

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
    if ( !isset( $repo_branches[ $url ] ) ) {
        error_exit( "$url not defined in \$repo_branches" );
    }
    $branch = $repo_branches[ $url ];
    run_cmd( "cd $tdir && git clone -b $branch --single-branch $url repo" );
    $hash = trim( run_cmd( "cd $tdir/repo && git log -1 --oneline .| cut -d' ' -f1" ) );
    $rev  = trim( run_cmd( "cd $tdir/repo && git log --oneline | sed -n '/$hash/,99999p' | wc -l" ) );
    run_cmd( "rm -fr $tdir/repo" );
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

## replace branches

foreach ( $known_repos as $k => $v ) {
    if ( !isset( $known_repos[$k]["git"] ) ||
         !isset( $known_repos[$k]["git"]["url"] ) ) {
        error_exit( "\$known_repos->$k\->git is missing or is missing ->url" );
    }
    $url =  $known_repos[$k]["git"]["url"];
    if ( !isset( $repo_branches[$url] ) ) {
        error_exit( "$url from \$known_repos[$k] is not present in \$repo_branches (defined in $use_config_file)" );
    }
    $known_repos[$k]["git"]["branch"] = $repo_branches[$url];
}
    
$repodirs = [];
foreach ( $reposearchpaths as $v ) {
    $repodirs = array_merge( $repodirs, array_filter( explode( "\n", trim( run_cmd( "find $v -follow -name '*.git' 2>/dev/null | sed 's/.git\$//' | sed 's/\/\$//'" ) ) ) ) );
}

$repos = (object)[];

foreach ( $repodirs as $v ) {
    run_cmd( "git config --global --add safe.directory $v" );
    $repos->{ $v } = (object)[];
    $repos->{ $v }->{ 'revision' } = (object)[];
    $repos->{ $v }->{ 'revision' }->{ 'date' }   = trim( run_cmd( "cd $v && git log -1 --shortstat .|grep Date:|sed -e 's/Date:   //'" ) );
    $repos->{ $v }->{ 'revision' }->{ 'hash' }   = trim( run_cmd( "cd $v && git log -1 --oneline .| cut -d' ' -f1" ) );
    $repos->{ $v }->{ 'revision' }->{ 'number' } = trim( run_cmd( "cd $v && git log --oneline | sed -n '/" . $repos->{ $v }->{ 'revision' }->{ 'hash' } . "/,99999p' | wc -l" ) );
    $repos->{ $v }->{ 'local_changes' }          = trim( run_cmd( "cd $v && git status -s | grep '^ M' | wc -l" ) );
    $repos->{ $v }->{ 'remote' }                 = trim( run_cmd( "cd $v && git remote -v | grep '\(fetch\)' | awk '{ print \$2 }'" ) );
    if ( substr( $repos->{ $v }->{ 'remote' }, -4 ) != ".git" ) {
       error_exit( "remote for repo in $v does not end in .git\nreset with:\ncd $v && git remote set-url origin " .  $repos->{ $v }->{ 'remote' } . ".git" );
    }
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
        if ( $skip_unknown ) {
            unset( $repos->{ $v } );
        } else {
            $repos->{ $v }->{ 'use' } = "unknown";
            $repos->{ $v }->{ 'revision' }->{ 'remote' } = get_rev( $repos->{ $v }->{ 'remote' } );
            $repos->{ $v }->{ 'revdiffers' }             = $repos->{ $v }->{ 'revision' }->{ 'remote' } != $repos->{ $v }->{ 'revision' }->{ 'number' };
        }
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

if ( $summary ) {
    $order = [ "lims", "mpi", "gui" ];

    $hdr = '"server","db instances","db instances with diffs","lims sql version","local changes","us mpi version","local changes","us gui_version","local changes"';
    $out = '"' . trim( run_cmd( 'hostname' ) ) . '"';

    $ver = (object)[];

    $cmd = "php uslims_db_schemas.php --compare $summary_keep_ref";
    $out .= ',' . trim( run_cmd( "php uslims_db_schemas.php --compare $summary_keep_ref" ) );

    foreach ( $known_repos as $k => $v ) {
        if ( !isset( $v[ 'summary' ] ) || !$v[ 'summary' ] ) {
            continue;
        }
        # debug_json( "$k", $v );
        $ver->{ $repos->{$k}->{"use"} } = (object)[];
        if ( isset( $repos->{$k} ) ) {
            # debug_json( "repos $k", $repos->{$k} );
            $ver->{ $repos->{$k}->{"use"} }->{"version"}      = $repos->{$k}->{"revision"}->{"number"};
            $ver->{ $repos->{$k}->{"use"} }->{"local_changes"} = $repos->{$k}->{"local_changes"} > 0 ? $repos->{$k}->{"local_changes"} : '';
        } else {
            $ver->{ $repos->{$k}->{"use"} }->{"version"}      = 'n/a';
            $ver->{ $repos->{$k}->{"use"} }->{"local_changes"} = '"n/a"';
        }            
    }

    # debug_json( "ver summary", $ver );

    foreach ( $order as $v ) {
        if ( isset( $ver->{$v} ) ) {
            # debug_json( "ver $v", $ver->{$v});
            $out .= ',' . $ver->{$v}->{"version"} . ',' . $ver->{$v}->{"local_changes"};
        }
    }

    echo "$hdr\n";
    echo "$out\n";
    
    exit(0);
}
        
## print std reports

printf( "%-60s| %-60s %1s | %-20s %1s | %-13s | %-5s %1s | %-31s| %13s |\n", 
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
echoline( "-", 226 );
foreach ( $repos as $k => $v ) {
    if ( !$skip_unknown || $v->{'use'} != 'unknown' ) {
        printf( "%-60s| %-60s %1s | %-20s %1s | %-13s | %5d %1s | %-31s| %11d %1s |\n", 
                substr( $k, 0, 60 )
                ,substr( $v->{'remote'}, 0, 60 )
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
}
echoline( "-", 226 );

if ( $diff_report ) {
    $diff_run = false;

    foreach ( $repos as $k => $v ) {
        if ( !$skip_unknown || $v->{'use'} != 'unknown' ) {
            if ( $v->{'local_changes'} > 0 ) {
                echoline( "=" );
                echo "git changes\nrepo: " . $v->{'remote'} . "\ndirectory: $k\n";
                echoline( "=" );
                echo run_cmd( "cd $k && git diff" );
                $diff_run = true;
            }
        }
    }
    if ( !$diff_run ) {
        echoline( "=" );
        echo "no local changes to report\n";
    }           
    echoline( "=" );
}    

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
    $missing_repos       = 0;
    $repos_to_update     = [];
    foreach ( $repos as $k => $v ) {
        if ( $v->{ 'remote' } != "missing" ) {
            if ( $update_repo_use == "all" ||
                 $v->{'use'} == $update_repo_use ) {
                if ( $v->{'local_changes'} ) {
                    $local_changes_repos++;
                } else {
                    $repos_to_update[] = $k;
                }
            }
        } else {
            $missing_repos++;
        }
    }
    if ( $local_changes_repos ) {
        error_exit( "$local_changes_repos contain local changes, please correct before continuing" );
    }

    if ( $missing_repos ) {
        $notes .= "NOTICE: $missing_repos missing repo(s) will not be updated\n";
    }

    foreach ( $repos_to_update as $k ) {
        $v = $repos->{ $k };
        echoline();
        echo "Updating: pull in $k\n";
        $branch = $repos->{$k}->{'branch'};
        run_cmd( "cd $k && git pull" );
        if ( $update_pull_build &&
             isset( $known_repos[ $k ][ 'buildable' ] ) ) {
            echo "Updating: build in $k, this may take a while\n";
            echo "Using $coresparallel cores to build if supported\n";
            $logfile = newfile_file( 'build' . str_replace( '/', '_', $k ) . '.log', '' );
            $build_cmd = preg_replace( '/__coresparallel__/', $coresparallel, $known_repos[ $k ][ 'build_cmd' ] );
            $cmd = "(cd $k && $build_cmd) >> $logfile";
            run_cmd( $cmd );
            echo "Updating: build finished\n";
            $notes .= "NOTE: check output for build of $k in $logfile\n";
        }
        if ( isset( $known_repos[ $k ][ 'own' ] ) ) {
            $own = $known_repos[ $k ][ 'own' ];
            echo "Updating: permissions of $k to $own\n";
            run_cmd( "chown -R $own $k" );
            echo "Updating: permissions updated\n";
        }
    }
}

if ( strlen( $notes ) ) {
    echoline();
    echo $notes;
}
