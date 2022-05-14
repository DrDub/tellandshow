<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

$WEBSITE = 'http://127.0.0.1//tands/';
$MAXITEMS = 6000;
$CUTOFF = 4850;
$NICKS = 2;

/*

each script generates $NICKS random nicks and populates 6000 calls for each

the interaction is made against the $WEBSITE/annotation.php

the items fetched and the random prefs are emitted also to stdout

checking whether the DB is correctly populated should be done outside this script

*/

$nicks = [];
$nickCount = [];
$nickState = [];
$nickBias = [];
$nickForgets = [];
$nickSeen = [];
foreach(range(0,$NICKS-1) as $r){
    $nick = "p" . getmypid() . "n$r";
    $nicks[] = $nick;
    $nickCount[$nick] = 0;
    $nickState[$nick] = [];
    $nickSeen[$nick] = [];
    $nickBias[$nick] = rand(4,6);
    $nickForgets[$nick] = rand(0,3) * 10;
    echo "$nick Bias: " . $nickBias[$nick] . "\n";
    echo "$nick Forgets: " . $nickForgets[$nick] . "\n";
}
$activeNicks = $nicks;

function post($nick, $params) {
    global $WEBSITE;
    $ch = curl_init("$WEBSITE/annotation.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 130);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    
    $page = curl_exec($ch);
    if(curl_error($ch)) {
        echo 'ERROR: ' . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return $page;
}

function parse($page, $nick) {
    $lines = explode("\n", $page);
    $items=[];
    $seen = [];
    $finished = false;
    foreach($lines as $line) {
        $matches = array();
        // <p><input type="radio" id="item_4322074_yes" name="item_4322074"
        preg_match('/name=\"item_(\d+)/', $line, $matches);
        if($matches) {
            $item = $matches[1];
            if(! isset($seen[$item])) {
                $seen[$item] = 1;
                $items[] = $item;
                echo "$nick $item\n";
            }
        }
        // <div class="alert alert-primary" role="alert">Continuing an existing annotation run for this nickname</div>
        preg_match('/alert alert-primary.*alert\">(.*)\<\/div/', $line, $matches);
        if($matches){
            echo "$nick INFO " . $matches[1]. "\n";
        }
        preg_match('/\-\- FINISHED\! /', $line, $matches);
        if($matches){
            echo "$nick INFO run finished------------------------------------------------------\n";
            $finished = true;
        }
    }
    echo "$nick        TOTAL " . count($items) . "\n";
    return [ $items, $finished ];
}

while($activeNicks) {
    shuffle($activeNicks);
    $nick = $activeNicks[0];
    if(! $nickState[$nick]) {
        echo "$nick Starting fresh\n";
        $page = post($nick, array( 'nick' => $nick, 'changenick' => 1 ));
        if($page) {
            $nickState[$nick] = parse($page, $nick)[0];
        }else{
            $nickCount[$nick] += 10; // error
            if($nickCount[$nick] > $MAXITEMS) {
                unset($activeNicks[0]);
            }
            continue;
        }
    }
    $items = $nickState[$nick];
    $params = array( 'nick' => $nick, 'annotate' => 1 );
    $log = "";
    foreach($items as $item) {
        if(rand(1,100) < $nickForgets[$nick]) {
            continue; // leave unannotated
        }
        if(rand(1,100) < 10){
            $pref = 0; $annot = 'dont_understand';
        }else{
            if(rand(1,10) < $nickBias[$nick]) {
                $pref = 1; $annot = 'yes';
            }else{
                $pref = -1;  $annot = 'no';
            }
        }
        $log .= "$nick $item $pref\n";
        $params['item_' . $item] = $annot;
    }
    $log .= "$nick      TOTAL SENT " . (count($params) - 2). "\n";
    $page = post($nick, $params);
    if($page) {
        echo $log;
        if(count($nickSeen[$nick]) < $CUTOFF - 20) {
            foreach($nickState[$nick] as $item) {
                if(isset($nickSeen[$nick][$item])) {
                    die("ERROR repeated item for $nick, item $item\n");
                }
                if(isset($params['item_' . $item])) {
                    $nickSeen[$nick][$item] = 1;
                }
            }
        }
        $sent = count($nickState[$nick]);
        [ $state, $finished ] = parse($page, $nick);
        if(! $finished) { 
            $nickState[$nick] = $state;
        }else{
            $nickState[$nick] = [];
        }
        $nickCount[$nick] += $sent;
        if($nickCount[$nick] > $MAXITEMS) {
            unset($activeNicks[0]);
        }
    } // else, will retry with previous state
    usleep(250000 * rand(1,6)); // 0.25 to 1.5s
    //sleep(rand(1,2));
}

