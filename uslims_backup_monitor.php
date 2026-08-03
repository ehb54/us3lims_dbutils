<?php

# uslims_backup_monitor.php
#
# Transport-agnostic backup-link health monitor with service-style daemon control.
#
# Reads db_config.php (same convention as the other uslims_* tools) and periodically
# probes the network path used by uslims_daily_rsync.php, logging a daily CSV + human
# log so latency / throughput / reconnects can be correlated with time-of-day.
#
# The rsync destination varies by deployment (rsync-over-ssh, or a locally mounted
# CIFS / NFSv3 / NFSv4 / other share); the monitor infers the transport and runs only
# the probes valid for it -- it never assumes ssh / port 22.
#
# commands: start | stop | restart | status | foreground
#
# Minimal footprint: one file, a pidfile + logs, no system install. The 'foreground'
# verb is a drop-in ExecStart for a future systemd unit.
#
# See ehb54/ultrascan-tickets#982.

$self = __FILE__;
$hdir = __DIR__;
$prog = basename( $self );

date_default_timezone_set( 'UTC' );

$notes = <<<__EOD
usage: php $self <command> {options} {db_config_file}

Backup-link health monitor (reads db_config.php like the other uslims_* tools).

commands:
  start        fork a detached background monitor (survives logout), write pidfile
  stop         stop the running monitor (SIGTERM, then SIGKILL after --grace)
  restart      stop then start
  status       report running/stopped, pid, uptime, last sample, csv path
  foreground   run the loop attached (testing today; ExecStart for a future systemd unit)

options:
  --interval S     seconds between samples           (default: \$monitor_interval or 300)
  --count N        run N cycles then exit, 0=forever  (default: 0)
  --mode M         auto|ssh|mount|custom             (default: \$monitor_mode or auto)
  --host H         host/ip to ping/probe             (default: derived from config)
  --throughput MB  enable throughput probe, MB blob   (default: \$monitor_throughput_mb or 0=off)
  --logdir DIR     dir for log + csv + pidfile        (default: \$monitor_logdir or \$rsync_logs)
  --pidfile FILE   pidfile path                       (default: <logdir>/$prog.pid)
  --grace S        stop grace before SIGKILL          (default: 10)
  --alert          email on threshold breach (reuses backup_email_*)
  --no-alert       disable alerts
  --help           this text

With no command, prints this help and exits (no action taken).

__EOD;

require __DIR__ . "/utility.php";

# ---------------------------------------------------------------------------
# argument parsing: <command> {options} {db_config_file}
# ---------------------------------------------------------------------------

$u_argv = $argv;
array_shift( $u_argv );

if ( !count( $u_argv ) || $u_argv[ 0 ] === '--help' || $u_argv[ 0 ] === '-h' ) {
    echo $notes;
    exit;
}

$command       = array_shift( $u_argv );
$valid_command = [ 'start', 'stop', 'restart', 'status', 'foreground' ];
if ( !in_array( $command, $valid_command, true ) ) {
    error_exit( "unknown command '$command'\n\n$notes" );
}

# null == not supplied on the command line (so config/default wins)
$opt = [
    'interval'   => null,
    'count'      => null,
    'mode'       => null,
    'host'       => null,
    'throughput' => null,
    'logdir'     => null,
    'pidfile'    => null,
    'grace'      => null,
    'alert'      => null,
];

while ( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) === '-' ) {
    switch ( $arg = $u_argv[ 0 ] ) {
        case '--interval':   $opt[ 'interval' ]   = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--count':      $opt[ 'count' ]      = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--mode':       $opt[ 'mode' ]       = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--host':       $opt[ 'host' ]       = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--throughput': $opt[ 'throughput' ] = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--logdir':     $opt[ 'logdir' ]     = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--pidfile':    $opt[ 'pidfile' ]    = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--grace':      $opt[ 'grace' ]      = mon_req_val( $u_argv, $arg, $notes ); break;
        case '--alert':      array_shift( $u_argv ); $opt[ 'alert' ] = true;  break;
        case '--no-alert':   array_shift( $u_argv ); $opt[ 'alert' ] = false; break;
        case '--help':       echo $notes; exit;
        default:             error_exit( "unknown option '$arg'\n\n$notes" );
    }
}

$config_file = "$hdir/db_config.php";
if ( count( $u_argv ) ) {
    $config_file = array_shift( $u_argv );
}
if ( count( $u_argv ) ) {
    error_exit( "unexpected extra arguments: " . implode( ' ', $u_argv ) . "\n\n$notes" );
}
if ( !file_exists( $config_file ) ) {
    error_exit( "config file '$config_file' does not exist" );
}
file_perms_must_be( $config_file );
require $config_file;

# ---------------------------------------------------------------------------
# resolve effective settings: built-in default <- $monitor_* config <- CLI
# ---------------------------------------------------------------------------

