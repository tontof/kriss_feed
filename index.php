<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk

define('DATA_DIR', 'data');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('FEED_VERSION', 2);


define('MIN_TIME_UPDATE', 5); // Minimum accepted time for update

define('ERROR_NO_XML', 1);
define('ERROR_ITEMS_MISSED', 2);
define('ERROR_LAST_UPDATE', 3);


class Feed_Conf
{
    private $_file = '';

    public $login = '';

    public $hash = '';

    public $salt = '';

    public $title = "Kriss feed";

    public $redirector = '';

    public $shaarli = '';

    public $byPage = 10;

    public $maxItems = 100;

    public $maxUpdate = 60;

    public $reverseOrder = true;

    public $newItems = true;

    public $expandedView = true;

    public $defaultView = 'show';

    public $public = false;

    public $version;

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
    }

    private function _install()
    {
        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword'])) {
            $this->setSalt(sha1(uniqid('', true).'_'.mt_rand()));
            $this->setLogin($_POST['setlogin']);
            $this->setHash($_POST['setpassword']);

            if (!is_dir(DATA_DIR)) {
                if (!@mkdir(DATA_DIR, 0755)) {
                    echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
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
                    }
                }
            }

            if ($this->write()) {
                echo '
<script>
 alert("Your simple and smart (or stupid) feed reader is now configured.");
 document.location=window.location.href;
</script>';
            } else {
                echo '
<script>
 alert("Error: can not write config and data files.");
 document.location=window.location.href;
</script>';
            }
            Session::logout();
        } else {
            echo '
<h1>Feed reader installation</h1>
<form method="post" action="">
  <p><label>Login: <input type="text" name="setlogin" /></label></p>
  <p><label>Password: <input type="password" name="setpassword" /></label></p>
  <p><input type="submit" value="OK" class="submit" /></p>
</form>';
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
    }

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function setPublic($public)
    {
        $this->public = $public;
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

    public function setRedirector($redirector)
    {
        $this->redirector = $redirector;
    }

    public function setByPage($byPage)
    {
        $this->byPage = $byPage;
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

    public function setNewItems($new)
    {
        $this->newItems = $new;
    }

    public function setExpandedView($expandedView)
    {
        $this->expandedView = $expandedView;
    }

    public function setDefaultView($defaultView)
    {
        if ($defaultView == 'show') {
            $this->defaultView = 'show';
        } elseif ($defaultView == 'reader') {
            $this->defaultView = 'reader';
        }
    }

    public function setReverseOrder($reverseOrder)
    {
        $this->reverseOrder = $reverseOrder;
    }

    public function write()
    {
        $data = array('login', 'hash', 'salt', 'title', 'redirector', 'public',
                      'byPage', 'reverseOrder', 'maxUpdate', 'shaarli',
                      'maxItems', 'newItems', 'expandedView', 'defaultView');
        $out = '<?php';
        $out .= "\n";

        foreach ($data as $key) {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out .= '?>';

        if (!@file_put_contents($this->_file, $out)) {
            return false;
        }

        return true;
    }
}

class Feed_Page
{
    private $_css = <<<CSS
<style>
.admin, .error {
  color: red !important;
  font-size: 1.1em !important;
}

html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}

body {
  font-family: Arial, Helvetica, sans-serif;
  background: #eee;
  color: #000;
}

#config, #edit, #feeds, #import, #show {
  border: 2px solid #999;
  border-top: none;
  background: #fff;
  width:800px;
  margin:auto;
  padding: .2em;
}

#reader {
  background: #fff;
  width:100%;
  height:100%;
}

#list-feeds {
  width: 250px;
  height: 100%;
  float: left;
  overflow: auto;
}

#list-feeds h3 {
  background-color: #ccc;
}

#list-feeds ul {
  font-size: .9em;
  list-style: none;
  margin: 0;
  padding : 0;
}

#container {
  margin-left:250px;
  height: 100%;
  overflow: auto;
}

#extra {
  position:absolute;
  background: #fff;
  border: 1px dotted #999;
  width:15px;
  height:15px;
  overflow: hidden;
}

#extra:hover {
  width:auto;
  height:auto;
}

#title {
  margin: 0;
  color: #666;
  border-bottom: 1px dotted #999;
}

#subtitle {
  text-align: right;
  font-style: italic;
  margin: 0;
  margin-bottom: 1em;
  color: #666;
}

#status {
  margin: 0;
  font-size: 0.7em;
  text-align: center;
  clear:both;
  background: #fff;
  width: 100%;
}


#nav {
  border: 1px dashed #999;
  font-size: .9em;
  color: #666;
  text-align: center;
  background: #fff;
}

.pagination {
  list-style-type: none;
  text-align: center;
  margin: .5em;
}

.pagination li {
  display: inline;
  margin: .3em;
}

.selected {
  font-weight: bold;
  font-size: 1.2em;
}

#article, .article, .comment{
  border: 1px dotted #999;
  padding: .5em;
  margin: 1.5em 0;
  overflow: auto;
}

.title {
  margin: 0;
}

.subtitle {
  text-align: right;
  font-style: italic;
  color: #666;
  border-bottom: 1px dotted #999;
  margin: 0;
  margin-bottom: 1em;
}

.content{
  padding:.5em;
}

.description{
  padding:.5em;
}

.link {
  font-size: .9em;
  float: right;
  border: 1px dotted #999;
  padding: .3em;
}

.read {
  opacity: 0.4;
}

fieldset{
  padding: 1em;
}

legend {
  font-weight: bold;
  margin: 0 .42em;
  padding: 0 .42em;
}

input[type=text], textarea{
  border: 1px solid #000;
  margin: .2em 0;
  padding: .2em;
  font-size: 1em;
  width:100%;
}

button{
  font-size: 1.1em;
}

a:active, a:visited, a:link {
  text-decoration: underline;
  color: #666;
}

a:hover {
  text-decoration: none;
}

ul, ol {
  margin-left:1em;
  margin-bottom:.2em;
  padding-left:0;
}

li {
  margin-bottom:.2em;
}

#plusmenu li {
  display:inline;
}

@media (max-width: 800px) {
body{
  width: 100%;
  height: 100%;
}

#config, #edit, #feeds, #import, #show, #reader, #list-feeds, #container {
  width: auto;
  height: auto;
}

#list-feeds {
  float: none;
}

#container {
  margin-left: 0;
}

img, video, iframe, object{
  max-width:100%;
  height:auto;
}

