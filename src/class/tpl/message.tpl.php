<!DOCTYPE html>
<html>
  <head>
<?php FeedPage::includesTpl(); ?>
  </head>
  <body onload="document.getElementById('again').focus();">
    <div class="container-fluid full-height">
      <div class="row-fluid full-height">
        <div id="main-container" class="span12 full-height">
          <?php FeedPage::statusTpl(); ?>
          <div class="text-center">
     <?php echo Intl::msg('Click on flag to select your language.').'<br>';
     
 foreach(Intl::$langList as $lang => $info) { ?>
<a href="?lang=<?php echo $lang; ?>" title="<?php echo $info['name']; ?>" class="flag <?php echo $info['class']; ?>"></a>
<?php } ?>
          </div>
          <div class="<?php if (empty($class)) { echo 'text-error'; } else { echo $class; } ?> text-center">
            <?php echo $message; ?><br>
     <a id="again" tabindex="1" class="btn" href="<?php echo $referer; ?>"><?php if (empty($button)) { echo Intl::msg('Try again'); } else { echo $button; } ?></a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>

