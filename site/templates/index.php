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
            <a href="img/tsne_centers.png"><img src="img/tsne_centers.png" alt="wiki commons centers" width="512"></a>
          </div>
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <?= $_['tands_descr'] ?>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['get_involved'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <?= $_['how_to_help'] ?>
          </div>
        </div>
        
        <div class="row">
          <div class="col-lg-7">
            <p>
              <a href="roadmap_<?= $lang ?>.html" class="btn btn-primary"><?= $_['get_reco'] ?></a>
              <a href="volunteer_<?= $lang ?>.html" class="btn btn-success"><?= $_['volunteer'] ?></a>
              <a href="learn_<?= $lang ?>.html" class="btn btn-secondary"><?= $_['learn_more'] ?></a>
            </p>
        </div>
      </div>
<?php include_once("bottom.tmpl"); ?>
