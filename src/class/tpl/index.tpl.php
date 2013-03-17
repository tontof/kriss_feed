<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div id="index" class="container-fluid full-height" data-view="<?php echo $view; ?>" data-list-feeds="<?php echo $listFeeds; ?>" data-filter="<?php echo $filter; ?>" data-order="<?php echo $order; ?>" data-by-page="<?php echo $byPage; ?>" data-autoread-item="<?php echo $autoreadItem; ?>" data-autoread-page="<?php echo $autoreadPage; ?>" data-autohide="<?php echo $autohide; ?>" data-current-hash="<?php echo $currentHash; ?>" data-current-page="<?php echo $currentPage; ?>" data-nb-items="<?php echo $nbItems; ?>" data-shaarli="<?php echo $shaarli; ?>" data-redirector="<?php echo $redirector; ?>" data-autoupdate="<?php echo $autoupdate; ?>" data-autofocus="<?php echo $autofocus; ?>">
      <div class="row-fluid full-height">
        <?php if ($listFeeds == 'show') { ?>
        <div id="main-container" class="span9 full-height">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <div id="paging-up">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
          <?php FeedPage::listItemsTpl(); ?>
          <div id="paging-down">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
        </div>
        <div id="minor-container" class="span3 full-height minor-container">
          <?php FeedPage::listFeedsTpl(); ?>
        </div>
        <?php } else { ?>
        <div id="main-container" class="span12 full-height">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <div id="paging-up">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
          <?php FeedPage::listItemsTpl(); ?>
          <div id="paging-down">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
    <?php if (is_file('inc/script.js')) { ?>
    <script type="text/javascript" src="inc/script.js?version=<?php echo $version;?>"></script>
    <?php } else { ?>
    <script type="text/javascript">
      <?php include("inc/script.js"); ?>
    </script>
    <?php } ?>
  </body>
</html>
