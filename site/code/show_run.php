<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

global $argc, $argv;

$runfile = $argv[1];
$run = unserialize(file_get_contents($runfile));

print_r($run);

