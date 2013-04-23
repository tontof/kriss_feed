<div id="menu" class="navbar">
  <div class="navbar-inner">
    <div class="container">
      
      <!-- .btn-navbar is used as the toggle for collapsed navbar content -->
      <a id="menu-toggle" class="btn btn-navbar" data-toggle="collapse" data-target="#menu-collapse">
        menu
      </a>

      <a id="nav-home" class="brand" href="<?php echo MyTool::getUrl(); ?>" title="Home">
        <span class="ico-navbar">
          <span class="ico">
            <span class="ico-home-square"></span>
            <span class="ico-home-triangle"></span>
            <span class="ico-home-line"></span>
          </span>
        </span>
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
            <a href="<?php echo $query.'view=list'; ?>" title="Switch to list view (one line per item)">
              <span class="menu-ico ico-list">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-list-line-1"></span>
                  <span class="ico-list-line-2"></span>
                  <span class="ico-list-line-3"></span>
                  <span class="ico-list-line-4"></span>
                  <span class="ico-list-line-5"></span>
                </span>
              </span>
              <span class="menu-text menu-list">
                View as list
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'view=expanded'; ?>" title="Switch to expanded view">
              <span class="menu-ico ico-expanded">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-expanded-line-1"></span>
                  <span class="ico-expanded-line-2"></span>
                  <span class="ico-expanded-line-3"></span>
                </span>
              </span>
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
            <a href="<?php echo $query.'listFeeds=hide'; ?>" title="Hide the feeds list">
              <span class="menu-ico ico-list-feeds-hide">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-list-feeds-line-5"></span>
                </span>
              </span>
              <span class="menu-text menu-list-feeds-hide">
                Hide feeds list
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'listFeeds=show'; ?>" title="Show the feeds list">
              <span class="menu-ico ico-list-feeds-show">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-list-feeds-line-1"></span>
                  <span class="ico-list-feeds-line-2"></span>
                  <span class="ico-list-feeds-line-3"></span>
                  <span class="ico-list-feeds-line-4"></span>
                </span>
              </span>
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
            <a href="<?php echo $query.'filter=all'; ?>" title="Filter: show all (read and unread) items">
              <span class="menu-ico ico-filter-all">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-item-circle-1"></span>
                  <span class="ico-item-circle-2"></span>
                  <span class="ico-item-circle-3"></span>
                  <span class="ico-item-line-1"></span>
                  <span class="ico-item-line-2"></span>
                  <span class="ico-item-line-3"></span>
                </span>
              </span>
              <span class="menu-text menu-filter-all">
                Show all items
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a href="<?php echo $query.'filter=unread'; ?>" title="Filter: show unread items">
              <span class="menu-ico ico-filter-unread">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-item-circle-1"></span>
                  <span class="ico-item-circle-2"></span>
                  <span class="ico-item-line-1"></span>
                  <span class="ico-item-line-2"></span>
                </span>
              </span>
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
            <a href="<?php echo $query.'order=olderFirst'; ?>" title="Show older first items">
              <span class="menu-ico ico-order-older">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-w-triangle-down"></span>
                  <span class="ico-w-line-v"></span>
                </span>
              </span>
              <span class="menu-text menu-order">
                Show older first
              </span>
            </a>
          </li>
          <?php } else { ?>
          <li>
            <a class="repeat" href="<?php echo $query.'order=newerFirst'; ?>" title="Show newer first items">
              <span class="menu-ico ico-order-newer">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-w-triangle-up"></span>
                  <span class="ico-w-line-v"></span>
                </span>
              </span>
              <span class="menu-text menu-order">         
                Show newer first
              </span>
            </a>
          </li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuUpdate': ?>
          <li>
            <a href="<?php echo $query.'update='.$currentHash; ?>" title="Update <?php echo $currentHashType; ?> manually">
              <span class="menu-ico ico-update">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-update-circle"></span>
                  <span class="ico-update-triangle"></span>
                </span>
              </span>
              <span class="menu-text menu-update">
                Update <?php echo $currentHashType; ?>
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuRead': ?>
          <li>
            <a href="<?php echo $query.'read='.$currentHash; ?>" title="Mark <?php echo $currentHashType; ?> as read">
              <span class="menu-ico ico-mark-as-read">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-eye-triangle-left"></span>
                  <span class="ico-eye-triangle-right"></span>
                  <span class="ico-eye-circle-1"></span>
                  <span class="ico-eye-circle-2"></span>
                </span>
              </span>
              <span class="menu-text menu-mark-as-read">
                Mark <?php echo $currentHashType; ?> as read
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuUnread': ?>
          <li>
            <a href="<?php echo $query.'unread='.$currentHash; ?>" title="Mark <?php echo $currentHashType; ?> as unread">
              <span class="menu-ico ico-mark-as-unread">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-eye-triangle-left"></span>
                  <span class="ico-eye-triangle-right"></span>
                  <span class="ico-eye-circle-3"></span>
                </span>
              </span>
              <span class="menu-text menu-mark-as-unread">
                Mark <?php echo $currentHashType; ?> as unread
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuEdit': ?>
          <li>
            <a href="<?php echo $query.'edit='.$currentHash; ?>" title="Edit <?php echo $currentHashType; ?>">
              <span class="menu-ico ico-edit">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-edit-square"></span>
                  <span class="ico-edit-circle-1"></span>
                  <span class="ico-edit-circle-2"></span>
                  <span class="ico-edit-circle-3"></span>
                  <span class="ico-edit-circle-4"></span>
                </span>
              </span>
              <span class="menu-text menu-edit">
                Edit <?php echo $currentHashType; ?>
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuAdd': ?>
          <li>
            <a href="<?php echo $query.'add'; ?>" title="Add a new feed">
              <span class="menu-ico ico-add-feed">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-w-line-h"></span>
                  <span class="ico-w-line-v"></span>
                </span>
              </span>
              <span class="menu-text menu-add-feed">
                Add a new feed
              </span>
            </a>
          </li>
          <?php break; ?>
          <?php case 'menuHelp': ?>
          <li>
            <a href="<?php echo $query.'help'; ?>" title="Help : how to use KrISS feed">
              <span class="menu-ico ico-help">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-help-line-1"></span>
                  <span class="ico-help-line-2"></span>
                  <span class="ico-help-line-3"></span>
                  <span class="ico-help-line-4"></span>
                </span>
              </span>
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
            <a href="<?php echo $query.'stars'; ?>" title="Show starred items">Starred Items</a>
          </li>
          <?php }
             break; ?>
          <?php default: ?>
          <?php break; ?>
          <?php } ?>
          <?php } ?>
          <?php if ($kf->kfc->isLogged()) { ?>
          <li>
            <a href="?config" title="Configuration">
              <span class="menu-ico ico-config">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-config-circle-1"></span>
                  <span class="ico-config-circle-2"></span>
                  <span class="ico-config-circle-3"></span>
                </span>
              </span>
              <span class="menu-text menu-config">
                Configuration
              </span>
            </a>
          </li>
          <?php } ?>
          <?php if (Session::isLogged()) { ?>
          <li>
            <a href="?logout" title="Logout">
              <span class="menu-ico ico-logout">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-onoff-circle"></span>
                  <span class="ico-onoff-line"></span>
                </span>
              </span>
              <span class="menu-text menu-logout">
                Logout
              </span>
            </a></li>
          <?php } else { ?>
          <li>
            <a href="?login">
              <span class="menu-ico ico-login">
                <span class="ico">
                  <span class="ico-b-disc"></span>
                  <span class="ico-onoff-circle"></span>
                  <span class="ico-onoff-line"></span>
                </span>
              </span>
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
