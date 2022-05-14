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
                    <?= $_['tands_volunteer1'] ?>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['volunteer'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <?= $_['volunteer_lead'] ?>
          <ul>
               <li><?= $_['volunteer1'] ?>
                           <a href="annotate_<?= $lang ?>.html" class="btn btn-success"><?= $_['volunteer1_button'] ?></a></li>
               <li><?= $_['volunteer2'] ?></li>
               <li><?= $_['volunteer3'] ?></li>
               <li><?= $_['volunteer4'] ?></li>
               <li><?= $_['volunteer5'] ?>
<script type='text/javascript' src='https://storage.ko-fi.com/cdn/widget/Widget_2.js'></script><script type='text/javascript'>kofiwidget2.init('Support Me on Ko-fi', '#29abe0', 'M4M87P13P');kofiwidget2.draw();</script>                            
                           </li>
          </ul>
          </div>
        </div>
      </div>

<?php include_once("bottom.tmpl"); ?>

