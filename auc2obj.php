<?php
{};

function auc2obj( $aucdata ) {
    echo "auc2obj()\n";
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
        ,$guid
        ,$description
        ,$minimum_radius
        ,$maximum_radius
        ,$minimum_data1
        ,$maximum_data1
        ,$minimum_data2
        ,$maximum_data2
        ,$scan_count
        );

    #    for ( $i = 0; $i < $scan_count; ++$i ) {

    $no_sd = $minimum_data2 == 0 && $maximum_data2 == 0;

    for ( $i = 0; $i < $scan_count; ++$i ) {
        $magic_number   = substr( $aucdata, $pos, 4 ); $pos += 4;
        $temperature    = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $rpm            = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $seconds        = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $omega2t        = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $wavelength     = implode( ':', unpack( 's', substr( $aucdata, $pos, 2 ) ) ); $pos += 2;
        $wavelength     = ($wavelength / 100) + 180;
        
        $radius_delta   = implode( ':', unpack( 'f', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;
        $reading_count  = implode( ':', unpack( 'l', substr( $aucdata, $pos, 4 ) ) ); $pos += 4;

        echo sprintf( 
            "scan           $i\n"
            . "magic_number   %s\n"
            . "temperature    %f\n"
            . "rpm            %f\n"
            . "seconds        %f\n"
            . "omega2t        %f\n"
            . "wavelength     %f\n"
            . "radius_delta   %f\n"
            . "reading_count  %d\n"

            ,$magic_number
            ,$temperature
            ,$rpm
            ,$seconds
            ,$omega2t
            ,$wavelength
            ,$radius_delta
            ,$reading_count
            );

        ## reading data
        $pos += $reading_count * ( $no_sd ? 2 : 4 );
        ## interpolation flag
        $pos += ceil( $reading_count / 8 );
    }
    


}
