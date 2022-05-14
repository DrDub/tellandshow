#!/usr/bin/perl -w

use strict;
use B;

# read and filter exact duplicates
my %descr = ();
while(<STDIN>){
    chomp;
    my($title, $descr)= m/^([^\t]+)\t(.*)$/;
    $descr=~s/\s+/ /g;
    $descr=~s/^\s+//;
    $descr=~s/\s+$//;
    my $entry = $descr{$descr};
    if(! defined($entry)){
        $entry = [];
        $descr{$descr} = $entry;
    }
    push @$entry, $title;
}

print STDERR "Found ".scalar(keys(%descr))." unique descriptions\n";

foreach my$descr(sort keys %descr) {
    my@entries = @{$descr{$descr}};
    print $entries[$#entries]."\t$descr\n";
}
