<?php

$self                 = __FILE__;
$hdir                 = __DIR__;

$compresswith         = "gzip -f";
$compressext          = "gz";
$rdiff_bin            = "/bin/rdiff";

$us3bin               = exec( "ls -d ~us3/lims/bin" );
include_once          "$us3bin/listen-config.php";
$rsync_php            = "uslims_daily_rsync.php";

$duration_status_file = "$hdir/.uslims_backup_stats";

# $debug = 1;
date_default_timezone_set('UTC');

$notes = <<<__EOD
usage: $self {config_file}

does importable mysql dumps of every uslims3_% database found
if config_file specified, it will be used instead of ../db config
produces multiple compressed files
my.cnf must exist in the current directory

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

$errors = "";
if ( !isset( $backup_logs ) ) {
    $errors .= "\$backup_logs is not set in $use_config_file\n";
}
if ( !isset( $backup_dir ) ) {
    $errors .= "\$backup_dir is not set in $use_config_file\n";
}
if ( !isset( $backup_count ) ) {
    $errors .= "\$backup_count is not set in $use_config_file\n";
}
if ( !isset( $backup_host ) ) {
    $errors .= "\$backup_host is not set in $use_config_file\n";
}
if ( !isset( $backup_rsync ) ) {
    $errors .= "\$backup_rsync is not set in $use_config_file\n";
} else {
    if ( $backup_rsync ) {
        if ( !isset( $rsync_user ) ) {
            $errors .= "\$rsync_user is not set in $use_config_file\n";
        }
        if ( !isset( $rsync_host ) ) {
            $errors .= "\$rsync_host is not set in $use_config_file\n";
        }
        if ( !isset( $rsync_path ) ) {
            $errors .= "\$rsync_path is not set in $use_config_file\n";
        }
        if ( !isset( $rsync_logs ) ) {
            $errors .= "\$rsync_logs is not set in $use_config_file\n";
        }
    }
}
    
if ( !isset( $backup_user ) ) {
    $errors .= "\$backup_user is not set in $use_config_file\n";
}
if ( !isset( $backup_email_reports ) ) {
    $errors .= "\$backup_email_reports is not set in $use_config_file\n";
} else {
    if ( $backup_email_reports && !isset( $backup_email_address ) ) {
        $errors .= "\$backup_email_address is not set in $use_config_file\n";
    }
}
if ( !isset( $backup_rdiff ) ) {
    $errors .= "\$backup_rdiff is not set in $use_config_file\n";
} else {
    if ( $backup_rdiff && !isset( $backup_rdiff_temp ) ) {
        $errors .= "\$backup_rdiff_temp is not set in $use_config_file\n";
    }
}
if ( isset( $backup_df_check ) && count( $backup_df_check ) ) {
    if ( !isset( $backup_df_pct_warn ) ) {
        $errors .= "\$backup_df_check has contents and \$backup_df_pct_warn is not set in $use_config_file\n";
    }
    $backup_df_run = true;
} else {
    $backup_df_run = false;
}

if ( $backup_rsync &&
     $backup_rdiff ) {
    if ( !file_exists( $rdiff_bin ) ) {
        $errors .= "\$backup_rsync && \$backup_rdiff set but \$rdiff_bin ($rdiff_bin) does not exist\n";
    }
    if ( !is_dir( $backup_rdiff_temp ) ) {
        if ( !mkdir( $backup_rdiff_temp ) ) {
            $errors .= "could not make directory $backup_rdiff_temp\n";
        } else {
            if ( !is_dir( $backup_rdiff_temp ) ) {
                $errors .= "$backup_rdiff_temp is not a directory\n";
            }
        }
    }
}

if ( strlen( $errors ) ) {
    error_exit( $errors );
}

$myconf = "$hdir/my.cnf";
if ( !file_exists( $myconf ) ) {
    error_exit( 
    "create a file '$myconf' in the current directory with the following contents:\n"
    . "[mysqldump]\n"
    . "password=YOUR_ROOT_DB_PASSWORD\n"
    . "max_allowed_packet=256M\n"
    );
}
file_perms_must_be( $myconf );

$date = trim( run_cmd( 'date +"%y%m%d%H"' ) );
dt_store_now( "backup start" );

