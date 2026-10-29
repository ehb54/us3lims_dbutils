<?php

# utility

$STDERR = STDERR;

## This host's real IANA zone name (e.g. 'America/Chicago'), for a caller
## that read MySQL's own @@session.time_zone and got back the literal
## string 'SYSTEM' -- which means "whatever this host's OS zone is" and
## resolves differently depending on which host later evaluates it. That
## is harmless for a query that runs and completes on the same host, but
## wrong the moment the string itself is carried somewhere else to be
## interpreted later (stage1 used to record the
## literal word 'SYSTEM' in its export package, so stage2 importing to a
## different time zone applied CONVERT_TZ(..., 'SYSTEM', ...) on ITS OWN
## connection -- the importing host's zone, not the exporting host's that
## actually wrote the rows -- silently shifting by the difference between
## the two, with no error at all).
##
## /etc/localtime is the standard symlink to the real zoneinfo file on
## every Linux distribution this stack targets (both the Debian/Ubuntu and
## RHEL/EL-family dbutils has ever run on); its target's path, relative to
## zoneinfo's own root, IS the IANA name. /etc/timezone (Debian/Ubuntu
## only) is tried first since it is a plain file with nothing to resolve.
## Returns null, not a guess, when neither can be read -- a caller must
## decide for itself whether "SYSTEM, unresolved" is acceptable to still
## record (interactive, same-host use) or a hard stop (exporting to a
## package another host will read).
##
## $etc_timezone/$etc_localtime default to the real paths; a test passes
## its own fixture files/symlinks instead of touching the real host's.
## $zoneinfo_root defaults to the real tree; a test passes its own, for
## the content-comparison fallback below.
##
## Three distinct, common, valid server
## configurations all resolved to null here, forcing stage1 to hard-stop
## on every one of them --
## 1. /etc/localtime as a plain copy of the zoneinfo file, not a symlink
##    (some distributions do this instead of symlinking) -- readlink()
##    simply fails for a non-symlink, falling through to null with no
##    other attempt made.
## 2. /etc/localtime symlinked to .../zoneinfo/Etc/UTC -- a common,
##    explicit way to set UTC -- rejected because timezone_identifiers_
##    list()'s own default group (DateTimeZone::ALL) excludes the "Etc/*"
##    backward-compatibility zones entirely; DateTimeZone::ALL_WITH_BC
##    includes them.
## 3. /etc/localtime symlinked under .../zoneinfo/posix/America/Chicago
##    (the POSIX-rules variant of the same tree, identical data, a
##    distribution choice) -- the extracted name keeps its "posix/"
##    prefix, which is never in the identifiers list on its own.
function resolve_system_timezone( $etc_timezone = '/etc/timezone', $etc_localtime = '/etc/localtime',
                                   $zoneinfo_root = '/usr/share/zoneinfo' ) {
    $known = timezone_identifiers_list( DateTimeZone::ALL_WITH_BC );

    $tzFile = @file_get_contents( $etc_timezone );
    if ( $tzFile !== false ) {
        $tzFile = trim( $tzFile );
        if ( $tzFile !== '' && in_array( $tzFile, $known, true ) ) {
            return $tzFile;
        }
    }

    $link = @readlink( $etc_localtime );
    if ( $link !== false ) {
        $marker = '/zoneinfo/';
        $pos    = strrpos( $link, $marker );
        if ( $pos !== false ) {
            $name = substr( $link, $pos + strlen( $marker ) );
            if ( in_array( $name, $known, true ) ) {
                return $name;
            }
            ## "posix/America/Chicago" and "right/America/Chicago" are the
            ## same zone data under zoneinfo's two rule variants -- strip
            ## the variant directory and retry.
            foreach ( [ 'posix/', 'right/' ] as $variant_prefix ) {
                if ( strpos( $name, $variant_prefix ) === 0 ) {
                    $stripped = substr( $name, strlen( $variant_prefix ) );
                    if ( in_array( $stripped, $known, true ) ) {
                        return $stripped;
                    }
                }
            }
        }
    }

    ## /etc/localtime is a regular file (not a symlink): readlink() above
    ## returned false, and there is no path string to extract a name from
    ## at all. The only way left to identify the zone is by content: every
    ## real zoneinfo file for a given zone is byte-identical across a
    ## distribution, so an exact match against the installed tree is
    ## reliable, not a guess -- not cheap (hundreds of files), but this
    ## runs once per stage1 invocation, never in a hot path.
    if ( is_file( $etc_localtime ) && !is_link( $etc_localtime ) ) {
        $localtime_contents = @file_get_contents( $etc_localtime );
        if ( $localtime_contents !== false && $localtime_contents !== '' ) {
            foreach ( $known as $id ) {
                $candidate = "$zoneinfo_root/$id";
                if ( is_file( $candidate )
                     && @file_get_contents( $candidate ) === $localtime_contents ) {
                    return $id;
                }
            }
        }
    }

    return null;
}

## Appends a PHP statement to a config file's contents, keeping it inside
## the existing PHP block rather than landing it after a closing tag.
## A config.php ending in a
## closing tag is common (every config newinst wrote before mid-2024, plus
## dbinst's own config.php.template) -- blindly appending to EOF lands the
## new statement outside any PHP block, where it prints as literal text
## instead of ever executing, and the variable it was meant to set is never
## actually defined. Shared by every migration that appends a single
## assignment to an existing config file (migrations/4.3.0_common_dir_
## backfill.php and migrations/4.3.0_webinfo_newinst_common_dir_backfill.php),
## so the two can never diverge on this.
function php_append_statement_before_closing_tag( $contents, $statement ) {
    if ( preg_match( '/\?>\s*$/', $contents ) ) {
        return preg_replace( '/\?>\s*$/', $statement . '?>', $contents, 1 );
    }
    return $contents . $statement;
}

function write_logl( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo "{$self}: $msg\n";
    }
}

function timestamp( $msg = "" ) {
    return date( "Y-m-d H:i:s " ) . $msg;
}

function write_logld( $msg, $this_level = 0 ) {
    global $logging_level;
    global $self;
    if ( $logging_level >= $this_level ) {
        echo timestamp() . "{$self}: $msg\n";
    }
}

function db_obj_result( $db_handle, $query, $expectedMultiResult = false, $emptyok = false ) {
    $result = @mysqli_query( $db_handle, $query );

    if ( !$result || ( is_object( $result ) && !$result->num_rows ) ) {
        if ( $result ) {
            # $result->free_result();
        }
        if ( $emptyok ) {
            return false;
        }
        write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
        if ( $result ) {
            debug_json( "query result", $result );
        }
        exit;
    }

    if ( is_object( $result ) && $result->num_rows > 1 && !$expectedMultiResult ) {
        write_logl( "WARNING: db query returned " . $result->num_rows . " rows : $query" );
    }    

    if ( $expectedMultiResult ) {
        return $result;
    } else {
        if ( is_object( $result ) ) {
            return mysqli_fetch_object( $result );
        } else {
            return $result;
        }
    }
}

function debug_json( $msg, $json, $debuglevel = 0 ) {
    global $debug;
    global $STDERR;
    if ( $debuglevel > 0 && $debug < $debuglevel ) {
        return;
    }
    fwrite( $STDERR,  "$msg\n" );
    fwrite( $STDERR, json_encode( $json, JSON_PRETTY_PRINT ) );
    fwrite( $STDERR, "\n" );
}

function echo_json( $msg, $json, $debuglevel = 0 ) {
    echo  "$msg\n";
    echo json_encode( $json, JSON_PRETTY_PRINT );
    echo "\n";
}

function run_cmd( $cmd, $die_if_exit = true, $array_result = false ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    exec( "$cmd 2>&1", $res, $res_code );
    if ( $die_if_exit && $res_code ) {
        error_exit( "shell command '$cmd' returned result:\n" . implode( "\n", $res ) . "\nand with exit status '$res_code'" );
    }
    if ( !$array_result ) {
        return implode( "\n", $res ) . "\n";
    }
    return $res;
}

