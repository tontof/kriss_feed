<ul id="list-items" class="unstyled">
  <?php
     foreach (array_keys($items) as $itemHash){
     $item = $kf->getItem($itemHash);
  ?>
  <li id="item-<?php echo $itemHash; ?>" class="<?php echo ($view==='expanded'?'item-expanded':'item-list'); ?><?php echo ($item['read']==1?' read':''); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">

    <?php if ($view==='list') { ?>
    <a id="item-toggle-<?php echo $itemHash; ?>" class="item-toggle item-toggle-plus" href="<?php echo $query.'current='.$itemHash.((!isset($_GET['open']) or $currentItemHash != $itemHash)?'&amp;open':''); ?>" data-toggle="collapse" data-target="#item-div-<?php echo $itemHash; ?>">
      <span class="ico">
        <span class="ico-circle"></span>
        <span class="ico-line-h"></span>
        <span class="ico-line-v<?php echo ((!isset($_GET['open']) or $currentItemHash != $itemHash)?' item-toggle-close':' item-toggle-open'); ?>"></span>
      </span>
    </a>
    <dl class="dl-horizontal item">
      <dt class="item-feed">
        <span class="item-author">
          <?php echo $item['author']; ?>
        </span>
      </dt>
      <dd class="item-info">
        <span class="item-title">
          <?php if ($item['read'] == 1) { ?>
          <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label">unread</span></a>
          <?php } else { ?>
          <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label">read</span></a>
          <?php } ?>
          <a class="item-link" href="<?php echo $redirector.$item['link']; ?>">
            <?php echo $item['title']; ?>
          </a>
        </span>
        <span class="item-description">
          <a class="item-toggle muted" href="<?php echo $query.'current='.$itemHash.((!isset($_GET['open']) or $currentItemHash != $itemHash)?'&amp;open':''); ?>" data-toggle="collapse" data-target="#item-div-<?php echo $itemHash; ?>">
            <?php echo $item['description']; ?>
          </a>
        </span>
      </dd>
    </dl>
    <?php } ?>

    <div id="item-div-<?php echo $itemHash; ?>" class="item collapse<?php echo (($view==='expanded' or ($currentItemHash == $itemHash and isset($_GET['open'])))?' in well':''); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">
      <?php if ($view==='expanded' or ($currentItemHash == $itemHash and isset($_GET['open']))) { ?>
      <div class="item-title">
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label">share</span></a>
        <?php if ($item['read'] == 1) { ?>
        <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label item-label-mark-as">unread</span></a>
        <?php } else { ?>
        <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label item-label-mark-as">read</span></a>
        <?php } ?>
        <a class="item-link" href="<?php echo $redirector.$item['link']; ?>"><?php echo $item['title']; ?></a>
      </div>
      <div class="item-info-end">
        from <a class="item-via" href="<?php echo $redirector.$item['via']; ?>"><?php echo $item['author']; ?></a>
        <a class="item-xml" href="<?php echo $redirector.$item['xmlUrl']; ?>">
          <span class="ico">
            <span class="ico-feed-dot"></span>
            <span class="ico-feed-circle-1"></span>
            <span class="ico-feed-circle-2"></span>
          </span>
        </a>
      </div>
      <div class="clear"></div>
      <div class="item-content">
        <?php echo $item['content']; ?>
      </div>
      <div class="item-info-end">
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label label-expanded">share</span></a>
        <?php if ($item['read'] == 1) { ?>
        <a class="item-mark-as" class="link-mark" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label label-expanded">unread</span></a>
        <?php } else { ?>
        <a class="item-mark-as" class="link-mark" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label label-expanded">read</span></a>
        <?php } ?>
      </div>
      <div class="clear"></div>
      <?php } ?>
    </div>
  </li>
  <?php } ?>
</ul>