if ( !is_admin( false ) ) {
    error_exit( "This program must be run by root or a sudo enabled user" );
}

if ( is_locked( $rsync_php ) ) {
    backup_rsync_failure( "$rsync_php is currently running" );
}

// lock
if ( isset( $lock_dir ) ) {
   $lock_main_script_name  = __FILE__;
   require "$us3bin/lock.php";
} 

# df check
$df_report = "";
if ( $backup_df_run ) {
    foreach ( $backup_df_check as $mount ) {
        $res = trim( run_cmd( "df --direct $mount | tail -1 | awk '{ print \$5 }'", false ) );
        if ( $res > $backup_df_pct_warn ) {
            $df_report .= "WARNING: mount $mount is at $res capacity\n";
        }
    }       
}


$dbnames_used = array_fill_keys( existing_dbs(), 1 );
$dbnames_used[ "newus3" ] = 1;
if ( isset( $backup_extra_dbs ) ) {
    if ( !is_array( $backup_extra_dbs ) ) {
        error_exit( "\$backup_extra_dbs is set but is not an array" );
    }
    if ( isset( $backup_extra_dbs_only ) && $backup_extra_dbs_only ) {
        $dbnames_used = $backup_extra_dbs;
    } else {
        $dbnames_used = array_merge( $dbnames_used, $backup_extra_dbs );
    }        
}

echoline( '=' );
echo "found " . count( $dbnames_used ) . " unique dbname records as follows\n";
echoline();
echo implode( "\n", array_keys( $dbnames_used ) );
echo "\n";
echoline( '=' );

# make & change to directory
if ( !is_dir( $backup_dir ) ) {
    mkdir( $backup_dir );
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $backup_dir" );
}

echo "hdir is $hdir\n";

if ( !chdir( $backup_dir ) ) {
    backup_rsync_failure( "Could not change to directory $backup_dir" );
}

$logf = "$backup_logs/$backup_host-$date.log";
if ( !is_dir( $backup_logs ) ) {
    mkdir( $backup_logs, 0700 );
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $backup_logs" );
}

backup_rsync_run_cmd( "echo $backup_host Mysqldump Error Log : `date` > $logf" );

$errors = "";

# get data
echoline( '=' );
echo "exporting data\n";
echoline();

$extra_files = [];
foreach ( $dbnames_used as $db => $val ) {
    $dumpfile = "$db-dump-$date.sql";
    $cmd = "mysqldump --defaults-file=$myconf -u root $db > $dumpfile 2>> $logf";
    echo "starting: exporting $db to $dumpfile\n";
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $dumpfile ) ) {
        backup_rsync_failure( "Error exporting '$dumpfile' terminating" );
    }
    echo "complete: exporting $db to $dumpfile\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $dumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $dumpfile" );

    $dumped[] = $dumpfile;

    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        backup_rsync_failure( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $cdumpfile" );
    $cdumped[] = $cdumpfile;
}

## export all GRANTS
# also dump the grant table in the form of a script to re-establish
# be sure to edit and remove problematic ones, i.e., root
{
    $db = "GRANTS";
    $dumpfile = "uslims3_$db-dump-$date.sql";
    echo "starting: exporting GRANTS to $dumpfile\n";
    $cmd = "(mysql --defaults-file=$myconf -B -N -u root -e \"SELECT DISTINCT CONCAT( 'SHOW GRANTS FOR ''', user, '''@''', host, ''';') AS query FROM mysql.user\" | mysql --defaults-file=$myconf -u root | sed 's/\(GRANT .*\)/\\1;/;s/^\(Grants for .*\)/## \\1 ##/;/##/{x;p;x;}' > $dumpfile) 2>> $logf";
    backup_rsync_run_cmd( $cmd );
    echo "complete: exporting $db to $dumpfile\n";
    $cdumpfile = "$dumpfile.$compressext";
    echo "starting: compressing $dumpfile with $compresswith\n";
    $cmd = "$compresswith $dumpfile";
    backup_rsync_run_cmd( $cmd );
    if ( !file_exists( $cdumpfile ) ) {
        backup_rsync_failure( "Error compressing '$cdumpfile' terminating" );
    }
    echo "completed: compressing $dumpfile with $compresswith\n";
    backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $cdumpfile" );
    backup_rsync_run_cmd( "sudo chmod 400 $cdumpfile" );
    $cdumped[] = $cdumpfile;
}
 
