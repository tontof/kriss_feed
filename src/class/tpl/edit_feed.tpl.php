<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-feed" class="span6 offset3">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="">
            <fieldset>
              <legend>Feed main information</legend>
              <div class="control-group">
                <label class="control-label" for="title">Feed title</label>
                <div class="controls">
                  <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($feed['title']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Feed XML url</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="xmlUrl" value="<?php echo htmlspecialchars($feed['xmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Feed main url</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="htmlUrl" value="<?php echo htmlspecialchars($feed['htmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label" for="description">Feed description</label>
                <div class="controls">
                  <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($feed['description']); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Feed folders</legend>
              <?php
                 foreach ($folders as $hash => $folder) {
              $checked = '';
              if (in_array($hash, $feed['foldersHash'])) {
              $checked = ' checked="checked"';
              }
              ?>
              <div class="control-group">
                <div class="controls">
                  <label for="folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="folder-<?php echo $hash; ?>" name="folders[]" <?php echo $checked; ?> value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?>
                  </label>
                </div>
              </div>
              <?php } ?>
              <div class="control-group">
                <label class="control-label" for="newfolder">New folder</label>
                <div class="controls">
                  <input type="text" name="newfolder" value="" placeholder="New folder">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Feed preferences</legend>
              <div class="control-group">
                <label class="control-label" for="timeUpdate">Time update </label>
                <div class="controls">
                  <input type="text" id="timeUpdate" name="timeUpdate" value="<?php echo $feed['timeUpdate']; ?>">
                  <span class="help-block">'auto', 'max' or a number of minutes less than 'max' define in <a href="?config">config</a></span>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Last update (<em>read only</em>)</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="lastUpdate" value="<?php echo $lastUpdate; ?>">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="save" value="Save" />
                  <input class="btn" type="submit" name="cancel" value="Cancel"/>
                  <input class="btn" type="submit" name="delete" value="Delete"/>
                </div>
              </div>
            </fieldset>
            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
          </form><br>
        </div>
      </div>
    </div>
  </body>
</html>
