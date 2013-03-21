<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span4 offset4">
          <div id="install">
            <form class="form-horizontal" method="post" action="" name="installform">
              <fieldset>
                <legend>KrISS feed installation</legend>
                <div class="control-group">
                  <label class="control-label" for="setlogin">Login</label>
                  <div class="controls">
                    <input type="text" id="setlogin" name="setlogin" placeholder="Login">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="setlogin">Password</label>
                  <div class="controls">
                    <input type="password" id="setpassword" name="setpassword" placeholder="Password">
                  </div>
                </div>
                <div class="control-group">
                  <div class="controls">
                    <button type="submit" class="btn">Submit</button>
                  </div>
                </div>
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
              </fieldset>
            </form>
            <?php FeedPage::statusTpl(); ?>
          </div>
        </div>
        <script>
          document.installform.setlogin.focus();
        </script>
      </div>
    </div>
  </body>
</html>

