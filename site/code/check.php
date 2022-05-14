<?php
// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';

include "MyBallTree.php";

global $argc, $argv;


$tree = unserialize(file_get_contents($argv[1]));

$clusters = array();
$centroids = 0;
foreach ($tree->itemdata as $item => $row) {
    [ $cluster, $centroid ] = $row;
    if($centroid) {
        $centroids += 1;
    }
    $clusters[$cluster]  =1;
}
$distances = 0;
foreach($tree->centerDistances as $row) {
    $distances += count($row);
}
$centerLen = count($tree->centers);
foreach($tree->itemdata as $item => $itemdata) {
    if(count($itemdata) != 3){
        die("$item has row of " . count($itemdata) . "\n");
    }
    if($itemdata[2] >= $centerLen) {
        die("$item has centerIdx out-of-range (max: $centerLen), found: " . $itemdata[2] . "\n");
    }
}

echo "Items: " . count($tree->itemdata) ."\n";
echo "Clusters: " . count($clusters) ."\n";
echo "Centroids: $centroids\n";
echo "Height: " . $tree->height() . "\n";
echo "Balance: " . $tree->balance() . "\n";
echo "Bare: " . $tree->bare() . "\n";
echo "Centers: $centerLen\n";
echo "Distances: $distances\n";
$tree->dump();

