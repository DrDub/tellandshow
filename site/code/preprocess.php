<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

include "MyBallTree.php";

use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;

global $argc, $argv;

if($argc != 4) {
    echo "Usage: preprocess.php label-run-id embedding-run-id output.ser\n";
    exit;
}

$stmt = $db->prepare(<<<'END'
SELECT cluster_labels.cluster, cluster_labels.is_centroid, embeddings_laser.* FROM embeddings_laser LEFT JOIN
   cluster_labels
ON cluster_labels.item = embeddings_laser.item
WHERE
   cluster_labels.run = ? AND
   embeddings_laser.version = ?
END
);
$stmt->bindValue(1, intval($argv[1]), SQLITE3_INTEGER);
$stmt->bindValue(2, intval($argv[2]), SQLITE3_INTEGER);

$start = time();
$result = $stmt->execute();
$itemdata = array();

$data  = array();
$dummy = array();

while($arr = $result->fetchArray(SQLITE3_NUM)) {
    $cluster = array_shift($arr);
    $is_centroid = array_shift($arr);
    $item = array_shift($arr);
    $itemdata[$item] = [ $cluster, $is_centroid ];
    $dummy[] = $item;
    array_shift($arr); // version
    $data[]  = $arr;
}

$labeled = new Labeled($data, $dummy);

echo "Fetched " . (count($data)) . " vectors in ".(time() - $start)."s\n";

$start = time();
$tree = new MyBallTree($itemdata);
$tree->grow($labeled);

echo "Build ball tree in ".(time() - $start)."s\n";

$start = time();
$tree->indexAndComputeDistances();
echo "Computed " . (count($tree->centers) * (count($tree->centers) - 1) / 2) . " center distances in ".(time() - $start)."s\n";

file_put_contents($argv[3], serialize($tree), LOCK_EX);