function error_exit( $msg ) {
    global $STDERR;
    fwrite( $STDERR, "$msg\nTerminating due to errors.\n" );
    exit(-1);
}

## A database/table identifier cannot be bound as a prepared-statement
## placeholder the way a value can, so code that has to interpolate one
## into SQL (SHOW TABLES IN, a dbname.table prefix, ...) checks it against
## this whitelist first instead. Deliberately permissive of what a real
## MySQL identifier allows (a leading digit, for one) and restrictive only
## of what would let the interpolated text escape the identifier position
## it is placed in.
function is_sql_identifier( $value ) {
    return is_string( $value ) && preg_match( '/^[A-Za-z0-9_$]+$/', $value ) === 1;
}

## A cluster_details[...]['localhost'] flag, parsed the same way a human
## reads it, matching common#33's own
## remote_exec::is_local() fix for the identical mistake: a bare PHP bool
## is trusted as-is, but plain `!empty()`/`!` treats the STRING 'false' as
## truthy (PHP only treats an empty string/'0' as falsy), so a config
## written as 'localhost' => 'false' -- an easy mistake, since every other
## boolean-ish setting in the template is a bare true/false, not a quoted
## one -- was silently read as local.
function cluster_entry_is_local( $localhost ) {
    return is_string( $localhost ) ? filter_var( $localhost, FILTER_VALIDATE_BOOLEAN ) : (bool) $localhost;
}

## Fatal, for code with no sensible way to skip and keep going (a CLI
## option value, one explicit target). A per-instance loop over several
## dbinstances should call is_sql_identifier() itself instead and report-
## and-continue, so one malformed name does not stop every instance after it.
function sql_identifier_or_die( $value, $what = 'database name' ) {
    if ( !is_sql_identifier( $value ) ) {
        error_exit( "not a valid $what: " . var_export( $value, true ) );
    }
    return $value;
}

## Run a command, keeping its stderr separate from its stdout. Returns
## [ stdout, stderr, exit status ] so a caller can report why a command
## failed rather than only that it produced nothing.
function capture( $cmd ) {
    $errfile = tempnam( sys_get_temp_dir(), 'us3up' );
    if ( $errfile === false ) {
        error_exit( "could not create a temporary file" );
    }
    $lines = [];
    ## The subshell keeps the redirect over the whole command: "a; b 2>f" would
    ## otherwise redirect only b, letting a's stderr reach the terminal.
    exec( '( ' . $cmd . ' ) 2>' . escapeshellarg( $errfile ), $lines, $rc );
    $err = trim( (string) @file_get_contents( $errfile ) );
    @unlink( $errfile );
    return [ implode( "\n", $lines ), $err, $rc ];
}

## Variables a PHP config file defines, read in a separate process so that an
## old config's own helper functions cannot clash with utility.php's.
## $why is set to the reason when the file cannot be read.
##
## A base+overlay config (dbinst's own contract -- uslims_domain_info.php's
## base_class_dir()/instance_overlay() read it the same way) defines no
## plain variables at all: it returns an array shaped
## [ 'values' => [ 'dbname' => ..., ... ] ]. get_defined_vars() alone sees
## nothing for one, so this also captures the include's own return value
## and falls back to its 'values' when the plain-variable capture came back
## empty -- every other caller's config.php/global_config.php/etc. has no
## top-level return at all, so this fallback never triggers for them.
function config_vars( $file, &$why = null ) {
    $code = 'ob_start(); $__returned = include ' . var_export( $file, true ) . '; ob_end_clean();'
          . ' $v = array_filter( get_defined_vars(), function ( $k ) { return $k[ 0 ] !== "_" && $k !== "GLOBALS"; },'
          . ' ARRAY_FILTER_USE_KEY ); unset( $v["argv"], $v["argc"] );'
          . ' if ( empty( $v ) && is_array( $__returned ) && isset( $__returned[ "values" ] )'
          . '      && is_array( $__returned[ "values" ] ) ) { $v = $__returned[ "values" ]; }'
          . ' echo json_encode( $v, JSON_PARTIAL_OUTPUT_ON_ERROR );';
    list( $out, $err, $rc ) = capture( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $code ) );
    $vars = json_decode( $out, true );
    if ( is_array( $vars ) ) {
        $why = '';
        return $vars;
    }
    ## Distinguish a fatal in the config, a non-zero exit and unparseable output.
    if ( $err !== '' ) {
        $why = $err;
    } elseif ( $rc !== 0 ) {
        $why = "php exited $rc with no message";
    } else {
        $why = 'the config produced no readable variables: ' . json_last_error_msg();
    }
    return null;
}

## Every LIMS instance's database config on this host: single-tenant
## ($wwwpath/uslims3/config.php) and/or multi-tenant ($wwwpath/uslims3/<name>/config.php),
## resolving a migrated instance's generated shim to its real overlay file the
## same way uslims_domain_info.php's instance_overlay() does. Shared by
## uslims_upgrade.php's step 9 and migrate_people_audit_utc.php so the two
## agree on what "every instance on this host" means.
##
## Returns [ label => [ 'dbname'|'dbhost'|'dbusername'|'dbpasswd' => ..., ... ]
## or [ 'error' => string ], ... ]. A per-instance error does not stop the
## others from being reported.
## $paths, when passed by reference, is populated with label => the real
## config file path actually read (the overlay file for a migrated
## instance, the raw config.php otherwise) -- the same resolution this
## function already does internally to read $cfg, exposed so a caller that
## needs to write a missing setting back (uslims_upgrade.php's step 9) can
## target the exact file this function itself would read next time, not
## guess at one of the two possible paths.
##
## $newus3_configs, when passed by reference, is populated with the entries
## this function itself would otherwise silently discard (dbname 'newus3' --
## see the skip below): label => [ 'path' => real config path, 'cfg' =>
## the resolved vars ]. webinfo's and newinst's own host-level config.php
## both resolve here (same glob, same 'newus3' dbname), so a caller that
## needs to reach exactly those two files -- and no others, on any layout --
## does not have to hardcode their paths or guess which glob hits they are;
## it gets exactly what this function already found and was going to throw
## away (nothing previously had a way to reach these two files at all).
function discover_lims_instance_configs( $wwwpath, $us3_home, &$paths = null, &$newus3_configs = null ) {
    $paths = [];
    $newus3_configs = [];
    $instance_configs = [];
    if ( is_file( "$wwwpath/uslims3/config.php" ) ) {
        ## Single-tenant: one instance, directly under uslims3/.
        $instance_configs[ 'default' ] = "$wwwpath/uslims3/config.php";
    }
    foreach ( glob( "$wwwpath/uslims3/*/config.php" ) ?: [] as $instpath ) {
        $instance_configs[ basename( dirname( $instpath ) ) ] = $instpath;
    }

    $resolved = [];
    foreach ( $instance_configs as $label => $instpath ) {
        $contents     = @file_get_contents( $instpath );
        $overlay_path = "$us3_home/lims/etc/config/instances/$label.php";
        $real_path    = ( $contents !== false && strpos( $contents, 'us3_dbinst_config_bootstrap' ) !== false
                           && is_file( $overlay_path ) )
                       ? $overlay_path : $instpath;

        $cfg = config_vars( $real_path, $why );
        if ( $cfg === null
             || !isset( $cfg[ 'dbname' ], $cfg[ 'dbhost' ], $cfg[ 'dbusername' ], $cfg[ 'dbpasswd' ] ) ) {
            $resolved[ $label ] = [ 'error' => "could not read database settings from $real_path"
                                  . ( $why !== null && $why !== '' ? " ($why)" : '' ) ];
            continue;
        }
        ## newus3 is dbutils' own registry database (uslims_dbs.php,
        ## uslims_domain_info.php, uslims_db_stash.php, ... all read/write
        ## newus3.metadata/.people by this same literal name) -- the new-
        ## instance self-service UI's own config (commonly surfaced as a
        ## uslims3/newlims/ or similar docroot) points here, not at any real
        ## tenant's LIMS schema. It has no people_audit table at all, so
        ## every run reported it unreadable/unmigratable instead of simply
        ## not being an instance to begin with, and on a host where it
        ## collides with the single-tenant 'default' entry too, the post-
        ## apply recheck never settled. Skipped outright, not reported as
        ## an error: this is expected, not a problem to flag.
        if ( strcasecmp( $cfg[ 'dbname' ], 'newus3' ) === 0 ) {
            $newus3_configs[ $label ] = [ 'path' => $real_path, 'cfg' => $cfg ];
            continue;
        }
        $resolved[ $label ] = $cfg;
        $paths[ $label ]    = $real_path;
    }
    return $resolved;
}

