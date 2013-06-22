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
          <form class="form-horizontal" method="post" action="">
            <fieldset>
              <legend><?php echo Intl::msg('Reorder folders'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php $i=0; foreach ($folders as $hash => $folder) { ?>
                  <label for="order-folder-<?php echo $hash; ?>">
                    <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>) <br>
                    <input type="text" id="order-folder-<?php echo $hash; ?>" name="order-folder-<?php echo $hash; ?>" value="<?php echo $i; $i++; ?>">
                  </label>
                  <?php } ?>
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('Add selected folders to selected feeds'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="addfolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
                <div class="controls">
                  <input type="text" name="addnewfolder" value="" placeholder="New folder">
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('Remove selected folders to selected feeds'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="remove-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="remove-folder-<?php echo $hash; ?>" name="removefolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('List of feeds'); ?></legend>

              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = true; }" value="<?php echo Intl::msg('Select all'); ?>">
              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = false; }" value="<?php echo Intl::msg('Unselect all'); ?>">

              <ul class="unstyled">
                <?php foreach ($listFeeds as $feedHash => $feed) { ?>
                <li>
                  <label for="feed-<?php echo $feedHash; ?>">
                    <input type="checkbox" id="feed-<?php echo $feedHash; ?>" name="feeds[]" value="<?php echo $feedHash; ?>">
                    <?php echo htmlspecialchars($feed['title']); ?> (<a href="?edit=<?php echo $feedHash; ?>"><?php echo Intl::msg('Edit feed'); ?></a>)
                  </label>
                </li>
                <?php } ?>
              </ul>
            </fieldset>

            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="delete" value="<?php echo Intl::msg('Delete selected feeds'); ?>" onclick="return confirm('<?php echo Intl::msg('Do you really want to delete all selected feeds?'); ?>');"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
          </form>
        </div>
      </div>
  </body>
</html>
