<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::statusTpl(); ?>
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend><?php echo Intl::msg('Change your password'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="oldpassword"><?php echo Intl::msg('Old password'); ?></label>
                    <div class="controls">
                      <input type="password" id="oldpassword" name="oldpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="newpassword"><?php echo Intl::msg('New password'); ?></label>
                    <div class="controls">
                      <input type="password" id="newpassword" name="newpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save new password'); ?>" />
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
