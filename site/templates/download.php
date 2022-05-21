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
                    <?= $_['tands_download1'] ?>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['download'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
                    <?= $_['download_blob'] ?>
                    <p><a class="btn btn-primary" href="tellandshow_runs20220521.tsv">tellandshow_runs20220521.tsv</a></p>
                    <p><pre>id URL nickname run preference</pre></p>
          </div>
        </div>
      </div>

      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['other_data'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
             <p><a href="roadmap_<?= $lang ?>.html"><?= $_['other'] ?></a></p>
             <?= $_['need'] ?>
          </div>
        </div>
      </div>
            
      <div class="bs-docs-section">
        <div class="page-header">
          <div class="row">
            <div class="col-lg-12">
              <h1><?= $_['source_code'] ?></h1>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-5 col-sm-6">
             <ul>
                  <li><p><a href="https://gitlab.com/DrDub/tellandshow">Gitlab</a></p></li>
                  <li><p><a href="https://github.com/DrDub/tellandshow">Github Mirror</a></p></li>
             </ul>
          </div>
        </div>
      </div>

<?php include_once("bottom.tmpl"); ?>

