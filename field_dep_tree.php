<?php

# developer defines
$debugcolor    = 0;
$logging_level = 2;
# end of developer defines

$self = __FILE__;
    
$notes = <<<__EOD
usage: $self dbname

create table foreign key dependency graph
requires graphviz command "dot"
.dot and .svg output files will be created

__EOD;

$config_file = "db_config.php";
if ( !file_exists( $config_file ) ) {
    fwrite( STDERR, "$self: 
$config_file does not exist

to fix:

cp ${config_file}.template $config_file
and edit with appropriate values
")
        ;
    exit(-1);
}
            
require "utility.php";
file_perms_must_be( $config_file );
require $config_file;

if ( count( $argv ) != 2 ) {
    echo $notes;
    exit;
}

$lims_db = $argv[ 1 ];

$outdot = "$lims_db.dot";
$outsvg = "$lims_db.svg";

$errors = "";
if ( file_exists( $outdot ) ) {
    $errors .= "$outdot exists, you must remove this first\n";
}
if ( file_exists( $outsvg ) ) {
    $errors .= "$outsvg exists, you must remove this first\n";
}

if ( strlen( $errors ) ) {
    fwrite( STDERR, $errors );
    exit(-1);
}

# main

$db_handle = mysqli_connect( $dbhost, $user, $passwd, $lims_db );
if ( !$db_handle ) {
    write_logl( "could not connect to mysql: $dbhost, $user, $lims_db. exiting\n" );
    exit(-1);
}

$res = db_obj_result( $db_handle, "show tables from $lims_db", true );
$tables = [];
while( $row = mysqli_fetch_array($res) ) {
    $tables[] = $row[ "Tables_in_$lims_db" ];
}
# debug_json( "tables", $tables);

$create = (object)[];

foreach ( $tables as $k => $v ) {
    $q = "show create table $v";
    $res = db_obj_result( $db_handle, $q );
    $constraints = preg_replace( "/^.*FOREIGN KEY \(`(\S+)`\) REFERENCES `(\S+)` \(`(\S+)`\).*$/", "$v $1 $2 $3", array_values( preg_grep( "/^\s*CONSTRAINT/", explode( "\n", $res->{ "Create Table" } ) ) ) );
    $create->{$v} = (object)[];
    foreach ( $constraints as $k2 => $v2 ) {
        $tokens = explode( " ", $v2 );
        $create->{$v}->{'parenttable'}[] = $tokens[ 0 ];
        $create->{$v}->{'parentfield'}[] = $tokens[ 1 ];
        $create->{$v}->{'childtable' }[] = $tokens[ 2 ];
        $create->{$v}->{'childfield' }[] = $tokens[ 3 ];
    }
    if ( !count( (array) $create->{$v} ) ) {
        unset( $create->{$v} );
    }
}
# debug_json( "create", $create );


$out =
"digraph F {
node [shape=\"box\",style=\"rounded\"];
concentrate=true;
ranksep=1.5;
rankdir=LR;
labelloc=\"t\";
label=\"$lims_db foreign key dependencies\"
";

$colors = [
    "rosybrown",
    "darkgreen",
    "crimson",
    "darkslategray",
    "orangered",
    "darkorange",
    "yellowgreen",
    "salmon",
    "indianred",
    "turquoise",
    "slategrey",
    "darkkhaki",
    "mediumvioletred",
    "orchid",
    "red",
    "burlywood",
    "palevioletred",
    "black",
    "firebrick",
    "blue",
    "lavenderblush",
    "dimgrey",
    "navy",
    "mediumaquamarine",
    "mediumslateblue",
    "mediumpurple",
    "cadetblue",
    "plum",
    "chartreuse",
    "cyan",
    "lawngreen",
    "deepskyblue",
    "blueviolet",
    "darkseagreen",
    "darksalmon",
    "darkviolet",
    "brown",
    "limegreen",
    "seagreen",
    "mediumturquoise",
    "violet",
    "forestgreen",
    "saddlebrown",
    "darkgoldenrod",
    "darkslateblue",
    "darkslategrey",
    "peru",
    "magenta",
    "mintcream",
    "purple",
    "maroon",
    "lightslategrey",
    "chocolate",
    "orange",
    "lightslategray",
    "greenyellow",
    "olivedrab",
    "lightseagreen",
    "mediumseagreen",
    "indigo",
    "slateblue",
    "coral",
    "slategray",
    "darkturquoise",
    "dodgerblue",
    "sandybrown",
    "powderblue",
    "hotpink",
    "lightskyblue",
    "lightcoral",
    "darkorchid",
    "skyblue",
    "lightsalmon",
    "tan",
    "deeppink",
    "darkolivegreen",
    "mediumspringgreen",
    "steelblue",
    "tomato",
    "mediumblue",
    "cornflowerblue",
    "palegreen",
    "springgreen",
    "royalblue",
    "green",
    "midnightblue",
    "mediumorchid",
    "sienna",
    "goldenrod",
    "pink"
    ];

$pos = 0;

foreach ( $create as $k => $v ) {
    for ( $i = 0; $i < count( (array)$v->{'parenttable'} ); ++$i ) {
        $label = $v->{'parentfield'}[$i];
        if ( $label != $v->{'childfield'}[$i] ) {
            $label .= " -> " . $v->{'childfield'}[$i];
        }
        if ( isset( $debugcolor ) && $debugcolor ) {
            $out .= '"' . $v->{'parenttable'}[$i] . '" -> "' . $v->{'childtable'}[$i] . "\" [label=\"$label $colors[$pos]\",color=$colors[$pos],fontcolor=$colors[$pos]];\n";
        } else {
            $out .= '"' . $v->{'parenttable'}[$i] . '" -> "' . $v->{'childtable'}[$i] . "\" [label=\"$label\",color=$colors[$pos],fontcolor=$colors[$pos]];\n";
        }
        $pos++;
        $pos = $pos % count( $colors );
    }
}
$out .= "}\n";
file_put_contents( $outdot, $out );
$cmd = "dot -Tsvg $outdot > $outsvg";
echo `$cmd`;
