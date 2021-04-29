<?php

# user defines

$us3bin         = "/home/us3/lims/bin";
$listenconfig   = "$us3bin/listen-config.php";
$wwwpath        = "/srv/www/htdocs";
$srvconfig      = "$wwwpath/uslims3/config.php";
$httpdconfigdir = "/etc/httpd/conf.d";
$ssl_self_dir   = "/etc/httpd/ssl";
$ssl_self_key   = "self-priv.key";
$ssl_self_cert  = "self-cert.key";
$ssl_self_csr   = "self-csr.key";

# end user defines

# developer defines

$logging_level = 2;

# end of developer defines

$self = __FILE__;
$cwd  = getcwd();

require "utility.php";
    
$notes = <<<__EOD
usage: $self {--change old_domain_name new_domain_name} {remote_config_file}

list all domain name info
__EOD;

$u_argv = $argv;
array_shift( $u_argv ); # first element is program name
$new_domain_processing = false;
if ( count( $u_argv ) && $new_domain_processing = $u_argv[0] == '--change' ) {
    array_shift( $u_argv );
    if ( count( $u_argv ) < 2 ) {
        echo "$self: --change keyword requires two domain names\n";
        exit(-1);
    }
    $old_domain = array_shift( $u_argv );
    $new_domain = array_shift( $u_argv );
    if ( $old_domain == $new_domain ) {
        echo "$self: --change old domain can not be the same as new domain\n";
        exit(-1);
    }
}

if ( count( $u_argv ) > 1 ) {
    echo $notes;
    exit;
}

$config_file = "db_config.php";
if ( count( $u_argv ) == 1 ) {
    $use_config_file = $u_argv[ 0 ];
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
            
require $use_config_file;

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}

# main

# hostname check

$hostname = trim( run_cmd( 'hostname' ) );

echoline();
echo "system hostname '$hostname'\n";
echoline();


# metadata hosts check

$uniqhosts = [];
$meta_dbs  = [];

$res = db_obj_result( $db_handle, "select dbname, dbhost, limshost, status from newus3.metadata where status='completed'", True );
while( $row = mysqli_fetch_array($res) ) {
    #    debug_json( "row", $row );
    $dbname   = $row['dbname'];
    $dbhost   = $row['dbhost'];
    $limshost = $row['limshost'];
    if ( !isset( $uniqhosts[ $dbhost ] ) ) {
        $uniqhosts[ $dbhost ] = 1;
    }
    if ( $dbhost != $limshost ) {
        $warnings .= "WARNING: dbhost/limshost mismatch in metadata: dbname '$dbname' dbhost '$dbhost' limshost '$limshost'\n";
        if ( !isset( $uniqhosts[ $limshost ] ) ) {
            $uniqhosts[ $limshost ] = 1;
        }
    }
    $meta_dbs[ $dbname ] = 1;
}

flush_warnings();

echo count( $uniqhosts ) . " unique host name(s) from completed metadata: " . implode( ' ', array_keys( $uniqhosts ) ) . "\n";
echoline();

$host_accepted = $hostname;
if ( count( $uniqhosts ) != 1 ) {
    $host_accepted = array_keys( $uniqhosts )[0];
    $warnings .= "WARNING: more than one host defined in completed metadata, assuming the first found is correct\n";
} else {
    $host_accepted = array_keys( $uniqhosts )[0];
    if ( !isset( $uniqhosts[ $hostname ] ) ) {
        $warnings .= "WARNING: metadata hostname does not match system hostname\n";
    }
}

flush_warnings();
# check dbs

$res = db_obj_result( $db_handle, "show databases like 'uslims3_%'", True );
$mysql_dbs = [];
while( $row = mysqli_fetch_array($res) ) {
    $this_db = (string)$row[0];
    if ( $this_db != "uslims3_global" ) {
        $mysql_dbs[ $this_db ] = 1;
    }
}

# make sure they match
foreach ( $meta_dbs as $k => $v ) {
    if ( !isset( $mysql_dbs[ $k ] ) ) {
        $warnings .= "WARNING: db $k is present in completed metadata but does not exist as a mysql db\n";
    }
}

