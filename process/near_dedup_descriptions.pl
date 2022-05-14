#!/usr/bin/perl -w

use strict;
use B;

# read and filter exact duplicates
my %descr = ();
while(<STDIN>){
    chomp;
    my($title, $descr)= m/^([^\t]+)\t(.*)$/;
    my $entry = $descr{$descr};
    if(! defined($entry)){
        $entry = [];
        $descr{$descr} = $entry;
    }
    push @$entry, $title;
}

print STDERR "Found ".scalar(keys(%descr))." unique descriptions\n";

# transform to ids
my@descr=();
foreach my$descr(keys %descr) {
    my@entries = @{$descr{$descr}};
    my$id = scalar(@descr);
    push@descr, [ $descr, $id, $entries[$#entries] ];
    $descr{$descr} = $id;
}

# comoute character n-grams
my$SIZE=10;
my%ngrams = ();
foreach my$entry(@descr) {
    my$descr = $entry->[0];
    my$id = $entry->[1];

    foreach my$i(0..(length($descr) - $SIZE)) {
        next if rand() < 0.9;
        my $ngram=substr($descr, $i, $SIZE);
        my $nhash = hex(B::hash($ngram));
        my $nentry = $ngrams{$nhash};
        if(! defined($nentry)){
            $nentry = [ scalar(keys %ngrams) ];
            $ngrams{$nhash} = $nentry;
        }
        push @$nentry, $id;
        push @$entry, $nentry;
    }
}

print STDERR "Found ".scalar(keys %ngrams)." ngrams\n";

# remove near duplicates
foreach my$nentry(values %ngrams) {
    my@current = ();
    my@all = @$nentry;
    my$nid = shift@all;
    foreach my$i(@all){
        push @current, $i if $descr[$i]->[0];
    }
    next unless scalar(@current) > 2;

    foreach my$i0(0..($#current - 1)) {
        my@ngrams0 = @{$descr[$i0]};
        my$descr0 = shift@ngrams0;
        shift@ngrams0; # id
        shift@ngrams0; # file
        my%ngrams0 = map { $_ => 1 } @ngrams0;
        my@same = ();
        foreach my$i1(($i0+1)..$#current) {
            my@ngrams1 = @{$descr[$i1]};
            my$descr1 = shift@ngrams1;
            next unless $descr1;
            shift@ngrams1; # id
            shift@ngrams1; # file
            my$overlap = 0;
            foreach my$nid1(@ngrams1) {
                $overlap++ if $ngrams0{$nid1};
            }
            if($overlap > length($descr1)*(length($descr1)-1)/2 * 0.5) {
                push@same, $i1;
            }
        }
        if(@same) {
            foreach my$in(0..@same){
                $descr[$in]->[0] = "";
            }
            if($#same > 9) {
                # nuke
                $descr[$i0]->[0] = "";
            }
        }
    }
}

my$c=0;
foreach my$entry(@descr) {
    my$descr = $entry->[0];
    next unless $descr;
    print $entry->[2]."\t$descr\n";
    $c++;
}
print STDERR "Kept $c non-duplicate entries\n";

