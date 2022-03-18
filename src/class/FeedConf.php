<?php
/**
 * Feed Conf is a class to configurate Feed reader.
 */
class FeedConf
{
    /**
     * Configuration file
     */
    private $_file = '';

    /**
     * Login
     */
    public $login = '';

    /**
     * Hash of the password
     */
    public $hash = '';

    /**
     * Disable session protection
     */
    public $disableSessionProtection = false;

    /**
     * Salt
     */
    public $salt = '';

    /**
     * Feed title
     */
    public $title = "Kriss feed";

    /**
     * Redirector (e.g. http://anonym.to/? will mask the HTTP_REFERER)
     * (consider only links in the article, not images and media)
     */
    public $redirector = '';

    /**
     * locale
     */
    public $locale = 'en_GB';

    /**
     * Shaarli link
     */
    public $shaarli = '';

    /**
     * Max number of articles by channel
     */
    public $maxItems = 100;

    /**
     * Max number of minutes between each update of channel
     */
    public $maxUpdate = 60;

    /**
     * Order ('newerFirst' or 'olderFirst')
     */
    public $order = 'newerFirst';

    /**
     * Mark as read when next item
     */
    public $autoreadItem = false;

    /**
     * Mark as read when next page
     */
    public $autoreadPage = false;

    /**
     * Autoupdate with javascript
     */
    public $autoUpdate = false;

    /**
     * Hide feed with 0 item in list feeds
     */
    public $autohide = false;

    /**
     * Focus automatically to current item
     */
    public $autofocus = true;

    /**
     * Add favicon next to feed
     */
    public $addFavicon = false;

    /**
     * Preload feed items
     */
    public $preload = false;

    /**
     * Target _blank
     */
    public $blank = false;

    /**
     * Swipe on mobile
     */
    public $swipe = true;

    /**
     * Visibility public/protected/private feed reader
     */
    public $visibility = 'private';

    /**
     * Kriss_feed version
     */
    public $version;

    /**
     * Default view (expanded or list)
     */
    public $view = 'list';

    /**
     * filter ('unread' or 'all' items)
     */
    public $filter = 'unread';

    /**
     * Show list of feeds
     */
    public $listFeeds = 'show';

    /**
     * Number of entries to display per page
     */
    public $byPage = 10;

    /**
     * current hash : 'all', feed hash or folder hash
     */
    public $currentHash = 'all';

    /**
     * current page
     */
    public $currentPage = 1;

    /**
     * language (en_GB, fr_FR, etc.)
     */
    public $lang = '';

    /**
     * menu personnalization
     */
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

    /**
     * paging personnalization
     */
    public $pagingItem = 1;
    public $pagingPage = 2;
    public $pagingByPage = 3;
    public $pagingMarkAs = 4;