backup_rsync_run_cmd( "sudo chown $backup_user:$backup_user $logf" );
backup_rsync_run_cmd( "sudo chmod 400 $logf" );
# check for old backups
foreach ( $dbnames_used as $db => $val ) {
    $cmd = "ls -1t $db-dump*.sql.$compressext";
    $result = array_slice( explode( "\n", trim( backup_rsync_run_cmd( $cmd ) ) ), $backup_count );

    # debug_json( "result for $db:", $result );
    foreach ( $result as $v ) {
        unlink( $v );
    }
}

# also for grants
{
    $db = "uslims3_GRANTS";
    $cmd = "ls -1t $db-dump*.sql.$compressext";
    $result = array_slice( explode( "\n", trim( backup_rsync_run_cmd( $cmd ) ) ), $backup_count );

    # debug_json( "result for $db:", $result );
    foreach ( $result as $v ) {
        unlink( $v );
    }
}

echoline( '=' );
echo "completed:
backup files are in: $backup_dir
          log is in: $logf\n";
echoline( '=' );
dt_store_now( "backup end" );

# get duration info 
if ( file_exists( $duration_status_file ) ) {
    $duration_info = json_decode( file_get_contents( $duration_status_file ), false );
    if ( !isset( $duration_info->datestring ) ) {
        $duration_info->datestring = [];
    }
} else {
    $duration_info = (object)[];
    $duration_info->backup      = [];
    $duration_info->backup_dt   = [];
    $duration_info->rsync       = [];
    $duration_info->rsync_dt    = [];
    $duration_info->datestring  = [];
}

$rdiff_restore_cmds = [];

if ( $backup_rsync ) {
    if ( $backup_rdiff && 
         count( $duration_info->datestring ) ) {
        $olddate = end( $duration_info->datestring );
        echo "Creating deltas from prior date $olddate\n";
        echoline();
        $rsync_dest = "$rsync_path/$backup_host/sqldumps";

        # compute rdiffs, mv files etc
        # echo "rdiff active old date $olddate\n";

        $remote_restore_cmds_file = "rdiffpatch-$date.sh";
        $remote_restore_cmds      = "cd $rsync_dest\n";

        foreach ( $dbnames_used as $db => $val ) {
            $filebase   = "$db-dump";
            $cdumpfile  = "$filebase-$date.sql.$compressext";
            $pcdumpfile = "$filebase-$olddate.sql.$compressext";
            $file_sig   = "$filebase-$date.sig";
            $file_delta = "$filebase-$date.delta";
            # does a prior backup exist?
            if ( file_exists( $pcdumpfile ) ) {
                echo "computing deltas: $db from $pcdumpfile\n";
                # create signature file in $backup_rdiff_temp
                backup_rsync_run_cmd( "$rdiff_bin signature $pcdumpfile $backup_rdiff_temp/$file_sig && sudo chown $backup_user:$backup_user $backup_rdiff_temp/$file_sig && sudo chmod 400 $backup_rdiff_temp/$file_sig" );
                # create delta file in normal rsync directory
                backup_rsync_run_cmd( "$rdiff_bin delta     $backup_rdiff_temp/$file_sig $cdumpfile $file_delta && sudo chown $backup_user:$backup_user $file_delta && sudo chmod 400 $file_delta" );
                # move backup file to $backup_rdiff_temp
                backup_rsync_run_cmd( "mv -f $cdumpfile $backup_rdiff_temp/" );
                # setup restore commands to be run after rsync
                $remote_restore_cmds .=
                      "rdiff patch $rsync_dest/$pcdumpfile $rsync_dest/$file_delta $rsync_dest/$cdumpfile\n"
                    . "chown $rsync_user:$rsync_user $rsync_dest/$cdumpfile\n"
                    . "chmod 400 $rsync_dest/$cdumpfile\n"
                    . "rm -f $rsync_dest/$file_delta\n"
                    ;
                $rdiff_restore_cmds[] = "mv -f $backup_rdiff_temp/$cdumpfile . && rm -f $file_delta $backup_rdiff_temp/$file_sig";
            } else {
                echo "skipping: $db : prior backup $pcdumpfile not found\n";
            }
        }
        if ( count( $rdiff_restore_cmds ) ) {
            $remote_restore_cmds .= "rm -f $remote_restore_cmds_file\n";
            if ( !file_put_contents( $remote_restore_cmds_file, $remote_restore_cmds ) ) {
                backup_rsync_failure( "could not create \$remote_restore_cmds_file $remote_restore_cmds_file" );
            }
            $rdiff_restore_cmds[] = "rm $remote_restore_cmds_file";
            $rdiff_restore_cmds[] = "ssh $rsync_user@$rsync_host -o StrictHostKeyChecking=no -i ~$rsync_user/.ssh/id_rsa sudo bash $rsync_dest/$remote_restore_cmds_file";
            
        }
    }
    dt_store_now( "rsync start" );
    echo "starting: run $rsync_php\n";
    backup_rsync_run_cmd( "cd $hdir && php $rsync_php $use_config_file" );
    echo "completed: run $rsync_php\n";
    echoline( '=' );
    if ( count( $rdiff_restore_cmds ) ) {
        echo "Applying deltas\n";
        foreach ( $rdiff_restore_cmds as $cmd )  {
            backup_rsync_run_cmd( $cmd );
        }
        echoline( '=' );
    }
    dt_store_now( "rsync end" );
}

