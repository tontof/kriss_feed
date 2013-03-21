<!DOCTYPE html>
<html>
  <head>
  <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div id="reader">
      <div id="feeds" class="nomobile">
<?php
       if (Session::isLogged()) {
?>
        <div class="article">
          <form action="?" method="get">
           <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
           <label for="newfeed">- New feed</label>
           <input type="submit" name="add" value="Subscribe">
           <input type="text" name="newfeed" id="newfeed"
              onfocus="removeEvent(window, 'keypress', checkKey);"
              onblur="addEvent(window, 'keypress', checkKey);">
          </form>
        </div>
<?php
        }
?>
     <?php FeedPage::listTpl(); ?>
      </div>
      <div id="container">
     <?php FeedPage::statusTpl(); ?>
     <?php FeedPage::navTpl(); ?>

        <div id="section">
        <div id="new-items">
          <button id="butplusmenu" onclick="loadUnreadItems();">
             0 new item(s)
          </button>
        </div>
          <ul id="list-items"></ul>
        </div>
      </div>
    </div>
<script type="text/javascript">
    <?php /* include("inc/script.js"); */ ?>
</script>
  </body>
</html>
