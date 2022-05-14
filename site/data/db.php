<?php

$db = new SQLite3(dirname(__FILE__) . "/db/production.db");
$db->busyTimeout(5000);
$db->exec('PRAGMA journal_mode = wal;');

$db_cluster_run   = 1;
$db_laser_version = 1;
$db_languages = array( 'en' => 1 );