$M = [];
$M[ 'self' ]       = $self;
$M[ 'prog' ]       = $prog;
$M[ 'interval' ]   = max( 5, (int)( $opt[ 'interval' ]   !== null ? $opt[ 'interval' ]   : mon_cfg( 'monitor_interval', 300 ) ) );
$M[ 'count' ]      = (int)(       $opt[ 'count' ]      !== null ? $opt[ 'count' ]      : 0 );
$M[ 'mode' ]       =              $opt[ 'mode' ]       !== null ? $opt[ 'mode' ]       : mon_cfg( 'monitor_mode', 'auto' );
$M[ 'host' ]       =              $opt[ 'host' ]       !== null ? $opt[ 'host' ]       : mon_cfg( 'monitor_host', '' );
$M[ 'throughput' ] = (int)(       $opt[ 'throughput' ] !== null ? $opt[ 'throughput' ] : mon_cfg( 'monitor_throughput_mb', 0 ) );
$M[ 'grace' ]      = max( 1, (int)( $opt[ 'grace' ]     !== null ? $opt[ 'grace' ]      : 10 ) );
$M[ 'alert' ]      = (bool)(      $opt[ 'alert' ]      !== null ? $opt[ 'alert' ]      : mon_cfg( 'monitor_alert', false ) );
$M[ 'logdir' ]     =              $opt[ 'logdir' ]     !== null ? $opt[ 'logdir' ]     : mon_cfg( 'monitor_logdir', mon_cfg( 'rsync_logs', "$hdir/log" ) );
$M[ 'logdir' ]     = rtrim( $M[ 'logdir' ], '/' );
$M[ 'pidfile' ]    =              $opt[ 'pidfile' ]    !== null ? $opt[ 'pidfile' ]    : $M[ 'logdir' ] . "/$prog.pid";
$M[ 'keep' ]       = max( 1, (int) mon_cfg( 'monitor_keep', 30 ) );
$M[ 'tcp_ports' ]  = (array) mon_cfg( 'monitor_tcp_ports', [] );
$M[ 'rpcinfo' ]    = mon_cfg( 'monitor_rpcinfo', 'auto' );
$M[ 'ports_off' ]  = (bool) mon_cfg( 'monitor_ports_off', false );
$M[ 'checks' ]     = (array) mon_cfg( 'monitor_checks', [] );
$M[ 'ssh_port' ]   = (int) mon_cfg( 'monitor_ssh_port', 22 );
$M[ 'ssh_opts' ]   = mon_cfg( 'monitor_ssh_opts', '' );
$M[ 'mount' ]      = mon_cfg( 'monitor_mount', '' );

# ---------------------------------------------------------------------------
# dispatch
# ---------------------------------------------------------------------------

switch ( $command ) {
    case 'status':     exit( mon_do_status( $M ) );
    case 'stop':       exit( mon_do_stop( $M ) );
    case 'start':      exit( mon_do_start( $M, $config_file ) );
    case 'restart':    mon_do_stop( $M ); usleep( 300000 ); exit( mon_do_start( $M, $config_file ) );
    case 'foreground': exit( mon_do_foreground( $M ) );
}
exit( 0 );

# ===========================================================================
# small helpers
# ===========================================================================

function mon_req_val( &$argv, $arg, $notes ) {
    array_shift( $argv );
    if ( !count( $argv ) ) {
        error_exit( "option '$arg' requires an argument\n\n$notes" );
    }
    return array_shift( $argv );
}

# read a global config var with a fallback default
function mon_cfg( $name, $default ) {
    return isset( $GLOBALS[ $name ] ) ? $GLOBALS[ $name ] : $default;
}

# run a shell command, capture combined output + exit code
function mon_sh( $cmd, &$code = null ) {
    $out  = [];
    $code = 0;
    exec( "$cmd 2>&1", $out, $code );
    return implode( "\n", $out );
}

function mon_have_cmd( $c ) {
    static $cache = [];
    if ( isset( $cache[ $c ] ) ) {
        return $cache[ $c ];
    }
    $p = trim( mon_sh( "command -v " . escapeshellarg( $c ) ) );
    return $cache[ $c ] = ( strlen( $p ) > 0 );
}

function mon_ensure_dir( $d ) {
    if ( !is_dir( $d ) ) {
        if ( !@mkdir( $d, 0700, true ) && !is_dir( $d ) ) {
            error_exit( "could not create logdir '$d'" );
        }
    }
}

function mon_csv_num( $v ) { return $v === null ? '' : $v; }
function mon_csv_bool( $v ) { return $v === null ? '' : ( $v ? '1' : '0' ); }

# ===========================================================================
# pidfile / process helpers
# ===========================================================================

function mon_read_pidfile( $pidfile ) {
    if ( !file_exists( $pidfile ) ) {
        return 0;
    }
    $pid = (int) trim( (string) @file_get_contents( $pidfile ) );
    return $pid > 0 ? $pid : 0;
}

function mon_proc_alive( $pid ) {
    if ( $pid <= 0 ) {
        return false;
    }
    if ( function_exists( 'posix_kill' ) ) {
        return @posix_kill( $pid, 0 );
    }
    return is_dir( "/proc/$pid" );
}

# confirm the pid is actually one of our monitors (avoid killing a recycled pid)
function mon_pid_is_ours( $pid, $prog ) {
    if ( $pid <= 0 ) {
        return false;
    }
    $cmdline_file = "/proc/$pid/cmdline";
    if ( file_exists( $cmdline_file ) ) {
        $cmd = str_replace( "\0", " ", (string) @file_get_contents( $cmdline_file ) );
        return strpos( $cmd, $prog ) !== false && strpos( $cmd, 'foreground' ) !== false;
    }
    # no /proc: best-effort via ps, else just liveness
    if ( mon_have_cmd( 'ps' ) ) {
        $out = mon_sh( "ps -p $pid -o args=" );
        return strpos( $out, $prog ) !== false;
    }
    return mon_proc_alive( $pid );
}

function mon_running_pid( $M ) {
    $pid = mon_read_pidfile( $M[ 'pidfile' ] );
    if ( $pid && mon_proc_alive( $pid ) && mon_pid_is_ours( $pid, $M[ 'prog' ] ) ) {
        return $pid;
    }
    return 0;
}

function mon_send_signal( $pid, $sig ) {
    $map = [
        'TERM' => defined( 'SIGTERM' ) ? SIGTERM : 15,
        'KILL' => defined( 'SIGKILL' ) ? SIGKILL : 9,
        'INT'  => defined( 'SIGINT' )  ? SIGINT  : 2,
    ];
    $n = isset( $map[ $sig ] ) ? $map[ $sig ] : 15;
    if ( function_exists( 'posix_kill' ) ) {
        return @posix_kill( $pid, $n );
    }
    mon_sh( "kill -s $sig $pid" );
    return true;
}

# ===========================================================================
# commands
# ===========================================================================

