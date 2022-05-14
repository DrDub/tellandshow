<?php

// Copyright (C) 2022 Pablo Duboue, distributed under AGPLv3

include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../data/db.php';

$lang = $_REQUEST['lang'] ?? 'en';
$base = yaml_parse_file(dirname(__FILE__) . "/../templates/base.yaml");
$page = yaml_parse_file(dirname(__FILE__) . "/../templates/annotation.yaml");
$_  = $base[$lang] ?? array();
$_ += $page[$lang] ?? array();
$en  = $base['en'];
$en += $page['en'];
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

include_once dirname(__FILE__) . '/../code/annotate.php';
include_once dirname(__FILE__) . "/../templates/headerbare.tmpl";
?>

<div class="bs-docs-section">
<?php if(count($message) > 0) {
?>
        <div class="row">
          <div class="col-lg-12 col-md-8 col-sm-5">
<?php    
    foreach($message as $m) {
?>
            <div class="alert alert-primary" role="alert"><?= $m ?></div>
<?php
    }
?>
          </div>
        </div>
<?php
}
if($finished) {
?>
        <div class="row">
          <div class="col-lg-12 col-md-8 col-sm-5">
          <!-- FINISHED! --><?= $_['finished'] ?>
          <p><form method="POST">
               <input type="hidden" name="nick" value="<?= $nick ?>">
               <input type="submit" name="changenick" class="btn btn-primary" value="<?= $_['start'] ?>">
             </form>
          </p>
          </div>
        </div>
<?php
}else{
?>
      <div class="page-header" id="banner">
        <div class="row">
          <div class="col-lg-8 col-md-7 col-sm-6">
              <form method="POST">
              <?= $_['nickname'] ?>: <input type="text" width="30" name="nick" value="<?= $nick ?>"></input> <input type="submit" name="changenick" value="<?= $_['change'] ?>">
              </form>
          </div>
          <div class="col-lg-4 col-md-5 col-sm-6">
              <p><?= $_['nick'] ?></p>            
        </div>
      </div>
    
        <div class="row">
          <div class="col-lg-12 col-md-8 col-sm-5">
              <form method="POST">
              <input type="hidden" name="nick" value="<?= $nick ?>">
              <ul>
<?php
    foreach($toannotate as $item => $text) {
        echo "<li><p>$text</p>\n<p>";
        foreach([ [ 'yes', 'btn-success' ], [ 'no', 'btn-primary' ], [ 'dont_understand', 'btn-secondary' ] ] as $row) {
            [ $label, $style ] = $row;
            echo "<input type=\"radio\" id=\"item_".$item."_$label\" name=\"item_$item\" value=\"$label\"/>";
            echo "<label for=\"item_". $item . "_$label\">";
            echo "<button class=\"btn $style btn-sm\" " .
                 "onclick=\"document.getElementById('item_" . $item . "_$label').checked = true; return false;\">" . $_[$label] ."</button></label>\n";
        }
        echo "</p><p>&nbsp;</p>\n";
    }
?>
              </ul>
              <input type="submit" name="annotate" value="<?= $_['annotate'] ?>">
              </form>
          </div>
                <p> &nbsp; </p>                
                <p> &nbsp; </p>                
                <p> &nbsp; </p>
<p><a href="#instructions" onclick="document.getElementById('instr').style.display = 'block';" class="btn btn-primary"><?= $_['instructions'] ?></a></p>

<iframe id="instr" style="display: none;" src="annotate_<?= $lang ?>.html" witdh="100%" height="400"></iframe>
</a>                                                                       
        </div>
<?php
}
?>
</div>

<?php include_once(dirname(__FILE__) . "/../templates/bottom.tmpl"); ?>

