<div id="list-feeds">
  <?php
     if ($listFeeds == 'show') {
     ?>
  <ul class="unstyled">
    <li id="all-subscriptions" class="folder">
      <h4><a class="mark-as" href="<?php echo ($feedsView['all']['nbUnread']==0?'?currentHash=all&unread':$query.'read').'=all'; ?>" title="Mark all as <?php echo ($feedsView['all']['nbUnread']==0?'unread':'read');?>"><span class="label"><?php echo $feedsView['all']['nbUnread']; ?></span></a><a href="<?php echo '?currentHash=all'; ?>"><?php echo $feedsView['all']['title']; ?></a></h4>
      <ul class="unstyled">
        <?php
           foreach ($feedsView['all']['feeds'] as $feedHash => $feed) {
        $atitle = trim(htmlspecialchars($feed['description']));
        if (empty($atitle) || $atitle == ' ') {
        $atitle = trim(htmlspecialchars($feed['title']));
        }
        if (isset($feed['error'])) {
        $atitle = $feed['error'];
        }
        ?>
        
        <li id="<?php echo 'feed-'.$feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'?><?php if ($autohide and $feed['nbUnread']== 0) { echo ' autohide-feed';} ?>">
          <?php if ($addFavicon) { ?>
          <img src="<?php echo grabFavicon($feed['htmlUrl'], $feedHash); ?>" height="16px" width="16px" title="favicon" alt="favicon"/>
          <?php } ?>
<a class="mark-as" href="<?php echo $query.'read='.$feedHash; ?>"><span class="label"><?php echo $feed['nbUnread']; ?></span></a><a class="feed<?php echo (isset($feed['error'])?' text-error':''); ?>" href="<?php echo '?currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
          
        </li>

        <?php
           }
           foreach ($feedsView['folders'] as $hashFolder => $folder) {
        $isOpen = $folder['isOpen'];
        ?>
        
        <li id="folder-<?php echo $hashFolder; ?>" class="folder<?php if ($autohide and $folder['nbUnread']== 0) { echo ' autohide-folder';} ?>">
          <h5>
            <a class="mark-as" href="<?php echo $query.'read='.$hashFolder; ?>"><span class="label"><?php echo $folder['nbUnread']; ?></span></a>
            <a class="folder-toggle" href="<?php echo $query.'toggleFolder='.$hashFolder; ?>" data-toggle="collapse" data-target="#folder-ul-<?php echo $hashFolder; ?>">
              <span class="ico">
                <span class="ico-circle"></span>
                <span class="ico-line-h"></span>
                <span class="ico-line-v<?php echo ($isOpen?' folder-toggle-open':' folder-toggle-close'); ?>"></span>
              </span>
            </a>
            <a href="<?php echo '?currentHash='.$hashFolder; ?>"><?php echo htmlspecialchars($folder['title']); ?></a>
          </h5>
          <ul id="folder-ul-<?php echo $hashFolder; ?>" class="collapse unstyled<?php echo $isOpen?' in':''; ?>">
            <?php
               foreach ($folder['feeds'] as $feedHash => $feed) {
            $atitle = trim(htmlspecialchars($feed['description']));
            if (empty($atitle) || $atitle == ' ') {
            $atitle = trim(htmlspecialchars($feed['title']));
            }
            if (isset($feed['error'])) {
            $atitle = $feed['error'];
            }
            ?>

            <li id="folder-<?php echo $hashFolder; ?>-feed-<?php echo $feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'?><?php if ($autohide and $feed['nbUnread']== 0) { echo ' autohide-feed';} ?>">
              
              <?php if ($addFavicon) { ?>
              <img src="<?php echo grabFavicon($feed['htmlUrl'], $feedHash); ?>" height="16px" width="16px" title="favicon" alt="favicon"/>
              <?php } ?>
              <a class="mark-as" href="<?php echo $query.'read='.$feedHash; ?>"><span class="label"><?php echo $feed['nbUnread']; ?></span></a><a class="feed<?php echo (isset($feed['error'])?' text-error':''); ?>" href="<?php echo '?currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
            </li>
            <?php } ?>
          </ul>
        </li>
        <?php
           }
           ?>
      </ul>
  </ul>
  <?php
     }
     ?>

</div>
