#!/usr/bin/perl -w

use strict;

# to be executed from /site

open(D, "sqlite3 ./data/db/production.db .dump |") or die "cannot dump DB: $!";

my%nick2runs = ();
my%rid2run = ();

while(<D>){
    if(m/INSERT INTO pref_runs VALUES/) {
        chomp;
        s/INSERT INTO pref_runs VALUES\(//;
        my($rid, $nick) = split(/,\'/, $_);
        $nick =~ s/\'\)\;//;
        if(! defined($nick2runs{$nick})) {
            $nick2runs{$nick} = [];
        }
        push @{$nick2runs{$nick}}, $rid;
        if(! defined($rid2run{$rid})) {
            $rid2run{$rid} = {};
        }
    }
    if(m/INSERT INTO preferences VALUES/) {
        chomp;
        s/INSERT INTO preferences VALUES\(//;
        my($rid, $iid, $pref) = split(/,/, $_);
        $pref =~ s/\)\;//;
        if(! defined($rid2run{$rid})) {
            $rid2run{$rid} = {};
        }
        $rid2run{$rid}->{$iid} = $pref;
    }
}
close D;

print "Read " . scalar(keys %nick2runs) . " nicks and " . scalar(keys %rid2run) . " runs\n";

my%currentRun = map { $_ => 0 } keys %nick2runs;

foreach my$i(0..$#ARGV) {
    print $ARGV[$i]. "\n";
    open(L, $ARGV[$i]) or die "cannot read: $!";
    my$current = "";
    my%sent = ();
    while(<L>) {
        chomp;
        my($nick,$rest) = m/^([^ ]+) (.*)/;
        if(!($nick eq $current)) {
            $current = $nick;
            %sent = ();
        }
        $_=$rest;
        #print "$_\n";
        if(m/^\d+ (0|(-?1))$/) {
            # sent
            my($item, $pref) = m/^(\d+) (0|(-?1))/;
            $sent{$item} = $pref;
        }elsif(m/^INFO received /){
            my($item, $pref) = m/^INFO received (\d+) (0|(-?1))/;
            my$cr = $currentRun{$nick};
            die "received '$_' ($item, $pref) but " . join("=>", %sent). " for (nick $nick, run $cr)\n" unless $sent{$item} == $pref;
            if($cr < scalar(@{$nick2runs{$nick}})) {
                my$rid = $nick2runs{$nick}->[$cr];
                my$inDB = $rid2run{$rid}->{$item};
                die "received '$_' ($item, $pref) but DB has no $item in run $rid (nick $nick, run # $cr)\n" unless defined($inDB);
                die "received '$_' ($item, $pref) but DB has $inDB for $item in run $rid (nick $nick, run # $cr)\n" unless $inDB == $pref;
            }
        }elsif(m/INFO run finished\-+/){
            $currentRun{$nick}++;
        }
    }
}
