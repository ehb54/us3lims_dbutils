<?php

# developer defines
$debug = 1;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self {options} {db_config_file}

rename table column name

Options

--help               : print this information and exit

--db dbname          : (required) specify the database name 
--short              : only ID, description
--dbcmp dbname       : compare values with db (trimmed description)
--details            : show details when compare
--cmp-exponent n     : when comparing numeric differences, accept abs( diff ) <= 10^-n
--skip-same          : skip entries with no differences
--skip-c-range       : ignore c_range comparisons

__EOD;

require "utility.php";
$u_argv = $argv;
array_shift( $u_argv ); # first element is program name

$dbname              = "";
$short               = false;
$dbcmp               = "";
$details             = false;
$doexpnt             = false;
$cmp_expnt           = 10;
$skip_c_range        = false;
$skip_same           = false;

while( count( $u_argv ) && substr( $u_argv[ 0 ], 0, 1 ) == "-" ) {
    switch( $u_argv[ 0 ] ) {
        case "--help": {
            echo $notes;
            exit;
        }
        case "--db": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --db requires an argument\n\n$notes" );
            }
            $dbname = array_shift( $u_argv );
            if ( empty( $dbname ) ) {
                error_exit( "--db requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--dbcmp": {
            array_shift( $u_argv );
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --dbcmp requires an argument\n\n$notes" );
            }
            $dbcmp = array_shift( $u_argv );
            if ( empty( $dbcmp ) ) {
                error_exit( "--dbcmp requires a non-empty value\n\n$notes" );
            }
            break;
        }
        case "--short": {
            array_shift( $u_argv );
            $short = true;
            break;
        }
        case "--details": {
            array_shift( $u_argv );
            $details = true;
            break;
        }
        case "--skip-c-range": {
            array_shift( $u_argv );
            $skip_c_range = true;
            break;
        }
        case "--skip-same": {
            array_shift( $u_argv );
            $skip_same = true;
            break;
        }
        case "--cmp-exponent": {
            array_shift( $u_argv );
            $doexpnt = true;
            if ( !count( $u_argv ) ) {
                error_exit( "\nOption --cmp-exponent requires an argument\n\n$notes" );
            }
            $cmp_expnt = array_shift( $u_argv );
            if ( !strlen( $cmp_expnt ) ) {
                error_exit( "--cmp-exponent requires a non-empty value\n\n$notes" );
            }
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

if ( empty( $dbname ) ) {
    echo $notes;
    exit;
}

$db_handle = mysqli_connect( $dbhost, $user, $passwd, "" );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user exiting\n" );
    exit(-1);
}


# list - default option
if ( empty( $dbcmp ) ) {
    $q = "select * from $dbname.bufferComponent";

    if ( $short ) {
        $fmt = "%-7s | %-40s\n";
    } else {
        $fmt = "%-7s | %-10s | %-4s | %-40s\n";
    }
        
    $len = 60;

    echoline( "-", $len );
    print sprintf( $fmt, "ID", "CRC32", "dlen", "description" );
    echoline( "-", $len );

    $res = db_obj_result( $db_handle, $q, True );
    while( $row = mysqli_fetch_array($res) ) {
        $rowo = (object) $row;
        if ( $short ) {
            print sprintf( $fmt
                           ,$rowo->bufferComponentID
                           ,trim($rowo->description)
                );
        } else {
            print sprintf( $fmt
                           ,$rowo->bufferComponentID
                           ,dechex( crc32( $rowo->units . $rowo->viscosity . $rowo->density . $rowo->c_range ) )
                           ,strlen( $rowo->description )
                           ,trim($rowo->description)
                );
        }            
    }
    echoline( "-", $len );
    exit();
}

function compare2( $field, $obj1, $obj2 ) {
    global $doexpnt;
    global $cmp_expnt;
    global $skip_c_range;

    switch( $field ) {
        case "units" :
            return $obj1->{ $field } == $obj2->{ $field };

        case "description" :
            return trim( $obj1->{ $field } ) == trim( $obj2->{ $field } );

        case "viscosity" :
        case "density" :

            $nums1 = explode( ' ', $obj1->{ $field } );
            $nums2 = explode( ' ', $obj2->{ $field } );
            if ( count( $nums1 ) != count( $nums2 ) ) {
                return false;
            }
            for ( $i = 0; $i < count( $nums1 ); ++$i ) {
                if ( $doexpnt ) {
                    if ( abs( $nums1[ $i ] - $nums2[ $i ] ) > 10**-$cmp_expnt ) {
                        return false;
                    }
                } else {
                    if ( floatval( $nums1[ $i ] ) != floatval( $nums2[ $i ] ) ) {
                        return false;
                    }
                }                    
            }
            return true;
            
        case "c_range" :
            
            if ( $skip_c_range ) {
                return true;
            }

            $vals1 = explode( ' ', $obj1->{ $field } );
            $vals2 = explode( ' ', $obj2->{ $field } );
            if ( count( $vals1 ) != count( $vals2 ) ) {
                return false;
            }

            if ( $vals1[ 1 ] != $vals2[ 1 ] ) {
                return false;
            }

            $nums1 = explode( '-', $vals1[ 0 ] );
            $nums2 = explode( '-', $vals2[ 0 ] );

            if ( count( $nums1 ) != count( $nums2 ) ) {
                return false;
            }

            for ( $i = 0; $i < count( $nums1 ); ++$i ) {
                if ( $doexpnt ) {
                    if ( abs( $nums1[ $i ] - $nums2[ $i ] ) > 10**-$cmp_expnt ) {
                        return false;
                    }
                } else {
                    if ( floatval( $nums1[ $i ] ) != floatval( $nums2[ $i ] ) ) {
                        return false;
                    }
                }
            }

            return true;

        case "gradientForming" :
            return $obj1->{ $field } == $obj2->{ $field };
    }
}


if ( $dbcmp ) {
    # compare values for each key

    $fmt = "%-7s | %-7s | %-7s | %-7s | %-7s | %-7s | %-7s | %-s\n";

    $len = 84;

    echoline( "-", $len );
    print sprintf( $fmt
                   , "ID"
                   , "desc"
                   , "units"
                   , "visc"
                   , "dens"
                   , "c_range"
                   , "grad_f"
                   , "notes"
        );
        
    echoline( "-", $len );

    $q = "select * from $dbname.bufferComponent";

    $res = db_obj_result( $db_handle, $q, True );
    while( $row = mysqli_fetch_array($res) ) {
        $rowo = (object) $row;

        $q2   = "select * from $dbcmp.bufferComponent where bufferComponentID = $rowo->bufferComponentID";
        $res2 = db_obj_result( $db_handle, $q2, false );

        # debug_json( "result from $dbcmp for id $rowo->bufferComponentID", $res2 );
        
        if ( !$skip_same
             || !compare2( 'description', $rowo, $res2 ) 
             || !compare2( 'units', $rowo, $res2 )
             || !compare2( 'viscosity', $rowo, $res2 )
             || !compare2( 'density', $rowo, $res2 )
             || !compare2( 'c_range', $rowo, $res2 )
             || !compare2( 'gradientForming', $rowo, $res2 )
            ) {
            print sprintf( $fmt
                           ,$rowo->bufferComponentID
                           ,compare2( 'description', $rowo, $res2 ) ? "same" : "differ"
                           ,compare2( 'units', $rowo, $res2 ) ? "same" : "differ"
                           ,compare2( 'viscosity', $rowo, $res2 ) ? "same" : "differ"
                           ,compare2( 'density', $rowo, $res2 ) ? "same" : "differ"
                           ,compare2( 'c_range', $rowo, $res2 ) ? "same" : "differ"
                           ,compare2( 'gradientForming', $rowo, $res2 ) ? "same" : "differ"
                           ,trim( $rowo->description )
                );

            if ( $details ) {
                foreach ( [ "description", "units", "viscosity", "density", "c_range", "gradientForming" ] as $v ) {
                    if ( !compare2( $v, $rowo, $res2 ) ) {
                        print sprintf(
                            "%-12s : %-20s : %-s\n"
                            . "%-12s : %-20s : %-s\n"
                            ,$dbname
                            ,$v
                            ,$rowo->{$v}
                            ,$dbcmp
                            ,$v
                            ,$res2->{$v}
                            );
                    }
                }
                echoline( "-", $len );
            }
        }
    }

    echoline( "-", $len );
}