#plusmenu li {
  display:block;
}
.nomobile{
  display:none;
}
}
</style>
CSS;

    public function __construct($cssFile)
    {
        // We allow the user to have its own stylesheet
        if (file_exists($cssFile)) {
            $this->_css = '<link rel="stylesheet" href="'.$cssFile.'">';
        }
    }

    public function menu($type, $kfc, $hash = '')
    {
        $menu = '
      <p>';
        switch($type) {
        case 'show':
            $menu .= '
        <button onclick="previousItem();">&lt;</button>
        <button onclick="plusMenu();" id="butplusmenu">+</button>
        <button onclick="nextItem();">&gt;</button>
        <ul id="plusmenu" style="display:none">
          <li><a href="?show" title="Show view">Show</a></li>
          <li><a href="?reader" title="Reader view">Reader</a></li>
          <li>
            <a href="#" onclick="shareItem();" title="Share item on shaarli">
              Shaarli
            </a>
          </li>
          <li>
            <a href="#" onclick="forceUpdate();" title="Update manually">
              Update
            </a>
          </li>
          <li>
            <input type="checkbox" name="keepunread" id="keepunread">
            <label for="keepunread">Keep unread</label>
          </li>';
            if (Session::isLogged()) {
                $menu .= '
          <li>
            <a href="?config" class="admin" title="Configuration">
              Configuration
            </a>
          </li>
          <li><a href="?logout" class="admin" title="Logout">Logout</a></li>
        </ul>
';
            } else {
                $menu .= '
          <li><a href="?login">Login</a></li>
';
            }
            break;
        case 'edit':
            $menu .= '
        <a href="?show" title="Show view">Show</a>
      | <a href="?reader" title="Reader view">Reader</a>';
            if (Session::isLogged()) {
                $menu .= '
      | <a href="?config" class="admin" title="Configuration">Configuration</a>
      | <a href="?logout" class="admin" title="Logout">Logout</a>';
            }
            break;
        case 'reader':
            $menu .= '
        <a href="?show" title="Show view">Show</a>
      | <a href="?reader" title="Reader view">Reader</a>';

            if (Session::isLogged()) {
                $sep = '';
                if ($hash != '') {
                    $sep = '=';
                    $menu .= '
      | <a href="?edit'.$sep.$hash.'" class="admin">Edit</a>';
                }
                $menu .= '
      | <a href="?update'.$sep.$hash.'" class="admin" title="Manual update">
          Update
        </a>
      | <a href="?read'.$sep.$hash.'" class="admin" title="Mark as read">
          Read
        </a>
      | <a href="?unread'.$sep.$hash.'" class="admin" title="Mark as unread">
          Unread
        </a>
      | <a href="?config" class="admin" title="Configuration">Configuration</a>
      | <a href="?logout" class="admin" title="Logout">Logout</a>';
            } else {
                $menu .= '
      | <a href="?login">login</a>';
            }
            break;
        case 'config':
            $menu .= '
        <a href="?show" title="Show view">Show</a>
      | <a href="?reader" title="Reader view">Reader</a>';
            if (Session::isLogged()) {
                $menu .= '
      | <a href="?import" class="admin" title="Import OPML file">Import</a>
      | <a href="?export" class="admin" title="Export OPML file">Export</a>
      | <a href="?config" class="admin" title="Configuration">Configuration</a>
      | <a href="?logout" class="admin" title="Logout">Logout</a>';
            } else {
                $menu .= '
      | <a href="?login">login</a>';
            }
            break;
        default:
            break;
        }
        $menu .= '
      </p>';

        return $menu;
    }

    public function status()
    {
        return '<a href="http://github.com/tontof/kriss_feed">KrISS feed'
            . ' ' . FEED_VERSION . '</a><span class="nomobile">'
            . '- A simple and smart (or stupid) feed reader'
            . '</span>. By <a href="http://tontof.net">Tontof</a>';
    }

    public function htmlPage($title, $body)
    {
        return '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=yes" />
    <title>'.$title.'</title>
    '.$this->_css.'
    <link rel="alternate" type="application/rss+xml" title="'
            . $title . ' RSS" href="?rss">
  </head>
  <body>
'.$body.'
  </body>
</html>';
    }

    public function configPage($kfc)
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }
        $menu = $this->menu('config', $kfc);

        return '
    <div id="config">
      <div id="nav">
'.$menu.'
      </div>
      <div id="section">
        <form method="post" action="">
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <fieldset>
            <legend>Feed Reader information</legend>
            <label>- Feed reader title</label><br>
            <input type="text" name="title" value="'
            . htmlspecialchars($kfc->title) . '"><br>
            <label for="publicReader">- Public/private feed reader</label><br>
            <input type="radio" id="publicReader" name="public" value="1" '
            . ($kfc->public? 'checked="checked"' : '')
            . ' /><label for="publicReader">Public kriss feed</label><br>
            <input type="radio" id="privateReader" name="public" value="0" '
            . (!$kfc->public? 'checked="checked"' : '')
            . ' /><label for="privateReader">Private kriss feed</label><br>
            <label for="showView">- Default view</label><br>
            <input type="radio" id="showView" name="defaultView" value="show" '
            . ($kfc->defaultView=='show' ? 'checked="checked"' : '')
            . ' /><label for="showView">Show view</label><br>
            <input type="radio" id="readerView" name="defaultView"'
            . ' value="reader" '
            . ($kfc->defaultView=='reader' ? 'checked="checked"' : '')
            . ' /><label for="readerView">Reader view</label><br>
            <label>- Shaarli url</label><br>
            <input type="text" name="shaarli" value="'
            . htmlspecialchars($kfc->shaarli) . '"><br>
            <label>- Feed reader redirector (only for links,'
            . ' media are not considered)</label><br>
            <input type="text" name="redirector" value="'
            . htmlspecialchars($kfc->redirector) . '"><br>
            <p>(e.g. http://anonym.to/? will mask the HTTP_REFERER)</p>
          </fieldset>
          <fieldset>
            <legend>Feed reader preferences</legend>
            <label>- Number of entries by page</label><br>
            <input type="text" maxlength="3" name="byPage" value="'
            . (int) $kfc->byPage . '"><br>
            <label>- Maximum number of entries by channel</label><br>
            <input type="text" maxlength="3" name="maxItems" value="'
            . (int) $kfc->maxItems . '"><br>
            <label>- Maximum delay between channel update (in minutes)'
            . '</label><br>
            <input type="text" maxlength="4" name="maxUpdate" value="'
            . (int) $kfc->maxUpdate . '"><br>
            <label for="expandedview">- Item view'
            . '(<strong>not implemented yet</strong>)</label><br>
            <input type="radio" id="expandedview" name="expandedView"'
            . ' value="1" ' . ($kfc->expandedView ? 'checked="checked"' : '')
            . ' /><label for="expandedview">Expanded view</label><br>
            <input type="radio" id="listview" name="expandedView" value="0" '
            . (!$kfc->expandedView ? 'checked="checked"' : '')
            . ' /><label for="listview">List view</label><br>
            <label for="newitems">- Items selection</label><br>
            <input type="radio" id="newitems" name="newItems" value="1" '
            . ($kfc->newItems ? 'checked="checked"' : '')
            . ' /><label for="newitems">New items</label><br>
            <input type="radio" id="readitems" name="newItems" value="0" '
            . (!$kfc->newItems ? 'checked="checked"' : '')
            . ' /><label for="readitems">All items</label><br>
            <label for="reverseorder">- Order of entries</label><br>
            <input type="radio" id="normalorder" name="reverseOrder" value="0" '
            . (!$kfc->reverseOrder ? 'checked="checked"' : '')
            . ' /><label for="normalorder">From the latest to the newest'
            . '</label><br>
            <input type="radio" id="reverseorder" name="reverseOrder"'
            . ' value="1" ' . ($kfc->reverseOrder ? 'checked="checked"' : '')
            . ' /><label for="reverseorder"><strong>Reverse order:</strong>'
            . ' from the newest to the latest</label><br>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form><br>
      </div>
    </div>';
    }

    public function editFolderPage($kf, $folder)
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }

        $menu = $this->menu('edit', $kf->kfc);

        return '
    <div id="edit">
      <div id="nav">
'.$menu.'
      </div>
      <div id="section">
        <div class="article">
        <form method="post" action="">
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <fieldset>
            <label>- Folder name (leave empty to delete)</label><br>
            <input type="text" name="foldername" value="'
            . htmlspecialchars($folder) . '"><br>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form>
        </div>
      </div>
    </div>';
    }

    public function editFeedPage($kf, $feed)
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }

        $menu = $this->menu('edit', $kf->kfc);
        $lastUpdate = 'need update';
        if (!$kf->needUpdate($feed)) {
            $diff = (int) (time() - $feed['lastUpdate']);
            $lastUpdate =
                (int) ($diff / 60) . ' m ' . (int) ($diff % 60) . ' s';
        }

        $folders = $kf->getFolders();
        $inputFolders = '';
        foreach ($folders as $hash => $folder) {
            $checked = '';
            if (in_array($folder, $feed['folders'])) {
                $checked = ' checked="checked"';
            }
            $inputFolders .= '<input type="checkbox" name="folders[]"'
                . $checked . ' value="' . $hash . '"><label>- '
                . htmlspecialchars($folder) . '</label><br>';
        }
        $inputFolders .= '<input type="text" name="newfolder" value=""'
            . ' placeholder="New folder"><br>';


        return '
    <div id="edit">
      <div id="nav">
