<?php

$self = __FILE__;
$hdir = __DIR__;

$us3bin    = exec( "ls -d ~us3/lims/bin" );
include_once "$us3bin/listen-config.php";

# $debug = 1;

$notes = <<<__EOD
usage: $self {config_file}

rsyncs to remote server

__EOD;

$u_argv = $argv;
array_shift( $u_argv );

if ( count( $u_argv ) > 1 ) {
    echo $notes;
    exit;
}

$config_file = "$hdir/db_config.php";
if ( count( $u_argv ) ) {
    $use_config_file = array_shift( $u_argv );
} else {
    $use_config_file = $config_file;
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
            
if ( count( $u_argv ) ) {
    echo $notes;
    exit;
}    
            
include "utility.php";
file_perms_must_be( $use_config_file );
require $use_config_file;

$rsync_local = isset( $rsync_local ) && $rsync_local;

$errors = "";
if ( !isset( $backup_dir ) ) {
    $errors .= "\$backup_dir is not set in $use_config_file\n";
}
if ( !isset( $rsync_user ) ) {
    $errors .= "\$rsync_user is not set in $use_config_file\n";
}
if ( !$rsync_local && !isset( $rsync_host ) ) {
    $errors .= "\$rsync_host is not set in $use_config_file\n";
}
if ( !isset( $rsync_path ) ) {
    $errors .= "\$rsync_path is not set in $use_config_file\n";
}
if ( !isset( $rsync_logs ) ) {
    $errors .= "\$rsync_logs is not set in $use_config_file\n";
}
if ( !isset( $backup_host ) ) {
    $errors .= "\$backup_host is not set in $use_config_file\n";
}
if ( !isset( $backup_email_reports ) ) {
    $errors .= "\$backup_email_reports is not set in $use_config_file\n";
}
if ( $backup_email_reports && !isset( $backup_email_address ) ) {
    $errors .= "\$backup_email_address is not set in $use_config_file\n";
}
if ( isset( $rsync_add ) && $rsync_add ) {
    $rsync_add_details = '';
    if ( !isset( $rsync_add_path ) ) {
        $errors .= "\$rsync_add_path must be set if \$rsync_add is set in $use_config_file\n";
    } else {
        if ( !is_array( $rsync_add_includes ) ) {
            $errors .= "\$rsync_add_includes must be an array in $use_config_file\n";
        } else {
            if ( !count( $rsync_add_includes ) ) {
                $errors .= "\$rsync_add_includes must be a non-empty array in $use_config_file\n";
            } 
            if ( in_array( "/", $rsync_add_includes ) ) {
                $errors .= "\$rsync_add_includes must not contain '/' in $use_config_file\n";
            } 
            $rsync_add_details .= implode( " ", $rsync_add_includes ) . " ";
        }
        if ( $rsync_add_path == $rsync_path ) {
            $errors .= "\$rsync_add_path must be different than \$rsync_path in $use_config_file\n";
        }
        if ( strncmp( $rsync_path, $rsync_add_path, strlen( $rsync_path ) ) == 0 ) {
            $errors .= "\$rsync_add_path must not contain \$backup_path in $use_config_file\n";
        }
    }
    $rsync_add_details .= $rsync_add_delete ? "--delete " : "";
    if ( isset( $rsync_add_excludes ) ) {
        if ( !is_array( $rsync_add_excludes ) ) {
            $errors .= "\$rsync_add_excludes must be an array in $use_config_file\n";
        } else {
            if ( count( $rsync_add_excludes ) ) {
                $rsync_add_details .= " --exclude " . implode( " --exclude ", $rsync_add_excludes );
            }
        }
    }
}

if ( $rsync_local && !strlen( $errors ) ) {
    if ( backup_rsync_path_is_under( $rsync_path, $backup_dir ) ) {
        $errors .= "\$rsync_path must not be \$backup_dir nor a path under it when \$rsync_local is set in $use_config_file\n";
    }
    if ( isset( $rsync_add ) && $rsync_add && backup_rsync_path_is_under( $rsync_add_path, $backup_dir ) ) {
        $errors .= "\$rsync_add_path must not be \$backup_dir nor a path under it when \$rsync_local is set in $use_config_file\n";
    }
}

if ( strlen( $errors ) ) {
    error_exit( $errors );
}

# keep the "add" from copying what the main rsync already copies and, when local, from copying the destinations into themselves
$rsync_add_auto_excludes = [];
if ( isset( $rsync_add ) && $rsync_add ) {
    $rsync_add_auto_paths = [];
    if ( isset( $rsync_add_exclude_backup_dir ) && $rsync_add_exclude_backup_dir ) {
        $rsync_add_auto_paths[] = $backup_dir;
        if ( isset( $backup_rdiff_temp ) ) {
            $rsync_add_auto_paths[] = $backup_rdiff_temp;
        }
    }
    if ( $rsync_local ) {
        $rsync_add_auto_paths[] = $rsync_path;
        $rsync_add_auto_paths[] = $rsync_add_path;
    }
    $rsync_add_auto_excludes = array_diff(
        backup_rsync_anchored_excludes( $rsync_add_includes, $rsync_add_auto_paths )
        ,isset( $rsync_add_excludes ) ? $rsync_add_excludes : []
        );
    if ( count( $rsync_add_auto_excludes ) ) {
        $rsync_add_details .= " --exclude " . implode( " --exclude ", $rsync_add_auto_excludes );
    }
}

$date = trim( run_cmd( 'date +"%y%m%d%H"' ) );
$use_backup_host = $backup_host;
if ( isset( $rsync_no_hostname ) && $rsync_no_hostname ) {
    $use_backup_host = '';
}

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
   mkdir( $backup_dir );
   backup_rsync_run_cmd( "sudo chown $rsync_user:$rsync_user $backup_dir" );
}

