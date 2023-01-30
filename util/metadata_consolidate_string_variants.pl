#!/usr/bin/perl

use JSON;
use File::Basename;
# use Data::Dumper;

$scriptdir = dirname(__FILE__);
require "$scriptdir/utility.pm";

$notes = "usage: $0 files

consolidates metadata string variants


";

die $notes if !@ARGV;

$summary = \{};

while ( $f = shift @ARGV ) {
    die "$f does not exist\n" if !-e $f;
    die "$f is not readable\n" if !-r $f;
    print "$f\n" if $debug;

    my $fcontents = `sed '0,/^string variants/d' $f`;

    $json_data = decode_json( $fcontents );
    while ( my ($k,$v) = each %{$json_data} ) {
        for my $k2 ( keys %{$v} ) {
            $summary{$k}{$k2}=1;
        }
    }
}

my $json = JSON->new;
print $json->pretty->encode( \%summary );


