<ul id="list-items" class="unstyled">
  <?php
     foreach (array_keys($items) as $itemHash){
     $item = $ks->getItem($itemHash);
  ?>
  <li id="item-<?php echo $itemHash; ?>" class="<?php echo ($view==='expanded'?'item-expanded':'item-list'); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">

    <?php if ($view==='list') { ?>
    <a id="item-toggle-<?php echo $itemHash; ?>" class="item-toggle item-toggle-plus" href="<?php echo $query.'current='.$itemHash.((!isset($_GET['open']) or $currentItemHash != $itemHash)?'&amp;open':''); ?>" data-toggle="collapse" data-target="#item-div-<?php echo $itemHash; ?>">
      <span class="ico ico-toggle-item">
        <span class="ico-b-disc"></span>
        <span class="ico-w-line-h"></span>
        <span class="ico-w-line-v<?php echo ((!isset($_GET['open']) or $currentItemHash != $itemHash)?' item-toggle-close':' item-toggle-open'); ?>"></span>
      </span>
      <?php echo $item['time']['list']; ?>
    </a>
    <dl class="dl-horizontal item">
      <dt class="item-feed">
        <?php if ($addFavicon) { ?>
        <span class="item-favicon">
          <img src="<?php echo $item['favicon']; ?>" height="16" width="16" title="favicon" alt="favicon"/>
        </span>
        <?php } ?>
        <span class="item-author">
          <a class="item-feed" href="<?php echo '?currentHash='.substr($itemHash, 0, 6); ?>">
            <?php echo $item['author']; ?>
          </a>
        </span>
      </dt>
      <dd class="item-info">
        <span class="item-title">
     <?php 
     if (isset($item['starred'])) { ?>
          <a class="item-markStar-as" href="<?php echo $query.'unstarred='.$itemHash; ?>"><span class="label">unstarred</span></a>
          <?php } else { ?>
          <a class="item-markStar-as" href="<?php echo $query.'starred='.$itemHash; ?>"><span class="label">starred</span></a>
          <?php } ?>
          <a target="_blank"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> class="item-link" href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['link']; ?>">
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
    <div class="clear"></div>
    <?php } ?>

    <div id="item-div-<?php echo $itemHash; ?>" class="item collapse<?php echo (($view==='expanded' or ($currentItemHash == $itemHash and isset($_GET['open'])))?' in well':''); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">
      <?php if ($view==='expanded' or ($currentItemHash == $itemHash and isset($_GET['open']))) { ?>
      <div class="item-title">
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label">share</span></a>
        <?php
        if ($item['starred'] == 1) { ?>
        <a class="item-markStar-as" href="<?php echo $query.'unstarred='.$itemHash; ?>"><span class="label">unstarred</span></a>
        <?php } else { ?>
        <a class="item-markStar-as" href="<?php echo $query.'starred='.$itemHash; ?>"><span class="label">starred</span></a>
        <?php }?>
        <a target="_blank"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> class="item-link" href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['link']; ?>"><?php echo $item['title']; ?></a>
      </div>
      <div class="clear"></div>
      <div class="item-info-end">
        <?php echo $item['time']['expanded']; ?>
      </div>
      <div class="item-info-end">
        from <a class="item-via"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['via']; ?>"><?php echo $item['author']; ?></a>
        <a class="item-xml"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['xmlUrl']; ?>">
          <span class="ico">
            <span class="ico-feed-dot"></span>
            <span class="ico-feed-circle-1"></span>
            <span class="ico-feed-circle-2"></span>
          </span>
        </a>
      </div>
      <div class="clear"></div>
      <div class="item-content">
        <article>
          <?php echo $item['content']; ?>
        </article>
      </div>
      <div class="clear"></div>
      <div class="item-info-end">
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label label-expanded">share</span></a>
        <?php
        if ($item['starred'] == 1) { ?>
        <a class="item-markStar-as" href="<?php echo $query.'unstarred='.$itemHash; ?>"><span class="label">unstarred</span></a>
        <?php } else { ?>
        <a class="item-markStar-as" href="<?php echo $query.'starred='.$itemHash; ?>"><span class="label label-expanded">starred</span></a>
        <?php }?>
      </div>
      <div class="clear"></div>
      <?php } ?>
    </div>
  </li>
  <?php } ?>
</ul>
<div class="clear"></div>
