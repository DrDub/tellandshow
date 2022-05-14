#!/usr/bin/perl -w

use strict;

while(<STDIN>){
    chomp;
    my@parts = split(/\t/, $_);
    my$title = shift(@parts);
    my$text = join(" ", @parts);
    if($text=~m/\{\{LangSwitch/ || $text=~m/\{\{Mld/){
        @parts = split(/|/, $text);
        shift@parts;
        $text="";
        foreach my$part(@parts){
            if($part=~m/\s*en\s*=/){
                $part=~s/\s*en\s*=\s*//;
                $text=$part;
            }
        }        
    }elsif($text=~m/\{\{/){
        @parts = split(/\}\}\s*\{\{/, $text);
        $parts[0] =~ s/\s*\{\{s\*//;
        $parts[$#parts] =~ s/\}\}\s*$//;
        $text="";
        foreach my$part(@parts){
            if($part=~m/en\|/){
                $part=~s/.*en\|//;
                $text=$part;
            }
        }
        if(! $text && scalar(@parts) == 1 && $parts[0] !~ m/\|/){
            $text = $parts[0];
        }
    }
    $text=~s/This image was copied from [^ ]*wikipedia.org. The original description was: //;
    $text=~s/\{\{w\|//g;
    $text=~s/\}\}//g;    
    $text=~s/^\s*\d\s*=\s*//;
    $text=~s/\&quot;/"/g;
    $text=~s/\&amp;/&/g;
    $text=~s/\&nbsp;/ /g;
    $text=~s/&lt;/</g;
    $text=~s/&gt;/>/g;
    $text=~s/\'\'\'?//g;
    $text=~s/\=\=.*//;
    $text=~s/\<[^>]+\>/ /g;
    $text=~s/^\s*=//;
    if($text=~m/\[[^\]]+\]/){
        @parts = split(/\[/, $text);
        $text = shift@parts;
        foreach my$part(@parts){
            my($begin, $rest) = split(/\]/, $part);
            if($begin=~m/ /){
                $begin=~s/^[^ ]* //;
            }
            $text .= $begin;
            $text .= $rest if $rest;
        }
    }
    print "$title\t$text\n" unless length($text) < 128;
}
