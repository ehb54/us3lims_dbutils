<?php

{};

# user defines

$repo_api_url  = "https://github.com/ehb54/aucsol_api.git";
$repo_api_dest = "/opt/us3_api";
$repo_cli_url  = "https://github.com/ehb54/aucsol_api_cli.git";
$repo_cli_dest = "/opt/us3_cli";

$api_host      = "127.0.0.1";
$api_port      = 59283;

$endpoint_host = "127.0.0.1";
$endpoint_port = 59284;

$mock_path     = "/tmp/mock_server.php";

# end user defines

# api developer defines

$pat_table          = "personal_access_tokens";
$aucsol_api_db_user = "aucsol_api";
$api_image_name     = "aucsol_api";
$api_service_path   = "/etc/systemd/system/aucsol_api.service";

$cli_image_name     = "aucsol-api-cli";
$cli_service_path   = "/etc/systemd/system/aucsol_cli.service";

# end api developer defines

require "utility.php";

$self = __FILE__;
$cwd  = getcwd();

# $debug = 1;

$notes = <<<__EOD
usage: $self {db_config_file}

Manages API client

Options

--help                  : print this information and exit
--debug                 : turn on debugging

--php-yaml              : install php-yaml
--mock port             : setup mock server on port

--install {api|cli|all} : installs
--git-user user         : git user
--git-token token       : git user token
--repo-api url          : api repo url (default $repo_api_url)
--repo-api-dest dir     : api repo dest (default $repo_api_dest)
--repo-cli url          : cli repo url (default $repo_cli_url)
--repo-cli-dest dir     : cli repo dest (default $repo_cli_dest)
--repo-plugins url      : plugin repo url (can be repeated), will be placed --repo-cli-dest/plugins

--db name               : database
--api-db-password pw    : set user '$aucsol_api_db_user' db password

--api-port port         : set the api port number, default $api_port;

__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$debug                  = 0;
$install                = '';
$git_user               = '';
$git_token              = '';
$repo_plugin            = [];
$repo_plugin_dest       = [];
$db                     = '';
unset( $aucsol_api_db_password );
$php_yaml               = false;
$mock_port              = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $arg = $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--debug": {
            array_shift( $u_argv );
            $debug++;
            break;
        }
        case "--mock": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $mock_port = array_shift( $u_argv );
            break;
        }
        case "--php-yaml": {
            array_shift( $u_argv );
            $php_yaml = true;
            break;
        }
        case "--install" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $install = array_shift( $u_argv );
            if ( !preg_match( '/(api|cli|all)/', $install ) ) {
                error_exit( "invalid option '$install' for --install" );
            }
            break;
        }
        case "--git-user" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $git_user = array_shift( $u_argv );
            break;
        }
        case "--git-token" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $git_token = array_shift( $u_argv );
            break;
        }
        case "--repo-api" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $repo_api_url = array_shift( $u_argv );
            break;
        }
        case "--repo-api-dest" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $repo_api_dest = array_shift( $u_argv );
            break;
        }
        case "--repo-cli" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $repo_cli_url = array_shift( $u_argv );
            break;
        }
        case "--repo-cli-dest" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $repo_cli_dest = array_shift( $u_argv );
            break;
        }
        case "--repo-plugins" : {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption '$arg' requires an argument\n\n$notes" );
            }
            $repo_plugin[]      = array_shift( $u_argv );
            break;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $db = array_shift( $u_argv );
            break;
        }
        case "--api-db-password": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $aucsol_api_db_password = array_shift( $u_argv );
            break;
        }
        case "--api-port": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "ERROR: option '$arg' requires an argument\n$notes" );
            }
            $api_port = array_shift( $u_argv );
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
    error_exit( "Incorrect command format\n$notes" );
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

$myconf = "my.cnf";
if ( !file_exists( $myconf ) ) {
   error_exit( "create a file '$myconf' in the current directory with the following contents:\n" .
   "[mysqldump]\n" .
   "password=YOUR_ROOT_DB_PASSWORD\n"
   );
}
file_perms_must_be( $myconf );