function echoline( $str = "-", $count = 80, $print = true ) {
    $out = "";
    for ( $i = 0; $i < $count; ++$i ) {
       $out .= $str;
    }
    if ( $print ) {
        echo "$out\n";
    }
    return "$out\n";
}

function headerline( $msg, $str = "=", $count = 80, $print = true ) {
    $out =
        echoline( $str, $count, false )
        . "$msg\n"
        . echoline( $str, $count, false )
        ;
    if ( $print ) {
        echo $out;
    }
    return $out;
}

$warnings       = '';
$warnings_count = 0;
function flush_warnings( $msg = NULL ) {
    global $warnings;
    global $warnings_count;
    if ( strlen( $warnings ) ) {
        echo $warnings;
        $warnings_count += count( explode( "\n", trim( $warnings ) ) );
        echoline();
        $warnings = '';
        return true;
    } else {
        if ( $msg ) {
            echo "$msg\n";
        }
        return false;
    }
}

function warnings_summary( $msg = NULL ) {
    global $warnings_count;
    return $warnings_count ? "Warnings generated $warnings_count\n" : "";
}

$errors = '';
function flush_errors_exit() {
    global $errors;
    if ( strlen( $errors ) ) {
        error_exit( $errors );
    }
}

function get_yn_answer( $question, $quit_if_no = false ) {
    global $STDERR;
    echoline( '=' );
    do {
        $answer = readline( "$question (y or n) : " );
    } while ( $answer != "y" && $answer != "n" );
    if ( $quit_if_no && $answer == "n" ) {
        fwrite( $STDERR, "Terminated by user response.\n" );
        exit(-1);
    }
    return $answer == "y";
}

$util_backup_dir = "";

function backup_dir_init( $dir = "backup" ) {
    global $util_backup_dir;
    ## -pid<pid> too, not just the timestamp: two runs within
    ## the same second (ran: back-to-back --activate invocations) used to
    ## collide on one directory -- mkdir() for the second run failed
    ## silently (no @ here, but nothing checks its return either), and
    ## is_dir() right after still passed because the first run's directory
    ## was already there, so the second run's backup_file() calls silently
    ## shared it. A file both runs backed up then kept only the second run's
    ## copy, overwriting the first run's original -- exactly what a rollback
    ## later needs. Appending the pid makes the directory name unique per
    ## process regardless of timing.
    $util_backup_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) ) . '-pid' . getmypid();
    if ( !mkdir( $util_backup_dir ) || !is_dir( $util_backup_dir ) ) {
        error_exit( "Could not make backup directory $util_backup_dir" );
    }
}

function backup_file( $filename ) {
    global $util_backup_dir;
    if ( !file_exists( $filename ) ) {
        error_exit( "backup_file : $filename does not exist!" );
    }
    if ( !strlen( $util_backup_dir ) ) {
        backup_dir_init();
    }
    run_cmd( "cp $filename $util_backup_dir" );
    echo "Original $filename backed up in to $util_backup_dir\n";
}

$newfile_dir = "";

function newfile_dir_init( $dir = "newfile" ) {
    global $newfile_dir;
    $newfile_dir = "$dir-" . trim( run_cmd( 'date +"%Y%m%d%H%M%S"' ) );
    mkdir( $newfile_dir );
    if ( !is_dir( $newfile_dir ) ) {
        error_exit( "Could not make newfile directory $newfile_dir" );
    }
    return $newfile_dir;
}

function newfile_file( $filename, $contents ) {
    global $newfile_dir;
    if ( !strlen( $newfile_dir ) ) {
        newfile_dir_init();
    }
    $outfile = "$newfile_dir/$filename";
    if ( false === file_put_contents( $outfile, $contents ) ) {
        error_exit( "Could not write $outfile" );
    }
    echo "CREATED: New file $outfile\n";
    return $outfile;
}

function is_admin( $must_be_root = true, $as_user = "" ) {
    $user = posix_getpwuid(posix_geteuid())['name'];
    if ( strlen( $as_user ) ) {
        return $user == $as_user;
    }
        
    if ( $user == 'root' ) {
        return true;
    }
    if ( $must_be_root ) {
        return false;
    }

    $groupInfo = posix_getgrnam('wheel');
    if ($groupInfo === false) {
        return false;
    }

    return in_array( $user, $groupInfo['members'] );
}

$db_handle = NULL;

function check_db( $sleep_seconds = 30 ) {
    global $db_handle;
        
    while ( !mysqli_ping( $db_handle ) ) {
        write_logld( "mysql server has gone away" );
        sleep( $sleep_seconds );
        write_logl( "attempting to reconnect" );
        open_db();
        if ( mysqli_ping( $db_handle ) ) {
            write_logl( "reconnected - success" );
        }            
    }
}

function open_db() {
    global $db_handle;
    global $dbhost;
    global $user;
    global $passwd;
    $db_handle = mysqli_connect( $dbhost, $user, $passwd );
    if ( !$db_handle ) {
        write_logl( "could not connect to mysql: $dbhost, $user. exiting\n" );
        exit(-1);
    }
}    
    
function existing_dbs( $include_global = false ) {
    global $db_handle;
    if ( $db_handle === NULL ) {
        open_db();
    }
    $res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True, True );
    $existing_dbs = [];
    if ( !$res ) {
        if ( mysqli_errno( $db_handle ) ) {
            write_logl( "db query failed : show databases like 'uslims3_%'\ndb query error: " . mysqli_error( $db_handle ) . "\n" );
            exit( 1 );
        }
        return $existing_dbs; # no instance databases visible; not a fatal condition
    }
    while( $row = mysqli_fetch_array($res) ) {
        $this_db = (string)$row[0];
        if ( $this_db != "uslims3_global" || $include_global ) {
            $existing_dbs[] = $this_db;
        }
    }
    return $existing_dbs;
}

function existing_stash_dbs() {
    global $db_handle;
    if ( $db_handle === NULL ) {
        open_db();
    }
    $res = db_obj_result( $db_handle, "show databases like 'stash_%'", true, true );
    $existing_dbs = [];
    if ( $res ) {
        while( $row = mysqli_fetch_array($res) ) {
            $this_db = (string)$row[0];
            $existing_dbs[] = $this_db;
        }
    }
    return $existing_dbs;
}

function boolstr( $val, $truestr = "True", $falsestr = "" ) {
    return $val ? $truestr : $falsestr;
}

function tempdir( $dir = NULL, $prefix = NULL ) {
    $template = "{$prefix}XXXXXX";
    if ( $dir && is_dir($dir) ) {
        $tmpdir = "--tmpdir=$dir";
    } else {
        $tmpdir = '--tmpdir=' . sys_get_temp_dir();
    }
    return exec( "mktemp -d $tmpdir $template" );
}

function file_perms_must_be( $file, $least_restrictive = "600" ) {
    $least_restrictive = octdec( $least_restrictive );
    if ( !file_exists( $file ) ) {
        error_exit( "file permissions check: file '$file' does not exist" );
    }
    $perms = fileperms( $file ) & octdec( "777" );
    $remainder = ( $perms | $least_restrictive ) - $least_restrictive;
    if ( $remainder ) {
        error_exit( sprintf( "Permissions on '$file' are too lenient, fix with:\nchmod %o $file", $least_restrictive ) );
    }
    return;
}