function mon_do_status( $M ) {
    $pid = mon_running_pid( $M );
    if ( $pid ) {
        $since = @filemtime( $M[ 'pidfile' ] );
        $csv   = mon_current_csv_path( $M );
        $last  = mon_last_csv_time( $csv );
        echo "running (pid $pid)" . ( $since ? " since " . gmdate( 'Y-m-d H:i:s', $since ) . " UTC" : "" ) . "\n";
        echo "  pidfile: {$M[ 'pidfile' ]}\n";
        echo "  csv:     $csv" . ( strlen( $last ) ? " (last sample $last UTC)" : " (no samples yet)" ) . "\n";
        return 0;
    }
    $raw = mon_read_pidfile( $M[ 'pidfile' ] );
    if ( $raw ) {
        echo "stopped (stale pidfile {$M[ 'pidfile' ]}: pid $raw not running)\n";
    } else {
        echo "stopped\n";
    }
    return 3; # LSB: program is not running
}

function mon_do_stop( $M ) {
    $pid = mon_running_pid( $M );
    if ( !$pid ) {
        if ( mon_read_pidfile( $M[ 'pidfile' ] ) ) {
            @unlink( $M[ 'pidfile' ] ); # clean stale
        }
        echo "not running\n";
        return 0;
    }
    echo "stopping (pid $pid) ...\n";
    mon_send_signal( $pid, 'TERM' );
    $deadline = microtime( true ) + $M[ 'grace' ];
    while ( microtime( true ) < $deadline ) {
        if ( !mon_proc_alive( $pid ) ) {
            break;
        }
        usleep( 200000 );
    }
    if ( mon_proc_alive( $pid ) ) {
        echo "  did not stop within {$M[ 'grace' ]}s; sending SIGKILL\n";
        mon_send_signal( $pid, 'KILL' );
        usleep( 400000 );
    }
    if ( mon_proc_alive( $pid ) ) {
        error_exit( "failed to stop pid $pid" );
    }
    if ( file_exists( $M[ 'pidfile' ] ) ) {
        @unlink( $M[ 'pidfile' ] );
    }
    echo "stopped\n";
    return 0;
}

function mon_do_start( $M, $config_file ) {
    mon_ensure_dir( $M[ 'logdir' ] );

    $pid = mon_running_pid( $M );
    if ( $pid ) {
        echo "already running (pid $pid)\n";
        return 0;
    }
    if ( mon_read_pidfile( $M[ 'pidfile' ] ) ) {
        @unlink( $M[ 'pidfile' ] ); # stale
    }

    $bootlog  = $M[ 'logdir' ] . "/{$M[ 'prog' ]}.boot.log";
    $php      = ( defined( 'PHP_BINARY' ) && PHP_BINARY ) ? PHP_BINARY : 'php';
    $child    = escapeshellarg( $php ) . ' ' . escapeshellarg( $M[ 'self' ] )
              . ' foreground ' . mon_build_fg_args( $M, $config_file );

    # detach so the monitor survives an ssh logout
    $launcher = mon_have_cmd( 'setsid' ) ? 'setsid ' : ( mon_have_cmd( 'nohup' ) ? 'nohup ' : '' );
    $full     = $launcher . $child . ' < /dev/null >> ' . escapeshellarg( $bootlog ) . ' 2>&1 &';
    exec( $full );

    # the worker writes its own pidfile on startup; poll up to 5s to confirm
    for ( $i = 0; $i < 50; $i++ ) {
        usleep( 100000 );
        $p = mon_running_pid( $M );
        if ( $p ) {
            echo "started (pid $p)\n";
            echo "  logdir: {$M[ 'logdir' ]}\n";
            echo "  csv:    " . mon_current_csv_path( $M ) . "\n";
            return 0;
        }
    }
    echo "failed to start within 5s; see $bootlog\n";
    return 1;
}

# reconstruct the resolved settings as explicit foreground options so the
# detached child derives identical behavior
function mon_build_fg_args( $M, $config_file ) {
    $a   = [];
    $a[] = '--interval '   . escapeshellarg( $M[ 'interval' ] );
    $a[] = '--count '      . escapeshellarg( $M[ 'count' ] );
    $a[] = '--mode '       . escapeshellarg( $M[ 'mode' ] );
    if ( strlen( $M[ 'host' ] ) ) {
        $a[] = '--host '   . escapeshellarg( $M[ 'host' ] );
    }
    $a[] = '--throughput ' . escapeshellarg( $M[ 'throughput' ] );
    $a[] = '--logdir '     . escapeshellarg( $M[ 'logdir' ] );
    $a[] = '--pidfile '    . escapeshellarg( $M[ 'pidfile' ] );
    $a[] = '--grace '      . escapeshellarg( $M[ 'grace' ] );
    $a[] = $M[ 'alert' ] ? '--alert' : '--no-alert';
    $a[] = escapeshellarg( $config_file );
    return implode( ' ', $a );
}

function mon_do_foreground( $M ) {
    mon_ensure_dir( $M[ 'logdir' ] );

    $existing = mon_running_pid( $M );
    if ( $existing && $existing != getmypid() ) {
        error_exit( "monitor already running (pid $existing)" );
    }
    file_put_contents( $M[ 'pidfile' ], getmypid() . "\n" );

    $GLOBALS[ 'mon_stop' ] = false;
    if ( function_exists( 'pcntl_async_signals' ) ) {
        pcntl_async_signals( true );
        pcntl_signal( SIGTERM, 'mon_signal' );
        pcntl_signal( SIGINT,  'mon_signal' );
        pcntl_signal( SIGHUP,  SIG_IGN );
    }
    $pidfile = $M[ 'pidfile' ];
    register_shutdown_function( function () use ( $pidfile ) {
        if ( mon_read_pidfile( $pidfile ) === getmypid() ) {
            @unlink( $pidfile );
        }
    } );

    mon_log_line( $M, "monitor started (pid " . getmypid() . ", interval {$M[ 'interval' ]}s, mode {$M[ 'mode' ]})" );

    $cycle = 0;
    while ( empty( $GLOBALS[ 'mon_stop' ] ) ) {
        $sample = mon_run_probes( $M );
        mon_write_csv( $M, $sample );
        mon_log_line( $M, mon_format_sample( $sample ) );
        mon_maybe_alert( $M, $sample );
        mon_prune_old_logs( $M );

        $cycle++;
        if ( $M[ 'count' ] > 0 && $cycle >= $M[ 'count' ] ) {
            break;
        }
        mon_interruptible_sleep( $M[ 'interval' ] );
    }

    mon_log_line( $M, "monitor stopping (pid " . getmypid() . ", cycles $cycle)" );
    if ( mon_read_pidfile( $M[ 'pidfile' ] ) === getmypid() ) {
        @unlink( $M[ 'pidfile' ] );
    }
    return 0;
}