# duration status

{

    $duration_info->backup[]     = dt_store_duration( "backup start", "backup end" );
    $duration_info->backup_dt[]  = dt_store_get     ( "backup start" );
    $duration_info->datestring[] = $date;
    if ( $backup_rsync ) {
        $duration_info->rsync[]    = dt_store_duration( "rsync start", "rsync end" );
        $duration_info->rsync_dt[] = dt_store_get     ( "rsync start" );
    }

    file_put_contents( $duration_status_file, json_encode( $duration_info ) );

    # compute statistics
    $duration_stats = [];
    $n = 5;

    $duration_stats[ "count"           ] = count( $duration_info->backup );
    $duration_stats[ "count last"      ] = min( $n, count( $duration_info->backup ) );
    $duration_stats[ "backup min"      ] = min( $duration_info->backup );
    $duration_stats[ "backup max"      ] = max( $duration_info->backup );
    $duration_stats[ "backup avg"      ] = sprintf( "%.2f", array_sum( $duration_info->backup ) / count( $duration_info->backup ) );
    $duration_stats[ "backup min last" ] = min( array_slice( $duration_info->backup, -$n ) );
    $duration_stats[ "backup max last" ] = max( array_slice( $duration_info->backup, -$n ) );
    $duration_stats[ "backup avg last" ] = sprintf( "%.2f", array_sum( array_slice( $duration_info->backup, -$n ) ) / $duration_stats[ "count last" ] );
    $duration_report =
        "Backup Duration report\n"
        . echoline( "-", 80, false )
        . sprintf( 
              "Number of backups registered   %s\n"
            . "\n"
            . "Minimum backup time            %s minutes\n"
            . "Maximum backup time            %s minutes\n"
            . "Average backup time            %s minutes\n"
            ,$duration_stats[ "count"      ]
            ,$duration_stats[ "backup min" ]
            ,$duration_stats[ "backup max" ]
            ,$duration_stats[ "backup avg" ]
            )
        ;

    if ( $duration_stats[ "count" ] > $duration_stats[ "count last" ]  ) {
        $duration_report .=
            sprintf( 
                  "\n"
                . "Minimum backup time [last $n]   %s minutes\n"
                . "Maximum backup time [last $n]   %s minutes\n"
                . "Average backup time [last $n]   %s minutes\n"
                ,$duration_stats[ "backup min last" ]
                ,$duration_stats[ "backup max last" ]
                ,$duration_stats[ "backup avg last" ]
                )
            ;
        }

    if ( $backup_rsync ) {
        $duration_stats[ "rsync min"      ] = min( $duration_info->rsync );
        $duration_stats[ "rsync max"      ] = max( $duration_info->rsync );
        $duration_stats[ "rsync avg"      ] = sprintf( "%.2f", array_sum( $duration_info->rsync ) / count( $duration_info->rsync ) );
        $duration_stats[ "rsync min last" ] = min( array_slice( $duration_info->rsync, -$n ) );
        $duration_stats[ "rsync max last" ] = max( array_slice( $duration_info->rsync, -$n ) );
        $duration_stats[ "rsync avg last" ] = sprintf( "%.2f", array_sum( array_slice( $duration_info->rsync, -$n ) ) / $duration_stats[ "count last" ] );
        $duration_report .=
            sprintf( 
                  "\n"
                . "Minimum rsync time             %s minutes\n"
                . "Maximum rsync time             %s minutes\n"
                . "Average rsync time             %s minutes\n"
                ,$duration_stats[ "rsync min" ]
                ,$duration_stats[ "rsync max" ]
                ,$duration_stats[ "rsync avg" ]
                )
            ;
        if ( $duration_stats[ "count" ] > $duration_stats[ "count last" ]  ) {
            $duration_report .=
                sprintf( 
                      "\n"
                    . "Minimum rsync time [last $n]    %s minutes\n"
                    . "Maximum rsync time [last $n]    %s minutes\n"
                    . "Average rsync time [last $n]    %s minutes\n"
                    ,$duration_stats[ "rsync min last" ]
                    ,$duration_stats[ "rsync max last" ]
                    ,$duration_stats[ "rsync avg last" ]
                    )
                ;
        }
    }
}

