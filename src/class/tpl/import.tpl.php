<!DOCTYPE html>
<html>
  <head>
<?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span4 offset4">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="?import" enctype="multipart/form-data">
            <fieldset>
              <legend><?php echo Intl::msg('Import opml file'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="filetoupload"><?php echo Intl::msg('Opml file:'); ?></label>
                <div class="controls">
                  <input class="btn" type="file" id="filetoupload" name="filetoupload">
                  <span class="help-block"><?php echo Intl::msg('Size max:'); ?> <?php echo MyTool::humanBytes(MyTool::getMaxFileSize()); ?>
                    </span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <label for="overwrite">
                    <input type="checkbox" name="overwrite" id="overwrite">
                    <?php echo Intl::msg('Overwrite existing feeds'); ?>
                  </label>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="import" value="<?php echo Intl::msg('Import opml file'); ?>">
                  <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>">
                </div>
              </div>

              <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MyTool::getMaxFileSize(); ?>">
              <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
              <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            </fieldset>
          </form>
        </div>
      </div>
    </div>
  </body>
</html> 
