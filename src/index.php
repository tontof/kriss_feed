<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk
define('DATA_DIR', 'data');
define('INC_DIR', 'inc');
define('CACHE_DIR', DATA_DIR.'/cache');
define('FAVICON_DIR', INC_DIR.'/favicon');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('BAN_FILE', DATA_DIR.'/ipbans.php');
define('UPDATECHECK_FILE', DATA_DIR.'/lastupdatecheck.txt');
// Updates check frequency. 86400 seconds = 24 hours
define('UPDATECHECK_INTERVAL', 86400);

define('FEED_VERSION', 6);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

define('MIN_TIME_UPDATE', 5); // Minimum accepted time for update

define('ERROR_NO_ERROR', 0);
define('ERROR_NO_XML', 1);
define('ERROR_ITEMS_MISSED', 2);
define('ERROR_LAST_UPDATE', 3);
define('ERROR_UNKNOWN', 4);

// fix some warning
date_default_timezone_set('Europe/Paris'); 

if (!is_dir(DATA_DIR)) {
    if (!@mkdir(DATA_DIR, 0755)) {
        echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
        exit();
    }
    @chmod(DATA_DIR, 0755);
    if (!is_file(DATA_DIR.'/.htaccess')) {
        if (!@file_put_contents(
                DATA_DIR.'/.htaccess',
                "Allow from none\nDeny from all\n"
                )) {
            echo '
<script>
 alert("Can not protect '.DATA_DIR.'");
 document.location=window.location.href;
</script>';
            exit();
        }
    }
}


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

    public $visibility = 'private';

    public $version;

    public $view = 'list';

    public $filter = 'unread';

    public $listFeeds = 'show';

    public $byPage = 10;

    public $currentHash = 'all';

    public $currentPage = 1;

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

    public $pagingItem = 1;
    public $pagingPage = 2;
    public $pagingByPage = 3;
    public $pagingMarkAs = 4;

    public function __construct($configFile, $version)
    {
        $this->_file = $configFile;
        $this->version = $version;

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
                    die("Can not create inc dir: ".INC_DIR);
                }
            }
            if (!is_dir(FAVICON_DIR)) {
                if (!@mkdir(FAVICON_DIR, 0755)) {
                    die("Can not create inc dir: ".FAVICON_DIR);
                }
            }
        }

        if ($this->isLogged()) {
            unset($_SESSION['view']);
            unset($_SESSION['listFeeds']);
            unset($_SESSION['filter']);
            unset($_SESSION['order']);
            unset($_SESSION['byPage']);
        }

        $view = $this->getView();
        $listFeeds = $this->getListFeeds();
        $filter = $this->getFilter();
        $order = $this->getOrder();
        $byPage = $this->getByPage();

        if ($this->view != $view
            || $this->listFeeds != $listFeeds
            || $this->filter != $filter
            || $this->order != $order
            || $this->byPage != $byPage
        ) {
            $this->view = $view;
            $this->listFeeds = $listFeeds;
            $this->filter = $filter;
            $this->order = $order;
            $this->byPage = $byPage;

            $this->write();
        }

        if (!$this->isLogged()) {
            $_SESSION['view'] = $view;
            $_SESSION['listFeeds'] = $listFeeds;
            $_SESSION['filter'] = $filter;
            $_SESSION['order'] = $order;
            $_SESSION['byPage'] = $byPage;
        }
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            $this->write();
            echo '
<script>
 alert("Your simple and smart (or stupid) feed reader is now configured.");
 document.location="'.MyTool::getUrl().'?import'.'";
