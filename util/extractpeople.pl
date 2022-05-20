#!/usr/bin/perl

$notes = "usage: $0 file

extracts the people table data from a standard lims sqldump

e.g. $0 <(zcat /srv/data/sqldumps/uslims3_XYZ...sql.gz)


";


$f = shift || die $notes; 

open IN, $f;

while (<IN>) {
    next if !$inpeople && !/INSERT INTO `people`/;
    if ( !$inpeople ) {
        $res .= $_;
        $inpeople++;
        next;
    }
    last if /ENABLE KEYS/;
    $res .= $_;
}

$res =~ s/INSERT INTO `people` VALUES //;
$res =~ s/\),\(/)\n(/g;

print $res;

    
    
