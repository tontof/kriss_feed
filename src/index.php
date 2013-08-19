<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk
define('BASE_URL', '');
define('DATA_DIR', 'data');
define('INC_DIR', 'inc');
define('CACHE_DIR', DATA_DIR.'/cache');
define('FAVICON_DIR', INC_DIR.'/favicon');

define('DATA_FILE', DATA_DIR.'/data.php');
define('STAR_FILE', DATA_DIR.'/star.php');
define('ITEM_FILE', DATA_DIR.'/item.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('BAN_FILE', DATA_DIR.'/ipbans.php');
define('UPDATECHECK_FILE', DATA_DIR.'/lastupdatecheck.txt');
// Updates check frequency. 86400 seconds = 24 hours
define('UPDATECHECK_INTERVAL', 86400);

define('FEED_VERSION', 7);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

define('MIN_TIME_UPDATE', 5); // Minimum accepted time for update


// fix some warning
date_default_timezone_set('Europe/Paris');


class FeedConf
{
    private $_file = '';

    public $login = '';

    public $hash = '';

    public $disableSessionProtection = false;

    public $salt = '';

    public $title = "Kriss feed";

    public $redirector = '';

    public $locale = 'en_GB';

    public $shaarli = '';

    public $maxItems = 100;

    public $maxUpdate = 60;

    public $order = 'newerFirst';

    public $autoreadItem = false;

    public $autoreadPage = false;

    public $autoUpdate = false;

    public $autohide = false;

    public $autofocus = true;

    public $addFavicon = false;

    public $preload = false;

    public $blank = false;

    public $visibility = 'private';

    public $version;

    public $view = 'list';

    public $filter = 'unread';

    public $listFeeds = 'show';

    public $byPage = 10;

    public $currentHash = 'all';

    public $currentPage = 1;

    public $lang = '';

    public $menuView = 1;
    public $menuListFeeds = 2;
    public $menuFilter = 3;
    public $menuOrder = 4;
    public $menuUpdate = 5;
    public $menuRead = 6;
    public $menuUnread = 7;
    public $menuEdit = 8;
    public $menuAdd = 9;
    public $menuHelp = 10;
    public $menuStars = 11;

    public $pagingItem = 1;
    public $pagingPage = 2;
    public $pagingByPage = 3;
    public $pagingMarkAs = 4;

    public function __construct($configFile, $version)
    {
        $this->_file = $configFile;
        $this->version = $version;
        $this->lang = Intl::$lang;

        // Loading user config
        if (file_exists($this->_file)) {
            include_once $this->_file;
        } else {
            $this->_install();
        }

        Session::$disableSessionProtection = $this->disableSessionProtection;

        if ($this->addFavicon) {
            /* favicon dir */
            if (!is_dir(INC_DIR)) {
                if (!@mkdir(INC_DIR, 0755)) {
                    $pb->assign('message', sprintf(Intl::msg('Can not create %s directory, check permissions'), INC_DIR));
                    $pb->renderPage('message');
                }
            }
            if (!is_dir(FAVICON_DIR)) {
                if (!@mkdir(FAVICON_DIR, 0755)) {
                    $pb->assign('message', sprintf(Intl::msg('Can not create %s directory, check permissions'), FAVICON_DIR));
                    $pb->renderPage('message');
                }
            }
        }

        if ($this->isLogged()) {
            unset($_SESSION['view']);
            unset($_SESSION['listFeeds']);
            unset($_SESSION['filter']);
            unset($_SESSION['order']);
            unset($_SESSION['byPage']);
            unset($_SESSION['lang']);
        }

        $view = $this->getView();
        $listFeeds = $this->getListFeeds();
        $filter = $this->getFilter();
        $order = $this->getOrder();
        $byPage = $this->getByPage();
        $lang = $this->getLang();

        if ($this->view != $view
            || $this->listFeeds != $listFeeds
            || $this->filter != $filter
            || $this->order != $order
            || $this->byPage != $byPage
            || $this->lang != $lang
        ) {
            $this->view = $view;
            $this->listFeeds = $listFeeds;
            $this->filter = $filter;
            $this->order = $order;
            $this->byPage = $byPage;
            $this->lang = $lang;

            $this->write();
        }

        if (!$this->isLogged()) {
            $_SESSION['view'] = $view;
            $_SESSION['listFeeds'] = $listFeeds;
            $_SESSION['filter'] = $filter;
            $_SESSION['order'] = $order;
            $_SESSION['byPage'] = $byPage;
            $_SESSION['lang'] = $lang;
        }

        Intl::$lang = $this->lang;
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            $this->write();

            FeedPage::init(
                array(
                    'base' => '',
                    'class' => 'text-success',
                    'message' => Intl::msg('Your simple and smart (or stupid) feed reader is now configured.'),
                    'referer' => MyTool::getUrl().'?import',
                    'button' => Intl::msg('Continue'),
                    'version' => $this->version,
                    'pagetitle' => 'KrISS feed installation'
                )
            );
            FeedPage::messageTpl();
        } else {
            FeedPage::init(
                array(
                    'base' => '',
                    'version' => $this->version,
                    'pagetitle' => Intl::msg('KrISS feed installation')
                )
            );
            FeedPage::installTpl();
        }
        exit();
    }

    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            // get setter
            $method = 'set'.ucfirst($key);
            // if setter exists just call it
            // (php is not case-sensitive with functions)
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        $this->write();
    }

    public function getLang()
    {
        $lang = $this->lang;
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
        } else if (isset($_SESSION['lang'])) {
            $lang = $_SESSION['lang'];
        }

        if (!in_array($lang, array_keys(Intl::$langList))) {
            $lang = $this->lang;
        }

        return $lang;
    }

    public function getView()
    {
        $view = $this->view;
        if (isset($_GET['view'])) {
            if ($_GET['view'] == 'expanded') {
                $view = 'expanded';
            }
            if ($_GET['view'] == 'list') {
                $view = 'list';
            }
        } else if (isset($_SESSION['view'])) {
            $view = $_SESSION['view'];
        }

        return $view;
    }

    public function getFilter()
    {
        $filter = $this->filter;
        if (isset($_GET['filter'])) {
            if ($_GET['filter'] == 'unread') {
                $filter = 'unread';
            }
            if ($_GET['filter'] == 'all') {
                $filter = 'all';
            }
        } else if (isset($_SESSION['filter'])) {
            $filter = $_SESSION['filter'];
        }

        return $filter;
    }

    public function getListFeeds()
    {
        $listFeeds = $this->listFeeds;
        if (isset($_GET['listFeeds'])) {
            if ($_GET['listFeeds'] == 'show') {
                $listFeeds = 'show';
            }
            if ($_GET['listFeeds'] == 'hide') {
                $listFeeds = 'hide';
            }
        } else if (isset($_SESSION['listFeeds'])) {
            $listFeeds = $_SESSION['listFeeds'];
        }

        return $listFeeds;
    }

    public function getByPage()
    {
        $byPage = $this->byPage;
        if (isset($_GET['byPage']) && is_numeric($_GET['byPage']) && $_GET['byPage'] > 0) {
            $byPage = $_GET['byPage'];
        } else if (isset($_SESSION['byPage'])) {
            $byPage = $_SESSION['byPage'];
        }

        return $byPage;
    }

    public function getOrder()
    {
        $order = $this->order;
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'newerFirst') {
                $order = 'newerFirst';
            }
            if ($_GET['order'] === 'olderFirst') {
                $order = 'olderFirst';
            }
        } else if (isset($_SESSION['order'])) {
            $order = $_SESSION['order'];
        }

        return $order;
    }

    public function getCurrentHash()
    {
        $currentHash = $this->currentHash;
        if (isset($_GET['currentHash'])) {
            $currentHash = preg_replace('/[^a-zA-Z0-9-_@]/', '', substr(trim($_GET['currentHash'], '/'), 0, 6));
        }

        if (empty($currentHash)) {
            $currentHash = 'all';
        }

        return $currentHash;
    }

    public function getCurrentPage()
    {
        $currentPage = $this->currentPage;
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $currentPage = (int) $_GET['page'];
        } else if (isset($_GET['previousPage']) && !empty($_GET['previousPage'])) {
            $currentPage = (int) $_GET['previousPage'] - 1;
            if ($currentPage < 1) {
                $currentPage = 1;
            }
        } else if (isset($_GET['nextPage']) && !empty($_GET['nextPage'])) {
            $currentPage = (int) $_GET['nextPage'] + 1;
        }

        return $currentPage;
    }

    public function setDisableSessionProtection($disableSessionProtection)
    {
        $this->disableSessionProtection = $disableSessionProtection;
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    public function setHash($pass)
    {
        $this->hash = sha1($pass.$this->login.$this->salt);
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function setRedirector($redirector)
    {
        $this->redirector = $redirector;
    }

    public function setAutoreadPage($autoreadPage)
    {
        $this->autoreadPage = $autoreadPage;
    }

    public function setAutoUpdate($autoUpdate)
    {
        $this->autoUpdate = $autoUpdate;
    }

    public function setAutoreadItem($autoreadItem)
    {
        $this->autoreadItem = $autoreadItem;
    }

    public function setAutohide($autohide)
    {
        $this->autohide = $autohide;
    }

    public function setAutofocus($autofocus)
    {
        $this->autofocus = $autofocus;
    }

    public function setAddFavicon($addFavicon)
    {
        $this->addFavicon = $addFavicon;
    }

    public function setPreload($preload)
    {
        $this->preload = $preload;
    }

    public function setShaarli($url)
    {
        $this->shaarli = $url;
    }

    public function setMaxUpdate($max)
    {
        $this->maxUpdate = $max;
    }

    public function setMaxItems($max)
    {
        $this->maxItems = $max;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function setBlank($blank)
    {
        $this->blank = $blank;
    }

    public function getMenu()
    {
        $menu = array();

        if ($this->menuView != 0) {
            $menu['menuView'] = $this->menuView;
        }
        if ($this->menuListFeeds != 0) {
            $menu['menuListFeeds'] = $this->menuListFeeds;
        }
        if ($this->menuFilter != 0) {
            $menu['menuFilter'] = $this->menuFilter;
        }
        if ($this->menuOrder != 0) {
            $menu['menuOrder'] = $this->menuOrder;
        }
        if ($this->menuUpdate != 0) {
            $menu['menuUpdate'] = $this->menuUpdate;
        }
        if ($this->menuRead != 0) {
            $menu['menuRead'] = $this->menuRead;
        }
        if ($this->menuUnread != 0) {
            $menu['menuUnread'] = $this->menuUnread;
        }
        if ($this->menuEdit != 0) {
            $menu['menuEdit'] = $this->menuEdit;
        }
        if ($this->menuAdd != 0) {
            $menu['menuAdd'] = $this->menuAdd;
        }
        if ($this->menuHelp != 0) {
            $menu['menuHelp'] = $this->menuHelp;
        }

        if ($this->menuStars != 0) {
            $menu['menuStars'] = $this->menuStars;
        }

        asort($menu);

        return $menu;
    }

    public function getPaging()
    {
        $paging = array();

        if ($this->pagingItem != 0) {
            $paging['pagingItem'] = $this->pagingItem;
        }
        if ($this->pagingPage != 0) {
            $paging['pagingPage'] = $this->pagingPage;
        }
        if ($this->pagingByPage != 0) {
            $paging['pagingByPage'] = $this->pagingByPage;
        }
        if ($this->pagingMarkAs != 0) {
            $paging['pagingMarkAs'] = $this->pagingMarkAs;
        }

        asort($paging);

        return $paging;
    }

    public function setMenuView($menuView)
    {
        $this->menuView = $menuView;
    }

    public function setMenuListFeeds($menuListFeeds)
    {
        $this->menuListFeeds = $menuListFeeds;
    }

    public function setMenuFilter($menuFilter)
    {
        $this->menuFilter = $menuFilter;
    }

    public function setMenuOrder($menuOrder)
    {
        $this->menuOrder = $menuOrder;
    }

    public function setMenuUpdate($menuUpdate)
    {
        $this->menuUpdate = $menuUpdate;
    }

    public function setMenuRead($menuRead)
    {
        $this->menuRead = $menuRead;
    }

    public function setMenuUnread($menuUnread)
    {
        $this->menuUnread = $menuUnread;
    }

    public function setMenuEdit($menuEdit)
    {
        $this->menuEdit = $menuEdit;
    }

    public function setMenuAdd($menuAdd)
    {
        $this->menuAdd = $menuAdd;
    }

    public function setMenuHelp($menuHelp)
    {
        $this->menuHelp = $menuHelp;
    }

    public function setMenuStars($menuStars)
    {
        $this->menuStars = $menuStars;
    }

    public function setPagingItem($pagingItem)
    {
        $this->pagingItem = $pagingItem;
    }

    public function setPagingPage($pagingPage)
    {
        $this->pagingPage = $pagingPage;
    }

    public function setPagingByPage($pagingByPage)
    {
        $this->pagingByPage = $pagingByPage;
    }

    public function setPagingMarkAs($pagingMarkAs)
    {
        $this->pagingMarkAs = $pagingMarkAs;
    }

    public function isLogged()
    {
        global $argv;

        if (Session::isLogged()
            || $this->visibility === 'public'
            || (isset($_GET['cron'])
                && $_GET['cron'] === sha1($this->salt.$this->hash))
            || (isset($argv)
                && count($argv) >= 3
                && $argv[1] == 'update'
                && $argv[2] == sha1($this->salt.$this->hash))) {

            return true;
        }

        return false;
    }

    public function write()
    {
        if ($this->isLogged() || !is_file($this->_file)) {
            $data = array('login', 'hash', 'salt', 'title', 'redirector', 'shaarli',
                          'byPage', 'order', 'visibility', 'filter', 'view','locale',
                          'maxItems',  'autoreadItem', 'autoreadPage', 'maxUpdate',
                          'autohide', 'autofocus', 'listFeeds', 'autoUpdate', 'menuView',
                          'menuListFeeds', 'menuFilter', 'menuOrder', 'menuUpdate',
                          'menuRead', 'menuUnread', 'menuEdit', 'menuAdd', 'menuHelp', 'menuStars',
                          'pagingItem', 'pagingPage', 'pagingByPage', 'addFavicon', 'preload',
                          'pagingMarkAs', 'disableSessionProtection', 'blank', 'lang');
            $out = '<?php';
            $out .= "\n";
            foreach ($data as $key) {
                $out .= '$this->'.$key.' = '.var_export($this->$key, true).";\n";
            }
            $out .= '?>';

            if (!@file_put_contents($this->_file, $out)) {
                die("Can't write to ".CONFIG_FILE." check permissions");
            }
        }
    }
}

class FeedPage
{
    public static $var = array();

    public static function init($var)
    {
        FeedPage::$var = $var;
    }

    public static function includesTpl()
    {
        extract(FeedPage::$var);
?>
    <base href="<?php echo $base;?>">
    <title><?php echo $pagetitle;?></title>
    <meta charset="utf-8">
<?php if (is_file('inc/favicon.ico')) { ?>
    <link href="inc/favicon.ico" rel="icon" type="image/x-icon">
<?php } else { ?>
    <link href="?file=favicon.ico" rel="icon" type="image/x-icon">
<?php } ?>
<?php if (is_file('inc/style.css')) { ?>
    <link type="text/css" rel="stylesheet" href="inc/style.css?version=<?php echo $version;?>" />
<?php } else { ?>
    <link type="text/css" rel="stylesheet" href="?file=style.css&amp;version=<?php echo $version;?>" />
<?php } ?>
<?php if (is_file('inc/user.css')) { ?>
    <link type="text/css" rel="stylesheet" href="inc/user.css?version=<?php echo $version;?>" />
<?php } ?>
    <meta name="viewport" content="width=device-width">
<?php
    }

    public static function messageTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
<?php FeedPage::includesTpl(); ?>
  </head>
  <body onload="document.getElementById('again').focus();">
    <div class="container-fluid full-height">
      <div class="row-fluid full-height">
        <div id="main-container" class="span12 full-height">
          <?php FeedPage::statusTpl(); ?>
          <div class="text-center">
     <?php echo Intl::msg('Click on flag to select your language.').'<br>';
     
 foreach(Intl::$langList as $lang => $info) { ?>
<a href="?lang=<?php echo $lang; ?>" title="<?php echo $info['name']; ?>" class="flag <?php echo $info['class']; ?>"></a>
<?php } ?>
          </div>
          <div class="<?php if (empty($class)) { echo 'text-error'; } else { echo $class; } ?> text-center">
            <?php echo $message; ?><br>
     <a id="again" tabindex="1" class="btn" href="<?php echo $referer; ?>"><?php if (empty($button)) { echo Intl::msg('Try again'); } else { echo $button; } ?></a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>

<?php
    }

    public static function installTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span4 offset4">
          <div id="install">
            <form class="form-horizontal" method="post" action="" name="installform">
              <fieldset>
                <legend><?php echo Intl::msg('KrISS feed installation'); ?></legend>
          <div class="text-center">
     <?php echo Intl::msg('Click on flag to select your language.').'<br>';
     foreach(Intl::$langList as $lang => $info) { ?>
<a href="?lang=<?php echo $lang; ?>" title="<?php echo $info['name']; ?>" class="flag <?php echo $info['class']; ?>"></a>
<?php } ?>
          </div>
                <div class="control-group">
                  <label class="control-label" for="setlogin"><?php echo Intl::msg('Login'); ?></label>
                  <div class="controls">
                    <input type="text" id="setlogin" name="setlogin" placeholder="<?php echo Intl::msg('Login'); ?>">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="setlogin"><?php echo Intl::msg('Password'); ?></label>
                  <div class="controls">
                    <input type="password" id="setpassword" name="setpassword" placeholder="<?php echo Intl::msg('Password'); ?>">
                  </div>
                </div>
                <div class="control-group">
                  <div class="controls">
                    <button type="submit" class="btn"><?php echo Intl::msg('Install KrISS feed'); ?></button>
                  </div>
                </div>
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
              </fieldset>
            </form>
            <?php FeedPage::statusTpl(); ?>
          </div>
        </div>
      </div>
    </div>
    <script>document.installform.setlogin.focus();</script>
  </body>
</html>

<?php
    }

    public static function loginTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body onload="document.loginform.login.focus();">
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span4 offset4">
          <div id="login">
            <form class="form-horizontal" method="post" action="?login" name="loginform">
              <fieldset>
     <legend><?php echo Intl::msg('Welcome to KrISS feed'); ?></legend>
                <div class="control-group">
     <label class="control-label" for="login"><?php echo Intl::msg('Login'); ?></label>
                  <div class="controls">
                    <input type="text" id="login" name="login" placeholder="<?php echo Intl::msg('Login'); ?>" tabindex="1">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="password"><?php echo Intl::msg('Password'); ?></label>
                  <div class="controls">
                    <input type="password" id="password" name="password" placeholder="<?php echo Intl::msg('Password'); ?>" tabindex="2">
                  </div>
                </div>
                <div class="control-group">
                  <div class="controls">
                    <label><input type="checkbox" name="longlastingsession" tabindex="3">&nbsp;<?php echo Intl::msg('Stay signed in (do not check on public computers)'); ?></label>
                  </div>
                </div>
                
                <div class="control-group">
                  <div class="controls">
                    <button type="submit" class="btn" tabindex="4"><?php echo Intl::msg('Sign in'); ?></button>
                  </div>
                </div>
              </fieldset>

              <input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($referer);?>">
              <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            </form>
            <?php FeedPage::statusTpl(); ?>
          </div>
        </div>
      </div>
    </div>                                           
  </body>
</html> 
<?php
    }

    public static function changePasswordTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::statusTpl(); ?>
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend><?php echo Intl::msg('Change your password'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="oldpassword"><?php echo Intl::msg('Old password'); ?></label>
                    <div class="controls">
                      <input type="password" id="oldpassword" name="oldpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="newpassword"><?php echo Intl::msg('New password'); ?></label>
                    <div class="controls">
                      <input type="password" id="newpassword" name="newpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save new password'); ?>" />
                    </div>
                  </div>
                </fieldset>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function navTpl()
    {
        extract(FeedPage::$var);
?>

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
<?php
    }

    public static function statusTpl()
    {
        extract(FeedPage::$var);
?>
<div id="status" class="text-center">
  <a href="http://github.com/tontof/kriss_feed">KrISS feed <?php echo $version; ?></a>
  <span class="hidden-phone"> - <?php echo Intl::msg('A simple and smart (or stupid) feed reader'); ?></span>. <?php /* KrISS: By Tontof */echo Intl::msg('By'); ?> <a href="http://tontof.net">Tontof</a>
<span id="flags-sel">
  <a id="hide-flags" href="#flags" class="flag <?php echo Intl::$langList[Intl::$lang]['class']; ?>" title="<?php echo Intl::$langList[Intl::$lang]['name']; ?>"></a>
  <a id="show-flags" href="#flags-sel" class="flag <?php echo Intl::$langList[Intl::$lang]['class']; ?>" title="<?php echo Intl::$langList[Intl::$lang]['name']; ?>"></a>
</span>
<div id="flags">
<?php foreach(Intl::$langList as $lang => $info) { ?>
<a href="?lang=<?php echo $lang; ?>" title="<?php echo $info['name']; ?>" class="flag <?php echo $info['class']; ?>"></a>
<?php } ?>
</div>
</div>
<?php
    }

    public static function configTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::statusTpl(); ?>
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed main information'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="title"><?php echo Intl::msg('KrISS feed title'); ?></label>
                    <div class="controls">
                      <input type="text" id="title" name="title" value="<?php echo $kfctitle; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('KrISS feed visibility'); ?></label>
                    <div class="controls">
                      <label for="publicReader">
                        <input type="radio" id="publicReader" name="visibility" value="public" <?php echo ($kfcvisibility==='public'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Public KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('No restriction. Anyone can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                      <label for="protectedReader">
                        <input type="radio" id="protectedReader" name="visibility" value="protected" <?php echo ($kfcvisibility==='protected'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Protected KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('Anyone can access feeds and items but only you can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                      <label for="privateReader">
                        <input type="radio" id="privateReader" name="visibility" value="private" <?php echo ($kfcvisibility==='private'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Private KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('Only you can access feeds and items and only you can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="shaarli"><?php echo Intl::msg('Shaarli URL'); ?></label>
                    <div class="controls">
                      <input type="text" id="shaarli" name="shaarli" value="<?php echo $kfcshaarli; ?>">
                      <span class="help-block"><?php echo Intl::msg('Options:'); ?><br>
                        - <?php echo Intl::msg('${url}: item link'); ?><br>
                        - <?php echo Intl::msg('${title}: item title'); ?><br>
                        - <?php echo Intl::msg('${via}: if domain of &lt;link&gt; and &lt;guid&gt; are different ${via} is equals to: <code>via &lt;guid&gt;</code>'); ?><br>
                        - <?php echo Intl::msg('${sel}: <strong>Only available</strong> with javascript: <code>selected text</code>'); ?><br>
                        - <?php echo Intl::msg('example with shaarli:'); ?> <code>http://your-shaarli/?post=${url}&title=${title}&description=${sel}%0A%0A${via}&source=bookmarklet</code>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="redirector"><?php echo Intl::msg('KrISS feed redirector (only for links, media are not considered, <strong>item content is anonymize only with javascript</strong>)'); ?></label>
                    <div class="controls">
                      <input type="text" id="redirector" name="redirector" value="<?php echo $kfcredirector; ?>">
                      <span class="help-block"><?php echo Intl::msg('<strong>http://anonym.to/?</strong> will mask the HTTP_REFERER, you can also use <strong>noreferrer</strong> to use HTML5 property'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="disablesessionprotection">Session protection</label>
                    <div class="controls">
                      <label><input type="checkbox" id="disablesessionprotection" name="disableSessionProtection"<?php echo ($kfcdisablesessionprotection ? ' checked="checked"' : ''); ?>><?php echo Intl::msg('Disable session cookie hijacking protection'); ?></label>
                      <span class="help-block"><?php echo Intl::msg('Check this if you get disconnected often or if your IP address changes often.'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed preferences'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="maxItems"><?php echo Intl::msg('Maximum number of items by feed'); ?></label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxItems" name="maxItems" value="<?php echo $kfcmaxitems; ?>">
                      <span class="help-block"><?php echo Intl::msg('Need update to be taken into consideration'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="maxUpdate"><?php echo Intl::msg('Maximum delay between feed update (in minutes)'); ?></label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxUpdate" name="maxUpdate" value="<?php echo $kfcmaxupdate; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto read next item option'); ?></label>
                    <div class="controls">
                      <label for="donotautoreaditem">
                        <input type="radio" id="donotautoreaditem" name="autoreadItem" value="0" <?php echo (!$kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not mark as read when next item'); ?>
                      </label>
                      <label for="autoread">
                        <input type="radio" id="autoread" name="autoreadItem" value="1" <?php echo ($kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto mark current as read when next item'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto read next page option'); ?></label>
                    <div class="controls">
                      <label for="donotautoreadpage">
                        <input type="radio" id="donotautoreadpage" name="autoreadPage" value="0" <?php echo (!$kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not mark as read when next page'); ?>
                      </label>
                      <label for="autoreadpage">
                        <input type="radio" id="autoreadpage" name="autoreadPage" value="1" <?php echo ($kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto mark current as read when next page'); ?>
                      </label>
                      <span class="help-block"><strong><?php echo Intl::msg('Not implemented yet'); ?></strong></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto hide option'); ?></label>
                    <div class="controls">
                      <label for="donotautohide">
                        <input type="radio" id="donotautohide" name="autohide" value="0" <?php echo (!$kfcautohide ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Always show feed in feeds list'); ?>
                      </label>
                      <label for="autohide">
                        <input type="radio" id="autohide" name="autohide" value="1" <?php echo ($kfcautohide ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically hide feed when 0 unread item'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto focus option'); ?></label>
                    <div class="controls">
                      <label for="donotautofocus">
                        <input type="radio" id="donotautofocus" name="autofocus" value="0" <?php echo (!$kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not automatically jump to current item when it changes'); ?>
                      </label>
                      <label for="autofocus">
                        <input type="radio" id="autofocus" name="autofocus" value="1" <?php echo ($kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically jump to the current item position'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Add favicon option'); ?></label>
                    <div class="controls">
                      <label for="donotaddfavicon">
                        <input type="radio" id="donotaddfavicon" name="addFavicon" value="0" <?php echo (!$kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not add favicon next to feed on list of feeds/items'); ?>
                      </label>
                      <label for="addfavicon">
                        <input type="radio" id="addfavicon" name="addFavicon" value="1" <?php echo ($kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Add favicon next to feed on list of feeds/items'); ?><br><strong><?php echo Intl::msg('Warning: It depends on http://getfavicon.appspot.com/'); ?> <?php if (in_array('curl', get_loaded_extensions())) { echo Intl::msg('but it will cache favicon on your server'); } ?></strong>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Preload option'); ?></label>
                    <div class="controls">
                      <label for="donotpreload">
                        <input type="radio" id="donotpreload" name="preload" value="0" <?php echo (!$kfcpreload ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not preload items.'); ?>
                      </label>
                      <label for="preload">
                        <input type="radio" id="preload" name="preload" value="1" <?php echo ($kfcpreload ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Preload current page items in background. This greatly enhance speed sensation when opening a new item. Note: It uses your bandwith more than needed if you do not read all the page items.'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto target="_blank"</label>
                    <div class="controls">
                      <label for="donotblank">
                        <input type="radio" id="donotblank" name="blank" value="0" <?php echo (!$kfcblank ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not open link in new tab'); ?>
                      </label>
                      <label for="doblank">
                        <input type="radio" id="doblank" name="blank" value="1" <?php echo ($kfcblank ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically open link in new tab'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto update with javascript'); ?></label>
                    <div class="controls">
                      <label for="donotautoupdate">
                        <input type="radio" id="donotautoupdate" name="autoUpdate" value="0" <?php echo (!$kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not auto update with javascript'); ?>
                      </label>
                      <label for="autoupdate">
                        <input type="radio" id="autoupdate" name="autoUpdate" value="1" <?php echo ($kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto update with javascript'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed menu preferences'); ?></legend>
                  <?php echo Intl::msg('You can order or remove elements in the menu. Set a position or leave empty if you do not want the element to appear in the menu.'); ?>
                  <div class="control-group">
                    <label class="control-label" for="menuView"><?php echo Intl::msg('View'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuView" name="menuView" value="<?php echo empty($kfcmenu['menuView'])?'0':$kfcmenu['menuView']; ?>">
                      <span class="help-block"><?php echo Intl::msg('View as list'); ?>/<?php echo Intl::msg('View as expanded'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuListFeeds"><?php echo Intl::msg('Feeds'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuListFeeds" name="menuListFeeds" value="<?php echo empty($kfcmenu['menuListFeeds'])?'0':$kfcmenu['menuListFeeds']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Hide feeds list'); ?>/<?php echo Intl::msg('Show feeds list'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuFilter"><?php echo Intl::msg('Filter'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuFilter" name="menuFilter" value="<?php echo empty($kfcmenu['menuFilter'])?'0':$kfcmenu['menuFilter']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Show all items'); ?>/<?php echo Intl::msg('Show unread items'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuOrder"><?php echo Intl::msg('Order'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuOrder" name="menuOrder" value="<?php echo empty($kfcmenu['menuOrder'])?'0':$kfcmenu['menuOrder']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Show older first'); ?>/<?php echo Intl::msg('Show newer first'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUpdate"><?php echo Intl::msg('Update'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuUpdate" name="menuUpdate" value="<?php echo empty($kfcmenu['menuUpdate'])?'0':$kfcmenu['menuUpdate']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Update all'); ?>/<?php echo Intl::msg('Update folder'); ?>/<?php echo Intl::msg('Update feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuRead"><?php echo Intl::msg('Mark as read'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuRead" name="menuRead" value="<?php echo empty($kfcmenu['menuRead'])?'0':$kfcmenu['menuRead']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Mark all as read'); ?>/<?php echo Intl::msg('Mark folder as read'); ?>/<?php echo Intl::msg('Mark feed as read'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUnread"><?php echo Intl::msg('Mark as unread'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuUnread" name="menuUnread" value="<?php echo empty($kfcmenu['menuUnread'])?'0':$kfcmenu['menuUnread']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Mark all as unread'); ?>/<?php echo Intl::msg('Mark folder as unread'); ?>/<?php echo Intl::msg('Mark feed as unread'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuEdit"><?php echo Intl::msg('Edit'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuEdit" name="menuEdit" value="<?php echo empty($kfcmenu['menuEdit'])?'0':$kfcmenu['menuEdit']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Edit all'); ?>/<?php echo Intl::msg('Edit folder'); ?>/<?php echo Intl::msg('Edit feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuAdd"><?php echo Intl::msg('Add a new feed'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuAdd" name="menuAdd" value="<?php echo empty($kfcmenu['menuAdd'])?'0':$kfcmenu['menuAdd']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Add a new feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuHelp"><?php echo Intl::msg('Help'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuHelp" name="menuHelp" value="<?php echo empty($kfcmenu['menuHelp'])?'0':$kfcmenu['menuHelp']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Help'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuStars"><?php echo Intl::msg('Starred items'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuStars" name="menuStars" value="<?php echo empty($kfcmenu['menuStars'])?'0':$kfcmenu['menuStars']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Starred items'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed paging menu preferences'); ?></legend>
                  <div class="control-group">
                    <label class="control-label" for="pagingItem"><?php echo Intl::msg('Item'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingItem" name="pagingItem" value="<?php echo empty($kfcpaging['pagingItem'])?'0':$kfcpaging['pagingItem']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to go previous and next item'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingPage"><?php echo Intl::msg('Page'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingPage" name="pagingPage" value="<?php echo empty($kfcpaging['pagingPage'])?'0':$kfcpaging['pagingPage']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to go previous and next page'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingByPage"><?php echo Intl::msg('Items by page'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingByPage" name="pagingByPage" value="<?php echo empty($kfcpaging['pagingByPage'])?'0':$kfcpaging['pagingByPage']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to modify number of items by page'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingMarkAs"><?php echo Intl::msg('Mark as read'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingMarkAs" name="pagingMarkAs" value="<?php echo empty($kfcpaging['pagingMarkAs'])?'0':$kfcpaging['pagingMarkAs']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to add a mark as read button into paging'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('Cron configuration'); ?></legend>
                  <code><?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?></code>
                  <?php echo Intl::msg('You can use <code>&force</code> to force update.'); ?><br>
                  <?php echo Intl::msg('To update every hour:'); ?><br>
                  <code>0 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('If you can not use wget, you may try php command line:'); ?><br>
                  <code>0 * * * * php -f <?php echo $_SERVER["SCRIPT_FILENAME"].' update '.$kfccron; ?> > /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('If previous solutions do not work, try to create an update.php file into data directory containing:'); ?><br>
                  <code>
                  &lt;?php<br>
                  $url = "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>";<br>
                  $options = array('http'=>array('method'=>'GET'));<br>
                  $context = stream_context_create($options);<br>
                  $data=file_get_contents($url,false,$context);<br>
                  print($data);
                  </code><br>
                  <?php echo Intl::msg('Then set up your cron with:'); ?><br>
                  <code>0 * * * * php -f <?php echo dirname($_SERVER["SCRIPT_FILENAME"]).'/data/update.php'; ?> > /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('Do not forget to check permissions'); ?><br>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
              </form><br>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function pluginsTpl()
    {
        extract(FeedPage::$var);
?>
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
<?php
    }

    public static function helpTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::statusTpl(); ?>
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <h2><?php echo Intl::msg('Keyboard shortcuts'); ?></h2>
              <fieldset>
                <legend><?php echo Intl::msg('Key legend'); ?></legend>
                <dl class="dl-horizontal">
                  <dt>&#x23b5;</dt>
                  <dd><?php echo Intl::msg('Space key'); ?></dd>
                  <dt>&#x21e7;</dt>
                  <dd><?php echo Intl::msg('Shift key'); ?></dd>
                  <dt>&#x2192;</dt>
                  <dd><?php echo Intl::msg('Right arrow key'); ?></dd>
                  <dt>&#x2190;</dt>
                  <dd><?php echo Intl::msg('Left arrow key'); ?></dd>
                </dl>
              </fieldset>
              <fieldset>
                <legend><?php echo Intl::msg('Items navigation'); ?></legend>
                <dl class="dl-horizontal">
                  <dt>&#x23b5;, t</dt>
                  <dd><?php echo Intl::msg('Toggle current item (open, close item in list view)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>m</dt>
                  <dd><?php echo Intl::msg('Mark current item as read if unread or unread if read'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x21e7; + m</dt>
                  <dd><?php echo Intl::msg('Mark current item as read if unread or unread if read and open current (useful in list view and unread filter)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x2192;, n</dt>
                  <dd><?php echo Intl::msg('Go to next item'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x2190;, p</dt>
                  <dd><?php echo Intl::msg('Go to previous item'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x21e7; + n</dt>
                  <dd><?php echo Intl::msg('Go to next page'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x21e7; + p</dt>
                  <dd><?php echo Intl::msg('Go to previous page'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>j</dt>
                  <dd><?php echo Intl::msg('Go to next item and open it (in list view)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>k</dt>
                  <dd><?php echo Intl::msg('Go to previous item and open it (in list view)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>o</dt>
                  <dd><?php echo Intl::msg('Open current item in new tab'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>&#x21e7; + o</dt>
                  <dd><?php echo Intl::msg('Open current item in current window'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>s</dt>
                  <dd><?php echo Intl::msg('Share current item'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>a</dt>
                  <dd><?php echo Intl::msg('Mark all items, all items from current feed or all items from current folder as read'); ?></dd>
                </dl>
              </fieldset>
              <fieldset>
                <legend><?php echo Intl::msg('Menu navigation'); ?></legend>
                <dl class="dl-horizontal">
                  <dt>h</dt>
                  <dd><?php echo Intl::msg('Go to Home page'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>v</dt>
                  <dd><?php echo Intl::msg('Change view as list or expanded'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>f</dt>
                  <dd><?php echo Intl::msg('Show or hide list of feeds/folders'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>e</dt>
                  <dd><?php echo Intl::msg('Edit current selection (all, folder or feed)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>u</dt>
                  <dd><?php echo Intl::msg('Update current selection (all, folder or feed)'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>r</dt>
                  <dd><?php echo Intl::msg('Reload the page as the F5 key in most of browsers'); ?></dd>
                </dl>
                <dl class="dl-horizontal">
                  <dt>?, F1</dt>
                  <dd><?php echo Intl::msg('Go to Help page (this page)'); ?></dd>
                </dl>
              </fieldset>
              <h2><?php echo Intl::msg('Configuration check'); ?></h2>
              <fieldset>
                <legend><?php echo Intl::msg('PHP configuration'); ?></legend>
                <dl class="dl-horizontal">
                  <dt>open_ssl</dt>
                  <dd>
                    <?php if (extension_loaded('openssl')) { ?>
                    <span class="text-success"><?php echo Intl::msg('You should be able to load https:// rss links.'); ?></span>
                    <?php } else { ?>
                    <span class="text-error"><?php echo Intl::msg('You may have problems using https:// rss links.'); ?></span>
                    <?php } ?>
                  </dd>
                </dl>
              </fieldset>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function addFeedTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-all" class="span6 offset3">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" action="?add" method="POST">
            <fieldset>
              <legend><?php echo Intl::msg('Add a new feed'); ?></legend>
              <div class="control-group">
                <label class="control-label" ><?php echo Intl::msg('Feed URL'); ?></label>
                <div class="controls">
                  <input type="text" id="newfeed" name="newfeed" value="<?php echo $newfeed; ?>">                  
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Add selected folders to feed'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="folders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit feed'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Add a new folder'); ?></label>
                <div class="controls">
                  <input type="text" name="newfolder" value="">
                </div>
              </div>
              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="add" value="<?php echo Intl::msg('Add new feed'); ?>"/>
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Use bookmarklet to add a new feed'); ?></legend>
              <div id="add-feed-bookmarklet" class="text-center">
                <a onclick="alert('<?php echo Intl::msg('Drag this link to your bookmarks toolbar, or right-click it and choose Bookmark This Link...'); ?>');return false;" href="javascript:(function(){var%20url%20=%20location.href;window.open('<?php echo $base; ?>?add&amp;newfeed='+encodeURIComponent(url),'_blank','menubar=no,height=390,width=600,toolbar=no,scrollbars=yes,status=no,dialog=1');})();"><b>KF+</b></a>
              </div>
            </fieldset>
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function editAllTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-all" class="span6 offset3">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="">
            <fieldset>
              <legend><?php echo Intl::msg('Reorder folders'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php $i=0; foreach ($folders as $hash => $folder) { ?>
                  <label for="order-folder-<?php echo $hash; ?>">
                    <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>) <br>
                    <input type="text" id="order-folder-<?php echo $hash; ?>" name="order-folder-<?php echo $hash; ?>" value="<?php echo $i; $i++; ?>">
                  </label>
                  <?php } ?>
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('Add selected folders to selected feeds'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="addfolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
                <div class="controls">
                  <input type="text" name="addnewfolder" value="" placeholder="New folder">
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('Remove selected folders to selected feeds'); ?></legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="remove-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="remove-folder-<?php echo $hash; ?>" name="removefolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>"><?php echo Intl::msg('Edit folder'); ?></a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />

            <fieldset>
              <legend><?php echo Intl::msg('List of feeds'); ?></legend>

              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = true; }" value="<?php echo Intl::msg('Select all'); ?>">
              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = false; }" value="<?php echo Intl::msg('Unselect all'); ?>">

              <ul class="unstyled">
                <?php foreach ($listFeeds as $feedHash => $feed) { ?>
                <li>
                  <label for="feed-<?php echo $feedHash; ?>">
                    <input type="checkbox" id="feed-<?php echo $feedHash; ?>" name="feeds[]" value="<?php echo $feedHash; ?>">
                    <?php echo htmlspecialchars($feed['title']); ?> (<a href="?edit=<?php echo $feedHash; ?>"><?php echo Intl::msg('Edit feed'); ?></a>)
                  </label>
                </li>
                <?php } ?>
              </ul>
            </fieldset>

            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
            <input class="btn" type="submit" name="delete" value="<?php echo Intl::msg('Delete selected feeds'); ?>" onclick="return confirm('<?php echo Intl::msg('Do you really want to delete all selected feeds?'); ?>');"/>
            <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
          </form>
        </div>
      </div>
  </body>
</html>
<?php
    }

    public static function editFolderTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-folder" class="span4 offset4">
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="">
            <fieldset>
              <div class="control-group">
                <label class="control-label" for="foldertitle"><?php echo Intl::msg('Folder title'); ?></label>
                <div class="controls">
                  <input type="text" id="foldertitle" name="foldertitle" value="<?php echo $foldertitle; ?>">
                  <span class="help-block"><?php echo Intl::msg('Leave empty to delete'); ?></span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                  <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                </div>
              </div>
            </fieldset>

            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
          </form>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function editFeedTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div id="edit-feed" class="span6 offset3">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="">
            <fieldset>
              <legend><?php echo Intl::msg('Feed main information'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="title"><?php echo Intl::msg('Feed title'); ?></label>
                <div class="controls">
                  <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($feed['title']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Feed XML URL'); ?></label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="xmlUrl" value="<?php echo htmlspecialchars($feed['xmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Feed main URL'); ?></label>
                <div class="controls">
                  <input type="text" name="htmlUrl" value="<?php echo htmlspecialchars($feed['htmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label" for="description"><?php echo Intl::msg('Feed description'); ?></label>
                <div class="controls">
                  <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($feed['description']); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Feed folders'); ?></legend>
              <?php
                 foreach ($folders as $hash => $folder) {
              $checked = '';
              if (in_array($hash, $feed['foldersHash'])) {
              $checked = ' checked="checked"';
              }
              ?>
              <div class="control-group">
                <div class="controls">
                  <label for="folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="folder-<?php echo $hash; ?>" name="folders[]" <?php echo $checked; ?> value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?>
                  </label>
                </div>
              </div>
              <?php } ?>
              <div class="control-group">
                <label class="control-label" for="newfolder"><?php echo Intl::msg('New folder'); ?></label>
                <div class="controls">
                  <input type="text" name="newfolder" value="" placeholder="<?php echo Intl::msg('New folder'); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend><?php echo Intl::msg('Feed preferences'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="timeUpdate"><?php echo Intl::msg('Time update'); ?></label>
                <div class="controls">
                  <input type="text" id="timeUpdate" name="timeUpdate" value="<?php echo $feed['timeUpdate']; ?>">
                  <span class="help-block"><?php echo Intl::msg('"auto", "max" or a number of minutes less than "max" define in <a href="?config">configuration</a>'); ?></span>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label"><?php echo Intl::msg('Last update'); ?></label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="lastUpdate" value="<?php echo $lastUpdate; ?>">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                  <input class="btn" type="submit" name="delete" value="<?php echo Intl::msg('Delete feed'); ?>"/>
                  <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                </div>
              </div>
            </fieldset>
            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
          </form><br>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
    }

    public static function updateTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
    <?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid full-height">
      <div class="row-fluid full-height">
        <div class="span12 full-height">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <div class="container-fluid">
            <div class="row-fluid">
              <div class="span6 offset3">
                <ul class="unstyled">
                  <?php $kf->updateFeedsHash($feedsHash, $forceUpdate, 'html')?>
                </ul>
                <a class="btn ico-home" href="<?php echo MyTool::getUrl(); ?>" title="<?php echo Intl::msg('Home'); ?>"></a>
                <?php if (!empty($referer)) { ?>
                <a class="btn" href="<?php echo $referer; ?>"><?php echo Intl::msg('Go back'); ?></a>
                <?php } ?>
                <a class="btn" href="<?php echo $query."update=".$currentHash."&force"; ?>"><?php echo Intl::msg('Force update'); ?></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript">
      <?php /* include("inc/script.js"); */ ?>
    </script>
  </body>
</html>
<?php
    }

    public static function importTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
<?php FeedPage::includesTpl(); ?>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span4 offset4">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <form class="form-horizontal" method="post" action="?import" enctype="multipart/form-data" name="importform">
            <fieldset>
              <legend><?php echo Intl::msg('Import opml file'); ?></legend>
              <div class="control-group">
                <label class="control-label" for="filetoupload"><?php echo Intl::msg('Opml file:'); ?></label>
                <div class="controls">
                  <input tabindex="1" class="btn" type="file" id="filetoupload" name="filetoupload">
                  <span class="help-block"><?php echo Intl::msg('Size max:'); ?> <?php echo MyTool::humanBytes(MyTool::getMaxFileSize()); ?>
                    </span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <label for="overwrite">
                    <input type="checkbox" name="overwrite" id="overwrite">
                    <?php echo Intl::msg('Overwrite existing feeds'); ?>
                  </label>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="import" value="<?php echo Intl::msg('Import opml file'); ?>">
                  <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>">
                </div>
              </div>

              <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo MyTool::getMaxFileSize(); ?>">
              <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
              <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            </fieldset>
          </form>
        </div>
      </div>
    </div>
  </body>
</html> 
<?php
    }

    public static function listFeedsTpl()
    {
        extract(FeedPage::$var);
?>
<div id="list-feeds">
  <?php
     if ($listFeeds == 'show') {
     ?>
	 <input type="text" id="Nbunread" style="display:none;" value='<?php echo $feedsView['all']['nbUnread']; ?>'>
  <ul class="unstyled">
    <li id="all-subscriptions" class="folder<?php if ($currentHash == 'all') echo ' current-folder'; ?>">
      <?php if (isset($_GET['stars'])) { ?>
      <h4><a class="mark-as" href="?stars&currentHash=all"><span class="label"><?php echo $feedsView['all']['nbAll']; ?></span></a><a href="<?php echo '?stars&currentHash=all'; ?>"><?php echo $feedsView['all']['title']; ?></a></h4>
      <?php } else { ?>
      <h4><a class="mark-as" href="<?php echo ($feedsView['all']['nbUnread']==0?'?currentHash=all&unread':$query.'read').'=all'; ?>" title="Mark all as <?php echo ($feedsView['all']['nbUnread']==0?'unread':'read');?>"><span class="label"><?php echo $feedsView['all']['nbUnread']; ?></span></a><a href="<?php echo '?currentHash=all'; ?>"><?php echo $feedsView['all']['title']; ?></a></h4>
      <?php } ?>

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
        
        <li id="<?php echo 'feed-'.$feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'; ?><?php if ($currentHash == $feedHash) echo ' current-feed'; ?><?php if ($autohide and $feed['nbUnread']== 0) echo ' autohide-feed'; ?>">
          <?php if ($addFavicon) { ?>
          <span class="feed-favicon">
            <img src="<?php echo $kf->getFaviconFeed($feedHash); ?>" height="16" width="16" title="favicon" alt="favicon"/>
          </span>
          <?php } ?>
          <?php if (isset($_GET['stars'])) { ?>
          <a class="mark-as" href="<?php echo $query.'currentHash='.$feedHash; ?>"><span class="label"><?php echo $feed['nbAll']; ?></span></a><a class="feed" href="<?php echo '?stars&currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
          <?php } else { ?>
          <a class="mark-as" href="<?php echo $query.'read='.$feedHash; ?>"><span class="label"><?php echo $feed['nbUnread']; ?></span></a><a class="feed<?php echo (isset($feed['error'])?' text-error':''); ?>" href="<?php echo '?currentHash='.$feedHash.'#feed-'.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
          <?php } ?>
          
        </li>

        <?php
           }
           foreach ($feedsView['folders'] as $hashFolder => $folder) {
        $isOpen = $folder['isOpen'];
        ?>
        
        <li id="folder-<?php echo $hashFolder; ?>" class="folder<?php if ($currentHash == $hashFolder) echo ' current-folder'; ?><?php if ($autohide and $folder['nbUnread']== 0) { echo ' autohide-folder';} ?>">
          <h5>
            <a class="mark-as" href="<?php echo $query.'read='.$hashFolder; ?>"><span class="label"><?php echo $folder['nbUnread']; ?></span></a>
            <a class="folder-toggle" href="<?php echo $query.'toggleFolder='.$hashFolder; ?>" data-toggle="collapse" data-target="#folder-ul-<?php echo $hashFolder; ?>">
              <span class="ico">
                <span class="ico-b-disc"></span>
                <span class="ico-w-line-h"></span>
                <span class="ico-w-line-v<?php echo ($isOpen?' folder-toggle-open':' folder-toggle-close'); ?>"></span>
              </span>
            </a>
            <a href="<?php echo '?currentHash='.$hashFolder.'#folder-'.$hashFolder; ?>"><?php echo htmlspecialchars($folder['title']); ?></a>
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

            <li id="folder-<?php echo $hashFolder; ?>-feed-<?php echo $feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'; ?><?php if ($currentHash == $feedHash) echo ' current-feed'; ?><?php if ($autohide and $feed['nbUnread']== 0) { echo ' autohide-feed';} ?>">
              
              <?php if ($addFavicon) { ?>
              <span class="feed-favicon">
                <img src="<?php echo $kf->getFaviconFeed($feedHash); ?>" height="16" width="16" title="favicon" alt="favicon"/>
              </span>
              <?php } ?>
              <a class="mark-as" href="<?php echo $query.'read='.$feedHash; ?>"><span class="label"><?php echo $feed['nbUnread']; ?></span></a><a class="feed<?php echo (isset($feed['error'])?' text-error':''); ?>" href="<?php echo '?currentHash='.$feedHash.'#folder-'.$hashFolder.'-feed-'.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
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
<?php
    }

    public static function listItemsTpl()
    {
        extract(FeedPage::$var);
?>
<?php
 $unread = Intl::msg('unread');
 $read = Intl::msg('read');
 $share = Intl::msg('share');
 $star = Intl::msg('star');
 $unstar = Intl::msg('unstar');
 $top = Intl::msg('top');
 
?>
<ul id="list-items" class="unstyled">
  <?php
     foreach (array_keys($items) as $itemHash){
     $item = $kf->getItem($itemHash);
  ?>
  <li id="item-<?php echo $itemHash; ?>" class="<?php echo ($view==='expanded'?'item-expanded':'item-list'); ?><?php echo ($item['read']==1?' read':''); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">

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
          <?php if (!isset($_GET['stars'])) { ?>
          <?php if ($item['read'] == 1) { ?>
          <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label"><?php echo $unread; ?></span></a>
          <?php } else { ?>
          <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label"><?php echo $read; ?></span></a>
          <?php } ?>
          <?php } ?>
          <a<?php if ($blank) { echo ' target="_blank"'; } ?><?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> class="item-link" href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['link']; ?>">
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
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label"><?php echo $share; ?></span></a>
        <?php if (!isset($_GET['stars'])) { ?>
        <?php if ($item['read'] == 1) { ?>
        <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label item-label-mark-as"><?php echo $unread; ?></span></a>
        <?php } else { ?>
        <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label item-label-mark-as"><?php echo $read; ?></span></a>
        <?php } ?>
        <?php } ?>
        <?php if (isset($item['starred']) && $item['starred']===1) { ?>
        <a class="item-starred" href="<?php echo $query.'unstar='.$itemHash; ?>"><span class="label"><?php echo $unstar; ?></span></a>
        <?php } else { ?>
        <a class="item-starred" href="<?php echo $query.'star='.$itemHash; ?>"><span class="label"><?php echo $star; ?></span></a>
        <?php }?>
        <a<?php if ($blank) { echo ' target="_blank"'; } ?><?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> class="item-link" href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['link']; ?>"><?php echo $item['title']; ?></a>
      </div>
      <div class="clear"></div>
      <div class="item-info-end item-info-time">
        <?php echo $item['time']['expanded']; ?>
      </div>
      <div class="item-info-end item-info-author">
        <a class="item-via"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['via']; ?>"><?php echo $item['author']; ?></a>
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
        <a class="item-top" href="#status"><span class="label label-expanded"><?php echo $top; ?></span></a> 
        <a class="item-shaarli" href="<?php echo $query.'shaarli='.$itemHash; ?>"><span class="label label-expanded"><?php echo $share; ?></span></a>
        <?php if (!isset($_GET['stars'])) { ?>
        <?php if ($item['read'] == 1) { ?>
        <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label label-expanded"><?php echo $unread; ?></span></a>
        <?php } else { ?>
        <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label label-expanded"><?php echo $read; ?></span></a>
        <?php } ?>
        <?php } ?>
        <?php if (isset($item['starred']) && $item['starred']===1) { ?>
        <a class="item-starred" href="<?php echo $query.'unstar='.$itemHash; ?>"><span class="label label-expanded"><?php echo $unstar; ?></span></a>
        <?php } else { ?>
        <a class="item-starred" href="<?php echo $query.'star='.$itemHash; ?>"><span class="label label-expanded"><?php echo $star; ?></span></a>
        <?php }?>
        <?php if ($view==='list') { ?>
        <a id="item-toggle-<?php echo $itemHash; ?>" class="item-toggle item-toggle-plus" href="<?php echo $query.'current='.$itemHash.((!isset($_GET['open']) or $currentItemHash != $itemHash)?'&amp;open':''); ?>" data-toggle="collapse" data-target="#item-div-<?php echo $itemHash; ?>">
          <span class="ico ico-toggle-item">
            <span class="ico-b-disc"></span>
            <span class="ico-w-line-h"></span>
          </span>
        </a>
        <?php } ?>
      </div>
      <div class="clear"></div>
      <?php } ?>
    </div>
  </li>
  <?php } ?>
</ul>
<div class="clear"></div>
<?php
    }

    public static function pagingTpl()
    {
        extract(FeedPage::$var);
?>

<ul class="inline"><?php
foreach(array_keys($paging) as $pagingOpt) {
  switch($pagingOpt) {
    case 'pagingItem': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn2 btn-info previous-item" href="<?php echo $query.'previous='.$currentItemHash; ?>" title="<?php echo Intl::msg("Previous item"); ?>"><?php echo Intl::msg("Previous item"); ?></a>
      <a class="btn btn2 btn-info next-item" href="<?php echo $query.'next='.$currentItemHash; ?>" title="<?php echo Intl::msg("Next item"); ?>"><?php echo Intl::msg("Next item"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingMarkAs': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn-info" href="<?php echo $query.'read='.$currentHash; ?>" title="<?php echo Intl::msg("Mark as read"); ?>"><?php echo Intl::msg("Mark as read"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingPage': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn3 btn-info previous-page<?php echo ($currentPage === 1)?' disabled':''; ?>" href="<?php echo $query.'previousPage='.$currentPage; ?>" title="<?php echo Intl::msg("Previous page"); ?>"><?php echo Intl::msg("Previous page"); ?></a>
      <button class="btn btn3 disabled current-max-page"><?php echo $currentPage.' / '.$maxPage; ?></button>
      <a class="btn btn3 btn-info next-page<?php echo ($currentPage === $maxPage)?' disabled':''; ?>" href="<?php echo $query.'nextPage='.$currentPage; ?>" title="<?php echo Intl::msg("Next page"); ?>"><?php echo Intl::msg("Next page"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingByPage': ?>

  <li>
    <div class="btn-group">
      <form class="form-inline" action="" method="GET">
        <div class="input-prepend input-append paging-by-page">
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=1'; ?>">1</a>
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=10'; ?>">10</a>
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=50'; ?>">50</a>
          <div class="btn-break"></div>
          <input class="btn2 input-by-page input-mini" type="text" name="byPage">
          <input type="hidden" name="currentHash" value="<?php echo $currentHash; ?>">
          <button type="submit" class="btn btn2"><?php echo Intl::msg("Items per page"); ?></button>
        </div>
      </form>
    </div>
  </li><?php
      break;
    default:
      break;
  }
} ?>

</ul>
<div class="clear"></div>
<?php
    }

    public static function indexTpl()
    {
        extract(FeedPage::$var);
?>
<!DOCTYPE html>
<html>
  <head>
<?php FeedPage::includesTpl(); ?>
  </head>
  <body>
<div id="index" class="container-fluid full-height" data-view="<?php echo $view; ?>" data-list-feeds="<?php echo $listFeeds; ?>" data-filter="<?php echo $filter; ?>" data-order="<?php echo $order; ?>" data-by-page="<?php echo $byPage; ?>" data-autoread-item="<?php echo $autoreadItem; ?>" data-autoread-page="<?php echo $autoreadPage; ?>" data-autohide="<?php echo $autohide; ?>" data-current-hash="<?php echo $currentHash; ?>" data-current-page="<?php echo $currentPage; ?>" data-nb-items="<?php echo $nbItems; ?>" data-shaarli="<?php echo $shaarli; ?>" data-redirector="<?php echo $redirector; ?>" data-autoupdate="<?php echo $autoupdate; ?>" data-autofocus="<?php echo $autofocus; ?>" data-add-favicon="<?php echo $addFavicon; ?>" data-preload="<?php echo $preload; ?>" data-is-logged="<?php echo $isLogged; ?>" data-blank="<?php echo $blank; ?>" data-intl-top="<?php echo Intl::msg('top'); ?>" data-intl-share="<?php echo Intl::msg('share'); ?>" data-intl-read="<?php echo Intl::msg('read'); ?>" data-intl-unread="<?php echo Intl::msg('unread'); ?>" data-intl-star="<?php echo Intl::msg('star'); ?>" data-intl-unstar="<?php echo Intl::msg('unstar'); ?>" data-intl-from="<?php echo Intl::msg('from'); ?>" data-intl-unstar="<?php echo Intl::msg('unstar'); ?>"<?php if (isset($_GET['stars'])) { echo ' data-stars="1"'; } ?>>
      <div class="row-fluid full-height">
        <?php if ($listFeeds == 'show') { ?>
        <div id="main-container" class="span9 full-height">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <div id="paging-up">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
          <?php FeedPage::listItemsTpl(); ?>
          <div id="paging-down">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
        </div>
        <div id="minor-container" class="span3 full-height minor-container">
          <?php FeedPage::listFeedsTpl(); ?>
        </div>
        <?php } else { ?>
        <div id="main-container" class="span12 full-height">
          <?php FeedPage::statusTpl(); ?>
          <?php FeedPage::navTpl(); ?>
          <div id="paging-up">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
          <?php FeedPage::listItemsTpl(); ?>
          <div id="paging-down">
            <?php if (!empty($paging)) {FeedPage::pagingTpl();} ?>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>
    <?php if (is_file('inc/script.js')) { ?>
    <script type="text/javascript" src="inc/script.js?version=<?php echo $version;?>"></script>
    <?php } else { ?>
    <script type="text/javascript" src="?file=script.js&amp;version=<?php echo $version;?>"></script>
    <?php } ?>
  </body>
</html>
<?php
    }
}

class Feed
{
    public $dataFile = '';

    public $cacheDir = '';

    public $kfc;

    private $_data = array();

    public function __construct($dataFile, $cacheDir, $kfc)
    {
        $this->kfc = $kfc;
        $this->dataFile = $dataFile;
        $this->cacheDir = $cacheDir;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function setData($data)
    {
        $this->_data = $data;
    }

    public function initData()
    {
        $this->_data['feeds'] = array(
            MyTool::smallHash('http://tontof.net/?rss') => array(
                'title' => 'Tontof',
                'foldersHash' => array(),
                'timeUpdate' => 'auto',
                'lastUpdate' => 0,
                'nbUnread' => 0,
                'nbAll' => 0,
                'htmlUrl' => 'http://tontof.net',
                'xmlUrl' => 'http://tontof.net/?rss',
                'description' => 'A simple and smart (or stupid) blog'));
        $this->_data['folders'] = array();
        $this->_data['items'] = array();
        $this->_data['newItems'] = array();
    }

    public function loadData()
    {
        if (empty($this->_data)) {
            if (file_exists($this->dataFile)) {
                $this->_data = unserialize(
                    gzinflate(
                        base64_decode(
                            substr(
                                file_get_contents($this->dataFile),
                                strlen(PHPPREFIX),
                                -strlen(PHPSUFFIX)
                                )
                            )
                        )
                    );

                return true;
            } else {
                $this->initData();
                $this->writeData();

                return false;
            }
        }

        // data already loaded
        return true;
    }

    public function writeData()
    {
        if ($this->kfc->isLogged()) {
            $write = @file_put_contents(
                $this->dataFile,
                PHPPREFIX
                . base64_encode(gzdeflate(serialize($this->_data)))
                . PHPSUFFIX
                );
            if (!$write) {
                die("Can't write to " . $this->dataFile);
            }
        }
    }

    public function setFeeds($feeds) {
        $this->_data['feeds'] = $feeds;
    }

    public function getFeeds()
    {
        return $this->_data['feeds'];
    }

    public function sortFeeds()
    {
        uasort(
            $this->_data['feeds'],
            'Feed::sortByTitle'
            );
    }

    public function sortFolders()
    {
        uasort(
            $this->_data['folders'],
            'Feed::sortByOrder'
            );
    }

    public function getFeedsView()
    {
        $feedsView = array('all' => array('title' => Intl::msg('All feeds'), 'nbUnread' => 0, 'nbAll' => 0, 'feeds' => array()), 'folders' => array());
        
        foreach ($this->_data['feeds'] as $feedHash => $feed) {
            if (isset($feed['error'])) {
                $feed['error'] = $feed['error'];
            }
            if (isset($feed['nbUnread'])) {
                $feedsView['all']['nbUnread'] += $feed['nbUnread'];
            } else {
                $feedsView['all']['nbUnread'] += $feed['nbAll'];
            }
            $feedsView['all']['nbAll'] += $feed['nbAll'];
            if (empty($feed['foldersHash'])) {
                $feedsView['all']['feeds'][$feedHash] = $feed;
                if (!isset($feed['nbUnread'])) {
                    $feedsView['all']['feeds'][$feedHash]['nbUnread'] = $feed['nbAll'];
                }
            } else {
                foreach ($feed['foldersHash'] as $folderHash) {
                    $folder = $this->getFolder($folderHash);
                    if ($folder !== false) {
                        if (!isset($feedsView['folders'][$folderHash]['title'])) {
                            $feedsView['folders'][$folderHash]['title'] = $folder['title'];
                            $feedsView['folders'][$folderHash]['isOpen'] = $folder['isOpen'];
                            $feedsView['folders'][$folderHash]['nbUnread'] = 0;
                            $feedsView['folders'][$folderHash]['nbAll'] = 0;
                            if (isset($folder['order'])) {
                                $feedsView['folders'][$folderHash]['order'] = $folder['order'];
                            } else {
                                $feedsView['folders'][$folderHash]['order'] = 0;
                            }
                        }
                        $feedsView['folders'][$folderHash]['feeds'][$feedHash] = $feed;
                        $feedsView['folders'][$folderHash]['nbUnread'] += $feed['nbUnread'];
                        $feedsView['folders'][$folderHash]['nbAll'] += $feed['nbAll'];
                    }
                }
            }
        }

        uasort($feedsView['folders'], 'Feed::sortByOrder');

        return $feedsView;
    }

    public function getFeed($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash])) {
            // FIX: problem of version 6 &amp;amp;
            $this->_data['feeds'][$feedHash]['xmlUrl'] = preg_replace('/&(amp;)*/', '&', $this->_data['feeds'][$feedHash]['xmlUrl']);
            $this->_data['feeds'][$feedHash]['htmlUrl'] = preg_replace('/&(amp;)*/', '&', $this->_data['feeds'][$feedHash]['htmlUrl']);

            return $this->_data['feeds'][$feedHash];
        }

        return false;
    }

    public function getFaviconFeed($feedHash)
    {
        $htmlUrl = $this->_data['feeds'][$feedHash]['htmlUrl'];
        $url = 'http://getfavicon.appspot.com/'.$htmlUrl.'?defaulticon=bluepng';
        $file = FAVICON_DIR.'/favicon.'.$feedHash.'.ico';

        if ($this->kfc->isLogged() && $this->kfc->addFavicon) {
            MyTool::grabToLocal($url, $file);
        }

        if (file_exists($file)) {
            return $file;
        } else {
            return $url;
        }
    }

    public function getFeedHtmlUrl($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash]['htmlUrl'])) {
            return $this->_data['feeds'][$feedHash]['htmlUrl'];
        }

        return false;
    }

    public function getFeedTitle($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash]['title'])) {
            return $this->_data['feeds'][$feedHash]['title'];
        }

        return false;
    }

    public function loadFeed($feedHash)
    {
        if (!isset($this->_data['feeds'][$feedHash]['items'])) {
            $this->_data['feeds'][$feedHash]['items'] = array();

            if (file_exists($this->cacheDir.'/'.$feedHash.'.php')) {
                $items = unserialize(
                    gzinflate(
                        base64_decode(
                            substr(
                                file_get_contents($this->cacheDir.'/'.$feedHash.'.php'),
                                strlen(PHPPREFIX),
                                -strlen(PHPSUFFIX)
                                )
                            )
                        )
                    );

                $this->_data['feeds'][$feedHash]['items'] = $items;
            }
        }
    }

    public function editFeed(
        $feedHash,
        $title,
        $description,
        $foldersHash,
        $timeUpdate,
        $htmlUrl)
    {
        if (isset($this->_data['feeds'][$feedHash])) {
            if (!empty($title)) {
                $this->_data['feeds'][$feedHash]['title'] = $title;
            }
            if (!empty($description)) {
                $this->_data['feeds'][$feedHash]['description'] = $description;
            }
            if (!empty($htmlUrl)) {
                $this->_data['feeds'][$feedHash]['htmlUrl'] = $htmlUrl;
            }
            
            $this->_data['feeds'][$feedHash]['foldersHash'] = $foldersHash;
            $this->_data['feeds'][$feedHash]['timeUpdate'] = 'auto';
            if (!empty($timeUpdate)) {
                if ($timeUpdate == 'max') {
                    $this->_data['feeds'][$feedHash]['timeUpdate'] = $timeUpdate;
                } else {
                    $this->_data['feeds'][$feedHash]['timeUpdate'] = (int) $timeUpdate;
                    $maxUpdate = $this->kfc->maxUpdate;
                    if ($this->_data['feeds'][$feedHash]['timeUpdate'] < MIN_TIME_UPDATE
                        || $this->_data['feeds'][$feedHash]['timeUpdate'] > $maxUpdate
                    ) {
                        $this->_data['feeds'][$feedHash]['timeUpdate'] = 'auto';
                    }
                }
            }
        }
    }

    public function removeFeed($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash])) {
            unset($this->_data['feeds'][$feedHash]);
            unlink($this->cacheDir. '/' .$feedHash.'.php' );
            foreach (array_keys($this->_data['items']) as $itemHash) {
                if (substr($itemHash, 0, 6) === $feedHash) {
                    unset($this->_data['items'][$itemHash]);
                }
            }
            foreach (array_keys($this->_data['newItems']) as $itemHash) {
                if (substr($itemHash, 0, 6) === $feedHash) {
                    unset($this->_data['newItems'][$itemHash]);
                }
            }
        }
    }

    public function writeFeed($feedHash, $feed)
    {
        if ($this->kfc->isLogged() || (isset($_GET['cron']) && $_GET['cron'] === sha1($this->kfc->salt.$this->kfc->hash))) {
            if (!is_dir($this->cacheDir)) {
                if (!@mkdir($this->cacheDir, 0755)) {
                    die("Can not create cache dir: ".$this->cacheDir);
                }
                @chmod($this->cacheDir, 0755);
                if (!is_file($this->cacheDir.'/.htaccess')) {
                    if (!@file_put_contents(
                            $this->cacheDir.'/.htaccess',
                            "Allow from none\nDeny from all\n"
                            )) {
                        die("Can not protect cache dir: ".$this->cacheDir);
                    }
                }
            }

            $write = @file_put_contents(
                $this->cacheDir.'/'.$feedHash.'.php',
                PHPPREFIX
                . base64_encode(gzdeflate(serialize($feed)))
                . PHPSUFFIX
                );

            if (!$write) {
                die("Can't write to " . $this->cacheDir.'/'.$feedHash.'.php');
            }
        }
    }

    public function orderFeedsForUpdate($feedsHash)
    {
        $newFeedsHash = array();
        foreach(array_keys($this->_data['items']) as $itemHash) {
            $feedHash = substr($itemHash, 0, 6);
            if (in_array($feedHash, $feedsHash) and !in_array($feedHash, $newFeedsHash)) {
                $newFeedsHash[] = $feedHash;
            }
        }

        if ($this->kfc->order !== 'newerFirst') {
            $newFeedsHash = array_reverse($newFeedsHash);
        }

        foreach($feedsHash as $feedHash) {
            if (!in_array($feedHash, $newFeedsHash)) {
                $newFeedsHash[] = $feedHash;
            }
        }

        return $newFeedsHash;
    }

    public function getFeedsHashFromFolderHash($folderHash)
    {
        $list = array();
        $folders = $this->getFolders();

        if (isset($folders[$folderHash])) {
            foreach ($this->_data['feeds'] as $feedHash => $feed) {
                if (in_array($folderHash, $feed['foldersHash'])) {
                    $list[] = $feedHash;
                }
            }
        }

        return $list;
    }

    public function getFolders()
    {
        return $this->_data['folders'];
    }

    public function getFolder($folderHash)
    {
        if (isset($this->_data['folders'][$folderHash])) {
            return $this->_data['folders'][$folderHash];
        }

        return false;
    }

    public function addFolder($folderTitle, $newFolderHash = '')
    {
        if (empty($newFolderHash)) {
            $newFolderHash = MyTool::smallHash($newFolderTitle);
        }
        $this->_data['folders'][$newFolderHash] = array(
            'title' => $folderTitle,
            'isOpen' => 1
        );
    }

    public function renameFolder($oldFolderHash, $newFolderTitle)
    {
        $newFolderHash = '';
        if (!empty($newFolderTitle)) {
            $newFolderHash = MyTool::smallHash($newFolderTitle);
            $this->addFolder($newFolderTitle, $newFolderHash);
            $this->_data['folders'][$newFolderHash]['isOpen'] = $this->_data['folders'][$oldFolderHash]['isOpen'];
        }
        unset($this->_data['folders'][$oldFolderHash]);

        foreach ($this->_data['feeds'] as $feedHash => $feed) {
            $i = array_search($oldFolderHash, $feed['foldersHash']);
            if ($i !== false) {
                unset($this->_data['feeds'][$feedHash]['foldersHash'][$i]);
                if (!empty($newFolderTitle)) {
                    $this->_data['feeds'][$feedHash]['foldersHash'][] = $newFolderHash;
                }
            }
        }
    }

    public function orderFolder(
        $folderHash,
        $order)
    {
        if (isset($this->_data['folders'][$folderHash])) {
            $this->_data['folders'][$folderHash]['order'] = $order;
        }
    }

    public function toggleFolder($hash)
    {
        if ($this->_data['folders'][$hash]) {
            $isOpen = $this->_data['folders'][$hash]['isOpen'];
            if ($isOpen) {
                $this->_data['folders'][$hash]['isOpen'] = 0;
            } else {
                $this->_data['folders'][$hash]['isOpen'] = 1;
            }
        }

        return true;
    }

    public function getFolderTitle($folderHash)
    {
        if (isset($this->_data['folders'][$folderHash])) {
            return $this->_data['folders'][$folderHash]['title'];
        }

        return false;
    }

    public function getItems($hash = 'all', $filter = 'all')
    {
        if (empty($hash) or $hash == 'all' and $filter == 'all') {
            if (isset($this->_data['newItems'])) {
                return $this->_data['items']+$this->_data['newItems'];
            } else {
                return $this->_data['items'];
            }
        }

        if (empty($hash) or $hash == 'all' and $filter == 'old') {
            return $this->_data['items'];
        }

        if (empty($hash) or $hash == 'all' and $filter == 'new') {
            if (isset($this->_data['newItems'])) {
                return $this->_data['newItems'];
            } else {
                return array();
            }
        }
        
        $list = array();
        $isRead = 1;
        if ($filter === 'unread') {
            $isRead = 0;
        }

        if (empty($hash) || $hash == 'all') {
            // all items
            foreach ($this->_data['items'] as $itemHash => $item) {
                if ($item[1] === $isRead) {
                    $list[$itemHash] = $item;
                }
            }
            if (isset($this->_data['newItems'])) {
                foreach ($this->_data['newItems'] as $itemHash => $item) {
                    if ($item[1] === $isRead) {
                        $list[$itemHash] = $item;
                    }
                }
            }
        } else {
            if (strlen($hash) === 12) {
                // an item
                if (isset($this->_data['items'][$hash])) {
                    $list[$hash] = $this->_data['items'][$hash];
                } else if (isset($this->_data['newItems']) && isset($this->_data['newItems'][$hash])) {
                    $list[$hash] = $this->_data['newItems'][$hash];
                }
            } else {
                $feedsHash = array();
                if (isset($this->_data['feeds'][$hash])) {
                    // a feed
                    $feedsHash[] = $hash;
                } else if (isset($this->_data['folders'][$hash])) {
                    // a folder
                    foreach ($this->_data['feeds'] as $feedHash => $feed) {
                        if (in_array($hash, $feed['foldersHash'])) {
                            $feedsHash[] = $feedHash;
                        }
                    }
                }

                // get items from a list of feeds
                if (!empty($feedsHash)) {
                    $flipFeedsHash = array_flip($feedsHash);
                    foreach ($this->_data['items'] as $itemHash => $item) {
                        if (isset($flipFeedsHash[substr($itemHash, 0, 6)])) {
                            if ($filter === 'all' or $item[1] === $isRead) {
                                $list[$itemHash] = $item;
                            }
                        }
                    }
                    if (isset($this->_data['newItems'])) {
                        foreach ($this->_data['newItems'] as $itemHash => $item) {
                            if (isset($flipFeedsHash[substr($itemHash, 0, 6)])) {
                                if ($filter === 'all' or $item[1] === $isRead) {
                                    $list[$itemHash] = $item;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $list;
    }

    public function setItems($items)
    {
        $this->_data['items'] = $items;
    }

    public function loadItem($itemHash, $keep)
    {
        $feedHash = substr($itemHash, 0, 6);
        $item = array();
        if (isset($this->_data['feeds'][$feedHash]['items'])) {
            if (isset($this->_data['feeds'][$feedHash]['items'][$itemHash])) {
                $item = $this->_data['feeds'][$feedHash]['items'][$itemHash];
            }
        } else {
            $this->loadFeed($feedHash);

            return $this->loadItem($itemHash, $keep);
        }

        if (!$keep) {
            unset($this->_data['feeds'][$feedHash]['items']);
        }

        return $item;
    }

    public function getItem($itemHash, $keep = true)
    {
        $item = $this->loadItem($itemHash, $keep);
         
        if (!empty($item)) {
            $item['itemHash'] = $itemHash;
            $time = $item['time'];
            if (strftime('%Y%m%d', $time) == strftime('%Y%m%d', time())) {
                // Today
                $item['time'] = array('time' => $time, 'list' => utf8_encode(strftime('%H:%M', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
            } else {
                if (strftime('%Y', $time) == strftime('%Y', time())) {
                    $item['time'] = array('time' => $time, 'list' => utf8_encode(strftime('%b %d', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
                } else {
                    $item['time'] = array('time' => $time, 'list' => utf8_encode(strftime('%b %d, %Y', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
                }
            }
            if (isset($this->_data['items'][$itemHash])) {
                $item['read'] = $this->_data['items'][$itemHash][1];
            } else if (isset($this->_data['newItems'][$itemHash])) {
                $item['read'] = $this->_data['newItems'][$itemHash][1];

                $currentNewItemIndex = array_search($itemHash, array_keys($this->_data['newItems']));
                if (isset($_SESSION['lastNewItemsHash'])) {
                    $lastNewItemIndex = array_search($_SESSION['lastNewItemsHash'], array_keys($this->_data['newItems']));

                    if ($lastNewItemIndex < $currentNewItemIndex) {
                        $_SESSION['lastNewItemsHash'] = $itemHash;
                    }
                } else {
                    $_SESSION['lastNewItemsHash'] = $itemHash;
                }
            } else {
                // FIX: data may be corrupted
                return false;
            }
            
            $item['author'] = htmlspecialchars(html_entity_decode(strip_tags($item['author']), ENT_QUOTES, 'utf-8'), ENT_NOQUOTES);
            $item['title'] = htmlspecialchars(html_entity_decode(strip_tags($item['title']), ENT_QUOTES, 'utf-8'), ENT_NOQUOTES);
            $item['link'] = htmlspecialchars($item['link']);
            $item['via'] = htmlspecialchars($item['via']);
            
            $item['favicon'] = $this->getFaviconFeed(substr($itemHash, 0, 6));
            $item['xmlUrl'] = htmlspecialchars($item['xmlUrl']);

            if (isset($GLOBALS['starredItems'][$itemHash])) {
                $item['starred'] = 1 ;
            } else {
                $item['starred'] = 0 ;
            }

            return $item;
        }

        return false;
    }

    public function updateItems()
    {
        if (isset($this->_data['needSort']) or (isset($this->_data['order']) and $this->_data['order'] != $this->kfc->order)) {
            unset($this->_data['needSort']);

            $this->_data['items'] = $this->_data['items']+$this->_data['newItems'];
            $this->_data['newItems'] = array();
            // sort items
            if ($this->kfc->order === 'newerFirst') {
                arsort($this->_data['items']);
            } else {
                asort($this->_data['items']);
            }
            $this->_data['order'] = $this->kfc->order;

            return true;
        }

        return false;
    }

    public function initFeedCache($feed, $force)
    {
        if (!empty($feed)) {
            if ($force) {
                $feed['etag'] = '';
                $feed['lastModified'] = '';
            }

            MyTool::$opts['http']['headers'] = array();
            if (!empty($feed['lastModified'])) {
                MyTool::$opts['http']['headers'][] = 'If-Modified-Since: ' . $feed['lastModified'];
            }
            if (!empty($feed['etag'])) {
                MyTool::$opts['http']['headers'][] = 'If-None-Match: ' . $feed['etag'];
            }
        }
        
        return $feed;
    }

    public function updateFeedCache($feed, $outputUrl)
    {
        // really new (2XX) and errors (4XX and 5XX) are considered new
        if ($outputUrl['code'] != 304) {
            if (preg_match('/^ETag: ([^\r\n]*)[\r\n]*$/im', $outputUrl['header'], $matches)) {
                $feed['etag'] = $matches[1];
            }
            if (preg_match('/^Last-Modified: ([^\r\n]*)[\r\n]*$/im', $outputUrl['header'], $matches)) {
                $feed['lastModified'] = $matches[1];
            }
        }

        if (empty($feed['etag'])) {
            unset($feed['etag']);
        }
        if (empty($feed['lastModified'])) {
            unset($feed['lastModified']);
        }

        return $feed;
    }

    public function updateFeedFromDom($feed, $dom) {
        if (empty($feed)) {
            // addFeed
            $feed = Rss::getFeed($dom);

            if (!MyTool::isUrl($feed['htmlUrl'])) {
                $feed['htmlUrl'] = ' ';
            }
            if (empty($feed['description'])) {
                $feed['description'] = ' ';
            }
            $feed['foldersHash'] = array();
            $feed['timeUpdate'] = 'auto';            
        } else if (empty($feed['description']) || empty($feed['htmlUrl'])) {
            // if feed description/htmlUrl is empty try to update
            // (after opml import, description/htmlUrl are often empty)
            $rssFeed = Rss::getFeed($dom);
            if (empty($feed['description'])) {
                if (empty($rssFeed['description'])) {
                    $rssFeed['description'] = ' ';
                }
                $feed['description'] = $rssFeed['description'];
            }
            if (empty($feed['htmlUrl'])) {
                if (empty($rssFeed['htmlUrl'])) {
                    $rssFeed['htmlUrl'] = ' ';
                }
                $feed['htmlUrl'] = $rssFeed['htmlUrl'];
            }
        }

        return $feed;
    }

    public function updateItemsFromDom($dom) {
        $items = Rss::getItems($dom);

        $newItems = array();
        foreach($items as $item) {
            if (!empty($item['link'])) {
                $hashUrl = MyTool::smallHash($item['link']);
                $newItems[$hashUrl] = array();
                $newItems[$hashUrl]['title'] = $item['title'];
                $newItems[$hashUrl]['time']  = strtotime($item['time'])
                    ? strtotime($item['time'])
                    : time();
                if (MyTool::isUrl($item['via']) &&
                    parse_url($item['via'], PHP_URL_HOST)
                    != parse_url($item['link'], PHP_URL_HOST)) {
                    $newItems[$hashUrl]['via'] = $item['via'];
                } else {
                    $newItems[$hashUrl]['via'] = '';
                }
                $newItems[$hashUrl]['link'] = $item['link'];
                $newItems[$hashUrl]['author'] = $item['author'];
                mb_internal_encoding("UTF-8");
                $newItems[$hashUrl]['description'] = mb_substr(
                    strip_tags($item['description']), 0, 500
                    );
                $newItems[$hashUrl]['content'] = $item['content'];
            }
        }

        return $newItems;
    }

    public function loadRss($xmlUrl, $feed = array(), $force = false)
    {
        $items = array();
        $feed = $this->initFeedCache($feed, $force);

        $outputUrl = MyTool::loadUrl($xmlUrl);
        
        if (!empty($outputUrl['error'])) {
            $feed['error'] = $outputUrl['error'];
        } else if (empty($outputUrl['data'])) {
            if ($outputUrl['code'] != 304) { // 304 Not modified
                $feed['error'] = Intl::msg('Empty output data');;
            }
        } else {
            $outputDom = Rss::loadDom($outputUrl['data']);
            if (!empty($outputDom['error'])) {
                $feed['error'] = $outputDom['error'];
            } else {
                unset($feed['error']);
                $feed = $this->updateFeedFromDom($feed, $outputDom['dom']);
                $feed = $this->updateFeedCache($feed, $outputUrl);
                $items = $this->updateItemsFromDom($outputDom['dom']);
            }
        }
        $feed['lastUpdate'] = time();

        return array(
            'feed' => $feed,
            'items' => $items,
        );
    }

    public function addChannel($xmlUrl)
    {
        $feedHash = MyTool::smallHash($xmlUrl);
        $error = '';
        if (!isset($this->_data['feeds'][$feedHash])) {
            $output = $this->loadRss($xmlUrl);

            if (empty($output['feed']['error'])) {
                $output['feed']['xmlUrl'] = $xmlUrl;
                $output['feed']['nbUnread'] = count($output['items']);
                $output['feed']['nbAll'] = count($output['items']);
                $this->_data['feeds'][$feedHash] = $output['feed'];
                $this->_data['needSort'] = true;

                $items = $output['items'];
                foreach (array_keys($items) as $itemHash) {
                    if (empty($items[$itemHash]['via'])) {
                        $items[$itemHash]['via'] = $output['feed']['htmlUrl'];
                    }
                    if (empty($items[$itemHash]['author'])) {
                        $items[$itemHash]['author'] = $output['feed']['title'];
                    } else {
                        $items[$itemHash]['author']
                            = $output['feed']['title'] . ' ('
                            . $items[$itemHash]['author'] . ')';
                    }
                    $items[$itemHash]['xmlUrl'] = $xmlUrl;

                    $this->_data['newItems'][$feedHash . $itemHash] = array(
                        $items[$itemHash]['time'],
                        0
                    );
                    $items[$feedHash . $itemHash] = $items[$itemHash];
                    unset($items[$itemHash]);
                }
                $this->writeFeed($feedHash, $items);
            } else {
                $error = $output['feed']['error'];
            }
        } else {
            $error = Intl::msg('Duplicated feed');
        }

        return array('error' => $error);
    }

    public function getTimeUpdate($feed)
    {
        $max = $feed['timeUpdate'];

        if ($max == 'auto') {
            $max = $this->kfc->maxUpdate;
        } elseif ($max == 'max') {
            $max = $this->kfc->maxUpdate;
        } elseif ((int) $max < MIN_TIME_UPDATE
                  || (int) $max > $this->kfc->maxUpdate) {
            $max = $this->kfc->maxUpdate;
        }

        return (int) $max;
    }

    public function needUpdate($feed)
    {
        $diff = (int) (time()-$feed['lastUpdate']);
        if ($diff > $this->getTimeUpdate($feed) * 60) {
            return true;
        }

        return false;
    }

    public function updateChannel($feedHash, $force = false)
    {
        $error = '';
        $newItems = array();

        if (!isset($this->_data['feeds'][$feedHash])) {
            return array(
                'error' => Intl::msg('Unknown feedhash'),
                'newItems' => $newItems
            );
        }

        $xmlUrl = $this->_data['feeds'][$feedHash]['xmlUrl'];

        $output = $this->loadRss($xmlUrl, $this->_data['feeds'][$feedHash], $force);
        // Update feed information
        $this->_data['feeds'][$feedHash] = $output['feed'];
        if (empty($output['feed']['error'])) {
            $this->loadFeed($feedHash);
            $oldItems = $this->_data['feeds'][$feedHash]['items'];
            $lastTime = 0;
            if (isset($this->_data['feeds'][$feedHash]['lastTime'])) {
                $lastTime = $this->_data['feeds'][$feedHash]['lastTime'];
            }
            if (!empty($oldItems)) {
                $lastTime = current($oldItems);
                $lastTime = $lastTime['time'];
            }
            $newLastTime = $lastTime;
        
            $rssItems = $output['items'];
            $rssItems = array_slice($rssItems, 0, $this->kfc->maxItems, true);
            $rssItemsHash = array_keys($rssItems);
        
            if (count($rssItemsHash) !== 0) {
                // Look for new items
                foreach ($rssItemsHash as $itemHash) {
                    // itemHash is smallHash of link. To compare to item
                    // hashes into data, we need to concatenate to feedHash.
                    if (!isset($oldItems[$feedHash.$itemHash])) {
                        if (empty($rssItems[$itemHash]['via'])) {
                            $rssItems[$itemHash]['via']
                                = $this->_data['feeds'][$feedHash]['htmlUrl'];
                        }
                        if (empty($rssItems[$itemHash]['author'])) {
                            $rssItems[$itemHash]['author']
                                = $this->_data['feeds'][$feedHash]['title'];
                        } else {
                            $rssItems[$itemHash]['author']
                                = $this->_data['feeds'][$feedHash]['title'] . ' ('
                                . $rssItems[$itemHash]['author'] . ')';
                        }
                        $rssItems[$itemHash]['xmlUrl'] = $xmlUrl;

                        if ($rssItems[$itemHash]['time'] > $lastTime) {
                            if ($rssItems[$itemHash]['time'] > $newLastTime) {
                                $newLastTime = $rssItems[$itemHash]['time'];
                            }
                            $newItems[$feedHash . $itemHash] = $rssItems[$itemHash];
                        }
                    }
                }
                $newItemsHash = array_keys($newItems);
                $this->_data['feeds'][$feedHash]['items']
                    = $newItems+$oldItems;

                // Check if items may have been missed
                if (count($oldItems) !== 0 and count($rssItemsHash) === count($newItemsHash)) {
                    $error = Intl::msg('Items may have been missed since last update');
                }

                // Remove from cache already read items not any more in the feed
                $listOfOldItems = $this->getItems($feedHash);
                foreach ($listOfOldItems as $itemHash => $item) {
                    $itemRssHash = substr($itemHash, 6, 6);
                    if (!isset($rssItems[$itemRssHash]) and $item[1] == 1) {
                        unset($this->_data['feeds'][$feedHash]['items'][$itemHash]);
                    }
                }

                // Check if quota exceeded
                $nbAll = count($this->_data['feeds'][$feedHash]['items']);
                if ($nbAll > $this->kfc->maxItems) {
                    $this->_data['feeds'][$feedHash]['items']
                        = array_slice(
                            $this->_data['feeds'][$feedHash]['items'],
                            0,
                            $this->kfc->maxItems, true
                            );
                    $nbAll = $this->kfc->maxItems;
                }

                // Remove items not any more in the cache
                foreach (array_keys($listOfOldItems) as $itemHash) {
                    if (!isset($this->_data['feeds'][$feedHash]['items'][$itemHash])) {
                        // Remove items not any more in the cache
                        unset($this->_data['items'][$itemHash]);
                        unset($this->_data['newItems'][$itemHash]);
                    }
                }

                // Update items list and feed information (nbUnread, nbAll)
                $this->_data['feeds'][$feedHash]['nbAll'] = $nbAll;
                $nbUnread = 0;
                foreach ($this->_data['feeds'][$feedHash]['items'] as $itemHash => $item) {
                    if (isset($this->_data['items'][$itemHash])) {
                        if ($this->_data['items'][$itemHash][1] === 0) {
                            $nbUnread++;
                        }
                    } else if (isset($this->_data['newItems'][$itemHash])) {
                        if ($this->_data['newItems'][$itemHash][1] === 0) {
                            $nbUnread++;
                        }
                    } else {
                        // TODO: Check if itemHash is appended at the end ??
                        $this->_data['newItems'][$itemHash] = array(
                            $item['time'],
                            0                        
                            );
                        $nbUnread++;
                    }
                }
                $this->_data['feeds'][$feedHash]['nbUnread'] = $nbUnread;
            }

            if (empty($this->_data['feeds'][$feedHash]['items'])) {
                $this->_data['feeds'][$feedHash]['lastTime'] = $newLastTime;
            } else {
                unset($this->_data['feeds'][$feedHash]['lastTime']);
            }
            $this->writeFeed($feedHash, $this->_data['feeds'][$feedHash]['items']);
            unset($this->_data['feeds'][$feedHash]['items']);

        } else {
            $error = $output['feed']['error'];
        }

        if (!empty($error)) {
            $this->_data['feeds'][$feedHash]['error'] = $error;
        }

        if (empty($newItems)) {
            $this->writeData();
        } else {
            $this->_data['needSort'] = true;

            if (isset($_SESSION['lastNewItemsHash'])) {
                $lastNewItemIndex = array_search($_SESSION['lastNewItemsHash'], array_keys($this->_data['newItems']));
                $this->_data['items'] = $this->_data['items']+array_slice($this->_data['newItems'], 0, $lastNewItemIndex + 1, true);
                $this->_data['newItems'] = array_slice($this->_data['newItems'], $lastNewItemIndex + 1, count($this->_data['newItems']) - $lastNewItemIndex, true);
                unset($_SESSION['lastNewItemsHash']);
            }

            if ($this->kfc->order === 'newerFirst') {
                arsort($this->_data['newItems']);
            } else {
                asort($this->_data['newItems']);
            }
            $this->_data['order'] = $this->kfc->order;

            $this->writeData();
        }

        return array(
            'error' => $error,
            'newItems' => $newItems
            );
    }

    public function updateFeedsHash($feedsHash, $force, $format = '')
    {
        $i = 0;

        $feedsHash = $this->orderFeedsForUpdate($feedsHash);

        ob_end_flush();
        if (ob_get_level() == 0) ob_start();

        if ($format === 'html') {
            echo '<table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>'.Intl::msg('Feed').'</th>
                  <th>'.Intl::msg('New items').'</th>
                  <th>'.Intl::msg('Time').'</th>
                  <th>'.Intl::msg('Status').'</th>
                </tr>
              </thead>
              <tbody>';
        }
        $start = microtime(true);
        foreach ($feedsHash as $feedHash) {
            $i++;
            $feed = $this->getFeed($feedHash);
            $strBegin = "\n".'<tr><td>'.str_pad($i.'/'.count($feedsHash), 7, ' ', STR_PAD_LEFT).'</td><td> <a href="?currentHash='.$feedHash.'">'.substr(str_pad($feed['title'], 50), 0, 50).'</a> </td><td>';
            if ($format === 'html') {
                echo str_pad($strBegin, 4096);
                ob_flush();
                flush();
            }

            $strEnd = '';
            if ($force or $this->needUpdate($feed)) {
                $info = $this->updateChannel($feedHash, $force);
                $strEnd .= '<span class="text-success">'.str_pad(count($info['newItems']), 3, ' ', STR_PAD_LEFT).'</span> </td><td>'.str_pad(number_format(microtime(true)-$start, 1), 6, ' ', STR_PAD_LEFT).'s </td><td>';
                if (empty($info['error'])) {
                    $strEnd .= Intl::msg('Successfully updated').'</td></tr>';
                } else {
                    $strEnd .= '<span class="text-error">'.$info['error'].'</span></td></tr>';
                }
            } else {
                $strEnd .= str_pad('0', 3, ' ', STR_PAD_LEFT).' </td><td>'.str_pad(number_format(microtime(true)-$start, 1), 6, ' ', STR_PAD_LEFT).'s </td><td><span class="text-warning">'.Intl::msg('Already up-to-date').'</span></td></tr>';
            }
            if ($format==='html') {
                echo str_pad($strEnd,4096);
                ob_flush();
                flush();
            } else {
                echo strip_tags($strBegin.$strEnd);
            }
        }
        if ($format === 'html') {
            echo '</tbody></table>';
        }
    }

    public function markAll($read) {
        $save = false;

        foreach (array_keys($this->_data['items']) as $itemHash) {
            if (!$save and $this->_data['items'][$itemHash][1] != $read) {
                $save = true;
            }
            $this->_data['items'][$itemHash][1] = $read;
        }
        foreach (array_keys($this->_data['newItems']) as $itemHash) {
            if (!$save and $this->_data['newItems'][$itemHash][1] != $read) {
                $save = true;
            }
            $this->_data['newItems'][$itemHash][1] = $read;
        }

        if ($save) {
            foreach ($this->_data['feeds'] as $feedHash => $feed) {
                if ($read == 1) {
                    $this->_data['feeds'][$feedHash]['nbUnread'] = 0;
                } else {
                    $this->_data['feeds'][$feedHash]['nbUnread'] = $this->_data['feeds'][$feedHash]['nbAll'];
                }
            }
        }

        return $save;
    }

    public function markItem($itemHash, $read) {
        $save = false;

        if (isset($this->_data['items'][$itemHash])) {
            if ($this->_data['items'][$itemHash][1] != $read) {
                $save = true;
                $this->_data['items'][$itemHash][1] = $read;
            }
        } else if (isset($this->_data['newItems'][$itemHash])) {
            if ($this->_data['newItems'][$itemHash][1] != $read) {
                $save = true;
                $this->_data['newItems'][$itemHash][1] = $read;
            }
        }

        if ($save) {
            $feedHash = substr($itemHash, 0, 6);
            if ($read == 1) {
                $this->_data['feeds'][$feedHash]['nbUnread']--;
            } else {
                $this->_data['feeds'][$feedHash]['nbUnread']++;
            }
        }

        return $save;
    }

    public function markFeeds($feedsHash, $read) {
        $save = false;

        // get items from a list of feeds
        $flipFeedsHash = array_flip($feedsHash);
        foreach ($this->_data['items'] as $itemHash => $item) {
            if (isset($flipFeedsHash[substr($itemHash, 0, 6)])) {
                if ($this->_data['items'][$itemHash][1] != $read) {
                    $save = true;
                    $this->_data['items'][$itemHash][1] = $read;
                }
            }
        }
        foreach ($this->_data['newItems'] as $itemHash => $item) {
            if (isset($flipFeedsHash[substr($itemHash, 0, 6)])) {
                if ($this->_data['newItems'][$itemHash][1] != $read) {
                    $save = true;
                    $this->_data['newItems'][$itemHash][1] = $read;
                }
            }
        }

        if ($save) {
            foreach (array_values($feedsHash) as $feedHash) {
                if ($read == 1) {
                    $this->_data['feeds'][$feedHash]['nbUnread'] = 0;
                } else {
                    $this->_data['feeds'][$feedHash]['nbUnread'] = $this->_data['feeds'][$feedHash]['nbAll'];
                }
            }
        }

        return $save;
    }
        
    public function mark($hash, $read)
    {
        if (empty($hash) || $hash == 'all') {
            // all items
            return $this->markAll($read);
        } else {
            if (strlen($hash) === 12) {
                // an item
                return $this->markItem($hash, $read);
            } else {
                $feedsHash = array();
                if (isset($this->_data['feeds'][$hash])) {
                    // a feed
                    $feedsHash[] = $hash;
                } else if (isset($this->_data['folders'][$hash])) {
                    // a folder
                    foreach ($this->_data['feeds'] as $feedHash => $feed) {
                        if (in_array($hash, $feed['foldersHash'])) {
                            $feedsHash[] = $feedHash;
                        }
                    }
                }

                return $this->markFeeds($feedsHash, $read);
            }
        }

        return false;
    }

        
    

    public function hashType($hash)
    {
        $type = '';
        if (empty($hash) || $hash=='all') {
            $type = 'all';
        } else {
            if (strlen($hash) === 12) {
                // should be an item
                $type = 'item';
            } else {
                if (isset($this->_data['folders'][$hash])) {
                    // a folder
                    $type = 'folder';
                } else {
                    if (isset($this->_data['feeds'][$hash])) {
                        // a feed
                        $type = 'feed';
                    } else {
                        $type = 'unknown';
                    }
                }
            }
        }

        return $type;
    }

    public static function sortByOrder($a, $b) {
        return strnatcasecmp($a['order'], $b['order']);
    }

    public static function sortByTitle($a, $b) {
        return strnatcasecmp($a['title'], $b['title']);
    }
}


class Intl
{
    public static $lazy = false;
    public static $lang = "en_US";
    public static $dir = "locale";
    public static $domain = "messages";
    public static $messages = array();
    public static $langList = array();

    public static function init()
    {
        $lang = self::$lang;
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
        } else if (isset($_SESSION['lang'])) {
            $lang = $_SESSION['lang'];
        }
         
        if (in_array($lang, array_keys(self::$langList))) {
            self::$lang = $lang;
        } else {
            unset($_SESSION['lang']);
        }
    }

    public static function addLang($lang, $name, $class) {
        self::$langList[$lang] = array(
            'name' => $name,
            'class' => $class
        );
    }

    public static function load($lang) {
        self::$lazy = true;

        if (file_exists(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po')) {
            self::$messages[$lang] = self::compress(self::read(self::$dir.'/'.$lang.'/LC_MESSAGES/'.self::$domain.'.po'));
        } else if (class_exists('Intl_'.$lang)) {
            call_user_func_array(
                array('Intl_'.$lang, 'init'),
                array(&self::$messages)
            );
        }
        
        return isset(self::$messages[$lang])?self::$messages[$lang]:array();
    }

    public static function compress($hash) {
        foreach ($hash as $hashId => $hashArray) {
            $hash[$hashId] = $hashArray['msgstr'];
        }

        return $hash;
    }

    public static function msg($string, $context = "")
    {
        if (!self::$lazy) {
            self::load(self::$lang);
        }

        return self::n_msg($string, '', 0, $context);
    }

    public static function n_msg($string, $plural, $count, $context = "")
    {
        if (!self::$lazy) {
            self::load(self::$lang);
        }

        // TODO extract Plural-Forms from po file
        // https://www.gnu.org/savannah-checkouts/gnu/gettext/manual/html_node/Plural-forms.html
        $count = $count > 1 ? 1 : 0;

        if (isset(self::$messages[self::$lang][$string][$count])) {
            return self::$messages[self::$lang][$string][$count];
        }

        if ($count != 0) {
            return $plural;
        }

        return $string;
    }

    public static function read($pofile)
    {
        $handle = fopen( $pofile, 'r' );
        $hash = array();
        $fuzzy = false;
        $tcomment = $ccomment = $reference = null;
        $entry = $entryTemp = array();
        $state = null;
        $just_new_entry = false; // A new entry has ben just inserted

        while(!feof($handle)) {
            $line = trim( fgets($handle) );

            if($line==='') {
                if($just_new_entry) {
                    // Two consecutive blank lines
                    continue;
                }

                // A new entry is found!
                $hash[] = $entry;
                $entry = array();
                $state= null;
                $just_new_entry = true;
                continue;
            }

            $just_new_entry = false;

            $split = preg_split('/\s/ ', $line, 2 );
            $key = $split[0];
            $data = isset($split[1])? $split[1]:null;
                        
            switch($key) {
            case '#,':  //flag
                $entry['fuzzy'] = in_array('fuzzy', preg_split('/,\s*/', $data) );
                $entry['flags'] = $data;
                break;

            case '#':   //translation-comments
                $entryTemp['tcomment'] = $data;
                $entry['tcomment'] = $data;
                break;

            case '#.':  //extracted-comments
                $entryTemp['ccomment'] = $data;
                break;

            case '#:':  //reference
                $entryTemp['reference'][] = addslashes($data);
                $entry['reference'][] = addslashes($data);
                break;

            case '#|':  //msgid previous-untranslated-string
                // start a new entry
                break;
                                
            case '#@':  // ignore #@ default
                $entry['@'] = $data;
                break;

                // old entry
            case '#~':
                $key = explode(' ', $data );
                $entry['obsolete'] = true;
                switch( $key[0] )
                {
                case 'msgid': $entry['msgid'] = trim($data,'"');
                    break;

                case 'msgstr':  $entry['msgstr'][] = trim($data,'"');
                    break;
                default:        break;
                }
                                                        
                continue;
                break;

            case 'msgctxt' :
                // context
            case 'msgid' :
                // untranslated-string
            case 'msgid_plural' :
                // untranslated-string-plural
                $state = $key;
                $entry[$state] = $data;
                break;
                                
            case 'msgstr' :
                // translated-string
                $state = 'msgstr';
                $entry[$state][] = $data;
                break;

            default :

                if( strpos($key, 'msgstr[') !== FALSE ) {
                    // translated-string-case-n
                    $state = 'msgstr';
                    $entry[$state][] = $data;
                } else {
                    // continued lines
                    //echo "O NDE ELSE:".$state.':'.$entry['msgid'];
                    switch($state) {
                    case 'msgctxt' :
                    case 'msgid' :
                    case 'msgid_plural' :
                        //$entry[$state] .= "\n" . $line;
                        if(is_string($entry[$state])) {
                            // Convert it to array
                            $entry[$state] = array( $entry[$state] );
                        }
                        $entry[$state][] = $line;
                        break;
                                                                
                    case 'msgstr' :
                        //Special fix where msgid is ""
                        if($entry['msgid']=="\"\"") {
                            $entry['msgstr'][] = trim($line,'"');
                        } else {
                            //$entry['msgstr'][sizeof($entry['msgstr']) - 1] .= "\n" . $line;
                            $entry['msgstr'][]= trim($line,'"');
                        }
                        break;
                                                                
                    default :
                        throw new Exception('Parse ERROR!');
                        return FALSE;
                    }
                }
                break;
            }
        }
        fclose($handle);

        // add final entry
        if($state == 'msgstr') {
            $hash[] = $entry;
        }

        // Cleanup data, merge multiline entries, reindex hash for ksort
        $temp = $hash;
        $entries = array ();
        foreach($temp as $entry) {
            foreach($entry as & $v) {
                $v = self::clean($v);
                if($v === FALSE) {
                    // parse error
                    return FALSE;
                }
            }

            $id = is_array($entry['msgid'])? implode('',$entry['msgid']):$entry['msgid'];
                        
            $entries[ $id ] = $entry;
        }

        return $entries;
    }

    public static function clean($x)
    {
        if(is_array($x)) {
            foreach($x as $k => $v) {
                $x[$k] = self::clean($v);
            }
        } else {
            // Remove " from start and end
            if($x == '') {
                return '';
            }

            if($x[0]=='"') {
                $x = substr($x, 1, -1);
            }

            $x = stripcslashes( $x );
        }

        return $x;
    }
}


class MyTool
{
    // http://php.net/manual/en/function.libxml-set-streams-context.php
    static $opts = array();
    static $redirects = 20;

    const ERROR_UNKNOWN_CODE = 1;
    const ERROR_LOCATION = 2;
    const ERROR_TOO_MANY_REDIRECTS = 3;
    const ERROR_NO_CURL = 4;

    public static function loadUrl($url)
    {
        $redirects = self::$redirects;
        $opts = self::$opts;
        $header = '';
        $code = '';
        $data = '';
        $error = '';

        if (in_array('curl', get_loaded_extensions())) {
            $ch = curl_init($url);

            if (!empty($opts)) {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opts['http']['timeout']);
                curl_setopt($ch, CURLOPT_TIMEOUT, $opts['http']['timeout']);
                curl_setopt($ch, CURLOPT_USERAGENT, $opts['http']['user_agent']);
                if (!empty($opts['http']['headers'])) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['http']['headers']);
                }
            }
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);

            if ((!ini_get('open_basedir') && !ini_get('safe_mode')) || $redirects < 1) {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirects > 0);
                curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
                $data = curl_exec($ch);
            } else {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
                do {
                    $data = curl_exec($ch);
                    if (curl_errno($ch)) {
                        break;
                    }
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($code != 301 && $code != 302 && $code!=303 && $code!=307) {
                        break;
                    }

                    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $header = substr($data, 0, $headerSize);
                    if (!preg_match('/^(?:Location|URI): ([^\r\n]*)[\r\n]*$/im', $header, $matches)) {
                        $error = self::ERROR_LOCATION;
                        break;
                    }
                    curl_setopt($ch, CURLOPT_URL, $matches[1]);
                } while (--$redirects);

                if (!$redirects) {
                    $error = self::ERROR_TOO_MANY_REDIRECTS;
                }
            }

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($data, 0, $headerSize);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $data = substr($data, $headerSize);
            $error = curl_error($ch);

            curl_close($ch);
        } else {
            $context = stream_context_create($opts);
            if ($stream = fopen($url, 'r', false, $context)) {
                $data = stream_get_contents($stream);
                $status = $http_response_header[0];
                $code = explode(' ', $status);
                if (count($code)>1) {
                    $code = $code[1];
                } else {
                    $code = '';
                    $error = self::ERROR_UNKNOWN_CODE;
                }
                $header = implode("\r\n", $http_response_header);
                fclose($stream);
            } else {
                $error = self::ERROR_NO_CURL;
            }
        }

        return array(
            'header' => $header,
            'code' => $code,
            'data' => $data,
            'error' => self::getError($error),
        );
    }

    public static function getError($error)
    {
        switch ($error) {
        case self::ERROR_UNKNOWN_CODE:
            return Intl::msg('Http code not valid');
            break;
        case self::ERROR_LOCATION:
            return Intl::msg('Location not found');
            break;
        case self::ERROR_TOO_MANY_REDIRECTS:
            return Intl::msg('Too many redirects');
            break;
        case self::ERROR_NO_CURL:
            return Intl::msg('Error when loading without curl');
            break;
        default:
            return $error;
            break;
        }
    }

    public static function initPhp()
    {
        define('START_TIME', microtime(true));

        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 !");
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        function stripslashesDeep($value) {
            return is_array($value)
                ? array_map('stripslashesDeep', $value)
                : stripslashes($value);
        }

        if (get_magic_quotes_gpc()) {
            $_POST = array_map('stripslashesDeep', $_POST);
            $_GET = array_map('stripslashesDeep', $_GET);
            $_COOKIE = array_map('stripslashesDeep', $_COOKIE);
        }

        ob_start();
        register_shutdown_function('ob_end_flush');
    }

    public static function isUrl($url)
    {
        // http://neo22s.com/check-if-url-exists-and-is-online-php/
        $pattern='|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i';

        return preg_match($pattern, $url);
    }

    public static function isEmail($email)
    {
        $pattern = "/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2, 4}$/i";

        return (preg_match($pattern, $email));
    }

    public static function formatBBCode($text)
    {
        $replace = array(
            '/\[m\](.+?)\[\/m\]/is'
            => '/* moderate */',
            '/\[b\](.+?)\[\/b\]/is'
            => '<strong>$1</strong>',
            '/\[i\](.+?)\[\/i\]/is'
            => '<em>$1</em>',
            '/\[s\](.+?)\[\/s\]/is'
            => '<del>$1</del>',
            '/\[u\](.+?)\[\/u\]/is'
            => '<span style="text-decoration: underline;">$1</span>',
            '/\[url\](.+?)\[\/url]/is'
            => '<a href="$1">$1</a>',
            '/\[url=(\w+:\/\/[^\]]+)\](.+?)\[\/url]/is'
            => '<a href="$1">$2</a>',
            '/\[quote\](.+?)\[\/quote\]/is'
            => '<blockquote>$1</blockquote>',
            '/\[code\](.+?)\[\/code\]/is'
            => '<code>$1</code>',
            '/\[([^[]+)\|([^[]+)\]/is'
            => '<a href="$2">$1</a>'
            );
        $text = preg_replace(
            array_keys($replace),
            array_values($replace),
            $text
        );

        return $text;
    }

    public static function formatText($text)
    {
        $text = preg_replace_callback(
            '/<code_html>(.*?)<\/code_html>/is',
            create_function(
                '$matches',
                'return htmlspecialchars($matches[1]);'
            ),
            $text
        );
        $text = preg_replace_callback(
            '/<code_php>(.*?)<\/code_php>/is',
            create_function(
                '$matches',
                'return highlight_string("<?php $matches[1] ?>", true);'
            ),
            $text
        );
        $text = preg_replace('/<br \/>/is', '', $text);

        $text = preg_replace(
            '#(^|\s)([a-z]+://([^\s\w/]?[\w/])*)(\s|$)#im',
            '\\1<a href="\\2">\\2</a>\\4',
            $text
        );
        $text = preg_replace(
            '#(^|\s)wp:?([a-z]{2}|):([\w]+)#im',
            '\\1<a href="http://\\2.wikipedia.org/wiki/\\3">\\3</a>',
            $text
        );
        $text = str_replace(
            'http://.wikipedia.org/wiki/',
            'http://www.wikipedia.org/wiki/',
            $text
        );
        $text = str_replace('\wp:', 'wp:', $text);
        $text = str_replace('\http:', 'http:', $text);
        $text = MyTool::formatBBCode($text);
        $text = nl2br($text);

        return $text;
    }

    public static function getUrl()
    {
        $https = (!empty($_SERVER['HTTPS'])
                  && (strtolower($_SERVER['HTTPS']) == 'on'))
            || (isset($_SERVER["SERVER_PORT"])
                && $_SERVER["SERVER_PORT"] == '443'); // HTTPS detection.
        $serverport = (!isset($_SERVER["SERVER_PORT"])
                       || $_SERVER["SERVER_PORT"] == '80'
                       || ($https && $_SERVER["SERVER_PORT"] == '443')
                       ? ''
                       : ':' . $_SERVER["SERVER_PORT"]);

        $scriptname = str_replace('/index.php', '/', $_SERVER["SCRIPT_NAME"]);

        if (!isset($_SERVER["SERVER_NAME"])) {
            return $scriptname;
        }

        return 'http' . ($https ? 's' : '') . '://'
            . $_SERVER["SERVER_NAME"] . $serverport . $scriptname;
    }

    public static function rrmdir($dir)
    {
        if (is_dir($dir) && ($d = @opendir($dir))) {
            while (($file = @readdir($d)) !== false) {
                if ( $file == '.' || $file == '..' ) {
                    continue;
                } else {
                    unlink($dir . '/' . $file);
                }
            }
        }
    }

    public static function humanBytes($bytes)
    {
        $siPrefix = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
        $base = 1024;
        $class = min((int) log($bytes, $base), count($siPrefix) - 1);
        $val = sprintf('%1.2f', $bytes / pow($base, $class));

        return $val . ' ' . $siPrefix[$class];
    }

    public static function returnBytes($val)
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        switch($last)
        {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
        }

        return $val;
    }

    public static function getMaxFileSize()
    {
        $sizePostMax   = MyTool::returnBytes(ini_get('post_max_size'));
        $sizeUploadMax = MyTool::returnBytes(ini_get('upload_max_filesize'));

        // Return the smaller of two:
        return min($sizePostMax, $sizeUploadMax);
    }

    public static function smallHash($text)
    {
        $t = rtrim(base64_encode(hash('crc32', $text, true)), '=');
        // Get rid of characters which need encoding in URLs.
        $t = str_replace('+', '-', $t);
        $t = str_replace('/', '_', $t);
        $t = str_replace('=', '@', $t);

        return $t;
    }

    public static function renderJson($data)
    {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json; charset=UTF-8');

        echo json_encode($data);
        exit();
    }

    public static function grabToLocal($url, $file, $force = false)
    {
        if ((!file_exists($file) || $force) && in_array('curl', get_loaded_extensions())){
            $ch = curl_init ($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
            $raw = curl_exec($ch);
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                $fp = fopen($file, 'x');
                fwrite($fp, $raw);
                fclose($fp);
            }
            curl_close ($ch);
        }
    }

    public static function redirect($rurl = '')
    {
        if ($rurl === '') {
            // if (!empty($_SERVER['HTTP_REFERER']) && strcmp(parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST),$_SERVER['SERVER_NAME'])==0)
            $rurl = (empty($_SERVER['HTTP_REFERER'])?'?':$_SERVER['HTTP_REFERER']);
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
        }

        // prevent loop
        if (empty($rurl) || parse_url($rurl, PHP_URL_QUERY) === $_SERVER['QUERY_STRING']) {
            $rurl = MyTool::getUrl();
        }

        if (substr($rurl, 0, 1) !== '?') {
            $ref = MyTool::getUrl();
            if (substr($rurl, 0, strlen($ref)) !== $ref) {
                $rurl = $ref;
            }
        }
        header('Location: '.$rurl);
        exit();
    }
}

class Opml
{
    public static function importOpml($kfData)
    {
        $feeds = $kfData['feeds'];
        $folders = $kfData['folders'];

        $filename  = $_FILES['filetoupload']['name'];
        $filesize  = $_FILES['filetoupload']['size'];
        $data      = file_get_contents($_FILES['filetoupload']['tmp_name']);
        $overwrite = isset($_POST['overwrite']);

        $opml = new DOMDocument('1.0', 'UTF-8');

        $importCount=0;
        if ($opml->loadXML($data)) {
            $body = $opml->getElementsByTagName('body');
            $xmlArray = Opml::getArrayFromXml($body->item(0));
            $array = Opml::convertOpmlArray($xmlArray['outline']);

            foreach ($array as $hashUrl => $arrayInfo) {
                $title = '';
                if (isset($arrayInfo['title'])) {
                    $title = $arrayInfo['title'];
                } else if (isset($arrayInfo['text'])) {
                    $title = $arrayInfo['text'];
                }
                $foldersHash = array();
                if (isset($arrayInfo['folders'])) {
                    foreach ($arrayInfo['folders'] as $folder) {
                        $folderTitle = html_entity_decode(
                            $folder,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        $folderHash = MyTool::smallHash($folderTitle);
                        if (!isset($folders[$folderHash])) {
                            $folders[$folderHash] = array('title' => $folderTitle, 'isOpen' => true);
                        }
                        $foldersHash[] = $folderHash;
                    }
                }
                $timeUpdate = 'auto';
                $lastUpdate = 0;
                $xmlUrl = '';
                if (isset($arrayInfo['xmlUrl'])) {
                    $xmlUrl = $arrayInfo['xmlUrl'];
                }
                $htmlUrl = '';
                if (isset($arrayInfo['htmlUrl'])) {
                    $htmlUrl = $arrayInfo['htmlUrl'];
                }
                $description = '';
                if (isset($arrayInfo['description'])) {
                    $description = $arrayInfo['description'];
                }
                // create new feed
                if (!empty($xmlUrl)) {
                    $oldFeed = array('nbUnread' => 0, 'nbAll' => 0);
                    if (isset($feeds[$hashUrl])) {
                        $oldFeed['nbUnread'] = $feeds[$hashUrl]['nbUnread'];
                        $oldFeed['nbAll'] = $feeds[$hashUrl]['nbAll'];
                    }
                    $currentFeed = array(
                        'title'
                        =>
                        html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                        'description'
                        =>
                        html_entity_decode($description, ENT_QUOTES, 'UTF-8'),
                        'htmlUrl'
                        =>
                        html_entity_decode($htmlUrl, ENT_QUOTES, 'UTF-8'),
                        'xmlUrl'
                        =>
                        html_entity_decode($xmlUrl, ENT_QUOTES, 'UTF-8'),
                        'nbUnread' => $oldFeed['nbUnread'],
                        'nbAll' => $oldFeed['nbAll'],
                        'foldersHash' => $foldersHash,
                        'timeUpdate' => $timeUpdate,
                        'lastUpdate' => $lastUpdate);

                    if ($overwrite || !isset($feeds[$hashUrl])) {
                        $feeds[$hashUrl] = $currentFeed;
                        $importCount++;
                    }
                }
            }

            FeedPage::init(
                array(
                    'base' => '',
                    'message' => sprintf(Intl::msg('File %s (%s) was successfully processed: %d links imported'),htmlspecialchars($filename),MyTool::humanBytes($filesize), $importCount),
                    'button' => Intl::msg('Continue'),
                    'referer' => MyTool::getUrl(),
                    'version' => FEED_VERSION,
                    'pagetitle' => 'KrISS feed installation'
                )
            );
            FeedPage::messageTpl();

            $kfData['feeds'] = $feeds;
            $kfData['folders'] = $folders;

            return $kfData;
        } else {

            FeedPage::init(
                array(
                    'base' => '',
                    'class' => 'text-success',
                    'message' => sprintf(Intl::msg('File %s (%s) has an unknown file format. Check encoding, try to remove accents and try again. Nothing was imported.'),htmlspecialchars($filename),MyTool::humanBytes($filesize)),
                    'referer' => MyTool::getUrl().'?import',
                    'version' => FEED_VERSION,
                    'pagetitle' => 'KrISS feed installation'
                )
            );
            FeedPage::messageTpl();
            exit;
        }
    }

    public static function exportOpml($feeds, $folders)
    {
        $withoutFolder = array();
        $withFolder = array();

        // get a new representation of data using folders as key
        foreach ($feeds as $hashUrl => $arrayInfo) {
            if (empty($arrayInfo['foldersHash'])) {
                $withoutFolder[] = $hashUrl;
            } else {
                foreach ($arrayInfo['foldersHash'] as $folderHash) {
                    $withFolder[$folderHash][] = $hashUrl;
                }
            }
        }

        // generate opml file
        header('Content-Type: text/xml; charset=utf-8');
        header(
            'Content-disposition: attachment; filename=kriss_feed_'
            . strval(date('Ymd_His')) . '.opml'
        );
        $opmlData = new DOMDocument('1.0', 'UTF-8');

        // we want a nice output
        $opmlData->formatOutput = true;

        // opml node creation
        $opml = $opmlData->createElement('opml');
        $opmlVersion = $opmlData->createAttribute('version');
        $opmlVersion->value = '1.0';
        $opml->appendChild($opmlVersion);

        // head node creation
        $head = $opmlData->createElement('head');
        $title = $opmlData->createElement('title', 'KrISS Feed');
        $head->appendChild($title);
        $opml->appendChild($head);

        // body node creation
        $body = $opmlData->createElement('body');

        // without folder outline node
        foreach ($withoutFolder as $hashUrl) {
            $outline = $opmlData->createElement('outline');
            $outlineTitle = $opmlData->createAttribute('title');
            $outlineTitle->value = htmlspecialchars(
                $feeds[$hashUrl]['title']
            );
            $outline->appendChild($outlineTitle);
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value
                = htmlspecialchars($feeds[$hashUrl]['title']);
            $outline->appendChild($outlineText);
            if (!empty($feeds[$hashUrl]['description'])) {
                $outlineDescription
                    = $opmlData->createAttribute('description');
                $outlineDescription->value
                    = htmlspecialchars($feeds[$hashUrl]['description']);
                $outline->appendChild($outlineDescription);
            }
            $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
            $outlineXmlUrl->value
                = htmlspecialchars($feeds[$hashUrl]['xmlUrl']);
            $outline->appendChild($outlineXmlUrl);
            $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
            $outlineHtmlUrl->value = htmlspecialchars(
                $feeds[$hashUrl]['htmlUrl']
            );
            $outline->appendChild($outlineHtmlUrl);
            $outlineType = $opmlData->createAttribute('type');
            $outlineType->value = 'rss';
            $outline->appendChild($outlineType);
            $body->appendChild($outline);
        }

        // with folder outline node
        foreach ($withFolder as $folderHash => $arrayHashUrl) {
            $outline = $opmlData->createElement('outline');
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value = htmlspecialchars($folders[$folderHash]['title']);
            $outline->appendChild($outlineText);

            foreach ($arrayHashUrl as $hashUrl) {
                $outlineKF = $opmlData->createElement('outline');
                $outlineTitle = $opmlData->createAttribute('title');
                $outlineTitle->value
                    = htmlspecialchars($feeds[$hashUrl]['title']);
                $outlineKF->appendChild($outlineTitle);
                $outlineText = $opmlData->createAttribute('text');
                $outlineText->value
                    = htmlspecialchars($feeds[$hashUrl]['title']);
                $outlineKF->appendChild($outlineText);
                if (!empty($feeds[$hashUrl]['description'])) {
                    $outlineDescription
                        = $opmlData->createAttribute('description');
                    $outlineDescription->value = htmlspecialchars(
                        $feeds[$hashUrl]['description']
                    );
                    $outlineKF->appendChild($outlineDescription);
                }
                $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
                $outlineXmlUrl->value
                    = htmlspecialchars($feeds[$hashUrl]['xmlUrl']);
                $outlineKF->appendChild($outlineXmlUrl);
                $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
                $outlineHtmlUrl->value
                    = htmlspecialchars($feeds[$hashUrl]['htmlUrl']);
                $outlineKF->appendChild($outlineHtmlUrl);
                $outlineType = $opmlData->createAttribute('type');
                $outlineType->value = 'rss';
                $outlineKF->appendChild($outlineType);
                $outline->appendChild($outlineKF);
            }
            $body->appendChild($outline);
        }

        $opml->appendChild($body);
        $opmlData->appendChild($opml);

        echo $opmlData->saveXML();
        exit();
    }

    public static function getArrayFromXml($node)
    {
        $array = false;

        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attr) {
                $array[$attr->nodeName] = $attr->nodeValue;
            }
        }

        if ($node->hasChildNodes()) {
            if ($node->childNodes->length == 1) {
                $array[$node->firstChild->nodeName]
                    = $node->firstChild->nodeValue;
            } else {
                foreach ($node->childNodes as $childNode) {
                    if ($childNode->nodeType != XML_TEXT_NODE) {
                        $array[$childNode->nodeName][]
                            = Opml::getArrayFromXml($childNode);
                    }
                }
            }
        }

        return $array;
    }

    public static function convertOpmlArray($array, $listFolders = array())
    {
        $newArray = array();

        for ($i = 0, $len = count($array); $i < $len; $i++) {
            if (isset($array[$i]['outline'])
                && (isset($array[$i]['text'])
                || isset($array[$i]['title']))
            ) {
                // here is a folder
                if (isset($array[$i]['text'])) {
                    $listFolders[] = $array[$i]['text'];
                } else {
                    $listFolders[] = $array[$i]['title'];
                }
                $newArray = array_merge(
                    $newArray,
                    Opml::convertOpmlArray(
                        $array[$i]['outline'],
                        $listFolders
                    )
                );
                array_pop($listFolders);
            } else {
                if (isset($array[$i]['xmlUrl'])) {
                    // here is a feed
                    $xmlUrl = MyTool::smallHash($array[$i]['xmlUrl']);
                    if (isset($newArray[$xmlUrl])) {
                        //feed already exists
                        foreach ($listFolders as $val) {
                            // add folder to the feed
                            if (!in_array(
                                $val,
                                $newArray[$xmlUrl]['folders']
                            )) {
                                $newArray[$xmlUrl]['folders'][] = $val;
                            }
                        }
                    } else {
                        // here is a new feed
                        foreach ($array[$i] as $attr => $val) {
                            $newArray[$xmlUrl][$attr] = $val;
                        }
                        $newArray[$xmlUrl]['folders'] = $listFolders;
                    }
                }
            }
        }

        return $newArray;
    }
}


class PageBuilder
{
    private $tpl; // For lazy initialization

    private $pageClass;

    public $var = array();

    public function __construct($pageClass)
    {
        $this->tpl = false;
        $this->pageClass = $pageClass;
    }

    private function init()
    {
        $this->tpl = true;
    }

    // 
    public function assign($variable, $value = null)
    {
        if ($this->tpl === false) {
            $this->init(); // Lazy initialization
        }
        if (is_array($variable)) {
            $this->var += $variable;
        } else {
            $this->var[$variable] = $value;
        }
    }

    public function renderPage($page, $exit = true)
    {
        if ($this->tpl===false) {
            $this->init(); // Lazy initialization
        }
        $method = $page.'Tpl';
        if (method_exists($this->pageClass, $method)) {
            $this->assign('template', $page);
            $classPage = new $this->pageClass;
            $classPage->init($this->var);
            ob_start();
            $classPage->$method();
            ob_end_flush();
        } else {
            return false;
        }
        if ($exit) {
            exit();
        }

        return true;
    }
}

class Plugin
{
    public static $dir = "plugins";
    public static $hooks = array();

    public static function init() {
        $arrayPlugins = glob(self::$dir. '/*.php');
      
        if(is_array($arrayPlugins)) {  
            foreach($arrayPlugins as $plugin) {  
                include $plugin;  
            }  
        }
    }

    public static function listAll() {
        $list = array();
        self::callHook('Plugin_registry', array(&$list));
        return $list;
    }

    public static function addHook($hookName, $functionName, $priority = 10) {
        self::$hooks[$hookName][$priority][] = $functionName;
    } 

    public static function callHook($hookName, $hookArguments = array()) {
	if(isset(self::$hooks[$hookName])) {
            ksort(self::$hooks[$hookName]);
            foreach (self::$hooks[$hookName] as $hooks) {
                foreach($hooks as $functionName) {
                    call_user_func_array($functionName, $hookArguments);
                }    
            }
        } 
    }
}

class Rss
{
    const UNKNOWN = 0;
    const RSS = 1;
    const ATOM = 2;

    static $feedFormat = array(
        'title' => array('title'),
        'description' => array('description', 'subtitle'),
        'htmlUrl' => array('link', 'id', 'guid')
    );

    static $itemFormat = array(
        'author' => array('author', 'creator', 'dc:author', 'dc:creator'),
        'content' => array('content:encoded', 'content', 'description', 'summary', 'subtitle'),
        'description' => array('description', 'summary', 'subtitle', 'content', 'content:encoded'),
        'via' => array('guid', 'id'),
        'link' => array('feedburner:origLink', array('link', 'href', array('rel=alternate')), array('link', 'href'), 'link', 'guid', 'id'),
        'time' => array('pubDate', 'updated', 'lastBuildDate', 'published', 'dc:date', 'date', 'created', 'modified'),
        'title' => array('title')
    );

    public static function formatElement($element, $formats)
    {
        $newElement = array();
        foreach ($formats as $format => $list) {
            $newElement[$format] = '';
            $len = count($list);
            for ($i = 0; $i < $len && empty($newElement[$format]); $i++) {
                $selector = $list[$i];
                if (is_array($list[$i])) {
                    $selector = $list[$i][0];
                    if (count($list[$i]) === 2) {
                        $list[$i][] = array();
                    }
                } else {
                    $list[$i] = array($list[$i], '', array());
                }

                $name = explode(':', $selector);

                if (count($name) > 1) {
                    $elements = $element->getElementsByTagNameNS('*', $name[1]);
                } else {
                    $elements = $element->getElementsByTagName($name[0]);
                }

                for ($j = 0; $j < $elements->length && empty($newElement[$format]); $j++) {
                    $elt = $elements->item($j);
                    $isCorrect = true;
                    if ($elements->item($j)->tagName != $selector) {
                        $isCorrect = false;
                    } else {
                        foreach($list[$i][2] as $attr) {
                            $attrs = explode('=', $attr);
                            if (count($attrs) !== 2 || 
                                !$elements->item($j)->hasAttribute($attrs[0]) ||
                                $elements->item($j)->getAttribute($attrs[0]) !== $attrs[1]) {
                                $isCorrect = false;
                            }
                        }
                    }

                    if (!$elt->parentNode->isSameNode($element)) {
                        $isCorrect = false;
                    }

                    if ($isCorrect) {
                        if (empty($list[$i][1])) {
                            $newElement[$format] = $elt->textContent;
                        } else {
                            if ($elements->item($j)->hasAttribute($list[$i][1])) {
                                $newElement[$format] = $elt->getAttribute($list[$i][1]);
                            }
                        }
                    }
                }
            }
        }

        return $newElement;
    }

    public static function getFeed($dom)
    {
        $feed = new DOMNodelist;

        $type = self::getType($dom);
        if ($type === self::RSS) {
            $feed = $dom->getElementsByTagName('channel')->item(0);
        } elseif ($type === self::ATOM) {
            $feed = $dom->getElementsByTagName('feed')->item(0);
        }

        return self::formatElement($feed, self::$feedFormat);
    }

    public static function getItems($dom, $nb = -1)
    {
        $items = new DOMNodelist;

        $type = self::getType($dom);
        if ($type === self::RSS) {
            $items = $dom->getElementsByTagName('item');
        } elseif ($type === self::ATOM) {
            $items = $dom->getElementsByTagName('entry');
        }
        
        $newItems = array();
        $max = $nb === -1 ? $items->length : max($nb, $item->length);
        for ($i = 0; $i < $max; $i++) {
            $item = self::formatElement($items->item($i), self::$itemFormat);
            if (!empty($item)) {
                $newItems[] = $item;
            }
        }

        return $newItems;
    }

    public static function getType($dom) {
        $type = self::UNKNOWN;

        $feed = $dom->getElementsByTagName('channel');
        if ($feed->item(0)) { // RSS/rdf:RDF feed
            $type = self::RSS;
        } else {
            $feed = $dom->getElementsByTagName('feed');
            if ($feed->item(0)) { // Atom feed
                $type = self::ATOM;
            }
        }

        return $type;
    }

    public static function loadDom($data)
    {
        $error = '';
        set_error_handler(array('Rss', 'silenceErrors'));
        libxml_clear_errors();
        $dom = new DOMDocument();
        $isValid = $dom->loadXML($data);
        restore_error_handler();

        return array(
            'dom' => $dom,
            'error' => self::getError(libxml_get_last_error())
        );
    }

    public static function getError($error)
    {
        $return = '';

        if ($error !== false) {
            switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return = "Warning XML $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return = "Error XML $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return = "Fatal Error XML $error->code: ";
                break;
            }
            $return .= $return.trim($error->message);
        }

        return $return;
    }

    public static function silenceErrors($num, $str)
    {
        // No-op                                                       
    }
}

class Session
{
    public static $inactivityTimeout = 3600;

    public static $disableSessionProtection = false;

    public static $banAfter = 4;
    public static $banDuration = 1800;
    public static $banFile;

    public static function init($sessionName = '', $banFile = '')
    {
        self::$banFile = $banFile;

        // Force cookie path (but do not change lifetime)
        $cookie = session_get_cookie_params();
        // Default cookie expiration and path.
        $cookiedir = '';
        if(dirname($_SERVER['SCRIPT_NAME'])!='/') {
            $cookiedir = dirname($_SERVER["SCRIPT_NAME"]).'/';
        }
        $ssl = false;

        if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $ssl = true;
        }
                
        session_set_cookie_params($cookie['lifetime'], $cookiedir, $cookie['domain'], $ssl);
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            if (!empty($sessionName)) {
                session_name($sessionName);
            }
            session_start();
        }
    }

    private static function _allIPs()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $ip.= isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? '_'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $ip.= isset($_SERVER['HTTP_CLIENT_IP']) ? '_'.$_SERVER['HTTP_CLIENT_IP'] : '';

        return $ip;
    }

    public static function login (
        $login,
        $password,
        $loginTest,
        $passwordTest,
        $pValues = array())
    {
        self::banInit();
        if (!self::banCanLogin()) {
            die('I said: NO. You are banned for the moment. Go away.');
        }
        if ($login == $loginTest && $password==$passwordTest) {
            self::banLoginOk();
            // Generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('', true).'_'.mt_rand());
            $_SESSION['ip'] = Session::_allIPs();
            $_SESSION['username'] = $login;
            // Set session expiration.
            $_SESSION['expires_on'] = time() + Session::$inactivityTimeout;

            foreach ($pValues as $key => $value) {
                $_SESSION[$key] = $value;
            }

            return true;
        }
        self::banLoginFailed();
        Session::logout();

        return false;
    }

    public static function logout()
    {
        unset($_SESSION['uid'], $_SESSION['ip'], $_SESSION['expires_on']);
    }

    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || (Session::$disableSessionProtection == false
                && $_SESSION['ip']!=Session::_allIPs())
            || time()>=$_SESSION['expires_on']) {
            Session::logout();

            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        if (time()+Session::$inactivityTimeout > $_SESSION['expires_on']) {
            $_SESSION['expires_on'] = time()+Session::$inactivityTimeout;
        }

        return true;
    }

    public static function getToken($salt = '')
    {
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('', true).'_'.mt_rand().$salt);
        $_SESSION['tokens'][$rnd]=1;

        return $rnd;
    }

    public static function isToken($token)
    {
        if (isset($_SESSION['tokens'][$token])) {
            unset($_SESSION['tokens'][$token]); // Token is used: destroy it.

            return true; // Token is ok.
        }

        return false; // Wrong token, or already used.
    }

    public static function banLoginFailed()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];

        if (!isset($gb['FAILURES'][$ip])) {
            $gb['FAILURES'][$ip] = 0;
        }
        $gb['FAILURES'][$ip]++;
        if ($gb['FAILURES'][$ip] > (self::$banAfter-1)) {
            $gb['BANS'][$ip]= time() + self::$banDuration;
        }

        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    public static function banLoginOk()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];
        unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    public static function banInit()
    {
        if (!is_file(self::$banFile) && self::$banFile !== '') {
            file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");
        }
        if (self::$banFile !== '') {
            include self::$banFile;
        }
    }

    public static function banCanLogin()
    {

        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];
        if (isset($gb['BANS'][$ip])) {
            // User is banned. Check if the ban has expired:
            if ($gb['BANS'][$ip] <= time()) {
                // Ban expired, user can try to login again.
                unset($gb['FAILURES'][$ip]);
                unset($gb['BANS'][$ip]);
                file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
                return true; // Ban has expired, user can login.
            }
            return false; // User is banned.
        }
        return true; // User is not banned.
    }
}

class Star extends Feed
{
    public $starItemFile;

    public function __construct($starFile, $starItemFile, $kfc)
    {
        parent::__construct($starFile, '', $kfc);

        $this->starItemFile = $starItemFile;
        if (is_file($this->starItemFile)) {
            include_once $this->starItemFile;
        }
    }


    public function initData()
    {
        $data = array();
        
        $data['feeds'] = array();
        $data['items'] = array();
        $GLOBALS['starredItems'] = array();

        $this->setData($data);
    }

    public function markItem($itemHash, $starred, $feed = false, $item = false) {
        $save = false;
        $feeds = $this->getFeeds();
        $feedHash = substr($itemHash, 0, 6);
        $items = $this->getItems();

        if (isset($items[$itemHash]) && $starred === 0) {
            $save = true;
            unset($items[$itemHash]);
            unset($GLOBALS['starredItems'][$itemHash]);
            if (isset($feeds[$feedHash])){
                unset($feeds[$feedHash]['items'][$itemHash]);
                $feeds[$feedHash]['nbAll']--;
                if ($feeds[$feedHash]['nbAll'] <= 0) {
                    unset($feeds[$feedHash]);
                }
            }
        } else if (!isset($items[$itemHash]) && $starred === 1) {
            // didn't exists, want to star it
            $save = true;

            $items[$itemHash] = time();

            if (!isset($feeds[$feedHash])){
                $feed['nbAll'] = 0;
                $feed['items'] = array();
                // remove useless feed information
                unset($feed['timeUpdate']);
                unset($feed['nbUnread']);
                unset($feed['lastUpdate']);
                unset($feed['foldersHash']);
                unset($feed['error']);
                $feeds[$feedHash] = $feed;
            }
            
            $feeds[$feedHash]['items'][$itemHash] = $item;
            $feeds[$feedHash]['nbAll']++;

            $GLOBALS['starredItems'][$itemHash] = 1;
        }

        if ($save) {
            arsort($items);
            $this->setItems($items);
            $this->setFeeds($feeds);
        }

        return $save;
    }
    
    public function writeStarredItems()
    {
        if ($this->kfc->isLogged()) {
            $data = array('starredItems');

            $out = '<?php '."\n";
            foreach ($data as $key) {
                $out .= '$GLOBALS[\''.$key.'\'] = '.var_export($GLOBALS[$key],true).';'."\n";
            }
            
            
            if (!@file_put_contents($this->starItemFile, $out)) {
                die("Can't write to ".$this->starItemFile." check permissions");
            }
        }
    }

    public function writeData()
    {
        parent::writeData();
        $this->writeStarredItems();
    }
}

Plugin::init();


if (!class_exists('Intl_fr_FR')) {
    class Intl_fr_FR {
        public static function init(&$messages) {
            $messages['fr_FR'] = unserialize(gzinflate(base64_decode("
vVzbctxGkn3fr6hh2CYVwVuTbF5ash0aXSzNiDJDpOT100Y1urobIhqAC0CTlMMfMH+xb2vNwz7tH/DHNk/WBSgATVL27L5ITaAqKysr75kFOdrbOxn9Wox2R2trj+VoMBj9Go92H9sH8WhAP/cHo7UznX1UUbn1erL1QekiztKR+Lt+fX4upkpN/g1D92joHk07+/Fi65lWsqRBW89lqUaC3+/T+8EJ3m+9U8u4aL0+wOvj0dobWZRbF1qmRSLLTNu3Q7w9xNt0VsmZ2rpQcmHfHWLhk/rdSLyk6bf/JeNCbOBnpB7xwCO7xOnr0xf1Ngbbu/z2mN4eEP7PsrRUKeFwkxOoUl2XO3ki4/SxiOZSF6r8tiqnW8c85wTk2WvMAd5TpbdepFE2idPZSByP45LHDkDWAxp8llRaJlsvM70oRiLN+c/i273Hwvz8diMV34nBo8c07TeaQtt+JlORZqWIQFclvi7EJNZ0Hpm+2SS0VHQpcqUXcYEtFXyS7iCHNP31Is/o1ThRYqIIyO1npQX+wFP6+XWxKZa3n3U8jdUnekHgdRaXBa9/uFevn+uspGUDBMRVXM7F9ryUUaSK4j58jodtfAD09vOsjZKQSxWJKhXTOJrjUWOJlcgSA6/9pLN0JsrsUqXBynt09H9TZZZi0eL2c1TpmBYWcbqUSTxRBsA+MVI2i2lZGSdq8pcQxMFo7fYf2CBgJFJEWZqqa9qk+AtPPxqtncezlGAG806YRcxIHgdBeTYnhlXiJqu0yGVRXGV6Eq526AZpscxKrcSCzgAUo9EGXcLnlUryYBo9e+q2QxDe5xPimWDEgI70NC6UuP1P8ZGWNzs/ZtakrRdiIVMSpAWxdDiP9vGDKiDZhEYBfsVwTzja4zSeEQ+XvM/mzO5bzNkjel3MFR1xwoQQkjZZ6puYD1BUeZLJiSA5Jh4Zy3FyI8bxDOQoiSziai5L+kWvr9S4UHpJLyJ6Di7JS7HxdfFoW5wlStJOHahUFAuZJBg5r9LLYjtEc0jc+UZ5lvulUkT5qhDEdfKGHkzWVbrM8IvIQJhmOb1PqkWcquqazkULLfM80yVIGykGQFtj5HDMqiotegTCIPiCQX/CuZJgTiqSK3BWSexn5RX/6pTw2XZnCgHSrbMZuMcN+oIVJhMhRaquWFOH/EWH8pTOH7hUEPBqqWQlpkl17Y+0KKXWighXqkXRYQapyziCCJIcYI+3n2tmOL9z5tMVMwnjF5M43NoxhI4eum3tM6fysUaWp6wYFXMwUBKnl3SEugjBHBCFPqgKZP1UT3SilcQqNbJFFJxZZUByUqWkc3t3cdjYf0oSkVRWYdIefmZs4kVORyjTCZiOeGIjo4dllceTR3waAqAJgxgArmqcJiFXHhE1PzCSFqIqRVGRgh0rAllZkOoRcVpUgs1oGzhE5tEx9uUg3342LHRsTFacVqpHT/FzoxTAIrWdJ/EhfkiSrnxDVb1uvAQK9TwGhUNPEv676OiGCwgZCFkzH6mGF4u8vBHEoDmJDWkxGc4jOj8nrUrWrBBLIoDlPRLh5yTucURqb9LD9aR6X4I6k9vPH0lKc6JKwZruN3Oo79NLOo2UZ85lMe+oTp4dp9D9lWFawuQ1GIQU542Yk+ESY0VkhwEkFAoaC5mm06i6yviQCPOcduB5iZTEkvARt/9klS/TXyqZlmTmVF4Rp5BuYHVw+zvetrU4YfKyveMDg7I7hbekC3rYmej91miAa4+LA3kRL1pIg8bwI5xGImkvq5bnAbGlx97gnVdsvqdVQorcUKJ1NIPQLkEhVvAHvIF6mkBkMHurzLa6hg0sYY41MG2061dlmZMcEF/CkWGL32GLZ3g9x0DIc9MrgGOaRYa1MX+aVemk4xq8U8YrMqNS2Idq6YgEGBdZhgO9IcE3I4sODhewKbAFNSxzDHDDX2hNKuRqTrwFcwYzCe+LJESQN5MEwKAlabwxO+mEWAiWCD6sseyiIF/VTiPwQxr+EmaYPC8YJnElC9IyjRMjG4w/1GQkvp6wki1EzBandYxHx4ERdQCluP0MfyvKNDZmkCi1ZCfMwlSEUgbe55EGujcqkOwARZJO0q5ku6zA4t2U3GpZbotn7IUq64dvwquAS6HVIiPxhA2GTEE3442ckYe/Ld5m5E6AqLLeWstDODzq3VyqzL6KPCOQ5KCBAeG8Mjqske0Mu/1t8aF2YtcZTzI77FZFUsuohHyTq0uKPtckfsbVdXiTBYD0Ga9kW7yDkpctqm17Peo02r+fvhEKLNSROorvxsnt7wvFY5q67dgoFPH+3ZuOv0HPxKThMMDDhr9RKLZEpHyzhHRVAcJ39DBCIed9sBdvPP8CrnliGD+lV02H5MQ4B11Yg6F1EUwQURuRw8ABYmw6dqvHBbK4eCcAMP6QC7VP79+TQhtn2SW5AJcJHRwRQ652yoa03PsyTkgN8l6aM3PoRHnHcic0+bmWM+MVsx9Eq7Fr5ODgMLJkLPWmIFWi49m83IrIWF6STWBxiOZZRhj/1Y4XFwD1hkBtb4eicEKn/UMCE6fhoxoHCjrFOFRjuH8IAwscJHxZclWm8va/SzpsQh6LmtBNsF2jxeAt55CCovIg/Rn8mEz6wyR2J9MIy3fiI0yEveuPr3a9yevONFF3pEKleoSl0ipxvIF4D+Y+XbXG/gFG0BozqSccC6SrVkQw1PC1FqSRSBKNAmn7W1Dur+t3CJBIaONcsiR1fC/IQQN0GZeJ6tDwIsaxdSdDPzQmI3UzJv4sb0Jikgh+cK9IC/UgQfs7q8Y49uabFhaNlXIM/qUy1Dk+wGGR/i5KHbN22BZP05sMilfi5Cfx9MY7ukyVTcEMTLq4duE3reNh/NA2Sw9gxZ9WUZWqcKW369YcUEQXmzCOV4RCN2mA1rKENlT9gjRqYl1b51TRe1WC1M7LcX4vY2N9pTOTaSEqrKAVBjVpZZIot58NqXehiWvqmLyJ2TMLOdNCjGkbWUqmHaH3v5CKh8QxPRQjLCjAZZ1+bQOUsnZ7F8jXFYpkix0DDrtzyMqnh5C6SeCa7veSmpMeOl5iL6uY8qhF6HjpqDygM/ixSb8VdMav/xM6k3Y776XY3aRG/Pj/TWhiifO5lDqJu67EgXUlFAJ4DHGex485a7dRO0i1z8XIK5avfiVH9rcRk40NX8fMmxHkaLpIP1m3+/BC99WvrBkdmK6ahINTDyqdwgwhsZ/61a/LWALQlJwJVuXZVHyTlI+B2zez8jEzBR7MqnhiHgBYPJ0qjWMxAJAaUBT/JTDbI/EEAcx39CKY+WSHH7c02aBGooiN4VkAWYMO4x3gQ0wRoFPADwc+5Kayz2kxWkoS5XtRwfntAwPyBQmDJ6RLs3T2HUuLXMo4keNEPdmxj00O+SO9KCId56UD7x1JpOH7tgmHp71GQyJkaxlOKXeXAXTV9DtvPzc3AlKqa8mpF0bUcmnIlnt7GKVMygfr2FGOSfdC4+biO3LCNlg3kDE3QRVkaUJ0BT9w0p84nQJRmrDp98j8GZmqA3hEUrh5s4g/KaNnWuT0BHjU8pqP65gVceJGrRVyJ8Qck23yzwWplBhuq+ENBDkGN+IQCtJq7BJlcas4nqn1TlE6TInYDR3UOpcmvpxNJNl0sBGej3Z2DJjtMtv5vslFSUKKqiCvd67Eq4uLs/949+Lli3cv3m3WKjopMlGRg+sApplWJHNa6RpQaYa8ujh9M4RpJS+25e4MhgcPw4nQgd6UkMAQo6biJdMtLS2sIr0LPT6cygcJklHUsQn/GGmTuCEBfB4XEAGiNddi6Fyyy1iJefxRRpcIdW1Jp+1gwrQ8p0OlQDRe+kXsSD5dzd7sMktYb1vwHL7yEiZmPzpCCQOROEckpA1xDjMFzVJw8YTFO5sSsyAeMQO0eH2GGEnDmEZcASnMmNAAHnO+Mppbg4WEMKk6puvtPxFmFJlJpRGH2sVoS4hDeBi2IHkRhQXNQm5O7R+wg28so8kAtZI2iEpTrWYxHY81kK3RvxntEHgRivV8pFrA4COTMBmlG/U680gEncrreFEtRFotxrQm2Rbr0d10HZh99pwXY04mmmmT9TrbKHUdPx5wuET4WbeD5GBM/8pLhVCE/nK6qBuRDInZ3hKZwQg0Ex50MzlpeNakNIlVSReAixZ5qXyR0+1pohJ5Q+uWV8ihThvobMTIqaYU/hahJoMiI25NZOy3qAyHyhCLiXVLxIZqgvrNZgIqzhBJBPvXpXECsrwrG0PngbDxJ65CAYLMYoZoDEEsMrZw+Ux0h9W9i0CxLbmbNt28zyls1vKB88cZPo9DGIEe4ojqaLl2whprRLRXJPweigfq3rx5xiKqNLsgD8AG1dxTg0pj/6zHuth4RB+KVs+ZcHqg70wO/sCZSJdt4CXVA48k5/JQ0wnZXX0kTfNnSsa8pCXJg7F66AF1cBvs7q48of7w9M+giQD+LVGOy1RYg2T3RoVlOKTMzmTBqVk6Igy9/YyxLqpyhz4nRdN31NCR9VFHkvR/c2vmFEGv5ErekA2YZ1euemUjM7KbrdIggTw1Nq3MKmiKuhplclq0XcxSXoN4ffnULh1JJMkZaV6ND2VXNOqHocoaIsHUwt0IjlsYgTMcrV13NrbM6IMeJtM0i+gE76WTGdam0/DIc7sM9vGxWuRQ/47VWBfyluLSmeRQL3FliRwBGNs8kZGRhPbmAlkP1VRd57YLGBIftUnsUIOPF6CXZ0XcocIBLHgDqYcgVJebJhMxlcuYzF4fhcFlDaWzzqlZwyFmkrH9w1qlyAZAFlibGYdogb9gyZlHd7oVOtb8Zg1D26SxEPcb3P6Pybzdybg7YXHvKNzlFyO16xLg/xJ04EX8JIkJ0Dr1GrRE7Qo1IWGdbPId7SLbMs+LPCu3SY/thMaAPKSnJWIjHM3IucquEyOO1L3QrM5FhowYngMLo2k8O6S25YD7TkLRpuU5mxVQhAZCmIsyiy7hhSLHbXxQ2x3iWzbOtOJmlT6ZDswcStd1Qc/HqpbXcguGD227w7hvm/M9PwWnMTg+qrFxosZa33ibpFDHFEbMNKqh26ZSMEOLGkmpSudIn4siBycVFESa6inrEAqocMS2BgJgXHtTfOYUeRW2ZkFqgUPZBSwF9/ykBA2q3AQSE7NT1rCkG1gl1Phtt+zOifGt7Y7vs3nb4pnCM6UXCFfWJYW/SZxpE2xomccTl+huFFVZnSCC0OyATNZdmSZxi5mdEldGAL+UdSA3VrKi1XP0VTFK2L+pELARdrFNispIQVEjDq3MVtvvbV9XsCwBupuyUJwy6Us5DrUlvHjgr2uWMFVZlt56MxSHJqp2YkMNfe8yEPMfeZE+43ffgnvO9NmwoJXt6GzoVJU+AGiv18o8tF1A+cBlhtYFVKWr/T54tXbZB1mTVeHh/rATHlZmRk+5BYHpzzbrkWkkg1HwM/VvZdwzFmEIDWBsi3Mkib0ZxfBEIfhV3IcTytwVDDemWlBc1sxzReFkE2a3MPChkfYgtEyVFyG5LXSbCJpkzXiFlhESh+PrdBKbRHQDU7LuRYyCOABRFMg1SbQEmbCTu/DWPUiRrhOmUvM4Pq9gCdf08iFWVyH16VlV1xXxHi54x5tE/vdDFpuNcJK48J6tm6Sucwh3t9TgJ9oMmrWLpgofckOzrwdrvnK+Zw9OMBzsbuoVriwAnDtXuQ/A0LnIqyAccndG2TKG9qlW3rzxKtDWXW+iuUhHsdUVVwBY2ZeHHL1HtKkXmz16iOB1uxnAPHSYHtqFuGmgp51wf9hah0vQ1tch/UcyvPA9BA4YKcN+YIchsNR1YvVC23VdvSBjt44LJzQsx/imTDutrxNiyIoyrOGEjRCDen6nGrnXN7tmrj0OQn003WmkPG1VTD3VzCxilr6ZcHbMbnunn9jpZrf9EI7qtRvd5yGcIwcH+rkXykEAhc8sBHFQ798wbqeWG1KgGegdh1Tomz/spUMDCHfWhbTogWMyiyuo0UTppEWRPpyOV9GkAejYNvR02HjPbggVh7LBf6b9p8u9g+OgAajJtrDmzcyrnMHzvNPMHhyHZrZpZXn+qh5X2wfaaRlpVAcRVbw2ltSY0EzMMmCyjKHwEAj3p9qQ4Tq3HmCRVXNJI1qF3jqpCzcXz22vmE3S+H7Os3aayD/7Agw7uabD/XsxxJwAPdXEz+n4fddOO77pyWgdNbrHkb3OPeLDDuK20N6TJu/AxUWZLvZ1RZwLqGP26WXv8ujcai1v+ryCPCJFlLiCwtl0w4vhQZz0YUFu8JjEAfk3qNZ+dYembp2lYeG+oy+DWxhg7EjbtnpwvfMXuUDGFdBvppmOlC2HcmYAf1ufOHTv0Mnc9O58YNMHiTfCT3Q7S7/tVdaFd74VRVQ3Yk5vw6orB3VMk9D6lEhJGP9hTrG1cr0CyMXYQ4rspSbs9WqmSlMjRAc3OkLzec70BbtTKKPCZZH3Pq/DMbthuJJ+0wakuzjCnj/7TTNTe7ewFS/k+N8ieXLCSHqRK7KkMg0PzvnO9KVvabWXwnBq5lAAkXthmcnQN9+4rQW3UsacYOm2Pp3HTLF6vVBSU3gOqW3NNBXgeof1zbLGna0GRs7HdlaF8TJOLqRl5IOhC2QICoVmeZMGAIdy9NU6+n0U3oh0ZeMeCAsqT2CKj9oBHXHczPRg3n1NDQ7Nh55bZvaeF7zld8pEVbbJtWOPLnTcam31t1dWNcnWzzrO/vDk/nZZpJW80xg+NxI+ANYcAP6B1V+kiVr+idX5al0zndhpAnrTE5bg3gDj1XUSjq2TEDQMe3/zfVr0z/NeI6LGzlROoNHEUt1FD/DeeRCwrqT5MfMeVAvJKbIjzjhMzCpw68KVvg+1KgeFVaI+bbG6WVLcavr36/XXKfBQC3OfshcV8b3f3Mv7m0t3g+bSRm9p0O2No3m5op1037eTVmEUwBPQY/6QfnKAqdHtzDjxM7jkHMw8tDOJHpxq6VxINLdu3Lt+PHslG1dfHPe3yfG2v8sc2uJtX2u5w3JlO8CgJ99TrzgwN3P6bhUh+XOhFnnhCuHoSoiRWW2GhGjhXkOSam1TrC3k9RpyPrLhLtkSOaYXJgFrhk3UFD1kdCpPpJgT9t+ufW908Np3gf/xZEe2GtN2u2tWJs9nvCvlFyX+hAZGZxIhbRcmYsS2z7y7dLtt0axuSfVmxQUsqPrnq29UDWqF0Nsb2lQDLR4ysVaPeBw2xKPJEDjvN42Um9cSnaD1TSfJ5dWB9wb/rm7GmdQTlEB1GVVl10a9I++cJkdxIaJELpsZBppNO5qp9j0nevUG17Xhu0DbkKdFprRwFzTOc1TZLtVNVyHwQPIXMMIPn8fTsjv80A9fyI9VEVWutxJ2F3cWKNbR2VVnIpwYO3Ga3P6O/4EmTHedgHqjpl82fybxxIusiU1SItesx8cmqr71r5zX08piDdkEzWZJq4K5gcz5Jh0FbmDwk9iW4OhorsKel6MhStzkDqRsAdo11Q0k8WO9SUyrF8EI54ouK3uAdYYSefnTZocB4+Bbkqcu65Zpn3+bdtMhwxPfcnBHE0gR20wA5N/+QqNqVfdW/yFUTLc1KhBu5gZ5+NMqCWjJo+zEKWctW72RB3t/ahOIak0ZpedoECO4c+DL76V2aRHMoxlTONDuPpg5oEdeAH7IoBj6swTcMsP321d21fAtfgbhA4wOGNM/0QHTCAf6sOnE1HvDJphVHSMtZLpQTnqgNCMT34vRokzNCnHJzWMrROlweBfRzPUkruqRQImNfvl55PvYemj7YERO7iP7lyCzxx30DUFwGqW38ndoSnJxnzK5o/g3XLGG+/sqTidZWEeBS3z3UrShqUpNt6DpQK27MfmKf3O1Dv+fmSv8PdBN0vGgkVC11yz8TzHV2cKDN10QeuXr1YlldnOs/uhUMjb7qraVy5EaOpA+6R1UJ2VDVeTl8RSJyhXGadAyTiat2RLlV9lC9QjhYY8QTtbJf6hUnPjGVPtRk2WjNgYC9pa7ILGtOhnX7/q7x30kb6oy2rRahf0pfQ47Lue70gpnffqLYDtBkH5gv0Phj9pEaCDaBjHDpjt6QgMrt7pR9xvJaNmIxOp+ug3kpzb5MA1aQKFWIbbM8kcWR4m1XYm5F4e4g8QJMgXc9+F7KtBsQL9fDuE1QcQXmaH9mDypok32Q75bYDojdKO5grlVWq8RwJzA50mFb2/wgVj2Jc+/8Gkhy5wqyQ2oDe4ox8+QAPsrGBXcshERZZSbZM1PmBHltFAn1repoMgn/tuRhq8Unb06uyMDixHhejTeHzuyr+SrV8kEbdfct09b5lNAv1Ix2tkRujAXe1vdLZjOudeJIsWKXCSpasiWOwGfGHCQfPvXzzbryd+twId2Eii6qkCZ5L5lD92yOsY1h08gNC/Lnwdyl8q529skRlnF96Iy8N+1IUu5SDiD2fHNzYAwx4jRPmr50U1tZTdRK2+Md4lWpDw/cV/8qHNOF+YLPNxQjtyHzVCiA2up9JVGzkFdkxIBobopIvDs7T8iLYtmhogn+FIMcVaZdb7eNKfo2FWp+Ys2Yep5tGa/UeO/+NGxPvQsibW/yNxTngNyJB5u0IH50k/Y1jxasx/nsSsxpM4wgpThom6NDmxkSIrRmv2QBpKfz/i2ObH+NJGzOu9ocr2J/Yhbi7tPeBpzWGEr01rmyKdw7BvNs7iI/ad8AMM2Pw3t57w6p/sWt+HWLVOWrv3umHuTe+6YIxroXC4/9h+9ueuWrh1CXNBzsfonlUSwt0SGVSCIon+NVbokg2huxreLjif8+ZUbCjxmqWlz3rB1ApPjRgrP3LvGjQtkkNvXJ46gq4uS3Qp7TUZspK6XnQ0mFq64kQjlT/6qCMN85ATpwn1Go+UHEWj/pQrHIafsdYQMe+o8EXzTLGt9bwY1v4ancWw/s5ZVZeeYntu7Pv5ba/BsrFvS/xmA/foza0nPN9agmF5c36OYzACCoPoU0z63Ua6ItRqfw+oEWNxU3RfnYVNP+4K75lK9tdOz3ujJLdSdsm+nhGGb/+JRrvQX1WjRMf/gL2N1lMD79Au/gWUEjSQozPPwpgw7GtcCfaydU3mnSOhiPlOpNecHG8m9ugzaSSe+7K1r0tzf/hc=")));
        }
    }
    Intl::addLang('fr_FR', 'Français (France)', 'flag-fr');
}


// Check if php version is correct
MyTool::initPHP();
// Initialize Session
Session::init('kriss', BAN_FILE);

// Initialize internationalization
Intl::addLang('en_GB', 'English (Great Britain)', 'flag-gb');
Intl::addLang('en_US', 'English (America)', 'flag-us');
Intl::init();

MyTool::$opts = array(
    'http' => array(
        'timeout' => 4,
        'user_agent' => 'KrISS feed agent '.FEED_VERSION.' by Tontof.net http://tontof.net/kriss/feed',
        )
    );

$ref = MyTool::getUrl();
$referer = (empty($_SERVER['HTTP_REFERER'])?'?':$_SERVER['HTTP_REFERER']);
if (substr($referer, 0, strlen($ref)) !== $ref) {
    $referer = $ref;
}

if (isset($_GET['file'])) {
    $gmtTime = gmdate('D, d M Y H:i:s', filemtime(__FILE__)) . ' GMT';
    $etag = '"'.md5($gmtTime).'"';

    header("Cache-Control:");
    header("Pragma:");

    header("Last-Modified: $gmtTime");
    header("ETag: $etag");

    $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : false;
    if (($if_none_match && $if_none_match == $etag) ||
        ($if_modified_since && $if_modified_since == $gmtTime)) {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }
    
    if ($_GET['file'] == 'favicon.ico') {
        header('Content-Type: image/vnd.microsoft.icon');
        $favicon = '
AAABAAIAEBAAAAEACABoBQAAJgAAABAQAgABAAEAsAAAAI4FAAAoAAAAEAAAACAAAAABAAgAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACZd2wAoXtsAK2DbAClh3QApZd8AK2ffAC1y5AAudOUA
LHTmAC525gAueegAL3rpADh86AAvfusAMn/rAE5/5ABYgeIAMILtADKC7QAzg+0AN4XtADGG7wAy
iPAANYnwAEaJ7AA0i/EAUozqAECO8AA1j/QANpDzAD2Q8QA2kPQAN5D0ADWS9QA2k/UARZPxAFKT
7gAylPYANZX3ADWX+AA4l/cAQ5f0ADmY9wA3mPgAOJn4ADmZ+ABzmOgAPpn3ADma+QA4m/kAOZv5
ADmc+QA6nPkAOZz6AE6d9ABOnfUARp73AGug7gBGovoAZKPyAFGm+QBUpvgAWqn4AFeq+gBtq/QA
XK36AG2u9gBlr/kAabD5AGiz+gBws/gAhLT0AIi29ACatu8AnLrxAJS89ACFvfkAi8H5AK/D8gCN
xPsApcX1AI3G/ACnyvcAncz7ALnQ9gCv1/wAttj8ALvb/AC+2/sAw9z6ALzd/QDI4/4A1Of8ANXn
/ADT6P0A4ez8AODv/gDi8P4A5/H9AOfz/gDz+f8A9Pn/APj7/wD7/P8A/P3/AP3+/wD+//8A////
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMSkdFxMAAAAABgQBAgMAADE2P0MA
AAAAAAAQLxEDAAAsQGFpAAAAAAAAS2xPAwAAJ0RmbFcAAAAADVVsSgQAACMwUFZCPgAASRlgAAAF
AAAeIiYoQFxsXSRIAAAAAAAAGipHVGJsZEU4AAAAAAAAABZBZ2xqX0Y7WAAAAAAAAAASPF5ZTTk9
W2tmAAAAAAAADxUcHzdOAABlUisAABQAAAwlU1pjAAAAADUyKSAYAAAJOmhsAAAAAAAAMzQpIQAA
BxtRTAAAAAAAAC0zNikAAAgICgsOAAAAADEpLjExAAAAAAAAAAAAAAAAAAAAAAABgAAAAYAAAAPA
AAADwAAAAYAAAAAAAAAADAAAAB8AAAAfAAAADAAAAAAAAAGAAAADwAAAA8AAAAGAAAABgAAAKAAA
ABAAAAAgAAAAAQABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP//AAD/
/wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//AAD//wAA//8AAP//
AAA=
';
        echo base64_decode($favicon);
    } else if ($_GET['file'] == 'style.css') {
        header('Content-type: text/css');
?>
article,
footer,
header,
nav,
section {
  display: block;
}

html, button {
  font-size: 100%;
}

body {
  font-family: Helvetica, Arial, sans-serif;
}

html, body, .full-height {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: auto;
}

img {
  max-width: 100%;
  height: auto;
  vertical-align: middle;
  border: 0;
}

a {
  color: #666;
  text-decoration: none;
}

a:hover {
  color: #000;
  text-decoration: underline;
}

small {
  font-size: 85%;
}

strong {
  font-weight: bold;
}

em {
  font-style: italic;
}

cite {
  font-style: normal;
}

ol,
ul {
  padding: 0;
  margin: 0;
  list-style: none;
}

code {
  font-family: "Courier New", monospace;
  font-size: 12px;
  color: #333333;
  border-radius: 3px;
  padding: 2px 4px;
  color: #d14;
  background-color: #f7f7f9;
  border: 1px solid #e1e1e8;
}

.table {
  width: 100%;
}

.table th,
.table td {
  padding: 8px;
  line-height: 20px;
  text-align: left;
  vertical-align: top;
  border-top: 1px solid #dddddd;
}

.table th {
  font-weight: bold;
}

.table-striped tbody > tr:nth-child(odd) > td,
.table-striped tbody > tr:nth-child(odd) > th {
  background-color: #f9f9f9;
}

.muted {
  color: #999999;
}

.text-warning {
  color: #c09853;
}

.text-error {
  color: #b94a48;
}

.text-info {
  color: #3a87ad;
}

.text-success {
  color: #468847;
}

.text-center {
  text-align: center;
}

.collapse {
  position: relative;
  height: 0;
  overflow: hidden;
}

.collapse.in {
  height: auto;
}

.row-fluid .span12 {
  width: 99%;
}

.row-fluid .span9 {
  width: 75%;
}

.row-fluid .span6 {
  width: 49%;
}

.row-fluid .span4 {
  width: 33%;
}

.row-fluid .span3 {
  width: 24%;
}

.row-fluid .offset4 {
  margin-left: 33%;
}

.row-fluid .offset3 {
  margin-left: 24%;
}

.container {
  margin: auto;
  padding: 10px;
}

.well {
  border: 1px solid black;
  border-radius: 10px;
  padding: 10px;
  margin-bottom: 10px;
}

/**
 * Form
 */
.form-horizontal .control-label {
  display: block;
  float: left;
  width: 20%;
  padding-top: 5px;
  text-align: right;
}

.form-horizontal .controls {
  margin-left: 22%;
  width: 78%;
}

label {
  display: block;
}

input[type="text"],
input[type="password"] {
  width: 90%;
  position: relative;
  display: inline-block;
  line-height: 20px;
  height: 20px;
  padding: 5px;
  margin-left: -1px;
  border: 1px solid #444;
  vertical-align: middle;
  margin-bottom: 0;
  border-radius: 0;
}

input[readonly="readonly"]{
  opacity: 0.4;
}


.input-mini {
  width: 24px !important;
}

button::-moz-focus-inner,
input::-moz-focus-inner {
  padding: 0;
  border: 0;
}

.control-group {
  clear: both;
  margin-bottom: 10px;
}

.help-block {
  color: #666;
  display: block;
  font-size: 90%;
  margin-bottom: 10px;
}

/**
 * Menu
 */
.navbar {
  background-color: #fafafa;
  border: 1px solid #444;
  border-radius: 4px;
}

.nav-collapse.collapse {
  height: auto;
  overflow: visible;
  float: left;
}

.navbar .brand {
  padding: 5px;
  font-size: 18px;
  display: block;
  float: left;
}

.navbar .btn-navbar {
  padding: 5px !important;
  margin: 5px !important;
  font-size: 18px;
  display: none;
  float: right;
}

.navbar .nav {
  display: block;
  float: left;
}

.navbar .nav > li {
  float: left;
}

.navbar .nav > li > a {
  display: block;
  padding: 10px 15px;
}

.menu-ico {
  display: none;
}

/**
 * Paging
 */
#paging-up,
#paging-down {
  margin: 10px;
}

.input-append,
.input-prepend,
.btn-group {
  position: relative;
  display: inline-block;
  font-size: 0;
  white-space: nowrap;
}

.btn, button {
  display: inline-block;
  line-height: 20px;
  font-size: 14px;
  text-align: center;
  border: 1px solid #444;
  background-color: #ddd;
  vertical-align: middle;
  padding: 5px;
  margin: 0;
  margin-left: -1px;
  min-width: 20px;
  border-radius: 0;
}

.btn-break {
  display: none;
}

ul.inline > li,
ol.inline > li {
  float: left;
  padding-right: 5px;
  padding-left: 5px;
}

/**
 * List of feeds
 */
li.feed {
  margin-left: 4px;
}

li.feed:hover {
  background-color: #eee;
}

li.feed.has-unread {
  font-weight: bold;
}

.item-favicon {
  float: left;
  margin-right: 2px;
}

li.folder > h4 {
  border: 1px solid #444;
  border-radius: 4px;
  padding: 2px;
  margin: 0;
}

li.folder > h5 {
  border: 1px solid #444;
  border-radius: 4px;
  padding: 2px;
  margin: 2px 0;
}

li.folder > h4:hover,
li.folder > h5:hover {
  background-color: #eee;
}

.mark-as {
  float: right;
}

/**
 * List of items
 */
li.item-list {
  border-bottom: 1px solid #ddd;
}

.current, .current-feed, .current-folder > h5, .current-folder > h4 {
  background-color: #eee;
}

.current .item-title {
  font-weight: bold;
}

dl {
  margin: 0 !important;
}

dt,
dd {
  line-height: 20px;
}

.dl-horizontal dt {
  float: left;
  width: 18%;
  overflow: hidden;
  clear: left;
  text-align: right;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dl-horizontal dd {
  margin-left: 20%;
}

.item-info {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-link {
  color: #000;
}

.read {
  opacity: 0.4;
}

.autohide-feed,.autohide-folder {
  display: none;
}

#main-container {
  float: right;
}

#minor-container {
  margin-left: 0;
}

.clear {
  clear: both;
}

#menu-toggle {
  color: #000 !important;
}

#status {
  font-size: 85%;
}

.item-toggle-plus {
  float: right;
}

.item-content {
  margin: 10px 0;
}

.item-info-end {
  float: right;
}

.folder-toggle:focus, .folder-toggle:hover, .folder-toggle:active, .item-toggle:focus, .item-toggle:hover, .item-toggle:active, .mark-as:hover, .mark-as:active {
  text-decoration: none;
}

.folder-toggle-open, .item-toggle-open, .item-close {
  display: none;
}

.folder-toggle-close, .item-toggle-close, .item-open {
  display: block;
}

.label,
.badge {
  padding: 2px 4px;
  font-size: 11px;
  line-height: 14px;
  font-weight: bold;
  color: #ffffff;
  background-color: #999999;
  border-radius: 3px;
}

.label-expanded {
  padding: 6px;
}

/* Large desktop */
@media (min-width: 1200px) {

}

/* Portrait tablet to landscape and desktop */
@media (min-width: 768px) and (max-width: 979px) {

  .hidden-desktop {
    display: inherit !important;
  }
  .visible-desktop {
    display: none !important ;
  }
  .visible-tablet {
    display: inherit !important;
  }
  .hidden-tablet {
    display: none !important;
  }
}

@media (min-width: 768px) {
  .nav-collapse.collapse {
    height: auto !important;
    overflow: visible !important;
  }
}

/* Landscape phone to portrait tablet */
@media (max-width: 767px) {
  input[type="text"],
  input[type="password"] {
    padding: 10px 0 !important;
  }

  .btn {
    padding: 10px !important;
  }

  .label {
    padding: 10px !important;
    border-radius: 10px !important;
  }

  .item-top > .label,
  .item-shaarli > .label,
  .item-starred > .label,
  .item-mark-as > .label {
    display: block;
    float: left;
    margin: 5px;
  }

  .item-link {
    clear: both;
  }

  li.feed, li.folder > h4,  li.folder > h5{
    padding: 10px 0;
  }
  .row-fluid .span12,
  .row-fluid .span9,
  .row-fluid .span6,
  .row-fluid .span4,
  .row-fluid .span3 {
    width: 99%;
  }
  .row-fluid .offset4,
  .row-fluid .offset3 {
    margin-left: 0;
  }

  #main-container {
    float: none;
    margin: auto;
  }

  #minor-container {
    margin: auto;
  }

  .hidden-desktop {
    display: inherit !important;
  }
  .visible-desktop {
    display: none !important;
  }
  .visible-phone {
    display: inherit !important;
  }
  .hidden-phone {
    display: none !important;
  }
  html, body, .full-height {
    height: auto;
  }

  .navbar .container {
    width: auto;
  }

  .nav-collapse.collapse {
    float: none;
    clear: both;
  }

  .nav-collapse .nav,
  .nav-collapse .nav > li {
    float: none;
  }

  .nav-collapse .nav > li > a {
    display: block;
    padding: 10px;
  }
  .nav-collapse .btn {
    font-weight: normal;
  }
  .nav-collapse .nav > li > a:hover,
  .nav-collapse .nav > li > a:focus {
    background-color: #f2f2f2;
  }

  .nav-collapse.collapse {
    height: 0;
    overflow: hidden;
  }
  .navbar .btn-navbar {
    display: block;
  }

  .dl-horizontal dt {
    float: none;
    width: auto;
    clear: none;
    text-align: left;
    padding: 10px;
  }

  .dl-horizontal dd {
    clear: both;
    padding: 10px;
    margin-left: 0;
  }
}

/* Landscape phones and down */
@media (max-width: 480px) {
  ul.inline {
    width: 100%;
  }

  ul.inline > li {
    width: 100%;
    padding: 0;
    margin: 0;
  }
  
  .btn-group {
    width: 100%;
    margin: 5px auto;
  }

  .btn-group .btn {
    width: 100%;
    padding: 10px 0 !important;
    margin: auto;
  }

  .btn-break {
    display: block;
  }

  .btn2 {
    width: 50% !important;
  }

  .btn3 {
    width: 33.333% !important;
  }

  .paging-by-page {
    width: 100%;
  }

  .paging-by-page > input {
    padding-left: 0;
    padding-right: 0;
  }

  .item-toggle-plus {
    padding: 10px;
  }

  .item-info {
    white-space: normal;
  }

  .item-description {
    display: none;
  }
}

/* feed icon inspired from peculiar by Lucian Marin - lucianmarin.com */
/* https://github.com/lucianmarin/peculiar */
.ico {
  position: relative;
  width: 16px;
  height: 16px;
  display: inline-block;
  margin-right: 4px;
  margin-left: 4px;
}
.ico-feed-dot {
  background-color: #000;
  width: 4px;
  height: 4px;
  border-radius: 3px;
  position: absolute;
  bottom: 2px;
  left: 2px;
}
.ico-feed-circle-1 {
  border: #000 2px solid;
  border-bottom-color: transparent;
  border-left-color: transparent;
  width: 6px;
  height: 6px;
  border-radius: 6px;
  position: absolute;
  bottom: 0;
  left: 0;
}
.ico-feed-circle-2 {
  border: #000 2px solid;
  border-bottom-color: transparent;
  border-left-color: transparent;
  width: 9px;
  height: 9px;
  border-radius: 4px 7px;
  position: absolute;
  bottom: 0;
  left: 0;
}
.ico-b-disc {
  background-color: #000;
  border-radius:8px;
  width: 16px;
  height: 16px;
  position: absolute;
  top:0;
  left:0;
}
.ico-w-line-h {
  background-color: #fff;
  width: 8px;
  height: 2px;
  border-radius: 1px;
  position: absolute;
  top:7px;
  left: 4px;
}
.ico-w-line-v {
  background-color: #fff;
  width: 2px;
  height: 8px;
  border-radius: 1px;
  position: absolute;
  top: 4px;
  left: 7px;
}
.ico-w-circle {
  border: #fff 2px solid;
  width: 8px;
  height: 8px;
  border-radius: 8px;
  position: absolute;
  bottom: 2px;
  left: 2px;
}

.ico-toggle-item {
  float: right;
}

.menu-ico {
  text-decoration: none !important;
}

.menu-ico:before {
  content: "";
  speak: none;
  display: none;
  text-decoration: none !important;
}

.ico-star:before {
  content: "\2605";
}

.ico-unstar:before {
  content: "\2606";
}

.ico-update:before {
  content: "\21BA";
}

.ico-add-feed:before {
  content: "\271A";
}

.ico-home:before {
  content: "\23CF";
}

.ico-help:before {
  content: "\2048";
}

.ico-edit:before {
  content: "\2318";
}

.ico-config:before {
  content: "\273F";
}

.ico-order-older:before {
  content: "\25BC";
}

.ico-order-newer:before {
  content: "\25B2";
}

.ico-login:before {
  content: "\2611";
}

.ico-logout:before {
  content: "\2612";
}

.ico-list-feeds-hide:before {
  content: "\25FB";
}

.ico-list-feeds-show:before {
  content: "\25E7";
}

.ico-list:before {
  content: "\2630 ";
}

.ico-expanded:before {
  content: "\2B12";
}

.ico-mark-as-read:before {
  content: "\25C9";
}

.ico-mark-as-unread:before {
  content: "\25CE";
}

.ico-filter-all:before {
  content: "\26C3";
}

.ico-filter-unread:before {
  content: "\26C0";
}

/**
 * flags
 * http://flag-sprites.com/en_US/
 * http://famfamfam.com/lab/icons/flags/
 */
#flags-sel + #flags {
  display: none;
}

#flags-sel:target + #flags {
  display: block;
}

#flags-sel:target #hide-flags, #flags-sel #show-flags {
  display: inline-block;
}

#flags-sel:target #show-flags, #flags-sel #hide-flags {
  display: none;
}
.flag {
  display: inline-block;
  width: 16px;
  height: 11px;
  background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAWCAYAAAChWZ5EAAAFVElEQVRIia2Vf2yW1RXHP/ftqxmDwSxM6FghGMt0Thbn2M9SFCmo+2miIAjbZE5jRubCmJUtGwQM2zCiYcSGH6EsA3GhyUTXJlNWu7oEdabggAmEho0h0Flmbd++7/Pce885++N5W374x/6gJzl57n2eJ/ec7/ec+z3OzLgcW/7MoYsO0Kj88BujERHSNEVEiDHinOOa2TehBYgpRECB/GVFL9tddR8HwNRQNaqqPsIgMDNDVTEzrnh8PRYD6j0WAuceWz08CWDQXwyIZMEGBgYws6HgzjlEhNGnT6E+xXwKY8cRgdxlB9cMuYghqkQxcrkcZoZzbmitqqhP0DRFQ4p5PzwliFERVUQUESOqYFZOQhVVGyqHphl69QHnPX44EvA+omqIKEEViUZFRUWGGMjnQNXhnMsSCB71Hhfj8DDgY0Z7ECWKEqMO1X/QRTJWzKeI95gP5IaLgcTHrPZRsySCZOjLnS9yfp8FT9EQIIbhYSBJYhY8KjEKIepQwA94mmbXMARcFOJwJFD0nhiVD4+oQCLEmBvq/kFzLuuBivFVaIw4iZgMEwPFYmTt9oMkiaeYRAqJZ+KIKrz3hCTBi+C9J0kSpv2mCeW8CgK4DZvabGrtNF7tOEZ3D4AgKjy26EYOrd2IiXDjzx/hR+tfx0KOgWJCdc0IZh1o5cTX5nH2jUukXBU0Ah4wwLN58l6sOEDPu2fx5eCDieQrx4xi7vE25jx0D13/HmDf/jOIOWqmVDL1ppHgU2xKJXd8vpoQhdobxjK9YxM0r2NDy9ucpcCWxi+xo8NYMFN59pUcC2cYv+1wLK6NNLXnkVtvxww+anqBRCutrbvJtx0vMaF+Nrfteo5rP3E1135zFr9s/AfOAW91QUhwDk6eM1aNO07Fqy/jfrKGpt0H+fW617jz5k+zo8PY1aHsaAdRZeteQwy2vORIYuSBl34G/+2Fvveh0A/FIm7hHVz/5l8zKX7nzHus7JnGSbkGWbOKFQ9/KqOz1A+lEgBPnGhk5Jxajt61hFl3PsehI30kaQoETOH5BiNVeKEhIAYtjxZJonHYQXHlSgpPPUnfls307tyJa59EafFSCg824I52nbOaKZUZ4ktt3jxIU9iz5wOfzKDzyH9ofOoodQu+TFObIpohjwFSMw6TEot/I57ZCMUiFApYkoD35JZ8i9cOd5I/+PjTTP3sSHjrOIQA3p9/qmJpSrzllmx4hGyUIkL+uon86c0CzP4FC2cYW/caf2xIqF/zIVoa+piw1vPPZUr10pS+pm0XiZOq0jv+at5r3f1/GKirQ9JAxev7yh3tskOwjIGjPWxev58v3lvPtpeF1AwNcNC6kfQI/EvgpBBHr82QpymkKZYk5H4wnxe7juBmfvsF+3pdNZ1vdPPTR77ADRME/+iPuXLrduzm6YgI+QOdgHL6ttkcm7+cBavfobb+YzS/uJeT+5dRVTWJEMJFCC9dX7gvlUpMuQI2Pr+DfJJExo+9kp2b5lJYvZL3R4xi6VUP8TvK41MVzPjKrbt4pb2NMVu2caC+g83Tl9G8JzJx+9O42+8m19KGdXdDby8UCpknCVYsgvfYhesQ0OXfZcypE+QXLa7hvq9eR/KrFfx95vdobj9N5SjBDNz1E3EaMDOqP1nJou80s+T7M5iz5D4efuAeuu+fT9e9n2Hy5Kn0f24Go3yCWXbHL52IZkaMgSSpBE4hoty9YRX5M++e45k5D/KHmpl07vwLIZaIItw/r4bWfb1YjMx9u4ff7/kzqLC7pYNxk65ixRPrOLaikZF9z9JtmbL1ldWNC5QuAfqBYtlD+d3gf/8DcCnYzK68GQMAAAAASUVORK5CYII=') no-repeat
}

.flag.flag-fr {background-position: -16px 0}
.flag.flag-gb {background-position: 0 -11px}
.flag.flag-us {background-position: -16px -11px}
<?php        
    } else if ($_GET['file'] == 'script.js') {
        header('Content-type: text/javascript');
?>
/*jshint sub:true, evil:true */

(function () {
  "use strict";
  var view = '', // data-view
      listFeeds = '', // data-list-feeds
      filter = '', // data-filter
      order = '', // data-order
      autoreadItem = '', // data-autoread-item
      autoreadPage = '', // data-autoread-page
      autohide = '', // data-autohide
      byPage = -1, // data-by-page
      shaarli = '', // data-shaarli
      redirector = '', // data-redirector
      currentHash = '', // data-current-hash
      currentPage = 1, // data-current-page
      currentNbItems = 0, // data-nb-items
      autoupdate = false, // data-autoupdate
      autofocus = false, // data-autofocus
      addFavicon = false, // data-add-favicon
      preload = false, // data-preload
      stars = false, // data-stars
      isLogged = false, // data-is-logged
      blank = false, // data-blank
      status = '',
      listUpdateFeeds = [],
      listItemsHash = [],
      currentItemHash = '',
      currentUnread = 0,
      title = '',
      cache = {},
      intlTop = 'top',
      intlShare = 'share',
      intlRead = 'read',
      intlUnread = 'unread',
      intlStar = 'star',
      intlUnstar = 'unstar',
      intlFrom = 'from';

  /**
   * trim function
   * https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/String/Trim
   */
  if(!String.prototype.trim) {
    String.prototype.trim = function () {
      return this.replace(/^\s+|\s+$/g,'');
    };
  }
  /**
   * http://javascript.info/tutorial/bubbling-and-capturing
   */
  function stopBubbling(event) {
    if(event.stopPropagation) {
      event.stopPropagation();
    }
    else {
      event.cancelBubble = true;
    }
  }

  /**
   * JSON Object
   * https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/JSON
   */
  if (!window.JSON) {
    window.JSON = {
      parse: function (sJSON) { return eval("(" + sJSON + ")"); },
      stringify: function (vContent) {
        if (vContent instanceof Object) {
          var sOutput = "";
          if (vContent.constructor === Array) {
            for (var nId = 0; nId < vContent.length; sOutput += this.stringify(vContent[nId]) + ",", nId++);
            return "[" + sOutput.substr(0, sOutput.length - 1) + "]";
          }
          if (vContent.toString !== Object.prototype.toString) { return "\"" + vContent.toString().replace(/"/g, "\\$&") + "\""; }
          for (var sProp in vContent) { sOutput += "\"" + sProp.replace(/"/g, "\\$&") + "\":" + this.stringify(vContent[sProp]) + ","; }
          return "{" + sOutput.substr(0, sOutput.length - 1) + "}";
        }
        return typeof vContent === "string" ? "\"" + vContent.replace(/"/g, "\\$&") + "\"" : String(vContent);
      }
    };
  }

  /**
   * https://developer.mozilla.org/en-US/docs/AJAX/Getting_Started
   */
  function getXHR() {
    var httpRequest = false;

    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
      httpRequest = new XMLHttpRequest();
    } else if (window.ActiveXObject) { // IE
      try {
        httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch (e) {
        try {
          httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (e2) {}
      }
    }

    return httpRequest;
  }

  /**
   * http://www.sitepoint.com/xhrrequest-and-javascript-closures/
   */
  // Constructor for generic HTTP client
  function HTTPClient() {}
  HTTPClient.prototype = {
    url: null,
    xhr: null,
    callinprogress: false,
    userhandler: null,
    init: function(url, obj) {
      this.url = url;
      this.obj = obj;
      this.xhr = new getXHR();
    },
    asyncGET: function (handler) {
      // Prevent multiple calls
      if (this.callinprogress) {
        throw "Call in progress";
      }
      this.callinprogress = true;
      this.userhandler = handler;
      // Open an async request - third argument makes it async
      this.xhr.open('GET', this.url, true);
      var self = this;
      // Assign a closure to the onreadystatechange callback
      this.xhr.onreadystatechange = function() {
        self.stateChangeCallback(self);
      };
      this.xhr.send(null);
    },
    stateChangeCallback: function(client) {
      switch (client.xhr.readyState) {
        // Request not yet made
        case 1:
        try { client.userhandler.onInit(); }
        catch (e) { /* Handler method not defined */ }
        break;
        // Contact established with server but nothing downloaded yet
        case 2:
        try {
          // Check for HTTP status 200
          if ( client.xhr.status != 200 ) {
            client.userhandler.onError(
              client.xhr.status,
              client.xhr.statusText
            );
            // Abort the request
            client.xhr.abort();
            // Call no longer in progress
            client.callinprogress = false;
          }
        }
        catch (e) { /* Handler method not defined */ }
        break;
        // Called multiple while downloading in progress
        case 3:
        // Notify user handler of download progress
        try {
          // Get the total content length
          // -useful to work out how much has been downloaded
          var contentLength;
          try {
            contentLength = client.xhr.getResponseHeader("Content-Length");
          }
          catch (e) { contentLength = NaN; }
          // Call the progress handler with what we've got
          client.userhandler.onProgress(
            client.xhr.responseText,
            contentLength
          );
        }
        catch (e) { /* Handler method not defined */ }
        break;
        // Download complete
        case 4:
        try {
          client.userhandler.onSuccess(client.xhr.responseText, client.obj);
        }
        catch (e) { /* Handler method not defined */ }
        finally { client.callinprogress = false; }
        break;
      }
    }
  };

  /**
   * Handler
   */
  var ajaxHandler = {
    onInit: function() {},
    onError: function(status, statusText) {},
    onProgress: function(responseText, length) {},
    onSuccess: function(responseText, noFocus) {
      var result = JSON.parse(responseText);

      if (result['logout'] && isLogged) {
        alert('You have been disconnected');
      }
      if (result['item']) {
        cache['item-' + result['item']['itemHash']] = result['item'];
        loadDivItem(result['item']['itemHash'], noFocus);
      }
      if (result['page']) {
        updateListItems(result['page']);
        setCurrentItem();
        if (preload) {
          preloadItems();
        }
      }
      if (result['read']) {
        markAsRead(result['read']);
      }
      if (result['unread']) {
        markAsUnread(result['unread']);
      }
      if (result['update']) {
        updateNewItems(result['update']);
      }
    }
  };

  /**
   * http://stackoverflow.com/questions/4652734/return-html-from-a-user-selection/4652824#4652824
   */
  function getSelectionHtml() {
    var html = '';
    if (typeof window.getSelection != 'undefined') {
      var sel = window.getSelection();
      if (sel.rangeCount) {
        var container = document.createElement('div');
        for (var i = 0, len = sel.rangeCount; i < len; ++i) {
          container.appendChild(sel.getRangeAt(i).cloneContents());
        }
        html = container.innerHTML;
      }
    } else if (typeof document.selection != 'undefined') {
      if (document.selection.type == 'Text') {
        html = document.selection.createRange().htmlText;
      }
    }
    return html;
  }

  /**
   * Some javascript snippets
   */
  function removeChildren(elt) {
    while (elt.hasChildNodes()) {
      elt.removeChild(elt.firstChild);
    }
  }

  function removeElement(elt) {
    if (elt && elt.parentNode) {
      elt.parentNode.removeChild(elt);
    }
  }

  function addClass(elt, cls) {
    if (elt) {
      elt.className = (elt.className + ' ' + cls).trim();
    }
  }

  function removeClass(elt, cls) {
    if (elt) {
      elt.className = (' ' + elt.className + ' ').replace(cls, ' ').trim();
    }
  }

  function hasClass(elt, cls) {
    if (elt && (' ' + elt.className + ' ').indexOf(' ' + cls + ' ') > -1) {
      return true;
    }
    return false;
  }

  /**
   * Add redirector to link
   */
  function anonymize(elt) {
    if (redirector !== '') {
      var domain, a_to_anon = elt.getElementsByTagName("a");
      for (var i = 0; i < a_to_anon.length; i++) {
        domain = a_to_anon[i].href.replace('http://','').replace('https://','').split(/[/?#]/)[0];
        if (domain !== window.location.host) {
          if (redirector !== 'noreferrer') {
            a_to_anon[i].href = redirector+a_to_anon[i].href;
          } else {
            a_to_anon[i].setAttribute('rel', 'noreferrer');
          }
        }
      }
    }
  }

  function initAnonyme() {
    if (redirector !== '') {
      var i = 0, elements = document.getElementById('list-items');
      elements = elements.getElementsByTagName('div');
      for (i = 0; i < elements.length; i += 1) {
        if (hasClass(elements[i], 'item-content')) {
          anonymize(elements[i]);
        }
      }
    }
  }

  /**
   * Replace collapse bootstrap function
   */
  function collapseElement(element) {
    if (element !== null) {
      var targetElement = document.getElementById(
        element.getAttribute('data-target').substring(1)
      );

      if (hasClass(targetElement, 'in')) {
        removeClass(targetElement, 'in');
        targetElement.style.height = 0;
      } else {
        addClass(targetElement, 'in');
        targetElement.style.height = 'auto';
      }
    }
  }

  function collapseClick(event) {
    event = event || window.event;
    stopBubbling(event);

    collapseElement(this);
  }

  function initCollapse(list) {
    var i = 0;

    for (i = 0; i < list.length; i += 1) {
      if (list[i].hasAttribute('data-toggle') && list[i].hasAttribute('data-target')) {
        addEvent(list[i], 'click', collapseClick);
      }
    }
  }

  /**
   * Shaarli functions
   */
  function htmlspecialchars_decode(string) {
    return string
           .replace(/&lt;/g, '<')
           .replace(/&gt;/g, '>')
           .replace(/&quot;/g, '"')
           .replace(/&amp;/g, '&')
           .replace(/&#0*39;/g, "'")
           .replace(/&nbsp;/g, " ");
  }

  function shaarliItem(itemHash) {
    var domainUrl, url, domainVia, via, title, sel, element;

   element = document.getElementById('item-div-'+itemHash);
    if (element.childNodes.length > 1) {
      title = getTitleItem(itemHash);
      url = getUrlItem(itemHash);
      via = getViaItem(itemHash);
      if (redirector != 'noreferrer') {
        url = url.replace(redirector,'');
        via = via.replace(redirector,'');
      }
      domainUrl = url.replace('http://','').replace('https://','').split(/[/?#]/)[0];
      domainVia = via.replace('http://','').replace('https://','').split(/[/?#]/)[0];
      if (domainUrl !== domainVia) {
        via = 'via ' + via;
      } else {
        via = '';
      }
      sel = getSelectionHtml();
      if (sel !== '') {
        sel = '«' + sel + '»';
      }

      if (shaarli !== '') {
        window.open(
          shaarli
          .replace('${url}', encodeURIComponent(htmlspecialchars_decode(url)))
          .replace('${title}', encodeURIComponent(htmlspecialchars_decode(title)))
          .replace('${via}', encodeURIComponent(htmlspecialchars_decode(via)))
          .replace('${sel}', encodeURIComponent(htmlspecialchars_decode(sel))),
          '_blank',
          'height=390, width=600, menubar=no, toolbar=no, scrollbars=no, status=no'
        );
      } else {
        alert('Please configure your share link first');
      }
    } else {
      loadDivItem(itemHash);
      alert('Sorry ! This item is not loaded, try again !');
    }
  }

  function shaarliCurrentItem() {
    shaarliItem(currentItemHash);
  }

  function shaarliClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    shaarliItem(getItemHash(this));

    return false;
  }

  /**
   * Folder functions
   */
  function getFolder(element) {
    var folder = null;

    while (folder === null && element !== null) {
      if (element.tagName === 'LI' && element.id.indexOf('folder-') === 0) {
        folder = element;
      }
      element = element.parentNode;
    }

    return folder;
  }

  function getLiParentByClassName(element, classname) {
    var li = null;

    while (li === null && element !== null) {
      if (element.tagName === 'LI' && hasClass(element, classname)) {
        li = element;
      }
      element = element.parentNode;
    }

    if (classname === 'folder' && li.id === 'all-subscriptions') {
      li = null;
    }

    return li;
  }

  function getFolderHash(element) {
    var folder = getFolder(element);

    if (folder !== null) {
      return folder.id.replace('folder-','');
    }

    return null;
  }

  function toggleFolder(folderHash) {
    var i, listElements, url, client;

    listElements = document.getElementById('folder-' + folderHash);
    listElements = listElements.getElementsByTagName('h5');
    if (listElements.length > 0) {
      listElements = listElements[0].getElementsByTagName('span');

      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'folder-toggle-open')) {
          removeClass(listElements[i], 'folder-toggle-open');
          addClass(listElements[i], 'folder-toggle-close');
        } else if (hasClass(listElements[i], 'folder-toggle-close')) {
          removeClass(listElements[i], 'folder-toggle-close');
          addClass(listElements[i], 'folder-toggle-open');
        }
      }
    }

    url = '?toggleFolder=' + folderHash + '&ajax';
    client = new HTTPClient();

    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function toggleClickFolder(event) {
    event = event || window.event;
    stopBubbling(event);

    toggleFolder(getFolderHash(this));

    return false;
  }

  function initLinkFolders(listFolders) {
    var i = 0;

    for (i = 0; i < listFolders.length; i += 1) {
      if (listFolders[i].hasAttribute('data-toggle') && listFolders[i].hasAttribute('data-target')) {
        listFolders[i].onclick = toggleClickFolder;
      }
    }
  }

  function getListLinkFolders() {
    var i = 0,
        listFolders = [],
        listElements = document.getElementById('list-feeds');

    if (listElements) {
      listElements = listElements.getElementsByTagName('a');

      for (i = 0; i < listElements.length; i += 1) {
        listFolders.push(listElements[i]);
      }
    }

    return listFolders;
  }

  /**
   * MarkAs functions
   */
  function toggleMarkAsLinkItem(itemHash) {
    var i, item = getItem(itemHash), listLinks;

    if (item !== null) {
      listLinks = item.getElementsByTagName('a');

      for (i = 0; i < listLinks.length; i += 1) {
        if (hasClass(listLinks[i], 'item-mark-as')) {
          if (listLinks[i].href.indexOf('unread=') > -1) {
            listLinks[i].href = listLinks[i].href.replace('unread=','read=');
            listLinks[i].firstChild.innerHTML = intlRead;
          } else {
            listLinks[i].href = listLinks[i].href.replace('read=','unread=');
            listLinks[i].firstChild.innerHTML = intlUnread;
          }
        }
      }
    }
  }

  function getUnreadLabelItems(itemHash) {
    var i, listLinks, regex = new RegExp('read=' + itemHash.substr(0,6)), items = [];
    listLinks = getListLinkFolders();
    for (i = 0; i < listLinks.length; i += 1) {
      if (regex.test(listLinks[i].href)) {
        items.push(listLinks[i].children[0]);
      }
    }
    return items;
  }

  function addToUnreadLabel(unreadLabelItem, value) {
      var unreadLabel = -1;
      if (unreadLabelItem !== null) {
        unreadLabel = parseInt(unreadLabelItem.innerHTML, 10) + value;
        unreadLabelItem.innerHTML = unreadLabel;
      }
      return unreadLabel;
  }

  function getUnreadLabel(folder) {
    var element = null;
    if (folder !== null) {
      element = folder.getElementsByClassName('label')[0];
    }
    return element;
  }

  function markAsItem(itemHash) {
    var item, url, client, indexItem, i, unreadLabelItems, nb, feed, folder, addRead = 1;

    item = getItem(itemHash);

    if (item !== null) {
      unreadLabelItems = getUnreadLabelItems(itemHash);
      if (!hasClass(item, 'read')) {
        addRead = -1;
      }
      for (i = 0; i < unreadLabelItems.length; i += 1) {
        nb = addToUnreadLabel(unreadLabelItems[i], addRead);
        if (nb === 0) {
          feed = getLiParentByClassName(unreadLabelItems[i], 'feed');
          removeClass(feed, 'has-unread');
          if (autohide) {
            addClass(feed, 'autohide-feed');
          }
        }
        folder = getLiParentByClassName(unreadLabelItems[i], 'folder');
        nb = addToUnreadLabel(getUnreadLabel(folder), addRead);
        if (nb === 0 && autohide) {
          addClass(folder, 'autohide-folder');
        }
      }
      addToUnreadLabel(getUnreadLabel(document.getElementById('all-subscriptions')), addRead);

      if (hasClass(item, 'read')) {
        url = '?unread=' + itemHash;
        removeClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
      } else {
        url = '?read=' + itemHash;
        addClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
        if (filter === 'unread') {
          url += '&currentHash=' + currentHash +
            '&page=' + currentPage +
            '&last=' + listItemsHash[listItemsHash.length - 1];

          removeElement(item);
          indexItem = listItemsHash.indexOf(itemHash);
          listItemsHash.splice(listItemsHash.indexOf(itemHash), 1);
          if (listItemsHash.length <= byPage) {
            appendItem(listItemsHash[listItemsHash.length - 1]);
          }
          setCurrentItem(listItemsHash[indexItem]);
        }
      }
    } else {
      url = '?currentHash=' + currentHash +
        '&page=' + currentPage;
    }

    client = new HTTPClient();
    client.init(url + '&ajax');
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function markAsCurrentItem() {
    markAsItem(currentItemHash);
  }

  function markAsClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    markAsItem(getItemHash(this));

    return false;
  }

  function toggleMarkAsStarredLinkItem(itemHash) {
    var i, item = getItem(itemHash), listLinks, url = '';

    if (item !== null) {
      listLinks = item.getElementsByTagName('a');

      for (i = 0; i < listLinks.length; i += 1) {
        if (hasClass(listLinks[i], 'item-starred')) {
          url = listLinks[i].href;
          if (listLinks[i].href.indexOf('unstar=') > -1) {
            listLinks[i].href = listLinks[i].href.replace('unstar=','star=');
            listLinks[i].firstChild.innerHTML = intlStar;
          } else {
            listLinks[i].href = listLinks[i].href.replace('star=','unstar=');
            listLinks[i].firstChild.innerHTML = intlUnstar;
          }
        }
      }
    }

    return url;
  }

  function markAsStarredCurrentItem() {
    markAsStarredItem(currentItemHash);
  }

  function markAsStarredItem(itemHash) {
    var url, client, indexItem;

    url = toggleMarkAsStarredLinkItem(itemHash);
    if (url.indexOf('unstar=') > -1 && stars) {
      removeElement(getItem(itemHash));
      indexItem = listItemsHash.indexOf(itemHash);
      listItemsHash.splice(listItemsHash.indexOf(itemHash), 1);
      if (listItemsHash.length <= byPage) {
        appendItem(listItemsHash[listItemsHash.length - 1]);
      }
      setCurrentItem(listItemsHash[indexItem]);

      url += '&page=' + currentPage;
    }
    if (url !== '') {
      client = new HTTPClient();
      client.init(url + '&ajax');
      try {
        client.asyncGET(ajaxHandler);
      } catch (e) {
        alert(e);
      }
    }
  }

  function markAsStarredClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    markAsStarredItem(getItemHash(this));

    return false;
  }

  function markAsRead(itemHash) {
    setNbUnread(currentUnread - 1);
  }

  function markAsUnread(itemHash) {
    setNbUnread(currentUnread + 1);
  }

  /**
   * Div item functions
   */
  function loadDivItem(itemHash, noFocus) {
    var element, url, client, cacheItem;
    element = document.getElementById('item-div-'+itemHash);
    if (element.childNodes.length <= 1) {
      cacheItem = getCacheItem(itemHash);
      if (cacheItem !== null) {
        setDivItem(element, cacheItem);
        if(!noFocus) {
          setItemFocus(element);
        }
        removeCacheItem(itemHash);
      } else {
        url = '?'+(stars?'stars&':'')+'currentHash=' + currentHash +
          '&current=' + itemHash +
          '&ajax';
        client = new HTTPClient();
        client.init(url, noFocus);
        try {
          client.asyncGET(ajaxHandler);
        } catch (e) {
          alert(e);
        }
      }
    }
  }

  function toggleItem(itemHash) {
    var i, listElements, element, targetElement;

    if (view === 'expanded') {
      return;
    }

    if (currentItemHash != itemHash) {
      closeCurrentItem();
    }

    // looking for ico + or -
    listElements = document.getElementById('item-toggle-' + itemHash);
    listElements = listElements.getElementsByTagName('span');
    for (i = 0; i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-toggle-open')) {
        removeClass(listElements[i], 'item-toggle-open');
        addClass(listElements[i], 'item-toggle-close');
      } else if (hasClass(listElements[i], 'item-toggle-close')) {
        removeClass(listElements[i], 'item-toggle-close');
        addClass(listElements[i], 'item-toggle-open');
      }
    }

    element = document.getElementById('item-toggle-'+itemHash);
    targetElement = document.getElementById(
      element.getAttribute('data-target').substring(1)
    );
    if (element.href.indexOf('&open') > -1) {
      element.href = element.href.replace('&open','');
      addClass(targetElement, 'well');
      setCurrentItem(itemHash);
      loadDivItem(itemHash);
    } else {
      element.href = element.href + '&open';
      removeClass(targetElement, 'well');
    }
  }

  function toggleCurrentItem() {
    toggleItem(currentItemHash);
    collapseElement(document.getElementById('item-toggle-' + currentItemHash));
  }

  function toggleClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    toggleItem(getItemHash(this));

    return false;
  }

  /**
   * Item management functions
   */
  function getItem(itemHash) {
    return document.getElementById('item-' + itemHash);
  }

  function getTitleItem(itemHash) {
    var i = 0, element = document.getElementById('item-div-'+itemHash), listElements = element.getElementsByTagName('a'), title = '';

    for (i = 0; title === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-link')) {
        title = listElements[i].innerHTML;
      }
    }

    return title;
  }

  function getUrlItem(itemHash) {
    var i = 0, element = document.getElementById('item-'+itemHash), listElements = element.getElementsByTagName('a'), url = '';

    for (i = 0; url === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-link')) {
        url = listElements[i].href;
      }
    }

    return url;
  }

  function getViaItem(itemHash) {
    var i = 0, element = document.getElementById('item-div-'+itemHash), listElements = element.getElementsByTagName('a'), via = '';

    for (i = 0; via === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-via')) {
        via = listElements[i].href;
      }
    }

    return via;
  }

  function getLiItem(element) {
    var item = null;

    while (item === null && element !== null) {
      if (element.tagName === 'LI' && element.id.indexOf('item-') === 0) {
        item = element;
      }
      element = element.parentNode;
    }

    return item;
  }

  function getItemHash(element) {
    var item = getLiItem(element);

    if (item !== null) {
      return item.id.replace('item-','');
    }

    return null;
  }

  function getCacheItem(itemHash) {
    if (typeof cache['item-' + itemHash] !== 'undefined') {
      return cache['item-' + itemHash];
    }

    return null;
  }

  function removeCacheItem(itemHash) {
    if (typeof cache['item-' + itemHash] !== 'undefined') {
      delete cache['item-' + itemHash];
    }
  }

  function isCurrentUnread() {
    var item = getItem(currentItemHash);

    if (hasClass(item, 'read')) {
      return false;
    }

    return true;
  }

  function setDivItem(div, item) {
    var markAs = intlRead, starred = intlStar, target = ' target="_blank"';

    if (item['read'] == 1) {
      markAs = intlUnread;
    }

    if (item['starred'] == 1) {
      starred = intlUnstar;
    }

    if (!blank) {
      target = '';
    }

    div.innerHTML = '<div class="item-title">' +
      '<a class="item-shaarli" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label">' + intlShare + '</span></a> ' +
      (stars?'':
      '<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label item-label-mark-as">' + markAs + '</span></a> ') +
      '<a class="item-starred" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + starred + '=' + item['itemHash'] + '"><span class="label item-label-starred">' + starred + '</span></a> ' +
      '<a' + target + ' class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a>' +
      '</div>' +
      '<div class="clear"></div>' +
      '<div class="item-info-end item-info-time">' +
      item['time']['expanded'] +
      '</div>' +
      '<div class="item-info-end item-info-authors">' +
      intlFrom + ' <a class="item-via" href="' + item['via'] + '">' +
      item['author'] +
      '</a> ' +
      '<a class="item-xml" href="' + item['xmlUrl'] + '">' +
      '<span class="ico">' +
      '<span class="ico-feed-dot"></span>' +
      '<span class="ico-feed-circle-1"></span>' +
      '<span class="ico-feed-circle-2"></span>'+
      '</span>' +
      '</a>' +
      '</div>' +
      '<div class="clear"></div>' +
      '<div class="item-content"><article>' +
      item['content'] +
      '</article></div>' +
      '<div class="clear"></div>' +
      '<div class="item-info-end">' +
      '<a class="item-top" href="#status"><span class="label label-expanded">' + intlTop + '</span></a> ' +
      '<a class="item-shaarli" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label label-expanded">' + intlShare + '</span></a> ' +
      (stars?'':
      '<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label label-expanded">' + markAs + '</span></a> ') +
      '<a class="item-starred" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + starred + '=' + item['itemHash'] + '"><span class="label label-expanded">' + starred + '</span></a>' +
      (view=='list'?
      '<a id="item-toggle-'+ item['itemHash'] +'" class="item-toggle item-toggle-plus" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] +'&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '"> ' +
      '<span class="ico ico-toggle-item">' +
      '<span class="ico-b-disc"></span>' +
      '<span class="ico-w-line-h"></span>' +
      '</span>' +
      '</a>':'') +
      '</div>' +
      '<div class="clear"></div>';

    initLinkItems(div.getElementsByTagName('a'));
    initCollapse(div.getElementsByTagName('a'));
    anonymize(div);
  }

  function setLiItem(li, item) {
    var markAs = intlRead, target = ' target="_blank"';

    if (item['read'] == 1) {
      markAs = intlUnread;
    }

    if (!blank) {
      target = '';
    }

    li.innerHTML = '<a id="item-toggle-'+ item['itemHash'] +'" class="item-toggle item-toggle-plus" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] +'&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '"> ' +
      '<span class="ico ico-toggle-item">' +
      '<span class="ico-b-disc"></span>' +
      '<span class="ico-w-line-h"></span>' +
      '<span class="ico-w-line-v item-toggle-close"></span>' +
      '</span>' +
      item['time']['list'] +
      '</a>' +
      '<dl class="dl-horizontal item">' +
      '<dt class="item-feed">' +
      (addFavicon?
      '<span class="item-favicon">' +
      '<img src="' + item['favicon'] + '" height="16" width="16" title="favicon" alt="favicon"/>' +
      '</span>':'' ) +
      '<span class="item-author">' +
      '<a class="item-feed" href="?'+(stars?'stars&':'')+'currentHash=' + item['itemHash'].substring(0, 6) + '">' +
      item['author'] +
      '</a>' +
      '</span>' +
      '</dt>' +
      '<dd class="item-info">' +
      '<span class="item-title">' +
      (stars?'':'<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label">' + markAs + '</span></a> ') +
      '<a' + target + ' class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a> ' +
      '</span>' +
      '<span class="item-description">' +
      '<a class="item-toggle muted" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] + '&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '">' +
      item['description'] +
      '</a> ' +
      '</span>' +
      '</dd>' +
      '</dl>' +
      '<div class="clear"></div>';

    initCollapse(li.getElementsByTagName('a'));
    initLinkItems(li.getElementsByTagName('a'));

    anonymize(li);
  }

  function createLiItem(item) {
    var li = document.createElement('li'),
        div = document.createElement('div');

    div.id = 'item-div-'+item['itemHash'];
    div.className= 'item collapse'+(view === 'expanded' ? ' in well' : '');

    li.id = 'item-'+item['itemHash'];
    if (view === 'list') {
      li.className = 'item-list';
      setLiItem(li, item);
    } else {
      li.className = 'item-expanded';
      setDivItem(div, item);
    }
    li.className += (item['read'] === 1)?' read':'';
    li.appendChild(div);

    return li;
  }

  /**
   * List items management functions
   */
  function getListItems() {
    return document.getElementById('list-items');
  }

  function updateListItems(itemsList) {
    var i;

    for (i = 0; i < itemsList.length; i++) {
      if (listItemsHash.indexOf(itemsList[i]['itemHash']) === -1 && listItemsHash.length <= byPage) {
        cache['item-' + itemsList[i]['itemHash']] = itemsList[i];
        listItemsHash.push(itemsList[i]['itemHash']);
        if (listItemsHash.length <= byPage) {
          appendItem(itemsList[i]['itemHash']);
        }
      }
    }
  }

  function appendItem(itemHash) {
    var listItems = getListItems(),
        item = getCacheItem(itemHash),
        li;

    if (item !== null) {
      li = createLiItem(item);
      listItems.appendChild(li);
      removeCacheItem(itemHash);
    }
  }

  function getListLinkItems() {
    var i = 0,
        listItems = [],
        listElements = document.getElementById('list-items');

    listElements = listElements.getElementsByTagName('a');

    for (i = 0; i < listElements.length; i += 1) {
      listItems.push(listElements[i]);
    }

    return listItems;
  }

  function initListItemsHash() {
    var i,
        listElements = document.getElementById('list-items');

    listElements = listElements.getElementsByTagName('li');
    for (i = 0; i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-list') || hasClass(listElements[i], 'item-expanded')) {
        if (hasClass(listElements[i], 'current')) {
          currentItemHash = getItemHash(listElements[i]);
        }
        listItemsHash.push(listElements[i].id.replace('item-',''));
      }
    }
  }

  function initLinkItems(listItems) {
    var i = 0;

    for (i = 0; i < listItems.length; i += 1) {
      if (hasClass(listItems[i], 'item-toggle')) {
        listItems[i].onclick = toggleClickItem;
      }
      if (hasClass(listItems[i], 'item-mark-as')) {
        listItems[i].onclick = markAsClickItem;
      }
      if (hasClass(listItems[i], 'item-starred')) {
        listItems[i].onclick = markAsStarredClickItem;
      }
      if (hasClass(listItems[i], 'item-shaarli')) {
        listItems[i].onclick = shaarliClickItem;
      }
    }
  }

  function initListItems() {
    var url, client;

    url = '?currentHash=' + currentHash +
      '&page=' + currentPage +
      '&last=' + listItemsHash[listItemsHash.length -1] +
      '&ajax' +
      (stars?'&stars':'');

    client = new HTTPClient();
    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function preloadItems()
  {
    // Pre-fetch items from top to bottom
    for(var i = 0, len = listItemsHash.length; i < len; ++i)
    {
      loadDivItem(listItemsHash[i], true);
    }
  }

  /**
   * Update
   */
  function setStatus(text) {
    if (text === '') {
      document.getElementById('status').innerHTML = status;
    } else {
      document.getElementById('status').innerHTML = text;
    }
  }

  function getTimeMin() {
    return Math.round((new Date().getTime()) / 1000 / 60);
  }

  function updateFeed(feedHashIndex) {
    var i = 0, url, client, feedHash = '';

    if (feedHashIndex !== '') {
      setStatus('updating ' + listUpdateFeeds[feedHashIndex][1]);
      feedHash = listUpdateFeeds[feedHashIndex][0];
      listUpdateFeeds[feedHashIndex][2] = getTimeMin();
    }

    url = '?update'+(feedHash === ''?'':'='+feedHash)+'&ajax';

    client = new HTTPClient();
    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function updateNextFeed() {
    var i = 0, nextTimeUpdate = 0, currentMin, diff, minDiff = -1, feedToUpdateIndex = '', minFeedToUpdateIndex = '';
    if (listUpdateFeeds.length !== 0) {
      currentMin = getTimeMin();
      for (i = 0; feedToUpdateIndex === '' && i < listUpdateFeeds.length; i++) {
        diff = currentMin - listUpdateFeeds[i][2];
        if (diff >= listUpdateFeeds[i][3]) {
          //need update
          feedToUpdateIndex = i;
        } else {
          if (minDiff === -1 || diff < minDiff) {
            minDiff = diff;
            minFeedToUpdateIndex = i;
          }
        }
      }
      if (feedToUpdateIndex === '') {
        feedToUpdateIndex = minFeedToUpdateIndex;
      }
      updateFeed(feedToUpdateIndex);
    } else {
      updateFeed('');
    }
  }

  function updateTimeout() {
    var i = 0, nextTimeUpdate = 0, currentMin, diff, minDiff = -1, feedToUpdateIndex = '';

    if (listUpdateFeeds.length !== 0) {
      currentMin = getTimeMin();
      for (i = 0; minDiff !== 0 && i < listUpdateFeeds.length; i++) {
        diff = currentMin - listUpdateFeeds[i][2];
        if (diff >= listUpdateFeeds[i][3]) {
          //need update
          minDiff = 0;
        } else {
          if (minDiff === -1 || (listUpdateFeeds[i][3] - diff) < minDiff) {
            minDiff = listUpdateFeeds[i][3] - diff;
          }
        }
      }
      window.setTimeout(updateNextFeed, minDiff * 1000 * 60 + 200);
    }
  }

  function updateNewItems(result) {
    var i = 0, list, currentMin, folder, feed, unreadLabelItems, nbItems;
    setStatus('');
    if (result !== false) {
      if (result['feeds']) {
        // init list of feeds information for update
        listUpdateFeeds = result['feeds'];
        currentMin = getTimeMin();
        for (i = 0; i < listUpdateFeeds.length; i++) {
          listUpdateFeeds[i][2] = currentMin - listUpdateFeeds[i][2];
        }
      }
      if (result.newItems && result.newItems.length > 0) {
        nbItems = result.newItems.length;
        currentNbItems += nbItems;
        setNbUnread(currentUnread + nbItems);
        addToUnreadLabel(getUnreadLabel(document.getElementById('all-subscriptions')), nbItems);
        unreadLabelItems = getUnreadLabelItems(result.newItems[0].substr(0,6));
        for (i = 0; i < unreadLabelItems.length; i += 1) {
          feed = getLiParentByClassName(unreadLabelItems[i], 'feed');
          folder = getLiParentByClassName(feed, 'folder');
          addClass(feed, 'has-unread');
          if (autohide) {
            removeClass(feed, 'autohide-feed');
            removeClass(folder, 'autohide-folder');
          }
          addToUnreadLabel(getUnreadLabel(feed), nbItems);
          addToUnreadLabel(getUnreadLabel(folder), nbItems);
        }
      }
      updateTimeout();
    }
  }

  function initUpdate() {
    window.setTimeout(updateNextFeed, 1000);
  }

  /**
   * Navigation
   */
  function setItemFocus(item) {
    if(autofocus) {
      // First, let the browser do some rendering
      // Indeed, the div might not be visible yet, so there is no scroll
      setTimeout(function()
      {
        // Dummy implementation
        var container = document.getElementById('main-container'),
          scrollPos = container.scrollTop,
          itemPos = item.offsetTop,
          temp = item;
        while(temp.offsetParent != document.body) {
          temp = temp.offsetParent;
          itemPos += temp.offsetTop;
        }
        var current = itemPos - scrollPos;
        // Scroll only if necessary
        // current < 0: Happens when asking for previous item and displayed item is filling the screen
        // Or check if item bottom is outside screen
        if(current < 0 || current + item.offsetHeight > container.clientHeight) {
          container.scrollTop = itemPos;
        }
      }, 0);
    }
  }

  function previousClickPage(event) {
    event = event || window.event;
    stopBubbling(event);

    previousPage();

    return false;
  }

  function nextClickPage(event) {
    event = event || window.event;
    stopBubbling(event);

    nextPage();

    return false;
  }

  function nextPage() {
    currentPage = currentPage + 1;
    if (currentPage > Math.ceil(currentNbItems / byPage)) {
      currentPage = Math.ceil(currentNbItems / byPage);
    }
    if (listItemsHash.length === 0) {
      currentPage = 1;
    }
    listItemsHash = [];
    initListItems();
    removeChildren(getListItems());
  }

  function previousPage() {
    currentPage = currentPage - 1;
    if (currentPage < 1) {
      currentPage = 1;
    }
    listItemsHash = [];
    initListItems();
    removeChildren(getListItems());
  }

  function previousClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    previousItem();

    return false;
  }

  function nextClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    nextItem();

    return false;
  }

  function nextItem() {
    var nextItemIndex = listItemsHash.indexOf(currentItemHash) + 1, nextCurrentItemHash;

    closeCurrentItem();
    if (autoreadItem && isCurrentUnread()) {
      markAsCurrentItem();
      if (filter == 'unread') {
        nextItemIndex -= 1;
      }
    }

    if (nextItemIndex < 0) { nextItemIndex = 0; }

    if (nextItemIndex < listItemsHash.length) {
      nextCurrentItemHash = listItemsHash[nextItemIndex];
    }

    if (nextItemIndex >= byPage) {
      nextPage();
    } else {
      setCurrentItem(nextCurrentItemHash);
    }
  }

  function previousItem() {
    var previousItemIndex = listItemsHash.indexOf(currentItemHash) - 1, previousCurrentItemHash;

    if (previousItemIndex < listItemsHash.length && previousItemIndex >= 0) {
      previousCurrentItemHash = listItemsHash[previousItemIndex];
    }

    closeCurrentItem();
    if (previousItemIndex < 0) {
      previousPage();
    } else {
      setCurrentItem(previousCurrentItemHash);
    }
  }

  function closeCurrentItem() {
    var element = document.getElementById('item-toggle-' + currentItemHash);

    if (element && view === 'list') {
      var targetElement = document.getElementById(
            element.getAttribute('data-target').substring(1)
          );

      if (element.href.indexOf('&open') < 0) {
        element.href = element.href + '&open';
        removeClass(targetElement, 'well');
        collapseElement(element);
      }

      var i = 0,
          listElements = element.getElementsByTagName('span');

      // looking for ico + or -
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'item-toggle-open')) {
          removeClass(listElements[i], 'item-toggle-open');
          addClass(listElements[i], 'item-toggle-close');
        }
      }
    }
  }

  function setCurrentItem(itemHash) {
    var currentItemIndex;

    if (itemHash !== currentItemHash) {
      removeClass(document.getElementById('item-'+currentItemHash), 'current');
      removeClass(document.getElementById('item-div-'+currentItemHash), 'current');
      if (typeof itemHash !== 'undefined') {
        currentItemHash = itemHash;
      }
      currentItemIndex = listItemsHash.indexOf(currentItemHash);
      if (currentItemIndex === -1) {
        if (listItemsHash.length > 0) {
          currentItemHash = listItemsHash[0];
        } else {
          currentItemHash = '';
        }
      } else {
        if (currentItemIndex >= byPage) {
          currentItemHash = listItemsHash[byPage - 1];
        }
      }

      if (currentItemHash !== '') {
        var item = document.getElementById('item-'+currentItemHash),
          itemDiv = document.getElementById('item-div-'+currentItemHash);
        addClass(item, 'current');
        addClass(itemDiv, 'current');
        setItemFocus(itemDiv);
        updateItemButton();
      }
    }
    updatePageButton();
  }

  function openCurrentItem(blank) {
    var url;

    url = getUrlItem(currentItemHash);
    if (blank) {
      window.location.href = url;
    } else {
      window.open(url);
    }
  }

  // http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.js (swipe)
  function checkMove(e) {
    // More than this horizontal displacement, and we will suppress scrolling.
    var scrollSupressionThreshold = 10,
    // More time than this, and it isn't a swipe.
        durationThreshold = 500,
    // Swipe horizontal displacement must be more than this.
        horizontalDistanceThreshold = 30,
    // Swipe vertical displacement must be less than this.
        verticalDistanceThreshold = 75;

    if (e.targetTouches.length == 1) {
      var touch = e.targetTouches[0],
      start = { time: ( new Date() ).getTime(),
                coords: [ touch.pageX, touch.pageY ] },
      stop;
      var moveHandler = function ( e ) {

        if ( !start ) {
          return;
        }

        if (e.targetTouches.length == 1) {
          var touch = e.targetTouches[0];
          stop = { time: ( new Date() ).getTime(),
                   coords: [ touch.pageX, touch.pageY ] };
        }
      };

      addEvent(window, 'touchmove', moveHandler);
      addEvent(window, 'touchend', function (e) {
        removeEvent(window, 'touchmove', moveHandler);
        if ( start && stop ) {
          if ( stop.time - start.time < durationThreshold &&
            Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] ) > horizontalDistanceThreshold &&
            Math.abs( start.coords[ 1 ] - stop.coords[ 1 ] ) < verticalDistanceThreshold
             ) {
            if ( start.coords[0] > stop.coords[ 0 ] ) {
              nextItem();
            }
            else {
              previousItem();
            }
          }
          start = stop = undefined;
        }
      });
    }
  }

  function checkKey(e) {
    var code;
    if (!e) e = window.event;
    if (e.keyCode) code = e.keyCode;
    else if (e.which) code = e.which;

    if (!e.ctrlKey && !e.altKey) {
      switch(code) {
        case 32: // 'space'
        toggleCurrentItem();
        break;
        case 65: // 'A'
        if (window.confirm('Mark all current as read ?')) {
          window.location.href = '?read=' + currentHash;
        }
		break;
        case 67: // 'C'
        window.location.href = '?config';
        break;
        case 69: // 'E'
        window.location.href = (currentHash===''?'?edit':'?edit='+currentHash);
        break;
        case 70: // 'F'
        if (listFeeds =='show') {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'listFeeds=hide';
        } else {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'listFeeds=show';
        }
        break;
        case 72: // 'H'
        window.location.href = document.getElementById('nav-home').href;
        break;
        case 74: // 'J'
        nextItem();
        toggleCurrentItem();
        break;
        case 75: // 'K'
        previousItem();
        toggleCurrentItem();
        break;
        case 77: // 'M'
        if (e.shiftKey) {
          markAsCurrentItem();
          toggleCurrentItem();
        } else {
          markAsCurrentItem();
        }
        break;
        case 39: // right arrow
        case 78: // 'N'
        if (e.shiftKey) {
          nextPage();
        } else {
          nextItem();
        }
        break;
        case 79: // 'O'
        if (e.shiftKey) {
          openCurrentItem(true);
        } else {
          openCurrentItem(false);
        }
        break;
        case 37: // left arrow
        case 80 : // 'P'
        if (e.shiftKey) {
          previousPage();
        } else {
          previousItem();
        }
        break;
        case 82: // 'R'
        window.location.reload(true);
        break;
        case 83: // 'S'
        shaarliCurrentItem();
        break;
        case 84: // 'T'
        toggleCurrentItem();
        break;
        case 85: // 'U'
        window.location.href = (currentHash===''?'?update':'?currentHash=' + currentHash + '&update='+currentHash);
        break;
        case 86: // 'V'
        if (view == 'list') {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'view=expanded';
        } else {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'view=list';
        }
        break;
        case 90: // 'z'
		if (filter == 'unread') {
			while(listItemsHash.indexOf(currentItemHash) != -1) {
				window.open(getUrlItem(currentItemHash),'_blank');
				markAsCurrentItem();
			}
        } else if (filter == 'all') {
			for (var i=0;i<listItemsHash.length;i++){
				if (!hasClass(getItem(listItemsHash[i]), 'read')){
					window.open(getUrlItem(currentItemHash),'_blank');
					markAsCurrentItem();
				}
				nextItem();
			}
		}
        break;
        case 170: // '*'
          markAsStarredCurrentItem();
          break;
        case 112: // 'F1'
        case 188: // '?'
        case 191: // '?'
        window.location.href = '?help';
        break;
        default:
        break;
      }
    }
    // e.ctrlKey e.altKey e.shiftKey
  }

  function initPageButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].onclick = previousClickPage;
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].onclick = nextClickPage;
        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].onclick = previousClickPage;
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].onclick = nextClickPage;
        }
      }
    }
  }

  function updatePageButton() {
    var i = 0, paging, listElements, maxPage;

    if (filter == 'unread') {
      currentNbItems = currentUnread;
    }

    if (currentNbItems < byPage) {
      maxPage = 1;
    } else {
      maxPage = Math.ceil(currentNbItems / byPage);
    }

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previousPage=' + currentPage;
          if (currentPage === 1) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&nextPage=' + currentPage;
          if (currentPage === maxPage) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
      }
      listElements = paging.getElementsByTagName('button');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'current-max-page')) {
          listElements[i].innerHTML = currentPage + ' / ' + maxPage;
        }
      }
    }
    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previousPage=' + currentPage;
          if (currentPage === 1) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&nextPage=' + currentPage;
          if (currentPage === maxPage) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
      }
      listElements = paging.getElementsByTagName('button');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'current-max-page')) {
          listElements[i].innerHTML = currentPage + ' / ' + maxPage;
        }
      }
    }
  }

  function initItemButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].onclick = previousClickItem;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].onclick = nextClickItem;
        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].onclick = previousClickItem;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].onclick = nextClickItem;
        }
      }
    }
  }

  function updateItemButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&next=' + currentItemHash;

        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&next=' + currentItemHash;
        }
      }
    }
  }

  /**
   * init KrISS feed javascript
   */
  function initUnread() {
    var element = document.getElementById((stars?'nb-starred':'nb-unread'));

    currentUnread = parseInt(element.innerHTML, 10);

    title = document.title;
    setNbUnread(currentUnread);
  }

  function setNbUnread(nb) {
    var element = document.getElementById((stars?'nb-starred':'nb-unread'));

    if (nb < 0) {
      nb = 0;
    }

    currentUnread = nb;
    element.innerHTML = currentUnread;
    document.title = title + ' (' + currentUnread + ')';
  }

  function initOptions() {
    var elementIndex = document.getElementById('index');

    if (elementIndex.hasAttribute('data-view')) {
      view = elementIndex.getAttribute('data-view');
    }
    if (elementIndex.hasAttribute('data-list-feeds')) {
      listFeeds = elementIndex.getAttribute('data-list-feeds');
    }
    if (elementIndex.hasAttribute('data-filter')) {
      filter = elementIndex.getAttribute('data-filter');
    }
    if (elementIndex.hasAttribute('data-order')) {
      order = elementIndex.getAttribute('data-order');
    }
    if (elementIndex.hasAttribute('data-autoread-item')) {
      autoreadItem = parseInt(elementIndex.getAttribute('data-autoread-item'), 10);
      autoreadItem = (autoreadItem === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autoread-page')) {
      autoreadPage = parseInt(elementIndex.getAttribute('data-autoread-page'), 10);
      autoreadPage = (autoreadPage === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autohide')) {
      autohide = parseInt(elementIndex.getAttribute('data-autohide'), 10);
      autohide = (autohide === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autofocus')) {
      autofocus = parseInt(elementIndex.getAttribute('data-autofocus'), 10);
      autofocus = (autofocus === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autoupdate')) {
      autoupdate = parseInt(elementIndex.getAttribute('data-autoupdate'), 10);
      autoupdate = (autoupdate === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-by-page')) {
      byPage = parseInt(elementIndex.getAttribute('data-by-page'), 10);
    }
    if (elementIndex.hasAttribute('data-shaarli')) {
      shaarli = elementIndex.getAttribute('data-shaarli');
    }
    if (elementIndex.hasAttribute('data-redirector')) {
      redirector = elementIndex.getAttribute('data-redirector');
    }
    if (elementIndex.hasAttribute('data-current-hash')) {
      currentHash = elementIndex.getAttribute('data-current-hash');
    }
    if (elementIndex.hasAttribute('data-current-page')) {
      currentPage = parseInt(elementIndex.getAttribute('data-current-page'), 10);
    }
    if (elementIndex.hasAttribute('data-nb-items')) {
      currentNbItems = parseInt(elementIndex.getAttribute('data-nb-items'), 10);
    }
    if (elementIndex.hasAttribute('data-add-favicon')) {
      addFavicon = parseInt(elementIndex.getAttribute('data-add-favicon'), 10);
      addFavicon = (addFavicon === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-preload')) {
      preload = parseInt(elementIndex.getAttribute('data-preload'), 10);
      preload = (preload === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-stars')) {
      stars = parseInt(elementIndex.getAttribute('data-stars'), 10);
      stars = (stars === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-blank')) {
      blank = parseInt(elementIndex.getAttribute('data-blank'), 10);
      blank = (blank === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-is-logged')) {
      isLogged = parseInt(elementIndex.getAttribute('data-is-logged'), 10);
      isLogged = (isLogged === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-intl-top')) {
      intlTop = elementIndex.getAttribute('data-intl-top');
    }
    if (elementIndex.hasAttribute('data-intl-share')) {
      intlShare = elementIndex.getAttribute('data-intl-share');
    }
    if (elementIndex.hasAttribute('data-intl-read')) {
      intlRead = elementIndex.getAttribute('data-intl-read');
    }
    if (elementIndex.hasAttribute('data-intl-unread')) {
      intlUnread = elementIndex.getAttribute('data-intl-unread');
    }
    if (elementIndex.hasAttribute('data-intl-star')) {
      intlStar = elementIndex.getAttribute('data-intl-star');
    }
    if (elementIndex.hasAttribute('data-intl-unstar')) {
      intlUnstar = elementIndex.getAttribute('data-intl-unstar');
    }
    if (elementIndex.hasAttribute('data-intl-from')) {
      intlFrom = elementIndex.getAttribute('data-intl-from');
    }

    status = document.getElementById('status').innerHTML;
  }

  function initKF() {
    var listItems,
        listLinkFolders = [],
        listLinkItems = [];

    initOptions();

    listLinkFolders = getListLinkFolders();
    listLinkItems = getListLinkItems();
    if (!window.jQuery || (window.jQuery && !window.jQuery().collapse)) {
      document.getElementById('menu-toggle'). onclick = collapseClick;
      initCollapse(listLinkFolders);
      initCollapse(listLinkItems);
    }
    initLinkFolders(listLinkFolders);
    initLinkItems(listLinkItems);

    initListItemsHash();
    initListItems();

    initUnread();

    initItemButton();
    initPageButton();

    initAnonyme();

    addEvent(window, 'keydown', checkKey);
    addEvent(window, 'touchstart', checkMove);

    if (autoupdate && !stars) {
      initUpdate();
    }

    listItems = getListItems();
    listItems.focus();
  }

  //http://scottandrew.com/weblog/articles/cbs-events
  function addEvent(obj, evType, fn, useCapture) {
    if (obj.addEventListener) {
      obj.addEventListener(evType, fn, useCapture);
    } else {
      if (obj.attachEvent) {
        obj.attachEvent('on' + evType, fn);
      } else {
        window.alert('Handler could not be attached');
      }
    }
  }

  function removeEvent(obj, evType, fn, useCapture) {
    if (obj.removeEventListener) {
      obj.removeEventListener(evType, fn, useCapture);
    } else if (obj.detachEvent) {
      obj.detachEvent("on"+evType, fn);
    } else {
      alert("Handler could not be removed");
    }
  }

  // when document is loaded init KrISS feed
  if (document.getElementById && document.createTextNode) {
    addEvent(window, 'load', initKF);
  }

  window.checkKey = checkKey;
  window.removeEvent = removeEvent;
  window.addEvent = addEvent;
})();

// unread count for favicon part
if(typeof GM_getValue == 'undefined') {
	GM_getValue = function(name, fallback) {
		return fallback;
	};
}

// Register GM Commands and Methods
if(typeof GM_registerMenuCommand !== 'undefined') {
  var setOriginalFavicon = function(val) { GM_setValue('originalFavicon', val); };
	GM_registerMenuCommand( 'GReader Favicon Alerts > Use Current Favicon', function() { setOriginalFavicon(false); } );
	GM_registerMenuCommand( 'GReader Favicon Alerts > Use Original Favicon', function() { setOriginalFavicon(true); } );
}

(function FaviconAlerts() {
	var self = this;

	this.construct = function() {
		this.head = document.getElementsByTagName('head')[0];
		this.pixelMaps = {numbers: {0:[[1,1,1],[1,0,1],[1,0,1],[1,0,1],[1,1,1]],1:[[0,1,0],[1,1,0],[0,1,0],[0,1,0],[1,1,1]],2:[[1,1,1],[0,0,1],[1,1,1],[1,0,0],[1,1,1]],3:[[1,1,1],[0,0,1],[0,1,1],[0,0,1],[1,1,1]],4:[[0,0,1],[0,1,1],[1,0,1],[1,1,1],[0,0,1]],5:[[1,1,1],[1,0,0],[1,1,1],[0,0,1],[1,1,1]],6:[[0,1,1],[1,0,0],[1,1,1],[1,0,1],[1,1,1]],7:[[1,1,1],[0,0,1],[0,0,1],[0,1,0],[0,1,0]],8:[[1,1,1],[1,0,1],[1,1,1],[1,0,1],[1,1,1]],9:[[1,1,1],[1,0,1],[1,1,1],[0,0,1],[1,1,0]],'+':[[0,0,0],[0,1,0],[1,1,1],[0,1,0],[0,0,0]],'k':[[1,0,1],[1,1,0],[1,1,0],[1,0,1],[1,0,1]]}};

		this.timer = setInterval(this.poll, 500);
		this.poll();

		return true;
	};

	this.drawUnreadCount = function(unread, callback) {
		if(!self.textedCanvas) {
			self.textedCanvas = [];
		}

		if(!self.textedCanvas[unread]) {
			self.getUnreadCanvas(function(iconCanvas) {
				var textedCanvas = document.createElement('canvas');
				textedCanvas.height = textedCanvas.width = iconCanvas.width;
				var ctx = textedCanvas.getContext('2d');
				ctx.drawImage(iconCanvas, 0, 0);

				ctx.fillStyle = '#b7bfc9';
				ctx.strokeStyle = '#7792ba';
				ctx.strokeWidth = 1;

				var count = unread.length;

				if(count > 4) {
					unread = '1k+';
					count = unread.length;
				}

				var bgHeight = self.pixelMaps.numbers[0].length;
				var bgWidth = 0;
				var padding = count < 4 ? 1 : 0;
				var topMargin = 0;

				for(var index = 0; index < count; index++) {
					bgWidth += self.pixelMaps.numbers[unread[index]][0].length;
					if(index < count-1) {
						bgWidth += padding;
					}
				}
				bgWidth = bgWidth > textedCanvas.width-4 ? textedCanvas.width-4 : bgWidth;

				ctx.fillRect(textedCanvas.width-bgWidth-4,topMargin,bgWidth+4,bgHeight+4);

				var digit;
				var digitsWidth = bgWidth;
				for(index = 0; index < count; index++) {
					digit = unread[index];

					if (self.pixelMaps.numbers[digit]) {
						var map = self.pixelMaps.numbers[digit];
						var height = map.length;
						var width = map[0].length;

						ctx.fillStyle = '#2c3323';

						for (var y = 0; y < height; y++) {
							for (var x = 0; x < width; x++) {
								if(map[y][x]) {
									ctx.fillRect(14- digitsWidth + x, y+topMargin+2, 1, 1);
								}
							}
						}

						digitsWidth -= width + padding;
					}
				}

				ctx.strokeRect(textedCanvas.width-bgWidth-3.5,topMargin+0.5,bgWidth+3,bgHeight+3);

				self.textedCanvas[unread] = textedCanvas;

				callback(self.textedCanvas[unread]);
			});
      callback(self.textedCanvas[unread]);
		}
	};
	this.getIcon = function(callback) {
		self.getUnreadCanvas(function(canvas) {
			callback(canvas.toDataURL('image/png'));
		});
	};
  this.getIconSrc = function() {
    var links = document.getElementsByTagName('link');
    for (var i = 0; i < links.length; i++) {
      if (links[i].rel === 'icon') {
        return links[i].href;
      }
    }
    return false;
  };
	this.getUnreadCanvas = function(callback) {
		if(!self.unreadCanvas) {
			self.unreadCanvas = document.createElement('canvas');
			self.unreadCanvas.height = self.unreadCanvas.width = 16;

			var ctx = self.unreadCanvas.getContext('2d');
			var img = new Image();

			img.addEventListener('load', function() {
				ctx.drawImage(img, 0, 0);
				callback(self.unreadCanvas);
			}, true);

		//	if(GM_getValue('originalFavicon', false)) {
		//		img.src = self.icons.original;
		//	} else {
		//		img.src = self.icons.current;
		//	}
		// img.src = 'inc/favicon.ico';
                  img.src = self.getIconSrc();
		} else {
			callback(self.unreadCanvas);
		}
	};
	this.getUnreadCount = function() {
		matches = self.getSearchText().match(/\((.*)\)/);
		return matches ? matches[1] : false;
	};
	this.getUnreadCountIcon = function(callback) {
		var unread = self.getUnreadCount();
    self.drawUnreadCount(unread, function(icon) {
      if(icon) {
        callback(icon.toDataURL('image/png'));
      }
    });
	};
	this.getSearchText = function() {
		var Nbunread = 'Kriss feed (' + parseInt(document.getElementById('nb-unread').innerHTML, 10) + ')' ;
		return Nbunread;
	};
	this.poll = function() {
		if(self.getUnreadCount() != "0") {
			self.getUnreadCountIcon(function(icon) {
				self.setIcon(icon);
			});
		} else {
			self.getIcon(function(icon) {
				self.setIcon(icon);
			});
		}
	};

	this.setIcon = function(icon) {
		var links = self.head.getElementsByTagName('link');
		for (var i = 0; i < links.length; i++)
			if ((links[i].rel == 'shortcut icon' || links[i].rel=='icon') &&
        links[i].href != icon)
				self.head.removeChild(links[i]);
			else if(links[i].href == icon)
				return;

		var newIcon = document.createElement('link');
		newIcon.type = 'image/png';
		newIcon.rel = 'shortcut icon';
		newIcon.href = icon;
		self.head.appendChild(newIcon);

		// Chrome hack for updating the favicon
		var shim = document.createElement('iframe');
		shim.width = shim.height = 0;
		document.body.appendChild(shim);
		shim.src = 'icon';
		document.body.removeChild(shim);
	};

	this.toString = function() { return '[object FaviconAlerts]'; };

	return this.construct();
}());
<?php
    }
    exit();
}

$pb = new PageBuilder('FeedPage');
$base = BASE_URL;
if (empty($base)) {
    $base = MyTool::getUrl();
}
$pb->assign('base', $base);
$pb->assign('version', FEED_VERSION);
$pb->assign('pagetitle', 'KrISS feed');
$pb->assign('referer', $referer);

if (!is_dir(DATA_DIR)) {
    if (!@mkdir(DATA_DIR, 0755)) {
        $pb->assign('message', sprintf(Intl::msg('Can not create %s directory, check permissions'), DATA_DIR));
        $pb->renderPage('message');
    }
    @chmod(DATA_DIR, 0755);
    if (!is_file(DATA_DIR.'/.htaccess')) {
        if (!@file_put_contents(
            DATA_DIR.'/.htaccess',
            "Allow from none\nDeny from all\n"
        )) {
            $pb->assign('message', sprintf(Intl::msg('Can not protect %s directory with .htaccess, check permissions'), DATA_DIR));
            $pb->renderPage('message');
        }
    }
}

// XSRF protection with token
if (!empty($_POST)) {
    if (!Session::isToken($_POST['token'])) {
        $pb->assign('message', Intl::msg('Wrong token'));
        $pb->renderPage('message');
    }
    unset($_SESSION['tokens']);
}

$kfc = new FeedConf(CONFIG_FILE, FEED_VERSION);
$kf = new Feed(DATA_FILE, CACHE_DIR, $kfc);
$ks = new Star(STAR_FILE, ITEM_FILE, $kfc);

$kfp = new FeedPage(STYLE_FILE);

// List or Expanded ?
$view = $kfc->view;
// show or hide list of feeds ?
$listFeeds =  $kfc->listFeeds;
// All or Unread ?
$filter =  $kfc->filter;
// newerFirst or olderFirst
$order =  $kfc->order;
// number of item by page
$byPage = $kfc->byPage;
// Hash : 'all', feed hash or folder hash
$currentHash = $kfc->getCurrentHash();
// Query
$query = '?';
if (isset($_GET['stars'])) {
    $query .= 'stars&';
}
if (!empty($currentHash) and $currentHash !== 'all') {
    $query .= 'currentHash='.$currentHash.'&';
}

$pb->assign('view', $view);
$pb->assign('listFeeds', $listFeeds);
$pb->assign('filter', $filter);
$pb->assign('order', $order);
$pb->assign('byPage', $byPage);
$pb->assign('currentHash', $currentHash);
$pb->assign('query', htmlspecialchars($query));
$pb->assign('redirector', $kfc->redirector);
$pb->assign('shaarli', htmlspecialchars($kfc->shaarli));
$pb->assign('autoreadItem', $kfc->autoreadItem);
$pb->assign('autoreadPage', $kfc->autoreadPage);
$pb->assign('autohide', $kfc->autohide);
$pb->assign('autofocus', $kfc->autofocus);
$pb->assign('autoupdate', $kfc->autoUpdate);
$pb->assign('addFavicon', $kfc->addFavicon);
$pb->assign('preload', $kfc->preload);
$pb->assign('blank', $kfc->blank);
$pb->assign('kf', $kf);
$pb->assign('isLogged', $kfc->isLogged());
$pb->assign('pagetitle', strip_tags($kfc->title));

if (isset($_GET['login'])) {
    // Login
    if (!empty($_POST['login'])
        && !empty($_POST['password'])
    ) {
        if (Session::login(
            $kfc->login,
            $kfc->hash,
            $_POST['login'],
            sha1($_POST['password'].$_POST['login'].$kfc->salt)
        )) {
            if (!empty($_POST['longlastingsession'])) {
                // (31536000 seconds = 1 year)
                $_SESSION['longlastingsession'] = 31536000;
                $_SESSION['expires_on'] =
                    time() + $_SESSION['longlastingsession'];
                session_set_cookie_params($_SESSION['longlastingsession']);
            } else {
                session_set_cookie_params(0); // when browser closes
            }
            session_regenerate_id(true);

            MyTool::redirect();
        }
        $pb->assign('message', Intl::msg('Login failed!'));
        $pb->renderPage('message');
    } else {
        $pb->assign('pagetitle', Intl::msg('Sign in').' - '.strip_tags($kfc->title));
        $pb->renderPage('login');
    }
} elseif (isset($_GET['logout'])) {
    //Logout
    Session::logout();
    MyTool::redirect();
} elseif (isset($_GET['password']) && $kfc->isLogged()) {
    if (isset($_POST['save'])) {
        if ($kfc->hash === sha1($_POST['oldpassword'].$kfc->login.$kfc->salt)) {
            $kfc->setHash($_POST['newpassword']);
            $kfc->write();
            MyTool::redirect();
        }
    } elseif (isset($_POST['cancel'])) {
        MyTool::redirect();
    }
    $pb->assign('pagetitle', Intl::msg('Change your password').' - '.strip_tags($kfc->title));
    $pb->renderPage('changePassword');
} elseif (isset($_GET['ajax'])) {
    if (isset($_GET['stars'])) {
        $filter = 'all';
        $kf = $ks;
    }
    $kf->loadData();
    $needSave = false;
    $needStarSave = false;
    $result = array();
    if (!$kfc->isLogged()) {
        $result['logout'] = true;
    }
    if (isset($_GET['current'])) {
        $result['item'] = $kf->getItem($_GET['current'], false);
        $result['item']['itemHash'] = $_GET['current'];
    }
    if (isset($_GET['read'])) {
        $needSave = $kf->mark($_GET['read'], 1);
        if ($needSave && $kfc->isLogged()) {
            $result['read'] = $_GET['read'];
        }
    }
    if (isset($_GET['unread'])) {
        $needSave = $kf->mark($_GET['unread'], 0);
        if ($needSave && $kfc->isLogged()) {
            $result['unread'] = $_GET['unread'];
        }
    }
    if (isset($_GET['star']) && !isset($_GET['stars'])) {
        $hash = $_GET['star'];
        $item = $kf->loadItem($hash, false);
        $feed = $kf->getFeed(substr($hash, 0, 6));

        $ks->loadData();
        $needStarSave = $ks->markItem($_GET['star'], 1, $feed, $item);
        if ($needStarSave) {
            $result['star'] = $hash;
        }
    }
    if (isset($_GET['unstar'])) {
        $hash = $_GET['unstar'];

        $ks->loadData();
        $needStarSave = $ks->markItem($hash, 0);
        if ($needStarSave) {
            $result['unstar'] = $hash;
        }
    }
    if (isset($_GET['toggleFolder'])) {
        $needSave = $kf->toggleFolder($_GET['toggleFolder']);
    }
    if (isset($_GET['page'])) {
        $listItems = $kf->getItems($currentHash, $filter);
        $currentPage = $_GET['page'];
        $index = ($currentPage - 1) * $byPage;
        $results = array_slice($listItems, $index, $byPage + 1, true);
        $result['page'] = array();
        $firstIndex = -1;
        if (isset($_GET['last'])) {
            $firstIndex = array_search($_GET['last'], array_keys($results));
            if ($firstIndex === false) {
                $firstIndex = -1;
            }
        }
        $i = 0;
        foreach (array_slice($results, $firstIndex + 1, count($results) - $firstIndex - 1, true) as $itemHash => $item) {
            if (isset($_GET['stars'])) {
                $result['page'][$i] = $kf->getItem($itemHash);
            } else {
                $result['page'][$i] = $kf->getItem($itemHash, false);
                $result['page'][$i]['read'] = $item[1];
            }
            $i++;
        }
    }
    if (isset($_GET['update'])) {
        if ($kfc->isLogged()) {
            if (empty($_GET['update'])) {
                $result['update']['feeds'] = array();
                $feedsHash = $kf->orderFeedsForUpdate(array_keys($kf->getFeeds()));
                foreach ($feedsHash as $feedHash) {
                    $feed = $kf->getFeed($feedHash);
                    $result['update']['feeds'][] = array($feedHash, $feed['title'], (int) ((time() - $feed['lastUpdate']) / 60), $kf->getTimeUpdate($feed));
                }
            } else {
                $feed = $kf->getFeed($_GET['update']);
                $info = $kf->updateChannel($_GET['update']);
                if (empty($info['error'])) {
                    $info['error'] = $feed['description'];
                }
                $info['newItems'] = array_keys($info['newItems']);
                $result['update'] = $info;
            }
        } else {
            $result['update'] = false;
        }
    }
    if ($needSave) {
        $kf->writeData();
    }
    if ($needStarSave) {
        $ks->writeData();
    }
    MyTool::renderJson($result);
} elseif (isset($_GET['help']) && ($kfc->isLogged() || $kfc->visibility === 'protected')) {
    $pb->assign('pagetitle', Intl::msg('Help').' - '.strip_tags($kfc->title));
    $pb->renderPage('help');
} elseif ((isset($_GET['update'])
          && ($kfc->isLogged()
              || (isset($_GET['cron'])
                  && $_GET['cron'] === sha1($kfc->salt.$kfc->hash))))
          || (isset($argv)
              && count($argv) >= 3
              && $argv[1] == 'update'
              && $argv[2] == sha1($kfc->salt.$kfc->hash))) {
    // Update
    $kf->loadData();
    $forceUpdate = false;
    if (isset($_GET['force'])) {
        $forceUpdate = true;
    }
    $feedsHash = array();
    $hash = 'all';
    if (isset($_GET['update'])) {
        $hash = $_GET['update'];
    }
    // type : 'feed', 'folder', 'all', 'item'
    $type = $kf->hashType($hash);
    switch($type) {
    case 'feed':
        $feedsHash[] = $hash;
        break;
    case 'folder':
        $feedsHash = $kf->getFeedsHashFromFolderHash($hash);
        break;
    case 'all':
    case '':
        $feedsHash = array_keys($kf->getFeeds());
        break;
    case 'item':
    default:
        break;
    }

    $pb->assign('currentHash', $hash);
    if (isset($_GET['cron']) || isset($argv) && count($argv) >= 3) {
        $kf->updateFeedsHash($feedsHash, $forceUpdate);
    } else {
        $pb->assign('feedsHash', $feedsHash);
        $pb->assign('forceUpdate', $forceUpdate);
        $pb->assign('pagetitle', Intl::msg('Update').' - '.strip_tags($kfc->title));
        $pb->renderPage('update');
    }
} elseif (isset($_GET['plugins']) && $kfc->isLogged()) {
    $pb->assign('pagetitle', Intl::msg('Plugins management').' - '.strip_tags($kfc->title));
    $pb->renderPage('plugins');
} elseif (isset($_GET['config']) && $kfc->isLogged()) {
    // Config
    if (isset($_POST['save'])) {
        if (isset($_POST['disableSessionProtection'])) {
            $_POST['disableSessionProtection'] = '1';
        } else {
            $_POST['disableSessionProtection'] = '0';
        }
        $kfc->hydrate($_POST);
        MyTool::redirect();
    } elseif (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $menu = $kfc->getMenu();
        $paging = $kfc->getPaging();

        $pb->assign('page', 'config');
        $pb->assign('pagetitle', Intl::msg('Configuration').' - '.strip_tags($kfc->title));
        $pb->assign('kfctitle', htmlspecialchars($kfc->title));
        $pb->assign('kfcredirector', htmlspecialchars($kfc->redirector));
        $pb->assign('kfcshaarli', htmlspecialchars($kfc->shaarli));
        $pb->assign('kfclocale', htmlspecialchars($kfc->locale));
        $pb->assign('kfcmaxitems', htmlspecialchars($kfc->maxItems));
        $pb->assign('kfcmaxupdate', htmlspecialchars($kfc->maxUpdate));
        $pb->assign('kfcvisibility', htmlspecialchars($kfc->visibility));
        $pb->assign('kfccron', sha1($kfc->salt.$kfc->hash));
        $pb->assign('kfcautoreaditem', (int) $kfc->autoreadItem);
        $pb->assign('kfcautoreadpage', (int) $kfc->autoreadPage);
        $pb->assign('kfcautoupdate', (int) $kfc->autoUpdate);
        $pb->assign('kfcautohide', (int) $kfc->autohide);
        $pb->assign('kfcautofocus', (int) $kfc->autofocus);
        $pb->assign('kfcaddfavicon', (int) $kfc->addFavicon);
        $pb->assign('kfcpreload', (int) $kfc->preload);
        $pb->assign('kfcblank', (int) $kfc->blank);
        $pb->assign('kfcdisablesessionprotection', (int) $kfc->disableSessionProtection);
        $pb->assign('kfcmenu', $menu);
        $pb->assign('kfcpaging', $paging);

        $pb->renderPage('config');
    }
} elseif (isset($_GET['import']) && $kfc->isLogged()) {
    // Import
    if (isset($_POST['import'])) {
        // If file is too big, some form field may be missing.
        if ((!isset($_FILES))
            || (isset($_FILES['filetoupload']['size'])
            && $_FILES['filetoupload']['size']==0)
        ) {
            $rurl = empty($_SERVER['HTTP_REFERER'])
                ? '?'
                : $_SERVER['HTTP_REFERER'];

            $pb->assign('message', sprintf(Intl::msg('The file you are trying to upload is probably bigger than what this webserver can accept (%s). Please upload in smaller chunks.'), MyTool::humanBytes(MyTool::getMaxFileSize())));
            $pb->assign('referer', $rurl);
            $pb->renderPage('message');
        }

        $kf->loadData();
        $kf->setData(Opml::importOpml($kf->getData()));
        $kf->sortFeeds();
        $kf->writeData();
        exit;
    } else if (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $pb->assign('pagetitle', Intl::msg('Import').' - '.strip_tags($kfc->title));
        $pb->renderPage('import');
    }
} elseif (isset($_GET['export']) && $kfc->isLogged()) {
    // Export
    $kf->loadData();
    Opml::exportOpml($kf->getFeeds(), $kf->getFolders());
} elseif (isset($_GET['add']) && $kfc->isLogged()) {
    // Add feed
    $kf->loadData();

    if (isset($_POST['newfeed']) && !empty($_POST['newfeed'])) {
        $addc = $kf->addChannel($_POST['newfeed']);
        if (empty($addc['error'])) {
            // Add success
            $folders = array();
            if (!empty($_POST['folders'])) {
                foreach ($_POST['folders'] as $hashFolder) {
                    $folders[] = $hashFolder;
                }
            }
            if (!empty($_POST['newfolder'])) {
                $newFolderHash = MyTool::smallHash($_POST['newfolder']);
                $kf->addFolder($_POST['newfolder'], $newFolderHash);
                $folders[] = $newFolderHash;
            }
            $hash = MyTool::smallHash($_POST['newfeed']);
            $kf->editFeed($hash, '', '', $folders, '', '');
            $kf->sortFeeds();
            $kf->writeData();
            MyTool::redirect('?currentHash='.$hash);
        } else {
            // Add fail
            $pb->assign('message', $addc['error']);
            $pb->renderPage('message');
        }
    }

    $newfeed = '';
    if (isset($_GET['newfeed'])) {
        $newfeed = htmlspecialchars($_GET['newfeed']);
    }
    $pb->assign('page', 'add');
    $pb->assign('pagetitle', Intl::msg('Add a new feed').' - '.strip_tags($kfc->title));
    $pb->assign('newfeed', $newfeed);
    $pb->assign('folders', $kf->getFolders());

    $pb->renderPage('addFeed');
} elseif (isset($_GET['toggleFolder']) && $kfc->isLogged()) {
    $kf->loadData();
    $kf->toggleFolder($_GET['toggleFolder']);
    $kf->writeData();

    MyTool::redirect($query);
} elseif ((isset($_GET['read'])
           || isset($_GET['unread']))
          && $kfc->isLogged()) {
    // mark all as read : item, feed, folder, all
    $kf->loadData();

    $read = 1;
    if (isset($_GET['read'])) {
        $hash = $_GET['read'];
        $read = 1;
    } else {
        $hash = $_GET['unread'];
        $read = 0;
    }

    $needSave = $kf->mark($hash, $read);
    if ($needSave) {
        $kf->writeData();
    }

    // type : 'feed', 'folder', 'all', 'item'
    $type = $kf->hashType($hash);
    if ($type === 'item') {
        $query .= 'current='.$hash;
    } else {
        if ($filter === 'unread' && $read === 1) {
            $query = '?';
        }
    }
    MyTool::redirect($query);
} elseif ((isset($_GET['star'])
           || isset($_GET['unstar']))
          && $kfc->isLogged()) {
    // mark all as starred : item, feed, folder, all
    $kf->loadData();
    $ks->loadData();

    $starred = 1;
    if (isset($_GET['star'])) {
        $hash = $_GET['star'];
        $starred = 1;

        $item = $kf->loadItem($hash, false);
        $feed = $kf->getFeed(substr($hash, 0, 6));

        $needSave = $ks->markItem($hash, $starred, $feed, $item);
    } else {
        $hash = $_GET['unstar'];
        $starred = 0;

        $needSave = $ks->markItem($hash, $starred);
    }
    if ($needSave) {
        $ks->writeData();
    }

    // type : 'feed', 'folder', 'all', 'item'
    $type = $kf->hashType($hash);

    if ($type === 'item') {
        $query .= 'current='.$hash;
    }
    MyTool::redirect($query);
} elseif (isset($_GET['stars']) && $kfc->isLogged()) {
    $ks->loadData();
    $listItems = $ks->getItems($currentHash, 'all');
    $listHash = array_keys($listItems);
    $currentItemHash = '';
    if (isset($_GET['current']) && !empty($_GET['current'])) {
        $currentItemHash = $_GET['current'];
    }
    if (isset($_GET['next']) && !empty($_GET['next'])) {
        $currentItemHash = $_GET['next'];
    }
    if (isset($_GET['previous']) && !empty($_GET['previous'])) {
        $currentItemHash = $_GET['previous'];
    }
    if (empty($currentItemHash)) {
        $currentPage = $kfc->getCurrentPage();
        $index = ($currentPage - 1) * $byPage;
    } else {
        $index = array_search($currentItemHash, $listHash);
        if (isset($_GET['next'])) {
            if ($index < count($listHash)-1) {
                $index++;
            }
        }

        if (isset($_GET['previous'])) {
            if ($index > 0) {
                $index--;
            }
        }
    }

    if ($index < count($listHash)) {
        $currentItemHash = $listHash[$index];
    } else {
        $index = count($listHash) - 1;
    }

    // pagination
    $currentPage = (int) ($index/$byPage)+1;
    if ($currentPage <= 0) {
        $currentPage = 1;
    }
    $begin = ($currentPage - 1) * $byPage;
    $maxPage = (count($listItems) <= $byPage) ? '1' : ceil(count($listItems) / $byPage);
    $nbItems = count($listItems);

    // list items
    $listItems = array_slice($listItems, $begin, $byPage, true);

    // type : 'feed', 'folder', 'all', 'item'
    $currentHashType = $kf->hashType($currentHash);
    $hashView = '';
    switch($currentHashType){
    case 'all':
        $hashView = '<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> '.Intl::msg('starred items').'</span>';
        break;
    case 'feed':
        $hashView = 'Feed (<a href="'.$kf->getFeedHtmlUrl($currentHash).'" title="">'.$kf->getFeedTitle($currentHash).'</a>): '.'<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> '.Intl::msg('starred items').'</span>';
        break;
    case 'folder':
        $hashView = 'Folder ('.$kf->getFolderTitle($currentHash).'): <span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> '.Intl::msg('starred items').'</span>';
        break;
    default:
        $hashView = '<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> '.Intl::msg('starred items').'</span>';
        break;
    }

    $menu = $kfc->getMenu();
    $paging = $kfc->getPaging();

    $pb->assign('menu', $menu);
    $pb->assign('paging', $paging);
    $pb->assign('currentHashType', $currentHashType);
    $pb->assign('currentHashView', $hashView);
    $pb->assign('currentPage', (int) $currentPage);
    $pb->assign('maxPage', (int) $maxPage);
    $pb->assign('currentItemHash', $currentItemHash);
    $pb->assign('nbItems', $nbItems);
    $pb->assign('items', $listItems);
    if ($listFeeds == 'show') {
        $pb->assign('feedsView', $ks->getFeedsView());
    }
    $pb->assign('kf', $ks);
    $pb->assign('pagetitle', Intl::msg('Starred items').' - '.strip_tags($kfc->title));
    $pb->renderPage('index');
} elseif (isset($_GET['edit']) && $kfc->isLogged()) {
    // Edit feed, folder, all
    $kf->loadData();
    $pb->assign('page', 'edit');
    $pb->assign('pagetitle', Intl::msg('Edit').' - '.strip_tags($kfc->title));

    $hash = substr(trim($_GET['edit'], '/'), 0, 6);
    // type : 'feed', 'folder', 'all', 'item'
    $type = $kf->hashType($currentHash);
    $type = $kf->hashType($hash);
    switch($type) {
    case 'feed':
        if (isset($_POST['save'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $htmlUrl = $_POST['htmlUrl'];
            $folders = array();
            if (!empty($_POST['folders'])) {
                foreach ($_POST['folders'] as $hashFolder) {
                    $folders[] = $hashFolder;
                }
            }
            if (!empty($_POST['newfolder'])) {
                $newFolderHash = MyTool::smallHash($_POST['newfolder']);
                $kf->addFolder($_POST['newfolder'], $newFolderHash);
                $folders[] = $newFolderHash;
            }
            $timeUpdate = $_POST['timeUpdate'];

            $kf->editFeed($hash, $title, $description, $folders, $timeUpdate, $htmlUrl);
            $kf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['delete'])) {
            $kf->removeFeed($hash);
            $kf->writeData();

            MyTool::redirect('?');
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $feed = $kf->getFeed($hash);
            if (!empty($feed)) {
                $lastUpdate = 'need update';
                if (!$kf->needUpdate($feed)) {
                    $diff = (int) (time() - $feed['lastUpdate']);
                    $lastUpdate =
                        (int) ($diff / 60) . ' m ' . (int) ($diff % 60) . ' s';
                }

                $pb->assign('feed', $feed);
                $pb->assign('folders', $kf->getFolders());
                $pb->assign('lastUpdate', $lastUpdate);
                $pb->renderPage('editFeed');
            } else {
                MyTool::redirect();
            }
        }
        break;
    case 'folder':
        if (isset($_POST['save'])) {
            $oldFolderTitle = $kf->getFolderTitle($hash);
            $newFolderTitle = $_POST['foldertitle'];
            if ($oldFolderTitle !== $newFolderTitle) {
                $kf->renameFolder($hash, $newFolderTitle);
                $kf->writeData();
            }

            if (empty($newFolderTitle)) {
                MyTool::redirect('?');
            } else {
                MyTool::redirect('?currentHash='.MyTool::smallHash($newFolderTitle));
            }
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $folderTitle = $kf->getFolderTitle($hash);
            $pb->assign('foldertitle', htmlspecialchars($folderTitle));
            $pb->renderPage('editFolder');
        }
        break;
    case 'all':
        if (isset($_POST['save'])) {

            foreach (array_keys($_POST) as $key) {
                if (strpos($key, 'order-folder-') !== false) {
                    $folderHash = str_replace('order-folder-', '', $key);
                    $kf->orderFolder($folderHash, (int) $_POST[$key]);
                }
            }

            $feedsHash = array();
            if (isset($_POST['feeds'])) {
                foreach ($_POST['feeds'] as $feedHash) {
                    $feedsHash[] = $feedHash;
                }
            }

            foreach ($feedsHash as $feedHash) {
                $feed = $kf->getFeed($feedHash);
                $addFoldersHash = $feed['foldersHash'];
                if (!empty($_POST['addfolders'])) {
                    foreach ($_POST['addfolders'] as $folderHash) {
                        if (!in_array($folderHash, $addFoldersHash)) {
                            $addFoldersHash[] = $folderHash;
                        }
                    }
                }
                if (!empty($_POST['addnewfolder'])) {
                    $newFolderHash = MyTool::smallHash($_POST['addnewfolder']);
                    $kf->addFolder($_POST['addnewfolder'], $newFolderHash);
                    $addFoldersHash[] = $newFolderHash;
                }
                $removeFoldersHash = array();
                if (!empty($_POST['removefolders'])) {
                    foreach ($_POST['removefolders'] as $folderHash) {
                        $removeFoldersHash[] = $folderHash;
                    }
                }
                $addFoldersHash = array_diff($addFoldersHash, $removeFoldersHash);

                $kf->editFeed($feedHash, '', '', $addFoldersHash, '', '');
            }
            $kf->sortFolders();
            $kf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['delete'])) {
            foreach ($_POST['feeds'] as $feedHash) {
                $kf->removeFeed($feedHash);
            }
            $kf->writeData();

            MyTool::redirect();
        } elseif (isset($_POST['cancel'])) {
            MyTool::redirect();
        } else {
            $folders = $kf->getFolders();
            $listFeeds = $kf->getFeeds();
            $pb->assign('folders', $folders);
            $pb->assign('listFeeds', $listFeeds);
            $pb->renderPage('editAll');
        }
        break;
    case 'item':
    default:
        MyTool::redirect();
        break;
    }
} elseif (isset($_GET['shaarli'])) {
    $kf->loadData();
    $item = $kf->getItem($_GET['shaarli'], false);
    $shaarli = $kfc->shaarli;
    if (!empty($shaarli)) {
        // remove sel used with javascript
        $shaarli = str_replace('${sel}', '', $shaarli);

        $url = htmlspecialchars_decode($item['link']);
        $via = htmlspecialchars_decode($item['via']);
        $title = htmlspecialchars_decode($item['title']);

        if (parse_url($url, PHP_URL_HOST) !== parse_url($via, PHP_URL_HOST)) {
            $via = 'via '.$via;
        } else {
            $via = '';
        }

        $shaarli = str_replace('${url}', urlencode($url), $shaarli);
        $shaarli = str_replace('${title}', urlencode($title), $shaarli);
        $shaarli = str_replace('${via}', urlencode($via), $shaarli);

        header('Location: '.$shaarli);
    } else {
        $pb->assign('message', Intl::msg('Please configure your share link first'));
        $pb->renderPage('message');
    }
} else {
    if (($kfc->isLogged() || $kfc->visibility === 'protected') && !isset($_GET['password']) && !isset($_GET['help']) && !isset($_GET['update']) && !isset($_GET['config']) && !isset($_GET['import']) && !isset($_GET['export']) && !isset($_GET['add']) && !isset($_GET['toggleFolder']) && !isset($_GET['read']) && !isset($_GET['unread']) && !isset($_GET['edit'])) {
        $kf->loadData();
        if ($kf->updateItems()) {
            $kf->writeData();
        }

        $listItems = $kf->getItems($currentHash, $filter);
        $listHash = array_keys($listItems);

        $currentItemHash = '';
        if (isset($_GET['current']) && !empty($_GET['current'])) {
            $currentItemHash = $_GET['current'];
        }
        if (isset($_GET['next']) && !empty($_GET['next'])) {
            $currentItemHash = $_GET['next'];
            if ($kfc->autoreadItem) {
                if ($kf->mark($currentItemHash, 1)) {
                    if ($filter == 'unread') {
                        unset($listItems[$currentItemHash]);
                    }
                    $kf->writeData();
                }
            }
        }
        if (isset($_GET['previous']) && !empty($_GET['previous'])) {
            $currentItemHash = $_GET['previous'];
        }
        if (empty($currentItemHash)) {
            $currentPage = $kfc->getCurrentPage();
            $index = ($currentPage - 1) * $byPage;
        } else {
            $index = array_search($currentItemHash, $listHash);
            if (isset($_GET['next'])) {
                if ($index < count($listHash)-1) {
                    $index++;
                }
            }

            if (isset($_GET['previous'])) {
                if ($index > 0) {
                    $index--;
                }
            }
        }
        if ($index < count($listHash)) {
            $currentItemHash = $listHash[$index];
        } else {
            $index = count($listHash) - 1;
        }

        $unread = 0;
        foreach ($listItems as $itemHash => $item) {
            if ($item[1] === 0) {
                $unread++;
            }
        }

        // pagination
        $currentPage = (int) ($index/$byPage)+1;
        if ($currentPage <= 0) {
            $currentPage = 1;
        }
        $begin = ($currentPage - 1) * $byPage;
        $maxPage = (count($listItems) <= $byPage) ? '1' : ceil(count($listItems) / $byPage);
        $nbItems = count($listItems);

        // list items
        $listItems = array_slice($listItems, $begin, $byPage, true);

        // type : 'feed', 'folder', 'all', 'item'
        $currentHashType = $kf->hashType($currentHash);
        $hashView = '';
        switch($currentHashType){
        case 'all':
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> '.Intl::msg('unread items').'</span>';
            break;
        case 'feed':
            $hashView = 'Feed (<a href="'.$kf->getFeedHtmlUrl($currentHash).'" title="">'.$kf->getFeedTitle($currentHash).'</a>): '.'<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> '.Intl::msg('unread items').'</span>';
            break;
        case 'folder':
            $hashView = 'Folder ('.$kf->getFolderTitle($currentHash).'): <span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> '.Intl::msg('unread items').'</span>';
            break;
        default:
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> '.Intl::msg('unread items').'</span>';
            break;
        }

        $menu = $kfc->getMenu();
        $paging = $kfc->getPaging();
        $pb->assign('menu', $menu);
        $pb->assign('paging', $paging);
        $pb->assign('currentHashType', $currentHashType);
        $pb->assign('currentHashView', $hashView);
        $pb->assign('currentPage', (int) $currentPage);
        $pb->assign('maxPage', (int) $maxPage);
        $pb->assign('currentItemHash', $currentItemHash);
        $pb->assign('nbItems', $nbItems);
        $pb->assign('items', $listItems);
        if ($listFeeds == 'show') {
            $pb->assign('feedsView', $kf->getFeedsView());
        }
        $pb->assign('pagetitle', strip_tags($kfc->title));

        $pb->renderPage('index');
    } else {
        $pb->assign('pagetitle', Intl::msg('Sign in').' - '.strip_tags($kfc->title));
        if (!empty($_SERVER['QUERY_STRING'])) {
            $pb->assign('referer', MyTool::getUrl().'?'.$_SERVER['QUERY_STRING']);
        }
        $pb->renderPage('login');
    }
}