function mon_signal( $signo ) {
    $GLOBALS[ 'mon_stop' ] = true;
}

function mon_interruptible_sleep( $secs ) {
    $secs = max( 1, (int) $secs );
    for ( $i = 0; $i < $secs; $i++ ) {
        if ( !empty( $GLOBALS[ 'mon_stop' ] ) ) {
            return;
        }
        sleep( 1 );
    }
}

# ===========================================================================
# transport resolution
# ===========================================================================

function mon_resolve_transport( $M ) {
    $rsync_host = (string) mon_cfg( 'rsync_host', '' );
    $rsync_user = (string) mon_cfg( 'rsync_user', mon_cfg( 'backup_user', '' ) );
    $rsync_path = (string) mon_cfg( 'rsync_path', '' );
    $mount      = (string) $M[ 'mount' ];

    $mode = $M[ 'mode' ];
    if ( $mode === 'auto' ) {
        if ( strlen( $mount ) ) {
            $mode = 'mount';
        } else if ( strlen( $rsync_host ) ) {
            $mode = 'ssh';
        } else {
            $mode = 'mount';
        }
    }

    $t = [
        'mode'        => $mode,
        'host'        => '',
        'fs'          => '',
        'mount'       => $mount,
        'remote_path' => '',
        'ssh_user'    => '',
        'ssh_port'    => $M[ 'ssh_port' ],
        'ssh_opts'    => $M[ 'ssh_opts' ],
        'server'      => '',
    ];

    if ( $mode === 'ssh' ) {
        $t[ 'host' ]        = strlen( $M[ 'host' ] ) ? $M[ 'host' ] : $rsync_host;
        $t[ 'ssh_user' ]    = $rsync_user;
        $t[ 'remote_path' ] = $rsync_path;
        $t[ 'server' ]      = $t[ 'host' ];
    } else if ( $mode === 'mount' ) {
        if ( strlen( $mount ) ) {
            $mi            = mon_mount_info( $mount );
            $t[ 'fs' ]     = $mi[ 'fs' ];
            $t[ 'server' ] = mon_parse_server( $mi[ 'src' ], $mi[ 'fs' ] );
        }
        $t[ 'host' ] = strlen( $M[ 'host' ] ) ? $M[ 'host' ] : $t[ 'server' ];
    } else { # custom: explicit host + ports (+ mount if given)
        $t[ 'host' ] = $M[ 'host' ];
        if ( strlen( $mount ) ) {
            $mi            = mon_mount_info( $mount );
            $t[ 'fs' ]     = $mi[ 'fs' ];
            $t[ 'server' ] = mon_parse_server( $mi[ 'src' ], $mi[ 'fs' ] );
        }
    }
    return $t;
}

function mon_mount_info( $mp ) {
    $res = [ 'fs' => '', 'src' => '' ];
    if ( mon_have_cmd( 'findmnt' ) ) {
        $out = trim( mon_sh( "findmnt -nro FSTYPE,SOURCE --target " . escapeshellarg( $mp ) ) );
        if ( strlen( $out ) ) {
            $parts        = preg_split( '/\s+/', $out, 2 );
            $res[ 'fs' ]  = isset( $parts[ 0 ] ) ? $parts[ 0 ] : '';
            $res[ 'src' ] = isset( $parts[ 1 ] ) ? $parts[ 1 ] : '';
            return $res;
        }
    }
    # fallback: longest matching mountpoint in /proc/mounts
    $lines = @file( '/proc/mounts', FILE_IGNORE_NEW_LINES );
    if ( is_array( $lines ) ) {
        $best = '';
        foreach ( $lines as $line ) {
            $f = preg_split( '/\s+/', $line );
            if ( count( $f ) < 3 ) {
                continue;
            }
            $mnt = stripcslashes( $f[ 1 ] );
            if ( rtrim( $mp, '/' ) === rtrim( $mnt, '/' ) && strlen( $mnt ) >= strlen( $best ) ) {
                $best         = $mnt;
                $res[ 'fs' ]  = $f[ 2 ];
                $res[ 'src' ] = $f[ 0 ];
            }
        }
    }
    return $res;
}

function mon_parse_server( $src, $fs ) {
    if ( $fs === 'cifs' ) {
        if ( preg_match( '#^//([^/]+)/#', $src, $m ) ) {
            return $m[ 1 ];
        }
    }
    if ( strpos( (string) $fs, 'nfs' ) === 0 ) {
        if ( preg_match( '#^([^:/]+):/#', $src, $m ) ) {
            return $m[ 1 ];
        }
    }
    return '';
}

function mon_default_ports( $fs ) {
    switch ( $fs ) {
        case 'cifs':  return [ 445 ];       # 139 (NetBIOS) is legacy: add via $monitor_tcp_ports
        case 'nfs4':  return [ 2049 ];      # v4: no rpcbind/mountd
        case 'nfs':   return [ 111, 2049 ]; # v3: rpcbind + nfs (mountd/lockd checked via rpcinfo)
        case 'iscsi': return [ 3260 ];
        default:      return [];
    }
}

# ===========================================================================
# probes
# ===========================================================================

function mon_check_enabled( $M, $name ) {
    if ( is_array( $M[ 'checks' ] ) && count( $M[ 'checks' ] ) ) {
        return in_array( $name, $M[ 'checks' ], true );
    }
    return true; # default: run all applicable probes
}

