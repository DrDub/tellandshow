<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

$stmt = $db->prepare(<<<'END'
SELECT urls.item, urls.url, pref_runs.nonce, pref_runs.rid, preferences.preference
FROM preferences
LEFT JOIN
  pref_runs, urls
ON
  pref_runs.rid == preferences.run AND
  urls.item == preferences.item
END
);
$result = $stmt->execute();

while($arr = $result->fetchArray(SQLITE3_NUM)) {
    echo implode("\t", $arr) . "\n";
}
