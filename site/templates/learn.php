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
            <?= $_['tands_learn1'] ?>
          </div>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['training'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
            <?= $_['training_blob'] ?>
          </div>
        </div>
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['production'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
            <?= $_['production_blob'] ?>
          </div>
        </div>
      </div>

<?php include_once("bottom.tmpl"); ?>