function mon_ping( $host, $count = 5 ) {
    $r = [ 'loss' => null, 'avg' => null, 'max' => null, 'jitter' => null, 'ok' => false ];
    if ( !strlen( $host ) ) {
        return $r;
    }
    $count    = (int) $count;
    $deadline = $count + 5;
    $out      = mon_sh( "ping -n -c $count -w $deadline " . escapeshellarg( $host ) );
    if ( preg_match( '/(\d+(?:\.\d+)?)%\s*packet loss/', $out, $m ) ) {
        $r[ 'loss' ] = (float) $m[ 1 ];
    }
    if ( preg_match( '#=\s*([\d.]+)/([\d.]+)/([\d.]+)/([\d.]+)\s*ms#', $out, $m ) ) {
        $r[ 'avg' ]    = (float) $m[ 2 ];
        $r[ 'max' ]    = (float) $m[ 3 ];
        $r[ 'jitter' ] = (float) $m[ 4 ];
    }
    $r[ 'ok' ] = ( $r[ 'loss' ] !== null && $r[ 'loss' ] < 100.0 );
    return $r;
}

function mon_tcp( $host, $port, $timeout = 5 ) {
    if ( !strlen( $host ) ) {
        return [ 'ok' => false, 'ms' => null, 'err' => 'no host' ];
    }
    $errno = 0;
    $errstr = '';
    $start = microtime( true );
    $fp = @stream_socket_client( "tcp://$host:$port", $errno, $errstr, $timeout );
    if ( $fp === false ) {
        return [ 'ok' => false, 'ms' => null, 'err' => trim( "$errno $errstr" ) ];
    }
    $ms = ( microtime( true ) - $start ) * 1000.0;
    fclose( $fp );
    return [ 'ok' => true, 'ms' => round( $ms, 2 ), 'err' => '' ];
}

function mon_ssh_rtt( $t, $timeout = 10 ) {
    if ( !mon_have_cmd( 'ssh' ) ) {
        return [ 'ok' => false, 'ms' => null, 'err' => 'ssh not found' ];
    }
    $target = strlen( $t[ 'ssh_user' ] ) ? "{$t[ 'ssh_user' ]}@{$t[ 'host' ]}" : $t[ 'host' ];
    $opts   = "-o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=$timeout -p {$t[ 'ssh_port' ]} {$t[ 'ssh_opts' ]}";
    $start  = microtime( true );
    $out    = mon_sh( "ssh $opts " . escapeshellarg( $target ) . " true", $code );
    $ms     = ( microtime( true ) - $start ) * 1000.0;
    return [ 'ok' => ( $code === 0 ), 'ms' => round( $ms, 2 ), 'err' => ( $code === 0 ? '' : trim( $out ) ) ];
}

# ssh in, detect the remote backup fs, pull cifs reconnects + timed df
function mon_remote_fs( $t ) {
    $r = [ 'fs' => '', 'reconnects' => null, 'df_ms' => null, 'avail_kb' => null, 'ok' => false, 'err' => '' ];
    if ( !mon_have_cmd( 'ssh' ) || !strlen( $t[ 'remote_path' ] ) ) {
        $r[ 'err' ] = 'no ssh/remote_path';
        return $r;
    }
    $target = strlen( $t[ 'ssh_user' ] ) ? "{$t[ 'ssh_user' ]}@{$t[ 'host' ]}" : $t[ 'host' ];
    $opts   = "-o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p {$t[ 'ssh_port' ]} {$t[ 'ssh_opts' ]}";
    $rp     = escapeshellarg( $t[ 'remote_path' ] );
    $remote =
          'p=' . $rp . '; '
        . 'fs=$(findmnt -nro FSTYPE --target "$p" 2>/dev/null || stat -f -c %T "$p" 2>/dev/null); echo "FS=$fs"; '
        . 'rc=$(grep -i reconnect /proc/fs/cifs/Stats 2>/dev/null | grep -oE "[0-9]+" | head -1); echo "RC=$rc"; '
        . 't0=$(date +%s.%N); a=$(df -Pk "$p" 2>/dev/null | awk "NR==2{print \\$4}"); t1=$(date +%s.%N); '
        . 'echo "DFMS=$(awk "BEGIN{printf \\"%.1f\\",($t1-$t0)*1000}")"; echo "AVAIL=$a"';
    $out = mon_sh( "ssh $opts " . escapeshellarg( $target ) . ' ' . escapeshellarg( $remote ), $code );
    if ( $code !== 0 ) {
        $r[ 'err' ] = trim( $out );
        return $r;
    }
    if ( preg_match( '/FS=(\S+)/',      $out, $m ) ) { $r[ 'fs' ]         = $m[ 1 ]; }
    if ( preg_match( '/RC=(\d+)/',      $out, $m ) ) { $r[ 'reconnects' ] = (int) $m[ 1 ]; }
    if ( preg_match( '/DFMS=([\d.]+)/', $out, $m ) ) { $r[ 'df_ms' ]      = (float) $m[ 1 ]; }
    if ( preg_match( '/AVAIL=(\d+)/',   $out, $m ) ) { $r[ 'avail_kb' ]   = (int) $m[ 1 ]; }
    $r[ 'ok' ] = true;
    return $r;
}

# local mount health (mount mode)
function mon_mount_local( $mountpoint ) {
    $r = [ 'fs' => '', 'reconnects' => null, 'df_ms' => null, 'avail_kb' => null, 'mounted' => false, 'err' => '' ];
    if ( !strlen( $mountpoint ) ) {
        $r[ 'err' ] = 'no mountpoint';
        return $r;
    }
    $mi          = mon_mount_info( $mountpoint );
    $r[ 'fs' ]   = $mi[ 'fs' ];
    $r[ 'mounted' ] = strlen( $mi[ 'fs' ] ) > 0;

    $t0  = microtime( true );
    $out = mon_sh( "df -Pk " . escapeshellarg( $mountpoint ), $code );
    $r[ 'df_ms' ] = round( ( microtime( true ) - $t0 ) * 1000.0, 1 );
    if ( preg_match( '/\n\S+\s+\d+\s+\d+\s+(\d+)\s+/', $out, $m ) ) {
        $r[ 'avail_kb' ] = (int) $m[ 1 ];
    } else if ( $code !== 0 ) {
        $r[ 'err' ] = 'df failed/blocked';
    }
    if ( $r[ 'fs' ] === 'cifs' && is_readable( '/proc/fs/cifs/Stats' ) ) {
        $s = (string) @file_get_contents( '/proc/fs/cifs/Stats' );
        if ( preg_match_all( '/reconnect[s]?[^0-9]*([0-9]+)/i', $s, $mm ) ) {
            $r[ 'reconnects' ] = array_sum( array_map( 'intval', $mm[ 1 ] ) );
        }
    }
    return $r;
}