function get_slurm_cores( $cores = 4, $slurmconf = "/etc/slurm/slurm.conf" ) {
    if ( !file_exists( $slurmconf ) ) {
        return $cores;
    }
    $res = run_cmd( 'grep -e "^\s*NodeName\s*=\\s*localhost" ' . $slurmconf, false );
    preg_match( '/Procs=(\d+)\s/', $res, $matches );
    if ( count( $matches ) != 2 ) {
        return $cores;
    }
    
    return intval( $matches[1] * 2 );
}

/**
 * Generates a Universally Unique IDentifier, version 4.
 *
 * RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) defines a special type of Globally
 * Unique IDentifiers (GUID), as well as several methods for producing them. One
 * such method, described in section 4.4, is based on truly random or pseudo-random
 * number generators, and is therefore implementable in a language like PHP.
 *
 * We choose to produce pseudo-random numbers with the Mersenne Twister, and to always
 * limit single generated numbers to 16 bits (ie. the decimal value 65535). That is
 * because, even on 32-bit systems, PHP's RAND_MAX will often be the maximum *signed*
 * value, with only the equivalent of 31 significant bits. Producing two 16-bit random
 * numbers to make up a 32-bit one is less efficient, but guarantees that all 32 bits
 * are random.
 *
 * The algorithm for version 4 UUIDs (ie. those based on random number generators)
 * states that all 128 bits separated into the various fields (32 bits, 16 bits, 16 bits,
 * 8 bits and 8 bits, 48 bits) should be random, except : (a) the version number should
 * be the last 4 bits in the 3rd field, and (b) bits 6 and 7 of the 4th field should
 * be 01. We try to conform to that definition as efficiently as possible, generating
 * smaller values where possible, and minimizing the number of base conversions.
 *
 * @copyright   Copyright (c) CFD Labs, 2006. This function may be used freely for
 *              any purpose ; it is distributed without any form of warranty whatsoever.
 * @author      David Holmes <dholmes@cfdsoftware.net>
 *
 * @return  string  A UUID, made up of 32 hex digits and 4 hyphens.
 */

function uuid() {
   
    // The field names refer to RFC 4122 section 4.1.2

    return sprintf('%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
        mt_rand(0, 65535), mt_rand(0, 65535), // 32 bits for "time_low"
        mt_rand(0, 65535), // 16 bits for "time_mid"
        mt_rand(0, 4095),  // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
        bindec(substr_replace(sprintf('%016b', mt_rand(0, 65535)), '01', 6, 2)),
            // 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
            // (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
            // 8 bits for "clk_seq_low"
        mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535) // 48 bits for "node" 
    ); 
}

function is_locked( $php ) {
    global $lock_dir;
    if ( !isset( $lock_dir ) ) {
        error_exit( "is_locked(): \$lock_dir is not set" );
    }
    $lock_file        = "$lock_dir/" . basename( $php ) . ".lock";
    $expected_cmdline = basename( $php );
    $isstale = false;

    if ( !file_exists($lock_file) ) {
        # echo "file $lock_file does not exist\n";
        return false;
    }

    if ( is_link($lock_file) ) {
        # echo "is_link(" . $lock_file . ") true\n";
        if ( ( $link = readlink( $lock_file ) ) === FALSE ) {
            $isstale = true;
            # echo "is stale 1\n";
        }
    } else {
        $isstale = true;
        # echo "is stale 2\n";
    }
    #    echo "tryLock() 2\n";

    if ( !$isstale && is_dir( $link ) ) {
        # make sure the cmdline exists & matches expected
        $cmdline_file = $link . "/cmdline";
        echo "cmdline_file = $cmdline_file\n";
        if ( ($cmdline = file_get_contents( $cmdline_file )) === FALSE ) {
            # echo "could not get contents of $cmdline_file\n";
            $isstale = true;
            # echo "is stale 3\n";
        } else {
            # remove nulls
            $cmdline = str_replace("\0", "", $cmdline);
            if ( strpos( $cmdline, $expected_cmdline ) === false ) {
                # echo "unexpected contents of $cmdline_file\n";
                $isstale = true;
                # echo "is stale 4 \n";
            }
        }
    }
    #    echo "tryLock() 3\n";

    if (is_link($lock_file) && !is_dir($lock_file)) {
        $isstale = true;
    }
    
    return !$isstale;
}

$dt_store_array = [];

function dt_duration_minutes ( $datetime_start, $datetime_end ) {
    return ($datetime_end->getTimestamp() - $datetime_start->getTimestamp()) / 60;
}

function dt_now () {
    return new DateTime( "now" );
}

function dt_store_now( $name ) {
    global $dt_store_array;
    $dt_store_array[ $name ] = dt_now();
}

function dt_store_get( $name ) {
    global $dt_store_array;
    if ( !array_key_exists( $name, $dt_store_array ) ) {
        error_exit( "dt_store_get() : \$dt_array does not contain key '$name'" );
    }
    return $dt_store_array[ $name ];
}

function dt_store_get_printable( $name ) {
    $dt = dt_store_get( $name );
    return $dt->format( DATE_ATOM );
}

function dt_store_duration( $name_start, $name_end ) {
    return sprintf( "%.2f", dt_duration_minutes( dt_store_get( $name_start ), dt_store_get( $name_end ) ) );
}

function dhms_from_minutes( $time ) {
    $res = '';
    $days  =  floor( $time / (24 * 60) );
    $time  -= $days * 24 * 60;
    $hours =  floor( $time / 60 );
    $time  -= $hours * 60;

    if ( $days ) {
        return sprintf( "%sd %sh %.2fm", $days, $hours, $time );
    }
    if ( $hours ) {
        return sprintf( "%sh %.2fm", $hours, $time );
    }
    return sprintf( "%.2fm", $time );
}

function backup_rsync_email_headers() {
    global $backup_host;
    global $backup_user;
    global $backup_email_address;

    $headers  = 
        "From: backups $backup_host<$backup_user@$backup_host>\r\n"
        . "Reply-To: $backup_email_address\r\n"
        ;

    return $headers;
}

function backup_rsync_failure( $msg ) {
    global $backup_email_reports;
    global $backup_email_address;
    global $backup_host;
    global $backup_logs;
    global $date;
    if ( !$backup_email_reports ) {
        error_exit( $msg );
    }
    $emaillog = "$backup_logs/summary-ERRORS-$date.txt";
    file_put_contents( $emaillog, $msg );
    if ( !mail( 
               $backup_email_address
               ,"BACKUP ERRORS for $backup_host"
               ,$msg
               ,backup_rsync_email_headers()
              )
       ) {
        error_exit( $msg . "\nemail notification also failed" );
    }
    error_exit( $msg );
}
    
function backup_rsync_run_cmd( $cmd, $die_if_exit = true ) {
    global $debug;
    if ( isset( $debug ) && $debug ) {
        echo "$cmd\n";
    }
    exec( "$cmd 2>&1", $res, $res_code );
    if ( $die_if_exit && $res_code ) {
        backup_rsync_failure( "shell command '$cmd' returned result:\n" . implode( "\n", $res ) . "\nand with exit status '$res_code'" );
    }
    return implode( "\n", $res ) . "\n";
}

function debug_echo ( $s, $debuglevel = 1 ) {
    global $debug;
    if ( !$debug || $debug < $debuglevel ) {
        return;
    }
    echo "$s\n";
}
    
function fix_single_quote( $str, $rplc = "" ) {
    return str_replace( "'", $rplc, $str );
}

## squash an object
## credit https://gist.github.com/woganmay/9a98dda059246bca664c

function squash($array, $prefix = '') {
    $flat = array();
    $sep = ".";
    
    if (!is_array($array)) $array = (array)$array;
    
    foreach($array as $key => $value)
    {
        $_key = ltrim($prefix.$sep.$key, ".");
        
        if (is_array($value) || is_object($value))
        {
            // Iterate this one too
            $flat = array_merge($flat, squash($value, $_key));
        }
        else
        {
            $flat[$_key] = $value;
        }
    }

    return $flat;
}

