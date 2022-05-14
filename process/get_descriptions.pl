#!/usr/bin/perl -w

use strict;

my$title;
my$c=0;
while(<STDIN>) {
    if(m/\<title\>/) {
        chomp;
        s/\s*\<\/?title\>//g;
        $title = $_;
    }elsif($c){
        if(m/\[\[/){
            my@parts = split(/\[\[/, $_);
            my$text = shift(@parts);
            foreach my$part(@parts){
                my($begin,$rest) = split(/\]\]/, $part);
                if($begin =~ m/\|/){
                    my@subparts = split(/\|/, $begin);
                    $text .= " " . $subparts[$#subparts] . " ";
                }elsif($begin =~ m/\:/){
                    my@subparts = split(/:/, $begin);
                    $text .= " " . $subparts[$#subparts] . " ";                    
                }else{
                    $text .= " " . $begin . " ";
                }
                $text .= $rest if $rest;
            }
            $_ = $text;
        }
        if($c == 2) {
            chomp;
            print "\t$_";
            my@o = m/(\{\{)/g;
            my@c = m/(\}\})/g;
            if($#c > $#o){
                $c=1;
            }
        }elsif(m/(:?(?!\{\{).)*\|/){ # https://stackoverflow.com/a/14127564
            print"\n";
            $c=0;
        }else{
            chomp;
            print "\t$_";
        }
    }elsif(m/description\s*=/){
        chomp;
        s/.*description\s*=\s*//;
        if($title){
            if(m/\[\[/){
                my@parts=split(/\[\[/, $_);
                my$text = shift(@parts);
                foreach my$part(@parts){
                    my($begin,$rest) = split(/\]\]/, $part);
                    if($begin =~ m/\|/){
                        my@subparts = split(/\|/, $begin);
                        $text .= " " . $subparts[$#subparts] . " ";
                    }elsif($begin =~ m/\:/){
                        my@subparts = split(/:/, $begin);
                        $text .= " " . $subparts[$#subparts] . " ";                    
                    }else{
                        $text .= " " . $begin . " ";
                    }
                    $text .= $rest if $rest;
                }
                $_ = $text;
            }
            if(m/^(:?(?!\{\{).)*\|/){
                s/\|.*//;
                print"$title\t$_\n";
                $title="";
            }else{
                print"$title\t$_";
                $title="";
                $c=1;
                my@o = m/(\{\{)/g;
                my@c = m/(\}\})/g;
                if($#o > $#c){
                    $c=2;
                }
            }
        }
    }
}