function mon_rpcinfo( $server ) {
    $r = [ 'ok' => false, 'missing' => [], 'err' => '' ];
    if ( !mon_have_cmd( 'rpcinfo' ) || !strlen( $server ) ) {
        $r[ 'err' ] = 'rpcinfo/server n/a';
        return $r;
    }
    $out = mon_sh( "rpcinfo -T tcp " . escapeshellarg( $server ), $code );
    if ( $code !== 0 ) {
        $out = mon_sh( "rpcinfo -p " . escapeshellarg( $server ), $code );
    }
    $need = [ 'portmapper' => false, 'nfs' => false, 'mountd' => false, 'nlockmgr' => false, 'status' => false ];
    foreach ( array_keys( $need ) as $svc ) {
        if ( stripos( $out, $svc ) !== false ) {
            $need[ $svc ] = true;
        }
    }
    foreach ( $need as $svc => $present ) {
        if ( !$present ) {
            $r[ 'missing' ][] = $svc;
        }
    }
    $r[ 'ok' ] = ( $code === 0 && count( $r[ 'missing' ] ) === 0 );
    return $r;
}

function mon_throughput( $M, $t ) {
    $res = [ 'mbps' => null, 'ok' => null ];
    $mb  = (int) $M[ 'throughput' ];
    if ( $mb <= 0 ) {
        return $res;
    }
    $blob = $M[ 'logdir' ] . "/.throughput_blob";
    if ( !file_exists( $blob ) || filesize( $blob ) != $mb * 1024 * 1024 ) {
        mon_sh( "dd if=/dev/urandom of=" . escapeshellarg( $blob ) . " bs=1M count=$mb" );
    }
    $tag = "/.uslims_mon_thr_" . getmypid();

    if ( $t[ 'mode' ] === 'ssh' ) {
        if ( !mon_have_cmd( 'rsync' ) ) {
            return $res;
        }
        $target = strlen( $t[ 'ssh_user' ] ) ? "{$t[ 'ssh_user' ]}@{$t[ 'host' ]}" : $t[ 'host' ];
        $opts   = "-o BatchMode=yes -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p {$t[ 'ssh_port' ]} {$t[ 'ssh_opts' ]}";
        $dest   = "$target:/tmp$tag";
        $t0     = microtime( true );
        mon_sh( "rsync -e " . escapeshellarg( "ssh $opts" ) . " --whole-file --inplace "
                . escapeshellarg( $blob ) . " " . escapeshellarg( $dest ), $code );
        $dt = microtime( true ) - $t0;
        if ( $code === 0 && $dt > 0 ) {
            $res[ 'mbps' ] = round( $mb / $dt, 2 );
            $res[ 'ok' ]   = true;
        } else {
            $res[ 'ok' ] = false;
        }
        mon_sh( "ssh $opts " . escapeshellarg( $target ) . " rm -f /tmp$tag" );
    } else if ( $t[ 'mode' ] === 'mount' && strlen( $t[ 'mount' ] ) ) {
        $dest = rtrim( $t[ 'mount' ], '/' ) . $tag;
        $t0   = microtime( true );
        mon_sh( "dd if=" . escapeshellarg( $blob ) . " of=" . escapeshellarg( $dest ) . " bs=1M conv=fsync", $code );
        $dt = microtime( true ) - $t0;
        if ( $code === 0 && $dt > 0 ) {
            $res[ 'mbps' ] = round( $mb / $dt, 2 );
            $res[ 'ok' ]   = true;
        } else {
            $res[ 'ok' ] = false;
        }
        @unlink( $dest );
    }
    return $res;
}

function mon_tcp_targets( $M, $t ) {
    $list = [];
    if ( $t[ 'mode' ] === 'ssh' ) {
        $list[] = [ $t[ 'host' ], $t[ 'ssh_port' ], "ssh:{$t[ 'ssh_port' ]}" ];
    }
    if ( ( $t[ 'mode' ] === 'mount' || $t[ 'mode' ] === 'custom' ) && strlen( $t[ 'server' ] ) ) {
        foreach ( mon_default_ports( $t[ 'fs' ] ) as $p ) {
            $list[] = [ $t[ 'server' ], $p, (string) $p ];
        }
    }
    foreach ( (array) $M[ 'tcp_ports' ] as $hp ) {
        if ( preg_match( '/^(.+):(\d+)$/', $hp, $m ) ) {
            $list[] = [ $m[ 1 ], (int) $m[ 2 ], $hp ];
        }
    }
    return $list;
}

