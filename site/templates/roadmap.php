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
                    <?= $_['tands_roadmap1'] ?>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['roadmap'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <h2><?= $_['training'] ?></h2>
                    <ol>
                         <li><?= $_['training1'] ?></li>
                         <li><?= $_['training2'] ?></li>
                    </ol>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-8 col-md-10 col-sm-12">
                    <h2 style="text-align: center;"><?= $_['preference_metric'] ?></h2>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6"> &nbsp;
          </div>
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <h2><?= $_['production'] ?></h2>
                    <ol>
                         <li><?= $_['production1'] ?></li>
                         <li><?= $_['production2'] ?></li>
                    </ol>
                    <?= $_['closing'] ?>
          </div>
        </div>
      </div>

<?php include_once("bottom.tmpl"); ?>

