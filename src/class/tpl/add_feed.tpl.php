<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-all" class="span6 offset3">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" action="?add" method="POST">
            <fieldset>
              <legend><?php echo Intl::msg('Add a new feed'); ?></legend>
              <div class="control-group">
                <label class="control-label" ><?php echo Intl::msg('Feed URL'); ?></label>
                <div class="controls">
                  <input type="text" id="newfeed" name="newfeed" value="<?php echo $newfeed; ?>">                  
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Add selected folders to feed'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="folders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit feed'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Add a new folder'); ?></label>
                <div class="controls">
                  <input type="text" name="newfolder" value="">
                </div>
              </div>
              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="add" value="<?php echo Intl::msg('Add new feed'); ?>"/>
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Use bookmarklet to add a new feed'); ?></legend>
              <div id="add-feed-bookmarklet" class="text-center">
                <a onclick="alert('<?php echo Intl::msg('Drag this link to your bookmarks toolbar, or right-click it and choose Bookmark This Link...'); ?>');return false;" href="javascript:(function(){var%20url%20=%20location.href;window.open('<?php echo $kfurl;?>?add&amp;newfeed='+encodeURIComponent(url),'_blank','menubar=no,height=390,width=600,toolbar=no,scrollbars=yes,status=no,dialog=1');})();"><b>KF+</b></a>
              </div>
            </fieldset>
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