</script>';
            exit();
        } else {
            FeedPage::init(
                array(
                    'version' => $this->version,
                    'pagetitle' => 'KrISS feed installation'
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
            $currentPage = (int)$_GET['page'];
        } else if (isset($_GET['previousPage']) && !empty($_GET['previousPage'])) {
            $currentPage = (int)$_GET['previousPage'] - 1;
            if ($currentPage < 1) {
                $currentPage = 1;
            }
        } else if (isset($_GET['nextPage']) && !empty($_GET['nextPage'])) {
            $currentPage = (int)$_GET['nextPage'] + 1;
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
        return Session::isLogged() || $this->visibility === 'public';
    }

    public function write()
    {
        if ($this->isLogged() || !is_file($this->_file)) {
            $data = array('login', 'hash', 'salt', 'title', 'redirector', 'shaarli',
                          'byPage', 'order', 'visibility', 'filter', 'view','locale',
                          'maxItems',  'autoreadItem', 'autoreadPage', 'maxUpdate',
                          'autohide', 'autofocus', 'listFeeds', 'autoUpdate', 'menuView',
                          'menuListFeeds', 'menuFilter', 'menuOrder', 'menuUpdate',
                          'menuRead', 'menuUnread', 'menuEdit', 'menuAdd', 'menuHelp',
                          'pagingItem', 'pagingPage', 'pagingByPage', 'addFavicon',
                          'pagingMarkAs', 'disableSessionProtection');
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
    private static $_instance;

    public static function init($var)
    {
        FeedPage::$var = $var;
    }

    public static function includesTpl()
    {
        extract(FeedPage::$var);
?>
<title><?php echo $pagetitle;?></title>
<meta charset="utf-8">

<!-- <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon"> -->
<?php if (is_file('inc/style.css')) { ?>
<link type="text/css" rel="stylesheet" href="inc/style.css?version=<?php echo $version;?>" />
<?php } else { ?>
<style>
/*!
 * Bootstrap v2.3.0
 *
 * Copyright 2012 Twitter, Inc
 * Licensed under the Apache License v2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world @twitter by @mdo and @fat.
 */.clearfix{*zoom:1}.clearfix:before,.clearfix:after{display:table;line-height:0;content:""}.clearfix:after{clear:both}.hide-text{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.input-block-level{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}article,aside,details,figcaption,figure,footer,header,hgroup,nav,section{display:block}audio,canvas,video{display:inline-block;*display:inline;*zoom:1}audio:not([controls]){display:none}html{font-size:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}a:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}a:hover,a:active{outline:0}sub,sup{position:relative;font-size:75%;line-height:0;vertical-align:baseline}sup{top:-0.5em}sub{bottom:-0.25em}img{width:auto\9;height:auto;max-width:100%;vertical-align:middle;border:0;-ms-interpolation-mode:bicubic}#map_canvas img,.google-maps img{max-width:none}button,input,select,textarea{margin:0;font-size:100%;vertical-align:middle}button,input{*overflow:visible;line-height:normal}button::-moz-focus-inner,input::-moz-focus-inner{padding:0;border:0}button,html input[type="button"],input[type="reset"],input[type="submit"]{cursor:pointer;-webkit-appearance:button}label,select,button,input[type="button"],input[type="reset"],input[type="submit"],input[type="radio"],input[type="checkbox"]{cursor:pointer}input[type="search"]{-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;-webkit-appearance:textfield}input[type="search"]::-webkit-search-decoration,input[type="search"]::-webkit-search-cancel-button{-webkit-appearance:none}textarea{overflow:auto;vertical-align:top}@media print{*{color:#000!important;text-shadow:none!important;background:transparent!important;box-shadow:none!important}a,a:visited{text-decoration:underline}a[href]:after{content:" (" attr(href) ")"}abbr[title]:after{content:" (" attr(title) ")"}.ir a:after,a[href^="javascript:"]:after,a[href^="#"]:after{content:""}pre,blockquote{border:1px solid #999;page-break-inside:avoid}thead{display:table-header-group}tr,img{page-break-inside:avoid}img{max-width:100%!important}@page{margin:.5cm}p,h2,h3{orphans:3;widows:3}h2,h3{page-break-after:avoid}}body{margin:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:20px;color:#333;background-color:#fff}a{color:#08c;text-decoration:none}a:hover,a:focus{color:#005580;text-decoration:underline}.ico-rounded{-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.ico-polaroid{padding:4px;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.1);-moz-box-shadow:0 1px 3px rgba(0,0,0,0.1);box-shadow:0 1px 3px rgba(0,0,0,0.1)}.ico-circle{-webkit-border-radius:500px;-moz-border-radius:500px;border-radius:500px}.row{margin-left:-20px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:20px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:940px}.span12{width:940px}.span11{width:860px}.span10{width:780px}.span9{width:700px}.span8{width:620px}.span7{width:540px}.span6{width:460px}.span5{width:380px}.span4{width:300px}.span3{width:220px}.span2{width:140px}.span1{width:60px}.offset12{margin-left:980px}.offset11{margin-left:900px}.offset10{margin-left:820px}.offset9{margin-left:740px}.offset8{margin-left:660px}.offset7{margin-left:580px}.offset6{margin-left:500px}.offset5{margin-left:420px}.offset4{margin-left:340px}.offset3{margin-left:260px}.offset2{margin-left:180px}.offset1{margin-left:100px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.127659574468085%;*margin-left:2.074468085106383%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.127659574468085%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.48936170212765%;*width:91.43617021276594%}.row-fluid .span10{width:82.97872340425532%;*width:82.92553191489361%}.row-fluid .span9{width:74.46808510638297%;*width:74.41489361702126%}.row-fluid .span8{width:65.95744680851064%;*width:65.90425531914893%}.row-fluid .span7{width:57.44680851063829%;*width:57.39361702127659%}.row-fluid .span6{width:48.93617021276595%;*width:48.88297872340425%}.row-fluid .span5{width:40.42553191489362%;*width:40.37234042553192%}.row-fluid .span4{width:31.914893617021278%;*width:31.861702127659576%}.row-fluid .span3{width:23.404255319148934%;*width:23.351063829787233%}.row-fluid .span2{width:14.893617021276595%;*width:14.840425531914894%}.row-fluid .span1{width:6.382978723404255%;*width:6.329787234042553%}.row-fluid .offset12{margin-left:104.25531914893617%;*margin-left:104.14893617021275%}.row-fluid .offset12:first-child{margin-left:102.12765957446808%;*margin-left:102.02127659574467%}.row-fluid .offset11{margin-left:95.74468085106382%;*margin-left:95.6382978723404%}.row-fluid .offset11:first-child{margin-left:93.61702127659574%;*margin-left:93.51063829787232%}.row-fluid .offset10{margin-left:87.23404255319149%;*margin-left:87.12765957446807%}.row-fluid .offset10:first-child{margin-left:85.1063829787234%;*margin-left:84.99999999999999%}.row-fluid .offset9{margin-left:78.72340425531914%;*margin-left:78.61702127659572%}.row-fluid .offset9:first-child{margin-left:76.59574468085106%;*margin-left:76.48936170212764%}.row-fluid .offset8{margin-left:70.2127659574468%;*margin-left:70.10638297872339%}.row-fluid .offset8:first-child{margin-left:68.08510638297872%;*margin-left:67.9787234042553%}.row-fluid .offset7{margin-left:61.70212765957446%;*margin-left:61.59574468085106%}.row-fluid .offset7:first-child{margin-left:59.574468085106375%;*margin-left:59.46808510638297%}.row-fluid .offset6{margin-left:53.191489361702125%;*margin-left:53.085106382978715%}.row-fluid .offset6:first-child{margin-left:51.063829787234035%;*margin-left:50.95744680851063%}.row-fluid .offset5{margin-left:44.68085106382979%;*margin-left:44.57446808510638%}.row-fluid .offset5:first-child{margin-left:42.5531914893617%;*margin-left:42.4468085106383%}.row-fluid .offset4{margin-left:36.170212765957444%;*margin-left:36.06382978723405%}.row-fluid .offset4:first-child{margin-left:34.04255319148936%;*margin-left:33.93617021276596%}.row-fluid .offset3{margin-left:27.659574468085104%;*margin-left:27.5531914893617%}.row-fluid .offset3:first-child{margin-left:25.53191489361702%;*margin-left:25.425531914893618%}.row-fluid .offset2{margin-left:19.148936170212764%;*margin-left:19.04255319148936%}.row-fluid .offset2:first-child{margin-left:17.02127659574468%;*margin-left:16.914893617021278%}.row-fluid .offset1{margin-left:10.638297872340425%;*margin-left:10.53191489361702%}.row-fluid .offset1:first-child{margin-left:8.51063829787234%;*margin-left:8.404255319148938%}[class*="span"].hide,.row-fluid [class*="span"].hide{display:none}[class*="span"].pull-right,.row-fluid [class*="span"].pull-right{float:right}.container{margin-right:auto;margin-left:auto;*zoom:1}.container:before,.container:after{display:table;line-height:0;content:""}.container:after{clear:both}.container-fluid{padding-right:20px;padding-left:20px;*zoom:1}.container-fluid:before,.container-fluid:after{display:table;line-height:0;content:""}.container-fluid:after{clear:both}p{margin:0 0 10px}.lead{margin-bottom:20px;font-size:21px;font-weight:200;line-height:30px}small{font-size:85%}strong{font-weight:bold}em{font-style:italic}cite{font-style:normal}.muted{color:#999}a.muted:hover,a.muted:focus{color:#808080}.text-warning{color:#c09853}a.text-warning:hover,a.text-warning:focus{color:#a47e3c}.text-error{color:#b94a48}a.text-error:hover,a.text-error:focus{color:#953b39}.text-info{color:#3a87ad}a.text-info:hover,a.text-info:focus{color:#2d6987}.text-success{color:#468847}a.text-success:hover,a.text-success:focus{color:#356635}.text-left{text-align:left}.text-right{text-align:right}.text-center{text-align:center}h1,h2,h3,h4,h5,h6{margin:10px 0;font-family:inherit;font-weight:bold;line-height:20px;color:inherit;text-rendering:optimizelegibility}h1 small,h2 small,h3 small,h4 small,h5 small,h6 small{font-weight:normal;line-height:1;color:#999}h1,h2,h3{line-height:40px}h1{font-size:38.5px}h2{font-size:31.5px}h3{font-size:24.5px}h4{font-size:17.5px}h5{font-size:14px}h6{font-size:11.9px}h1 small{font-size:24.5px}h2 small{font-size:17.5px}h3 small{font-size:14px}h4 small{font-size:14px}.page-header{padding-bottom:9px;margin:20px 0 30px;border-bottom:1px solid #eee}ul,ol{padding:0;margin:0 0 10px 25px}ul ul,ul ol,ol ol,ol ul{margin-bottom:0}li{line-height:20px}ul.unstyled,ol.unstyled{margin-left:0;list-style:none}ul.inline,ol.inline{margin-left:0;list-style:none}ul.inline>li,ol.inline>li{display:inline-block;*display:inline;padding-right:5px;padding-left:5px;*zoom:1}dl{margin-bottom:20px}dt,dd{line-height:20px}dt{font-weight:bold}dd{margin-left:10px}.dl-horizontal{*zoom:1}.dl-horizontal:before,.dl-horizontal:after{display:table;line-height:0;content:""}.dl-horizontal:after{clear:both}.dl-horizontal dt{float:left;width:160px;overflow:hidden;clear:left;text-align:right;text-overflow:ellipsis;white-space:nowrap}.dl-horizontal dd{margin-left:180px}hr{margin:20px 0;border:0;border-top:1px solid #eee;border-bottom:1px solid #fff}abbr[title],abbr[data-original-title]{cursor:help;border-bottom:1px dotted #999}abbr.initialism{font-size:90%;text-transform:uppercase}blockquote{padding:0 0 0 15px;margin:0 0 20px;border-left:5px solid #eee}blockquote p{margin-bottom:0;font-size:17.5px;font-weight:300;line-height:1.25}blockquote small{display:block;line-height:20px;color:#999}blockquote small:before{content:'\2014 \00A0'}blockquote.pull-right{float:right;padding-right:15px;padding-left:0;border-right:5px solid #eee;border-left:0}blockquote.pull-right p,blockquote.pull-right small{text-align:right}blockquote.pull-right small:before{content:''}blockquote.pull-right small:after{content:'\00A0 \2014'}q:before,q:after,blockquote:before,blockquote:after{content:""}address{display:block;margin-bottom:20px;font-style:normal;line-height:20px}code,pre{padding:0 3px 2px;font-family:Monaco,Menlo,Consolas,"Courier New",monospace;font-size:12px;color:#333;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}code{padding:2px 4px;color:#d14;white-space:nowrap;background-color:#f7f7f9;border:1px solid #e1e1e8}pre{display:block;padding:9.5px;margin:0 0 10px;font-size:13px;line-height:20px;word-break:break-all;word-wrap:break-word;white-space:pre;white-space:pre-wrap;background-color:#f5f5f5;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.15);-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}pre.prettyprint{margin-bottom:20px}pre code{padding:0;color:inherit;white-space:pre;white-space:pre-wrap;background-color:transparent;border:0}.pre-scrollable{max-height:340px;overflow-y:scroll}form{margin:0 0 20px}fieldset{padding:0;margin:0;border:0}legend{display:block;width:100%;padding:0;margin-bottom:20px;font-size:21px;line-height:40px;color:#333;border:0;border-bottom:1px solid #e5e5e5}legend small{font-size:15px;color:#999}label,input,button,select,textarea{font-size:14px;font-weight:normal;line-height:20px}input,button,select,textarea{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif}label{display:block;margin-bottom:5px}select,textarea,input[type="text"],input[type="password"],input[type="datetime"],input[type="datetime-local"],input[type="date"],input[type="month"],input[type="time"],input[type="week"],input[type="number"],input[type="email"],input[type="url"],input[type="search"],input[type="tel"],input[type="color"],.uneditable-input{display:inline-block;height:20px;padding:4px 6px;margin-bottom:10px;font-size:14px;line-height:20px;color:#555;vertical-align:middle;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}input,textarea,.uneditable-input{width:206px}textarea{height:auto}textarea,input[type="text"],input[type="password"],input[type="datetime"],input[type="datetime-local"],input[type="date"],input[type="month"],input[type="time"],input[type="week"],input[type="number"],input[type="email"],input[type="url"],input[type="search"],input[type="tel"],input[type="color"],.uneditable-input{background-color:#fff;border:1px solid #ccc;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-webkit-transition:border linear .2s,box-shadow linear .2s;-moz-transition:border linear .2s,box-shadow linear .2s;-o-transition:border linear .2s,box-shadow linear .2s;transition:border linear .2s,box-shadow linear .2s}textarea:focus,input[type="text"]:focus,input[type="password"]:focus,input[type="datetime"]:focus,input[type="datetime-local"]:focus,input[type="date"]:focus,input[type="month"]:focus,input[type="time"]:focus,input[type="week"]:focus,input[type="number"]:focus,input[type="email"]:focus,input[type="url"]:focus,input[type="search"]:focus,input[type="tel"]:focus,input[type="color"]:focus,.uneditable-input:focus{border-color:rgba(82,168,236,0.8);outline:0;outline:thin dotted \9;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(82,168,236,0.6)}input[type="radio"],input[type="checkbox"]{margin:4px 0 0;margin-top:1px \9;*margin-top:0;line-height:normal}input[type="file"],input[type="image"],input[type="submit"],input[type="reset"],input[type="button"],input[type="radio"],input[type="checkbox"]{width:auto}select,input[type="file"]{height:30px;*margin-top:4px;line-height:30px}select{width:220px;background-color:#fff;border:1px solid #ccc}select[multiple],select[size]{height:auto}select:focus,input[type="file"]:focus,input[type="radio"]:focus,input[type="checkbox"]:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.uneditable-input,.uneditable-textarea{color:#999;cursor:not-allowed;background-color:#fcfcfc;border-color:#ccc;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.025);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.025);box-shadow:inset 0 1px 2px rgba(0,0,0,0.025)}.uneditable-input{overflow:hidden;white-space:nowrap}.uneditable-textarea{width:auto;height:auto}input:-moz-placeholder,textarea:-moz-placeholder{color:#999}input:-ms-input-placeholder,textarea:-ms-input-placeholder{color:#999}input::-webkit-input-placeholder,textarea::-webkit-input-placeholder{color:#999}.radio,.checkbox{min-height:20px;padding-left:20px}.radio input[type="radio"],.checkbox input[type="checkbox"]{float:left;margin-left:-20px}.controls>.radio:first-child,.controls>.checkbox:first-child{padding-top:5px}.radio.inline,.checkbox.inline{display:inline-block;padding-top:5px;margin-bottom:0;vertical-align:middle}.radio.inline+.radio.inline,.checkbox.inline+.checkbox.inline{margin-left:10px}.input-mini{width:60px}.input-small{width:90px}.input-medium{width:150px}.input-large{width:210px}.input-xlarge{width:270px}.input-xxlarge{width:530px}input[class*="span"],select[class*="span"],textarea[class*="span"],.uneditable-input[class*="span"],.row-fluid input[class*="span"],.row-fluid select[class*="span"],.row-fluid textarea[class*="span"],.row-fluid .uneditable-input[class*="span"]{float:none;margin-left:0}.input-append input[class*="span"],.input-append .uneditable-input[class*="span"],.input-prepend input[class*="span"],.input-prepend .uneditable-input[class*="span"],.row-fluid input[class*="span"],.row-fluid select[class*="span"],.row-fluid textarea[class*="span"],.row-fluid .uneditable-input[class*="span"],.row-fluid .input-prepend [class*="span"],.row-fluid .input-append [class*="span"]{display:inline-block}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:20px}input.span12,textarea.span12,.uneditable-input.span12{width:926px}input.span11,textarea.span11,.uneditable-input.span11{width:846px}input.span10,textarea.span10,.uneditable-input.span10{width:766px}input.span9,textarea.span9,.uneditable-input.span9{width:686px}input.span8,textarea.span8,.uneditable-input.span8{width:606px}input.span7,textarea.span7,.uneditable-input.span7{width:526px}input.span6,textarea.span6,.uneditable-input.span6{width:446px}input.span5,textarea.span5,.uneditable-input.span5{width:366px}input.span4,textarea.span4,.uneditable-input.span4{width:286px}input.span3,textarea.span3,.uneditable-input.span3{width:206px}input.span2,textarea.span2,.uneditable-input.span2{width:126px}input.span1,textarea.span1,.uneditable-input.span1{width:46px}.controls-row{*zoom:1}.controls-row:before,.controls-row:after{display:table;line-height:0;content:""}.controls-row:after{clear:both}.controls-row [class*="span"],.row-fluid .controls-row [class*="span"]{float:left}.controls-row .checkbox[class*="span"],.controls-row .radio[class*="span"]{padding-top:5px}input[disabled],select[disabled],textarea[disabled],input[readonly],select[readonly],textarea[readonly]{cursor:not-allowed;background-color:#eee}input[type="radio"][disabled],input[type="checkbox"][disabled],input[type="radio"][readonly],input[type="checkbox"][readonly]{background-color:transparent}.control-group.warning .control-label,.control-group.warning .help-block,.control-group.warning .help-inline{color:#c09853}.control-group.warning .checkbox,.control-group.warning .radio,.control-group.warning input,.control-group.warning select,.control-group.warning textarea{color:#c09853}.control-group.warning input,.control-group.warning select,.control-group.warning textarea{border-color:#c09853;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.warning input:focus,.control-group.warning select:focus,.control-group.warning textarea:focus{border-color:#a47e3c;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #dbc59e}.control-group.warning .input-prepend .add-on,.control-group.warning .input-append .add-on{color:#c09853;background-color:#fcf8e3;border-color:#c09853}.control-group.error .control-label,.control-group.error .help-block,.control-group.error .help-inline{color:#b94a48}.control-group.error .checkbox,.control-group.error .radio,.control-group.error input,.control-group.error select,.control-group.error textarea{color:#b94a48}.control-group.error input,.control-group.error select,.control-group.error textarea{border-color:#b94a48;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.error input:focus,.control-group.error select:focus,.control-group.error textarea:focus{border-color:#953b39;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #d59392}.control-group.error .input-prepend .add-on,.control-group.error .input-append .add-on{color:#b94a48;background-color:#f2dede;border-color:#b94a48}.control-group.success .control-label,.control-group.success .help-block,.control-group.success .help-inline{color:#468847}.control-group.success .checkbox,.control-group.success .radio,.control-group.success input,.control-group.success select,.control-group.success textarea{color:#468847}.control-group.success input,.control-group.success select,.control-group.success textarea{border-color:#468847;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.success input:focus,.control-group.success select:focus,.control-group.success textarea:focus{border-color:#356635;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7aba7b}.control-group.success .input-prepend .add-on,.control-group.success .input-append .add-on{color:#468847;background-color:#dff0d8;border-color:#468847}.control-group.info .control-label,.control-group.info .help-block,.control-group.info .help-inline{color:#3a87ad}.control-group.info .checkbox,.control-group.info .radio,.control-group.info input,.control-group.info select,.control-group.info textarea{color:#3a87ad}.control-group.info input,.control-group.info select,.control-group.info textarea{border-color:#3a87ad;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);box-shadow:inset 0 1px 1px rgba(0,0,0,0.075)}.control-group.info input:focus,.control-group.info select:focus,.control-group.info textarea:focus{border-color:#2d6987;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3;-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075),0 0 6px #7ab5d3}.control-group.info .input-prepend .add-on,.control-group.info .input-append .add-on{color:#3a87ad;background-color:#d9edf7;border-color:#3a87ad}input:focus:invalid,textarea:focus:invalid,select:focus:invalid{color:#b94a48;border-color:#ee5f5b}input:focus:invalid:focus,textarea:focus:invalid:focus,select:focus:invalid:focus{border-color:#e9322d;-webkit-box-shadow:0 0 6px #f8b9b7;-moz-box-shadow:0 0 6px #f8b9b7;box-shadow:0 0 6px #f8b9b7}.form-actions{padding:19px 20px 20px;margin-top:20px;margin-bottom:20px;background-color:#f5f5f5;border-top:1px solid #e5e5e5;*zoom:1}.form-actions:before,.form-actions:after{display:table;line-height:0;content:""}.form-actions:after{clear:both}.help-block,.help-inline{color:#595959}.help-block{display:block;margin-bottom:10px}.help-inline{display:inline-block;*display:inline;padding-left:5px;vertical-align:middle;*zoom:1}.input-append,.input-prepend{display:inline-block;margin-bottom:10px;font-size:0;white-space:nowrap;vertical-align:middle}.input-append input,.input-prepend input,.input-append select,.input-prepend select,.input-append .uneditable-input,.input-prepend .uneditable-input,.input-append .dropdown-menu,.input-prepend .dropdown-menu,.input-append .popover,.input-prepend .popover{font-size:14px}.input-append input,.input-prepend input,.input-append select,.input-prepend select,.input-append .uneditable-input,.input-prepend .uneditable-input{position:relative;margin-bottom:0;*margin-left:0;vertical-align:top;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-append input:focus,.input-prepend input:focus,.input-append select:focus,.input-prepend select:focus,.input-append .uneditable-input:focus,.input-prepend .uneditable-input:focus{z-index:2}.input-append .add-on,.input-prepend .add-on{display:inline-block;width:auto;height:20px;min-width:16px;padding:4px 5px;font-size:14px;font-weight:normal;line-height:20px;text-align:center;text-shadow:0 1px 0 #fff;background-color:#eee;border:1px solid #ccc}.input-append .add-on,.input-prepend .add-on,.input-append .btn,.input-prepend .btn,.input-append .btn-group>.dropdown-toggle,.input-prepend .btn-group>.dropdown-toggle{vertical-align:top;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.input-append .active,.input-prepend .active{background-color:#a9dba9;border-color:#46a546}.input-prepend .add-on,.input-prepend .btn{margin-right:-1px}.input-prepend .add-on:first-child,.input-prepend .btn:first-child{-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-append input,.input-append select,.input-append .uneditable-input{-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-append input+.btn-group .btn:last-child,.input-append select+.btn-group .btn:last-child,.input-append .uneditable-input+.btn-group .btn:last-child{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-append .add-on,.input-append .btn,.input-append .btn-group{margin-left:-1px}.input-append .add-on:last-child,.input-append .btn:last-child,.input-append .btn-group:last-child>.dropdown-toggle{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append input,.input-prepend.input-append select,.input-prepend.input-append .uneditable-input{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.input-prepend.input-append input+.btn-group .btn,.input-prepend.input-append select+.btn-group .btn,.input-prepend.input-append .uneditable-input+.btn-group .btn{-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append .add-on:first-child,.input-prepend.input-append .btn:first-child{margin-right:-1px;-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.input-prepend.input-append .add-on:last-child,.input-prepend.input-append .btn:last-child{margin-left:-1px;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.input-prepend.input-append .btn-group:first-child{margin-left:0}input.search-query{padding-right:14px;padding-right:4px \9;padding-left:14px;padding-left:4px \9;margin-bottom:0;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.form-search .input-append .search-query,.form-search .input-prepend .search-query{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.form-search .input-append .search-query{-webkit-border-radius:14px 0 0 14px;-moz-border-radius:14px 0 0 14px;border-radius:14px 0 0 14px}.form-search .input-append .btn{-webkit-border-radius:0 14px 14px 0;-moz-border-radius:0 14px 14px 0;border-radius:0 14px 14px 0}.form-search .input-prepend .search-query{-webkit-border-radius:0 14px 14px 0;-moz-border-radius:0 14px 14px 0;border-radius:0 14px 14px 0}.form-search .input-prepend .btn{-webkit-border-radius:14px 0 0 14px;-moz-border-radius:14px 0 0 14px;border-radius:14px 0 0 14px}.form-search input,.form-inline input,.form-horizontal input,.form-search textarea,.form-inline textarea,.form-horizontal textarea,.form-search select,.form-inline select,.form-horizontal select,.form-search .help-inline,.form-inline .help-inline,.form-horizontal .help-inline,.form-search .uneditable-input,.form-inline .uneditable-input,.form-horizontal .uneditable-input,.form-search .input-prepend,.form-inline .input-prepend,.form-horizontal .input-prepend,.form-search .input-append,.form-inline .input-append,.form-horizontal .input-append{display:inline-block;*display:inline;margin-bottom:0;vertical-align:middle;*zoom:1}.form-search .hide,.form-inline .hide,.form-horizontal .hide{display:none}.form-search label,.form-inline label,.form-search .btn-group,.form-inline .btn-group{display:inline-block}.form-search .input-append,.form-inline .input-append,.form-search .input-prepend,.form-inline .input-prepend{margin-bottom:0}.form-search .radio,.form-search .checkbox,.form-inline .radio,.form-inline .checkbox{padding-left:0;margin-bottom:0;vertical-align:middle}.form-search .radio input[type="radio"],.form-search .checkbox input[type="checkbox"],.form-inline .radio input[type="radio"],.form-inline .checkbox input[type="checkbox"]{float:left;margin-right:3px;margin-left:0}.control-group{margin-bottom:10px}legend+.control-group{margin-top:20px;-webkit-margin-top-collapse:separate}.form-horizontal .control-group{margin-bottom:20px;*zoom:1}.form-horizontal .control-group:before,.form-horizontal .control-group:after{display:table;line-height:0;content:""}.form-horizontal .control-group:after{clear:both}.form-horizontal .control-label{float:left;width:160px;padding-top:5px;text-align:right}.form-horizontal .controls{*display:inline-block;*padding-left:20px;margin-left:180px;*margin-left:0}.form-horizontal .controls:first-child{*padding-left:180px}.form-horizontal .help-block{margin-bottom:0}.form-horizontal input+.help-block,.form-horizontal select+.help-block,.form-horizontal textarea+.help-block,.form-horizontal .uneditable-input+.help-block,.form-horizontal .input-prepend+.help-block,.form-horizontal .input-append+.help-block{margin-top:10px}.form-horizontal .form-actions{padding-left:180px}table{max-width:100%;background-color:transparent;border-collapse:collapse;border-spacing:0}.table{width:100%;margin-bottom:20px}.table th,.table td{padding:8px;line-height:20px;text-align:left;vertical-align:top;border-top:1px solid #ddd}.table th{font-weight:bold}.table thead th{vertical-align:bottom}.table caption+thead tr:first-child th,.table caption+thead tr:first-child td,.table colgroup+thead tr:first-child th,.table colgroup+thead tr:first-child td,.table thead:first-child tr:first-child th,.table thead:first-child tr:first-child td{border-top:0}.table tbody+tbody{border-top:2px solid #ddd}.table .table{background-color:#fff}.table-condensed th,.table-condensed td{padding:4px 5px}.table-bordered{border:1px solid #ddd;border-collapse:separate;*border-collapse:collapse;border-left:0;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.table-bordered th,.table-bordered td{border-left:1px solid #ddd}.table-bordered caption+thead tr:first-child th,.table-bordered caption+tbody tr:first-child th,.table-bordered caption+tbody tr:first-child td,.table-bordered colgroup+thead tr:first-child th,.table-bordered colgroup+tbody tr:first-child th,.table-bordered colgroup+tbody tr:first-child td,.table-bordered thead:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child th,.table-bordered tbody:first-child tr:first-child td{border-top:0}.table-bordered thead:first-child tr:first-child>th:first-child,.table-bordered tbody:first-child tr:first-child>td:first-child,.table-bordered tbody:first-child tr:first-child>th:first-child{-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topleft:4px}.table-bordered thead:first-child tr:first-child>th:last-child,.table-bordered tbody:first-child tr:first-child>td:last-child,.table-bordered tbody:first-child tr:first-child>th:last-child{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-moz-border-radius-topright:4px}.table-bordered thead:last-child tr:last-child>th:first-child,.table-bordered tbody:last-child tr:last-child>td:first-child,.table-bordered tbody:last-child tr:last-child>th:first-child,.table-bordered tfoot:last-child tr:last-child>td:first-child,.table-bordered tfoot:last-child tr:last-child>th:first-child{-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-moz-border-radius-bottomleft:4px}.table-bordered thead:last-child tr:last-child>th:last-child,.table-bordered tbody:last-child tr:last-child>td:last-child,.table-bordered tbody:last-child tr:last-child>th:last-child,.table-bordered tfoot:last-child tr:last-child>td:last-child,.table-bordered tfoot:last-child tr:last-child>th:last-child{-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-bottomright:4px}.table-bordered tfoot+tbody:last-child tr:last-child td:first-child{-webkit-border-bottom-left-radius:0;border-bottom-left-radius:0;-moz-border-radius-bottomleft:0}.table-bordered tfoot+tbody:last-child tr:last-child td:last-child{-webkit-border-bottom-right-radius:0;border-bottom-right-radius:0;-moz-border-radius-bottomright:0}.table-bordered caption+thead tr:first-child th:first-child,.table-bordered caption+tbody tr:first-child td:first-child,.table-bordered colgroup+thead tr:first-child th:first-child,.table-bordered colgroup+tbody tr:first-child td:first-child{-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topleft:4px}.table-bordered caption+thead tr:first-child th:last-child,.table-bordered caption+tbody tr:first-child td:last-child,.table-bordered colgroup+thead tr:first-child th:last-child,.table-bordered colgroup+tbody tr:first-child td:last-child{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-moz-border-radius-topright:4px}.table-striped tbody>tr:nth-child(odd)>td,.table-striped tbody>tr:nth-child(odd)>th{background-color:#f9f9f9}.table-hover tbody tr:hover>td,.table-hover tbody tr:hover>th{background-color:#f5f5f5}table td[class*="span"],table th[class*="span"],.row-fluid table td[class*="span"],.row-fluid table th[class*="span"]{display:table-cell;float:none;margin-left:0}.table td.span1,.table th.span1{float:none;width:44px;margin-left:0}.table td.span2,.table th.span2{float:none;width:124px;margin-left:0}.table td.span3,.table th.span3{float:none;width:204px;margin-left:0}.table td.span4,.table th.span4{float:none;width:284px;margin-left:0}.table td.span5,.table th.span5{float:none;width:364px;margin-left:0}.table td.span6,.table th.span6{float:none;width:444px;margin-left:0}.table td.span7,.table th.span7{float:none;width:524px;margin-left:0}.table td.span8,.table th.span8{float:none;width:604px;margin-left:0}.table td.span9,.table th.span9{float:none;width:684px;margin-left:0}.table td.span10,.table th.span10{float:none;width:764px;margin-left:0}.table td.span11,.table th.span11{float:none;width:844px;margin-left:0}.table td.span12,.table th.span12{float:none;width:924px;margin-left:0}.table tbody tr.success>td{background-color:#dff0d8}.table tbody tr.error>td{background-color:#f2dede}.table tbody tr.warning>td{background-color:#fcf8e3}.table tbody tr.info>td{background-color:#d9edf7}.table-hover tbody tr.success:hover>td{background-color:#d0e9c6}.table-hover tbody tr.error:hover>td{background-color:#ebcccc}.table-hover tbody tr.warning:hover>td{background-color:#faf2cc}.table-hover tbody tr.info:hover>td{background-color:#c4e3f3}[class^="icon-"],[class*=" icon-"]{display:inline-block;width:14px;height:14px;margin-top:1px;*margin-right:.3em;line-height:14px;vertical-align:text-top;background-image:url("../img/glyphicons-halflings.png");background-position:14px 14px;background-repeat:no-repeat}.ico-white,.nav-pills>.active>a>[class^="icon-"],.nav-pills>.active>a>[class*=" icon-"],.nav-list>.active>a>[class^="icon-"],.nav-list>.active>a>[class*=" icon-"],.navbar-inverse .nav>.active>a>[class^="icon-"],.navbar-inverse .nav>.active>a>[class*=" icon-"],.dropdown-menu>li>a:hover>[class^="icon-"],.dropdown-menu>li>a:focus>[class^="icon-"],.dropdown-menu>li>a:hover>[class*=" icon-"],.dropdown-menu>li>a:focus>[class*=" icon-"],.dropdown-menu>.active>a>[class^="icon-"],.dropdown-menu>.active>a>[class*=" icon-"],.dropdown-submenu:hover>a>[class^="icon-"],.dropdown-submenu:focus>a>[class^="icon-"],.dropdown-submenu:hover>a>[class*=" icon-"],.dropdown-submenu:focus>a>[class*=" icon-"]{background-image:url("../img/glyphicons-halflings-white.png")}.ico-glass{background-position:0 0}.ico-music{background-position:-24px 0}.ico-search{background-position:-48px 0}.ico-envelope{background-position:-72px 0}.ico-heart{background-position:-96px 0}.ico-star{background-position:-120px 0}.ico-star-empty{background-position:-144px 0}.ico-user{background-position:-168px 0}.ico-film{background-position:-192px 0}.ico-th-large{background-position:-216px 0}.ico-th{background-position:-240px 0}.ico-th-list{background-position:-264px 0}.ico-ok{background-position:-288px 0}.ico-remove{background-position:-312px 0}.ico-zoom-in{background-position:-336px 0}.ico-zoom-out{background-position:-360px 0}.ico-off{background-position:-384px 0}.ico-signal{background-position:-408px 0}.ico-cog{background-position:-432px 0}.ico-trash{background-position:-456px 0}.ico-home{background-position:0 -24px}.ico-file{background-position:-24px -24px}.ico-time{background-position:-48px -24px}.ico-road{background-position:-72px -24px}.ico-download-alt{background-position:-96px -24px}.ico-download{background-position:-120px -24px}.ico-upload{background-position:-144px -24px}.ico-inbox{background-position:-168px -24px}.ico-play-circle{background-position:-192px -24px}.ico-repeat{background-position:-216px -24px}.ico-refresh{background-position:-240px -24px}.ico-list-alt{background-position:-264px -24px}.ico-lock{background-position:-287px -24px}.ico-flag{background-position:-312px -24px}.ico-headphones{background-position:-336px -24px}.ico-volume-off{background-position:-360px -24px}.ico-volume-down{background-position:-384px -24px}.ico-volume-up{background-position:-408px -24px}.ico-qrcode{background-position:-432px -24px}.ico-barcode{background-position:-456px -24px}.ico-tag{background-position:0 -48px}.ico-tags{background-position:-25px -48px}.ico-book{background-position:-48px -48px}.ico-bookmark{background-position:-72px -48px}.ico-print{background-position:-96px -48px}.ico-camera{background-position:-120px -48px}.ico-font{background-position:-144px -48px}.ico-bold{background-position:-167px -48px}.ico-italic{background-position:-192px -48px}.ico-text-height{background-position:-216px -48px}.ico-text-width{background-position:-240px -48px}.ico-align-left{background-position:-264px -48px}.ico-align-center{background-position:-288px -48px}.ico-align-right{background-position:-312px -48px}.ico-align-justify{background-position:-336px -48px}.ico-list{background-position:-360px -48px}.ico-indent-left{background-position:-384px -48px}.ico-indent-right{background-position:-408px -48px}.ico-facetime-video{background-position:-432px -48px}.ico-picture{background-position:-456px -48px}.ico-pencil{background-position:0 -72px}.ico-map-marker{background-position:-24px -72px}.ico-adjust{background-position:-48px -72px}.ico-tint{background-position:-72px -72px}.ico-edit{background-position:-96px -72px}.ico-share{background-position:-120px -72px}.ico-check{background-position:-144px -72px}.ico-move{background-position:-168px -72px}.ico-step-backward{background-position:-192px -72px}.ico-fast-backward{background-position:-216px -72px}.ico-backward{background-position:-240px -72px}.ico-play{background-position:-264px -72px}.ico-pause{background-position:-288px -72px}.ico-stop{background-position:-312px -72px}.ico-forward{background-position:-336px -72px}.ico-fast-forward{background-position:-360px -72px}.ico-step-forward{background-position:-384px -72px}.ico-eject{background-position:-408px -72px}.ico-chevron-left{background-position:-432px -72px}.ico-chevron-right{background-position:-456px -72px}.ico-plus-sign{background-position:0 -96px}.ico-minus-sign{background-position:-24px -96px}.ico-remove-sign{background-position:-48px -96px}.ico-ok-sign{background-position:-72px -96px}.ico-question-sign{background-position:-96px -96px}.ico-info-sign{background-position:-120px -96px}.ico-screenshot{background-position:-144px -96px}.ico-remove-circle{background-position:-168px -96px}.ico-ok-circle{background-position:-192px -96px}.ico-ban-circle{background-position:-216px -96px}.ico-arrow-left{background-position:-240px -96px}.ico-arrow-right{background-position:-264px -96px}.ico-arrow-up{background-position:-289px -96px}.ico-arrow-down{background-position:-312px -96px}.ico-share-alt{background-position:-336px -96px}.ico-resize-full{background-position:-360px -96px}.ico-resize-small{background-position:-384px -96px}.ico-plus{background-position:-408px -96px}.ico-minus{background-position:-433px -96px}.ico-asterisk{background-position:-456px -96px}.ico-exclamation-sign{background-position:0 -120px}.ico-gift{background-position:-24px -120px}.ico-leaf{background-position:-48px -120px}.ico-fire{background-position:-72px -120px}.ico-eye-open{background-position:-96px -120px}.ico-eye-close{background-position:-120px -120px}.ico-warning-sign{background-position:-144px -120px}.ico-plane{background-position:-168px -120px}.ico-calendar{background-position:-192px -120px}.ico-random{width:16px;background-position:-216px -120px}.ico-comment{background-position:-240px -120px}.ico-magnet{background-position:-264px -120px}.ico-chevron-up{background-position:-288px -120px}.ico-chevron-down{background-position:-313px -119px}.ico-retweet{background-position:-336px -120px}.ico-shopping-cart{background-position:-360px -120px}.ico-folder-close{width:16px;background-position:-384px -120px}.ico-folder-open{width:16px;background-position:-408px -120px}.ico-resize-vertical{background-position:-432px -119px}.ico-resize-horizontal{background-position:-456px -118px}.ico-hdd{background-position:0 -144px}.ico-bullhorn{background-position:-24px -144px}.ico-bell{background-position:-48px -144px}.ico-certificate{background-position:-72px -144px}.ico-thumbs-up{background-position:-96px -144px}.ico-thumbs-down{background-position:-120px -144px}.ico-hand-right{background-position:-144px -144px}.ico-hand-left{background-position:-168px -144px}.ico-hand-up{background-position:-192px -144px}.ico-hand-down{background-position:-216px -144px}.ico-circle-arrow-right{background-position:-240px -144px}.ico-circle-arrow-left{background-position:-264px -144px}.ico-circle-arrow-up{background-position:-288px -144px}.ico-circle-arrow-down{background-position:-312px -144px}.ico-globe{background-position:-336px -144px}.ico-wrench{background-position:-360px -144px}.ico-tasks{background-position:-384px -144px}.ico-filter{background-position:-408px -144px}.ico-briefcase{background-position:-432px -144px}.ico-fullscreen{background-position:-456px -144px}.dropup,.dropdown{position:relative}.dropdown-toggle{*margin-bottom:-3px}.dropdown-toggle:active,.open .dropdown-toggle{outline:0}.caret{display:inline-block;width:0;height:0;vertical-align:top;border-top:4px solid #000;border-right:4px solid transparent;border-left:4px solid transparent;content:""}.dropdown .caret{margin-top:8px;margin-left:2px}.dropdown-menu{position:absolute;top:100%;left:0;z-index:1000;display:none;float:left;min-width:160px;padding:5px 0;margin:2px 0 0;list-style:none;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);*border-right-width:2px;*border-bottom-width:2px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);-moz-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);-webkit-background-clip:padding-box;-moz-background-clip:padding;background-clip:padding-box}.dropdown-menu.pull-right{right:0;left:auto}.dropdown-menu .divider{*width:100%;height:1px;margin:9px 1px;*margin:-5px 0 5px;overflow:hidden;background-color:#e5e5e5;border-bottom:1px solid #fff}.dropdown-menu>li>a{display:block;padding:3px 20px;clear:both;font-weight:normal;line-height:20px;color:#333;white-space:nowrap}.dropdown-menu>li>a:hover,.dropdown-menu>li>a:focus,.dropdown-submenu:hover>a,.dropdown-submenu:focus>a{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.active>a,.dropdown-menu>.active>a:hover,.dropdown-menu>.active>a:focus{color:#fff;text-decoration:none;background-color:#0081c2;background-image:-moz-linear-gradient(top,#08c,#0077b3);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#0077b3));background-image:-webkit-linear-gradient(top,#08c,#0077b3);background-image:-o-linear-gradient(top,#08c,#0077b3);background-image:linear-gradient(to bottom,#08c,#0077b3);background-repeat:repeat-x;outline:0;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0077b3',GradientType=0)}.dropdown-menu>.disabled>a,.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{color:#999}.dropdown-menu>.disabled>a:hover,.dropdown-menu>.disabled>a:focus{text-decoration:none;cursor:default;background-color:transparent;background-image:none;filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.open{*z-index:1000}.open>.dropdown-menu{display:block}.pull-right>.dropdown-menu{right:0;left:auto}.dropup .caret,.navbar-fixed-bottom .dropdown .caret{border-top:0;border-bottom:4px solid #000;content:""}.dropup .dropdown-menu,.navbar-fixed-bottom .dropdown .dropdown-menu{top:auto;bottom:100%;margin-bottom:1px}.dropdown-submenu{position:relative}.dropdown-submenu>.dropdown-menu{top:0;left:100%;margin-top:-6px;margin-left:-1px;-webkit-border-radius:0 6px 6px 6px;-moz-border-radius:0 6px 6px 6px;border-radius:0 6px 6px 6px}.dropdown-submenu:hover>.dropdown-menu{display:block}.dropup .dropdown-submenu>.dropdown-menu{top:auto;bottom:0;margin-top:0;margin-bottom:-2px;-webkit-border-radius:5px 5px 5px 0;-moz-border-radius:5px 5px 5px 0;border-radius:5px 5px 5px 0}.dropdown-submenu>a:after{display:block;float:right;width:0;height:0;margin-top:5px;margin-right:-10px;border-color:transparent;border-left-color:#ccc;border-style:solid;border-width:5px 0 5px 5px;content:" "}.dropdown-submenu:hover>a:after{border-left-color:#fff}.dropdown-submenu.pull-left{float:none}.dropdown-submenu.pull-left>.dropdown-menu{left:-100%;margin-left:10px;-webkit-border-radius:6px 0 6px 6px;-moz-border-radius:6px 0 6px 6px;border-radius:6px 0 6px 6px}.dropdown .dropdown-menu .nav-header{padding-right:20px;padding-left:20px}.typeahead{z-index:1051;margin-top:2px;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.well{min-height:20px;padding:19px;margin-bottom:20px;background-color:#f5f5f5;border:1px solid #e3e3e3;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 1px 1px rgba(0,0,0,0.05);box-shadow:inset 0 1px 1px rgba(0,0,0,0.05)}.well blockquote{border-color:#ddd;border-color:rgba(0,0,0,0.15)}.well-large{padding:24px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.well-small{padding:9px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.fade{opacity:0;-webkit-transition:opacity .15s linear;-moz-transition:opacity .15s linear;-o-transition:opacity .15s linear;transition:opacity .15s linear}.fade.in{opacity:1}.collapse{position:relative;height:0;overflow:hidden;-webkit-transition:height .35s ease;-moz-transition:height .35s ease;-o-transition:height .35s ease;transition:height .35s ease}.collapse.in{height:auto}.close{float:right;font-size:20px;font-weight:bold;line-height:20px;color:#000;text-shadow:0 1px 0 #fff;opacity:.2;filter:alpha(opacity=20)}.close:hover,.close:focus{color:#000;text-decoration:none;cursor:pointer;opacity:.4;filter:alpha(opacity=40)}button.close{padding:0;cursor:pointer;background:transparent;border:0;-webkit-appearance:none}.btn{display:inline-block;*display:inline;padding:4px 12px;margin-bottom:0;*margin-left:.3em;font-size:14px;line-height:20px;color:#333;text-align:center;text-shadow:0 1px 1px rgba(255,255,255,0.75);vertical-align:middle;cursor:pointer;background-color:#f5f5f5;*background-color:#e6e6e6;background-image:-moz-linear-gradient(top,#fff,#e6e6e6);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fff),to(#e6e6e6));background-image:-webkit-linear-gradient(top,#fff,#e6e6e6);background-image:-o-linear-gradient(top,#fff,#e6e6e6);background-image:linear-gradient(to bottom,#fff,#e6e6e6);background-repeat:repeat-x;border:1px solid #ccc;*border:0;border-color:#e6e6e6 #e6e6e6 #bfbfbf;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);border-bottom-color:#b3b3b3;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff',endColorstr='#ffe6e6e6',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);*zoom:1;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05)}.btn:hover,.btn:focus,.btn:active,.btn.active,.btn.disabled,.btn[disabled]{color:#333;background-color:#e6e6e6;*background-color:#d9d9d9}.btn:active,.btn.active{background-color:#ccc \9}.btn:first-child{*margin-left:0}.btn:hover,.btn:focus{color:#333;text-decoration:none;background-position:0 -15px;-webkit-transition:background-position .1s linear;-moz-transition:background-position .1s linear;-o-transition:background-position .1s linear;transition:background-position .1s linear}.btn:focus{outline:thin dotted #333;outline:5px auto -webkit-focus-ring-color;outline-offset:-2px}.btn.active,.btn:active{background-image:none;outline:0;-webkit-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05)}.btn.disabled,.btn[disabled]{cursor:default;background-image:none;opacity:.65;filter:alpha(opacity=65);-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.btn-large{padding:11px 19px;font-size:17.5px;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.btn-large [class^="icon-"],.btn-large [class*=" icon-"]{margin-top:4px}.btn-small{padding:2px 10px;font-size:11.9px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.btn-small [class^="icon-"],.btn-small [class*=" icon-"]{margin-top:0}.btn-mini [class^="icon-"],.btn-mini [class*=" icon-"]{margin-top:-1px}.btn-mini{padding:0 6px;font-size:10.5px;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.btn-block{display:block;width:100%;padding-right:0;padding-left:0;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.btn-block+.btn-block{margin-top:5px}input[type="submit"].btn-block,input[type="reset"].btn-block,input[type="button"].btn-block{width:100%}.btn-primary.active,.btn-warning.active,.btn-danger.active,.btn-success.active,.btn-info.active,.btn-inverse.active{color:rgba(255,255,255,0.75)}.btn-primary{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#006dcc;*background-color:#04c;background-image:-moz-linear-gradient(top,#08c,#04c);background-image:-webkit-gradient(linear,0 0,0 100%,from(#08c),to(#04c));background-image:-webkit-linear-gradient(top,#08c,#04c);background-image:-o-linear-gradient(top,#08c,#04c);background-image:linear-gradient(to bottom,#08c,#04c);background-repeat:repeat-x;border-color:#04c #04c #002a80;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc',endColorstr='#ff0044cc',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-primary:hover,.btn-primary:focus,.btn-primary:active,.btn-primary.active,.btn-primary.disabled,.btn-primary[disabled]{color:#fff;background-color:#04c;*background-color:#003bb3}.btn-primary:active,.btn-primary.active{background-color:#039 \9}.btn-warning{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#faa732;*background-color:#f89406;background-image:-moz-linear-gradient(top,#fbb450,#f89406);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fbb450),to(#f89406));background-image:-webkit-linear-gradient(top,#fbb450,#f89406);background-image:-o-linear-gradient(top,#fbb450,#f89406);background-image:linear-gradient(to bottom,#fbb450,#f89406);background-repeat:repeat-x;border-color:#f89406 #f89406 #ad6704;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffbb450',endColorstr='#fff89406',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-warning:hover,.btn-warning:focus,.btn-warning:active,.btn-warning.active,.btn-warning.disabled,.btn-warning[disabled]{color:#fff;background-color:#f89406;*background-color:#df8505}.btn-warning:active,.btn-warning.active{background-color:#c67605 \9}.btn-danger{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#da4f49;*background-color:#bd362f;background-image:-moz-linear-gradient(top,#ee5f5b,#bd362f);background-image:-webkit-gradient(linear,0 0,0 100%,from(#ee5f5b),to(#bd362f));background-image:-webkit-linear-gradient(top,#ee5f5b,#bd362f);background-image:-o-linear-gradient(top,#ee5f5b,#bd362f);background-image:linear-gradient(to bottom,#ee5f5b,#bd362f);background-repeat:repeat-x;border-color:#bd362f #bd362f #802420;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffee5f5b',endColorstr='#ffbd362f',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-danger:hover,.btn-danger:focus,.btn-danger:active,.btn-danger.active,.btn-danger.disabled,.btn-danger[disabled]{color:#fff;background-color:#bd362f;*background-color:#a9302a}.btn-danger:active,.btn-danger.active{background-color:#942a25 \9}.btn-success{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#5bb75b;*background-color:#51a351;background-image:-moz-linear-gradient(top,#62c462,#51a351);background-image:-webkit-gradient(linear,0 0,0 100%,from(#62c462),to(#51a351));background-image:-webkit-linear-gradient(top,#62c462,#51a351);background-image:-o-linear-gradient(top,#62c462,#51a351);background-image:linear-gradient(to bottom,#62c462,#51a351);background-repeat:repeat-x;border-color:#51a351 #51a351 #387038;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff62c462',endColorstr='#ff51a351',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-success:hover,.btn-success:focus,.btn-success:active,.btn-success.active,.btn-success.disabled,.btn-success[disabled]{color:#fff;background-color:#51a351;*background-color:#499249}.btn-success:active,.btn-success.active{background-color:#408140 \9}.btn-info{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#49afcd;*background-color:#2f96b4;background-image:-moz-linear-gradient(top,#5bc0de,#2f96b4);background-image:-webkit-gradient(linear,0 0,0 100%,from(#5bc0de),to(#2f96b4));background-image:-webkit-linear-gradient(top,#5bc0de,#2f96b4);background-image:-o-linear-gradient(top,#5bc0de,#2f96b4);background-image:linear-gradient(to bottom,#5bc0de,#2f96b4);background-repeat:repeat-x;border-color:#2f96b4 #2f96b4 #1f6377;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de',endColorstr='#ff2f96b4',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-info:hover,.btn-info:focus,.btn-info:active,.btn-info.active,.btn-info.disabled,.btn-info[disabled]{color:#fff;background-color:#2f96b4;*background-color:#2a85a0}.btn-info:active,.btn-info.active{background-color:#24748c \9}.btn-inverse{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#363636;*background-color:#222;background-image:-moz-linear-gradient(top,#444,#222);background-image:-webkit-gradient(linear,0 0,0 100%,from(#444),to(#222));background-image:-webkit-linear-gradient(top,#444,#222);background-image:-o-linear-gradient(top,#444,#222);background-image:linear-gradient(to bottom,#444,#222);background-repeat:repeat-x;border-color:#222 #222 #000;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff444444',endColorstr='#ff222222',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.btn-inverse:hover,.btn-inverse:focus,.btn-inverse:active,.btn-inverse.active,.btn-inverse.disabled,.btn-inverse[disabled]{color:#fff;background-color:#222;*background-color:#151515}.btn-inverse:active,.btn-inverse.active{background-color:#080808 \9}button.btn,input[type="submit"].btn{*padding-top:3px;*padding-bottom:3px}button.btn::-moz-focus-inner,input[type="submit"].btn::-moz-focus-inner{padding:0;border:0}button.btn.btn-large,input[type="submit"].btn.btn-large{*padding-top:7px;*padding-bottom:7px}button.btn.btn-small,input[type="submit"].btn.btn-small{*padding-top:3px;*padding-bottom:3px}button.btn.btn-mini,input[type="submit"].btn.btn-mini{*padding-top:1px;*padding-bottom:1px}.btn-link,.btn-link:active,.btn-link[disabled]{background-color:transparent;background-image:none;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.btn-link{color:#08c;cursor:pointer;border-color:transparent;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-link:hover,.btn-link:focus{color:#005580;text-decoration:underline;background-color:transparent}.btn-link[disabled]:hover,.btn-link[disabled]:focus{color:#333;text-decoration:none}.btn-group{position:relative;display:inline-block;*display:inline;*margin-left:.3em;font-size:0;white-space:nowrap;vertical-align:middle;*zoom:1}.btn-group:first-child{*margin-left:0}.btn-group+.btn-group{margin-left:5px}.btn-toolbar{margin-top:10px;margin-bottom:10px;font-size:0}.btn-toolbar>.btn+.btn,.btn-toolbar>.btn-group+.btn,.btn-toolbar>.btn+.btn-group{margin-left:5px}.btn-group>.btn{position:relative;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-group>.btn+.btn{margin-left:-1px}.btn-group>.btn,.btn-group>.dropdown-menu,.btn-group>.popover{font-size:14px}.btn-group>.btn-mini{font-size:10.5px}.btn-group>.btn-small{font-size:11.9px}.btn-group>.btn-large{font-size:17.5px}.btn-group>.btn:first-child{margin-left:0;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-bottomleft:4px;-moz-border-radius-topleft:4px}.btn-group>.btn:last-child,.btn-group>.dropdown-toggle{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-bottomright:4px}.btn-group>.btn.large:first-child{margin-left:0;-webkit-border-bottom-left-radius:6px;border-bottom-left-radius:6px;-webkit-border-top-left-radius:6px;border-top-left-radius:6px;-moz-border-radius-bottomleft:6px;-moz-border-radius-topleft:6px}.btn-group>.btn.large:last-child,.btn-group>.large.dropdown-toggle{-webkit-border-top-right-radius:6px;border-top-right-radius:6px;-webkit-border-bottom-right-radius:6px;border-bottom-right-radius:6px;-moz-border-radius-topright:6px;-moz-border-radius-bottomright:6px}.btn-group>.btn:hover,.btn-group>.btn:focus,.btn-group>.btn:active,.btn-group>.btn.active{z-index:2}.btn-group .dropdown-toggle:active,.btn-group.open .dropdown-toggle{outline:0}.btn-group>.btn+.dropdown-toggle{*padding-top:5px;padding-right:8px;*padding-bottom:5px;padding-left:8px;-webkit-box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 1px 0 0 rgba(255,255,255,0.125),inset 0 1px 0 rgba(255,255,255,0.2),0 1px 2px rgba(0,0,0,0.05)}.btn-group>.btn-mini+.dropdown-toggle{*padding-top:2px;padding-right:5px;*padding-bottom:2px;padding-left:5px}.btn-group>.btn-small+.dropdown-toggle{*padding-top:5px;*padding-bottom:4px}.btn-group>.btn-large+.dropdown-toggle{*padding-top:7px;padding-right:12px;*padding-bottom:7px;padding-left:12px}.btn-group.open .dropdown-toggle{background-image:none;-webkit-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05);box-shadow:inset 0 2px 4px rgba(0,0,0,0.15),0 1px 2px rgba(0,0,0,0.05)}.btn-group.open .btn.dropdown-toggle{background-color:#e6e6e6}.btn-group.open .btn-primary.dropdown-toggle{background-color:#04c}.btn-group.open .btn-warning.dropdown-toggle{background-color:#f89406}.btn-group.open .btn-danger.dropdown-toggle{background-color:#bd362f}.btn-group.open .btn-success.dropdown-toggle{background-color:#51a351}.btn-group.open .btn-info.dropdown-toggle{background-color:#2f96b4}.btn-group.open .btn-inverse.dropdown-toggle{background-color:#222}.btn .caret{margin-top:8px;margin-left:0}.btn-large .caret{margin-top:6px}.btn-large .caret{border-top-width:5px;border-right-width:5px;border-left-width:5px}.btn-mini .caret,.btn-small .caret{margin-top:8px}.dropup .btn-large .caret{border-bottom-width:5px}.btn-primary .caret,.btn-warning .caret,.btn-danger .caret,.btn-info .caret,.btn-success .caret,.btn-inverse .caret{border-top-color:#fff;border-bottom-color:#fff}.btn-group-vertical{display:inline-block;*display:inline;*zoom:1}.btn-group-vertical>.btn{display:block;float:none;max-width:100%;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.btn-group-vertical>.btn+.btn{margin-top:-1px;margin-left:0}.btn-group-vertical>.btn:first-child{-webkit-border-radius:4px 4px 0 0;-moz-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0}.btn-group-vertical>.btn:last-child{-webkit-border-radius:0 0 4px 4px;-moz-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px}.btn-group-vertical>.btn-large:first-child{-webkit-border-radius:6px 6px 0 0;-moz-border-radius:6px 6px 0 0;border-radius:6px 6px 0 0}.btn-group-vertical>.btn-large:last-child{-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px}.alert{padding:8px 35px 8px 14px;margin-bottom:20px;text-shadow:0 1px 0 rgba(255,255,255,0.5);background-color:#fcf8e3;border:1px solid #fbeed5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.alert,.alert h4{color:#c09853}.alert h4{margin:0}.alert .close{position:relative;top:-2px;right:-21px;line-height:20px}.alert-success{color:#468847;background-color:#dff0d8;border-color:#d6e9c6}.alert-success h4{color:#468847}.alert-danger,.alert-error{color:#b94a48;background-color:#f2dede;border-color:#eed3d7}.alert-danger h4,.alert-error h4{color:#b94a48}.alert-info{color:#3a87ad;background-color:#d9edf7;border-color:#bce8f1}.alert-info h4{color:#3a87ad}.alert-block{padding-top:14px;padding-bottom:14px}.alert-block>p,.alert-block>ul{margin-bottom:0}.alert-block p+p{margin-top:5px}.nav{margin-bottom:20px;margin-left:0;list-style:none}.nav>li>a{display:block}.nav>li>a:hover,.nav>li>a:focus{text-decoration:none;background-color:#eee}.nav>li>a>img{max-width:none}.nav>.pull-right{float:right}.nav-header{display:block;padding:3px 15px;font-size:11px;font-weight:bold;line-height:20px;color:#999;text-shadow:0 1px 0 rgba(255,255,255,0.5);text-transform:uppercase}.nav li+.nav-header{margin-top:9px}.nav-list{padding-right:15px;padding-left:15px;margin-bottom:0}.nav-list>li>a,.nav-list .nav-header{margin-right:-15px;margin-left:-15px;text-shadow:0 1px 0 rgba(255,255,255,0.5)}.nav-list>li>a{padding:3px 15px}.nav-list>.active>a,.nav-list>.active>a:hover,.nav-list>.active>a:focus{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.2);background-color:#08c}.nav-list [class^="icon-"],.nav-list [class*=" icon-"]{margin-right:2px}.nav-list .divider{*width:100%;height:1px;margin:9px 1px;*margin:-5px 0 5px;overflow:hidden;background-color:#e5e5e5;border-bottom:1px solid #fff}.nav-tabs,.nav-pills{*zoom:1}.nav-tabs:before,.nav-pills:before,.nav-tabs:after,.nav-pills:after{display:table;line-height:0;content:""}.nav-tabs:after,.nav-pills:after{clear:both}.nav-tabs>li,.nav-pills>li{float:left}.nav-tabs>li>a,.nav-pills>li>a{padding-right:12px;padding-left:12px;margin-right:2px;line-height:14px}.nav-tabs{border-bottom:1px solid #ddd}.nav-tabs>li{margin-bottom:-1px}.nav-tabs>li>a{padding-top:8px;padding-bottom:8px;line-height:20px;border:1px solid transparent;-webkit-border-radius:4px 4px 0 0;-moz-border-radius:4px 4px 0 0;border-radius:4px 4px 0 0}.nav-tabs>li>a:hover,.nav-tabs>li>a:focus{border-color:#eee #eee #ddd}.nav-tabs>.active>a,.nav-tabs>.active>a:hover,.nav-tabs>.active>a:focus{color:#555;cursor:default;background-color:#fff;border:1px solid #ddd;border-bottom-color:transparent}.nav-pills>li>a{padding-top:8px;padding-bottom:8px;margin-top:2px;margin-bottom:2px;-webkit-border-radius:5px;-moz-border-radius:5px;border-radius:5px}.nav-pills>.active>a,.nav-pills>.active>a:hover,.nav-pills>.active>a:focus{color:#fff;background-color:#08c}.nav-stacked>li{float:none}.nav-stacked>li>a{margin-right:0}.nav-tabs.nav-stacked{border-bottom:0}.nav-tabs.nav-stacked>li>a{border:1px solid #ddd;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.nav-tabs.nav-stacked>li:first-child>a{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-topleft:4px}.nav-tabs.nav-stacked>li:last-child>a{-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-moz-border-radius-bottomright:4px;-moz-border-radius-bottomleft:4px}.nav-tabs.nav-stacked>li>a:hover,.nav-tabs.nav-stacked>li>a:focus{z-index:2;border-color:#ddd}.nav-pills.nav-stacked>li>a{margin-bottom:3px}.nav-pills.nav-stacked>li:last-child>a{margin-bottom:1px}.nav-tabs .dropdown-menu{-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px}.nav-pills .dropdown-menu{-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.nav .dropdown-toggle .caret{margin-top:6px;border-top-color:#08c;border-bottom-color:#08c}.nav .dropdown-toggle:hover .caret,.nav .dropdown-toggle:focus .caret{border-top-color:#005580;border-bottom-color:#005580}.nav-tabs .dropdown-toggle .caret{margin-top:8px}.nav .active .dropdown-toggle .caret{border-top-color:#fff;border-bottom-color:#fff}.nav-tabs .active .dropdown-toggle .caret{border-top-color:#555;border-bottom-color:#555}.nav>.dropdown.active>a:hover,.nav>.dropdown.active>a:focus{cursor:pointer}.nav-tabs .open .dropdown-toggle,.nav-pills .open .dropdown-toggle,.nav>li.dropdown.open.active>a:hover,.nav>li.dropdown.open.active>a:focus{color:#fff;background-color:#999;border-color:#999}.nav li.dropdown.open .caret,.nav li.dropdown.open.active .caret,.nav li.dropdown.open a:hover .caret,.nav li.dropdown.open a:focus .caret{border-top-color:#fff;border-bottom-color:#fff;opacity:1;filter:alpha(opacity=100)}.tabs-stacked .open>a:hover,.tabs-stacked .open>a:focus{border-color:#999}.tabbable{*zoom:1}.tabbable:before,.tabbable:after{display:table;line-height:0;content:""}.tabbable:after{clear:both}.tab-content{overflow:auto}.tabs-below>.nav-tabs,.tabs-right>.nav-tabs,.tabs-left>.nav-tabs{border-bottom:0}.tab-content>.tab-pane,.pill-content>.pill-pane{display:none}.tab-content>.active,.pill-content>.active{display:block}.tabs-below>.nav-tabs{border-top:1px solid #ddd}.tabs-below>.nav-tabs>li{margin-top:-1px;margin-bottom:0}.tabs-below>.nav-tabs>li>a{-webkit-border-radius:0 0 4px 4px;-moz-border-radius:0 0 4px 4px;border-radius:0 0 4px 4px}.tabs-below>.nav-tabs>li>a:hover,.tabs-below>.nav-tabs>li>a:focus{border-top-color:#ddd;border-bottom-color:transparent}.tabs-below>.nav-tabs>.active>a,.tabs-below>.nav-tabs>.active>a:hover,.tabs-below>.nav-tabs>.active>a:focus{border-color:transparent #ddd #ddd #ddd}.tabs-left>.nav-tabs>li,.tabs-right>.nav-tabs>li{float:none}.tabs-left>.nav-tabs>li>a,.tabs-right>.nav-tabs>li>a{min-width:74px;margin-right:0;margin-bottom:3px}.tabs-left>.nav-tabs{float:left;margin-right:19px;border-right:1px solid #ddd}.tabs-left>.nav-tabs>li>a{margin-right:-1px;-webkit-border-radius:4px 0 0 4px;-moz-border-radius:4px 0 0 4px;border-radius:4px 0 0 4px}.tabs-left>.nav-tabs>li>a:hover,.tabs-left>.nav-tabs>li>a:focus{border-color:#eee #ddd #eee #eee}.tabs-left>.nav-tabs .active>a,.tabs-left>.nav-tabs .active>a:hover,.tabs-left>.nav-tabs .active>a:focus{border-color:#ddd transparent #ddd #ddd;*border-right-color:#fff}.tabs-right>.nav-tabs{float:right;margin-left:19px;border-left:1px solid #ddd}.tabs-right>.nav-tabs>li>a{margin-left:-1px;-webkit-border-radius:0 4px 4px 0;-moz-border-radius:0 4px 4px 0;border-radius:0 4px 4px 0}.tabs-right>.nav-tabs>li>a:hover,.tabs-right>.nav-tabs>li>a:focus{border-color:#eee #eee #eee #ddd}.tabs-right>.nav-tabs .active>a,.tabs-right>.nav-tabs .active>a:hover,.tabs-right>.nav-tabs .active>a:focus{border-color:#ddd #ddd #ddd transparent;*border-left-color:#fff}.nav>.disabled>a{color:#999}.nav>.disabled>a:hover,.nav>.disabled>a:focus{text-decoration:none;cursor:default;background-color:transparent}.navbar{*position:relative;*z-index:2;margin-bottom:20px;overflow:visible}.navbar-inner{min-height:40px;padding-right:20px;padding-left:20px;background-color:#fafafa;background-image:-moz-linear-gradient(top,#fff,#f2f2f2);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fff),to(#f2f2f2));background-image:-webkit-linear-gradient(top,#fff,#f2f2f2);background-image:-o-linear-gradient(top,#fff,#f2f2f2);background-image:linear-gradient(to bottom,#fff,#f2f2f2);background-repeat:repeat-x;border:1px solid #d4d4d4;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff',endColorstr='#fff2f2f2',GradientType=0);*zoom:1;-webkit-box-shadow:0 1px 4px rgba(0,0,0,0.065);-moz-box-shadow:0 1px 4px rgba(0,0,0,0.065);box-shadow:0 1px 4px rgba(0,0,0,0.065)}.navbar-inner:before,.navbar-inner:after{display:table;line-height:0;content:""}.navbar-inner:after{clear:both}.navbar .container{width:auto}.nav-collapse.collapse{height:auto;overflow:visible}.navbar .brand{display:block;float:left;padding:10px 20px 10px;margin-left:-20px;font-size:20px;font-weight:200;color:#777;text-shadow:0 1px 0 #fff}.navbar .brand:hover,.navbar .brand:focus{text-decoration:none}.navbar-text{margin-bottom:0;line-height:40px;color:#777}.navbar-link{color:#777}.navbar-link:hover,.navbar-link:focus{color:#333}.navbar .divider-vertical{height:40px;margin:0 9px;border-right:1px solid #fff;border-left:1px solid #f2f2f2}.navbar .btn,.navbar .btn-group{margin-top:5px}.navbar .btn-group .btn,.navbar .input-prepend .btn,.navbar .input-append .btn,.navbar .input-prepend .btn-group,.navbar .input-append .btn-group{margin-top:0}.navbar-form{margin-bottom:0;*zoom:1}.navbar-form:before,.navbar-form:after{display:table;line-height:0;content:""}.navbar-form:after{clear:both}.navbar-form input,.navbar-form select,.navbar-form .radio,.navbar-form .checkbox{margin-top:5px}.navbar-form input,.navbar-form select,.navbar-form .btn{display:inline-block;margin-bottom:0}.navbar-form input[type="image"],.navbar-form input[type="checkbox"],.navbar-form input[type="radio"]{margin-top:3px}.navbar-form .input-append,.navbar-form .input-prepend{margin-top:5px;white-space:nowrap}.navbar-form .input-append input,.navbar-form .input-prepend input{margin-top:0}.navbar-search{position:relative;float:left;margin-top:5px;margin-bottom:0}.navbar-search .search-query{padding:4px 14px;margin-bottom:0;font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:13px;font-weight:normal;line-height:1;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.navbar-static-top{position:static;margin-bottom:0}.navbar-static-top .navbar-inner{-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.navbar-fixed-top,.navbar-fixed-bottom{position:fixed;right:0;left:0;z-index:1030;margin-bottom:0}.navbar-fixed-top .navbar-inner,.navbar-static-top .navbar-inner{border-width:0 0 1px}.navbar-fixed-bottom .navbar-inner{border-width:1px 0 0}.navbar-fixed-top .navbar-inner,.navbar-fixed-bottom .navbar-inner{padding-right:0;padding-left:0;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0}.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:940px}.navbar-fixed-top{top:0}.navbar-fixed-top .navbar-inner,.navbar-static-top .navbar-inner{-webkit-box-shadow:0 1px 10px rgba(0,0,0,0.1);-moz-box-shadow:0 1px 10px rgba(0,0,0,0.1);box-shadow:0 1px 10px rgba(0,0,0,0.1)}.navbar-fixed-bottom{bottom:0}.navbar-fixed-bottom .navbar-inner{-webkit-box-shadow:0 -1px 10px rgba(0,0,0,0.1);-moz-box-shadow:0 -1px 10px rgba(0,0,0,0.1);box-shadow:0 -1px 10px rgba(0,0,0,0.1)}.navbar .nav{position:relative;left:0;display:block;float:left;margin:0 10px 0 0}.navbar .nav.pull-right{float:right;margin-right:0}.navbar .nav>li{float:left}.navbar .nav>li>a{float:none;padding:10px 15px 10px;color:#777;text-decoration:none;text-shadow:0 1px 0 #fff}.navbar .nav .dropdown-toggle .caret{margin-top:8px}.navbar .nav>li>a:focus,.navbar .nav>li>a:hover{color:#333;text-decoration:none;background-color:transparent}.navbar .nav>.active>a,.navbar .nav>.active>a:hover,.navbar .nav>.active>a:focus{color:#555;text-decoration:none;background-color:#e5e5e5;-webkit-box-shadow:inset 0 3px 8px rgba(0,0,0,0.125);-moz-box-shadow:inset 0 3px 8px rgba(0,0,0,0.125);box-shadow:inset 0 3px 8px rgba(0,0,0,0.125)}.navbar .btn-navbar{display:none;float:right;padding:7px 10px;margin-right:5px;margin-left:5px;color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#ededed;*background-color:#e5e5e5;background-image:-moz-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:-webkit-gradient(linear,0 0,0 100%,from(#f2f2f2),to(#e5e5e5));background-image:-webkit-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:-o-linear-gradient(top,#f2f2f2,#e5e5e5);background-image:linear-gradient(to bottom,#f2f2f2,#e5e5e5);background-repeat:repeat-x;border-color:#e5e5e5 #e5e5e5 #bfbfbf;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff2f2f2',endColorstr='#ffe5e5e5',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false);-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075);box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.075)}.navbar .btn-navbar:hover,.navbar .btn-navbar:focus,.navbar .btn-navbar:active,.navbar .btn-navbar.active,.navbar .btn-navbar.disabled,.navbar .btn-navbar[disabled]{color:#fff;background-color:#e5e5e5;*background-color:#d9d9d9}.navbar .btn-navbar:active,.navbar .btn-navbar.active{background-color:#ccc \9}.navbar .btn-navbar .ico-bar{display:block;width:18px;height:2px;background-color:#f5f5f5;-webkit-border-radius:1px;-moz-border-radius:1px;border-radius:1px;-webkit-box-shadow:0 1px 0 rgba(0,0,0,0.25);-moz-box-shadow:0 1px 0 rgba(0,0,0,0.25);box-shadow:0 1px 0 rgba(0,0,0,0.25)}.btn-navbar .ico-bar+.ico-bar{margin-top:3px}.navbar .nav>li>.dropdown-menu:before{position:absolute;top:-7px;left:9px;display:inline-block;border-right:7px solid transparent;border-bottom:7px solid #ccc;border-left:7px solid transparent;border-bottom-color:rgba(0,0,0,0.2);content:''}.navbar .nav>li>.dropdown-menu:after{position:absolute;top:-6px;left:10px;display:inline-block;border-right:6px solid transparent;border-bottom:6px solid #fff;border-left:6px solid transparent;content:''}.navbar-fixed-bottom .nav>li>.dropdown-menu:before{top:auto;bottom:-7px;border-top:7px solid #ccc;border-bottom:0;border-top-color:rgba(0,0,0,0.2)}.navbar-fixed-bottom .nav>li>.dropdown-menu:after{top:auto;bottom:-6px;border-top:6px solid #fff;border-bottom:0}.navbar .nav li.dropdown>a:hover .caret,.navbar .nav li.dropdown>a:focus .caret{border-top-color:#333;border-bottom-color:#333}.navbar .nav li.dropdown.open>.dropdown-toggle,.navbar .nav li.dropdown.active>.dropdown-toggle,.navbar .nav li.dropdown.open.active>.dropdown-toggle{color:#555;background-color:#e5e5e5}.navbar .nav li.dropdown>.dropdown-toggle .caret{border-top-color:#777;border-bottom-color:#777}.navbar .nav li.dropdown.open>.dropdown-toggle .caret,.navbar .nav li.dropdown.active>.dropdown-toggle .caret,.navbar .nav li.dropdown.open.active>.dropdown-toggle .caret{border-top-color:#555;border-bottom-color:#555}.navbar .pull-right>li>.dropdown-menu,.navbar .nav>li>.dropdown-menu.pull-right{right:0;left:auto}.navbar .pull-right>li>.dropdown-menu:before,.navbar .nav>li>.dropdown-menu.pull-right:before{right:12px;left:auto}.navbar .pull-right>li>.dropdown-menu:after,.navbar .nav>li>.dropdown-menu.pull-right:after{right:13px;left:auto}.navbar .pull-right>li>.dropdown-menu .dropdown-menu,.navbar .nav>li>.dropdown-menu.pull-right .dropdown-menu{right:100%;left:auto;margin-right:-1px;margin-left:0;-webkit-border-radius:6px 0 6px 6px;-moz-border-radius:6px 0 6px 6px;border-radius:6px 0 6px 6px}.navbar-inverse .navbar-inner{background-color:#1b1b1b;background-image:-moz-linear-gradient(top,#222,#111);background-image:-webkit-gradient(linear,0 0,0 100%,from(#222),to(#111));background-image:-webkit-linear-gradient(top,#222,#111);background-image:-o-linear-gradient(top,#222,#111);background-image:linear-gradient(to bottom,#222,#111);background-repeat:repeat-x;border-color:#252525;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff222222',endColorstr='#ff111111',GradientType=0)}.navbar-inverse .brand,.navbar-inverse .nav>li>a{color:#999;text-shadow:0 -1px 0 rgba(0,0,0,0.25)}.navbar-inverse .brand:hover,.navbar-inverse .nav>li>a:hover,.navbar-inverse .brand:focus,.navbar-inverse .nav>li>a:focus{color:#fff}.navbar-inverse .brand{color:#999}.navbar-inverse .navbar-text{color:#999}.navbar-inverse .nav>li>a:focus,.navbar-inverse .nav>li>a:hover{color:#fff;background-color:transparent}.navbar-inverse .nav .active>a,.navbar-inverse .nav .active>a:hover,.navbar-inverse .nav .active>a:focus{color:#fff;background-color:#111}.navbar-inverse .navbar-link{color:#999}.navbar-inverse .navbar-link:hover,.navbar-inverse .navbar-link:focus{color:#fff}.navbar-inverse .divider-vertical{border-right-color:#222;border-left-color:#111}.navbar-inverse .nav li.dropdown.open>.dropdown-toggle,.navbar-inverse .nav li.dropdown.active>.dropdown-toggle,.navbar-inverse .nav li.dropdown.open.active>.dropdown-toggle{color:#fff;background-color:#111}.navbar-inverse .nav li.dropdown>a:hover .caret,.navbar-inverse .nav li.dropdown>a:focus .caret{border-top-color:#fff;border-bottom-color:#fff}.navbar-inverse .nav li.dropdown>.dropdown-toggle .caret{border-top-color:#999;border-bottom-color:#999}.navbar-inverse .nav li.dropdown.open>.dropdown-toggle .caret,.navbar-inverse .nav li.dropdown.active>.dropdown-toggle .caret,.navbar-inverse .nav li.dropdown.open.active>.dropdown-toggle .caret{border-top-color:#fff;border-bottom-color:#fff}.navbar-inverse .navbar-search .search-query{color:#fff;background-color:#515151;border-color:#111;-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);box-shadow:inset 0 1px 2px rgba(0,0,0,0.1),0 1px 0 rgba(255,255,255,0.15);-webkit-transition:none;-moz-transition:none;-o-transition:none;transition:none}.navbar-inverse .navbar-search .search-query:-moz-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query:-ms-input-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query::-webkit-input-placeholder{color:#ccc}.navbar-inverse .navbar-search .search-query:focus,.navbar-inverse .navbar-search .search-query.focused{padding:5px 15px;color:#333;text-shadow:0 1px 0 #fff;background-color:#fff;border:0;outline:0;-webkit-box-shadow:0 0 3px rgba(0,0,0,0.15);-moz-box-shadow:0 0 3px rgba(0,0,0,0.15);box-shadow:0 0 3px rgba(0,0,0,0.15)}.navbar-inverse .btn-navbar{color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#0e0e0e;*background-color:#040404;background-image:-moz-linear-gradient(top,#151515,#040404);background-image:-webkit-gradient(linear,0 0,0 100%,from(#151515),to(#040404));background-image:-webkit-linear-gradient(top,#151515,#040404);background-image:-o-linear-gradient(top,#151515,#040404);background-image:linear-gradient(to bottom,#151515,#040404);background-repeat:repeat-x;border-color:#040404 #040404 #000;border-color:rgba(0,0,0,0.1) rgba(0,0,0,0.1) rgba(0,0,0,0.25);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff151515',endColorstr='#ff040404',GradientType=0);filter:progid:DXImageTransform.Microsoft.gradient(enabled=false)}.navbar-inverse .btn-navbar:hover,.navbar-inverse .btn-navbar:focus,.navbar-inverse .btn-navbar:active,.navbar-inverse .btn-navbar.active,.navbar-inverse .btn-navbar.disabled,.navbar-inverse .btn-navbar[disabled]{color:#fff;background-color:#040404;*background-color:#000}.navbar-inverse .btn-navbar:active,.navbar-inverse .btn-navbar.active{background-color:#000 \9}.breadcrumb{padding:8px 15px;margin:0 0 20px;list-style:none;background-color:#f5f5f5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.breadcrumb>li{display:inline-block;*display:inline;text-shadow:0 1px 0 #fff;*zoom:1}.breadcrumb>li>.divider{padding:0 5px;color:#ccc}.breadcrumb>.active{color:#999}.pagination{margin:20px 0}.pagination ul{display:inline-block;*display:inline;margin-bottom:0;margin-left:0;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;*zoom:1;-webkit-box-shadow:0 1px 2px rgba(0,0,0,0.05);-moz-box-shadow:0 1px 2px rgba(0,0,0,0.05);box-shadow:0 1px 2px rgba(0,0,0,0.05)}.pagination ul>li{display:inline}.pagination ul>li>a,.pagination ul>li>span{float:left;padding:4px 12px;line-height:20px;text-decoration:none;background-color:#fff;border:1px solid #ddd;border-left-width:0}.pagination ul>li>a:hover,.pagination ul>li>a:focus,.pagination ul>.active>a,.pagination ul>.active>span{background-color:#f5f5f5}.pagination ul>.active>a,.pagination ul>.active>span{color:#999;cursor:default}.pagination ul>.disabled>span,.pagination ul>.disabled>a,.pagination ul>.disabled>a:hover,.pagination ul>.disabled>a:focus{color:#999;cursor:default;background-color:transparent}.pagination ul>li:first-child>a,.pagination ul>li:first-child>span{border-left-width:1px;-webkit-border-bottom-left-radius:4px;border-bottom-left-radius:4px;-webkit-border-top-left-radius:4px;border-top-left-radius:4px;-moz-border-radius-bottomleft:4px;-moz-border-radius-topleft:4px}.pagination ul>li:last-child>a,.pagination ul>li:last-child>span{-webkit-border-top-right-radius:4px;border-top-right-radius:4px;-webkit-border-bottom-right-radius:4px;border-bottom-right-radius:4px;-moz-border-radius-topright:4px;-moz-border-radius-bottomright:4px}.paginatio-centered{text-align:center}.pagination-right{text-align:right}.pagination-large ul>li>a,.pagination-large ul>li>span{padding:11px 19px;font-size:17.5px}.pagination-large ul>li:first-child>a,.pagination-large ul>li:first-child>span{-webkit-border-bottom-left-radius:6px;border-bottom-left-radius:6px;-webkit-border-top-left-radius:6px;border-top-left-radius:6px;-moz-border-radius-bottomleft:6px;-moz-border-radius-topleft:6px}.pagination-large ul>li:last-child>a,.pagination-large ul>li:last-child>span{-webkit-border-top-right-radius:6px;border-top-right-radius:6px;-webkit-border-bottom-right-radius:6px;border-bottom-right-radius:6px;-moz-border-radius-topright:6px;-moz-border-radius-bottomright:6px}.pagination-mini ul>li:first-child>a,.pagination-small ul>li:first-child>a,.pagination-mini ul>li:first-child>span,.pagination-small ul>li:first-child>span{-webkit-border-bottom-left-radius:3px;border-bottom-left-radius:3px;-webkit-border-top-left-radius:3px;border-top-left-radius:3px;-moz-border-radius-bottomleft:3px;-moz-border-radius-topleft:3px}.pagination-mini ul>li:last-child>a,.pagination-small ul>li:last-child>a,.pagination-mini ul>li:last-child>span,.pagination-small ul>li:last-child>span{-webkit-border-top-right-radius:3px;border-top-right-radius:3px;-webkit-border-bottom-right-radius:3px;border-bottom-right-radius:3px;-moz-border-radius-topright:3px;-moz-border-radius-bottomright:3px}.pagination-small ul>li>a,.pagination-small ul>li>span{padding:2px 10px;font-size:11.9px}.pagination-mini ul>li>a,.pagination-mini ul>li>span{padding:0 6px;font-size:10.5px}.pager{margin:20px 0;text-align:center;list-style:none;*zoom:1}.pager:before,.pager:after{display:table;line-height:0;content:""}.pager:after{clear:both}.pager li{display:inline}.pager li>a,.pager li>span{display:inline-block;padding:5px 14px;background-color:#fff;border:1px solid #ddd;-webkit-border-radius:15px;-moz-border-radius:15px;border-radius:15px}.pager li>a:hover,.pager li>a:focus{text-decoration:none;background-color:#f5f5f5}.pager .next>a,.pager .next>span{float:right}.pager .previous>a,.pager .previous>span{float:left}.pager .disabled>a,.pager .disabled>a:hover,.pager .disabled>a:focus,.pager .disabled>span{color:#999;cursor:default;background-color:#fff}.modal-backdrop{position:fixed;top:0;right:0;bottom:0;left:0;z-index:1040;background-color:#000}.modal-backdrop.fade{opacity:0}.modal-backdrop,.modal-backdrop.fade.in{opacity:.8;filter:alpha(opacity=80)}.modal{position:fixed;top:10%;left:50%;z-index:1050;width:560px;margin-left:-280px;background-color:#fff;border:1px solid #999;border:1px solid rgba(0,0,0,0.3);*border:1px solid #999;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;outline:0;-webkit-box-shadow:0 3px 7px rgba(0,0,0,0.3);-moz-box-shadow:0 3px 7px rgba(0,0,0,0.3);box-shadow:0 3px 7px rgba(0,0,0,0.3);-webkit-background-clip:padding-box;-moz-background-clip:padding-box;background-clip:padding-box}.modal.fade{top:-25%;-webkit-transition:opacity .3s linear,top .3s ease-out;-moz-transition:opacity .3s linear,top .3s ease-out;-o-transition:opacity .3s linear,top .3s ease-out;transition:opacity .3s linear,top .3s ease-out}.modal.fade.in{top:10%}.modal-header{padding:9px 15px;border-bottom:1px solid #eee}.modal-header .close{margin-top:2px}.modal-header h3{margin:0;line-height:30px}.modal-body{position:relative;max-height:400px;padding:15px;overflow-y:auto}.modal-form{margin-bottom:0}.modal-footer{padding:14px 15px 15px;margin-bottom:0;text-align:right;background-color:#f5f5f5;border-top:1px solid #ddd;-webkit-border-radius:0 0 6px 6px;-moz-border-radius:0 0 6px 6px;border-radius:0 0 6px 6px;*zoom:1;-webkit-box-shadow:inset 0 1px 0 #fff;-moz-box-shadow:inset 0 1px 0 #fff;box-shadow:inset 0 1px 0 #fff}.modal-footer:before,.modal-footer:after{display:table;line-height:0;content:""}.modal-footer:after{clear:both}.modal-footer .btn+.btn{margin-bottom:0;margin-left:5px}.modal-footer .btn-group .btn+.btn{margin-left:-1px}.modal-footer .btn-block+.btn-block{margin-left:0}.tooltip{position:absolute;z-index:1030;display:block;font-size:11px;line-height:1.4;opacity:0;filter:alpha(opacity=0);visibility:visible}.tooltip.in{opacity:.8;filter:alpha(opacity=80)}.tooltip.top{padding:5px 0;margin-top:-3px}.tooltip.right{padding:0 5px;margin-left:3px}.tooltip.bottom{padding:5px 0;margin-top:3px}.tooltip.left{padding:0 5px;margin-left:-3px}.tooltip-inner{max-width:200px;padding:8px;color:#fff;text-align:center;text-decoration:none;background-color:#000;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.tooltip-arrow{position:absolute;width:0;height:0;border-color:transparent;border-style:solid}.tooltip.top .tooltip-arrow{bottom:0;left:50%;margin-left:-5px;border-top-color:#000;border-width:5px 5px 0}.tooltip.right .tooltip-arrow{top:50%;left:0;margin-top:-5px;border-right-color:#000;border-width:5px 5px 5px 0}.tooltip.left .tooltip-arrow{top:50%;right:0;margin-top:-5px;border-left-color:#000;border-width:5px 0 5px 5px}.tooltip.bottom .tooltip-arrow{top:0;left:50%;margin-left:-5px;border-bottom-color:#000;border-width:0 5px 5px}.popover{position:absolute;top:0;left:0;z-index:1010;display:none;max-width:276px;padding:1px;text-align:left;white-space:normal;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.2);-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px;-webkit-box-shadow:0 5px 10px rgba(0,0,0,0.2);-moz-box-shadow:0 5px 10px rgba(0,0,0,0.2);box-shadow:0 5px 10px rgba(0,0,0,0.2);-webkit-background-clip:padding-box;-moz-background-clip:padding;background-clip:padding-box}.popover.top{margin-top:-10px}.popover.right{margin-left:10px}.popover.bottom{margin-top:10px}.popover.left{margin-left:-10px}.popover-title{padding:8px 14px;margin:0;font-size:14px;font-weight:normal;line-height:18px;background-color:#f7f7f7;border-bottom:1px solid #ebebeb;-webkit-border-radius:5px 5px 0 0;-moz-border-radius:5px 5px 0 0;border-radius:5px 5px 0 0}.popover-title:empty{display:none}.popover-content{padding:9px 14px}.popover .arrow,.popover .arrow:after{position:absolute;display:block;width:0;height:0;border-color:transparent;border-style:solid}.popover .arrow{border-width:11px}.popover .arrow:after{border-width:10px;content:""}.popover.top .arrow{bottom:-11px;left:50%;margin-left:-11px;border-top-color:#999;border-top-color:rgba(0,0,0,0.25);border-bottom-width:0}.popover.top .arrow:after{bottom:1px;margin-left:-10px;border-top-color:#fff;border-bottom-width:0}.popover.right .arrow{top:50%;left:-11px;margin-top:-11px;border-right-color:#999;border-right-color:rgba(0,0,0,0.25);border-left-width:0}.popover.right .arrow:after{bottom:-10px;left:1px;border-right-color:#fff;border-left-width:0}.popover.bottom .arrow{top:-11px;left:50%;margin-left:-11px;border-bottom-color:#999;border-bottom-color:rgba(0,0,0,0.25);border-top-width:0}.popover.bottom .arrow:after{top:1px;margin-left:-10px;border-bottom-color:#fff;border-top-width:0}.popover.left .arrow{top:50%;right:-11px;margin-top:-11px;border-left-color:#999;border-left-color:rgba(0,0,0,0.25);border-right-width:0}.popover.left .arrow:after{right:1px;bottom:-10px;border-left-color:#fff;border-right-width:0}.thumbnails{margin-left:-20px;list-style:none;*zoom:1}.thumbnails:before,.thumbnails:after{display:table;line-height:0;content:""}.thumbnails:after{clear:both}.row-fluid .thumbnails{margin-left:0}.thumbnails>li{float:left;margin-bottom:20px;margin-left:20px}.thumbnail{display:block;padding:4px;line-height:20px;border:1px solid #ddd;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.055);-moz-box-shadow:0 1px 3px rgba(0,0,0,0.055);box-shadow:0 1px 3px rgba(0,0,0,0.055);-webkit-transition:all .2s ease-in-out;-moz-transition:all .2s ease-in-out;-o-transition:all .2s ease-in-out;transition:all .2s ease-in-out}a.thumbnail:hover,a.thumbnail:focus{border-color:#08c;-webkit-box-shadow:0 1px 4px rgba(0,105,214,0.25);-moz-box-shadow:0 1px 4px rgba(0,105,214,0.25);box-shadow:0 1px 4px rgba(0,105,214,0.25)}.thumbnail>img{display:block;max-width:100%;margin-right:auto;margin-left:auto}.thumbnail .caption{padding:9px;color:#555}.media,.media-body{overflow:hidden;*overflow:visible;zoom:1}.media,.media .media{margin-top:15px}.media:first-child{margin-top:0}.media-object{display:block}.media-heading{margin:0 0 5px}.media>.pull-left{margin-right:10px}.media>.pull-right{margin-left:10px}.media-list{margin-left:0;list-style:none}.label,.badge{display:inline-block;padding:2px 4px;font-size:11.844px;font-weight:bold;line-height:14px;color:#fff;text-shadow:0 -1px 0 rgba(0,0,0,0.25);white-space:nowrap;vertical-align:baseline;background-color:#999}.label{-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.badge{padding-right:9px;padding-left:9px;-webkit-border-radius:9px;-moz-border-radius:9px;border-radius:9px}.label:empty,.badge:empty{display:none}a.label:hover,a.label:focus,a.badge:hover,a.badge:focus{color:#fff;text-decoration:none;cursor:pointer}.label-important,.badge-important{background-color:#b94a48}.label-important[href],.badge-important[href]{background-color:#953b39}.label-warning,.badge-warning{background-color:#f89406}.label-warning[href],.badge-warning[href]{background-color:#c67605}.label-success,.badge-success{background-color:#468847}.label-success[href],.badge-success[href]{background-color:#356635}.label-info,.badge-info{background-color:#3a87ad}.label-info[href],.badge-info[href]{background-color:#2d6987}.label-inverse,.badge-inverse{background-color:#333}.label-inverse[href],.badge-inverse[href]{background-color:#1a1a1a}.btn .label,.btn .badge{position:relative;top:-1px}.btn-mini .label,.btn-mini .badge{top:0}@-webkit-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-moz-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-ms-keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}@-o-keyframes progress-bar-stripes{from{background-position:0 0}to{background-position:40px 0}}@keyframes progress-bar-stripes{from{background-position:40px 0}to{background-position:0 0}}.progress{height:20px;margin-bottom:20px;overflow:hidden;background-color:#f7f7f7;background-image:-moz-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:-webkit-gradient(linear,0 0,0 100%,from(#f5f5f5),to(#f9f9f9));background-image:-webkit-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:-o-linear-gradient(top,#f5f5f5,#f9f9f9);background-image:linear-gradient(to bottom,#f5f5f5,#f9f9f9);background-repeat:repeat-x;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fff5f5f5',endColorstr='#fff9f9f9',GradientType=0);-webkit-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1);-moz-box-shadow:inset 0 1px 2px rgba(0,0,0,0.1);box-shadow:inset 0 1px 2px rgba(0,0,0,0.1)}.progress .bar{float:left;width:0;height:100%;font-size:12px;color:#fff;text-align:center;text-shadow:0 -1px 0 rgba(0,0,0,0.25);background-color:#0e90d2;background-image:-moz-linear-gradient(top,#149bdf,#0480be);background-image:-webkit-gradient(linear,0 0,0 100%,from(#149bdf),to(#0480be));background-image:-webkit-linear-gradient(top,#149bdf,#0480be);background-image:-o-linear-gradient(top,#149bdf,#0480be);background-image:linear-gradient(to bottom,#149bdf,#0480be);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff149bdf',endColorstr='#ff0480be',GradientType=0);-webkit-box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);-moz-box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);box-shadow:inset 0 -1px 0 rgba(0,0,0,0.15);-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;-webkit-transition:width .6s ease;-moz-transition:width .6s ease;-o-transition:width .6s ease;transition:width .6s ease}.progress .bar+.bar{-webkit-box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15);-moz-box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15);box-shadow:inset 1px 0 0 rgba(0,0,0,0.15),inset 0 -1px 0 rgba(0,0,0,0.15)}.progress-striped .bar{background-color:#149bdf;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);-webkit-background-size:40px 40px;-moz-background-size:40px 40px;-o-background-size:40px 40px;background-size:40px 40px}.progress.active .bar{-webkit-animation:progress-bar-stripes 2s linear infinite;-moz-animation:progress-bar-stripes 2s linear infinite;-ms-animation:progress-bar-stripes 2s linear infinite;-o-animation:progress-bar-stripes 2s linear infinite;animation:progress-bar-stripes 2s linear infinite}.progress-danger .bar,.progress .bar-danger{background-color:#dd514c;background-image:-moz-linear-gradient(top,#ee5f5b,#c43c35);background-image:-webkit-gradient(linear,0 0,0 100%,from(#ee5f5b),to(#c43c35));background-image:-webkit-linear-gradient(top,#ee5f5b,#c43c35);background-image:-o-linear-gradient(top,#ee5f5b,#c43c35);background-image:linear-gradient(to bottom,#ee5f5b,#c43c35);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffee5f5b',endColorstr='#ffc43c35',GradientType=0)}.progress-danger.progress-striped .bar,.progress-striped .bar-danger{background-color:#ee5f5b;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-success .bar,.progress .bar-success{background-color:#5eb95e;background-image:-moz-linear-gradient(top,#62c462,#57a957);background-image:-webkit-gradient(linear,0 0,0 100%,from(#62c462),to(#57a957));background-image:-webkit-linear-gradient(top,#62c462,#57a957);background-image:-o-linear-gradient(top,#62c462,#57a957);background-image:linear-gradient(to bottom,#62c462,#57a957);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff62c462',endColorstr='#ff57a957',GradientType=0)}.progress-success.progress-striped .bar,.progress-striped .bar-success{background-color:#62c462;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-info .bar,.progress .bar-info{background-color:#4bb1cf;background-image:-moz-linear-gradient(top,#5bc0de,#339bb9);background-image:-webkit-gradient(linear,0 0,0 100%,from(#5bc0de),to(#339bb9));background-image:-webkit-linear-gradient(top,#5bc0de,#339bb9);background-image:-o-linear-gradient(top,#5bc0de,#339bb9);background-image:linear-gradient(to bottom,#5bc0de,#339bb9);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff5bc0de',endColorstr='#ff339bb9',GradientType=0)}.progress-info.progress-striped .bar,.progress-striped .bar-info{background-color:#5bc0de;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.progress-warning .bar,.progress .bar-warning{background-color:#faa732;background-image:-moz-linear-gradient(top,#fbb450,#f89406);background-image:-webkit-gradient(linear,0 0,0 100%,from(#fbb450),to(#f89406));background-image:-webkit-linear-gradient(top,#fbb450,#f89406);background-image:-o-linear-gradient(top,#fbb450,#f89406);background-image:linear-gradient(to bottom,#fbb450,#f89406);background-repeat:repeat-x;filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fffbb450',endColorstr='#fff89406',GradientType=0)}.progress-warning.progress-striped .bar,.progress-striped .bar-warning{background-color:#fbb450;background-image:-webkit-gradient(linear,0 100%,100% 0,color-stop(0.25,rgba(255,255,255,0.15)),color-stop(0.25,transparent),color-stop(0.5,transparent),color-stop(0.5,rgba(255,255,255,0.15)),color-stop(0.75,rgba(255,255,255,0.15)),color-stop(0.75,transparent),to(transparent));background-image:-webkit-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-moz-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:-o-linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent);background-image:linear-gradient(45deg,rgba(255,255,255,0.15) 25%,transparent 25%,transparent 50%,rgba(255,255,255,0.15) 50%,rgba(255,255,255,0.15) 75%,transparent 75%,transparent)}.accordion{margin-bottom:20px}.accordion-group{margin-bottom:2px;border:1px solid #e5e5e5;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.accordion-heading{border-bottom:0}.accordion-heading .accordion-toggle{display:block;padding:8px 15px}.accordion-toggle{cursor:pointer}.accordion-inner{padding:9px 15px;border-top:1px solid #e5e5e5}.carousel{position:relative;margin-bottom:20px;line-height:1}.carousel-inner{position:relative;width:100%;overflow:hidden}.carousel-inner>.item{position:relative;display:none;-webkit-transition:.6s ease-in-out left;-moz-transition:.6s ease-in-out left;-o-transition:.6s ease-in-out left;transition:.6s ease-in-out left}.carousel-inner>.item>img,.carousel-inner>.item>a>img{display:block;line-height:1}.carousel-inner>.active,.carousel-inner>.next,.carousel-inner>.prev{display:block}.carousel-inner>.active{left:0}.carousel-inner>.next,.carousel-inner>.prev{position:absolute;top:0;width:100%}.carousel-inner>.next{left:100%}.carousel-inner>.prev{left:-100%}.carousel-inner>.next.left,.carousel-inner>.prev.right{left:0}.carousel-inner>.active.left{left:-100%}.carousel-inner>.active.right{left:100%}.carousel-control{position:absolute;top:40%;left:15px;width:40px;height:40px;margin-top:-20px;font-size:60px;font-weight:100;line-height:30px;color:#fff;text-align:center;background:#222;border:3px solid #fff;-webkit-border-radius:23px;-moz-border-radius:23px;border-radius:23px;opacity:.5;filter:alpha(opacity=50)}.carousel-control.right{right:15px;left:auto}.carousel-control:hover,.carousel-control:focus{color:#fff;text-decoration:none;opacity:.9;filter:alpha(opacity=90)}.carousel-indicators{position:absolute;top:15px;right:15px;z-index:5;margin:0;list-style:none}.carousel-indicators li{display:block;float:left;width:10px;height:10px;margin-left:5px;text-indent:-999px;background-color:#ccc;background-color:rgba(255,255,255,0.25);border-radius:5px}.carousel-indicators .active{background-color:#fff}.carousel-caption{position:absolute;right:0;bottom:0;left:0;padding:15px;background:#333;background:rgba(0,0,0,0.75)}.carousel-caption h4,.carousel-caption p{line-height:20px;color:#fff}.carousel-caption h4{margin:0 0 5px}.carousel-caption p{margin-bottom:0}.hero-unit{padding:60px;margin-bottom:30px;font-size:18px;font-weight:200;line-height:30px;color:inherit;background-color:#eee;-webkit-border-radius:6px;-moz-border-radius:6px;border-radius:6px}.hero-unit h1{margin-bottom:0;font-size:60px;line-height:1;letter-spacing:-1px;color:inherit}.hero-unit li{line-height:30px}.pull-right{float:right}.pull-left{float:left}.hide{display:none}.show{display:block}.invisible{visibility:hidden}.affix{position:fixed}
/*!
 * Bootstrap Responsive v2.3.0
 *
 * Copyright 2012 Twitter, Inc
 * Licensed under the Apache License v2.0
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Designed and built with all the love in the world @twitter by @mdo and @fat.
 */.clearfix{*zoom:1}.clearfix:before,.clearfix:after{display:table;line-height:0;content:""}.clearfix:after{clear:both}.hide-text{font:0/0 a;color:transparent;text-shadow:none;background-color:transparent;border:0}.input-block-level{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}@-ms-viewport{width:device-width}.hidden{display:none;visibility:hidden}.visible-phone{display:none!important}.visible-tablet{display:none!important}.hidden-desktop{display:none!important}.visible-desktop{display:inherit!important}@media(min-width:768px) and (max-width:979px){.hidden-desktop{display:inherit!important}.visible-desktop{display:none!important}.visible-tablet{display:inherit!important}.hidden-tablet{display:none!important}}@media(max-width:767px){.hidden-desktop{display:inherit!important}.visible-desktop{display:none!important}.visible-phone{display:inherit!important}.hidden-phone{display:none!important}}.visible-print{display:none!important}@media print{.visible-print{display:inherit!important}.hidden-print{display:none!important}}@media(min-width:1200px){.row{margin-left:-30px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:30px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:1170px}.span12{width:1170px}.span11{width:1070px}.span10{width:970px}.span9{width:870px}.span8{width:770px}.span7{width:670px}.span6{width:570px}.span5{width:470px}.span4{width:370px}.span3{width:270px}.span2{width:170px}.span1{width:70px}.offset12{margin-left:1230px}.offset11{margin-left:1130px}.offset10{margin-left:1030px}.offset9{margin-left:930px}.offset8{margin-left:830px}.offset7{margin-left:730px}.offset6{margin-left:630px}.offset5{margin-left:530px}.offset4{margin-left:430px}.offset3{margin-left:330px}.offset2{margin-left:230px}.offset1{margin-left:130px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.564102564102564%;*margin-left:2.5109110747408616%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.564102564102564%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.45299145299145%;*width:91.39979996362975%}.row-fluid .span10{width:82.90598290598291%;*width:82.8527914166212%}.row-fluid .span9{width:74.35897435897436%;*width:74.30578286961266%}.row-fluid .span8{width:65.81196581196582%;*width:65.75877432260411%}.row-fluid .span7{width:57.26495726495726%;*width:57.21176577559556%}.row-fluid .span6{width:48.717948717948715%;*width:48.664757228587014%}.row-fluid .span5{width:40.17094017094017%;*width:40.11774868157847%}.row-fluid .span4{width:31.623931623931625%;*width:31.570740134569924%}.row-fluid .span3{width:23.076923076923077%;*width:23.023731587561375%}.row-fluid .span2{width:14.52991452991453%;*width:14.476723040552828%}.row-fluid .span1{width:5.982905982905983%;*width:5.929714493544281%}.row-fluid .offset12{margin-left:105.12820512820512%;*margin-left:105.02182214948171%}.row-fluid .offset12:first-child{margin-left:102.56410256410257%;*margin-left:102.45771958537915%}.row-fluid .offset11{margin-left:96.58119658119658%;*margin-left:96.47481360247316%}.row-fluid .offset11:first-child{margin-left:94.01709401709402%;*margin-left:93.91071103837061%}.row-fluid .offset10{margin-left:88.03418803418803%;*margin-left:87.92780505546462%}.row-fluid .offset10:first-child{margin-left:85.47008547008548%;*margin-left:85.36370249136206%}.row-fluid .offset9{margin-left:79.48717948717949%;*margin-left:79.38079650845607%}.row-fluid .offset9:first-child{margin-left:76.92307692307693%;*margin-left:76.81669394435352%}.row-fluid .offset8{margin-left:70.94017094017094%;*margin-left:70.83378796144753%}.row-fluid .offset8:first-child{margin-left:68.37606837606839%;*margin-left:68.26968539734497%}.row-fluid .offset7{margin-left:62.393162393162385%;*margin-left:62.28677941443899%}.row-fluid .offset7:first-child{margin-left:59.82905982905982%;*margin-left:59.72267685033642%}.row-fluid .offset6{margin-left:53.84615384615384%;*margin-left:53.739770867430444%}.row-fluid .offset6:first-child{margin-left:51.28205128205128%;*margin-left:51.175668303327875%}.row-fluid .offset5{margin-left:45.299145299145295%;*margin-left:45.1927623204219%}.row-fluid .offset5:first-child{margin-left:42.73504273504273%;*margin-left:42.62865975631933%}.row-fluid .offset4{margin-left:36.75213675213675%;*margin-left:36.645753773413354%}.row-fluid .offset4:first-child{margin-left:34.18803418803419%;*margin-left:34.081651209310785%}.row-fluid .offset3{margin-left:28.205128205128204%;*margin-left:28.0987452264048%}.row-fluid .offset3:first-child{margin-left:25.641025641025642%;*margin-left:25.53464266230224%}.row-fluid .offset2{margin-left:19.65811965811966%;*margin-left:19.551736679396257%}.row-fluid .offset2:first-child{margin-left:17.094017094017094%;*margin-left:16.98763411529369%}.row-fluid .offset1{margin-left:11.11111111111111%;*margin-left:11.004728132387708%}.row-fluid .offset1:first-child{margin-left:8.547008547008547%;*margin-left:8.440625568285142%}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:30px}input.span12,textarea.span12,.uneditable-input.span12{width:1156px}input.span11,textarea.span11,.uneditable-input.span11{width:1056px}input.span10,textarea.span10,.uneditable-input.span10{width:956px}input.span9,textarea.span9,.uneditable-input.span9{width:856px}input.span8,textarea.span8,.uneditable-input.span8{width:756px}input.span7,textarea.span7,.uneditable-input.span7{width:656px}input.span6,textarea.span6,.uneditable-input.span6{width:556px}input.span5,textarea.span5,.uneditable-input.span5{width:456px}input.span4,textarea.span4,.uneditable-input.span4{width:356px}input.span3,textarea.span3,.uneditable-input.span3{width:256px}input.span2,textarea.span2,.uneditable-input.span2{width:156px}input.span1,textarea.span1,.uneditable-input.span1{width:56px}.thumbnails{margin-left:-30px}.thumbnails>li{margin-left:30px}.row-fluid .thumbnails{margin-left:0}}@media(min-width:768px) and (max-width:979px){.row{margin-left:-20px;*zoom:1}.row:before,.row:after{display:table;line-height:0;content:""}.row:after{clear:both}[class*="span"]{float:left;min-height:1px;margin-left:20px}.container,.navbar-static-top .container,.navbar-fixed-top .container,.navbar-fixed-bottom .container{width:724px}.span12{width:724px}.span11{width:662px}.span10{width:600px}.span9{width:538px}.span8{width:476px}.span7{width:414px}.span6{width:352px}.span5{width:290px}.span4{width:228px}.span3{width:166px}.span2{width:104px}.span1{width:42px}.offset12{margin-left:764px}.offset11{margin-left:702px}.offset10{margin-left:640px}.offset9{margin-left:578px}.offset8{margin-left:516px}.offset7{margin-left:454px}.offset6{margin-left:392px}.offset5{margin-left:330px}.offset4{margin-left:268px}.offset3{margin-left:206px}.offset2{margin-left:144px}.offset1{margin-left:82px}.row-fluid{width:100%;*zoom:1}.row-fluid:before,.row-fluid:after{display:table;line-height:0;content:""}.row-fluid:after{clear:both}.row-fluid [class*="span"]{display:block;float:left;width:100%;min-height:30px;margin-left:2.7624309392265194%;*margin-left:2.709239449864817%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="span"]:first-child{margin-left:0}.row-fluid .controls-row [class*="span"]+[class*="span"]{margin-left:2.7624309392265194%}.row-fluid .span12{width:100%;*width:99.94680851063829%}.row-fluid .span11{width:91.43646408839778%;*width:91.38327259903608%}.row-fluid .span10{width:82.87292817679558%;*width:82.81973668743387%}.row-fluid .span9{width:74.30939226519337%;*width:74.25620077583166%}.row-fluid .span8{width:65.74585635359117%;*width:65.69266486422946%}.row-fluid .span7{width:57.18232044198895%;*width:57.12912895262725%}.row-fluid .span6{width:48.61878453038674%;*width:48.56559304102504%}.row-fluid .span5{width:40.05524861878453%;*width:40.00205712942283%}.row-fluid .span4{width:31.491712707182323%;*width:31.43852121782062%}.row-fluid .span3{width:22.92817679558011%;*width:22.87498530621841%}.row-fluid .span2{width:14.3646408839779%;*width:14.311449394616199%}.row-fluid .span1{width:5.801104972375691%;*width:5.747913483013988%}.row-fluid .offset12{margin-left:105.52486187845304%;*margin-left:105.41847889972962%}.row-fluid .offset12:first-child{margin-left:102.76243093922652%;*margin-left:102.6560479605031%}.row-fluid .offset11{margin-left:96.96132596685082%;*margin-left:96.8549429881274%}.row-fluid .offset11:first-child{margin-left:94.1988950276243%;*margin-left:94.09251204890089%}.row-fluid .offset10{margin-left:88.39779005524862%;*margin-left:88.2914070765252%}.row-fluid .offset10:first-child{margin-left:85.6353591160221%;*margin-left:85.52897613729868%}.row-fluid .offset9{margin-left:79.8342541436464%;*margin-left:79.72787116492299%}.row-fluid .offset9:first-child{margin-left:77.07182320441989%;*margin-left:76.96544022569647%}.row-fluid .offset8{margin-left:71.2707182320442%;*margin-left:71.16433525332079%}.row-fluid .offset8:first-child{margin-left:68.50828729281768%;*margin-left:68.40190431409427%}.row-fluid .offset7{margin-left:62.70718232044199%;*margin-left:62.600799341718584%}.row-fluid .offset7:first-child{margin-left:59.94475138121547%;*margin-left:59.838368402492065%}.row-fluid .offset6{margin-left:54.14364640883978%;*margin-left:54.037263430116376%}.row-fluid .offset6:first-child{margin-left:51.38121546961326%;*margin-left:51.27483249088986%}.row-fluid .offset5{margin-left:45.58011049723757%;*margin-left:45.47372751851417%}.row-fluid .offset5:first-child{margin-left:42.81767955801105%;*margin-left:42.71129657928765%}.row-fluid .offset4{margin-left:37.01657458563536%;*margin-left:36.91019160691196%}.row-fluid .offset4:first-child{margin-left:34.25414364640884%;*margin-left:34.14776066768544%}.row-fluid .offset3{margin-left:28.45303867403315%;*margin-left:28.346655695309746%}.row-fluid .offset3:first-child{margin-left:25.69060773480663%;*margin-left:25.584224756083227%}.row-fluid .offset2{margin-left:19.88950276243094%;*margin-left:19.783119783707537%}.row-fluid .offset2:first-child{margin-left:17.12707182320442%;*margin-left:17.02068884448102%}.row-fluid .offset1{margin-left:11.32596685082873%;*margin-left:11.219583872105325%}.row-fluid .offset1:first-child{margin-left:8.56353591160221%;*margin-left:8.457152932878806%}input,textarea,.uneditable-input{margin-left:0}.controls-row [class*="span"]+[class*="span"]{margin-left:20px}input.span12,textarea.span12,.uneditable-input.span12{width:710px}input.span11,textarea.span11,.uneditable-input.span11{width:648px}input.span10,textarea.span10,.uneditable-input.span10{width:586px}input.span9,textarea.span9,.uneditable-input.span9{width:524px}input.span8,textarea.span8,.uneditable-input.span8{width:462px}input.span7,textarea.span7,.uneditable-input.span7{width:400px}input.span6,textarea.span6,.uneditable-input.span6{width:338px}input.span5,textarea.span5,.uneditable-input.span5{width:276px}input.span4,textarea.span4,.uneditable-input.span4{width:214px}input.span3,textarea.span3,.uneditable-input.span3{width:152px}input.span2,textarea.span2,.uneditable-input.span2{width:90px}input.span1,textarea.span1,.uneditable-input.span1{width:28px}}@media(max-width:767px){body{padding-right:20px;padding-left:20px}.navbar-fixed-top,.navbar-fixed-bottom,.navbar-static-top{margin-right:-20px;margin-left:-20px}.container-fluid{padding:0}.dl-horizontal dt{float:none;width:auto;clear:none;text-align:left}.dl-horizontal dd{margin-left:0}.container{width:auto}.row-fluid{width:100%}.row,.thumbnails{margin-left:0}.thumbnails>li{float:none;margin-left:0}[class*="span"],.uneditable-input[class*="span"],.row-fluid [class*="span"]{display:block;float:none;width:100%;margin-left:0;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.span12,.row-fluid .span12{width:100%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.row-fluid [class*="offset"]:first-child{margin-left:0}.input-large,.input-xlarge,.input-xxlarge,input[class*="span"],select[class*="span"],textarea[class*="span"],.uneditable-input{display:block;width:100%;min-height:30px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}.input-prepend input,.input-append input,.input-prepend input[class*="span"],.input-append input[class*="span"]{display:inline-block;width:auto}.controls-row [class*="span"]+[class*="span"]{margin-left:0}.modal{position:fixed;top:20px;right:20px;left:20px;width:auto;margin:0}.modal.fade{top:-100px}.modal.fade.in{top:20px}}@media(max-width:480px){.nav-collapse{-webkit-transform:translate3d(0,0,0)}.page-header h1 small{display:block;line-height:20px}input[type="checkbox"],input[type="radio"]{border:1px solid #ccc}.form-horizontal .control-label{float:none;width:auto;padding-top:0;text-align:left}.form-horizontal .controls{margin-left:0}.form-horizontal .control-list{padding-top:0}.form-horizontal .form-actions{padding-right:10px;padding-left:10px}.media .pull-left,.media .pull-right{display:block;float:none;margin-bottom:10px}.media-object{margin-right:0;margin-left:0}.modal{top:10px;right:10px;left:10px}.modal-header .close{padding:10px;margin:-10px}.carousel-caption{position:static}}@media(max-width:979px){body{padding-top:0}.navbar-fixed-top,.navbar-fixed-bottom{position:static}.navbar-fixed-top{margin-bottom:20px}.navbar-fixed-bottom{margin-top:20px}.navbar-fixed-top .navbar-inner,.navbar-fixed-bottom .navbar-inner{padding:5px}.navbar .container{width:auto;padding:0}.navbar .brand{padding-right:10px;padding-left:10px;margin:0 0 0 -5px}.nav-collapse{clear:both}.nav-collapse .nav{float:none;margin:0 0 10px}.nav-collapse .nav>li{float:none}.nav-collapse .nav>li>a{margin-bottom:2px}.nav-collapse .nav>.divider-vertical{display:none}.nav-collapse .nav .nav-header{color:#777;text-shadow:none}.nav-collapse .nav>li>a,.nav-collapse .dropdown-menu a{padding:9px 15px;font-weight:bold;color:#777;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px}.nav-collapse .btn{padding:4px 10px 4px;font-weight:normal;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px}.nav-collapse .dropdown-menu li+li a{margin-bottom:2px}.nav-collapse .nav>li>a:hover,.nav-collapse .nav>li>a:focus,.nav-collapse .dropdown-menu a:hover,.nav-collapse .dropdown-menu a:focus{background-color:#f2f2f2}.navbar-inverse .nav-collapse .nav>li>a,.navbar-inverse .nav-collapse .dropdown-menu a{color:#999}.navbar-inverse .nav-collapse .nav>li>a:hover,.navbar-inverse .nav-collapse .nav>li>a:focus,.navbar-inverse .nav-collapse .dropdown-menu a:hover,.navbar-inverse .nav-collapse .dropdown-menu a:focus{background-color:#111}.nav-collapse.in .btn-group{padding:0;margin-top:5px}.nav-collapse .dropdown-menu{position:static;top:auto;left:auto;display:none;float:none;max-width:none;padding:0;margin:0 15px;background-color:transparent;border:0;-webkit-border-radius:0;-moz-border-radius:0;border-radius:0;-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none}.nav-collapse .open>.dropdown-menu{display:block}.nav-collapse .dropdown-menu:before,.nav-collapse .dropdown-menu:after{display:none}.nav-collapse .dropdown-menu .divider{display:none}.nav-collapse .nav>li>.dropdown-menu:before,.nav-collapse .nav>li>.dropdown-menu:after{display:none}.nav-collapse .navbar-form,.nav-collapse .navbar-search{float:none;padding:10px 15px;margin:10px 0;border-top:1px solid #f2f2f2;border-bottom:1px solid #f2f2f2;-webkit-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1);-moz-box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1);box-shadow:inset 0 1px 0 rgba(255,255,255,0.1),0 1px 0 rgba(255,255,255,0.1)}.navbar-inverse .nav-collapse .navbar-form,.navbar-inverse .nav-collapse .navbar-search{border-top-color:#111;border-bottom-color:#111}.navbar .nav-collapse .nav.pull-right{float:none;margin-left:0}.nav-collapse,.nav-collapse.collapse{height:0;overflow:hidden}.navbar .btn-navbar{display:block}.navbar-static .navbar-inner{padding-right:10px;padding-left:10px}}@media(min-width:980px){.nav-collapse.collapse{height:auto!important;overflow:visible!important}}


/* feed icon inspired from peculiar by Lucian Marin - lucianmarin.com */
/* https://github.com/lucianmarin/peculiar */
.ico {
  position: relative;
  width: 16px;
  height: 16px;
  display: inline-block;
  float: left;
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
.ico-home-triangle {
  border-color: transparent transparent #000000;
  border-image: none;
  border-style: solid;
  border-width: 8px;
  bottom: 7px;
  height: 0;
  left: 0;
  position: absolute;
  width: 0;
}
.ico-home-square {
  background-color: #000000;
  border-bottom-left-radius: 1px;
  border-bottom-right-radius: 1px;
  bottom: 1px;
  height: 10px;
  left: 3px;
  position: absolute;
  width: 10px;
}
.ico-home-line {
  background-color: #000000;
  border-radius: 1px 1px 1px 1px;
  height: 5px;
  left: 3px;
  position: absolute;
  top: 2px;
  width: 2px;
}
.ico-update-circle {
  border: #fff 2px solid;
  width: 8px;
  height: 8px;
  border-radius: 8px;
  position: absolute;
  bottom: 1px;
  left: 2px;
}
.ico-update-triangle {
  border: 4px solid;
  border-color: transparent #fff #000 transparent;
  height: 0;
  width: 0;
  position: absolute;
  top: 0;
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
.ico-w-triangle-up {
  border: 4px solid #fff;
  border-color: transparent transparent #fff transparent;
  height: 0;
  width: 0;
  position: absolute;
  bottom: 10px;
  left: 4px;
}
.ico-w-triangle-down {
  border: 4px solid #fff;
  border-color: #fff transparent transparent transparent;
  height: 0;
  width: 0;
  position: absolute;
  top: 10px;
  left: 4px;
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
.ico-eye-triangle-left {
  border: 4px solid #fff;
  border-color: transparent #fff transparent transparent;
  height: 0;
  width: 0;
  position: absolute;
  bottom: 4px;
  right: 11px;
}
.ico-eye-triangle-right {
  border: 4px solid #fff;
  border-color: transparent transparent transparent #fff;
  height: 0;
  width: 0;
  position: absolute;
  bottom: 4px;
  left: 11px;
}
.ico-eye-circle-1 {
  border: #fff 2px solid;
  width: 6px;
  height: 6px;
  border-radius: 6px;
  position: absolute;
  top: 3px;
  left: 3px;
}
.ico-eye-circle-2 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  border-radius: 1px;
  position: absolute;
  top: 7px;
  left: 7px;
}
.ico-eye-circle-3 {
  background-color: #fff;
  border-radius: 6px;
  width: 10px;
  height: 10px;
  position: absolute;
  top: 3px;
  left: 3px;
}
.ico-edit-square {
  border: #fff 1px solid;
  width: 4px;
  height: 4px;
  position: absolute;
  top: 5px;
  left: 5px;
}
.ico-edit-circle-1 {
  border: #fff 1px solid;
  width: 2px;
  height: 2px;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  border-bottom-left-radius: 4px;
  position: absolute;
  top: 2px;
  left: 2px;
}
.ico-edit-circle-2 {
  border: #fff 1px solid;
  width: 2px;
  height: 2px;
  border-top-left-radius: 4px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  position: absolute;
  top: 2px;
  right: 2px;
}
.ico-edit-circle-3 {
  border: #fff 1px solid;
  width: 2px;
  height: 2px;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
  border-bottom-right-radius: 4px;
  position: absolute;
  bottom: 2px;
  left: 2px;
}
.ico-edit-circle-4 {
  border: #fff 1px solid;
  width: 2px;
  height: 2px;
  border-bottom-left-radius: 4px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  position: absolute;
  bottom: 2px;
  right: 2px;
}
.ico-help-circle {
  border: #fff 2px solid;
  border-color: #fff #fff #fff transparent;
  width: 4px;
  height: 4px;
  border-radius: 4px;
  position: absolute;
  top: 2px;
  left: 4px;
}
.ico-help-line {
  background-color: #fff;
  width: 2px;
  height: 6px;
  border-radius: 1px;
  position: absolute;
  top: 7px;
  left: 7px;
}
.ico-help-line-1 {
  background-color: #fff;
  width: 2px;
  height: 13px;
  position: absolute;
  bottom: 0;
  left: 4px;
}
.ico-help-line-2 {
  background-color: #fff;
  width: 2px;
  height: 7px;
  position: absolute;
  bottom: 0;
  left: 7px;
}
.ico-help-line-3 {
  background-color: #fff;
  width: 2px;
  height: 6px;
  position: absolute;
  bottom: 0;
  left: 10px;
}
.ico-help-line-4 {
  background-color: #fff;
  width: 2px;
  height: 4px;
  position: absolute;
  bottom: 0;
  left: 13px;
}
.ico-onoff-circle {
  border: #fff 2px solid;
  width: 8px;
  height: 8px;
  border-radius: 8px;
  position: absolute;
  bottom: 1px;
  left: 2px;
}
.ico-onoff-line {
  background-color: #fff;
  border: #000 1px solid;
  width: 2px;
  height: 6px;
  border-radius: 1px;
  position: absolute;
  top: 1px;
  left: 6px;
}
.ico-expanded-line-1 {
  background-color: #fff;
  width: 10px;
  height: 4px;
  position: absolute;
  top: 2px;
  left: 3px;
}
.ico-expanded-line-2 {
  background-color: #fff;
  width: 10px;
  height: 4px;
  position: absolute;
  top: 7px;
  left: 3px;
}
.ico-expanded-line-3 {
  background-color: #fff;
  width: 10px;
  height: 2px;
  position: absolute;
  top: 12px;
  left: 3px;
}
.ico-list-line-1 {
  background-color: #fff;
  width: 10px;
  height: 1px;
  position: absolute;
  top: 3px;
  left: 3px;
}
.ico-list-line-2 {
  background-color: #fff;
  width: 10px;
  height: 1px;
  position: absolute;
  top: 5px;
  left: 3px;
}
.ico-list-line-3 {
  background-color: #fff;
  width: 10px;
  height: 1px;
  position: absolute;
  top: 7px;
  left: 3px;
}
.ico-list-line-4 {
  background-color: #fff;
  width: 10px;
  height: 1px;
  position: absolute;
  top: 9px;
  left: 3px;
}
.ico-list-line-5 {
  background-color: #fff;
  width: 10px;
  height: 1px;
  position: absolute;
  top: 11px;
  left: 3px;
}
.ico-list-feeds-line-1 {
  background-color: #fff;
  width: 4px;
  height: 1px;
  position: absolute;
  top: 4px;
  left: 3px;
}
.ico-list-feeds-line-2 {
  background-color: #fff;
  width: 4px;
  height: 1px;
  position: absolute;
  top: 7px;
  left: 3px;
}
.ico-list-feeds-line-3 {
  background-color: #fff;
  width: 4px;
  height: 1px;
  position: absolute;
  top: 10px;
  left: 3px;
}
.ico-list-feeds-line-4 {
  background-color: #fff;
  width: 5px;
  height: 7px;
  position: absolute;
  top: 4px;
  left: 8px;
}
.ico-list-feeds-line-5 {
  background-color: #fff;
  width: 10px;
  height: 7px;
  position: absolute;
  top: 4px;
  left: 3px;
}
.ico-config-circle-1 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 7px;
  left: 3px;
}
.ico-config-circle-2 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 7px;
  left: 7px;
}
.ico-config-circle-3 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 7px;
  left: 11px;
}
.ico-item-circle-1 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 4px;
  left: 3px;
}
.ico-item-circle-2 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 7px;
  left: 3px;
}
.ico-item-circle-3 {
  background-color: #fff;
  width: 2px;
  height: 2px;
  position: absolute;
  top: 10px;
  left: 3px;
}
.ico-item-line-1 {
  background-color: #fff;
  width: 7px;
  height: 2px;
  position: absolute;
  top: 4px;
  left: 6px;
}
.ico-item-line-2 {
  background-color: #fff;
  width: 7px;
  height: 2px;
  position: absolute;
  top: 7px;
  left: 6px;
}
.ico-item-line-3 {
  background-color: #fff;
  width: 7px;
  height: 2px;
  position: absolute;
  top: 10px;
  left: 6px;
}
.menu-ico {
  display: none;
}

.ico-toggle-item {
  float: right;
}
html, body, .full-height {
  height: 100%;
  overflow: auto;
}

li.feed {
  border-bottom: 1px dotted #999;
  font-weight: normal;
}

li.feed.has-unread {
  font-weight: bold;
}

li.folder {
  font-weight: bold;
}

li.item-list {
  border-bottom: 1px dotted #999;
}

.item-favicon {
  float: left;
}

h5.folder {
  background-color: #ddd;
  border-radius: 4px;
  padding: 2px;
  margin: 2px 0;
}

.mark-as {
  float: right;
}

.content {
  clear: both;
}

.current, .current-feed, .current-folder {
  border-color: red !important;
}

.current .item-title {
  font-weight: bold;
}

dl {
  margin-bottom: 0px !important;
}

.item-info {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
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

.item-title {
  margin-bottom: 10px;
}

.item-info-end {
  float: right;
}

.item-description {
  text-decoration: none;
}

.folder-toggle:focus, .folder-toggle:hover, .folder-toggle:active, .item-toggle:focus, .item-toggle:hover, .item-toggle:active {
  text-decoration: none;
}

.folder-toggle-open, .item-toggle-open, .item-close {
  display: none;
}

.folder-toggle-close, .item-toggle-close, .item-open {
  display: block;
}

.label-expanded {
  padding: 6px;
}

/* Large desktop */
@media (min-width: 1200px) {

}

/* Portrait tablet to landscape and desktop */
@media (min-width: 768px) and (max-width: 979px) {

}

/* Landscape phone to portrait tablet */
@media (max-width: 767px) {
    html, body, .full-height {
        height: auto;
    }
}

/* Landscape phones and down */
@media (max-width: 480px) {
  ul.inline {
    width: 100%;
  }

  ul.inline > li {
    width: 90%;
    padding-bottom: 10px;
    margin: auto;
  }

  .btn-group {
    width: 100%;
  }

  .btn-group > a {
    width: 100%;
    margin-left: -1px;
    padding-left: 0;
    padding-right: 0;
  }

  .btn-group > .btn {
    width: 100%;
    float: left;
    border-radius: 4px;
  }

  .item-title {
    no-wrap: normal;
  }

  .paging-by-page {
    width: 100%;
  }

  .paging-by-page > a {
    width: 100%;
    margin-left: -1px;
    padding-left: 0;
    padding-right: 0;
    border-radius: 4px;
  }

  .paging-by-page > input {
    width: 100%;
    float: left;
    margin-left: -1px;
    padding-left: 0;
    padding-right: 0;
    border-radius: 4px;
  }

  .paging-by-page > .btn {
    width: 100%;
    float: left;
    border-radius: 4px !important;
  }
}
</style>
<?php } ?>
<?php if (is_file('inc/user.css')) { ?>
<link type="text/css" rel="stylesheet" href="inc/user.css?version=<?php echo $version;?>" />
<?php } ?>
<meta name="viewport" content="width=device-width">
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
                <legend>KrISS feed installation</legend>
                <div class="control-group">
                  <label class="control-label" for="setlogin">Login</label>
                  <div class="controls">
                    <input type="text" id="setlogin" name="setlogin" placeholder="Login">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="setlogin">Password</label>
                  <div class="controls">
                    <input type="password" id="setpassword" name="setpassword" placeholder="Password">
                  </div>
                </div>
                <div class="control-group">
                  <div class="controls">
                    <button type="submit" class="btn">Submit</button>
                  </div>
                </div>
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
              </fieldset>
            </form>
            <?php FeedPage::statusTpl(); ?>
          </div>
        </div>
        <script>
          document.installform.setlogin.focus();
        </script>
      </div>
    </div>
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
                <legend>Welcome to KrISS feed</legend>
                <div class="control-group">
                  <label class="control-label" for="login">Login</label>
                  <div class="controls">
                    <input type="text" id="login" name="login" placeholder="Login" tabindex="1">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="password">Password</label>
                  <div class="controls">
                    <input type="password" id="password" name="password" placeholder="Password" tabindex="2">
                  </div>
                </div>
                <div class="control-group">
                  <div class="controls">
                    <label><input type="checkbox" name="longlastingsession" tabindex="3">&nbsp;Stay signed in (Do not check on public computers)</label>
                  </div>
                </div>
                
                <div class="control-group">
                  <div class="controls">
                    <button type="submit" class="btn" tabindex="4">Sign in</button>
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
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend>Change your password</legend>

                  <div class="control-group">
                    <label class="control-label" for="oldpassword">Old password</label>
                    <div class="controls">
                      <input type="password" id="oldpassword" name="oldpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="newpassword">New password</label>
                    <div class="controls">
                      <input type="password" id="newpassword" name="newpassword">
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save new password" />
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
            <a href="<?php echo $query.'update='.$currentHash; ?>" class="admin" title="Update <?php echo $currentHashType; ?> manually">
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
            <a href="<?php echo $query.'read='.$currentHash; ?>" class="admin" title="Mark <?php echo $currentHashType; ?> as read">
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
            <a href="<?php echo $query.'unread='.$currentHash; ?>" class="admin" title="Mark <?php echo $currentHashType; ?> as unread">
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
            <a href="<?php echo $query.'edit='.$currentHash; ?>" class="admin" title="Edit <?php echo $currentHashType; ?>">
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
            <a href="<?php echo $query.'add'; ?>" class="admin" title="Add a new feed">
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
          <?php default: ?>
          <?php break; ?>
          <?php } ?>
          <?php } ?>
          <?php if ($kf->kfc->isLogged()) { ?>
          <li>
            <a href="?config" class="admin" title="Configuration">
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
            <a href="?logout" class="admin" title="Logout">
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
          <li><a href="?password" class="admin" title="Change your password">Change password</a></li>
          <li><a href="?import" class="admin" title="Import OPML file">Import</a></li>
          <li><a href="?export" class="admin" title="Export OPML file">Export</a></li>
          <li><a href="?logout" class="admin" title="Logout">Logout</a></li>
          <?php
             break;
             default:
             ?>
          <?php if ($kf->kfc->isLogged()) { ?>
          <li><a href="?config" class="admin" title="Configuration">Configuration</a></li>
          <?php } ?>
          <?php if (Session::isLogged()) { ?>
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
<?php
    }

    public static function statusTpl()
    {
        extract(FeedPage::$var);
?>
<div id="status" class="text-center">
  <a href="http://github.com/tontof/kriss_feed">KrISS feed <?php echo $version; ?></a>
  <span class="hidden-phone"> - A simple and smart (or stupid) feed reader</span>. By <a href="http://tontof.net">Tontof</a>
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
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend>KrISS feed Reader information</legend>

                  <div class="control-group">
                    <label class="control-label" for="title">Feed reader title</label>
                    <div class="controls">
                      <input type="text" id="title" name="title" value="<?php echo $kfctitle; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Public/protected/private reader</label>
                    <div class="controls">
                      <label for="publicReader">
                        <input type="radio" id="publicReader" name="visibility" value="public" <?php echo ($kfcvisibility==='public'? 'checked="checked"' : ''); ?>/>
                        Public kriss feed
                      </label>
                       <span class="help-block">
                         No restriction. Anyone can modify configuration, mark as read items, update feeds...
                       </span>
                      <label for="protectedReader">
                        <input type="radio" id="protectedReader" name="visibility" value="protected" <?php echo ($kfcvisibility==='protected'? 'checked="checked"' : ''); ?>/>
                        Protected kriss feed
                      </label>
                      <span class="help-block">
                        Anyone can access feeds and items but only you can modify configuration, mark as read items, update feeds...
                      </span>
                      <label for="privateReader">
                        <input type="radio" id="privateReader" name="visibility" value="private" <?php echo ($kfcvisibility==='private'? 'checked="checked"' : ''); ?>/>
                        Private kriss feed
                      </label>
                      <span class="help-block">
                        Only you can access feeds and items and only you can modify configuration, mark as read items, update feeds...
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="shaarli">Shaarli url</label>
                    <div class="controls">
                      <input type="text" id="shaarli" name="shaarli" value="<?php echo $kfcshaarli; ?>">
                      <span class="help-block">options :<br>
                        - ${url}: link of item<br>
                        - ${title}: title of item<br>
                        - ${via}: if domain of &lt;link&gt; and &lt;guid&gt; are different ${via} is equals to: <code>via &lt;guid&gt;</code><br>
                        - ${sel}: <strong>Only available</strong> with javascript: <code>« selected text »</code><br>
                        - example with shaarli : <code>http://your-shaarli/?post=${url}&title=${title}&description=${sel}%0A%0A${via}&source=bookmarklet</code>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="redirector">Feed reader redirector (only for links, media are not considered, <strong>item content is anonymize only with javascript</strong>)</label>
                    <div class="controls">
                      <input type="text" id="redirector" name="redirector" value="<?php echo $kfcredirector; ?>">
                      <span class="help-block"><strong>http://anonym.to/?</strong> will mask the HTTP_REFERER, you can also use <strong>noreferrer</strong> to use HTML5 property</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="disablesessionprotection">Session protection</label>
                    <div class="controls">
                      <label><input type="checkbox" id="disablesessionprotection" name="disableSessionProtection"<?php echo ($kfcdisablesessionprotection ? ' checked="checked"' : ''); ?>>Disable session cookie hijacking protection</label>
                      <span class="help-block">Check this if you get disconnected often or if your IP address changes often.</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed reader preferences</legend>

                  <div class="control-group">
                    <label class="control-label" for="maxItems">Maximum number of items by feed</label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxItems" name="maxItems" value="<?php echo $kfcmaxitems; ?>">
                      <span class="help-block">Need update to be taken into consideration</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="maxUpdate">Maximum delay between feed update (in minutes)</label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxUpdate" name="maxUpdate" value="<?php echo $kfcmaxupdate; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto read next item option</label>
                    <div class="controls">
                      <label for="donotautoreaditem">
                        <input type="radio" id="donotautoreaditem" name="autoreadItem" value="0" <?php echo (!$kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        Do not mark as read when next item
                      </label>
                      <label for="autoread">
                        <input type="radio" id="autoread" name="autoreadItem" value="1" <?php echo ($kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        Auto mark current as read when next item
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto read next page option</label>
                    <div class="controls">
                      <label for="donotautoreadpage">
                        <input type="radio" id="donotautoreadpage" name="autoreadPage" value="0" <?php echo (!$kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        Do not mark as read when next page
                      </label>
                      <label for="autoreadpage">
                        <input type="radio" id="autoreadpage" name="autoreadPage" value="1" <?php echo ($kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        Auto mark current as read when next page
                      </label>
                      <span class="help-block"><strong>Not implemented yet</strong></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto hide option</label>
                    <div class="controls">
                      <label for="donotautohide">
                        <input type="radio" id="donotautohide" name="autohide" value="0" <?php echo (!$kfcautohide ? 'checked="checked"' : ''); ?>/>
                        Always show feed in feeds list
                      </label>
                      <label for="autohide">
                        <input type="radio" id="autohide" name="autohide" value="1" <?php echo ($kfcautohide ? 'checked="checked"' : ''); ?>/>
                        Automatically hide feed when 0 unread item
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto focus option</label>
                    <div class="controls">
                      <label for="donotautofocus">
                        <input type="radio" id="donotautofocus" name="autofocus" value="0" <?php echo (!$kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        Do not automatically jump to current item when it changes
                      </label>
                      <label for="autofocus">
                        <input type="radio" id="autofocus" name="autofocus" value="1" <?php echo ($kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        Automatically jump to the current item position
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Add favicon option</label>
                    <div class="controls">
                      <label for="donotaddfavicon">
                        <input type="radio" id="donotaddfavicon" name="addFavicon" value="0" <?php echo (!$kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        Do not add favicon next to feed on list of feeds/items
                      </label>
                      <label for="addfavicon">
                        <input type="radio" id="addfavicon" name="addFavicon" value="1" <?php echo ($kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        Add favicon next to feed on list of feeds/items<br><strong>Warning: It depends on http://getfavicon.appspot.com/ <?php if (in_array('curl', get_loaded_extensions())) { echo 'but it will cache favicon on your server'; } ?></strong>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto update with javascript</label>
                    <div class="controls">
                      <label for="donotautoupdate">
                        <input type="radio" id="donotautoupdate" name="autoUpdate" value="0" <?php echo (!$kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        Do not auto update with javascript
                      </label>
                      <label for="autoupdate">
                        <input type="radio" id="autoupdate" name="autoUpdate" value="1" <?php echo ($kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        Auto update with javascript
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed menu preferences</legend>
                  You can order or remove elements in the menu. Set a position or leave empty if you don't want the element to appear in the menu.
                  <div class="control-group">
                    <label class="control-label" for="menuView">View</label>
                    <div class="controls">
                      <input type="text" id="menuView" name="menuView" value="<?php echo empty($kfcmenu['menuView'])?'0':$kfcmenu['menuView']; ?>">
                      <span class="help-block">If you want to switch between list and expanded view</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuListFeeds">List of feeds</label>
                    <div class="controls">
                      <input type="text" id="menuListFeeds" name="menuListFeeds" value="<?php echo empty($kfcmenu['menuListFeeds'])?'0':$kfcmenu['menuListFeeds']; ?>">
                      <span class="help-block">If you want to show or hide list of feeds</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuFilter">Filter</label>
                    <div class="controls">
                      <input type="text" id="menuFilter" name="menuFilter" value="<?php echo empty($kfcmenu['menuFilter'])?'0':$kfcmenu['menuFilter']; ?>">
                      <span class="help-block">If you want to filter all or unread items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuOrder">Order</label>
                    <div class="controls">
                      <input type="text" id="menuOrder" name="menuOrder" value="<?php echo empty($kfcmenu['menuOrder'])?'0':$kfcmenu['menuOrder']; ?>">
                      <span class="help-block">If you want to order by newer or older items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUpdate">Update</label>
                    <div class="controls">
                      <input type="text" id="menuUpdate" name="menuUpdate" value="<?php echo empty($kfcmenu['menuUpdate'])?'0':$kfcmenu['menuUpdate']; ?>">
                      <span class="help-block">If you want to update all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuRead">Read</label>
                    <div class="controls">
                      <input type="text" id="menuRead" name="menuRead" value="<?php echo empty($kfcmenu['menuRead'])?'0':$kfcmenu['menuRead']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as read</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUnread">Unread</label>
                    <div class="controls">
                      <input type="text" id="menuUnread" name="menuUnread" value="<?php echo empty($kfcmenu['menuUnread'])?'0':$kfcmenu['menuUnread']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as unread</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuEdit">Edit</label>
                    <div class="controls">
                      <input type="text" id="menuEdit" name="menuEdit" value="<?php echo empty($kfcmenu['menuEdit'])?'0':$kfcmenu['menuEdit']; ?>">
                      <span class="help-block">If you want to edit all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuAdd">Add</label>
                    <div class="controls">
                      <input type="text" id="menuAdd" name="menuAdd" value="<?php echo empty($kfcmenu['menuAdd'])?'0':$kfcmenu['menuAdd']; ?>">
                      <span class="help-block">If you want to add a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuHelp">Help</label>
                    <div class="controls">
                      <input type="text" id="menuHelp" name="menuHelp" value="<?php echo empty($kfcmenu['menuHelp'])?'0':$kfcmenu['menuHelp']; ?>">
                      <span class="help-block">If you want to add a link to the help</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed paging menu preferences</legend>
                  <div class="control-group">
                    <label class="control-label" for="pagingItem">Item</label>
                    <div class="controls">
                      <input type="text" id="pagingItem" name="pagingItem" value="<?php echo empty($kfcpaging['pagingItem'])?'0':$kfcpaging['pagingItem']; ?>">
                      <span class="help-block">If you want to go previous and next item </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingPage">Page</label>
                    <div class="controls">
                      <input type="text" id="pagingPage" name="pagingPage" value="<?php echo empty($kfcpaging['pagingPage'])?'0':$kfcpaging['pagingPage']; ?>">
                      <span class="help-block">If you want to go previous and next page </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingByPage">Items by page</label>
                    <div class="controls">
                      <input type="text" id="pagingByPage" name="pagingByPage" value="<?php echo empty($kfcpaging['pagingByPage'])?'0':$kfcpaging['pagingByPage']; ?>">
                      <span class="help-block">If you want to modify number of items by page</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingMarkAs">Mark as read</label>
                    <div class="controls">
                      <input type="text" id="pagingMarkAs" name="pagingMarkAs" value="<?php echo empty($kfcpaging['pagingMarkAs'])?'0':$kfcpaging['pagingMarkAs']; ?>">
                      <span class="help-block">If you add a mark as read button into paging</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>Cron configuration</legend>
                  <code><?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?></code>
                  You can use <code>&force</code> to force update.<br>
                  To update every 15 minutes
                  <code>*/15 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  To update every hour
                  <code>0 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  If you can not use wget, you may try php command line :
                  <code>0 * * * * php -f <?php echo $_SERVER["SCRIPT_FILENAME"].' update '.$kfccron; ?> > /tmp/kf.cron</code>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
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
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <h2>Keyboard shortcut</h2>
              <h3>Items navigation</h3>
              <dl class="dl-horizontal">
                <dt>'space' or 't'</dt>
                <dd>When viewing items as list, let you open or close current item (<strong>t</strong>oggle current item)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'m'</dt>
                <dd><strong>M</strong>ark current item as read if unread or unread if read</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'm'</dt>
                <dd><strong>M</strong>ark current item as read if unread or unread if read and open current (useful in list view and unread filter)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'n' or right arrow</dt>
                <dd>Go to <strong>n</strong>ext item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'p' or left arrow</dt>
                <dd>Go to <strong>p</strong>revious item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'n'</dt>
                <dd>Go to <strong>n</strong>ext page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'p'</dt>
                <dd>Go to <strong>p</strong>revious page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'j'</dt>
                <dd>Go to <strong>n</strong>ext item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'k'</dt>
                <dd>Go to <strong>p</strong>revious item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'o'</dt>
                <dd><strong>O</strong>pen current item in new tab</dd>
                <dt>'shift' + 'o'</dt>
                <dd><strong>O</strong>pen current item in current window</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'s'</dt>
                <dd><strong>S</strong>hare current item (go in <a href="?config" title="configuration">configuration</a> to set up your link)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'a'</dt>
                <dd>Mark <strong>a</strong>ll items, <strong>a</strong>ll items from current feed or <strong>a</strong>ll items from current folder as read</dd>
              </dl>
              <h3>Menu navigation</h3>
              <dl class="dl-horizontal">
                <dt>'h'</dt>
                <dd>Go to <strong>H</strong>ome page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'v'</dt>
                <dd>Change <strong>v</strong>iew as list or expanded</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'f'</dt>
                <dd>Show or hide list of <strong>f</strong>eeds/<strong>f</strong>olders</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'e'</dt>
                <dd><strong>E</strong>dit current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'u'</dt>
                <dd><strong>U</strong>pdate current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'r'</dt>
                <dd><strong>R</strong>eload the page as the 'F5' key in most of browsers</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'?' or 'F1'</dt>
                <dd>Go to Help page (actually it's shortcut to go to this page)</dd>
              </dl>
            </div>

            <div id="section">
              <h2>Check configuration</h2>
              <dl class="dl-horizontal">
                <dt>open_ssl</dt>
                <dd>
                  <?php if (extension_loaded('openssl')) { ?>
                  <span class="text-success">You should be able to load https:// rss links.</span>
                  <?php } else { ?>
                  <span class="text-error">You may have problems using https:// rss links.</span>
                  <?php } ?>
                </dd>
              </dl>
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
              <legend>Add a new feed</legend>
              <div class="control-group">
                <label class="control-label" > Feed url</label>
                <div class="controls">
                  <input type="text" id="newfeed" name="newfeed" value="<?php echo $newfeed; ?>">                  
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Add selected folders to feed</legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="folders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>">edit</a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label" >Add to a new folder</label>
                <div class="controls">
                  <input type="text" name="newfolder" value="">
                </div>
              </div>
              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="add" value="Add new feed"/>
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Use bookmarklet to add a new feed</legend>
              <div id="add-feed-bookmarklet" class="text-center">
                <a onclick="alert('Drag this link to your bookmarks toolbar, or right-click it and choose Bookmark This Link...');return false;" href="javascript:(function(){var%20url%20=%20location.href;window.open('<?php echo $kfurl;?>?add&amp;newfeed='+encodeURIComponent(url),'_blank','menubar=no,height=390,width=600,toolbar=no,scrollbars=yes,status=no,dialog=1');})();"><b>Add KF</b></a>
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
              <legend>Add selected folders to selected feeds</legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="add-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="add-folder-<?php echo $hash; ?>" name="addfolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>">edit</a>)
                  </label>
                  <?php } ?>
                </div>
                <div class="controls">
                  <input type="text" name="addnewfolder" value="" placeholder="New folder">
                </div>
              </div>
            </fieldset>

            <fieldset>
              <legend>Remove selected folders to selected feeds</legend>
              <div class="control-group">
                <div class="controls">
                  <?php foreach ($folders as $hash => $folder) { ?>
                  <label for="remove-folder-<?php echo $hash; ?>">
                    <input type="checkbox" id="remove-folder-<?php echo $hash; ?>" name="removefolders[]" value="<?php echo $hash; ?>"> <?php echo htmlspecialchars($folder['title']); ?> (<a href="?edit=<?php echo $hash; ?>">edit</a>)
                  </label>
                  <?php } ?>
                </div>
              </div>
            </fieldset>

            <input class="btn" type="submit" name="cancel" value="Cancel"/>
            <input class="btn" type="submit" name="delete" value="Delete selected" onclick="return confirm('Do really want to delete all selected ?');"/>
            <input class="btn" type="submit" name="save" value="Save selected" />

            <fieldset>
              <legend>List of feeds</legend>

              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = true; }" value="Select all">
              <input class="btn" type="button" onclick="var feeds = document.getElementsByName('feeds[]'); for (var i = 0; i < feeds.length; i++) { feeds[i].checked = false; }" value="Unselect all">

              <ul class="unstyled">
                <?php foreach ($listFeeds as $feedHash => $feed) { ?>
                <li>
                  <label for="feed-<?php echo $feedHash; ?>">
                    <input type="checkbox" id="feed-<?php echo $feedHash; ?>" name="feeds[]" value="<?php echo $feedHash; ?>">
                    <?php echo htmlspecialchars($feed['title']); ?> (<a href="?edit=<?php echo $feedHash; ?>">edit</a>)
                  </label>
                </li>
                <?php } ?>
              </ul>
            </fieldset>

            <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
            <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
            <input class="btn" type="submit" name="cancel" value="Cancel"/>
            <input class="btn" type="submit" name="delete" value="Delete selected" onclick="return confirm('Do really want to delete all selected ?');"/>
            <input class="btn" type="submit" name="save" value="Save selected" />
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
                <label class="control-label" for="foldertitle">Folder title</label>
                <div class="controls">
                  <input type="text" id="foldertitle" name="foldertitle" value="<?php echo $foldertitle; ?>">
                  <span class="help-block">Leave empty to delete</span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="cancel" value="Cancel"/>
                  <input class="btn" type="submit" name="save" value="Save" />
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
              <legend>Feed main information</legend>
              <div class="control-group">
                <label class="control-label" for="title">Feed title</label>
                <div class="controls">
                  <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($feed['title']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Feed XML url</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="xmlUrl" value="<?php echo htmlspecialchars($feed['xmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Feed main url</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="htmlUrl" value="<?php echo htmlspecialchars($feed['htmlUrl']); ?>">
                </div>
              </div>
              <div class="control-group">
                <label class="control-label" for="description">Feed description</label>
                <div class="controls">
                  <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($feed['description']); ?>">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Feed folders</legend>
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
                <label class="control-label" for="newfolder">New folder</label>
                <div class="controls">
                  <input type="text" name="newfolder" value="" placeholder="New folder">
                </div>
              </div>
            </fieldset>
            <fieldset>
              <legend>Feed preferences</legend>
              <div class="control-group">
                <label class="control-label" for="timeUpdate">Time update </label>
                <div class="controls">
                  <input type="text" id="timeUpdate" name="timeUpdate" value="<?php echo $feed['timeUpdate']; ?>">
                  <span class="help-block">'auto', 'max' or a number of minutes less than 'max' define in <a href="?config">config</a></span>
                </div>
              </div>
              <div class="control-group">
                <label class="control-label">Last update (<em>read only</em>)</label>
                <div class="controls">
                  <input type="text" readonly="readonly" name="lastUpdate" value="<?php echo $lastUpdate; ?>">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="save" value="Save" />
                  <input class="btn" type="submit" name="cancel" value="Cancel"/>
                  <input class="btn" type="submit" name="delete" value="Delete"/>
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
                <a class="btn" href="?">Go home</a>
                <?php if (!empty($referer)) { ?>
                <a class="btn" href="<?php echo htmlspecialchars($referer); ?>">Go back</a>
                <?php } ?>
                <a class="btn" href="<?php echo $query."update=".$currentHash."&force"; ?>">Force update</a>
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
          <form class="form-horizontal" method="post" action="?import" enctype="multipart/form-data">
            <fieldset>
              <legend>Import Opml file</legend>
              Import an opml file as exported by Google Reader, Tiny Tiny RSS, RSS lounge...
              
              <div class="control-group">
                <label class="control-label" for="filetoupload">File (Size max: <?php echo MyTool::humanBytes(MyTool::getMaxFileSize()); ?>)</label>
                <div class="controls">
                  <input class="btn" type="file" id="filetoupload" name="filetoupload">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <label for="overwrite">
                    <input type="checkbox" name="overwrite" id="overwrite">
                    Overwrite existing feeds
                  </label>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <input class="btn" type="submit" name="import" value="Import">
                  <input class="btn" type="submit" name="cancel" value="Cancel">
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
        
        <li id="<?php echo 'feed-'.$feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'; ?><?php if ($currentHash == $feedHash) echo ' current-feed'; ?><?php if ($autohide and $feed['nbUnread']== 0) echo ' autohide-feed'; ?>">
          <?php if ($addFavicon) { ?>
          <span class="feed-favicon">
            <img src="<?php echo $kf->getFaviconFeed($feedHash); ?>" height="16px" width="16px" title="favicon" alt="favicon"/>
          </span>
          <?php } ?>
<a class="mark-as" href="<?php echo $query.'read='.$feedHash; ?>"><span class="label"><?php echo $feed['nbUnread']; ?></span></a><a class="feed<?php echo (isset($feed['error'])?' text-error':''); ?>" href="<?php echo '?currentHash='.$feedHash; ?>" title="<?php echo $atitle; ?>"><?php echo htmlspecialchars($feed['title']); ?></a>
          
        </li>

        <?php
           }
           foreach ($feedsView['folders'] as $hashFolder => $folder) {
        $isOpen = $folder['isOpen'];
        ?>
        
        <li id="folder-<?php echo $hashFolder; ?>" class="folder<?php if ($currentHash == $feedHash) echo ' current-folder'; ?><?php if ($autohide and $folder['nbUnread']== 0) { echo ' autohide-folder';} ?>">
          <h5>
            <a class="mark-as" href="<?php echo $query.'read='.$hashFolder; ?>"><span class="label"><?php echo $folder['nbUnread']; ?></span></a>
            <a class="folder-toggle" href="<?php echo $query.'toggleFolder='.$hashFolder; ?>" data-toggle="collapse" data-target="#folder-ul-<?php echo $hashFolder; ?>">
              <span class="ico">
                <span class="ico-b-disc"></span>
                <span class="ico-w-line-h"></span>
                <span class="ico-w-line-v<?php echo ($isOpen?' folder-toggle-open':' folder-toggle-close'); ?>"></span>
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

            <li id="folder-<?php echo $hashFolder; ?>-feed-<?php echo $feedHash; ?>" class="feed<?php if ($feed['nbUnread']!== 0) echo ' has-unread'; ?><?php if ($currentHash == $feedHash) echo ' current-feed'; ?><?php if ($autohide and $feed['nbUnread']== 0) { echo ' autohide-feed';} ?>">
              
              <?php if ($addFavicon) { ?>
              <span class="feed-favicon">
                <img src="<?php echo $kf->getFaviconFeed($feedHash); ?>" height="16px" width="16px" title="favicon" alt="favicon"/>
              </span>
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
<?php
    }

    public static function listItemsTpl()
    {
        extract(FeedPage::$var);
?>
<ul id="list-items" class="unstyled">
  <?php
     foreach (array_keys($items) as $itemHash){
     $item = $kf->getItem($itemHash);
  ?>
  <li id="item-<?php echo $itemHash; ?>" class="<?php echo ($view==='expanded'?'item-expanded':'item-list'); ?><?php echo ($item['read']==1?' read':''); ?><?php echo ($itemHash==$currentItemHash?' current':''); ?>">

    <?php if ($view==='list') { ?>
    <a id="item-toggle-<?php echo $itemHash; ?>" class="item-toggle item-toggle-plus" href="<?php echo $query.'current='.$itemHash.((!isset($_GET['open']) or $currentItemHash != $itemHash)?'&amp;open':''); ?>" data-toggle="collapse" data-target="#item-div-<?php echo $itemHash; ?>">
      <?php echo $item['time']['list']; ?>
      <span class="ico ico-toggle-item">
        <span class="ico-b-disc"></span>
        <span class="ico-w-line-h"></span>
        <span class="ico-w-line-v<?php echo ((!isset($_GET['open']) or $currentItemHash != $itemHash)?' item-toggle-close':' item-toggle-open'); ?>"></span>
      </span>
    </a>
    <dl class="dl-horizontal item">
      <dt class="item-feed">
        <?php if ($addFavicon) { ?>
        <span class="item-favicon">
          <img src="<?php echo $item['favicon']; ?>" height="16px" width="16px" title="favicon" alt="favicon"/>
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
          <?php if ($item['read'] == 1) { ?>
          <a class="item-mark-as" href="<?php echo $query.'unread='.$itemHash; ?>"><span class="label">unread</span></a>
          <?php } else { ?>
          <a class="item-mark-as" href="<?php echo $query.'read='.$itemHash; ?>"><span class="label">read</span></a>
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
        <a target="_blank"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> class="item-link" href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['link']; ?>"><?php echo $item['title']; ?></a>
      </div>
      <div class="clear"></div>
      <div class="item-info-end">
        from <a class="item-via"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['via']; ?>"><?php echo $item['author']; ?></a>
        <?php echo $item['time']['expanded']; ?>
        <a class="item-xml"<?php echo ($redirector==='noreferrer'?' rel="noreferrer"':''); ?> href="<?php echo ($redirector!='noreferrer'?$redirector:'').$item['xmlUrl']; ?>">
          <span class="ico">
            <span class="ico-feed-dot"></span>
            <span class="ico-feed-circle-1"></span>
            <span class="ico-feed-circle-2"></span>
          </span>
        </a>
      </div>
      <div class="clear"></div>
      <div class="item-content"><article>
        <?php echo $item['content']; ?>
      </article></div>
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
<?php
    }

    public static function pagingTpl()
    {
        extract(FeedPage::$var);
?>
<ul class="inline">
  <?php foreach(array_keys($paging) as $pagingOpt) { ?>
  <?php switch($pagingOpt) {
        case 'pagingItem': ?>
  <li>
    <div class="btn-group">
      <a class="btn btn-info previous-item" href="<?php echo $query.'previous='.$currentItemHash; ?>">Previous item</a>
      <a class="btn btn-info next-item" href="<?php echo $query.'next='.$currentItemHash; ?>">Next item</a>
    </div>
  </li>
  <?php break; ?>
  <?php case 'pagingMarkAs': ?>
  <li>
    <div class="btn-group">
      <a class="btn btn-info" href="<?php echo $query.'read='.$currentHash; ?>">Mark as read</a>
    </div>
  </li>
  <?php break; ?>
  <?php case 'pagingPage': ?>
  <li>
    <div class="btn-group">
      <a class="btn btn-info previous-page<?php echo ($currentPage === 1)?' disabled':''; ?>" href="<?php echo $query.'previousPage='.$currentPage; ?>">Previous page</a>
      <button class="btn disabled current-max-page"><?php echo $currentPage.' / '.$maxPage; ?></button>
      <a class="btn btn-info next-page<?php echo ($currentPage === $maxPage)?' disabled':''; ?>" href="<?php echo $query.'nextPage='.$currentPage; ?>">Next page</a>
    </div>
  </li>
  <?php break; ?>
  <?php case 'pagingByPage': ?>
  <li>
    <form class="form-inline" action="<?php echo $kfurl; ?>" method="GET">
      <div class="input-prepend input-append paging-by-page">
        <a class="btn btn-info" href="<?php echo $query.'byPage=1'; ?>">1</a>
        <a class="btn btn-info" href="<?php echo $query.'byPage=10'; ?>">10</a>
        <a class="btn btn-info" href="<?php echo $query.'byPage=50'; ?>">50</a>
        <input class="input-by-page input-mini" type="text" name="byPage">
        <input type="hidden" name="currentHash" value="<?php echo $currentHash; ?>">
        <button type="submit" class="btn">items per page</button>
      </div>
    </form>
  </li>
  <?php break; ?>
  <?php default: ?>
  <?php break; ?>
  <?php } ?>
  <?php } ?>
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
    <div id="index" class="container-fluid full-height" data-view="<?php echo $view; ?>" data-list-feeds="<?php echo $listFeeds; ?>" data-filter="<?php echo $filter; ?>" data-order="<?php echo $order; ?>" data-by-page="<?php echo $byPage; ?>" data-autoread-item="<?php echo $autoreadItem; ?>" data-autoread-page="<?php echo $autoreadPage; ?>" data-autohide="<?php echo $autohide; ?>" data-current-hash="<?php echo $currentHash; ?>" data-current-page="<?php echo $currentPage; ?>" data-nb-items="<?php echo $nbItems; ?>" data-shaarli="<?php echo $shaarli; ?>" data-redirector="<?php echo $redirector; ?>" data-autoupdate="<?php echo $autoupdate; ?>" data-autofocus="<?php echo $autofocus; ?>" data-add-favicon="<?php echo $addFavicon; ?>">
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
    <script type="text/javascript">
(function () {

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
      status = '',
      listUpdateFeeds = [],
      listItemsHash = [],
      currentItemHash = '',
      currentUnread = 0,
      title = '',
      cache = {};

  if(!String.prototype.trim) {
    String.prototype.trim = function () {
      return this.replace(/^\s+|\s+$/g,'');
    };
  }

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
        catch (e) {}
      }
    }

    return httpRequest;
  }

  // Constructor for generic HTTP client
  function HTTPClient() {};
  HTTPClient.prototype = {
    url: null,
    xhr: null,
    callinprogress: false,
    userhandler: null,
    init: function(url) {
      this.url = url;
      this.xhr = new getXHR();
    },
    asyncGET: function (handler) {
      // Prevent multiple calls
      if (this.callinprogress) {
        throw "Call in progress";
      };
      this.callinprogress = true;
      this.userhandler = handler;
      // Open an async request - third argument makes it async
      this.xhr.open('GET', this.url, true);
      var self = this;
      // Assign a closure to the onreadystatechange callback
      this.xhr.onreadystatechange = function() {
        self.stateChangeCallback(self);
      }
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
          client.userhandler.onSuccess(client.xhr.responseText);
        }
        catch (e) { /* Handler method not defined */ }
        finally { client.callinprogress = false; }
        break;
      }
    }
  }

  var ajaxHandler = {
    onInit: function() {},
    onError: function(status, statusText) {},
    onProgress: function(responseText, length) {},
    onSuccess: function(responseText) {
      var result = JSON.parse(responseText);

      if (result['item']) {
        cache['item-' + result['item']['itemHash']] = result['item'];
        loadDivItem(result['item']['itemHash']);
      }
      if (result['page']) {
        updateListItems(result['page']);
        setCurrentItem();
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

  function collapseClick() {
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
      if (sel != '') {
        sel = '«' + sel + '»';
      }

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
      loadDivItem(itemHash);
      alert('Sorry ! This item is not loaded, try again !');
    }
  }

  function shaarliCurrentItem() {
    shaarliItem(currentItemHash);
  }

  function shaarliClickItem() {
    shaarliItem(getItemHash(this));

    return false;
  }

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
    var folder = null;

    while (folder === null && element !== null) {
      if (element.tagName === 'LI' && hasClass(element, classname)) {
        folder = element;
      }
      element = element.parentNode;
    }

    return folder;
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

  function toggleClickFolder() {
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

  function toggleMarkAsLinkItem(itemHash) {
    var i, item = getItem(itemHash), listLinks;

    if (item !== null) {
      listLinks = item.getElementsByTagName('a');

      for (i = 0; i < listLinks.length; i += 1) {
        if (hasClass(listLinks[i], 'item-mark-as')) {
          if (listLinks[i].href.indexOf('unread=') > -1) {
            listLinks[i].href = listLinks[i].href.replace('unread=','read=');
            listLinks[i].firstChild.innerHTML = 'read';
          } else {
            listLinks[i].href = listLinks[i].href.replace('read=','unread=');
            listLinks[i].firstChild.innerHTML = 'unread';
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
    var item, url, client, indexItem, i, unreadLabelItems, nb, feed, folder;

    item = getItem(itemHash);

    if (item !== null) {
      unreadLabelItems = getUnreadLabelItems(itemHash);

      for (i = 0; i < unreadLabelItems.length; i += 1) {
        nb = addToUnreadLabel(unreadLabelItems[i], -1);
        if (nb === 0) {
          feed = getLiParentByClassName(unreadLabelItems[i], 'feed');
          removeClass(feed, 'has-unread');
          if (autohide) {
            addClass(feed, 'autohide-feed');
          }
        }
        folder = getLiParentByClassName(unreadLabelItems[i], 'folder');
        nb = addToUnreadLabel(getUnreadLabel(folder), -1);
        if (nb === 0 && autohide) {
          addClass(folder, 'autohide-folder');
        }
      }
      addToUnreadLabel(getUnreadLabel(document.getElementById('all-subscriptions')), -1);

      if (hasClass(item, 'read')) {
        url = '?unread=' + itemHash;
        removeClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
      } else {
        url = '?read=' + itemHash;
        addClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
        if (filter === 'unread') {
          url += '&currentHash=' + currentHash
               + '&page=' + currentPage
               + '&last=' + listItemsHash[listItemsHash.length - 1];

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
      url = '?currentHash=' + currentHash
          + '&page=' + currentPage;
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

  function markAsClickItem() {
    markAsItem(getItemHash(this));

    return false;
  }

  function markAsRead(itemHash) {
    setNbUnread(currentUnread - 1);

  }

  function markAsUnread(itemHash) {
    setNbUnread(currentUnread + 1);

  }

  function loadDivItem(itemHash) {
    var element, url, client, cacheItem;

    element = document.getElementById('item-div-'+itemHash);
    if (element.childNodes.length <= 1) {
      cacheItem = getCacheItem(itemHash);
      if (cacheItem != null) {
        setDivItem(element, cacheItem);
        removeCacheItem(itemHash);
      } else {
        url = '?currentHash=' + currentHash
            + '&current=' + itemHash
            + '&ajax';
        client = new HTTPClient();
        client.init(url, element);
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

  function toggleClickItem() {
    toggleItem(getItemHash(this));

    return false;
  }

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
    var item = null

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
    var markAs = 'read';

      if (item['read'] == 1) {
        markAs = 'unread';
      }

    div.innerHTML = '<div class="item-title">' +
      '<a class="item-shaarli" href="' + '?currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label">share</span></a> ' +
      '<a class="item-mark-as" href="' + '?currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label item-label-mark-as">' + markAs + '</span></a> ' +
      '<a target="_blank" class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a>' +
      '</div>' +
      '<div class="clear"></div>' +
      '<div class="item-info-end">' +
      'from <a class="item-via" href="' + item['via'] + '">' +
      item['author'] +
      '</a> ' +
      item['time']['expanded'] +
      ' <a class="item-xml" href="' + item['xmlUrl'] + '">' +
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
      '<div class="item-info-end">' +
      '<a class="item-shaarli" href="' + '?currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label label-expanded">share</span></a> ' +
      '<a class="item-mark-as" href="' + '?currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label label-expanded">' + markAs + '</span></a>' +
      '</div>' +
      '<div class="clear"></div>';

    initLinkItems(div.getElementsByTagName('a'));

    anonymize(div);
  }

  function setLiItem(li, item) {
    var markAs = 'read';

    if (item['read'] == 1) {
      markAs = 'unread';
    }

    li.innerHTML = '<a id="item-toggle-'+ item['itemHash'] +'" class="item-toggle item-toggle-plus" href="' + '?currentHash=' + currentHash + '&current=' + item['itemHash'] +'&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '"> ' +
      item['time']['list'] +
      ' <span class="ico">' +
      '<span class="ico-circle"></span>' +
      '<span class="ico-line-h"></span>' +
      '<span class="ico-line-v item-toggle-close"></span>' +
      '</span>' +
      '</a>' +
      '<dl class="dl-horizontal item">' +
      '<dt class="item-feed">' +
      (addFavicon?
      '<span class="item-favicon">' +
      '<img src="' + item['favicon'] + '" height="16px" width="16px" title="favicon" alt="favicon"/>' +
      '</span>':'' ) +
      '<span class="item-author">' +
      '<a class="item-feed" href="?currentHash=' + item['itemHash'].substring(0, 6) + '">' +
      item['author'] +
      '</a>' +
      '</span>' +
      '</dt>' +
      '<dd class="item-info">' +
      '<span class="item-title">' +
      '<a class="item-mark-as" href="' + '?currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label">' + markAs + '</span></a> ' +
      '<a target="_blank" class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a> ' +
      '</span>' +
      '<span class="item-description">' +
      '<a class="item-toggle muted" href="' + '?currentHash=' + currentHash + '&current=' + item['itemHash'] + '&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '">' +
      item['description'] +
      '</a> ' +
      '</span>' +
      '</dd>' +
      '</dl>';

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
      if (hasClass(listItems[i], 'item-shaarli')) {
        listItems[i].onclick = shaarliClickItem;
      }
    }
  }

  function initListItems() {
    var url, client;

    url = '?currentHash=' + currentHash
        + '&page=' + currentPage
        + '&last=' + listItemsHash[listItemsHash.length -1]
        + '&ajax';

    client = new HTTPClient();
    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

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

  function setWindowLocation() {
    if (currentItemHash != '' && autofocus) {
      window.location = '#item-' + currentItemHash;
    }
  }

  function previousClickPage() {
    previousPage();

    return false;
  }

  function nextClickPage() {
    nextPage();

    return false;
  }

  function nextPage() {
    currentPage = currentPage + 1;
    if (currentPage > Math.ceil(currentNbItems / byPage)) {
      currentPage = Math.ceil(currentNbItems / byPage);
    }
    if (listItemsHash.length == 0) {
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

  function previousClickItem() {
    previousItem();

    return false;
  }

  function nextClickItem() {
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
        addClass(document.getElementById('item-'+currentItemHash), 'current');
        addClass(document.getElementById('item-div-'+currentItemHash), 'current');
        setWindowLocation();
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
      function moveHandler( e ) {

        if ( !start ) {
          return;
        }

        if (e.targetTouches.length == 1) {
          var touch = e.targetTouches[0];
          stop = { time: ( new Date() ).getTime(),
                   coords: [ touch.pageX, touch.pageY ] };

          // prevent scrolling
          if ( Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] )
                                        >  scrollSupressionThreshold
             ) {
            e.preventDefault();
          }
        }
      }

      addEvent(window, 'touchmove', moveHandler);
      addEvent(window, 'touchend', function (e) {
        removeEvent(window, 'touchmove', moveHandler);
        if ( start && stop ) {
          if ( stop.time - start.time < durationThreshold
            && Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] )
             > horizontalDistanceThreshold
            && Math.abs( start.coords[ 1 ] - stop.coords[ 1 ] )
             < verticalDistanceThreshold
             ) {
            start.coords[0] > stop.coords[ 0 ]
                ? nextItem()
                : previousItem() ;
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
        window.location.href = (currentHash==''?'?edit':'?edit='+currentHash);
        break;
        case 70: // 'F'
        if (listFeeds =='show') {
          window.location.href = (currentHash==''?'?':'?currentHash='+currentHash+'&')+'listFeeds=hide';
        } else {
          window.location.href = (currentHash==''?'?':'?currentHash='+currentHash+'&')+'listFeeds=show';
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
        window.location.href = (currentHash==''?'?update':'?currentHash=' + currentHash + '&update='+currentHash);
        break;
        case 86: // 'V'
        if (view == 'list') {
          window.location.href = (currentHash==''?'?':'?currentHash='+currentHash+'&')+'view=expanded';
        } else {
          window.location.href = (currentHash==''?'?':'?currentHash='+currentHash+'&')+'view=list';
        }
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
          listElements[i].href = '?currentHash=' + currentHash
                               + '&previousPage=' + currentPage;
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
          listElements[i].href = '?currentHash=' + currentHash
                               + '&nextPage=' + currentPage;
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
          listElements[i].href = '?currentHash=' + currentHash
                               + '&previousPage=' + currentPage;
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
          listElements[i].href = '?currentHash=' + currentHash
                               + '&nextPage=' + currentPage;
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
          listElements[i].href = '?currentHash=' + currentHash
                               + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash
                               + '&next=' + currentItemHash;

        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].href = '?currentHash=' + currentHash
                               + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash
                               + '&next=' + currentItemHash;

        }
      }
    }
  }

  function initUnread() {
    var element = document.getElementById('nb-unread');

    currentUnread = parseInt(element.innerHTML, 10);

    title = document.title;
    setNbUnread(currentUnread);
  }

  function setNbUnread(nb) {
    var element = document.getElementById('nb-unread');

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

    if (autoupdate) {
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
    </script>
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

                return false;
            }
        }

        // data already loaded
        return true;
    }

    public function writeData()
    {
        if ($this->kfc->isLogged() || (isset($_GET['cron']) && $_GET['cron'] === sha1($this->kfc->salt.$this->kfc->hash))) {
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

    public function getFeedsView()
    {
        $feedsView = array('all' => array('title' => 'All feeds', 'nbUnread' => 0, 'nbAll' => 0, 'feeds' => array()), 'folders' => array());
        
        foreach ($this->_data['feeds'] as $feedHash => $feed) {
            if (isset($feed['error'])) {
                $feed['error'] = $this->getError($feed['error']);
            }
            $feedsView['all']['nbUnread'] += $feed['nbUnread'];
            $feedsView['all']['nbAll'] += $feed['nbAll'];
            if (empty($feed['foldersHash'])) {
                $feedsView['all']['feeds'][$feedHash] = $feed;
            } else {
                foreach ($feed['foldersHash'] as $folderHash) {
                    $folder = $this->getFolder($folderHash);
                    if ($folder !== false) {
                        if (!isset($feedsView['folders'][$folderHash]['title'])) {
                            $feedsView['folders'][$folderHash]['title'] = $folder['title'];
                            $feedsView['folders'][$folderHash]['isOpen'] = $folder['isOpen'];
                            $feedsView['folders'][$folderHash]['nbUnread'] = 0;
                            $feedsView['folders'][$folderHash]['nbAll'] = 0;
                        }
                        $feedsView['folders'][$folderHash]['feeds'][$feedHash] = $feed;
                        $feedsView['folders'][$folderHash]['nbUnread'] += $feed['nbUnread'];
                        $feedsView['folders'][$folderHash]['nbAll'] += $feed['nbAll'];
                    }
                }
            }
        }

        return $feedsView;
    }

    public function getFeed($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash])) {
            $this->_data['feeds'][$feedHash]['xmlUrl'] = htmlspecialchars($this->_data['feeds'][$feedHash]['xmlUrl']);
            $this->_data['feeds'][$feedHash]['htmlUrl'] = htmlspecialchars($this->_data['feeds'][$feedHash]['htmlUrl']);
            return $this->_data['feeds'][$feedHash];
        }

        return false;
    }

    public function getFaviconFeed($feedHash)
    {
        $htmlUrl = $this->_data['feeds'][$feedHash]['htmlUrl'];
        $url = 'http://getfavicon.appspot.com/'.$htmlUrl.'?defaulticon=bluepng';
        $file = FAVICON_DIR.'/favicon.'.$feedHash.'.ico';

        if ($this->kfc->isLogged()) {
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
        $timeUpdate)
    {
        if (isset($this->_data['feeds'][$feedHash])) {
            if (!empty($title)) {
                $this->_data['feeds'][$feedHash]['title'] = $title;
            }
            if (!empty($description)) {
                $this->_data['feeds'][$feedHash]['description'] = $description;
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
            return $this->_data['items']+$this->_data['newItems'];
        }

        if (empty($hash) or $hash == 'all' and $filter == 'old') {
            return $this->_data['items'];
        }

        if (empty($hash) or $hash == 'all' and $filter == 'new') {
            return $this->_data['newItems'];
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
            foreach ($this->_data['newItems'] as $itemHash => $item) {
                if ($item[1] === $isRead) {
                    $list[$itemHash] = $item;
                }
            }
        } else {
            if (strlen($hash) === 12) {
                // an item
                if (isset($this->_data['items'][$hash])) {
                    $list[$hash] = $this->_data['items'][$hash];
                } else if (isset($this->_data['newItems'][$hash])) {
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

        return $list;
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
                $item['time'] = array('list' => utf8_encode(strftime('%R %p', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
            } else {
                $item['time'] = array('list' => utf8_encode(strftime('%b %e, %Y', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));                
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

    public function formatChannel($channel)
    {
        $newChannel = array();

        // list of format for each info in order of importance
        $formats = array('title' => array('title'),
                         'description' => array('description', 'subtitle'),
                         'htmlUrl' => array('link', 'id', 'guid'));

        foreach ($formats as $format => $list) {
            $newChannel[$format] = '';
            $len = count($list);
            for ($i = 0; $i < $len; $i++) {
                if ($channel->hasChildNodes()) {
                    $child = $channel->childNodes;
                    for ($j = 0, $lenChannel = $child->length;
                         $j<$lenChannel;
                         $j++) {
                        if (isset($child->item($j)->tagName)
                            && $child->item($j)->tagName == $list[$i]
                        ) {
                            $newChannel[$format]
                                = $child->item($j)->textContent;
                        }
                    }
                }
            }
        }

        return $newChannel;
    }

    public function getChannelFromXml($xml)
    {
        $channel = array();

        // find feed type RSS, Atom
        $feed = $xml->getElementsByTagName('channel');
        if ($feed->item(0)) {
            // RSS/rdf:RDF feed
            $channel = $feed->item(0);
        } else {
            $feed = $xml->getElementsByTagName('feed');
            if ($feed->item(0)) {
                // Atom feed
                $channel = $feed->item(0);
            } else {
                // unknown feed
            }
        }

        if (!empty($channel)) {
            $channel = $this->formatChannel($channel);
        }

        return $channel;
    }

    public function formatItems($items, $formats)
    {
        $newItems = array();

        foreach ($items as $item) {
            $tmpItem = array();
            foreach ($formats as $format => $list) {
                $tmpItem[$format] = '';
                $len = count($list);
                for ($i = 0; $i < $len; $i++) {
                    if (is_array($list[$i])) {
                        $tag = $item->getElementsByTagNameNS(
                            $list[$i][0],
                            $list[$i][1]
                        );
                    } else {
                        $tag = $item->getElementsByTagName($list[$i]);
                        // wrong detection : e.g. media:content for content
                        if ($tag->length != 0) {
                            for ($j = $tag->length; --$j >= 0;) {
                                $elt = $tag->item($j);
                                if ($tag->item($j)->tagName != $list[$i]) {
                                    $elt->parentNode->removeChild($elt);
                                }
                            }
                        }
                    }
                    if ($tag->length != 0) {
                        // we find a correspondence for the current format
                        // select first item (item(0)), (may not work)
                        // stop to search for another one
                        if ($format == 'link') {
                            $tmpItem[$format] = '';
                            for ($j = 0; $j < $tag->length; $j++) {
                                if ($tag->item($j)->hasAttribute('rel') && $tag->item($j)->getAttribute('rel') == 'alternate') {
                                    $tmpItem[$format]
                                        = $tag->item($j)->getAttribute('href');
                                    $j = $tag->length;
                                }
                            }
                            if ($tmpItem[$format] == '') {
                                $tmpItem[$format]
                                    = $tag->item(0)->getAttribute('href');
                            }
                        }
                        if (empty($tmpItem[$format])) {
                            $tmpItem[$format] = $tag->item(0)->textContent;
                        }
                        $i = $len;
                    }
                }
            }
            if (!empty($tmpItem['link'])) {
                $hashUrl = MyTool::smallHash($tmpItem['link']);
                $newItems[$hashUrl] = array();
                $newItems[$hashUrl]['title'] = $tmpItem['title'];
                $newItems[$hashUrl]['time']  = strtotime($tmpItem['time'])
                    ? strtotime($tmpItem['time'])
                    : time();
                if (MyTool::isUrl($tmpItem['via'])
                    && $tmpItem['via'] != $tmpItem['link']) {
                    $newItems[$hashUrl]['via'] = $tmpItem['via'];
                } else {
                    $newItems[$hashUrl]['via'] = '';
                }
                $newItems[$hashUrl]['link'] = $tmpItem['link'];
                $newItems[$hashUrl]['author'] = $tmpItem['author'];
                mb_internal_encoding("UTF-8");
                $newItems[$hashUrl]['description'] = mb_substr(
                    strip_tags($tmpItem['description']), 0, 500
                );
                $newItems[$hashUrl]['content'] = $tmpItem['content'];
            }
        }

        return $newItems;
    }

    public function getItemsFromXml ($xml)
    {
        $items = array();

        // find feed type RSS, Atom
        $feed = $xml->getElementsByTagName('channel');
        if ($feed->item(0)) {
            // RSS/rdf:RDF feed
            $feed = $xml->getElementsByTagName('item');
            $len = $feed->length;
            for ($i = 0; $i < $len; $i++) {
                $items[$i] = $feed->item($i);
            }
            $feed = $xml->getElementsByTagName('rss');
            if (!$feed->item(0)) {
                $feed = $xml->getElementsByTagNameNS(
                    "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
                    'RDF'
                );
            }
        } else {
            $feed = $xml->getElementsByTagName('feed');
            if ($feed->item(0)) {
                // Atom feed
                $feed = $xml->getElementsByTagName('entry');
                $len = $feed->length;
                for ($i = 0; $i < $len; $i++) {
                    $items[$i] = $feed->item($i);
                }
                $feed = $xml->getElementsByTagName('feed');
            }
        }

        // list of format for each info in order of importance
        $formats = array(
            'author'      => array('author', 'creator', 'dc:author',
                                   'dc:creator'),
            'content'     => array('content:encoded', 'content', 'description',
                               'summary', 'subtitle'),
            'description' => array('description', 'summary', 'subtitle',
                                   'content', 'content:encoded'),
            'via'        => array('guid', 'id'),
            'link'        => array('feedburner:origLink', 'link', 'guid', 'id'),
            'time'        => array('pubDate', 'updated', 'lastBuildDate',
                                   'published', 'dc:date', 'date', 'created',
                                   'modified'),
            'title'       => array('title'));

        if ($feed->item(0)) {
            $formats = $this->formatRDF($formats, $feed->item(0));
        }

        return $this->formatItems($items, $formats);
    }

    public function getAttributeNS ($feed, $name)
    {
        $res = '';
        if ($feed->nodeName === $name) {
            $ns = explode(':', $name);
            $res = $feed->getAttribute('xmlns:'.$ns[0]);
        } else {
            if ($feed->hasChildNodes()) {
                foreach ($feed->childNodes as $childNode) {
                    if ($res === '') {
                        $res = $this->getAttributeNS($childNode, $name);
                    } else {
                        break;
                    }
                }
            }
        }

        return $res;
    }

    public function formatRDF($formats, $feed)
    {
        foreach ($formats as $format => $list) {
            for ($i = 0, $len = count($list); $i < $len; $i++) {
                $name = explode(':', $list[$i]);
                if (count($name)>1) {
                    $res = $feed->getAttribute('xmlns:'.$name[0]);
                    if (!empty($res)) {
                        $ns = $res;
                    } else {
                        $ns = $this->getAttributeNS($feed, $list[$i]);
                    }
                    $formats[$format][$i] = array($ns, $name[1]);
                }
            }
        }

        return $formats;
    }

    public function loadUrl($url, $opts = array()){
        $ch = curl_init($url);
        if (!empty($opts)) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $opts['http']['user_agent']);
        }
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $output = $this->curl_exec_follow($ch);

        curl_close($ch);

        return $output;
    }
 
    public function curl_exec_follow(&$ch, $redirects = 20, $curloptHeader = false) {
        if ((!ini_get('open_basedir') && !ini_get('safe_mode')) || $redirects < 1) {
            curl_setopt($ch, CURLOPT_HEADER, $curloptHeader);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirects > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
            return curl_exec($ch);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, false);

            do {
                $data = curl_exec($ch);
                if (curl_errno($ch))
                    break;
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                // 301 Moved Permanently
                // 302 Found
                // 303 See Other
                // 307 Temporary Redirect
                if ($code != 301 && $code != 302 && $code!=303 && $code!=307)
                    break;
                $header_start = strpos($data, "\r\n")+2;
                $headers = substr($data, $header_start, strpos($data, "\r\n\r\n", $header_start)+2-$header_start);
                if (!preg_match("!\r\n(?:Location|location|URI): *(.*?) *\r\n!", $headers, $matches))
                    break;
                curl_setopt($ch, CURLOPT_URL, $matches[1]);
            } while (--$redirects);
            if (!$redirects)
                trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
            if (!$curloptHeader)
                $data = substr($data, strpos($data, "\r\n\r\n")+4);

            return $data;
        }
    }

    public function loadXml($xmlUrl)
    {
        // hide warning/error
        set_error_handler(array('MyTool', 'silence_errors'));

        // set user agent
        // http://php.net/manual/en/function.libxml-set-streams-context.php
        $opts = array(
            'http' => array(
                'timeout' => 4,
                'user_agent' => 'KrISS feed agent '.$this->kfc->version.' by Tontof.net http://github.com/tontof/kriss_feed',
                )
            );
        $document = new DOMDocument();

        if (in_array('curl', get_loaded_extensions())) {
            $output = $this->loadUrl($xmlUrl, $opts);
            $document->loadXML($output);
        } else {
            // try using libxml
            $context = stream_context_create($opts);
            libxml_set_streams_context($context);

            // request a file through HTTP
            $document->load($xmlUrl);
        }
        // show back warning/error
        restore_error_handler();

        return $document;
    }

    public function addChannel($xmlUrl)
    {
        $feedHash = MyTool::smallHash($xmlUrl);
        if (!isset($this->_data['feeds'][$feedHash])) {
            $xml = $this->loadXml($xmlUrl);

            if (!$xml) {
                return false;
            } else {
                $channel = $this->getChannelFromXml($xml);
                $items = $this->getItemsFromXml($xml);
                if (count($items) == 0) {
                    return false;
                }
                foreach (array_keys($items) as $itemHash) {
                    if (empty($items[$itemHash]['via'])) {
                        $items[$itemHash]['via'] = $channel['htmlUrl'];
                    }
                    if (empty($items[$itemHash]['author'])) {
                        $items[$itemHash]['author'] = $channel['title'];
                    } else {
                        $items[$itemHash]['author']
                            = $channel['title'] . ' ('
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

                $channel['xmlUrl'] = $xmlUrl;
                $channel['foldersHash'] = array();
                $channel['nbUnread'] = count($items);
                $channel['nbAll'] = count($items);
                $channel['timeUpdate'] = 'auto';
                $channel['lastUpdate'] = time();

                $this->_data['feeds'][$feedHash] = $channel;
                $this->_data['needSort'] = true;

                $this->writeFeed($feedHash, $items);

                return true;
            }
        }

        return false;
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

    public function updateChannel($feedHash)
    {
        $error = '';
        $newItems = array();

        if (!isset($this->_data['feeds'][$feedHash])) {
            return array(
                'error' => $error,
                'newItems' => $newItems
                );
        }

        unset($this->_data['feeds'][$feedHash]['error']);
        $xmlUrl = $this->_data['feeds'][$feedHash]['xmlUrl'];
        $xml = $this->loadXml($xmlUrl);

        if (!$xml) {
            if (file_exists($this->cacheDir.'/'.$feedHash.'.php')) {
                $error = ERROR_LAST_UPDATE;
            } else {
                $error = ERROR_NO_XML;
            }
        } else {
            // if feed description is empty try to update description
            // (after opml import, description is often empty)
            if (empty($this->_data['feeds'][$feedHash]['description'])) {
                $channel = $this->getChannelFromXml($xml);
                if (isset($channel['description'])) {
                    $this->_data['feeds'][$feedHash]['description']
                        = $channel['description'];
                }
                // Check description only the first time description is empty
                if (empty($this->_data['feeds'][$feedHash]['description'])) {
                    $this->_data['feeds'][$feedHash]['description'] = ' ';
                }
            }

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

            $rssItems = $this->getItemsFromXml($xml);
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
                    $error = ERROR_ITEMS_MISSED;
                }

                // Remove useless items
                foreach ($this->getItems($feedHash) as $itemHash => $item) {
                    $itemRssHash = substr($itemHash, 6, 6);
                    // Remove from cache already read items not any more in the feed
                    if (!isset($rssItems[$itemRssHash]) and $item[1] == 1) {
                        unset($this->_data['feeds'][$feedHash]['items'][$itemHash]);
                    }
                
                    if (!isset($this->_data['feeds'][$feedHash]['items'][$itemHash])) {
                        // Remove items not any more in the cache
                        unset($this->_data['items'][$itemHash]);
                        unset($this->_data['newItems'][$itemHash]);
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
            } else {
                $error = ERROR_UNKNOWN;
            }
        }

        // update feed information
        $this->_data['feeds'][$feedHash]['lastUpdate'] = time();
        if (!empty($error)) {
            $this->_data['feeds'][$feedHash]['error'] = $error;
        }

        if (empty($this->_data['feeds'][$feedHash]['items'])) {
            $this->_data['feeds'][$feedHash]['lastTime'] = $newLastTime;
        } else {
            unset($this->_data['feeds'][$feedHash]['lastTime']);
        }
        $this->writeFeed($feedHash, $this->_data['feeds'][$feedHash]['items']);
        unset($this->_data['feeds'][$feedHash]['items']);

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
        $start = microtime(true);
        foreach ($feedsHash as $feedHash) {
            $i++;
            $feed = $this->getFeed($feedHash);
            $str = '<li>'.number_format(microtime(true)-$start,3).' seconds ('.$i.'/'.count($feedsHash).'): Updating: <a href="?currentHash='.$feedHash.'">'.$feed['title'].'</a></li>';
            echo ($format==='html'?$str:strip_tags($str)).str_pad('',4096)."\n";
            ob_flush();
            flush();
            if ($force or $this->needUpdate($feed)) {
                $info = $this->updateChannel($feedHash);
                $str = '<li>'.number_format(microtime(true)-$start,3).' seconds: Updated: <span class="text-success">'.count($info['newItems']).' new item(s)</span>';
                if (empty($info['error'])) {
                    $str .= '</li>';
                } else {
                    $str .= ' <span class="text-error">('.$this->getError($info['error']).')</span></li>';
                }
            } else {
                $str = '<li>'.number_format(microtime(true)-$start,3).' seconds: Already up-to-date: <span class="text-warning">'.$feed['title'].'</span></li>';

            }
            echo ($format==='html'?$str:strip_tags($str)).str_pad('',4096)."\n";
            ob_flush();
            flush();
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

    public static function sortByTitle($a, $b) {
        return strnatcasecmp($a['title'], $b['title']);
    }

    public static function getError($error)
    {
        switch ($error) {
        case ERROR_NO_XML:
            return 'Feed is not in XML format';
            break;
        case ERROR_ITEMS_MISSED:
            return 'Items may have been missed since last update';
            break;
        case ERROR_LAST_UPDATE:
        case ERROR_UNKNOWN:
            return 'Problem with the last update';
            break;
        default:
            return 'unknown error';
            break;
        }
    }
}


class MyTool
{
    public static function initPhp()
    {
        define('START_TIME', microtime(true));

        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 !");
        }

        error_reporting(E_ALL);

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

    public static function silence_errors($num, $str)
    {
	// No-op                                                       
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

            echo '<script>alert("File '
                . htmlspecialchars($filename) . ' (' . MyTool::humanBytes($filesize)
                . ') was successfully processed: ' . $importCount
                . ' links imported.");document.location=\'?\';</script>';

            $kfData['feeds'] = $feeds;
            $kfData['folders'] = $folders;

            return $kfData;
        } else {
            echo '<script>alert("File ' . htmlspecialchars($filename) . ' ('
                . MyTool::humanBytes($filesize) . ') has an unknown'
                . ' file format. Check encoding, try to remove accents'
                . ' and try again. Nothing was imported.");'
                . 'document.location=\'?\';</script>';
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
            $body->appendChild($outline);
        }

        // with folder outline node
        foreach ($withFolder as $folderHash => $arrayHashUrl) {
            $outline = $opmlData->createElement('outline');
            $outlineTitle = $opmlData->createAttribute('title');
            $outlineTitle->value = htmlspecialchars($folders[$folderHash]['title']);
            $outline->appendChild($outlineTitle);
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

    private function initialize()
    {
        $this->tpl = true;
        $ref = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
        $this->assign('referer', $ref);
    }

    // 
    public function assign($variable, $value = null)
    {
        if ($this->tpl === false) {
            $this->initialize(); // Lazy initialization
        }
        if (is_array($variable)) {
            $this->var += $variable;
        } else {
            $this->var[$variable] = $value;
        }
    }

    public function renderPage($page)
    {
        if ($this->tpl===false) {
            $this->initialize(); // Lazy initialization
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
            die("renderPage does not exist: ".$page);
        }
    }
}

class Session
{
    private static $_instance;

    public static $inactivityTimeout = 3600;

    public static $disableSessionProtection = false;

    public static $banFile = 'ipbans.php';
    public static $banAfter = 4;
    public static $banDuration = 1800;

    private function __construct($banFile)
    {
        // Check ban configuration
        self::$banFile = $banFile;

        if (!is_file(self::$banFile)) {
            file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");
        }
        include self::$banFile;

        // Force cookie path (but do not change lifetime)
        $cookie=session_get_cookie_params();
        // Default cookie expiration and path.
        $cookiedir = '';
        if(dirname($_SERVER['SCRIPT_NAME'])!='/') {
            $cookiedir = dirname($_SERVER["SCRIPT_NAME"]).'/';
        }
        session_set_cookie_params($cookie['lifetime'], $cookiedir);
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_name('kriss');
            session_start();
        }
    }

    public static function init($banFile)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session($banFile);
        }
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

    function banLoginOk()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $gb = $GLOBALS['IPBANS'];
        unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
        $GLOBALS['IPBANS'] = $gb;
        file_put_contents(self::$banFile, "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
    }

    function banCanLogin()
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
}//end class

// Check if php version is correct
MyTool::initPHP();
// Initialize Session
Session::init(BAN_FILE);
// XSRF protection with token
if (!empty($_POST)) {
    if (!Session::isToken($_POST['token'])) {
        die('Wrong token.');
    }
    unset($_SESSION['tokens']);
}

$kfc = new FeedConf(CONFIG_FILE, FEED_VERSION);
$kf = new Feed(DATA_FILE, CACHE_DIR, $kfc);

$pb = new PageBuilder('FeedPage');
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
$byPage = $kfc->getByPage();
// Hash : 'all', feed hash or folder hash
$currentHash = $kfc->getCurrentHash();
// Query
$query = '?';
if (!empty($currentHash) and $currentHash !== 'all') {
    $query = '?currentHash='.$currentHash.'&amp;';
}

$pb->assign('view', $view);
$pb->assign('listFeeds', $listFeeds);
$pb->assign('filter', $filter);
$pb->assign('order', $order);
$pb->assign('byPage', $byPage);
$pb->assign('currentHash', $currentHash);
$pb->assign('query', $query);
$pb->assign('redirector', $kfc->redirector);
$pb->assign('shaarli', htmlspecialchars($kfc->shaarli));
$pb->assign('autoreadItem', $kfc->autoreadItem);
$pb->assign('autoreadPage', $kfc->autoreadPage);
$pb->assign('autohide', $kfc->autohide);
$pb->assign('autofocus', $kfc->autofocus);
$pb->assign('autoupdate', $kfc->autoUpdate);
$pb->assign('addFavicon', $kfc->addFavicon);
$pb->assign('kf', $kf);
$pb->assign('version', FEED_VERSION);
$pb->assign('kfurl', MyTool::getUrl());

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
        die("Login failed !");
    } else {
        $pb->assign('pagetitle', 'Login - '.strip_tags($kfc->title));
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
    $pb->assign('pagetitle', 'Change your password');
    $pb->renderPage('changePassword');
} elseif (isset($_GET['ajax'])) {
    $kf->loadData();
    $needSave = false;
    $result = array();
    if (isset($_GET['current'])) {
        $result['item'] = $kf->getItem($_GET['current'], false);
        $result['item']['itemHash'] = $_GET['current'];
    }
    if (isset($_GET['read'])) {
        $needSave = $kf->mark($_GET['read'], 1);
        if ($needSave) {
            $result['read'] = $_GET['read'];
        }
    }
    if (isset($_GET['unread'])) {
        $needSave = $kf->mark($_GET['unread'], 0);
        if ($needSave) {
            $result['unread'] = $_GET['unread'];
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
        foreach(array_slice($results, $firstIndex + 1, count($results) - $firstIndex - 1, true) as $itemHash => $item) {
            $result['page'][$i] = $kf->getItem($itemHash, false);
            $result['page'][$i]['read'] = $item[1];
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
                } else {
                    $info['error'] = $kf->getError($info['error']);
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
    MyTool::renderJson($result);
} elseif (isset($_GET['help']) && $kfc->isLogged()) {
    $pb->assign('pagetitle', 'Help for KrISS feed');
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
    if (isset($_GET['cron']) || isset($argv) && count($argv) >= 3) {
        $kf->updateFeedsHash($feedsHash, $forceUpdate);
    } else {
        $pb->assign('feedsHash', $feedsHash);
        $pb->assign('forceUpdate', $forceUpdate);
        $pb->assign('pagetitle', 'Update');
        $pb->renderPage('update');
    }
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
        $pb->assign('pagetitle', 'Config - '.strip_tags($kfc->title));
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
            echo '<script>alert("The file you are trying to upload'
                . ' is probably bigger than what this webserver can accept '
                . '(' . MyTool::humanBytes(MyTool::getMaxFileSize())
                . ' bytes). Please upload in smaller chunks.");'
                . 'document.location=\'' . htmlspecialchars($rurl)
                . '\';</script>';
            exit;
        }
        
        $kf->loadData();
        $kf->setData(Opml::importOpml($kf->getData()));
        $kf->sortFeeds();
        $kf->writeData();
        exit;
    } else if (isset($_POST['cancel'])) {
        MyTool::redirect();
    } else {
        $pb->assign('pagetitle', 'Import');
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
        if ($kf->addChannel($_POST['newfeed'])) {
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
            $kf->editFeed($hash, '', '', $folders, '');
            $kf->sortFeeds();
            $kf->writeData();
            MyTool::redirect('?currentHash='.$hash);
        } else {
            // Add fail
            $returnurl = empty($_SERVER['HTTP_REFERER'])
                ? MyTool::getUrl()
                : $_SERVER['HTTP_REFERER'];
            echo '<script>alert("The feed you are trying to add already exists'
                . ' or is wrong. Check your feed or try again later.");'
                . 'document.location=\'' . htmlspecialchars($returnurl)
                . '\';</script>';
            exit;
        }
    }

    $newfeed = '';
    if (isset($_GET['newfeed'])) {
        $newfeed = htmlspecialchars($_GET['newfeed']);
    }
    $pb->assign('page', 'add');
    $pb->assign('pagetitle', 'Add a new feed');
    $pb->assign('newfeed', $newfeed);
    $pb->assign('folders', $kf->getFolders());
    
    $pb->renderPage('addFeed');
} elseif (isset($_GET['toggleFolder']) && $kfc->isLogged()) {
    $kf->loadData();
    if (isset($_GET['toggleFolder'])) {
        $kf->toggleFolder($_GET['toggleFolder']);
    }
    $kf->writeData();
    MyTool::redirect();
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
        MyTool::redirect($query.'current='.$hash);
    } else {
        if ($filter === 'unread' && $read === 1) {
            MyTool::redirect('?');
        } else {
            MyTool::redirect($query);
        }
    }
} elseif (isset($_GET['edit']) && $kfc->isLogged()) {
    // Edit feed, folder, all
    $kf->loadData();
    $pb->assign('page', 'edit');
    $pb->assign('pagetitle', 'edit');
    
    $hash = substr(trim($_GET['edit'], '/'), 0, 6);
// type : 'feed', 'folder', 'all', 'item'
$type = $kf->hashType($currentHash);
    $type = $kf->hashType($hash);
    switch($type) {
    case 'feed':
        if (isset($_POST['save'])) {
            $title = $_POST['title'];
            $description = $_POST['description'];
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

            $kf->editFeed($hash, $title, $description, $folders, $timeUpdate);
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

            $feedsHash = array();
            foreach ($_POST['feeds'] as $feedHash) {
                $feedsHash[] = $feedHash;
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

                $kf->editFeed(
                    $feedHash,
                    '',
                    '',
                    $addFoldersHash,
                    ''
                );
            }
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
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        case 'feed':
            $hashView = 'Feed (<a href="'.$kf->getFeedHtmlUrl($currentHash).'" title="">'.$kf->getFeedTitle($currentHash).'</a>): '.'<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        case 'folder':
            $hashView = 'Folder ('.$kf->getFolderTitle($currentHash).'): <span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        default:
            $hashView = '<span id="nb-unread">'.$unread.'</span><span class="hidden-phone"> unread items</span>';
            break;
        }

        $menu = $kfc->getMenu();
        $paging = $kfc->getPaging();
        $pb->assign('menu',  $menu);
        $pb->assign('paging',  $paging);
        $pb->assign('currentHashType', $currentHashType);
        $pb->assign('currentHashView', $hashView);
        $pb->assign('currentPage',  (int) $currentPage);
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
        $pb->assign('pagetitle', 'Login - '.strip_tags($kfc->title));
        if (!empty($_SERVER['QUERY_STRING'])) {
            $pb->assign('referer', MyTool::getUrl().'?'.$_SERVER['QUERY_STRING']);
        }
        $pb->renderPage('login');
    }
}
//print(number_format(microtime(true)-START_TIME,3).' secondes');
