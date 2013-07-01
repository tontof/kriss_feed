<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
        <?php print_r(Plugin::listAll()); ?>
        </div>
      </div>
    </div>
  </body>
</html>