foreach ( $mysql_dbs as $k => $v ) {
    if ( !isset( $meta_dbs[ $k ] ) ) {
        $warnings .= "WARNING: db $k is a mysql db but not present in completed metadata\n";
    }
}

flush_warnings( "OK: All completed metadata dbs match existing mysql dbs" );

# check names in variables

## listen-config hostname check

$check_names[ "$listenconfig: \$servhost"  ] = trim( run_cmd( "grep -e '^\s*\$servhost\s*=' $listenconfig | tail -1 | awk -F\\\" '{ print \$2 }'" ) );
$check_names[ "$listenconfig: \$host_name" ] = trim( run_cmd( "grep -e '^\s*\$host_name\s*=' $listenconfig  | tail -1 | awk -F\\\" '{ print \$2 }'" ) );

## check config.php
$class_dirs = [];

$check_names[ "$srvconfig: \$org_site"     ] = trim( run_cmd( "grep -e '^\s*\$org_site\s*=' $srvconfig  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
foreach ( $mysql_dbs as $k => $v ) {
    $instpath = "$wwwpath/uslims3/$k/config.php";
    $tmp_key  = "$instpath: \$org_site";
    $check_names[ $tmp_key ] = trim( run_cmd( "grep -e '^\s*\$org_site\s*=' $instpath  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
    $class_dirs [ $tmp_key ] = trim( run_cmd( "grep -e '^\s*\$class_dir\s*=' $instpath  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
    if ( !preg_match( "/\/$k\$/", $check_names[ $tmp_key ] ) ) {
        $warnings .= "WARNING: $tmp_key does not end with the expected extension '\/$k'\n";
    }
    $check_names[ $tmp_key ] = preg_replace( "/\/$k\$/", "", $check_names[ $tmp_key ] );
}    

{
    $k = "uslims3_newlims";
    $instpath = "$wwwpath/uslims3/$k/config.php";
    $tmp_key  = "$instpath: \$org_site";
    $check_names[ $tmp_key ] = trim( run_cmd( "grep -e '^\s*\$org_site\s*=' $instpath  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
    $class_dirs [ $tmp_key ] = trim( run_cmd( "grep -e '^\s*\$class_dir\s*=' $instpath  | tail -1 | awk -F\\' '{ print \$2 }'" ) );
    if ( !preg_match( "/\/$k\$/", $check_names[ $tmp_key ] ) ) {
        $warnings .= "WARNING: $tmp_key does not end with the expected extension '\/$k'\n";
    }
    $check_names[ $tmp_key ] = preg_replace( "/\/$k\$/", "", $check_names[ $tmp_key ] );
}    

## list class dirs
if ( count( array_unique( $class_dirs ) ) == 1 ) {
    echo "OK: All instance \$class_dirs match and have value " . array_shift( $class_dirs ) . "\n";
} else {
    echoline();
    foreach ( $class_dirs as $k => $v ) {
        echo "$k class dir $v\n";
    }
    echoline();
}

## validate $check_names

foreach ( $check_names as $k => $v ) {
    if ( $v != $host_accepted ) {
        $warnings .= "WARNING: $k value '$v' is not equal to our accepted host '$host_accepted'\n";
    }
}
flush_warnings( "OK: All config variables match our accepted host '$host_accepted'" );

# check httpd configs

$http_configs  = explode( "\n", trim( run_cmd( "cd $httpdconfigdir && find * -name 'http-*conf'"  ) ) );
$https_configs = explode( "\n", trim( run_cmd( "cd $httpdconfigdir && find * -name 'https-*conf'" ) ) );

$httpd_http_names  = [];
$httpd_https_names = [];

foreach ( $http_configs as $v ) {
    preg_match( '/^http-(.*).conf$/', $v, $matches );
    if ( count( $matches ) != 2 ) {
        $warnings .= "WARNING: $httpdconfigdir: could not find any domain name matching http-*conf\n";
    } else {
        $httpd_http_names[] = $matches[1];
    }
}

foreach ( $https_configs as $v ) {
    preg_match( '/^https-(.*).conf$/', $v, $matches );
    if ( count( $matches ) != 2 ) {
        $warnings .= "WARNING: $httpdconfigdir: could not find any domain name matching https-*conf\n";
    } else {
        $httpd_https_names[] = $matches[1];
    }
}

$httpd_domain_ok = true;
if ( $httpd_https_names != $httpd_http_names ) {
    $warnings .= "WARNING: $httpdconfigdir: inconsistent sets of names from $httpdconfigdir '" . implode( ' ', $httpd_https_names ) . "' vs '" . implode( ' ', $httpd_http_names ) . "\n";
    $httpd_domain_ok = false;
}

if ( count( $httpd_https_names ) > 1 ) {
    $warnings .= "WARNING: $httpdconfigdir: more than one domain name specified '" . implode( ' ', $httpd_https_names ) . "'\n";
    $httpd_domain_ok = false;
}

if ( count( $httpd_http_names ) == 0 ) {
    $warnings .= "WARNING: $httpdconfigdir: no httpd http config found\n";
    $httpd_domain_ok = false;
}

if ( count( $httpd_http_names ) == 0 ) {
    $warnings .= "WARNING: $httpdconfigdir: no httpd https config found\n";
    $httpd_domain_ok = false;
}

if ( $httpd_domain_ok ) {
    $httpd_name = $httpd_https_names[0];
    if ( $httpd_name != $host_accepted ) {
        $warnings .= "WARNING: $httpdconfigdir: httpd domain name '$httpd_name' does not match accepted host '$host_accepted'\n";
    }
}

$httpd_domain_ok = !flush_warnings( "OK: $httpdconfigdir: found 1 domain name matching accepted host '$host_accepted'" );
echo warnings_summary();
echoline();

if ( !$new_domain_processing ) {
    exit(0);
}

if ( $host_accepted == $new_domain ) {
    $warnings .= "NOTICE: the new domain '$new_domain' matches the accepted host '$host_accepted'\n";
}
if ( $host_accepted != $old_domain ) {
    $warnings .= "NOTICE: the old domain '$old_domain' does not match the accepted host '$host_accepted'\n";
}

flush_warnings();
get_yn_answer( "Update files not matching the new domain '$new_domain'", true );

if ( get_yn_answer( "Update metadata?" ) ) {
    echo "Updating metadata\n";
    $query = "update newus3.metadata set dbhost='$new_domain',limshost='$new_domain' where status='completed' and dbhost='$old_domain'";
    $res = mysqli_query( $db_handle, $query );
    if ( !$res ) {
        write_logl( "db query failed : $query\ndb query error: " . mysqli_error($db_handle) . "\n" );
    };
}

if ( get_yn_answer( "Update php variables?" ) ) {
    echoline();
    echo "Checking '$listenconfig'\n";
    $org_contents = $contents = file_get_contents( $listenconfig );
    if ( $contents !== false && strlen( $contents ) ) {
        $contents = preg_replace( "/^(\\s*\\\$servhost\\s*=).*;/m", "\${1} \"$new_domain\";", $contents );
        $contents = preg_replace( "/^(\\s*\\\$host_name\\s*=).*;/m", "\${1} \"$new_domain\";", $contents );
        if ( $org_contents == $contents ) {
            echo "NOTICE: $listenconfig already contains variables set to '$new_domain', not updated\n";
        } else {
            backup_file( $listenconfig );
            if ( false === file_put_contents( $listenconfig, $contents ) ) {
                error_exit( "could not write $listenconfig" );
            }
            echo "UPDATED: $listenconfig\n";
        }
    }
    echoline();
    echo "Checking '$srvconfig'\n";
    $org_contents = $contents = file_get_contents( $srvconfig );
    if ( $contents !== false && strlen( $contents ) ) {
        $contents = preg_replace( "/^(\\s*\\\$org_site\\s*=).*;/m", "\${1} '$new_domain';", $contents );
        if ( $org_contents == $contents ) {
            echo "NOTICE: $srvconfig already contains variables set to '$new_domain', not updated\n";
        } else {
            backup_file( $srvconfig );
            if ( false === file_put_contents( $srvconfig, $contents ) ) {
                error_exit( "could not write $srvconfig" );
            }
            echo "UPDATED: $srvconfig\n";
        }
    }
    foreach ( $mysql_dbs as $k => $v ) {
        $instpath = "$wwwpath/uslims3/$k/config.php";
        echoline();
        echo "Checking '$instpath'\n";
        $org_contents = $contents = file_get_contents( $instpath );
        if ( $contents !== false && strlen( $contents ) ) {
            $contents = preg_replace( "/^(\\s*\\\$org_site\\s*=).*;/m", "\${1} '$new_domain/$k';", $contents );
            if ( $org_contents == $contents ) {
                echo "NOTICE: $instpath already contains variables set to '$new_domain', not updated\n";
            } else {
                backup_file( $instpath );
                if ( false === file_put_contents( $instpath, $contents ) ) {
                    error_exit( "could not write $instpath" );
                }
                echo "UPDATED: $instpath\n";
            }
        }
    }
    {
        $k = 'uslims3_newlims';
        $instpath = "$wwwpath/uslims3/$k/config.php";
        echoline();
        echo "Checking '$instpath'\n";
        $org_contents = $contents = file_get_contents( $instpath );
        if ( $contents !== false && strlen( $contents ) ) {
            $contents = preg_replace( "/^(\\s*\\\$org_site\\s*=).*;/m", "\${1} '$new_domain/$k';", $contents );
            if ( $org_contents == $contents ) {
                echo "NOTICE: $instpath already contains variables set to '$new_domain', not updated\n";
            } else {
                backup_file( $instpath );
                if ( false === file_put_contents( $instpath, $contents ) ) {
                    error_exit( "could not write $instpath" );
                }
                echo "UPDATED: $instpath\n";
            }
        }
    }

}

$sudocmds = '';

if ( get_yn_answer( "Update httpd config?" ) ) {
    foreach ( [ "http", "https" ] as $v ) {
        $httpcfbase    = "$v-$old_domain.conf";
        $httpcfbasenew = "$v-$new_domain.conf";
        $httpcf        = "$httpdconfigdir/$httpcfbase";
        $httpcfnew     = "$httpdconfigdir/$httpcfbasenew";
        echo "Checking '$httpcf'\n";
        if ( !file_exists( $httpcf ) ) {
            echo "WARNING: '$httpcf' does not exist, skipping\n";
        } else {
            $org_contents = $contents = file_get_contents( $httpcf );
            if ( $contents !== false && strlen( $contents ) ) {
                $contents = preg_replace( "/$old_domain/m", "$new_domain", $contents );
                $contents = preg_replace( '/^\s*(SSL|Include)/m', "# \${1}", $contents );
                if ( $org_contents == $contents ) {
                    echo "NOTICE: $httpcf already contains variables set to '$new_domain', not updated\n";
                } else {
                    $newfile = newfile_file( $httpcfbasenew, $contents );
                    $sudocmds .= "cp $cwd/$newfile $httpdconfigdir && rm $httpcf\n";
                }
                if ( $v == 'https' ) {
                    if ( preg_match( '/ssl\/self-/m', $contents ) ) {
                        $sudocmds .=
                            "openssl genrsa -out $ssl_self_dir/$ssl_self_key 2048\n"
                            . "openssl req -new -key $ssl_self_dir/$ssl_self_key -out $ssl_self_dir/$ssl_self_csr\n"
                            . "openssl x509 -in $ssl_self_dir/$ssl_self_csr -out $ssl_self_dir/$ssl_self_cert -req -signkey $ssl_self_dir/$ssl_self_key -days 3650\n"
                            ;
                    } else {
                        $sudocmds .=
                            "# might be already done:\ndnf install certbot python3-certbot-apache\n"
                            . "certbot --apache -d $new_domain\n"
                            ;
                    }
                } 
            }
        }
    }
}


if ( get_yn_answer( "Restart uslims services?" ) ) {
    echoline();
    echo run_cmd( "cd $us3bin && php services.php restart" );
}

echoline( '=' );
$sudocmds = trim( $sudocmds );
echo "to complete domain conversion run under sudo:
hostnamectl set-hostname $new_domain
$sudocmds
service httpd restart
";