' . $menu . '
      </div>
      <div id="section">
        <form method="post" action="">
          <input type="hidden" name="returnurl" value="' . $ref . '" />
          <input type="hidden" name="token" value="' . Session::getToken() . '">
          <fieldset>
            <legend>Feed main information</legend>
            <label>- Feed title</label><br>
            <input type="text" name="title" value="'
            . htmlspecialchars($feed['title']) . '"><br>
            <label>- Feed XML url (<em>read only</em>)</label><br>
            <input type="text" readonly="readonly" name="xmlUrl" value="'
            . htmlspecialchars($feed['xmlUrl']) . '"><br>
            <label>- Feed main url (<em>read only</em>)</label><br>
            <input type="text" readonly="readonly" name="htmlUrl" value="'
            . htmlspecialchars($feed['htmlUrl']) . '"><br>
            <label>- Feed description</label><br>
            <input type="text" name="description" value="'
            . htmlspecialchars($feed['description']) . '"><br>
          </fieldset>
          <fieldset>
            <legend>Feed folders</legend>' . $inputFolders . '
          </fieldset>
          <fieldset>
            <legend>Feed preferences</legend>
            <label>- Time update (\'auto\', \'max\' or a number of minutes'
            . ' less than \'max\' define in <a href="?config">config</a>)'
            . '</label><br>
            <input type="text" name="timeUpdate" value="'
            . $feed['timeUpdate'] . ' ' . $kf->getTimeUpdate($feed) . '"><br>
            <label>- Last update (<em>read only</em>)</label><br>
            <input type="text" name="lastUpdate" value="'
            . $lastUpdate . '"><br>
            <input type="submit" name="save" value="Save" />
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="delete" value="Delete"/>
          </fieldset>
        </form><br>
      </div>
    </div>';
    }


    public function listFeeds($kf)
    {
        $str = '';
        $feeds = $kf->getFeeds();
        $folders = $kf->getFolders();
        $unread = $kf->getUnread('');
        if ($unread == 0) {
            $unread = '';
        } else {
            $unread = ' ('.$unread.')';
        }
        $str .= '
          <ul>
          <li><h3 class="title">'
            . '<button onclick="toggle(\'all-subscriptions\', this)">-</button>'
            .' <a href="?reader">Subscriptions</a>'.$unread.'</h3>
            <ul id="all-subscriptions">';
        foreach ($feeds as $hashUrl => $arrayInfo) {
            if (empty($arrayInfo['folders'])) {
                $unread = $kf->getUnread($hashUrl);
                if ($unread == 0) {
                    $unread = '';
                } else {
                    $unread = ' ('.$unread.')';
                }
                $atitle = trim(htmlspecialchars($arrayInfo['description']));
                if (empty($atitle)) {
                    $atitle = trim(htmlspecialchars($arrayInfo['title']));
                }
                $str .= '
            <li> - <strong><a href="?reader=' . $hashUrl . '"'
                    . (isset($arrayInfo['error'])
                       ?' class="error" title="'
                       . $kf->getError($arrayInfo['error']).'"'
                       :'')
                    . '>' . htmlspecialchars($arrayInfo['title'])
                    . '</a>' . $unread . '</strong></li>';
            }
        }
        foreach ($folders as $hashFold => $folder) {
            $unread = $kf->getUnread($hashFold);
            if ($unread == 0) {
                $unread = '';
            } else {
                $unread = ' ('.$unread.')';
            }
            $str .= '
            <li><h3 class="title"><button onclick="toggle(\'folder-'
                . $hashFold . '\', this)">-</button> <a href="?reader='
                . $hashFold . '">' . htmlspecialchars($folder) . '</a>'
                . $unread . '</h3>
              <ul id="folder-' . $hashFold . '">';
            foreach ($feeds as $hashUrl => $arrayInfo) {
                if (in_array($folder, $arrayInfo['folders'])) {
                    $unread = $kf->getUnread($hashUrl);
                    if ($unread == 0) {
                        $unread = '';
                    } else {
                        $unread = ' ('.$unread.')';
                    }
                    $atitle = trim(htmlspecialchars($arrayInfo['description']));
                    if (empty($atitle) || $atitle == ' ') {
                        $atitle = trim(htmlspecialchars($arrayInfo['title']));
                    }
                    $str .= '
              <li> - <strong><a href="?reader=' . $hashUrl . '"'
                        . (isset($arrayInfo['error'])
                           ?' class="error" title="'
                           . $kf->getError($arrayInfo['error']) . '"'
                           :'')
                        . '>' . htmlspecialchars($arrayInfo['title'])
                        . '</a>' . $unread . '</strong></li>';
                }
            }
            $str .= '
              </ul>
            </li>';
        }
        $str .= '
            </ul>
          </ul>';

        return $str;
    }


    public function readerPage($kf, $hash = '', $page = 1)
    {
        $list = $kf->getItems($hash);
        $type = $kf->hashType($hash);

        // create menu
        $menu = $this->menu('reader', $kf->kfc, $hash);

        // create pagination
        $pagination = '';

        $begin = ($page - 1) * $kf->kfc->byPage;
        $pages = (count($list) <= $kf->kfc->byPage)
            ?''
            :ceil(count($list) / $kf->kfc->byPage);

        if (!empty($pages)) {
            $previousPage = $page-1;
            $nextPage = $page+1;
            if ($previousPage < 1) {
                $previousPage = 1;
            }
            if ($nextPage > $pages) {
                $nextPage = $pages;
            }
            $pagination .= '
        <p class="pagination">
        ';
            $reader='reader';
            if (!empty($hash)) {
                $reader='reader='.$hash;
            }
            $pagination .= '
          <a href="?'.$reader.'&page='.$previousPage.'">previous</a>
          ';

            for ($p = 1; $p <= $pages; $p++) {
                $pagination .= ' <a href="?' . $reader . '&page=' . $p . '" '
                    . ($page == $p
                       ? ' class="selected"'
                       : '')
                    . '>' . $p . '</a> ';
            }

            $pagination .= '
          <a href="?' . $reader . '&page=' . $nextPage . '">next</a>
        </p>';
        }

        // create status
        $status = $this->status();

        // select items on the page
        $list = array_slice($list, $begin, $kf->kfc->byPage, true);
        $listItems = '';
        if (empty($list)) {
            $listItems .= '
        <div class="article"><p>No item.</p></div>';
        } else {
            $i=0;
            foreach ($list as $itemHash => $item) {
                $read = '';
                $markAs = '';
                if ($item['read']==1) {
                    $read = ' read';
                }
                if (Session::isLogged()) {
                    $markAs = ' (<a href="?read='
                        . $itemHash . '">Mark as read</a>)';
                    if ($item['read']==1) {
                        $markAs = ' (<a href="?unread='
                            . $itemHash . '">Mark as unread</a>)';
                    }
                }

                $listItems .= '
        <div class="article'.$read.'">
          <h3 class="title"><a href="'
                    . $kf->kfc->redirector . htmlspecialchars($item['link'])
                    . '">'
                    . htmlspecialchars(
                        html_entity_decode(
                            $item['title'],
                            ENT_QUOTES, 'UTF-8'
                        )
                    )
                    . '</a>' . $markAs . '</h3>
          <h4 class="subtitle">from <a href="'
                    . $kf->kfc->redirector.htmlspecialchars($item['xmlUrl'])
                    . '">' . htmlspecialchars($item['author']) . '</a></h4>
          <div class="content">
           '
                    . preg_replace(
                        '/<a(.*?)href=["\'](.*?)["\'](.*?)>/',
                        '<a$1href="' . $kf->kfc->redirector . '$2"$3>',
                        preg_replace(
                            '/<script\b[^>]*>(.*?)<\/script>/is',
                            "",
                            $item['content']
                        )
                    )
                    .'
          </div>
        </div>';
                $i++;
            }
        }

        // list of feeds
        $listFeeds = $this->listFeeds($kf);

        $addNewFeed = '';
        if (Session::isLogged()) {
            $addNewFeed .= '
        <div class="article">
          <form action="?" method="get">
           <input type="hidden" name="token" value="'.Session::getToken().'">
           <label for="newfeed">- New feed</label>
           <input type="submit" name="add" value="Subscribe">
           <input type="text" name="newfeed" id="newfeed">
          </form>
        </div>';
        }

        // ajaxscript
        $ajaxScript = $this->ajaxScript($kf);

        return <<<HTML
    <div id="reader">
      <div id="list-feeds">
$addNewFeed
$listFeeds
      </div>
      <div id="container">
        <div id="status">
$status
        </div>
        <div id="nav">
$menu
        </div>
$pagination
        <div id="section">
$listItems
        </div>
$pagination
      </div>
    </div>
$ajaxScript
HTML;
    }

    public function showPage($kf)
    {
        $menu = $this->menu('show', $kf->kfc);
        $status = $this->status();
        $ajaxscript = $this->ajaxScript($kf);

        return <<<HTML
    <div id="show">
      <div id="nav">
$menu
      </div>
      <div id="section">
        <div id="article">
          <h3 id="title"></h3>
          <h4 id="subtitle"></h4>
          <div id="content"></div>
        </div>
      </div>
      <div id="status">
$status
      </div>
    </div>
$ajaxscript
HTML;
    }

    public function ajaxScript($kf)
    {
        $redir = $kf->kfc->redirector;
        $shaarli = $kf->kfc->shaarli;

        return <<<JS
<script>
var redirector = '$redir',
    title = '',
    listFeedsHash = []
    listItemsHash = [],
    itemsUnread = 0,
    currentItemInd = 0,
    previous = '',
    current = '',
    next = '',
    working = false,
    workingsave = '',
    currentFeedInd = 0,
    updatingFeed = false,
    updateTimer = null,
    status = '',
    shaarli = '$shaarli';
if (typeof XMLHttpRequest == "undefined") {
  XMLHttpRequest = function () {
    try { return new ActiveXObject("Msxml2.XMLHTTP.6.0"); }
      catch (e) {}
    try { return new ActiveXObject("Msxml2.XMLHTTP.3.0"); }
      catch (e) {}
    try { return new ActiveXObject("Microsoft.XMLHTTP"); }
      catch (e) {}
    //Microsoft.XMLHTTP points to Msxml2.XMLHTTP and is redundant
    throw new Error("This browser does not support XMLHttpRequest.");
  };
}

Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function removeAllChild(el) {
    while (el.firstChild) {
        el.removeChild(el.firstChild);
    }
}

// http://stackoverflow.com/questions/1912501
function htmlDecode(input) {
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes.length === 0 ? "" : e.firstChild.nodeValue;
}

// http://papermashup.com/read-url-get-variables-withjavascript/
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(
        /[?&]+([^=&]+)=([^&]*)/gi,
        function(m, key, value) {
            vars[key] = value;
        }
    );
    return vars;
}

