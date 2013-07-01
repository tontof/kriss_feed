
<div id="menu" class="navbar">
  <div class="navbar-inner">
    <div class="container">
      <a id="menu-toggle" class="btn btn-navbar" data-toggle="collapse" data-target="#menu-collapse" title="<?php echo Intl::msg('Menu'); ?>"><?php echo Intl::msg('Menu'); ?></a>
      <a id="nav-home" class="brand ico-home" href="<?php echo MyTool::getUrl(); ?>" title="<?php echo Intl::msg('Home'); ?>"></a>
      <?php if (isset($currentHashView)) { ?><span class="brand"><?php echo $currentHashView ?></span><?php } ?>

      <div id="menu-collapse" class="nav-collapse collapse">
        <ul class="nav"><?php
switch($template) {
  case 'stars':
  case 'index':
    foreach(array_keys($menu) as $menuOpt) {
      switch($menuOpt) {
        case 'menuView':
          if ($view === 'expanded') { ?>

          <li><a href="<?php echo $query.'view=list'; ?>" title="<?php echo Intl::msg('View as list'); ?>" class="menu-ico ico-list"><span class="menu-text menu-list"> <?php echo Intl::msg('View as list'); ?></span></a></li><?php } else { ?>

          <li><a href="<?php echo $query.'view=expanded'; ?>" title="<?php echo Intl::msg('View as expanded'); ?>" class="menu-ico ico-expanded"><span class="menu-text menu-expanded"> <?php echo Intl::msg('View as expanded'); ?></span></a></li><?php } break;
        case 'menuListFeeds':
          if ($listFeeds == 'show') { ?>

          <li><a href="<?php echo $query.'listFeeds=hide'; ?>" title="<?php echo Intl::msg('Hide feeds list'); ?>" class="menu-ico ico-list-feeds-hide"><span class="menu-text menu-list-feeds-hide"> <?php echo Intl::msg('Hide feeds list'); ?></span></a></li><?php } else { ?>

          <li><a href="<?php echo $query.'listFeeds=show'; ?>" title="<?php echo Intl::msg('Show feeds list'); ?>" class="menu-ico ico-list-feeds-show"><span class="menu-text menu-list-feeds-show"> <?php echo Intl::msg('Show feeds list'); ?></span></a></li><?php } break;
        case 'menuFilter':
          if ($filter === 'unread') { ?>

          <li><a href="<?php echo $query.'filter=all'; ?>" title="<?php echo Intl::msg('Show all items'); ?>" class="menu-ico ico-filter-all"><span class="menu-text menu-filter-all"> <?php echo Intl::msg('Show all items'); ?></span></a></li><?php } else { ?>

          <li><a href="<?php echo $query.'filter=unread'; ?>" title="<?php echo Intl::msg('Show unread items'); ?>" class="menu-ico ico-filter-unread"><span class="menu-text menu-filter-unread"> <?php echo Intl::msg('Show unread items'); ?></span></a></li><?php } break;
        case 'menuOrder':
          if ($order === 'newerFirst') { ?>

          <li><a href="<?php echo $query.'order=olderFirst'; ?>" title="<?php echo Intl::msg('Show older first'); ?>" class="menu-ico ico-order-older"><span class="menu-text menu-order"> <?php echo Intl::msg('Show older first'); ?></span></a></li><?php } else { ?>

          <li><a href="<?php echo $query.'order=newerFirst'; ?>" title="<?php echo Intl::msg('Show newer first'); ?>" class="menu-ico ico-order-newer"><span class="menu-text menu-order"> <?php echo Intl::msg('Show newer first'); ?></span></a></li><?php } break;
        case 'menuUpdate':
          switch($currentHashType) {
            case 'all':
              $intl = Intl::msg('Update all');
              break;
            case 'folder':
              $intl = Intl::msg('Update folder');
              break;
            case 'feed':
              $intl = Intl::msg('Update feed');
              break;
            default;
              break;
          } ?>

          <li><a href="<?php echo $query.'update='.$currentHash; ?>" title="<?php echo $intl; ?>" class="menu-ico ico-update"><span class="menu-text menu-update"> <?php echo $intl; ?></span></a></li><?php
          break; 
        case 'menuRead':
          switch($currentHashType) {
            case 'all':
              $intl = Intl::msg('Mark all as read');
              break;
            case 'folder':
              $intl = Intl::msg('Mark folder as read');
              break;
            case 'feed':
              $intl = Intl::msg('Mark feed as read');
              break;
            default;
              break;
          } ?>

          <li><a href="<?php echo $query.'read='.$currentHash; ?>" title="<?php echo $intl; ?>" class="menu-ico ico-mark-as-read"><span class="menu-text menu-mark-as-read"> <?php echo $intl; ?></span></a></li><?php
          break;
        case 'menuUnread':
          switch($currentHashType) {
            case 'all':
              $intl = Intl::msg('Mark all as unread');
              break;
            case 'folder':
              $intl = Intl::msg('Mark folder as unread');
              break;
            case 'feed':
              $intl = Intl::msg('Mark feed as unread');
              break;
            default;
              break;
          } ?>

          <li><a href="<?php echo $query.'unread='.$currentHash; ?>" title="<?php echo $intl; ?>" class="menu-ico ico-mark-as-unread"><span class="menu-text menu-mark-as-unread"> <?php echo $intl; ?></span></a></li><?php
          break;
        case 'menuEdit':
          switch($currentHashType) {
            case 'all':
              $intl = Intl::msg('Edit all');
              break;
            case 'folder':
              $intl = Intl::msg('Edit folder');
              break;
            case 'feed':
              $intl = Intl::msg('Edit feed');
              break;
            default;
              break;
          } ?>

          <li><a href="<?php echo $query.'edit='.$currentHash; ?>" title="<?php echo $intl; ?>" class="menu-ico ico-edit"><span class="menu-text menu-edit"> <?php echo $intl; ?></span></a></li><?php
          break;
        case 'menuAdd': ?>

          <li><a href="<?php echo $query.'add'; ?>" title="<?php echo Intl::msg('Add a new feed'); ?>" class="menu-ico ico-add-feed"><span class="menu-text menu-add-feed"> <?php echo Intl::msg('Add a new feed'); ?></span></a></li><?php
          break;
        case 'menuHelp': ?>

          <li><a href="<?php echo $query.'help'; ?>" title="<?php echo Intl::msg('Help'); ?>" class="menu-ico ico-help"><span class="menu-text menu-help"> <?php echo Intl::msg('Help'); ?></span></a></li><?php
          break;
        case 'menuStars':
             if($template === 'index'){ ?>

          <li><a href="<?php echo $query.'stars'; ?>" title="<?php echo Intl::msg('Starred items'); ?>" class="menu-ico ico-star"><span class="menu-text menu-help"> <?php echo Intl::msg('Starred items'); ?></span></a></li><?php
             }
          break;
        default:
          break;
      }
    }

    if ($kf->kfc->isLogged()) { ?>

          <li><a href="?config" title="<?php echo Intl::msg('Configuration'); ?>" class="menu-ico ico-config"><span class="menu-text menu-config"> <?php echo Intl::msg('Configuration'); ?></span></a></li><?php
    }
    if (Session::isLogged()) { ?>

          <li><a href="?logout" title="<?php echo Intl::msg('Sign out'); ?>" class="menu-ico ico-logout"><span class="menu-text menu-logout"> <?php echo Intl::msg('Sign out'); ?></span></a></li><?php
    } else { ?>

          <li><a href="?login" title="<?php echo Intl::msg('Sign in'); ?>" class="menu-ico ico-login"><span class="menu-text menu-login"> <?php echo Intl::msg('Sign in'); ?></span></a></li><?php
    }

    break;
  case 'config': ?>
          <li><a href="?password" title="<?php echo Intl::msg('Change password'); ?>"> <?php echo Intl::msg('Change password'); ?></a></li>
          <li><a href="?import" title="<?php echo Intl::msg('Import opml file'); ?>"> <?php echo Intl::msg('Import opml file'); ?></a></li>
          <li><a href="?export" title="<?php echo Intl::msg('Export opml file'); ?>"> <?php echo Intl::msg('Export opml file'); ?></a></li>
          <li><a href="?plugins" title="<?php echo Intl::msg('Plugins management'); ?>"> <?php echo Intl::msg('Plugins management'); ?></a></li>
          <li><a href="?logout" title="<?php echo Intl::msg('Sign out'); ?>"> <?php echo Intl::msg('Sign out'); ?></a></li><?php
    break;
  default:
    if ($kf->kfc->isLogged()) { ?>

          <li><a href="?config" title="<?php echo Intl::msg('Configuration'); ?>"> <?php echo Intl::msg('Configuration'); ?></a></li><?php
    }
    if (Session::isLogged()) { ?>

          <li><a href="?logout" title="<?php echo Intl::msg('Sign out'); ?>"> <?php echo Intl::msg('Sign out'); ?></a></li><?php
    } else { ?>

          <li><a href="?login" title="<?php echo Intl::msg('Sign in'); ?>"> <?php echo Intl::msg('Sign in'); ?></a></li><?php
    } 
    break;
} ?>

        </ul>
        <div class="clear"></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>