    /**
     * Constructor
     *
     * @param string $configFile Configuration file
     * @param string $version    Kriss feed version
     */
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
                    FeedPage::$pb->assign('message', sprintf(Intl::msg('Can not create %s directory, check permissions'), INC_DIR));
                    FeedPage::$pb->renderPage('message');
                }
            }
            if (!is_dir(FAVICON_DIR)) {
                if (!@mkdir(FAVICON_DIR, 0755)) {
                    FeedPage::$pb->assign('message', sprintf(Intl::msg('Can not create %s directory, check permissions'), FAVICON_DIR));
                    FeedPage::$pb->renderPage('message');
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

    /**
     * Installation of the configuration file
     */
    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            $this->write();

            FeedPage::$pb->assign('pagetitle', 'KrISS feed installation');
            FeedPage::$pb->assign('class', 'text-success');
            FeedPage::$pb->assign('message', Intl::msg('Your simple and smart (or stupid) feed reader is now configured.'));
            FeedPage::$pb->assign('referer', MyTool::getUrl().'?import');
            FeedPage::$pb->assign('button', Intl::msg('Continue'));
            FeedPage::$pb->renderPage('message');
        } else {
            FeedPage::$pb->assign('pagetitle', Intl::msg('KrISS feed installation'));
            FeedPage::$pb->assign('token', Session::getToken());
            FeedPage::$pb->renderPage('install');
        }
        exit();
    }

    /**
     * Hydrate to set all configuration variables
     *
     * @param array $data List of variable to hydrate
     */
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

    /**
     * Get lang
     *
     * @return string 
     */
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

    /**
     * Get current view (expanded or list)
     *
     * @return string 'expanded' or 'list'
     */
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

    /**
     * Get current filter (unread all)
     *
     * @return string 'unread' or 'all'
     */
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

    /**
     * get list feeds
     *
     * @return true|false
     */
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

    /**
     * Get current byPage
     *
     * @return int number of item to display by page
     */
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

    /**
     * Get order
     *
     * @return string 'newerFirst' or 'olderFirst'
     */
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

    /**
     * Get currentHash
     *
     * @return string 'all' or feed hash or folder hash
     */
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

    /**
     * Get currentPage
     *
     * @return int current page
     */
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

    /**
     * Disable session protection setter
     *
     * @param string $disableSessionProtection Disable session protection
     */
    public function setDisableSessionProtection($disableSessionProtection)
    {
        $this->disableSessionProtection = $disableSessionProtection;
    }

    /**
     * Login setter
     *
     * @param string $login New login
     */
    public function setLogin($login)
    {
        $this->login = $login;
    }

    /**
     * Visibility setter
     *
     * @param string $visibility New visibility
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * Hash setter
     *
     * @param string $pass New hash
     */
    public function setHash($pass)
    {
        $this->hash = sha1($pass.$this->login.$this->salt);
    }

    /**
     * Salt setter
     *
     * @param string $salt New salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Title setter
     *
     * @param string $title New title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Locale setter
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Redirector setter
     *
     * @param string $redirector New redirector
     */
    public function setRedirector($redirector)
    {
        $this->redirector = $redirector;
    }

    /**
     * AutoreadPage setter
     *
     * @param string $autoreadPage
     */
    public function setAutoreadPage($autoreadPage)
    {
        $this->autoreadPage = $autoreadPage;
    }

    /**
     * AutoUpdate setter
     *
     * @param string $autoUpdate
     */
    public function setAutoUpdate($autoUpdate)
    {
        $this->autoUpdate = $autoUpdate;
    }

    /**
     * AutoreadItem setter
     *
     * @param string $autoreadItem
     */
    public function setAutoreadItem($autoreadItem)
    {
        $this->autoreadItem = $autoreadItem;
    }

    /**
     * Autohide setter
     *
     * @param string $autohide
     */
    public function setAutohide($autohide)
    {
        $this->autohide = $autohide;
    }

    /**
     * Autofocus setter
     *
     * @param string $autofocus
     */
    public function setAutofocus($autofocus)
    {
        $this->autofocus = $autofocus;
    }

    /**
     * Add favicon setter
     *
     * @param string $addFavicon
     */
    public function setAddFavicon($addFavicon)
    {
        $this->addFavicon = $addFavicon;
    }

    /**
     * Add preload setter
     *
     * @param bool $preload
     */
    public function setPreload($preload)
    {
        $this->preload = $preload;
    }

    /**
     * Shaarli setter
     *
     * @param string $url New shaarli
     */
    public function setShaarli($url)
    {
        $this->shaarli = $url;
    }

    /**
     * MaxUpdate setter
     *
     * @param string $max New maxUpdate
     */
    public function setMaxUpdate($max)
    {
        $this->maxUpdate = $max;
    }

    /**
     * MaxItems setter
     *
     * @param string $max New maxItems
     */
    public function setMaxItems($max)
    {
        $this->maxItems = $max;
    }

    /**
     * Order setter
     *
     * @param string $order New order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * Blank setter
     *
     * @param string $blank New blank
     */
    public function setBlank($blank)
    {
        $this->blank = $blank;
    }

    /**
     * Swipe setter
     *
     * @param string $swipe
     */
    public function setSwipe($swipe)
    {
        $this->swipe = $swipe;
    }

    /**
     * Get menu
     *
     * @return array of menu sorted elements
     */
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

    /**
     * Write configuration file
     *
     * @return true|false True if file successfully saved, false otherwise
     */
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
                          'pagingMarkAs', 'disableSessionProtection', 'blank', 'swipe', 'lang');
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