function changePage(direction) {
    var currentPage = getUrlVars()['page'];
    var currentHash = getUrlVars()['reader'];

    if (typeof(currentPage) == 'undefined' || parseInt(currentPage) <= 0) {
        currentPage = 1;
    }
    var nextPage = parseInt(currentPage) + direction;
    if (nextPage == 0) {
        nextPage = 1;
    }

    if (typeof(currentHash) == 'undefined') {
        window.location.href = '?reader&page='+nextPage;
    } else {
        window.location.href = '?reader='+currentHash+'&page='+nextPage;
    }
}

function nextPage() {
    changePage(1);
}

function previousPage() {
    changePage(-1);
}

function unloadItem() {
    var title = document.getElementById('title');
    var subtitle = document.getElementById('subtitle');
    var content = document.getElementById('content');

    removeAllChild(title);
    removeAllChild(subtitle);
    removeAllChild(content);
}

function shareItem() {
    if (shaarli != '') {
        var url = current['link'];
        var title = current['title'];
        var opt = 'menubar=no, height=390, width=600, toolbar=no,'
                + ' scrollbars=no, status=no';
        window.open(
            shaarli + '/index.php?post=' + encodeURIComponent(url)
            + '&title=' + encodeURIComponent(title)
            + '&source=bookmarklet',
            '_blank',
            opt
        );
    } else {
        alert('please configure your shaarli on config page !');
    }
}

function setTitle() {
    if (title === '') {
        title = document.title
    }
    document.title = title + ' ('+itemsUnread+')';

    var but = document.getElementById('butplusmenu');
    but.textContent = itemsUnread+' item(s)';

}

function anonymize(el) {
    var a_to_anon = el.getElementsByTagName("a");
    for (var i = 0; i < a_to_anon.length; i++) {
        a_to_anon[i].href = redirector+a_to_anon[i].href;
    }
}

function loadItem(item) {
    unloadItem();
    if (item !== '') {
        var article = document.getElementById('article');
        if (item['read']==1) {
            article.setAttribute('class', 'read');
        } else {
            article.setAttribute('class', '');
        }
        if (document.getElementById('keepunread') != 'null') {
            if (item['read']==-1) {
                document.getElementById('keepunread').checked = true;
            } else {
                document.getElementById('keepunread').checked = false;
            }
        }
        var title = document.getElementById('title');
        var subtitle = document.getElementById('subtitle');
        var content = document.getElementById('content');

        var link = document.createElement('a');
        link.href = htmlDecode(item['link']);
        link.title = htmlDecode(item['title']);
        link.textContent = htmlDecode(item['title']);

        title.appendChild(link);
        subtitle.textContent = 'from ';

        var author = document.createElement('a');
        author.href = htmlDecode(item['xmlUrl']);
        author.title = htmlDecode(item['author']);
        author.textContent = htmlDecode(item['author']);
        subtitle.appendChild(author);

        content.innerHTML = item['content'];
        anonymize(article);
    }
}

function nextItem() {
    if (current == '') {
        getCurrentItem();
    }
    if (!working) {
        markAsRead();
        if (next != '') {
            working=true;
            previous = current;
            current = next;
            currentItemInd++;
            next = '';
        }
        loadItem(current);
        getNextItem();
    } else {
        workingsave = 'next';
    }
}

function previousItem() {
    if (!working) {
        if (previous != '') {
            working=true;
            next = current;
            current = previous;
            currentItemInd--;
            previous = '';
            loadItem(current);
            getPreviousItem();
        }
    } else {
        workingsave = 'previous';
    }
}

function plusMenu() {;
    var pm = document.getElementById('plusmenu');
    var but = document.getElementById('butplusmenu');
    if (pm.getAttribute('style') == 'display:none') {
        pm.setAttribute('style', 'display:block;list-style:none;');
    } else {
        pm.setAttribute('style', 'display:none');
    }
}


function markAsRead() {
    keepunread = false;
    if (document.getElementById('keepunread')!= null) {
        if (document.getElementById('keepunread').checked) {
            keepunread = true;
        }
    }
    if (current!='' && current['read']!==1 && !keepunread) {
        var xhr = new XMLHttpRequest();
        var hash = listItemsHash[currentItemInd];
        var url = '?ajaxread='+hash;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    var res = JSON.parse(xhr.responseText);
                    if (res) {
                        itemsUnread--;
                        setTitle();
                    }
                }
            }
        };
        xhr.send(null);
        current['read']=1;
    } else {
        if (keepunread) {
            if (current['read']===1) {
                var xhr = new XMLHttpRequest();
                var hash = listItemsHash[currentItemInd];
                var url = '?ajaxkeepunread='+hash;
                xhr.open('GET', url, true);
                xhr.onreadystatechange = function() {
                    // Everything OK
                    if (xhr.readyState == 4) {
                        if (xhr.status == 200) {
                            var res = JSON.parse(xhr.responseText);
                            if (res) {
                                itemsUnread++;
                                setTitle();
                            }
                        }
                    }
                };
                xhr.send(null);
                current['read']=-1;
            } else if (current['read']===0) {
                var xhr = new XMLHttpRequest();
                var hash = listItemsHash[currentItemInd];
                var url = '?ajaxkeepunread='+hash;
                xhr.open('GET', url, true);
                xhr.send(null);
                current['read']=-1;
            }
        }
    }
}