function mon_run_probes( $M ) {
    $t   = mon_resolve_transport( $M );
    $now = time();
    $s   = [
        'epoch'           => $now,
        'ts'              => gmdate( 'Y-m-d H:i:s', $now ),
        'mode'            => $t[ 'mode' ],
        'host'            => $t[ 'host' ],
        'fs'              => $t[ 'fs' ],
        'ping_avg'        => null,
        'ping_max'        => null,
        'ping_loss'       => null,
        'ping_jitter'     => null,
        'ssh_ms'          => null,
        'reconnect'       => null,
        'reconnect_delta' => null,
        'df_ms'           => null,
        'avail_kb'        => null,
        'tcp_ok'          => null,
        'tcp_detail'      => '',
        'mbps'            => null,
        'status'          => 'ok',
        'notes'           => '',
    ];
    $notes = [];

    if ( strlen( $t[ 'host' ] ) && mon_check_enabled( $M, 'ping' ) ) {
        $p                 = mon_ping( $t[ 'host' ] );
        $s[ 'ping_avg' ]    = $p[ 'avg' ];
        $s[ 'ping_max' ]    = $p[ 'max' ];
        $s[ 'ping_loss' ]   = $p[ 'loss' ];
        $s[ 'ping_jitter' ] = $p[ 'jitter' ];
        if ( $p[ 'loss' ] !== null && $p[ 'loss' ] > 0 ) {
            $notes[] = "ping_loss={$p[ 'loss' ]}%";
        }
        if ( !$p[ 'ok' ] ) {
            $s[ 'status' ] = 'fail';
            $notes[]       = 'ping_down';
        }
    }

    if ( !$M[ 'ports_off' ] && mon_check_enabled( $M, 'tcp' ) ) {
        $ports   = mon_tcp_targets( $M, $t );
        $details = [];
        $all_ok  = count( $ports ) ? true : null;
        foreach ( $ports as $pp ) {
            $res       = mon_tcp( $pp[ 0 ], $pp[ 1 ] );
            $details[] = $pp[ 2 ] . '=' . ( $res[ 'ok' ] ? $res[ 'ms' ] . 'ms' : 'DOWN' );
            if ( !$res[ 'ok' ] ) {
                $all_ok = false;
            }
        }
        $s[ 'tcp_ok' ]     = $all_ok;
        $s[ 'tcp_detail' ] = implode( ';', $details );
        if ( $all_ok === false ) {
            $s[ 'status' ] = 'fail';
            $notes[]       = 'tcp_down';
        }
    }

    if ( $t[ 'mode' ] === 'ssh' ) {
        if ( mon_check_enabled( $M, 'ssh_rtt' ) ) {
            $sr           = mon_ssh_rtt( $t );
            $s[ 'ssh_ms' ] = $sr[ 'ms' ];
            if ( !$sr[ 'ok' ] ) {
                $s[ 'status' ] = 'fail';
                $notes[]       = 'ssh_down';
            }
        }
        if ( mon_check_enabled( $M, 'remote_fs' ) && strlen( $t[ 'remote_path' ] ) ) {
            $rf = mon_remote_fs( $t );
            if ( $rf[ 'ok' ] ) {
                if ( strlen( $rf[ 'fs' ] ) ) {
                    $s[ 'fs' ] = $rf[ 'fs' ];
                }
                $s[ 'reconnect' ] = $rf[ 'reconnects' ];
                $s[ 'df_ms' ]     = $rf[ 'df_ms' ];
                $s[ 'avail_kb' ]  = $rf[ 'avail_kb' ];
            } else if ( strlen( $rf[ 'err' ] ) ) {
                $notes[] = 'remote_fs_err';
            }
        }
    }

    if ( $t[ 'mode' ] === 'mount' ) {
        if ( mon_check_enabled( $M, 'mount_local' ) ) {
            $ml = mon_mount_local( $t[ 'mount' ] );
            if ( strlen( $ml[ 'fs' ] ) ) {
                $s[ 'fs' ] = $ml[ 'fs' ];
            }
            $s[ 'reconnect' ] = $ml[ 'reconnects' ];
            $s[ 'df_ms' ]     = $ml[ 'df_ms' ];
            $s[ 'avail_kb' ]  = $ml[ 'avail_kb' ];
            if ( !$ml[ 'mounted' ] ) {
                $s[ 'status' ] = 'fail';
                $notes[]       = 'not_mounted';
            }
        }
        if ( $s[ 'fs' ] === 'nfs' && $M[ 'rpcinfo' ] !== 'off' && mon_check_enabled( $M, 'rpcinfo' ) ) {
            $ri = mon_rpcinfo( $t[ 'server' ] );
            if ( !$ri[ 'ok' ] && count( $ri[ 'missing' ] ) ) {
                if ( $s[ 'status' ] === 'ok' ) {
                    $s[ 'status' ] = 'warn';
                }
                $notes[] = 'rpc_missing:' . implode( '/', $ri[ 'missing' ] );
            }
        }
    }

    if ( $s[ 'reconnect' ] !== null ) {
        $key  = $t[ 'mode' ] . ':' . $t[ 'host' ] . ':' . $s[ 'fs' ];
        $prev = mon_state_get( $M, "reconnect:$key" );
        if ( $prev !== null ) {
            $d                      = $s[ 'reconnect' ] - (int) $prev;
            $s[ 'reconnect_delta' ] = $d;
            if ( $d > 0 ) {
                $notes[] = "reconnects+$d";
                if ( $s[ 'status' ] === 'ok' ) {
                    $s[ 'status' ] = 'warn';
                }
            }
        }
        mon_state_set( $M, "reconnect:$key", $s[ 'reconnect' ] );
    }

    if ( $M[ 'throughput' ] > 0 && mon_check_enabled( $M, 'throughput' ) ) {
        $th           = mon_throughput( $M, $t );
        $s[ 'mbps' ]  = $th[ 'mbps' ];
        if ( $th[ 'ok' ] === false ) {
            $notes[] = 'throughput_fail';
        }
    }

    $s[ 'notes' ] = implode( ' ', $notes );
    return $s;
}

# ===========================================================================
# state / output
# ===========================================================================

function mon_state_file( $M ) {
    return $M[ 'logdir' ] . '/.' . $M[ 'prog' ] . '.state';
}

function mon_state_get( $M, $k ) {
    $s = @json_decode( (string) @file_get_contents( mon_state_file( $M ) ), true );
    return ( is_array( $s ) && array_key_exists( $k, $s ) ) ? $s[ $k ] : null;
}

function mon_state_set( $M, $k, $v ) {
    $f = mon_state_file( $M );
    $s = @json_decode( (string) @file_get_contents( $f ), true );
    if ( !is_array( $s ) ) {
        $s = [];
    }
    $s[ $k ] = $v;
    @file_put_contents( $f, json_encode( $s ) );
}