# A 4.2.0 host's people_audit.created_at was written from the column's own
# DEFAULT CURRENT_TIMESTAMP, which is the writing connection's session time
# zone -- not necessarily UTC. 4.3.0's write_audit_row() writes
# UTC_TIMESTAMP() explicitly instead, so any dbinstance that predates that
# fix is still carrying some or all of its people_audit rows in whatever
# local zone the writing host's MySQL session used.
#
# Shared by dbupgrade/stage2_import_dbinsts.php (a 4.2.0 dbinstance
# imported wholesale onto a new host -- every row predates the fix the
# first time this runs) and uslims_upgrade.php's in-place path (the same
# dbinstance, upgraded where it already runs -- some rows may already be
# correct UTC, written by 4.3.0 itself, if the fix has already deployed
# here before this script has). Idempotent via a marker table written
# inside the dbinstance itself (not metadata, not a file) so it is correct
# either way and travels automatically with any future export/import of
# this same dbinstance.
## Tables dbutils writes directly into a dbinstance's own schema as part
## of the people_audit UTC migration, rather than anything us3.sql defines
## or any dbinstance-to-dbinstance export/import is meant to carry a
## structural diff over. Shared here so table_record_counts.php,
## uslims_db_schemas.php and uslims_table_diffs.php all agree on what to
## leave out of a record-count listing, a schema comparison, or an
## --only-extras export filter -- without this, each of those saw a
## migrated dbinstance as carrying an unexpected extra table.
function dbutils_owned_tables() {
    return [ '_dbutils_people_audit_utc_migrated', '_dbutils_people_audit_utc_boundary' ];
}

