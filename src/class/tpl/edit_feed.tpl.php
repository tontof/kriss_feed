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
              <legend><?php echo Intl::msg('Feed main information'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="title"><?php echo Intl::msg('Feed title'); ?></label>
                <div class="controls">
                  <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($feed['title']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Feed XML URL'); ?></label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="xmlUrl" value="<?php echo htmlspecialchars($feed['xmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Feed main URL'); ?></label>
                <div class="controls">
                  <input type="text" name="htmlUrl" value="<?php echo htmlspecialchars($feed['htmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label" for="description"><?php echo Intl::msg('Feed description'); ?></label>
                <div class="controls">
                  <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($feed['description']); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Feed folders'); ?></legend>
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
                <label class="control-label" for="newfolder"><?php echo Intl::msg('New folder'); ?></label>
                <div class="controls">
                  <input type="text" name="newfolder" value="" placeholder="<?php echo Intl::msg('New folder'); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Feed preferences'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="timeUpdate"><?php echo Intl::msg('Time update'); ?></label>
                <div class="controls">
                  <input type="text" id="timeUpdate" name="timeUpdate" value="<?php echo $feed['timeUpdate']; ?>">
                  <span class="help-block"><?php echo Intl::msg('"auto", "max" or a number of minutes less than "max" define in <a href="?config">configuration</a>'); ?></span>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Last update'); ?></label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="lastUpdate" value="<?php echo $lastUpdate; ?>">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                  <input class="btn" type="submit" name="delete" value="<?php echo Intl::msg('Delete feed'); ?>"/>
                  <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
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
