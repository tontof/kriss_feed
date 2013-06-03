<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk
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

/**
 * autoload class
 *
 * @param string $className The name of the class to load
 */
function __autoload($className)
{
    include_once 'class/'. $className . '.php';
}

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
$ks = new Star(STAR_FILE, ITEM_FILE, $kfc);

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
$pb->assign('version', FEED_VERSION);
$pb->assign('kfurl', MyTool::getUrl());
$pb->assign('isLogged', $kfc->isLogged());

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
        foreach(array_slice($results, $firstIndex + 1, count($results) - $firstIndex - 1, true) as $itemHash => $item) {
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
    if ($needStarSave) {
        $ks->writeData();
    }
    MyTool::renderJson($result);
} elseif (isset($_GET['help']) && ($kfc->isLogged() || $kfc->visibility === 'protected')) {
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

    $pb->assign('currentHash', $hash);
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
            $kf->editFeed($hash, '', '', $folders, '', '');
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
        $hashView = '<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> starred items</span>';
        break;
    case 'feed':
        $hashView = 'Feed (<a href="'.$kf->getFeedHtmlUrl($currentHash).'" title="">'.$kf->getFeedTitle($currentHash).'</a>): '.'<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> starred items</span>';
        break;
    case 'folder':
        $hashView = 'Folder ('.$kf->getFolderTitle($currentHash).'): <span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> starred items</span>';
        break;
    default:
        $hashView = '<span id="nb-starred">'.$nbItems.'</span><span class="hidden-phone"> starred items</span>';
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
        $pb->assign('feedsView', $ks->getFeedsView());
    }
    $pb->assign('kf', $ks);
    $pb->assign('pagetitle', 'Starred items');
    $pb->renderPage('index');
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
} elseif (isset($_GET['file'])) {
    if ($_GET['file'] == 'favicon') {
        $favicon = '
<?php include("inc/favicon.ico"); ?>
';
        header('Content-Type: image/vnd.microsoft.icon');
        echo base64_decode($favicon);
        exit();
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