## Whether $db's people_audit was already migrated by a previous call,
## from either caller. Pulled out of migrate_people_audit_created_at_to_utc()
## so uslims_upgrade.php step 9 and migrate_people_audit_utc.php can decide
## their own 'ok' vs 'todo' reporting without duplicating this query (each
## used to run it a second time, inline, before ever calling the migration
## function at all).
## Content-driven, not marker-driven: the
## separate _dbutils_people_audit_utc_migrated table (still written by
## mark_people_audit_utc_migration_done() below, kept for history) cannot
## survive every path that can legitimately touch people_audit without
## touching it too -- a partial restore of a pre-upgrade backup into an
## already-migrated database overwrites people_audit's rows back to local
## time but, by construction, carries no marker-table data of its own to
## restore alongside them (the backup predates the marker table existing
## at all), so the marker alone kept saying "done" forever after a restore
## like that. Asking people_audit's own created_at_utc flag (set per row,
## at write time, by whichever code wrote it -- see
## ensure_people_audit_utc_flag_column() and dbinst's write_audit_row())
## whether anything is still unconverted is the one signal that travels
## with the data it describes instead of living beside it: a restore that
## reverts the rows reverts (or entirely drops, if the restored table
## predates the column) this flag right along with them.
function people_audit_utc_migration_done( $db_handle, $db ) {
    sql_identifier_or_die( $db );

    $exists = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE 'people_audit'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    if ( !$exists || mysqli_num_rows( $exists ) === 0 ) {
        return true; # nothing to convert, ever, until the table exists
    }

    ## Read-only: --check and a busy host ask this too. Without the flag column no row has been
    ## flagged yet, so every dated row still needs converting.
    $col = mysqli_query( $db_handle, "SELECT COUNT(*) AS n FROM information_schema.COLUMNS"
                        . " WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = 'people_audit' AND COLUMN_NAME = 'created_at_utc'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    $col_row = $col ? mysqli_fetch_assoc( $col ) : null;
    if ( !$col_row ) {
        return false; # can't even see the real signal; don't claim done
    }
    $flag_clause = (int) $col_row[ 'n' ] > 0 ? " AND created_at_utc = 0" : "";

    $res = mysqli_query( $db_handle, "SELECT COUNT(*) AS n FROM $db.people_audit" // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
                        . " WHERE created_at != '0000-00-00 00:00:00'" . $flag_clause );
    $row = $res ? mysqli_fetch_assoc( $res ) : null;
    return $row ? (int) $row[ 'n' ] === 0 : false;
}

## Idempotent and cheap once the column exists (ADD COLUMN IF NOT EXISTS
## is a fast metadata-only check on MariaDB 10.0.2+, well below this
## project's 10.3 target) -- called defensively from both sides that need
## the column to exist before touching it. dbinst's write_audit_row()
## cannot require this file under php-fpm (see create_instance.php's own
## identical note about the marker table) and so duplicates this exact
## statement locally; every writer here calls it too, so neither side has to
## assume the other has already run. Readers check for the column instead.
function ensure_people_audit_utc_flag_column( $db_handle, $db ) {
    sql_identifier_or_die( $db );
    return (bool) mysqli_query( $db_handle, "ALTER TABLE $db.people_audit" // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
                               . " ADD COLUMN IF NOT EXISTS created_at_utc TINYINT(1) NOT NULL DEFAULT 0" );
}

## Pulled out so stage2_import_dbinsts.php can create this table right after
## loading us3.sql into a freshly (re)created dbinstance, BEFORE importing
## that dbinstance's data dump -- see the long comment at the call site for
## why: the dump can contain a row for this table that otherwise has
## nowhere to land.
function create_people_audit_utc_migration_marker_table( $db_handle, $db ) {
    sql_identifier_or_die( $db );
    return mysqli_query( $db_handle, "CREATE TABLE IF NOT EXISTS $db._dbutils_people_audit_utc_migrated (" // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
                                    . " migrated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
                                    . " from_time_zone VARCHAR(64) NOT NULL,"
                                    . " system_time_zone VARCHAR(64) NULL,"
                                    . " max_audit_id INT UNSIGNED NULL )" );
}

## Writes the marker without attempting a conversion, for a dbinstance that
## currently has nothing pre-UTC to convert (no people_audit table yet, or
## one with no rows). Without this, such an instance is indistinguishable
## from one that was never checked at all, so a later run -- once rows have
## since been written here, by 4.3.0's own always-UTC write_audit_row() --
## treats them as still needing conversion and shifts already-correct UTC
## rows. $note travels in the marker's own from_time_zone column (no
## conversion actually used a time zone here, so it is not a real one).
##
## $max_audit_id records the highest people_audit.auditID this call
## actually converted or confirmed already-UTC, for later audit -- null
## when there was none (no table, or an empty one). system_time_zone
## records @@system_time_zone alongside $note's session-level value, since
## a session zone of 'SYSTEM' alone does not say which actual zone that
## resolved to on this server.
##
## @return bool whether the marker row was actually written. Every caller
##              used to ignore this (the INSERT's own result was never even
##              kept) -- one caller's $note
##              was 74 characters into a VARCHAR(64) column, which fails
##              outright under strict sql_mode, so the instance was never
##              actually marked done and kept getting re-examined on every
##              later run, silently. $note is truncated defensively here
##              too (not only fixed at that one call site), so a future
##              caller with the same mistake fails loudly via this return
##              value instead of reintroducing the identical silent miss.
## $create false inside a transaction: CREATE TABLE commits it implicitly.
function mark_people_audit_utc_migration_done( $db_handle, $db, $note, $max_audit_id = null, $create = true ) {
    sql_identifier_or_die( $db );
    if ( $create ) {
        create_people_audit_utc_migration_marker_table( $db_handle, $db );
    }

    $note_esc   = mysqli_real_escape_string( $db_handle, substr( $note, 0, 64 ) );
    $sys_res    = mysqli_query( $db_handle, "SELECT @@system_time_zone AS tz" );
    $sys_row    = $sys_res ? mysqli_fetch_assoc( $sys_res ) : null;
    $sys_tz_sql = ( $sys_row && $sys_row[ 'tz' ] !== '' )
                ? "'" . mysqli_real_escape_string( $db_handle, $sys_row[ 'tz' ] ) . "'"
                : 'NULL';
    $max_id_sql = $max_audit_id === null ? 'NULL' : (int) $max_audit_id;

    return (bool) mysqli_query( $db_handle, "INSERT INTO $db._dbutils_people_audit_utc_migrated" // NOSONAR $db is validated above by sql_identifier_or_die(); $note_esc is mysqli_real_escape_string()'d
                             . " ( from_time_zone, system_time_zone, max_audit_id )"
                             . " VALUES ( '$note_esc', $sys_tz_sql, $max_id_sql )" );
}

## The highest people_audit.auditID that record_people_audit_utc_boundary()
## saw already written the moment it ran, or null if nothing ever recorded
## one here (stage2, migrate_people_audit_utc.php run standalone, or an
## in-place host upgraded before uslims_upgrade.php recorded a boundary at
## all).
##
## Round 15, dbutils#52 blocking: migrate_people_audit_created_at_to_utc()
## no longer uses this value to decide which rows to convert -- it reads
## created_at_utc per row instead, since the boundary is only ever a
## snapshot of "the moment this run happened to start," and a row genuinely
## written by already-upgraded code before that moment (a stale password
## retried, a server left up during the pull, a restore) got an auditID at
## or below it and was wrongly reconverted. migrate_people_audit_utc.php's
## own refuse-without-a-boundary check still reads this directly, and step
## 0 still records and reports it, so it remains live -- just no longer
## load-bearing for which rows get converted.
function people_audit_utc_boundary( $db_handle, $db ) {
    sql_identifier_or_die( $db );
    $has = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE '_dbutils_people_audit_utc_boundary'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    if ( !$has || mysqli_num_rows( $has ) === 0 ) {
        return null;
    }
    $res = mysqli_query( $db_handle, "SELECT auditID FROM $db._dbutils_people_audit_utc_boundary LIMIT 1" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    $row = $res ? mysqli_fetch_assoc( $res ) : null;
    return $row ? (int) $row[ 'auditID' ] : null;
}

## Captures, once, the highest people_audit.auditID that already exists --
## so a later conversion can tell a row dbinst's already-upgraded code
## wrote in UTC (auditID above this) from one still in local time (auditID
## at or below it). Without this, every row written between dbinst's new
## code going live and the conversion actually running gets shifted a
## second time, landing it earlier than rows written before it (dbinst#75,
## confirmed +5h on a live edit). uslims_upgrade.php calls this
## at step 0, before steps 1-8 run, which is the earliest point in a given
## run it can -- narrowing the window to this run's own duration, not
## closing it: the code could have already been live, writing rows, for
## however long before this run started at all. Closing that larger window
## needs dbinst itself to keep writing local time until the instance is
## marked -- dbinst#75's own half of this item, not dbutils's. stage2 and
## migrate_people_audit_utc.php never call this, so neither ever has a
## boundary to apply, and their conversions still cover every row, which
## is correct for both: stage2's rows all predate the fix by definition,
## and the standalone script is for a host that upgraded before this
## existed, with no earlier run of this script to have drawn one.
##
## Idempotent and permanent once set: a later call (a rerun after an
## earlier FAILed step, or an --apply after an earlier --apply) must never
## move the boundary forward, or rows written since the first call --
## already correctly UTC -- would be misclassified as still needing
## conversion. No-ops once the migration itself is done; nothing is left
## to bound.
## Pulled out so stage2_import_dbinsts.php can create this table right
## after loading us3.sql, before importing that dbinstance's data dump --
## the exact same reason create_people_audit_utc_migration_marker_table()
## was already pulled out for the marker table (see the long comment at
## stage2's own call site). Without this, a dbinstance whose export
## already had a boundary row recorded (an in-place host upgraded before
## being exported) has nowhere for that row to land: the INSERT in the
## dump fails against a table that does not exist yet, after every
## dbinstance already imported in this run has already been dropped
## (the same class of abort as the migration-marker table, reintroduced
## for this second bookkeeping table).
function create_people_audit_utc_boundary_table( $db_handle, $db ) {
    sql_identifier_or_die( $db );
    return mysqli_query( $db_handle, "CREATE TABLE IF NOT EXISTS $db._dbutils_people_audit_utc_boundary (" // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
                                    . " auditID INT UNSIGNED NOT NULL )" );
}

## @return bool true only for a real, successful INSERT -- false for the
##              two already-done early-returns (nothing to record) and for
##              a genuine INSERT failure alike, so a caller counting
##              changes (uslims_upgrade.php's run_people_audit_utc_
##              boundaries()) does not count an instance
##              whose boundary was never actually written.
function record_people_audit_utc_boundary( $db_handle, $db ) {
    sql_identifier_or_die( $db );

    if ( people_audit_utc_migration_done( $db_handle, $db ) ) {
        return false;
    }
    if ( people_audit_utc_boundary( $db_handle, $db ) !== null ) {
        return false;
    }

    $exists = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE 'people_audit'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    $max_id = 0;
    if ( $exists && mysqli_num_rows( $exists ) > 0 ) {
        $max_res = mysqli_query( $db_handle, "SELECT COALESCE( MAX( auditID ), 0 ) AS max_id FROM $db.people_audit" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
        $max_row = $max_res ? mysqli_fetch_assoc( $max_res ) : null;
        $max_id  = $max_row ? (int) $max_row[ 'max_id' ] : 0;
    }

    create_people_audit_utc_boundary_table( $db_handle, $db );
    return (bool) mysqli_query( $db_handle, "INSERT INTO $db._dbutils_people_audit_utc_boundary ( auditID )" // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
                             . " VALUES ( $max_id )" );
}

## Adds to a schema freshly loaded from us3.sql every object dbutils or the
## application adds to a running instance, so a data-only dump of that
## instance imports. Both import pipelines call it before loading data.
function prepare_imported_instance_schema( $db_handle, $db ) {
    sql_identifier_or_die( $db );
    return create_people_audit_utc_migration_marker_table( $db_handle, $db )
        && create_people_audit_utc_boundary_table( $db_handle, $db )
        && ensure_people_audit_utc_flag_column( $db_handle, $db );
}

## The tables and columns a data-only dump writes to: [ table => [ column, ... ] ],
## read from --complete-insert INSERT lines and autoincrement ALTER lines.
## Reads .sql, .gz and .xz files.
function dump_insert_columns( array $files ) {
    $readers = [ 'sql' => 'cat', 'gz' => 'gzip -dc', 'xz' => 'xz -dc' ];
    $columns = [];
    foreach ( $files as $file ) {
        $ext = pathinfo( $file, PATHINFO_EXTENSION );
        if ( !isset( $readers[ $ext ] ) || !is_readable( $file ) ) {
            error_exit( "could not read $file" );
        }
        $fh = popen( $readers[ $ext ] . ' ' . escapeshellarg( $file ), 'r' );
        while ( ( $line = fgets( $fh ) ) !== false ) {
            if ( preg_match( '/^INSERT INTO `([^`]+)` \(([^)]*)\) VALUES/', $line, $m ) ) {
                $cols = isset( $columns[ $m[ 1 ] ] ) ? $columns[ $m[ 1 ] ] : [];
                foreach ( explode( ',', $m[ 2 ] ) as $col ) {
                    $cols[ trim( $col, " `" ) ] = true;
                }
                $columns[ $m[ 1 ] ] = $cols;
            } elseif ( preg_match( '/^alter table (?:[^ .]+\.)?([^ ]+) auto_increment/i', $line, $m )
                       && !isset( $columns[ $m[ 1 ] ] ) ) {
                $columns[ $m[ 1 ] ] = [];
            }
        }
        if ( pclose( $fh ) !== 0 ) {
            error_exit( "could not read $file" );
        }
    }
    return array_map( 'array_keys', $columns );
}

## Tables and columns in $insert_columns (from dump_insert_columns()) that $db lacks,
## as "table" or "table.column" strings; empty when the dump imports.
function import_schema_mismatches( $db_handle, $db, array $insert_columns ) {
    sql_identifier_or_die( $db );
    $have = [];
    $res = mysqli_query( $db_handle, "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS"
                         . " WHERE TABLE_SCHEMA = '$db'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    if ( !$res ) {
        error_exit( "could not read the columns of $db: " . mysqli_error( $db_handle ) );
    }
    while ( $row = mysqli_fetch_row( $res ) ) {
        $have[ $row[ 0 ] ][ $row[ 1 ] ] = true;
    }
    $missing = [];
    foreach ( $insert_columns as $table => $cols ) {
        if ( !isset( $have[ $table ] ) ) {
            $missing[] = $table;
            continue;
        }
        foreach ( $cols as $col ) {
            if ( !isset( $have[ $table ][ $col ] ) ) {
                $missing[] = "$table.$col";
            }
        }
    }
    return $missing;
}

## Checks each dump against a scratch schema built the way the import builds
## an instance (us3.sql, then prepare_imported_instance_schema()), before
## anything is dropped. $dumps is [ dbname => [ dump file, autoincrement file ] ];
## $mysql is the mysql command line, without a database name.
## @return string one line per instance that would not import; empty when all fit
function preflight_instance_imports( $db_handle, $mysql, $limsdbpath, array $dumps ) {
    $scratch = "_dbutils_import_preflight";
    if ( !mysqli_query( $db_handle, "DROP DATABASE IF EXISTS $scratch" )
         || !mysqli_query( $db_handle, "CREATE DATABASE $scratch" ) ) {
        error_exit( "could not create $scratch: " . mysqli_error( $db_handle ) );
    }
    run_cmd( "cd $limsdbpath && $mysql $scratch < us3.sql" );
    if ( !prepare_imported_instance_schema( $db_handle, $scratch ) ) {
        error_exit( "could not prepare $scratch: " . mysqli_error( $db_handle ) );
    }
    $errors = "";
    foreach ( $dumps as $db => $files ) {
        $missing = import_schema_mismatches( $db_handle, $scratch, dump_insert_columns( $files ) );
        if ( count( $missing ) ) {
            $errors .= "$db: not in the imported schema: " . implode( ', ', $missing ) . "\n";
        }
    }
    mysqli_query( $db_handle, "DROP DATABASE IF EXISTS $scratch" );
    return $errors;
}

## dbutils_boundary_known_instances_path()/_load()/_save() used to live
## here: a host-level file tracking every dbinstance name step 0 had ever
## drawn a boundary for, so a name missing from it was presumed created
## after the host's first-ever step-0 run.
## Removed: that signal was "is this instance absent
## from every step-0 run before this one", which breaks in both directions
## -- a genuinely pre-existing instance unreachable on every earlier run (a
## stale password, a restored backup) looked exactly like a brand-new one,
## and every instance on a host's first-ever --apply went through the
## boundary path unconditionally, even one created fresh under already-
## current code moments earlier. uslims_upgrade.php now asks
## instance_is_new_under_required_version() instead, which reads the
## instance's own VERSION file directly -- the real answer to "has this
## instance ever run older code", with no dependence on this host's
## step-0 run history at all.

## $writer_tz: the zone the row's own writer actually ran in, when the
## caller knows it and it differs from $db_handle's own session zone --
## stage2 (importing a dbinstance exported from a different host) is the
## one caller that can supply this, read from the export package's own
## per-db timezone file (see dbupgrade/stage1_export_dbinsts.php). null
## (every other caller: uslims_upgrade.php step 9, the standalone script,
## and stage2 itself when the export package predates this field) keeps the
## original behavior of reading $db_handle's own session.time_zone.
##
## @return bool true for a real conversion, or a legitimate no-op already
##              marked done (no people_audit table, or no row with
##              created_at_utc still 0 -- every case a caller should treat
##              as success); false only for a genuine skip that converted
##              nothing and marked nothing, rolled back as one unit (zone
##              tables not loaded, an unresolvable zone identifier, the
##              marker table or flag column could not be created, or the
##              UPDATE itself failed) -- a caller used to have no way to
##              tell this apart from success, so uslims_upgrade.php step 9
##              reported "1 change(s) made" right next to its own WARNING
##              about the same skip.
function migrate_people_audit_created_at_to_utc( $db_handle, $db, $writer_tz = null ) {
    sql_identifier_or_die( $db );

    $exists = mysqli_query( $db_handle, "SHOW TABLES IN $db LIKE 'people_audit'" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    if ( !$exists || mysqli_num_rows( $exists ) === 0 ) {
        # Predates the audit feature entirely; nothing to migrate, but mark
        # it anyway -- if people_audit is added later, every row written
        # from then on is already UTC (4.3.0's write_audit_row()), so there
        # is still nothing a future run should ever convert here.
        echo "$db has no people_audit table; nothing to migrate to UTC\n";
        mark_people_audit_utc_migration_done( $db_handle, $db, 'n/a (no people_audit table)' );
        return true;
    }

    # Content-driven, not timing-driven:
    # people_audit_utc_migration_done() now answers from the data itself
    # (created_at_utc per row), not from a side marker a restore can leave
    # stale, so it is safe to trust here on every call, not just the first.
    if ( people_audit_utc_migration_done( $db_handle, $db ) ) {
        echo "$db.people_audit has no rows still needing UTC conversion; marking done\n";
        mark_people_audit_utc_migration_done( $db_handle, $db, 'n/a (nothing pending)' );
        return true;
    }

    # $writer_tz, when supplied, is the writer's own real zone (see the
    # function-level comment above) and takes priority over $db_handle's
    # session zone -- for stage2, that session zone is only ever the
    # *importing* host's own zone, not necessarily the host that actually
    # wrote the rows. Every other caller
    # (uslims_upgrade.php step 9, the standalone script, and stage2 itself
    # against an export package made before this parameter existed) has no
    # $writer_tz and keeps the original behavior: $db_handle's own session
    # time zone, not a value this function guesses or hardcodes. 'SYSTEM'
    # (the common default) defers to the OS's own tz rules via CONVERT_TZ,
    # so DST in the historical data is still handled correctly without
    # needing mysql.time_zone_* loaded. A row written during the repeated
    # hour when clocks fall back is ambiguous; which occurrence it resolves
    # to depends on the zone and the conversion path (README, dbupgrade).
    if ( $writer_tz !== null && $writer_tz !== '' ) {
        $from_tz = $writer_tz;
    } else {
        $tz_res  = mysqli_query( $db_handle, "SELECT @@session.time_zone AS tz" );
        $tz_row  = $tz_res ? mysqli_fetch_assoc( $tz_res ) : null;
        $from_tz = ( $tz_row && $tz_row[ 'tz' ] !== '' ) ? $tz_row[ 'tz' ] : 'SYSTEM';
    }

    # A named zone (e.g. 'America/Chicago') used to need mysql.time_zone_name
    # loaded (mysql_tzinfo_to_sql) to resolve via CONVERT_TZ(); 'SYSTEM' and a
    # numeric offset (e.g. '+05:00', a valid session.time_zone value on its
    # own, never a named zone) still do not and still convert via CONVERT_TZ
    # below unchanged. The
    # named-zone case used to just warn and skip when the tables were not
    # loaded -- true of every host us3lims-roles builds, so stage1+stage2's
    # named zone (dbupgrade/stage1_export_dbinsts.php's resolve_system_
    # timezone(), always a PHP-recognized IANA identifier, by construction)
    # converted 0 rows there every time. PHP's own tzdata (timezone_
    # identifiers_list(), the same list resolve_system_timezone() validated
    # this zone against) already knows this zone with no dependency on
    # MariaDB's copy at all, so a named zone converts per row in PHP
    # instead, never touching mysql.time_zone_name.
    $is_named_zone = $from_tz !== 'SYSTEM' && !preg_match( '/^[+-]\d{2}:\d{2}$/', $from_tz );

    # Round 15, dbutils#52 blocking: selection is by created_at_utc, not by
    # auditID position -- a row this flag marks 1 (already written in UTC,
    # whenever that happened) is left alone regardless of where it falls
    # relative to any boundary snapshot; a row marked 0 is converted
    # regardless of how it got that way (an original pre-upgrade row, or
    # one a restore put back). See people_audit_utc_migration_done()'s own
    # comment for why this, and not the boundary, is the signal that
    # survives a partial restore.
    $flag_clause = " AND created_at_utc = 0";

    $from_tz_esc = mysqli_real_escape_string( $db_handle, $from_tz );
    echo "Migrating $db.people_audit.created_at from '$from_tz' to UTC (rows not yet flagged created_at_utc)\n";

    # Created (or confirmed) here, before the conversion below, not after:
    # mark_people_audit_utc_migration_done() creates this same table again
    # at the end, but its own result was never checked there either, and an
    # instance user lacking CREATE converted every row and then silently
    # never recorded it -- indistinguishable from a run that never happened
    # at all, so the next run shifted the now-already-UTC rows again.
    # Checked up front instead: if the marker table cannot be created, the
    # conversion is skipped entirely rather than left unrecorded. Low risk
    # in practice (newinst/stage2 grant this account ALL), but a real gap.
    # ensure_people_audit_utc_flag_column() is equally load-bearing here now
    # (the UPDATEs below set it), so a failure there skips the same way.
    if ( !create_people_audit_utc_migration_marker_table( $db_handle, $db )
         || !ensure_people_audit_utc_flag_column( $db_handle, $db ) ) {
        echo "WARNING: $db.people_audit: could not create or confirm the UTC migration"
           . " marker table or flag column (" . mysqli_error( $db_handle ) . "); skipping the"
           . " conversion rather than convert rows this account cannot then mark as done --"
           . " grant CREATE and rerun.\n";
        return false;
    }

    # One transaction for the conversion and its marker row, with no DDL inside it (DDL commits
    # implicitly); the marker table and flag column were created above.
    mysqli_begin_transaction( $db_handle );
    $ok = true;

    if ( $is_named_zone ) {
        # Per-row, in PHP: DateTimeZone already knows this zone (resolve_
        # system_timezone() only ever hands this function a value from
        # timezone_identifiers_list(), the same list DateTimeZone itself
        # uses), so this needs no MariaDB zone tables at all. DST is
        # handled correctly per row (each row's own local instant resolves
        # its own offset), with the same accepted fall-back-hour ambiguity
        # as the CONVERT_TZ path below (README).
        #
        # new DateTimeZone() below used to run outside any try/catch (round
        # 15 should-fix, dbutils#52): an unrecognized zone identifier in a
        # stage2 import package throws ValueError/Exception uncaught, and by
        # then stage2's caller has already dropped and recreated every
        # dbinstance in the import -- every instance after this one in the
        # list is lost over one bad package. Caught the same non-fatal way
        # every other failure in this function already is.
        try {
            $zone = new DateTimeZone( $from_tz );
        } catch ( Throwable $e ) {
            echo "WARNING: $db.people_audit: '$from_tz' is not a resolvable time zone ("
               . $e->getMessage() . "); skipping the conversion -- this dbinstance's data is"
               . " otherwise fully imported.\n";
            mysqli_rollback( $db_handle );
            return false;
        }
        $select_res = mysqli_query( $db_handle, // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
            "SELECT auditID, created_at FROM $db.people_audit"
          . " WHERE created_at != '0000-00-00 00:00:00'" . $flag_clause );
        $rows = [];
        while ( $select_res && ( $row = mysqli_fetch_assoc( $select_res ) ) ) {
            $rows[] = $row;
        }
        $unresolved = 0;
        $utc = new DateTimeZone( 'UTC' );
        foreach ( $rows as $row ) {
            try {
                $local = new DateTime( $row[ 'created_at' ], $zone );
            } catch ( Exception $e ) {
                ## A value CONVERT_TZ() would also have refused (not a real
                ## calendar instant) -- left untouched, same as that path.
                $unresolved++;
                continue;
            }
            $local->setTimezone( $utc );
            $converted = $local->format( 'Y-m-d H:i:s' );
            $update = mysqli_query( $db_handle, // NOSONAR $db is validated above by sql_identifier_or_die(); $converted is a formatted DateTime, not user input
                "UPDATE $db.people_audit SET created_at = '"
                . mysqli_real_escape_string( $db_handle, $converted ) . "', created_at_utc = 1 WHERE auditID = "
                . (int) $row[ 'auditID' ] );
            if ( !$update ) {
                echo "WARNING: $db.people_audit: could not convert created_at to UTC for"
                   . " auditID {$row['auditID']} (" . mysqli_error( $db_handle ) . "); this"
                   . " dbinstance's data is otherwise fully imported -- convert this row by hand.\n";
                $ok = false;
                break;
            }
        }
    } else {
        # 'SYSTEM' and a numeric offset: CONVERT_TZ() resolves both with no
        # MariaDB zone tables loaded, confirmed directly (MariaDB 10.3, no
        # zone tables, STRICT_ALL_TABLES): assigning its NULL to this NOT
        # NULL column raises ERROR 1048 and leaves that row untouched --
        # the WHERE clause below excludes any such row up front instead of
        # letting the UPDATE attempt it at all.
        $cmd = "UPDATE $db.people_audit"
             . " SET created_at = CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' ), created_at_utc = 1"
             . " WHERE created_at != '0000-00-00 00:00:00'"
             . "   AND CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' ) IS NOT NULL"
             . $flag_clause;
        $res = mysqli_query( $db_handle, $cmd );
        if ( !$res ) {
            # Not error_exit(): stage2's caller has already dropped and
            # recreated this (and every other) dbinstance by the time this
            # runs, so aborting the whole import here would leave every
            # dbinstance after this one in the list missing entirely, over a
            # problem confined to one table in one of them. uslims_upgrade.php
            # does not touch other dbinstances at all, so the same non-fatal
            # return is simply the right call there too.
            echo "WARNING: $db.people_audit: could not convert created_at to UTC ("
               . mysqli_error( $db_handle ) . "); this dbinstance's data is otherwise fully"
               . " imported -- convert people_audit by hand once the time zone is resolvable.\n";
            $ok = false;
        } else {
            # Rows excluded above (zero dates, and any CONVERT_TZ() genuinely
            # cannot resolve) are untouched, not corrupted -- still worth a
            # warning so an operator knows to look.
            $unresolved_res = mysqli_query( $db_handle, // NOSONAR $db is validated above by sql_identifier_or_die(); $from_tz_esc is mysqli_real_escape_string()'d
                "SELECT COUNT(*) AS n FROM $db.people_audit WHERE created_at != '0000-00-00 00:00:00'"
              . "   AND CONVERT_TZ( created_at, '$from_tz_esc', '+00:00' ) IS NULL"
              . $flag_clause );
            $unresolved_row = $unresolved_res ? mysqli_fetch_assoc( $unresolved_res ) : null;
            $unresolved = $unresolved_row ? (int) $unresolved_row[ 'n' ] : 0;
        }
    }

    if ( !$ok ) {
        mysqli_rollback( $db_handle );
        return false;
    }

    if ( $unresolved > 0 ) {
        echo "WARNING: $db.people_audit: $unresolved row(s) could not be resolved to UTC from"
           . " '$from_tz' and were left unchanged -- verify by hand.\n";
    }

    $max_res = mysqli_query( $db_handle, "SELECT MAX( auditID ) AS max_id FROM $db.people_audit" ); // NOSONAR $db is validated above by sql_identifier_or_die(), not an untrusted value
    $max_row = $max_res ? mysqli_fetch_assoc( $max_res ) : null;
    $max_audit_id = ( $max_row && $max_row[ 'max_id' ] !== null ) ? (int) $max_row[ 'max_id' ] : null;

    # The marker travels with the dbinstance, so a later run skips it.
    if ( !mark_people_audit_utc_migration_done( $db_handle, $db, $from_tz, $max_audit_id, false ) ) {
        echo "WARNING: $db.people_audit: could not record the UTC migration ("
           . mysqli_error( $db_handle ) . "); rolled back, nothing converted.\n";
        mysqli_rollback( $db_handle );
        return false;
    }
    mysqli_commit( $db_handle );
    return true;
}
