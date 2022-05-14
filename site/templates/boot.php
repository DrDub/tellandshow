<?php
global $argv,$argc;

$pagen = pathinfo($page)['filename'];
$lang = $argv[1];
$base = yaml_parse_file("base.yaml");
$page = yaml_parse_file("$pagen.yaml");
$_  = $base[$lang] ?? array();
$_ += $page[$lang] ?? array();
$en  = $base['en'];
$en += $page['en'];
$_['langs'] = $base['langs'];
foreach ($_ as &$value) {
    $value = str_replace('$LANG$', $lang, $value);
}
unset($value);
foreach ($en as &$value) {
    $value = str_replace('$LANG$', $lang, $value);
}
unset($value);
foreach ($en as $key => $value) {
    if(! isset($_[$key])) {
        $_[$key] = $value;
    }
}
?>
