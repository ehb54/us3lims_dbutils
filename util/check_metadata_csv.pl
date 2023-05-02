#!/usr/bin/perl

use Data::Dumper;
use Scalar::Util qw(looks_like_number);

$separator      = ' ';
$comment        = "\t";

$notes = "usage: $0 {options} csvfile

lists number of columns, number of rows and unique column values for each column
options:
 -b : report only bad values
 -l : list counts & first occurance name

";

$f = shift || die $notes;

while ( $f =~ /^-/ ) {
    if ( $f eq "-b" ) {
        $badvalues_only++;
        $f = shift || die $notes;
        next;
    }
    if ( $f eq "-l" ) {
        $list_details++;
        $f = shift || die $notes;
        next;
    }
    die "unknown option $f\n";
}

@column_names = (
    '@attributes.method',
    'CPUCount',
    'edited_radial_points.0',
    'edited_radial_points.1',
    'edited_radial_points.2',
    'edited_radial_points.3',
    'edited_radial_points.4',
    'edited_radial_points.5',
    'edited_radial_points.6',
    'edited_radial_points.7',
    'edited_radial_points.8',
    'edited_radial_points.9',
    'edited_scans.0',
    'edited_scans.1',
    'edited_scans.2',
    'edited_scans.3',
    'edited_scans.4',
    'edited_scans.5',
    'edited_scans.6',
    'edited_scans.7',
    'edited_scans.8',
    'edited_scans.9',
    'job.cluster.@attributes.name',
    'job.jobParameters.bucket_fixed.@attributes.fixedtype',
    'job.jobParameters.bucket_fixed.@attributes.value',
    'job.jobParameters.bucket_fixed.@attributes.xtype',
    'job.jobParameters.bucket_fixed.@attributes.ytype',
    'job.jobParameters.conc_threshold.@attributes.value',
    'job.jobParameters.crossover.@attributes.value',
    'job.jobParameters.curve_type.@attributes.value',
    'job.jobParameters.curves_points.@attributes.value',
    'job.jobParameters.demes.@attributes.value',
    'job.jobParameters.elitism.@attributes.value',
    'job.jobParameters.ff0_grid_points.@attributes.value',
    'job.jobParameters.ff0_max.@attributes.value',
    'job.jobParameters.ff0_min.@attributes.value',
    'job.jobParameters.ff0_resolution.@attributes.value',
    'job.jobParameters.generations.@attributes.value',
    'job.jobParameters.gfit_iterations.@attributes.value',
    'job.jobParameters.k_grid.@attributes.value',
    'job.jobParameters.max_iterations.@attributes.value',
    'job.jobParameters.mc_iterations.@attributes.value',
    'job.jobParameters.meniscus_points.@attributes.value',
    'job.jobParameters.meniscus_range.@attributes.value',
    'job.jobParameters.migration.@attributes.value',
    'job.jobParameters.mutate_sigma.@attributes.value',
    'job.jobParameters.mutation.@attributes.value',
    'job.jobParameters.p_mutate_k.@attributes.value',
    'job.jobParameters.p_mutate_s.@attributes.value',
    'job.jobParameters.p_mutate_sk.@attributes.value',
    'job.jobParameters.plague.@attributes.value',
    'job.jobParameters.population.@attributes.value',
    'job.jobParameters.regularization.@attributes.value',
    'job.jobParameters.req_mgroupcount.@attributes.value',
    'job.jobParameters.rinoise_option.@attributes.value',
    'job.jobParameters.s_grid.@attributes.value',
    'job.jobParameters.s_grid_points.@attributes.value',
    'job.jobParameters.s_max.@attributes.value',
    'job.jobParameters.s_min.@attributes.value',
    'job.jobParameters.s_resolution.@attributes.value',
    'job.jobParameters.seed.@attributes.value',
    'job.jobParameters.solute_type.@attributes.value',
    'job.jobParameters.thr_deltr_ratio.@attributes.value',
    'job.jobParameters.tikreg_alpha.@attributes.value',
    'job.jobParameters.tikreg_option.@attributes.value',
    'job.jobParameters.tinoise_option.@attributes.value',
    'job.jobParameters.uniform_grid.@attributes.value',
    'job.jobParameters.vars_count.@attributes.value',
    'job.jobParameters.x_max.@attributes.value',
    'job.jobParameters.x_min.@attributes.value',
    'job.jobParameters.y_max.@attributes.value',
    'job.jobParameters.y_min.@attributes.value',
    'job.jobParameters.z_value.@attributes.value',
    'simpoints.0',
    'simpoints.1',
    'simpoints.2',
    'simpoints.3',
    'simpoints.4',
    'simpoints.5',
    'simpoints.6',
    'simpoints.7',
    'simpoints.8',
    'simpoints.9',
    'CPUTime',
    'max_rss',
    'wallTime'
    );


die "$f does not exist\n" if !-e $f;
die "$f in not readable\n" if !-e $f;

open $fh, $f || die "$f $!\n";

while ( $l = <$fh> ) {
    chomp $l;
    my ( $name ) = $l =~ /$comment(.*)$/;
    $l =~ s/$comment.*$//;
    @l = split /$separator/, $l;
    for ( my $i = 0; $i < @l; ++$i ) {
        $values{ $column_names[$i] }{ $l[$i] }++;
        if ( !looks_like_number( $l[$i] ) ) {
            $bad_values{ $column_names[ $i ] }{ $l[$i] }++;
        }
        if ( !$first_occurance_name{ $column_names[ $i ] }{ $l[$i] } ) {
            $first_occurance_name{ $column_names[ $i ] }{ $l[$i] } = $name;
        }
    }
}

if ( !$badvalues_only ) {
    for $k ( keys %values ) {
        my $out = "$k :";
        # print Dumper( $values{$k} );
        
        if ( $list_details ) {
            $out .= "\n";
            for my $k2 ( keys % { $values{$k} } ) {
                my $use_k2 = $k2;
                $use_k2 = "_empty_" if $k2 eq '';
                $out .= "\t $use_k2 [$first_occurance_name{$k}{$k2},$values{$k}{$k2}]\n";
            }
        } else {
            for my $k2 ( keys % { $values{$k} } ) {
                my $use_k2 = $k2;
                $use_k2 = "_empty_" if $k2 eq '';
                $out .= " $use_k2";
            }
        }            

        for my $k2 ( keys % { $values{$k} } ) {
            $out .= " $k2";
        }
        push @outlines, $out . "\n";
    }
} else {
    for $k ( keys %bad_values ) {
        my $out = "$k :";
        # print Dumper( $bad_values{$k} );

        if ( $list_details ) {
            $out .= "\n";
            for my $k2 ( keys % { $bad_values{$k} } ) {
                my $use_k2 = $k2;
                $use_k2 = "_empty_" if $k2 eq '';
                $out .= "\t $use_k2 [$first_occurance_name{$k}{$k2},$bad_values{$k}{$k2}]\n";
            }
        } else {
            for my $k2 ( keys % { $bad_values{$k} } ) {
                my $use_k2 = $k2;
                $use_k2 = "_empty_" if $k2 eq '';
                $out .= " $use_k2";
            }
        }            

        push @outlines, $out . "\n";
    }
}

print sort { $a cmp $b } @outlines;
