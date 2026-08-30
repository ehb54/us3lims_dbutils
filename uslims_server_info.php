<?php

# developer defines
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

list stats for Jira Xray upgrade info

Options

--help               : print this information and exit


__EOD;

require_once "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$use_dbs             = [];
$times               = false;
$names               = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
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
require_once $use_config_file;

# checks

# main
echo "\n";
# cpu

$cpu_string = 'echo "$(lscpu | grep "^Socket(s):" | awk \'{print $2}\')x $(lscpu | grep "Model name:" | head -n 1 | sed \'s/Model name:[ \t]*//\')"';
echo run_cmd( $cpu_string );
echo "\n";

$ram_string = 'free -h --giga | awk \'/Mem:/ {print $2}\'';
echo run_cmd( $ram_string );
echo "\n";

echo run_cmd( "cat /etc/os-release | grep PRETTY_NAME | awk -F\\\" '{ print $2 }'" );
echo "\n";

echo run_cmd( "cat /proc/version" );
echo "\n";

echo run_cmd( "df -h | grep -v tmpfs" );
echo "\n";

echo run_cmd( "sudo ifconfig -a" );
echo "\n";


## gui version
$gui_vers = intval( run_cmd( "php uslims_git_info.php  | grep gui | awk '{ print $9 }'" ) );
$db_vers = intval( run_cmd( "php uslims_git_info.php  | grep us3_sql.git | awk '{ print $9 }'" ) );
echo sprintf( "GUI %d (%d visually) DB %d\n", $gui_vers, ($gui_vers + 2), $db_vers );
echo "\n";

## qt_version
$qt_vers = run_cmd( "ls -l /opt/qt | sed 's/^.*\/qt-//g'" );
echo $qt_vers;
echo "\n";


## db
echo run_cmd( "mysql -V" );
echo "\n";

## apache
echo run_cmd( "apachectl -V 2>&1 | head -1" );
echo "\n";

## php
echo run_cmd( "php -v 2>&1 | head -1" );
echo "\n";