function finishWorking() {
    working = false;
    switch(workingsave) {
    case 'next':
        workingsave = '';
        nextItem();
        break;
    case 'previous':
        workingsave = '';
        previousItem();
        break;
    case '':
    default:
        break;
    }
}

function getPreviousItem() {
    if (currentItemInd > 0) {
        var xhr        = new XMLHttpRequest(),
            hash       = listItemsHash[currentItemInd-1],
            url        = '?ajaxitem='+hash;

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    previous = JSON.parse(xhr.responseText);
                }
                finishWorking();
            }
        }
        xhr.send(null);
    } else {
        finishWorking();
    }
}

function getNextItem() {
    if (currentItemInd < listItemsHash.length-1) {
        var xhr = new XMLHttpRequest();
        var hash = listItemsHash[currentItemInd+1];
        var url = '?ajaxitem='+hash;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    next = JSON.parse(xhr.responseText);
                    if (current['read']==1 && next['read']!=1) {
                        workingsave = 'next';
                    }
                }
                finishWorking();
            }
        }
        xhr.send(null);
    } else {
        finishWorking();
    }
}

function getCurrentItem() {
    if (currentItemInd < listItemsHash.length) {
        var xhr = new XMLHttpRequest();
        var hash = listItemsHash[currentItemInd];
        var url = '?ajaxitem='+hash;
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4 && xhr.status == 200) {
                current = JSON.parse(xhr.responseText);
                loadItem(current);
            }
        }
        xhr.send(null);
    }
}

function addItems(res) {
    for (var i = 0, len = res.length; i < len; i++) {
        if (listItemsHash.indexOf(res[i]) == -1) {
            listItemsHash.push(res[i]);
            itemsUnread++;
        }
    }
    setTitle();
}

function forceUpdate(i) {
    if (typeof(i) == "undefined") {
        i = 0;
    }
    if (i < listFeedsHash.length) {
        setStatus("updating : "+listFeedsHash[i][2]);
        var xhr     = new XMLHttpRequest(),
            timeout = null,
            aborted = false
            url     = '?ajaxupdate='+listFeedsHash[i][1];

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                clearTimeout(timeout);
                if (aborted) {
                    listFeedsHash[i][3] =
                        Math.round((new Date().getTime())/1000);
                    setTimeout(forceUpdate, 1000, i+1);
                } else {
                    if (xhr.status == 200) {
                        res = JSON.parse(xhr.responseText);
                        addItems(res);
                        listFeedsHash[i][3] =
                        Math.round((new Date().getTime())/1000);
                        forceUpdate(i+1);
                    } else if (xhr.status == 500) {

                    }
                }
            }
        }
        xhr.send(null);
        timeout = setTimeout(function() {
            aborted = true;
            xhr.abort();
          }, 2000);
    } else {
        setStatus(status);
    }
}

function update(feed) {
    var xhr = new XMLHttpRequest();
    var url = '?ajaxupdate='+feed;
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        // Everything OK
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
                res = JSON.parse(xhr.responseText);
                addItems(res);
            }
            listFeedsHash[currentFeedInd][3] =
            Math.round((new Date().getTime())/1000);
            updatingFeed = false;
            currentFeedInd++;
        }
    }
    xhr.send(null);
}

function updateCurrentFeed() {
    if (!updatingFeed) {
        updatingFeed = true;
        if (currentFeedInd >= listFeedsHash.length) {
            updatingFeed = false;
            currentFeedInd = 0;
            window.clearInterval(updateTimer);
            setStatus(status);
        } else {
            if ((Math.round( ( new Date().getTime() ) / 1000 )
                 - listFeedsHash[currentFeedInd][3])
                / 60
                >
                listFeedsHash[currentFeedInd][4]
            ) {
                setStatus("updating : "+listFeedsHash[currentFeedInd][2]);
                update(listFeedsHash[currentFeedInd][1]);
            } else {
                updatingFeed = false;
                currentFeedInd++;
            }
        }
    }
}

function updateFeeds() {
    if (!updatingFeed) {
        currentFeedInd = 0;
        updateTimer = window.setInterval(updateCurrentFeed, 200);
    }
}

function setStatus(text) {
    document.getElementById('status').innerHTML = text;
}

function initAjax() {
    show = document.getElementById('show');
    if (show) {
        status = document.getElementById('status').innerHTML;
        var xhr = new XMLHttpRequest();
        var url = '?ajaxlist';
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4 && xhr.status == 200) {
                res = JSON.parse(xhr.responseText);
                listItemsHash = res.items;
                listFeedsHash = res.feeds;
                itemsUnread = res.unread;
                if (listItemsHash.length>0) {
                    getCurrentItem();
                }
                if (listItemsHash.length>1) {
                    getNextItem();
                }
                setTitle();
                updateFeeds();
                window.setInterval(updateFeeds, 60000);
            }
        }
        xhr.send(null);
    }
}

function toggle(id, el) {
  var e = document.getElementById(id);

  if (e.style.display == '') {
      e.style.display = 'none';
      el.innerHTML = '+';
  } else {
      e.style.display = '';
      el.innerHTML = '-';
  }
}

function checkKey(e) {
    if (!e.ctrlKey && !e.altKey && !e.shiftKey) {
        var code;
        if (!e) var e = window.event;
        if (e.keyCode) code = e.keyCode;
        else if (e.which) code = e.which;
        switch(code) {
        case 39: // right arrow
        case 110: // 'n'
        case 78: // 'N'
        case 106: // 'j'
        case 74: // 'J'
            if (document.getElementById('show')!=null) {
                nextItem();
            } else if (document.getElementById('reader')!=null) {
                nextPage();
            }
            break;
        case 37: // left arrow
        case 112: // 'p'
        case 80 : // 'P'
        case 107: // 'k'
        case 75: // 'K'
            if (document.getElementById('show')!=null) {
                previousItem();
            } else if (document.getElementById('reader')!=null) {
                previousPage();
            }
            break;
        case 115: // 's'
        case 83: // 'S'
            if (document.getElementById('show')!=null) {
                shareItem();
            }
        default:
            break;
        }
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

//http://scottandrew.com/weblog/articles/cbs-events
function addEvent(obj, evType, fn, useCapture) {
    if (obj.addEventListener) {
        obj.addEventListener(evType, fn, useCapture);
        return true;
    } else if (obj.attachEvent) {
        var r = obj.attachEvent("on"+evType, fn);
        return r;
    } else {
        alert("Handler could not be attached");
    }
}
function removeEvent(obj, evType, fn, useCapture) {
    if (obj.removeEventListener) {
        obj.removeEventListener(evType, fn, useCapture);
        return true;
    } else if (obj.detachEvent) {
        var r = obj.detachEvent("on"+evType, fn);
        return r;
    } else {
        alert("Handler could not be removed");
    }
}

// when document is loaded animated images
if (document.getElementById && document.createTextNode) {
    addEvent(window, 'load', initAjax);
    addEvent(window, 'keypress', checkKey);
    addEvent(window, 'touchstart', checkMove);
}
</script>
JS;
    }

    public function importPage()
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }

        return '
    <div id="import">
      <div id="section">
        <form method="post" action="?import" enctype="multipart/form-data">
          Import Opml file (as exported by Google Reader, Tiny Tiny RSS,'
            . ' RSS lounge...) (Max: '
            . MyTool::humanBytes(MyTool::getMaxFileSize()) . ')
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <input type="hidden" name="token" value="' . Session::getToken() . '">
          <input type="file" name="filetoupload" size="80">
          <input type="hidden" name="MAX_FILE_SIZE" value="'
            . MyTool::getMaxFileSize() . '">
          <input type="checkbox" name="overwrite" id="overwrite">'
            . '<label for="overwrite">Overwrite existing links</label>
          <input type="submit" name="import" value="Import"><br>
        </form>
      </div>
    </div>';
    }
}

