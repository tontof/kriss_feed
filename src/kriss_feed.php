<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk

define('DATA_DIR', 'data');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('FEED_VERSION', 2);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

define('MIN_TIME_UPDATE', 5); // Minimum accepted time for update

define('ERROR_NO_XML', 1);
define('ERROR_ITEMS_MISSED', 2);
define('ERROR_LAST_UPDATE', 3);

/**
 * autoload class
 *
 * @param string $className The name of the class to load
 */
function __autoload($className)
{
    include_once 'class/'. $className . '.php';
}

MyTool::initPhp();
Session::init();

$kfc = new Feed_Conf(CONFIG_FILE, FEED_VERSION);
$kfp = new Feed_Page(STYLE_FILE);
$kf = new Feed(DATA_FILE, $kfc);

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
            $rurl = $_POST['returnurl'];
            if (empty($rurl)) {
                $rurl = MyTool::getUrl();
            }
            header('Location: '.$rurl);
            exit();
        }
        die("Login failed !");
    } else {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
        }
        echo '
<h1>Login</h1>
<form method="post" action="?login">
  <input type="hidden" name="returnurl" value="'.$ref.'" />
  <p><label>Login: <input type="text" name="login" /></label></p>
  <p><label>Password: <input type="password" name="password" /></label></p>
  <p><input type="submit" value="OK" class="submit" /></p>
