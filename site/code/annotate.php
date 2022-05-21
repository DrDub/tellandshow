<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

include "MyBallTree.php";

$MAXDISTS = 5000;
$DEBUG = false;

$newrun = false;
$newnick = false;
$finished = false;

$message=[];

if(isset($_REQUEST['nick'])) {
    $nick = preg_replace('/[^a-z0-9_]/', "", strtolower($_REQUEST['nick']));
    if(strlen($nick) < 4) {
        $message[] = $_['invalid_nick'];
        $newnick = true;
    }else{
        $nick = substr($nick, 0, 30);
    }
}

if(! $newnick && isset($_REQUEST['changenick'])){
    // custom nick
    $stmt = $db->prepare(<<<'END'
SELECT COUNT(*) FROM pref_runs
WHERE
   pref_runs.nonce = ? 
END
);
    $stmt->bindValue(1, $nick, SQLITE3_TEXT);
    $result = $stmt->execute();
    $count = $result->fetchArray()[0];
    if($count) {
        $message[] = $_['got_runs'] . " $count";
    }
    $runfile = dirname(__FILE__) . "/../data/runs/$nick.ser";
    if(file_exists($runfile)) {
        $message[] = $_['continuing'];
    }
}

if($newnick || !isset($_REQUEST['nick'])) {
    // new start, generate a random nick
    $colors = explode("\n", file_get_contents(dirname(__FILE__) . "/../data/colors.lst"));
    array_pop($colors);
    $nick = "unset";
    $repeated = false;
    do {
        shuffle($colors);
        $num = rand(1000, 9999);
        $nick = $colors[0] . $num;
        $runfile = dirname(__FILE__) . "/../data/runs/$nick.ser";
        if(file_exists($runfile)) {
            $repeated = true;
        }else{
            $stmt = $db->prepare(<<<'END'
SELECT * FROM pref_runs
WHERE
   pref_runs.nonce = ? 
END
);
            $stmt->bindValue(1, $nick, SQLITE3_TEXT);
            $result = $stmt->execute();
            $repeated = $result->fetchArray();
        }
    } while($repeated);
    $newrun = true;
}

$tree = unserialize(file_get_contents(dirname(__FILE__) . "/../data/run" .
                                      $db_cluster_run .
                                      $db_laser_version . "-tree.ser"));
$centers = $tree->centers;
$centersLen = count($centers);
$itemsLen = count($tree->itemdata);

