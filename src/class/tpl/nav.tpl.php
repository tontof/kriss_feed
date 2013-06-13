<div id="menu" class="navbar">
  <div class="navbar-inner">
    <div class="container">
      
      <!-- .btn-navbar is used as the toggle for collapsed navbar content -->
      <a id="menu-toggle" class="btn btn-navbar" data-toggle="collapse" data-target="#menu-collapse">
        menu
      </a>

      <a id="nav-home" class="brand ico-home" href="<?php echo MyTool::getUrl(); ?>" title="Home">
      </a>

      <?php if (isset($currentHashView)) { ?>
      <span class="brand">
        <?php echo $currentHashView ?>
      </span>
      <?php } ?>

      <div id="menu-collapse" class="nav-collapse collapse">
        <ul class="nav">
          <?php
             switch($template) {
             case 'stars':
             case 'index':
             ?>
          <?php foreach(array_keys($menu) as $menuOpt) { ?>
          <?php switch($menuOpt) {
                case 'menuView': ?>
          <?php if ($view === 'expanded') { ?>
          <li>
            <a href="<?php echo $query.'view=list'; ?>" title="Switch to list view (one line per item)" class="menu-ico ico-list">
              <span class="menu-text menu-list">
                View as list
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'view=expanded'; ?>" title="Switch to expanded view" class="menu-ico ico-expanded">
              <span class="menu-text menu-expanded">
                View as expanded
              </span>
            </a>
          </li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuListFeeds': ?>
          <?php if ($listFeeds == 'show') { ?>
          <li>
            <a href="<?php echo $query.'listFeeds=hide'; ?>" title="Hide the feeds list" class="menu-ico ico-list-feeds-hide">
              <span class="menu-text menu-list-feeds-hide">
                Hide feeds list
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'listFeeds=show'; ?>" title="Show the feeds list" class="menu-ico ico-list-feeds-show">
              <span class="menu-text menu-list-feeds-show">
                Show feeds list
              </span>
            </a>
          </li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuFilter': ?>
          <?php if ($filter === 'unread') { ?>
          <li>
            <a href="<?php echo $query.'filter=all'; ?>" title="Filter: show all (read and unread) items" class="menu-ico ico-filter-all">
              <span class="menu-text menu-filter-all">
                Show all items
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'filter=unread'; ?>" title="Filter: show unread items" class="menu-ico ico-filter-unread">
              <span class="menu-text menu-filter-unread">
                Show unread items
              </span>
            </a>
          </li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuOrder': ?>
          <?php if ($order === 'newerFirst') { ?>
          <li>
            <a href="<?php echo $query.'order=olderFirst'; ?>" title="Show older first items" class="menu-ico ico-order-older">
              <span class="menu-text menu-order">
                Show older first
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'order=newerFirst'; ?>" title="Show newer first items" class="menu-ico ico-order-newer">
              <span class="menu-text menu-order">         
                Show newer first
              </span>
            </a>
          </li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuUpdate': ?>
          <li>
            <a href="<?php echo $query.'update='.$currentHash; ?>" title="Update <?php echo $currentHashType; ?> manually" class="menu-ico ico-update">
              </span>
              <span class="menu-text menu-update">
                Update <?php echo $currentHashType; ?>
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuRead': ?>
          <li>
            <a href="<?php echo $query.'read='.$currentHash; ?>" title="Mark <?php echo $currentHashType; ?> as read" class="menu-ico ico-mark-as-read">
              <span class="menu-text menu-mark-as-read">
                Mark <?php echo $currentHashType; ?> as read
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuUnread': ?>
          <li>
            <a href="<?php echo $query.'unread='.$currentHash; ?>" title="Mark <?php echo $currentHashType; ?> as unread" class="menu-ico ico-mark-as-unread">
              <span class="menu-text menu-mark-as-unread">
                Mark <?php echo $currentHashType; ?> as unread
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuEdit': ?>
          <li>
            <a href="<?php echo $query.'edit='.$currentHash; ?>" title="Edit <?php echo $currentHashType; ?>" class="menu-ico ico-edit">
              <span class="menu-text menu-edit">
                Edit <?php echo $currentHashType; ?>
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuAdd': ?>
          <li>
            <a href="<?php echo $query.'add'; ?>" title="Add a new feed" class="menu-ico ico-add-feed">
              <span class="menu-text menu-add-feed">
                Add a new feed
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuHelp': ?>
          <li>
            <a href="<?php echo $query.'help'; ?>" title="Help : how to use KrISS feed" class="menu-ico ico-help">
              <span class="menu-text menu-help">
                Help
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuStars': 
             if($template === 'index'){
             ?>
          <li>
            <a href="<?php echo $query.'stars'; ?>" title="Show starred items" class="menu-ico ico-star">
              <span class="menu-text menu-help">
                Starred Items
              </span>
            </a>
          </li>
          <?php }
             break; ?>
          <?php default: ?>
          <?php break; ?>
          <?php } ?>
          <?php } ?>
          <?php if ($kf->kfc->isLogged()) { ?>
          <li>
            <a href="?config" title="Configuration" class="menu-ico ico-config">
              <span class="menu-text menu-config">
                Configuration
              </span>
            </a>
          </li>
          <?php } ?>
          <?php if (Session::isLogged()) { ?>
          <li>
            <a href="?logout" title="Logout" class="menu-ico ico-logout">
              <span class="menu-text menu-logout">
                Logout
              </span>
            </a></li>
          <?php } else { ?>
          <li>
            <a href="?login" class="menu-ico ico-login">
              <span class="menu-text menu-login">
                Login
              </span>
            </a>
          </li>
          <?php } ?>
          <?php
             break;
             case 'config':
             ?>
          <li><a href="?password" title="Change your password">Change password</a></li>
          <li><a href="?import" title="Import OPML file">Import</a></li>
          <li><a href="?export" title="Export OPML file">Export</a></li>
          <li><a href="?logout" title="Logout">Logout</a></li>
          <?php
             break;
             default:
             ?>
          <?php if ($kf->kfc->isLogged()) { ?>
          <li><a href="?config" title="Configuration">Configuration</a></li>
          <?php } ?>
          <?php if (Session::isLogged()) { ?>
          <li><a href="?logout" title="Logout">Logout</a></li>
          <?php } else { ?>
          <li><a href="?login">Login</a></li>
          <?php } ?>
          <?php
             break;
             }
             ?>
        </ul>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>