class Feed
{
    public $file = '';

    public $kfc;

    private $_data = array();

    public function __construct($dataFile, $kfc)
    {
        $this->kfc = $kfc;
        $this->file = $dataFile;
    }

    public function importOpml()
    {
        $filename  = $_FILES['filetoupload']['name'];
        $filesize  = $_FILES['filetoupload']['size'];
        $data      = file_get_contents($_FILES['filetoupload']['tmp_name']);
        $overwrite = isset($_POST['overwrite']);

        $opml = new DOMDocument('1.0', 'UTF-8');

        $importCount=0;
        if ($opml->loadXML($data)) {
            $body = $opml->getElementsByTagName('body');
            $xmlArray = $this->getArrayFromXml($body->item(0));
            $array = $this->convertOpmlArray($xmlArray['outline']);

            $this->loadData();
            foreach ($array as $hashUrl => $arrayInfo) {
                $title = '';
                if (isset($arrayInfo['title'])) {
                    $title = $arrayInfo['title'];
                } else if (isset($arrayInfo['text'])) {
                    $title = $arrayInfo['text'];
                }
                $folders = array();
                if (isset($arrayInfo['folders'])) {
                    foreach ($arrayInfo['folders'] as $folder) {
                        $folders[] = html_entity_decode(
                            $folder,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    }
                }
                $timeupdate = 'Auto';
                $lastupdate = 0;
                $items = array();
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
                    $current = array(
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
                        'folders' => $folders,
                        'timeUpdate' => $timeUpdate,
                        'lastUpdate' => $lastUpdate,
                        'items' => $items);

                    if ($overwrite || !isset($this->_data[$hashUrl])) {
                        $this->_data[$hashUrl] = $current;
                        $importCount++;
                    }
                }
            }
            $this->writeData();

            echo '<script>alert("File '
                . $filename . ' (' . MyTool::humanBytes($filesize)
                . ') was successfully processed: ' . $importCount
                . ' links imported.");document.location=\'?\';</script>';
        } else {
            echo '<script>alert("File ' . $filename . ' ('
                . MyTool::humanBytes($filesize) . ') has an unknown'
                . ' file format. Nothing was imported.");'
                . 'document.location=\'?\';</script>';
            exit;
        }
    }

    public function exportOpml()
    {
        $withoutFolder = array();
        $withFolder = array();
        $folders = array_values($this->getFolders());

        // get a new representation of data using folders as key
        foreach ($this->_data as $hashUrl => $arrayInfo) {
            if (empty($arrayInfo['folders'])) {
                $withoutFolder[] = $hashUrl;
            } else {
                foreach ($arrayInfo['folders'] as $folder) {
                    $withFolder[$folder][] = $hashUrl;
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
                $this->_data[$hashUrl]['title']
            );
            $outline->appendChild($outlineTitle);
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value
                = htmlspecialchars($this->_data[$hashUrl]['title']);
            $outline->appendChild($outlineText);
            if (!empty($this->_data[$hashUrl]['description'])) {
                $outlineDescription
                    = $opmlData->createAttribute('description');
                $outlineDescription->value
                    = htmlspecialchars($this->_data[$hashUrl]['description']);
                $outline->appendChild($outlineDescription);
            }
            $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
            $outlineXmlUrl->value
                = htmlspecialchars($this->_data[$hashUrl]['xmlUrl']);
            $outline->appendChild($outlineXmlUrl);
            $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
            $outlineHtmlUrl->value = htmlspecialchars(
                $this->_data[$hashUrl]['htmlUrl']
            );
            $outline->appendChild($outlineHtmlUrl);
            $body->appendChild($outline);
        }

        // with folder outline node
        foreach ($withFolder as $folder => $arrayHashUrl) {
            $outline = $opmlData->createElement('outline');
            $outlineTitle = $opmlData->createAttribute('title');
            $outlineTitle->value = htmlspecialchars($folder);
            $outline->appendChild($outlineTitle);
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value = htmlspecialchars($folder);
            $outline->appendChild($outlineText);

            foreach ($arrayHashUrl as $hashUrl) {
                $outlineKF = $opmlData->createElement('outline');
                $outlineTitle = $opmlData->createAttribute('title');
                $outlineTitle->value
                    = htmlspecialchars($this->_data[$hashUrl]['title']);
                $outlineKF->appendChild($outlineTitle);
                $outlineText = $opmlData->createAttribute('text');
                $outlineText->value
                    = htmlspecialchars($this->_data[$hashUrl]['title']);
                $outlineKF->appendChild($outlineText);
                if (!empty($this->_data[$hashUrl]['description'])) {
                    $outlineDescription
                        = $opmlData->createAttribute('description');
                    $outlineDescription->value = htmlspecialchars(
                        $this->_data[$hashUrl]['description']
                    );
                    $outlineKF->appendChild($outlineDescription);
                }
                $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
                $outlineXmlUrl->value
                    = htmlspecialchars($this->_data[$hashUrl]['xmlUrl']);
                $outlineKF->appendChild($outlineXmlUrl);
                $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
                $outlineHtmlUrl->value
                    = htmlspecialchars($this->_data[$hashUrl]['htmlUrl']);
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

    public function getArrayFromXml($node)
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
                            = $this->getArrayFromXml($childNode);
                    }
                }
            }
        }

