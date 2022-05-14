<?php
$page = __FILE__;
include_once("boot.php");
include_once("header.tmpl");
?>

      <div class="page-header" id="banner">
        <div class="row">
          <div class="col-lg-8 col-md-7 col-sm-6">
            <h1>Tell-and-Show</h1>
            <p class="lead"><?= $_['lead'] ?></p>
          </div>
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <?= $_['tands_annotate1'] ?>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['annotate'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
            <?= $_['annotate_lead'] ?>
            <ul>
                 <li><?= $_['instruction1'] ?> <button class="btn btn-success btn-sm"><?= $_['yes'] ?></button>.</li>
                 <li><?= $_['instruction2'] ?> <button class="btn btn-primary btn-sm"><?= $_['no'] ?></button>.</li>
                 <li><?= $_['instruction3'] ?> <button class="btn btn-secondary btn-sm"><?= $_['dont_understand'] ?></button>.</li>
                 <li><p><?= $_['instruction4_1'] ?>
                             <button class="btn btn-success btn-sm"><?= $_['yes'] ?></button> <?= $_['instruction4_2'] ?>
                             <button class="btn btn-primary btn-sm"><?= $_['no'] ?></button>.</p>
                     <p><?= $_['instruction4_3'] ?>
                             <button class="btn btn-success btn-sm"><?= $_['yes'] ?></button> <?= $_['instruction4_4'] ?>
                             <button class="btn btn-primary btn-sm"><?= $_['no'] ?></button>.</p></li>
            </ul>
            <?= $_['closing'] ?>
            <p><input type="checkbox" id="agreed"><?= $_['agree'] ?></p>
            <script>
                 function check(evt) {
                     var event = evt || window.event;
                     if(! document.getElementById("agreed").checked) {
                         window.alert('<?= $_["alert"] ?>');
                         event.stopPropagation();
                         return false;
                     }
                     return true;
                 }
            </script>
            <p><a href="annotation.php?lang=<?= $lang ?>" onclick="return check(event)"  class="btn btn-primary btn-sm"><?= $_['start'] ?></a></p>
                                                       
                    
          </div>
        </div>
      </div>

<?php include_once("bottom.tmpl"); ?>