$summary_report = 
    sprintf( "Backup summary
%s
Host             %s

Backup start     %s
Backup end       %s
Backup duration  %s minutes

"
    ,echoline( '=', 80, false )
    ,$backup_host
    ,dt_store_get_printable( "backup start" )
    ,dt_store_get_printable( "backup end" )
    ,dt_store_duration( "backup start", "backup end" )
);
if ( $backup_rsync ) {
    $summary_report .=
    sprintf( "Rsync start      %s
Rsync end        %s
Rsync duration   %s minutes

"
    ,dt_store_get_printable( "rsync start" )
    ,dt_store_get_printable( "rsync end" )
    ,dt_store_duration( "rsync start", "rsync end" )
    );
}

# echo $summary_report;

if ( $backup_email_reports ) {
    $summary_report .= 
        echoline( '=', 80, false )
        . "processed " . count( $dbnames_used ) . " unique dbname records as follows\n"
        . echoline( '-', 80, false )
        . implode( "\n", array_keys( $dbnames_used ) )
        .  "\n"
        . echoline( '=', 80, false )
        ;

    $summary_report .= 
        sprintf( "Settings

Backup logs        %s:%s
Backup user        %s
Backup number kept %s

"
            ,$backup_host
            ,$backup_logs
            ,$backup_user
            ,$backup_count

            );
    if ( $backup_rsync ) {
    $summary_report .= 
        sprintf( "Rsync logs         %s:%s
Rsync user         %s
Rsync destination  %s:%s

"
            ,$backup_host
            ,$rsync_logs
            ,$rsync_user
            ,$rsync_host
            ,$rsync_path
            );
    }
    $summary_report .= 
        echoline( '=', 80, false )
        . $duration_report
        . echoline( '=', 80, false )
        ;
    
    $subject = "backup summary for $backup_host";
    if ( strlen( $df_report ) ) {
       $summary_report = $df_report . echoline( "=", 80, false ) . $summary_report;
       $subject = "backup summary DF WARNINGS for $backup_host";
       $emaillog = "$backup_logs/summary-WARNINGS-email-$date.txt";
    } else {
       $emaillog = "$backup_logs/summary-email-$date.txt";
    }

    file_put_contents( $emaillog, echoline( "=", 80, false ) . $subject . "\n" . echoline( "=", 80, false ) . $summary_report );
    run_cmd( "sudo chown $backup_user:$backup_user $emaillog", false );
    run_cmd( "sudo chmod 400 $emaillog", false );
    
    if ( !mail( 
               $backup_email_address
               ,$subject
               ,$summary_report
               ,backup_rsync_email_headers()
              )
       ) {
        error_exit( "email of reports failed" );
    } else {
        echo "reports emailed to $backup_email_address\n";
    }
}