        return $array;
    }

    public function convertOpmlArray($array, $listFolders = array())
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
                    $this->convertOpmlArray(
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

    public function renameFolder($oldFolder, $newFolder)
    {
        $k = 0;
        foreach ($this->_data as $feedHash => $feed) {
            $i = array_search($oldFolder, $feed['folders']);
            if ($i !== false) {
                unset($this->_data[$feedHash]['folders'][$i]);
                if (!empty($newFolder)) {
                    $this->_data[$feedHash]['folders'][] = $newFolder;
                }
            }
        }
        $this->writeData();
    }

    public function getFolders()
    {
        $folders = array();
        foreach ($this->_data as $xmlUrl => $arrayInfo) {
            foreach ($this->_data[$xmlUrl]['folders'] as $folder) {
                if (!in_array($folder, $folders)) {
                    $folders[MyTool::smallHash($folder)] = $folder;
                }
            }
        }

        return $folders;
    }

    public function getFolder($hash)
    {
        $folders = $this->getFolders();
        if (isset($folders[$hash])) {
            return $folders[$hash];
        }

        return false;
    }

    public function getFeeds()
    {
        return $this->_data;
    }

    public function getFeed($hash)
    {
        if (isset($this->_data[$hash])) {
            return $this->_data[$hash];
        }

        return false;
    }

    public function removeFeed($feedHash)
    {
        if (isset($this->_data[$feedHash])) {
            unset($this->_data[$feedHash]);
            $this->writeData();
        }
    }

    public function editFeed(
        $feedHash,
        $title,
        $description,
        $folders,
        $timeUpdate)
    {
        if (isset($this->_data[$feedHash])) {
            if (!empty($title)) {
                $this->_data[$feedHash]['title'] = $title;
            }
            if (!empty($description)) {
                $this->_data[$feedHash]['description'] = $description;
            }
            unset($this->_data[$feedHash]['folders']);
            $this->_data[$feedHash]['folders'] = array();
            if (!empty($folders)) {
                foreach ($folders as $folder) {
                    $this->_data[$feedHash]['folders'][] = $folder;
                }
            }
            $this->_data[$feedHash]['timeUpdate'] = 'auto';
            if (!empty($timeUpdate)) {
                if ($timeUpdate == 'max') {
                    $this->_data[$feedHash]['timeUpdate'] = $timeUpdate;
                } else {
                    $this->_data[$feedHash]['timeUpdate'] = (int) $timeUpdate;
                    $maxUpdate = $this->kfc->maxUpdate;
                    if ($this->_data[$feedHash]['timeUpdate'] < MIN_TIME_UPDATE
                        || $this->_data[$feedHash]['timeUpdate'] > $maxUpdate
                    ) {
                        $this->_data[$feedHash]['timeUpdate'] = 'auto';
                    }
                }
            }

            $this->writeData();
        }
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

        return $this->formatChannel($channel);
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
                    }
                    if ($tag->length != 0) {
                        // we find a correspondence for the current format
                        // select first item (item(0)), (may not work)
                        // stop to search for another one
                        if ($format == 'link') {
                            $tmpItem[$format]
                                = $tag->item(0)->getAttribute('href');
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
                $newItems[$hashUrl]['link'] = $tmpItem['link'];
                $newItems[$hashUrl]['author'] = $tmpItem['author'];
                $newItems[$hashUrl]['description'] = substr(
                    strip_tags($tmpItem['description']), 0, 500
                ) . "...";
                $newItems[$hashUrl]['content'] = $tmpItem['content'];
                $newItems[$hashUrl]['read'] = 0;
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
            'link'        => array('feedburner:origLink', 'link', 'guid', 'id'),
            'time'        => array('pubDate', 'updated', 'lastBuildDate',
                                   'published', 'dc:date', 'date'),
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

    public function loadXml($xmlUrl)
    {
        // set user agent
        // http://php.net/manual/en/function.libxml-set-streams-context.php
        $opts = array(
            'http' => array(
                'user_agent' => 'kriss feed agent',
                )
            );

        $context = stream_context_create($opts);
        libxml_set_streams_context($context);

        // request a file through HTTP
        return @DOMDocument::load($xmlUrl);
    }

    public function addChannel($xmlUrl)
    {
        $feedHash = MyTool::smallHash($xmlUrl);
        if (!isset($this->_data[$feedHash])) {
            $xml = $this->loadXml($xmlUrl);

            if (!$xml) {
                return false;
            } else {
                $channel = $this->getChannelFromXml($xml);
                $items = $this->getItemsFromXml($xml);
                foreach (array_keys($items) as $itemHash) {
                    if (empty($items[$itemHash]['author'])) {
                        $items[$itemHash]['author'] = $channel['title'];
                    } else {
                        $items[$itemHash]['author']
                            = $channel['title'] . ' ('
                            . $items[$itemHash]['author'] . ')';
                    }
                    $items[$itemHash]['xmlUrl'] = $xmlUrl;
                }

                $channel['xmlUrl'] = $xmlUrl;
                $channel['folders'] = array();
                $channel['timeUpdate'] = 'auto';
                $channel['lastUpdate'] = time();
                $channel['items'] = $items;

                $this->_data[$feedHash] = $channel;

                $this->writeData();

                return true;
            }
        }

        return false;
    }

    public function getFeedsUpdate()
    {
        $list = array();
        foreach (array_keys($this->_data) as $feedHash) {
            $list[] = array(
                $this->getAutoTimeUpdate($this->_data[$feedHash], false),
                $feedHash,
                $this->_data[$feedHash]['title'],
                $this->_data[$feedHash]['lastUpdate'],
                $this->getTimeUpdate($this->_data[$feedHash])
            );
        }
        sort($list);

        return $list;
    }

    public function getAutoTimeUpdate($feed, $auto = true)
    {
        // auto with the last 7 items
        $items = array_slice($feed['items'], 0, 7, true);
        $sum = 0;
        $firstTime = 0;
        $nbItems = 0;
        foreach ($items as $item) {
            if ($firstTime == 0) {
                $firstTime = $item['time'];
            }
            $sum += $firstTime-$item['time'];
            $nbItems++;
        }
        $freq = 0;
        if ($nbItems!=0) {
            $freq = (int) (($sum / $nbItems) / 60);
        }
        if ($auto) {
            return $freq;
        } else {
            return time()-$firstTime;
        }
    }

    public function getTimeUpdate($feed)
    {
        $max = $feed['timeUpdate'];

        if ($max == 'auto') {
            $freq = $this->getAutoTimeUpdate($feed);
            if ($freq >= MIN_TIME_UPDATE && $freq < $this->kfc->maxUpdate) {
                $max = $freq;
            } else {
                $max = $this->kfc->maxUpdate;
            }
        } elseif ($max == 'max') {
            $max = $this->kfc->maxUpdate;
        } elseif ((int) $max < 0 && (int) $max > $this->kfc->maxUpdate) {
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

    public function getError($error)
    {
        switch ($error) {
        case ERROR_NO_XML:
            return 'Feed is not in XML format';
            break;
        case ERROR_ITEMS_MISSED:
            return 'Items may have been missed since last update';
            break;
        case ERROR_LAST_UPDATE:
            return 'Problem with the last update';
            break;
        default:
            return 'unknown error';
            break;
        }
    }

    public function updateChannelItems($feedHash)
    {
        if (isset($this->_data[$feedHash])) {
            $xmlUrl = $this->_data[$feedHash]['xmlUrl'];
            $xml = $this->loadXml($xmlUrl);

            if (isset($this->_data[$feedHash]['error'])) {
                unset($this->_data[$feedHash]['error']);
            }
            if (!$xml) {
                if (isset($this->_data[$feedHash]['items'])
                    && !empty($this->_data[$feedHash]['items'])
                ) {
                    $this->_data[$feedHash]['error'] = ERROR_LAST_UPDATE;
                } else {
                    $this->_data[$feedHash]['error'] = ERROR_NO_XML;
                }
                $this->_data[$feedHash]['lastUpdate'] = time();
                $this->writeData();

                return false;
            } else {
                // if feed description is empty try to update description
                // (after opml import, description is often empty)
                if (empty($this->_data[$feedHash]['description'])) {
                    $channel = $this->getChannelFromXml($xml);
                    $this->_data[$feedHash]['description']
                        = $channel['description'];
                    if (empty($this->_data[$feedHash]['description'])) {
                        $this->_data[$feedHash]['description'] = ' ';
                    }
                }


                $oldItems = array();
                if (isset($this->_data[$feedHash]['items'])) {
                    $oldItems = $this->_data[$feedHash]['items'];
                }

                $newItems = $this->getItemsFromXml($xml);
                foreach (array_keys($newItems) as $itemHash) {
                    if (empty($newItems[$itemHash]['author'])) {
                        $newItems[$itemHash]['author']
                            = $this->_data[$feedHash]['title'];
                    } else {
                        $newItems[$itemHash]['author']
                            = $this->_data[$feedHash]['title'] . ' ('
                            . $newItems[$itemHash]['author'] . ')';
                    }
                    $newItems[$itemHash]['xmlUrl'] = $xmlUrl;
                }

                $this->_data[$feedHash]['items']
                    = array_merge($newItems, $oldItems);

                // Check if items may have been missed
                $countAll = count($this->_data[$feedHash]['items']);
                $countNew = count($newItems);
                $countOld = count($oldItems);
                if (($countAll == $countNew + $countOld)
                    && count($oldItems) != 0
                ) {
                    $this->_data[$feedHash]['error'] = ERROR_ITEMS_MISSED;
                }

                uasort($this->_data[$feedHash]['items'], "Feed::compItemsR");
                // Check if quota exceeded
                if ($countAll > $this->kfc->maxItems) {
                    $this->_data[$feedHash]['items']
                        = array_slice(
                            $this->_data[$feedHash]['items'],
                            0,
                            $this->kfc->maxItems, true
                        );
                }
                $this->_data[$feedHash]['lastUpdate'] = time();

                // Remove already read items not any more in the feed
                foreach ($this->_data[$feedHash]['items'] as
                         $itemHash => $item) {
                    if ($item['read'] == 1
                        && !in_array($itemHash, array_keys($newItems))
                    ) {
                        unset($this->_data[$feedHash]['items'][$itemHash]);
                    }
                }

                $this->writeData();

                return true;
            }
        }

        return false;
    }

    public function getFeedsHashFromFolderHash($folderHash)
    {
        $list = array();
        $folders = $this->getFolders();

        if (isset($folders[$folderHash])) {
            foreach ($this->_data as $feedHash => $feed) {
                if (in_array($folders[$folderHash], $feed['folders'])) {
                    $list[] = $feedHash;
                }
            }
        }

        return array_unique($list);
    }

    public function getUnread($hash = '')
    {
        $list = $this->getItems($hash);
        $unread = 0;
        foreach (array_values($list) as $item) {
            if ($item['read']!=1) {
                $unread++;
            }
        }

        return $unread;
    }

    public function mark($hash, $read, $force = false)
    {
        $list = array_keys($this->getItems($hash, false));
        foreach ($this->_data as $feedHash => $feed) {
            foreach ($feed['items'] as $itemHash => $item) {
                $current =& $this->_data[$feedHash]['items'];
                if (in_array($itemHash, $list)) {
                    if ($force) {
                        $current[$itemHash]['read'] = $read;
                    } else {
                        if ($read == 1) {
                            $isRead = $current[$itemHash]['read'];
                            if ($isRead != -1) {
                                $current[$itemHash]['read'] = $read;
                            }
                        } else {
                            $current[$itemHash]['read'] = $read;
                        }
                    }
                }
            }
        }

        $this->writeData();
    }

    public function hashType($hash)
    {
        $type = '';
        if (empty($hash)) {
            $type = '';
        } else {
            if (isset($this->_data[$hash])) {
                // a feed
                $type = 'feed';
            } else {
                $folders = $this->getFolders();
                if (isset($folders[$hash])) {
                    // a folder
                    $type = 'folder';
                } else {
                    // should be an item
                    $type = 'item';
                }
            }
        }

        return $type;
    }

    public function getItems($hash = '', $filter = true)
    {
        $list = array();

        if (empty($hash) || $hash == 'all') {
            // all items
            foreach (array_values($this->_data) as $arrayInfo) {
                $list = array_merge($list, $arrayInfo['items']);
            }
        } else {
            if (isset($this->_data[$hash])) {
                // a feed
                $list = $this->_data[$hash]['items'];
            } else {
                $folders = $this->getFolders();
                if (isset($folders[$hash])) {
                    // a folder
                    foreach ($this->_data as $feedHash => $arrayInfo) {
                        if (in_array($folders[$hash], $arrayInfo['folders'])) {
                            $list = array_merge($list, $arrayInfo['items']);
                        }
                    }
                } else {
                    // should be an item
                    foreach ($this->_data as $xmlUrl => $arrayInfo) {
                        if (isset($arrayInfo['items'][$hash])) {
                            $list[$hash] = $arrayInfo['items'][$hash];
                            break;
                        }
                    }
                }
            }
        }

        // remove useless items
        if (($filter === true && $this->kfc->newItems) || $filter === 'new') {
            foreach ($list as $itemHash => $item) {
                if ($item['read'] == 1) {
                    unset($list[$itemHash]);
                }
            }
        }

        // sort items
        if ($this->kfc->reverseOrder) {
            uasort($list, "Feed::compItemsR");
        } else {
            uasort($list, "Feed::compItems");
        }

        return $list;
    }

    public static function compItemsR($a, $b)
    {
        if ($a['time'] == $b['time']) {
            return 0;
        } else if ($a['time'] > $b['time']) {
            return -1;
        } else {
            return 1;
        }
    }

    public static function compItems($a, $b)
    {
        if ($a['time'] == $b['time']) {
            return 0;
        } else if ($a['time'] < $b['time']) {
            return -1;
        } else {
            return 1;
        }
    }

    public function loadData()
    {
        if (file_exists($this->file)) {
            $this->_data = unserialize(
                gzinflate(
                    base64_decode(
                        substr(
                            file_get_contents($this->file),
                            strlen(PHPPREFIX),
                            -strlen(PHPSUFFIX)
                        )
                    )
                )
            );
        } else {
            $this->_data[MyTool::smallHash('http://tontof.net/?rss')] = array(
                'title' => 'Tontof',
                'folders' => array(),
                'timeUpdate' => 'auto',
                'lastUpdate' => 0,
                'htmlUrl' => 'http://tontof.net',
                'xmlUrl' => 'http://tontof.net/?rss',
                'description' => 'A simple and smart (or stupid) kriss blog',
                'items' => array());

            $this->writeData();

            header('Location: '.MyTool::getUrl());
            exit();
        }
    }

    public function writeData()
    {
        $write = @file_put_contents(
            $this->file,
            PHPPREFIX
            . base64_encode(gzdeflate(serialize($this->_data)))
            . PHPSUFFIX
        );

        if (!$write) {
            die("Can't write to " . $this->file);
        }
    }
}

class MyTool
{
    public static function initPhp()
    {
        if (phpversion() < 5) {
            die("Argh you don't have PHP 5 !");
        }

        error_reporting(E_ALL);

        if (get_magic_quotes_gpc()) {
            $_POST = array_map('MyTool::_stripslashesDeep', $_POST);
            $_GET = array_map('MyTool::_stripslashesDeep', $_GET);
            $_COOKIE = array_map('MyTool::_stripslashesDeep', $_COOKIE);
        }

        ob_start('ob_gzhandler');
        register_shutdown_function('ob_end_flush');
    }

    private function _stripslashesDeep($value)
    {
        return is_array($value)
            ? array_map('MyTool::_stripslashesDeep', $value)
            : stripslashes($value);
    }

    public static function isUrl($url)
    {
        $pattern= "/^(https?:\/\/)(w{0}|w{3})\.?[A-Z0-9._-]+\.[A-Z]{2, 3}\$/i";

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
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $url = preg_replace('/([?&].*)$/', '', $url);

        return $url;
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

}

class Session
{
    public static $inactivityTimeout = 3600;

    private static $_instance;

    private function __construct()
    {
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()) {
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_start();
        }
    }

    public static function init()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session();
        }
    }

    private static function _allInfo()
    {
        $infos = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $infos.=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $infos.='_'.$_SERVER['HTTP_CLIENT_IP'];
        }
        $infos.='_'.$_SERVER['HTTP_USER_AGENT'];
        $infos.='_'.$_SERVER['HTTP_ACCEPT_LANGUAGE'];

        return sha1($infos);
    }

    public static function login (
        $login,
        $password,
        $loginTest,
        $passwordTest,
        $pValues = array())
    {
        if ($login == $loginTest && $password==$passwordTest) {
            // Generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('', true).'_'.mt_rand());
            $_SESSION['info']=Session::_allInfo();
            $_SESSION['username']=$login;
            // Set session expiration.
            $_SESSION['expires_on']=time()+Session::$inactivityTimeout;

            foreach ($pValues as $key => $value) {
                $_SESSION[$key] = $value;
            }

            return true;
        }
        Session::logout();

        return false;
    }

    public static function logout()
    {
        unset($_SESSION['uid'], $_SESSION['info'], $_SESSION['expires_on']);
    }

    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || $_SESSION['info']!=Session::_allInfo()
            || time()>=$_SESSION['expires_on']) {
            Session::logout();

            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        $_SESSION['expires_on']=time()+Session::$inactivityTimeout;

        return true;
    }

    public static function getToken()
    {
        if (!isset($_SESSION['tokens'])) {
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('', true).'_'.mt_rand());
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