if ( !$db ) {
    error_exit( "--db must be defined" );
}

if ( $php_yaml ) {
    $cmd = "php -m 2>&1 | grep yaml";
    $res = trim( run_cmd( $cmd, false ) );
    if ( $res == 'yaml' ) {
        echo "php yaml already installed\n";
    } else {
        echo "installing php-yaml\n";
        $cmd = "dnf install -y libyaml-devel";
        print "$cmd\n";
        print run_cmd( $cmd );
        $cmd = "yes '' | pecl install yaml";
        print "$cmd\n";
        print run_cmd( $cmd, false );
        $cmd = "echo 'extension=yaml.so' >> /etc/php.ini";
        print "$cmd\n";
        print run_cmd( $cmd, false );
    }
}

open_db();

$query =
    "SELECT count(*) from information_schema.tables where table_schema='$db' and table_name='$pat_table'";

$res = db_obj_result( $db_handle, $query, false, true );

if ( $res->{'count(*)'} != 1 ) {
    error_exit( "db $db missing TABLE $pat_table" );
}

$repo_api_parent = dirname( $repo_api_dest, 1 );
$repo_cli_parent = dirname( $repo_cli_dest, 1 );

if ( preg_match( '/(api|all)/', $install ) ) {
    if ( !is_dir( $repo_api_parent ) ) {
        error_exit( "directory $repo_api_parent does not exist" );
    }
    if ( is_dir( $repo_api_dest ) ) {
        error_exit( "directory $repo_api_dest exists" );
    }
}

if ( preg_match( '/(cli|all)/', $install ) ) {
    if ( !is_dir( $repo_cli_parent ) ) {
        error_exit( "directory $repo_cli_parent does not exist" );
    }
    if ( is_dir( $repo_cli_dest ) ) {
        error_exit( "directory $repo_cli_dest exists" );
    }
}

if ( preg_match( '/(api|all)/', $install ) ) {
    if ( !isset( $aucsol_api_db_password ) ) {
        error_exit( "--api-db-password must be specified" );
    }

    ## clone repo_api

    $cmd = "git clone $repo_api_url $repo_api_dest";
    echo "running: $cmd\n";
    run_cmd( $cmd );

    ## setup .env

    $cmd = "cd $repo_api_dest && cp .env.example .env";
    echo run_cmd( $cmd );

    ## add user

    $hosts = [
        "localhost"
        ,"127.0.0.1"
        ,"172.17.%"
        ];

    $queries = [];

    foreach ( $hosts as $h ) {
        $queries[] =
            "DROP USER IF EXISTS '$aucsol_api_db_user'@'$h'"
            ;
        $queries[] =
            "CREATE USER '$aucsol_api_db_user'@'$h' IDENTIFIED BY '$aucsol_api_db_password'"
            ;
        $queries[] =
            "grant execute, select, show view on $db.* to '$aucsol_api_db_user'@'$h'"
            ;
        $queries[] =
            "grant alter, delete, insert, references, select, show view, trigger, update on table $db.$pat_table to '$aucsol_api_db_user'@'$h'"
            ;
    }

    foreach ( $queries as $query ) {
        if ( $debug ) {
            echo "query: $query\n";
        }
        $res = db_obj_result( $db_handle, $query, false, true );

        if ( !$res ) {
            error_exit( "mysql error " . $query . " error : " . mysqli_error($db_handle) );
        }
        if ( $debug ) {
            debug_json( $query, $res );
        }
    }

    ## update .env
    update_key_value_in_file( "$repo_api_dest/.env", "DB_PASSWORD", "$aucsol_api_db_password\n" );

    ## build container 1st time

    $cmd = "cd $repo_api_dest && docker build -t $api_image_name .";
    echo "building the $api_image_name container\n";
    run_cmd( $cmd );
    echo "$api_image_name container built\n";
    $cmd = "cd $repo_api_dest && docker run --rm $api_image_name php artisan key:generate --show";
    $app_key = run_cmd( $cmd );
    echo "app key is '$app_key'\n";

    ## update .env
    update_key_value_in_file( "$repo_api_dest/.env", "APP_KEY", $app_key );
    update_key_value_in_file( "$repo_api_dest/.env", "APP_DEBUG", "false" );
    
    ## rebuild container 
    $cmd = "cd $repo_api_dest && docker build -t $api_image_name .";
    echo "rebuilding the $api_image_name container\n";
    run_cmd( $cmd );
    echo "$api_image_name container built\n";

    $servicefile = <<<__EOD
[Unit]
Description=Aucsol API Container
# Ensures docker and mariadb start before this service
After=docker.service mariadb.service
# If mariadb fails to start, this service will not start
Requires=docker.service mariadb.service

[Service]
Restart=always
# Pre-clean to ensure the name isn't already in use
ExecStartPre=-/usr/bin/docker stop $api_image_name
ExecStartPre=-/usr/bin/docker rm $api_image_name

# The main command
ExecStart=/usr/bin/docker run --rm \
    -p $api_port:80 \
    --name $api_image_name \
    --add-host=host.docker.internal:host-gateway \
    $api_image_name

# Clean shutdown
ExecStop=/usr/bin/docker stop $api_image_name

[Install]
WantedBy=multi-user.target

__EOD;

    if ( false === file_put_contents( $api_service_path, $servicefile ) ) {
        error_exit( "error writing the api service file $api_service_path" );
    }
    $cmd = "sudo systemctl daemon-reload";
    run_cmd( $cmd );
}

