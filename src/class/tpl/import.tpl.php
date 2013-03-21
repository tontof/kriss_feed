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
          <form class="form-horizontal" method="post" action="?import" enctype="multipart/form-data">
            <fieldset>
              <legend>Import Opml file</legend>
              Import an opml file as exported by Google Reader, Tiny Tiny RSS, RSS lounge...
              
              <div class="control-group">
                <label class="control-label" for="filetoupload">File (Size max: <?php echo MyTool::humanBytes(MyTool::getMaxFileSize()); ?>)</label>
                <div class="controls">
                  <input class="btn" type="file" id="filetoupload" name="filetoupload">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <label for="overwrite">
                    <input type="checkbox" name="overwrite" id="overwrite">
                    Overwrite existing feeds
                  </label>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="import" value="Import">
                  <input class="btn" type="submit" name="cancel" value="Cancel">
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
