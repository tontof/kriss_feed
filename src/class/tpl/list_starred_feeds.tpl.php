<div id="list-feeds">
  <?php
     $autohide=true;
     if ($listFeeds == 'show') {
     ?>
  <ul class="unstyled">
    <li id="all-subscriptions" class="folder">
      <h4><a class="mark-as" href="?stars&currentHash=all"><span class="label"><?php echo $feedsView['all']['nbAll']; ?></span></a><a href="<?php echo '?stars&currentHash=all'; ?>"><?php echo $feedsView['all']['title']; ?></a></h4>
      <ul class="unstyled">
        <?php
           foreach ($feedsView['all']['feeds'] as $feedHash => $feed) {
        $atitle = trim(htmlspecialchars($feed['description']));
        if (empty($atitle) || $atitle == ' ') {
        $atitle = trim(htmlspecialchars($feed['title']));
        }
        ?>
        
        <li id="<?php echo 'feed-'.$feedHash; ?>" class="feed<?php if ($feed['nbAll']!== 0) echo ' has-starred'?><?php if ($autohide and $feed['nbAll']== 0) { echo ' autohide-feed';} ?>">
        <?php if ($addFavicon) { ?>
          <span class="feed-favicon">
          <img src="<?php echo $ks->getFaviconFeed($feedHash); ?>" height="16" width="16" title="favicon" alt="favicon"/>
          </span>
          <?php } ?>
        <a class="mark-as" href="<?php echo $query.'currentHash='.$feedHash; ?>"><span class="label"><?php echo $feed['nbAll']; ?></span></a><a class="feed" href="<?php echo '?stars&currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
          
        </li>

        <?php
           }
           foreach ($feedsView['folders'] as $hashFolder => $folder) {
        $isOpen = $folder['isOpen'];
        ?>
        
        <li id="folder-<?php echo $hashFolder; ?>" class="folder<?php if ($autohide and $folder['nbAll']== 0) { echo ' autohide-folder';} ?>">
          <h5>
             <a class="mark-as" href="<?php echo $query.'currentHash='.$hashFolder; ?>"><span class="label"><?php echo $folder['nbAll']; ?></span></a>
            <a class="folder-toggle" href="<?php echo $query.'toggleFolder='.$hashFolder; ?>" data-toggle="collapse" data-target="#folder-ul-<?php echo $hashFolder; ?>">
              <span class="ico">
                <span class="ico-b-disc"></span>
                <span class="ico-w-line-h"></span>
                <span class="ico-w-line-v<?php echo ($isOpen?' folder-toggle-open':' folder-toggle-close'); ?>"></span>
              </span>
            </a>
            <a href="<?php echo '?stars&currentHash='.$hashFolder; ?>"><?php echo htmlspecialchars($folder['title']); ?></a>
          </h5>
          <ul id="folder-ul-<?php echo $hashFolder; ?>" class="collapse unstyled<?php echo $isOpen?' in':''; ?>">
            <?php
               foreach ($folder['feeds'] as $feedHash => $feed) {
            $atitle = trim(htmlspecialchars($feed['description']));
            if (empty($atitle) || $atitle == ' ') {
            $atitle = trim(htmlspecialchars($feed['title']));
            }
            ?>

            <li id="folder-<?php echo $hashFolder; ?>-feed-<?php echo $feedHash; ?>" class="feed<?php if ($feed['nbAll']!== 0) echo ' has-unread'?><?php if ($autohide and $feed['nbAll']== 0) { echo ' autohide-feed';} ?>">
              
              <?php if ($addFavicon) { ?>
              <span class="feed-favicon">
                <img src="<?php echo $ks->getFaviconFeed($feedHash); ?>" height="16" width="16" title="favicon" alt="favicon"/>
              </span>
              <?php } ?>
            <a class="mark-as" href="<?php echo $query.'currentHash='.$feedHash; ?>"><span class="label"><?php echo $feed['nbAll']; ?></span></a><a class="feed" href="<?php echo '?stars&currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
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