</form>';
    }
} elseif (isset($_GET['logout'])) {
    //Logout
    Session::logout();
    $rurl = (empty($_SERVER['HTTP_REFERER']) ? '?' : $_SERVER['HTTP_REFERER']);
    header('Location: '.$rurl);
    exit();
} elseif (isset($_GET['config']) && Session::isLogged()) {
    // Config
    if (isset($_POST['save'])) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
        $kfc->hydrate($_POST);
        if (!$kfc->write()) {
            die("Can't write to ".CONFIG_FILE);
        }

        $rurl = MyTool::getUrl();
        if (isset($_POST['returnurl'])) {
            $rurl = $_POST['returnurl'];
        }
        header('Location: '.$rurl);
        exit();
    } elseif (isset($_POST['cancel'])) {
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }

        $rurl = MyTool::getUrl();
        if (isset($_POST['returnurl'])) {
            $rurl = $_POST['returnurl'];
        }
        header('Location: '.$rurl);
        exit();
    } else {
        echo $kfp->htmlPage('Configuration', $kfp->configPage($kfc));
        exit();
    }
} elseif (isset($_GET['import']) && Session::isLogged()) {
    // Import
    if (isset($_POST['import'])) {
        // If file is too big, some form field may be missing.
        if (!isset($_POST['token'])
            || (!isset($_FILES))
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
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
        $kf->importOpml();
        exit;
    } else {
        echo $kfp->htmlPage('Import', $kfp->importPage());
        exit();
    }
} elseif (isset($_GET['export']) && Session::isLogged()) {
    // Export
    $kf->loadData();
    $kf->exportOpml();
} elseif (isset($_GET['newfeed'])
          && !empty($_GET['newfeed'])
          && Session::isLogged()
    ) {
    // Add channel
    $kf->loadData();
    if ($kf->addChannel($_GET['newfeed'])) {
        // Add success
        header(
            'Location: ' . MyTool::getUrl() . '?' . $kfc->defaultView
            . '=' . MyTool::smallHash($_GET['newfeed'])
        );
        exit();
    } else {
        $returnurl = empty($_SERVER['HTTP_REFERER'])
            ? '?' . $kfc->defaultView
            : $_SERVER['HTTP_REFERER'];
        echo '<script>alert("The feed you are trying to add already exists'
            . ' or is wrong. Check your feed or try again later.");'
            . 'document.location=\'' . htmlspecialchars($returnurl)
            . '\';</script>';
        exit;
        // Add fail
    }
} elseif ((isset($_GET['read']) || isset($_GET['unread']))
          && Session::isLogged()) {
    // mark all as read : item, feed, folder, all
    $kf->loadData();
    $hash = '';
    $read = 1;
    if (isset($_GET['read'])) {
        $hash = substr(trim($_GET['read'], '/'), 0, 6);
        $read = 1;
    } else {
        $hash = substr(trim($_GET['unread'], '/'), 0, 6);
        $read = 0;
    }
    $kf->mark($hash, $read);
    $rurl = MyTool::getUrl();
    if (isset($_SERVER['HTTP_REFERER'])) {
        $rurl = $_SERVER['HTTP_REFERER'];
    }
    header('Location: '.$rurl);
    exit();
} elseif (isset($_GET['edit']) && !empty($_GET['edit'])
          && Session::isLogged()) {
    // Edit feed, folder
    $kf->loadData();
    $hash = substr(trim($_GET['edit'], '/'), 0, 6);
    $type = $kf->hashType($hash);
    switch($type) {
    case 'feed':
        if (isset($_POST['save'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }

            $title = $_POST['title'];
            $description = $_POST['description'];
            $folders = array();
            foreach ($_POST['folders'] as $hashFolder) {
                $folders[] = $kf->getFolder($hashFolder);
            }
            if (!empty($_POST['newfolder'])) {
                $folders[] = $_POST['newfolder'];
            }
            $timeUpdate = $_POST['timeUpdate'];

            $kf->editFeed($hash, $title, $description, $folders, $timeUpdate);

            $rurl = MyTool::getUrl();
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            header('Location: '.$rurl);
            exit();
        } elseif (isset($_POST['delete'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }

            $kf->removeFeed($hash);

            $rurl = MyTool::getUrl();
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            header('Location: '.$rurl);
            exit();
        } elseif (isset($_POST['cancel'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }

            $rurl = MyTool::getUrl();
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            header('Location: '.$rurl);
            exit();
        } else {
            $feed = $kf->getFeed($hash);
            if (!empty($feed)) {
                echo $kfp->htmlPage(
                    strip_tags(
                        MyTool::formatText($kf->kfc->title)
                    ),
                    $kfp->editFeedPage($kf, $feed)
                );
                exit;
            }
        }
        break;
    case 'folder':
        if (isset($_POST['save'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }

            $oldFolder = $kf->getFolder($hash);
            $newFolder = $_POST['foldername'];
            if ($oldFolder != $newFolder) {
                $kf->renameFolder($oldFolder, $newFolder);
            }

            $rurl = MyTool::getUrl();
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            header('Location: '.$rurl);
            exit();
        } elseif (isset($_POST['cancel'])) {
            if (!Session::isToken($_POST['token'])) {
                die('Wrong token.');
            }

            $rurl = MyTool::getUrl();
            if (isset($_POST['returnurl'])) {
                $rurl = $_POST['returnurl'];
            }
            header('Location: '.$rurl);
            exit();
        } else {
            $folder = $kf->getFolder($hash);
            echo $kfp->htmlPage(
                strip_tags(
                    MyTool::formatText($kf->kfc->title)
                ),
                $kfp->editFolderPage($kf, $folder)
            );
            exit;
        }
        break;
    case 'item':
    default:
        break;
    }
    echo $kfp->htmlPage(
        strip_tags(
            MyTool::formatText($kf->kfc->title)
        ),
        $kfp->readerPage($kf)
    );
    exit;
} elseif (isset($_GET['update']) && Session::isLogged()) {
    // Update
    $kf->loadData();
    $hash = substr(trim($_GET['update'], '/'), 0, 6);
    $type = $kf->hashType($hash);
    switch($type) {
    case 'feed':
        $kf->updateChannelItems($hash);
        break;
    case 'folder':
        $feedsHash = $kf->getFeedsHashFromFolderHash($hash);
        foreach ($feedsHash as $feedHash) {
            $kf->updateChannelItems($feedHash);
        }
        break;
    case '':
        $feedsHash = array_keys($kf->getFeeds());
        foreach ($feedsHash as $feedHash) {
            $kf->updateChannelItems($feedHash);
        }
        break;
    case 'item':
    default:
        break;
    }
    $rurl = MyTool::getUrl();
    if (isset($_SERVER['HTTP_REFERER'])) {
        $rurl = $_SERVER['HTTP_REFERER'];
    }
    header('Location: '.$rurl);
    exit();
} elseif (isset($_GET['ajaxlist'])
          || isset($_GET['ajaxupdate'])
          || isset($_GET['ajaxitem'])
          || isset($_GET['ajaxread'])
          || isset($_GET['ajaxkeepunread'])
) {
    // Ajax
    $kf->loadData();

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json; charset=UTF-8');

    if (isset($_GET['ajaxlist'])) {
        $list = $kf->getItems('all', 'new');
        $feedsUpdate = array();
        if (Session::isLogged()) {
            $feedsUpdate = $kf->getFeedsUpdate();
        }
        echo json_encode(
            array(
                'items' => array_keys($list),
                'feeds' => $feedsUpdate,
                'unread' => $kf->getUnread()
            )
        );
        exit;
    } elseif (isset($_GET['ajaxupdate'])) {
        $kf->loadData();
        $hash = substr(trim($_GET['ajaxupdate'], '/'), 0, 6);
        $type = $kf->hashType($hash);
        switch($type) {
        case 'feed':
            $kf->updateChannelItems($hash);
            break;
        case 'folder':
            $feedsHash = $kf->getFeedsHashFromFolderHash($hash);
            foreach ($feedsHash as $feedHash) {
                $kf->updateChannelItems($feedHash);
            }
            break;
        case '':
            $feedsHash = array_keys($kf->getFeeds());
            foreach ($feedsHash as $feedHash) {
                !$kf->updateChannelItems($feedHash);
            }
            break;
        case 'item':
        default:
            break;
        }
        $list = $kf->getItems('all', 'new');
        echo json_encode(array_keys($list));
        exit;
    } elseif (isset($_GET['ajaxitem'])) {
        if (!empty($_GET['ajaxitem'])) {
            $hash = substr(trim($_GET['ajaxitem'], '/'), 0, 6);
        }
        if ($kf->hashType($hash)=='item') {
            $list = $kf->getItems($hash, false);
            echo json_encode($list[$hash]);
            exit;
        }
    } elseif (isset($_GET['ajaxread']) && Session::isLogged()) {
        if (!empty($_GET['ajaxread'])) {
            $hash = substr(trim($_GET['ajaxread'], '/'), 0, 6);
            $kf->mark($hash, 1, true);
            echo json_encode(true);
            exit;
        }
    } elseif (isset($_GET['ajaxkeepunread']) && Session::isLogged()) {
        if (!empty($_GET['ajaxkeepunread'])) {
            $hash = substr(trim($_GET['ajaxkeepunread'], '/'), 0, 6);
            $kf->mark($hash, -1);
            echo json_encode(true);
            exit;
        }
    }
    echo json_encode(false);
    exit;
} elseif (isset($_GET['reader']) || isset($_GET['page'])) {
    // List items : all, folder, feed or entry
    $hash = '';
    if (!empty($_GET['reader'])) {
        $hash = substr(trim($_GET['reader'], '/'), 0, 6);
    }
    $page = 1;
    if (isset($_GET['page']) && !empty($_GET['page'])) {
        $page = (int) $_GET['page'];
        if ($page<=0) {
            $page=1;
        }
    }
    $kf->loadData();
    echo $kfp->htmlPage(
        strip_tags(
            MyTool::formatText($kf->kfc->title)
        ),
        $kfp->readerPage($kf, $hash, $page)
    );
} elseif (isset($_GET['show'])) {
    // show, read article by article as Google reader
    // using 'n' and 'p' with ajax
    $kf->loadData();
    echo $kfp->htmlPage(
        strip_tags(
            MyTool::formatText($kf->kfc->title)
        ),
        $kfp->showPage($kf)
    );
    exit();
} else {
    header('Location: ?'.$kfc->defaultView);
    exit();
}