if ( !is_admin( false ) ) {
   error_exit( "This program must be run by root or a sudo enabled user" );
}

if ( !chdir( $backup_dir ) ) {
   backup_rsync_failure( "Could not change to directory $newfile_dir" );
}

if ( !is_dir( $rsync_logs ) ) {
    mkdir( $rsync_logs, 0700 );
    backup_rsync_run_cmd( "sudo chown $rsync_user:$rsync_user $rsync_logs" );
}

if ( $rsync_local ) {
    backup_rsync_run_cmd( "sudo mkdir -p $rsync_path", false );
    backup_rsync_local_mkdir( "$rsync_path/$use_backup_host", $rsync_user );
} else {
    $cmd = "runuser -l $backup_user -c \"ssh -o StrictHostKeyChecking=no $rsync_user@$rsync_host -C 'sudo mkdir -p $rsync_path'\"";
    backup_rsync_run_cmd( $cmd, false );
}

$logf = "$rsync_logs/remote-$backup_host-$date.log";
 
backup_rsync_run_cmd( "(echo -n 'rsync not started due to file lock: ' && date) > $logf", false );
// lock
if ( isset( $lock_dir ) ) {
   $lock_main_script_name  = __FILE__;
   require "$us3bin/lock.php";
} 
backup_rsync_run_cmd( "(echo -n 'rsync started: ' && date) > $logf", false );
if ( count( $rsync_add_auto_excludes ) ) {
    backup_rsync_run_cmd( "echo 'rsync add: automatically excluded " . implode( " ", $rsync_add_auto_excludes ) . "' >> $logf", false );
}

if ( isset( $rsync_no_sudo ) && $rsync_no_sudo ) {
    $sudo_rsync = "rsync";
} else {
    $sudo_rsync = "sudo rsync";
}

if ( isset( $rsync_path_sudo ) && $rsync_path_sudo ) {
    $use_rsync_path_sudo = "--rsync-path='$sudo_rsync'";
} else {
    $use_rsync_path_sudo = "";
}

# $cmd = "sudo -u $backup_user rsync -av -e 'ssh -l usadmin' --rsync-path='$sudo_rsync' --delete --update $backup_dir $rsync_user@$rsync_host:$rsync_path/$use_backup_host 2>&1 >> $logf";
# local: run as root and give the copies to $rsync_user, who owns them on a remote host too
if ( $rsync_local ) {
    $cmd = "sudo rsync -av --chown=$rsync_user:$rsync_user --delete --update $backup_dir $rsync_path/$use_backup_host 2>&1 >> $logf";
} else {
    $cmd = "sudo -u $backup_user rsync -av -e 'ssh -l $rsync_user' $use_rsync_path_sudo --delete --update $backup_dir $rsync_user@$rsync_host:$rsync_path/$use_backup_host 2>&1 >> $logf";
}
backup_rsync_run_cmd( $cmd );
if ( isset( $rsync_add ) && $rsync_add ) {
    if ( $rsync_local ) {
        backup_rsync_local_mkdir( $rsync_add_path, $rsync_user );
        backup_rsync_local_mkdir( "$rsync_add_path/$use_backup_host", $rsync_user );
        $cmd = "sudo rsync -a --no-links --info=skip0 --no-specials --no-devices --chown=$rsync_user:$rsync_user $rsync_add_details $rsync_add_path/$use_backup_host 2>&1 >> $logf";
    } else {
#        $cmd = "runuser -l $backup_user -c \"ssh -o StrictHostKeyChecking=no $rsync_user@$rsync_host -C 'sudo mkdir -p $rsync_add_path'\"";
        $cmd = "runuser -l $backup_user -c \"ssh -o StrictHostKeyChecking=no $rsync_user@$rsync_host -C 'mkdir -p $rsync_add_path'\"";
        backup_rsync_run_cmd( $cmd, false );
#        $cmd = "sudo rsync -az --no-links --info=skip0 --no-specials --no-devices -e 'ssh -i /home/usadmin/.ssh/id_rsa -l $rsync_user' --rsync-path='$sudo_rsync' $rsync_add_details $rsync_user@$rsync_host:$rsync_add_path/$use_backup_host 2>&1 >> $logf";
        $cmd = "sudo rsync -az --no-links --info=skip0 --no-specials --no-devices -e 'ssh -i /home/usadmin/.ssh/id_rsa -l $rsync_user' $use_rsync_path_sudo $rsync_add_details $rsync_user@$rsync_host:$rsync_add_path/$use_backup_host 2>&1 >> $logf";
    }
    backup_rsync_run_cmd( $cmd );
}
backup_rsync_run_cmd( "(echo -n 'rsync finished: ' && date) >> $logf", false );
backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $logf" );