function mon_current_csv_path( $M ) {
    return $M[ 'logdir' ] . '/backup-monitor-' . gmdate( 'Ymd' ) . '.csv';
}

function mon_current_log_path( $M ) {
    return $M[ 'logdir' ] . '/backup-monitor-' . gmdate( 'Ymd' ) . '.log';
}

function mon_csv_header() {
    return [
        'ts_utc', 'epoch', 'mode', 'host', 'fs',
        'ping_avg_ms', 'ping_max_ms', 'ping_loss_pct', 'ping_jitter_ms',
        'ssh_rtt_ms', 'reconnect', 'reconnect_delta', 'df_ms', 'avail_kb',
        'tcp_ok', 'tcp_detail', 'throughput_mbps', 'status', 'notes',
    ];
}

function mon_write_csv( $M, $s ) {
    $path = mon_current_csv_path( $M );
    $new  = !file_exists( $path );
    $fp   = @fopen( $path, 'a' );
    if ( !$fp ) {
        return;
    }
    if ( $new ) {
        fputcsv( $fp, mon_csv_header() );
    }
    fputcsv( $fp, [
        $s[ 'ts' ], $s[ 'epoch' ], $s[ 'mode' ], $s[ 'host' ], $s[ 'fs' ],
        mon_csv_num( $s[ 'ping_avg' ] ), mon_csv_num( $s[ 'ping_max' ] ),
        mon_csv_num( $s[ 'ping_loss' ] ), mon_csv_num( $s[ 'ping_jitter' ] ),
        mon_csv_num( $s[ 'ssh_ms' ] ), mon_csv_num( $s[ 'reconnect' ] ),
        mon_csv_num( $s[ 'reconnect_delta' ] ), mon_csv_num( $s[ 'df_ms' ] ),
        mon_csv_num( $s[ 'avail_kb' ] ), mon_csv_bool( $s[ 'tcp_ok' ] ),
        $s[ 'tcp_detail' ], mon_csv_num( $s[ 'mbps' ] ), $s[ 'status' ], $s[ 'notes' ],
    ] );
    fclose( $fp );
}

function mon_log_line( $M, $msg ) {
    @file_put_contents( mon_current_log_path( $M ), '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $msg . "\n", FILE_APPEND );
}

function mon_format_sample( $s ) {
    $bits = [ "mode={$s[ 'mode' ]}", "host={$s[ 'host' ]}" ];
    if ( $s[ 'ping_avg' ] !== null ) {
        $loss   = $s[ 'ping_loss' ] === null ? '?' : $s[ 'ping_loss' ];
        $bits[] = sprintf( "ping=%.1f/%.1fms loss=%s%%", $s[ 'ping_avg' ], $s[ 'ping_max' ], $loss );
    } else {
        $bits[] = "ping=n/a";
    }
    if ( $s[ 'ssh_ms' ] !== null )          { $bits[] = "ssh={$s[ 'ssh_ms' ]}ms"; }
    if ( strlen( (string) $s[ 'fs' ] ) )    { $bits[] = "fs={$s[ 'fs' ]}"; }
    if ( $s[ 'reconnect_delta' ] !== null ) { $bits[] = "reconn+{$s[ 'reconnect_delta' ]}"; }
    if ( $s[ 'df_ms' ] !== null )           { $bits[] = "df={$s[ 'df_ms' ]}ms"; }
    if ( strlen( $s[ 'tcp_detail' ] ) )     { $bits[] = "tcp[{$s[ 'tcp_detail' ]}]"; }
    if ( $s[ 'mbps' ] !== null )            { $bits[] = "thr={$s[ 'mbps' ]}MB/s"; }
    $bits[] = "status=" . strtoupper( $s[ 'status' ] );
    if ( strlen( $s[ 'notes' ] ) )          { $bits[] = "({$s[ 'notes' ]})"; }
    return implode( ' ', $bits );
}

function mon_maybe_alert( $M, $s ) {
    if ( !$M[ 'alert' ] || $s[ 'status' ] === 'ok' ) {
        return;
    }
    $last = (int) mon_state_get( $M, 'last_alert' );
    if ( time() - $last < 3600 ) {
        return; # rate-limit to at most hourly
    }
    $addr = (string) mon_cfg( 'backup_email_address', '' );
    if ( !strlen( $addr ) ) {
        return;
    }
    mon_state_set( $M, 'last_alert', time() );
    $host    = (string) mon_cfg( 'backup_host', gethostname() );
    $subject = "backup-monitor " . strtoupper( $s[ 'status' ] ) . " for $host";
    $body    = "Backup link health alert\n\n" . mon_format_sample( $s ) . "\n\n"
             . "csv: " . mon_current_csv_path( $M ) . "\n"
             . "log: " . mon_current_log_path( $M ) . "\n";
    $headers = function_exists( 'backup_rsync_email_headers' ) ? backup_rsync_email_headers() : '';
    @mail( $addr, $subject, $body, $headers );
}

function mon_prune_old_logs( $M ) {
    static $last = 0;
    if ( time() - $last < 3600 ) {
        return;
    }
    $last = time();
    $keep = $M[ 'keep' ];
    foreach ( [ 'csv', 'log' ] as $ext ) {
        $files = glob( $M[ 'logdir' ] . "/backup-monitor-*.$ext" );
        if ( !is_array( $files ) ) {
            continue;
        }
        sort( $files );
        $excess = count( $files ) - $keep;
        for ( $i = 0; $i < $excess; $i++ ) {
            @unlink( $files[ $i ] );
        }
    }
}

function mon_last_csv_time( $csv ) {
    if ( !file_exists( $csv ) ) {
        return '';
    }
    $line = trim( (string) @exec( "tail -1 " . escapeshellarg( $csv ) ) );
    if ( $line === '' || strpos( $line, 'ts_utc' ) === 0 ) {
        return '';
    }
    $c = str_getcsv( $line );
    return isset( $c[ 0 ] ) ? $c[ 0 ] : '';
}
