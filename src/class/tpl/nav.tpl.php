<div id="menu" class="navbar">
  <div class="navbar-inner">
    <div class="container">
      
      <!-- .btn-navbar is used as the toggle for collapsed navbar content -->
      <a id="menu-toggle" class="btn btn-navbar" data-toggle="collapse" data-target="#menu-collapse">
        menu
      </a>
      <a id="nav-home" class="brand" href="<?php echo MyTool::getUrl(); ?>" title="Home">
        <span class="ico ico-navbar">
          <span class="ico-square"></span>
          <span class="ico-triangle-up"></span>
          <span class="ico-home-line"></span>
        </span>
        &nbsp;
        &nbsp;
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
             case 'index':
             ?>
          <?php foreach(array_keys($menu) as $menuOpt) { ?>
          <?php switch($menuOpt) {
                case 'menuView': ?>
          <?php if ($view === 'expanded') { ?>
          <li><a href="<?php echo $query.'view=list'; ?>" title="Switch to list view (one line per item)">View as list</a></li>
          <?php } else { ?>
          <li><a href="<?php echo $query.'view=expanded'; ?>" title="Switch to expanded view">View as expanded</a></li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuListFeeds': ?>
          <?php if ($listFeeds == 'show') { ?>
          <li><a href="<?php echo $query.'listFeeds=hide'; ?>" title="Hide the feeds list">Hide feeds list</a></li>
          <?php } else { ?>
          <li><a href="<?php echo $query.'listFeeds=show'; ?>" title="Show the feeds list">Show feeds list</a></li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuFilter': ?>
          <?php if ($filter === 'unread') { ?>
          <li><a href="<?php echo $query.'filter=all'; ?>" title="Filter: show all (read and unread) items">Show all items</a></li>
          <?php } else { ?>
          <li><a href="<?php echo $query.'filter=unread'; ?>" title="Filter: show unread items">Show unread items</a></li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuOrder': ?>
          <?php if ($order === 'newerFirst') { ?>
          <li><a href="<?php echo $query.'order=olderFirst'; ?>" title="Show older first items">Show older first</a></li>
          <?php } else { ?>
          <li><a href="<?php echo $query.'order=newerFirst'; ?>" title="Show newer first items">Show newer first</a></li>
          <?php } ?>
          <?php break; ?>
          <?php case 'menuUpdate': ?>
          <li>
            <a href="<?php echo $query.'update='.$currentHash; ?>" class="admin" title="Update <?php echo $currentHashType; ?> manually">Update <?php echo $currentHashType; ?></a>
          </li>
          <?php break; ?>
          <?php case 'menuRead': ?>
          <li>
            <a href="<?php echo $query.'read='.$currentHash; ?>" class="admin" title="Mark <?php echo $currentHashType; ?> as read">Mark <?php echo $currentHashType; ?> as read</a>
          </li>
          <?php break; ?>
          <?php case 'menuUnread': ?>
          <li>
            <a href="<?php echo $query.'unread='.$currentHash; ?>" class="admin" title="Mark <?php echo $currentHashType; ?> as unread">Mark <?php echo $currentHashType; ?> as unread</a>
          </li>
          <?php break; ?>
          <?php case 'menuEdit': ?>
          <li>
            <a href="<?php echo $query.'edit='.$currentHash; ?>" class="admin" title="Edit <?php echo $currentHashType; ?>">Edit <?php echo $currentHashType; ?></a>
          </li>
          <?php break; ?>
          <?php case 'menuAdd': ?>
          <li>
            <a href="<?php echo $query.'add'; ?>" class="admin" title="Add a new feed">Add a new feed</a>
          </li>
          <?php break; ?>
          <?php case 'menuHelp': ?>
          <li>
            <a href="<?php echo $query.'help'; ?>" title="Help : how to use KrISS feed">Help</a>
          </li>
          <?php break; ?>
          <?php default: ?>
          <?php break; ?>
          <?php } ?>
          <?php } ?>
          <?php if (Session::isLogged()) { ?>
          <li><a href="?config" class="admin" title="Configuration">Configuration</a></li>
          <li><a href="?logout" class="admin" title="Logout">Logout</a></li>
          <?php } else { ?>
          <li><a href="?login">Login</a></li>
          <?php } ?>
          <?php
             break;
             case 'config':
             ?>
          <li><a href="?import" class="admin" title="Import OPML file">Import</a></li>
          <li><a href="?export" class="admin" title="Export OPML file">Export</a></li>
          <li><a href="?logout" class="admin" title="Logout">Logout</a></li>
          <?php
             break;
             default:
             ?>
          <?php if (Session::isLogged()) { ?>
          <li><a href="?config" class="admin text-error" title="Configuration">Configuration</a></li>
          <li><a href="?logout" class="admin" title="Logout">Logout</a></li>
          <?php } else { ?>
          <li><a href="?login">Login</a></li>
          <?php } ?>
          <?php
             break;
             }
             ?>
        </ul>
      </div>
    </div>
  </div>
</div>
