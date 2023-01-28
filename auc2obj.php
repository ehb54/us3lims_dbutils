<?php
{};

function auc2obj( $aucdata, $debug = 0 ) {
    # echo "auc2obj()\n";
    $pos = 0;
    $magic_number   = substr( $aucdata, $pos, 4 ); $pos += 4;
    $format_version = substr( $aucdata, $pos, 2 ); $pos += 2;
    $format_type    = substr( $aucdata, $pos, 2 ); $pos += 2;
    $cell           = ord( substr( $aucdata, $pos, 1 ) ); $pos += 1;
    $channel        = substr( $aucdata, $pos, 1 ); $pos += 1;
    $guid           = substr( $aucdata, $pos, 16 ); $pos += 16;
    $description    = trim( substr( $aucdata, $pos, 240 ) ); $pos += 240;
    $minimum_radius = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $maximum_radius = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $radius_delta   = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $minimum_data1  = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $maximum_data1  = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $minimum_data2  = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $maximum_data2  = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
    $scan_count     = implode( ':', unpack( 's', substr( $aucdata, $pos, 2 ) ) ); $pos += 2;

    if ( $debug ) {
        echo sprintf( 
            "magic_number   %s\n"
            . "format_version %s\n"
            . "format_type    %s\n"
            . "cell           %s\n"
            . "channel        %s\n"
            . "guid           %s\n"
            . "desc           %s\n"
            . "minimum_radius %.3f\n"
            . "maximum_radius %.3f\n"
            . "minimum_data1  %.3f\n"
            . "maximum_data1  %.3f\n"
            . "minimum_data2  %.3f\n"
            . "maximum_data2  %.3f\n"
            . "scan_count     %d\n"

            ,$magic_number
            ,$format_version
            ,$format_type
            ,$cell
            ,$channel
            ,bin2hex($guid)
            ,$description
            ,$minimum_radius
            ,$maximum_radius
            ,$minimum_data1
            ,$maximum_data1
            ,$minimum_data2
            ,$maximum_data2
            ,$scan_count
            );
    }

    $no_sd = $minimum_data2 == 0 && $maximum_data2 == 0;

    $result = (object)[];

    $result->scans         = intval($scan_count);
    $result->seconds       = [];    
    $result->omega2t       = [];    
    $result->rpm           = [];    
    $result->temperature   = [];    
    $result->radius_start  = floatval( sprintf( "%.3f", $minimum_radius ) );
    $result->radius_end    = floatval( sprintf( "%.3f", $maximum_radius ) );

    for ( $i = 0; $i < ( $debug ? 2 : $scan_count ); ++$i ) {
        $magic_number   = substr( $aucdata, $pos, 4 ); $pos += 4;
        $temperature    = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $rpm            = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $seconds        = implode( ':', unpack( 'I', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $omega2t        = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $wavelength     = implode( ':', unpack( 's', substr( $aucdata, $pos, 2 ) ) ); $pos += 2;
        $wavelength     = ($wavelength / 100) + 180;
        
        $rdelta =  substr( $aucdata, $pos, 4 );

        $radius_delta   = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $reading_count  = implode( ':', unpack( 'l', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $crdelta = floatval( sprintf( "%.6f", ( $maximum_radius - $minimum_radius ) / ($reading_count - 1) ) );
        
        ## reading data
        $pos += $reading_count * ( $no_sd ? 2 : 4 );
        ## interpolation flag
        $interpolation_flag = substr( $aucdata, $pos, ceil( $reading_count / 8 ) ); $pos += ceil( $reading_count / 8 );

        $result->temperature[] = floatval( sprintf( "%.3f", $temperature ) );
        $result->rpm[]         = floatval( $rpm );
        $result->seconds[]     = intval( $seconds );
        $result->omega2t[]     = floatval( $omega2t );

        if ( !$i ) {
            ## assume constant radial points
            $result->radial_points = intval( $reading_count );

            ## radius_delta is 0 for interpolated data
            $result->radius_delta  = empty( trim( $interpolation_flag ) ) ? $radius_delta : $crdelta;
        }

        if ( $debug ) {
            echo sprintf( 
                "scan            $i\n"
                . "magic_number    %s\n"
                . "temperature     %f\n"
                . "rpm             %f\n"
                . "seconds         %f\n"
                . "omega2t         %f\n"
                . "wavelength      %f\n"
                . "radius_delta    %.8f\n"
                . "computed rdelta %f\n"
                . "reading_count   %d\n"
                . "interpolation   %s ... %s\n"

                ,$magic_number
                ,$temperature
                ,$rpm
                ,$seconds
                ,$omega2t
                ,$wavelength
                ,$radius_delta
                ,$crdelta
                ,$reading_count
                ,bin2hex(substr( $interpolation_flag, 0, 6 )),bin2hex(substr( $interpolation_flag, -6 ))
                );
        }

    }

    return $result;
}