if ( preg_match( '/(cli|all)/', $install ) ) {

    ## clone repo_cli

    $cmd = "git clone $repo_cli_url $repo_cli_dest";
    echo "running: $cmd\n";
    run_cmd( $cmd );

    ## clone all plugins

    foreach ( $repo_plugin as $url ) {
        $cmd = "cd $repo_cli_dest/plugins && git clone $url";
        echo "$cmd\n";
        run_cmd( $cmd );
    }

    ## copy config

    if ( !is_dir( "/etc/us3_cli" ) ) {
        $cmd = "mkdir /etc/us3_cli";
        run_cmd( $cmd );
    }

    $yaml_file = "/etc/us3_cli/config.yaml";
        
    $cmd = "cp $repo_cli_dest/config/cli_config.global.example.yaml $yaml_file";
    run_cmd( $cmd );

    $yaml_config = yaml_parse_file( $yaml_file );
    $yaml_config['api']['url'] = "https://$api_host:$api_port";
    $yaml_config['plugins']['global_dirs'] = [ '/opt/us3_cli/plugins' ];
    $yaml_config['plugins']['autoflow_report_model_stats'] = [ 'result_endpoint' => [ 'url' => "https://$endpoint_host:$endpoint_port" ] ];
    unset( $yaml_config['plugins']['enabled'] );
    yaml_emit_file( $yaml_file, $yaml_config );

    $cmd = "cd $repo_cli_dest && docker build -t $cli_image_name .";
    echo "building the $cli_image_name container\n";
    run_cmd( $cmd );
    echo "$cli_image_name container built\n";
}


if ( $mock_port ) {
    $mock_code = <<<__EOD
<?php
\$stdout = fopen('php://stdout', 'w');

fwrite(\$stdout, "=== NEW REQUEST ===\n");
fwrite(\$stdout, \$_SERVER['REQUEST_METHOD'] . " " . \$_SERVER['REQUEST_URI'] . "\n");

fwrite(\$stdout, "HEADERS:\n");
if (function_exists('getallheaders')) {
    foreach (getallheaders() as \$name => \$value) {
        fwrite(\$stdout, "\$name: \$value\n");
    }
}

\$payload = file_get_contents('php://input');
fwrite(\$stdout, "\nPAYLOAD:\n");
fwrite(\$stdout, \$payload === '' ? "(empty)\n" : \$payload . "\n");
fwrite(\$stdout, "===================\n\n");

fclose(\$stdout);

http_response_code(200);


__EOD;

    if ( false === file_put_contents( $mock_path, $mock_code ) ) {
        error_exit( "error writing $mock_path" );
    }

    echo "run mock server :\nphp -S 0.0.0.0:$mock_port $mock_path\n";
}