$runfile = dirname(__FILE__) . "/../data/runs/$nick.ser";
if($newrun || (!file_exists($runfile))) {
    $oldannotated=[];
    if (! $newrun) {
        // fetch old runs
        $stmt = $db->prepare(<<<'END'
SELECT preferences.item FROM preferences LEFT JOIN
  pref_runs
ON preferences.run = pref_runs.rid
WHERE
   pref_runs.nonce = ? 
END
);
        $stmt->bindValue(1, $nick, SQLITE3_TEXT);
        $result = $stmt->execute();
        while($arr = $result->fetchArray(SQLITE3_NUM)) {
            $oldannotated[$arr[0]] = 1;
        }
        if(count($oldannotated) === count($tree->itemdata)){
            $oldannotated = []; // wrap-around
        }
    }
    // pick a node at random
    [ $fully_available_centers, $available_centers, $centerIdxToRemainingItems ] = $tree->getRemainingCentersAndItems($oldannotated);
    
    if(! $available_centers) {
        $message[]= $_['repeated_error']; 
        // something went wrong, we'll nuke oldannotated
        $available_centers = range(0, $centersLen - 1);
        $oldannotated = [];
    }
    shuffle($available_centers);
    $picked_center = $available_centers[0];
    $available_centers_set = array();
    foreach($available_centers as $centerIdx){
        $available_centers_set[$centerIdx] = 1;
    }

    // find another center as far as possible
    $other_center = $picked_center; // corner case, all that remains is in one clique
    $max_dist = 0;
    foreach($centers as $centerIdx=>$center) {
        if(isset($available_centers_set[$centerIdx])){
            $other_dist = $tree->centerDistance($picked_center, $centerIdx);
            if($other_dist > $max_dist) {
                $other_center = $centerIdx;
                $max_dist = $other_dist;
            }
        }
    }
    $candidates1 = $centers[$picked_center]->dataset()->labels();
    shuffle($candidates1);
    foreach($candidates1 as $picked_item) {
        if(! isset($oldannotated[$picked_item])){
            break;
        }
    }
    $candidates2 = $centers[$other_center]->dataset()->labels();
    shuffle($candidates2);
    foreach($candidates2 as $other_item) {
        if((! isset($oldannotated[$other_item])) && $picked_item !== $other_item){
            break;
        }
    }
    $picked_cluster = $tree->itemdata[$picked_item][0];
    $other_cluster  = $tree->itemdata[$other_item][0];

    $toannotate = [];
    $progress = 0;
    $stmt = $db->prepare(<<<'END'
SELECT descriptions.item, descriptions.description, urls.url FROM embeddings_laser LEFT JOIN
   descriptions
ON descriptions.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
LEFT JOIN
   langs
ON descriptions.lang = langs.lid
LEFT JOIN
   urls
ON descriptions.item = urls.item
WHERE
   (cluster_labels.cluster = ? OR cluster_labels.cluster = ?) AND
   langs.code = ? AND
   cluster_labels.run = ? AND
   embeddings_laser.version = ?
END
);
    $stmt->bindValue(1, $picked_cluster, SQLITE3_INTEGER); 
    $stmt->bindValue(2, $other_cluster,  SQLITE3_INTEGER); // might be the same only when one clique remains
    $description_language = $lang;
    if(! isset($db_languages[$description_language])) {
        $description_language = "en";
    }
    $stmt->bindValue(3, $description_language, SQLITE3_TEXT);
    $stmt->bindValue(4, $db_cluster_run, SQLITE3_INTEGER);
    $stmt->bindValue(5, $db_laser_version, SQLITE3_INTEGER);
    $result = $stmt->execute();

    while($arr = $result->fetchArray(SQLITE3_NUM)) {
        if(! isset($oldannotated[$arr[0]])) { // might be the case an element is missing in a cluster
            $url = str_replace("https://commons.wikimedia.org/wiki/", "", $arr[2]);
            $toannotate[ $arr[0] ] = [ $arr[1], $url ];
        }
    }

    if($DEBUG) {
        $message[]="New run, oldannotated: " . count($oldannotated);
    }

    $run = array(
        "oldannotated" => $oldannotated,
        "annotated" => array(),
        "toannotate" => $toannotate,
        "dists" => array()
    );
    file_put_contents($runfile, serialize($run), LOCK_EX);
}else{
    // load run
    $run = unserialize(file_get_contents($runfile));

    if(isset($_REQUEST['annotate'])) {
        // write down the data
        foreach($run['toannotate'] as $item => $description) {
            if(isset($_REQUEST["item_$item"])) {
                $set = $_REQUEST["item_$item"];
                $annotation = 0;
                if($set === "yes") {
                    $annotation = 1;
                }elseif ($set === "no") {
                    $annotation = -1;
                }
                $run['annotated'][$item] = $annotation;
                unset($run['toannotate'][$item]);
                if($DEBUG) {
                    $message[] = "received $item $annotation";
                }
            }
        } // items for which there were missing annotations are left to annotate
        file_put_contents($runfile, serialize($run), LOCK_EX);
        if(count($run['annotated']) >= 100 || (count($run['oldannotated']) + count($run['annotated']) === $itemsLen)) {
            // finish the run, save it to DB
            $finished = true;
            $stmt = $db->prepare(<<<'END'
INSERT INTO pref_runs(nonce) VALUES (?)
END
            );
            $stmt->bindValue(1, $nick, SQLITE3_TEXT);
            if(! $stmt->execute()) {
                $message[] = $_['db_error'];
                $message[] = $db->lastErrorMsg();
            }else{                
                $rid = $db->lastInsertRowID();

                $str = "BEGIN TRANSACTION;\n";
                foreach($run['annotated'] as $item => $annotation) {
                    $item = intval($item);
                    $annotation = intval($annotation);
                    $str .= "INSERT INTO preferences(run,item,preference) VALUES ($rid, $item, $annotation);\n";
                }
                $str .= "COMMIT;\n";
                if ($db->exec($str)) {
                    unlink($runfile);
                    $finished = true;
                }else{
                    $message[] = $_['db_error'];
                    $message[] = $db->lastErrorMsg();
                }
            }
        }else{
            // select more items to annotate
            if(count($run['oldannotated']) + count($run['annotated']) + count($run['toannotate']) === $itemsLen) { 
                // wrapped-around, nuke old
                $message[] = $_['repeated']; 
                $run['oldannotated'] = [];
            }
            [ $fully_available_centers,
              $available_centers,
              $centerIdxToRemainingItems ] = $tree->getRemainingCentersAndItems($run['oldannotated'] + $run['annotated']);

            $picked = array();
            $noise = 0.00001;
            $full_vectors = array();
                   
            foreach($run['annotated'] as $item => $pref) {
                $centerIdx = $tree->itemdata[$item][2];
                $center = $centers[$centerIdx];
                $picked[$centerIdx] = ($picked[$centerIdx] ?? 0 ) + $pref * $noise;
                $noise += 0.00001;
                if($pref != 0) {
                    $vector = $tree->fetchItemVector($center, $item);
                    if($vector){
                        $full_vectors[] = [ $item, $vector, $pref ];
                    }
                }
            }

            $bounds = [];
            // find an item equidistant from the annotated ones
            $pass = 0;
            do {
                foreach($centers as $centerIdx => $center) {
                    if( // cliques with no annotated members first
                        ($pass === 0 && isset($fully_available_centers[$centerIdx])) ||
                        // or drill down on cliques that already have annotated members if not enough
                        ($pass === 1 && isset($available_centers[$centerIdx]))) {
                        $accum_lower = 0; $accum_upper = 0;
                        foreach($picked as $otherIdx => $pref){
                            if($pref != 0){
                                $dist = $tree->centerDistance($centerIdx, $otherIdx);
                                $bound = $centers[$centerIdx]->radius() + $centers[$otherIdx]->radius();
                                $lower = $dist - $bound; $upper = $dist + $bound;
                                if($pref < 0) {
                                    [ $upper, $lower ] = [ $lower, $upper ];
                                }
                                $lower *= $pref;        $upper *= $pref;        
                                $accum_lower += $lower; $accum_upper += $upper;
                            }
                        }
                        $bounds[] = [ $accum_lower, $centerIdx, $accum_upper ];
                    }
                }
                $pass += 1;
            } while($pass < 2 && count($bounds) < 100);

            // we got bounds on the sum but we want a bound on the absolute value closer to zero
            $zero_bounds = 0;
            foreach($bounds as $idx=>$row){
                if($row[0] <= 0) {
                    if($row[2] <= 0) { // both negative, lower-boundary is the absolute of the max
                        $bounds[$idx] = [ abs($row[2]), $row[1], abs($row[0]) ];
                        
                    } else {  // crosses zero? lower-bound is zero, upper bound is the max of absolute values of bounds
                        $zero_bounds++;
                        $bounds[$idx] = [ 0, $row[1], max(abs($row[0]), $row[2]) ];
                    }
                } // else both positive, stays as-is
            }
            if($DEBUG) {
                $message[] = "Bounds: " . count($bounds) . " ($zero_bounds at zero)";
            }
                
            usort($bounds, function ($row1, $row2) {
                if($row1[0] === $row2[0]) { // both zero, look at upper boundary
                    return $row1[2] <=> $row2[2];
                }
                return $row1[0] <=> $row2[0];
            });
            $best = -1;
            $best_dist = 99999;
            $second_best = -1;
            $second_best_dist = 99999;
            $dists = 0;
            $hits = 0;
            $pruned = 0;
            while ($bounds) {
                [ $lower, $centerIdx, $upper ] = array_shift($bounds);
                $center = $centers[$centerIdx];
                $dataset = $center->dataset();
                foreach($dataset->labels() as $cl_idx => $cl_item) {
                    if(isset($run['annotated'][$cl_item]) || isset($run['oldannotated'][$cl_item]) || isset($run['toannotate'][$cl_item])) {
                        continue; // we have been through this before...
                    }
                    if($best >= 0 && $tree->itemdata[$cl_item][0] === $tree->itemdata[$best][0]) {
                        continue; // same cluster, don't bother
                    }
                    $dist = 0;
                    $cl_vector = $dataset->sample($cl_idx);
                    foreach($full_vectors as $row) {
                        $idxDist1 = min($cl_item, $row[0]);
                        $idxDist2 = max($cl_item, $row[0]);
                        $found = false;
                        if(isset($run['dists'][$idxDist1])) {
                            if(isset($run['dists'][$idxDist1][$idxDist2])){
                                $dist += $row[2] * $run['dists'][$idxDist1][$idxDist2];
                                $hits++;
                                $found = true;
                            }
                        }else{
                            $run['dists'][$idxDist1] = array();
                        }
                        if(!$found) {
                            $computed = $tree->kernel()->compute($cl_vector, $row[1]);
                            $dist += $row[2] * $computed;
                            $run['dists'][$idxDist1][$idxDist2] = $computed;
                            $dists++;
                        }
                    }
                    $dist = abs($dist);
                    if($dist < $second_best_dist) {
                        if($dist < $best_dist) {
                            if($best >= 0 && ($tree->itemdata[$cl_item][0] !== $tree->itemdata[$best][0])) {
                                // if they are both from same cluster we only keep the best one
                                $second_best = $best;
                                $second_best_dist = $best_dist;
                            }
                            $best_dist = $dist;
                            $best = $cl_item;
                        }else if($second_best >= 0 && ($tree->itemdata[$cl_item][0] !== $tree->itemdata[$second_best][0])) {
                            $second_best_dist = $dist;
                            $second_best = $cl_item;
                        }
                    }
                    if($dists > $MAXDISTS){
                        break;
                    }
                }
                if($dists > $MAXDISTS){
                    break;
                }
                // prune bounded cliques
                $idx = 0;
                $len = count($bounds);
                $end = false;
                while($idx < $len) {
                    if ($bounds[$idx][0] > $second_best_dist) {
                        // drop the rest
                        if($DEBUG){
                            $message[]="$idx $len " . $bounds[$idx][0] . " $second_best_dist";
                        }
                        $pruned += $len - $idx;
                        if($DEBUG) {
                            $message[]="-> $pruned $idx";
                        }
                        $end = true;
                        break;
                    }
                    $idx += 1;
                }
                if($end){
                    break;
                }
            }
            if($DEBUG){
                $cached = 0;
                foreach($run['dists'] as $row){
                    $cached += count($row);
                }
                $message[] = "Dists: $dists (+ $hits cache hits, cache size: $cached), pruned: $pruned";
            }
            // we got our winners, let's get the whole cluster
            foreach([$best, $second_best] as $selected) {
                if($selected < 0) {
                    continue; // might happen no second best at the end of a full run
                }
                $cluster = $tree->itemdata[$selected][0];
                //echo "cluster: $cluster <br>";
                $stmt = $db->prepare(<<<'END'
SELECT descriptions.item, descriptions.description, urls.url FROM embeddings_laser LEFT JOIN
   descriptions
ON descriptions.item = embeddings_laser.item
LEFT JOIN 
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
LEFT JOIN
   langs
ON descriptions.lang = langs.lid
LEFT JOIN
   urls
ON descriptions.item = urls.item
WHERE
   cluster_labels.cluster = ? AND
   langs.code = ? AND
   cluster_labels.run = ? AND
   embeddings_laser.version = ?
END
);
                $stmt->bindValue(1, $cluster, SQLITE3_INTEGER);
                $description_language = $lang;
                if(! isset($db_languages[$description_language])) {
                    $description_language = "en";
                }
                $stmt->bindValue(2, $description_language, SQLITE3_TEXT);
                $stmt->bindValue(3, $db_cluster_run, SQLITE3_INTEGER);
                $stmt->bindValue(4, $db_laser_version, SQLITE3_INTEGER);
                $result = $stmt->execute();
                if($result) {
                    $count = 0;
                    while($arr = $result->fetchArray(SQLITE3_NUM)) {
                        if(! isset($run['annotated'][$arr[0]]) &&
                           ! isset($run['oldannotated'][$arr[0]])) { // might be the case an element is missing in a cluster
                            $url = str_replace("https://commons.wikimedia.org/wiki/", "", $arr[2]);
                            $run['toannotate'][$arr[0]] = [ $arr[1], $url ];
                            $count++;
                        }
                    }
                    if($DEBUG){
                        $message[] = "For cluster $cluster got $count";
                    }
                }else{
                    $message[] = $db->lastErrorMsg();
                }
            }
            file_put_contents($runfile, serialize($run), LOCK_EX);
        }
    }
    $toannotate = $run['toannotate'];
    $progress = count($run['annotated']);
}

