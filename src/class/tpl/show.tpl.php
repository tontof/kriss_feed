<!DOCTYPE html>
<html>
  <head>
  <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div id="show">
<?php FeedPage::statusTpl(); ?>

<?php FeedPage::navTpl(); ?>
      <div id="section">
<?php
     switch ($view) {
     case 'expanded':
?>
<?php if (isset($item) && !empty($item)) { ?>
     <div id="article"<?php echo $item['read']==1?' class="read"':''; ?>>
         <h3 id="title">
           <a href="<?php echo $item['link']; ?>"><?php echo $item['title']; ?></a>
         </h3>
         <h4 id="subtitle">
           from <a href="<?php echo $item['via']; ?>"><?php echo $item['author']; ?></a>
           <a href="<?php echo $item['xmlUrl']; ?>" title="RSS">
             <span class="icon">
               <div class="icon-feed-dot"></div>
               <div class="icon-feed-circle-1"></div>
               <div class="icon-feed-circle-2"></div>
             </span>
           </a>
         </h4>
         <div id="content">
<?php echo $item['content']; ?>
         </div>
       </div>
<?php } else { ?>
       <div id="article">
         <h3 id="title"></h3>
         <h4 id="subtitle"></h4>
         <div id="content"></div>
       </div>
<?php } ?>
<?php
     break;
     case 'list':
?>
       <ul id="list-items"></ul>
<?php
     break;
     }
?>
      </div>
    </div>
<script type="text/javascript">
<?php /* include("inc/script.js"); */ ?>
</script>
  </body>
</html>
