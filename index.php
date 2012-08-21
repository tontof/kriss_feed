<?php
// kriss_feed simple and smart (or stupid) feed reader
// 2012 - Copyleft - Tontof - http://tontof.net
// use KrISS feed at your own risk

define('DATA_DIR', 'data');

define('DATA_FILE', DATA_DIR.'/data.php');
define('CONFIG_FILE', DATA_DIR.'/config.php');
define('STYLE_FILE', 'style.css');

define('FEED_VERSION', 3);

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

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

    public $mode = 'show';

    public $view = 'expanded';

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

    public function getMode()
    {
        $mode = $this->mode;
        if (isset($_GET['show']) && !isset($_GET['reader'])) {
            $mode = 'show';
        }
        if (isset($_GET['reader']) && !isset($_GET['show'])) {
            $mode = 'reader';
        }

        return $mode;
    }

    public function getView()
    {
        $view = $this->view;
        if (isset($_GET['expanded']) && !isset($_GET['list'])) {
            $view = 'expanded';
        }
        if (isset($_GET['list']) && !isset($_GET['expanded'])) {
            $view = 'list';
        }

        return $view;
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

    public function setMode($mode)
    {
        if ($mode === 'show') {
            $this->mode = 'show';
        } elseif ($mode === 'reader') {
            $this->mode = 'reader';
        }
    }

    public function setView($view)
    {
        if ($view === 'expanded') {
            $this->view = 'expanded';
        } elseif ($view === 'list') {
            $this->view = 'list';
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
                      'maxItems', 'newItems', 'view', 'mode');
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

#config, #edit, #import, #show, #login {
  border: 2px solid #999;
  border-top: none;
  background: #fff;
  width: 800px;
  margin: auto;
  padding: .2em;
}

#reader {
  background: #fff;
  width: 100%;
  height: 100%;
}

#feeds {
  width: 250px;
  height: 100%;
  float: left;
  overflow: auto;
}

#list-feeds h3 {
  background-color: #ccc;
}

#list-feeds, #list-feeds ul, #list-items{
  font-size: .9em;
  list-style: none;
  margin: 0;
  padding: 0;
  overflow: hidden;
}

#container {
  margin-left: 250px;
  height: 100%;
  overflow: auto;
}

#extra {
  position: absolute;
  background: #fff;
  border: 1px dotted #999;
  width: 15px;
  height: 15px;
  overflow: hidden;
}

#extra:hover {
  width: auto;
  height: auto;
}

#title, .article-title {
  margin: 0;
  color: #666;
  border-bottom: 1px dotted #999;
}

#subtitle, .article-subtitle {
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
  clear: both;
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

#new-items, #article, .article, .comment{
  border: 1px dotted #999;
  padding: .5em;
  margin: 1.5em 0;
  overflow: auto;
  background-color: #fff;
  white-space: normal;
}

#new-items {
  text-align: center;
}

.item, .item-feed, .item-title {
  line-height: 1.1em;
  white-space: nowrap;
  overflow: hidden;
  cursor:pointer;
}

.item {
  border-top: 1px solid #ccc;
}

.item-info {
  display: inline-block;
  width: 100%;
}

.item-feed {
  display: block;
  float: left;
  width: 11em;
  font-size: 0.9em;
}

.item-title {
    font-weight: bold;
    font-size: 0.9em;
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
  padding: .5em;
}

.description{
  padding: .5em;
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

input[type=text], input[type=password], textarea{
  border: 1px solid #000;
  margin: .2em 0;
  padding: .2em;
  font-size: 1em;
  width: 100%;
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
  margin-left: 1em;
  margin-bottom: .2em;
  padding-left: 0;
}

li {
  margin-bottom: .2em;
}

#plusmenu li {
  display: inline;
}

@media (max-width: 800px) {
body{
  width: 100%;
  height: 100%;
}

#config, #edit, #feeds, #import, #show, #reader, #container {
  width: auto;
  height: auto;
}

#feeds {
  float: none;
}

.item{
  display: block;
  border: 1px solid black;
  margin: .2em;
  background-color: #ccc;
}

.item-title{
  white-space: normal;
}

#container {
  margin-left: 0;
}

img, video, iframe, object{
  max-width: 100%;
  height: auto;
}

#plusmenu li {
  display: block;
}
.nomobile{
  display: none !important;
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


    public function loginPage($kfc)
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
        }else {
            $ref = '?'.$kfc->getMode();
        }
        $token = Session::getToken();
        $status = $this->status();

        return <<<HTML
<div id="login">
<form method="post" action="?login" name="loginform">
  <fieldset>
  <legend>Welcome to KrISS feed</legend>
  <input type="hidden" name="returnurl" value="$ref">
  <input type="hidden" name="token" value="$token">
  <label>Login: <input type="text" name="login" tabindex="1"/></label>
  <label>Password: <input type="password" name="password" tabindex="2"/></label>
  <label>
    <input type="checkbox" name="longlastingsession" tabindex="3">
    &nbsp;Stay signed in (Do not check on public computers)
  </label>
  <input type="submit" value="OK" class="submit" tabindex="4">
  </fieldset>
</form>
<div>
<div id="status">
$status
</div>
<script>
document.loginform.login.focus();
</script>
HTML;

    }

    public function menu($type, $kfc, $hash = '')
    {
        if ($hash == 'all') {
            $hash = '';
        }
        $view = $kfc->getView();
        $menu = '
      <p>';
        switch($type) {
        case 'show':
            $menu .= '
        <button onclick="previousItem();">&lt;</button>
        <button onclick="plusMenu(false);" id="butplusmenu">+</button>
        <button onclick="nextItem();">&gt;</button>
        <ul id="plusmenu" style="display:none">
          <li><a href="?show" title="Show mode">Show</a></li>
          <li><a href="?reader" title="Reader mode">Reader</a></li>';
            if ($view === 'expanded') {
                $menu .= '
          <li><a href="?show&list" title="List view">List</a></li>';
            } else {
                $menu .= '
          <li><a href="?show&expanded" title="Expanded view">Expanded</a></li>';
            }
            $menu .= '
          <li>
            <a href="#" onclick="shareItem();" title="Share item on shaarli">
              Shaarli
            </a>
          </li>
          <li>
            <input type="checkbox" name="keepunread" id="keepunread">
            <label for="keepunread">Keep unread</label>
          </li>';
            if (Session::isLogged()) {
                $menu .= '
          <li>
            <a href="#" class="admin" onclick="forceUpdate();" title="Update manually">Update</a>
          </li>
          <li>
            <a href="?read" class="admin" title="Mark as read">Read all</a>
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
        case 'reader':
            $sep = '';
            if ($hash != '') {
                $sep = '=';
            }
            $menu .= '
        <a href="?show" title="Show mode">Show</a>
      | <a href="?reader" title="Reader mode">Reader</a>';
            if ($view === 'expanded') {
                $menu .= '
      | <a href="?reader'
                    . $sep . $hash . '&list" title="List view">List</a>';
            } else {
                $menu .= '
      | <a href="?reader'
                    . $sep . $hash
                    . '&expanded" title="Expanded view">Expanded</a>';
            }
            if (Session::isLogged()) {
                if ($hash != '') {
                    $menu .= '
      | <a href="?edit' . $sep . $hash . '" class="admin">Edit</a>
      | <a href="?update'
                          . $sep . $hash
                          . '" class="admin" title="Manual update">Update</a>';
                } else {
                    $menu .= '
      | <a href="#" onclick="forceUpdate();" title="Update manually">Update</a>';
                }
                $menu .= '
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
        case 'edit':
            $menu .= '
        <a href="?show" title="Show mode">Show</a>
      | <a href="?reader" title="Reader mode">Reader</a>';
            if (Session::isLogged()) {
                $menu .= '
      | <a href="?config" class="admin" title="Configuration">Configuration</a>
      | <a href="?logout" class="admin" title="Logout">Logout</a>';
            }
            break;
        case 'config':
            $menu .= '
        <a href="?show" title="Show mode">Show</a>
      | <a href="?reader" title="Reader mode">Reader</a>';
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
            . ' - A simple and smart (or stupid) feed reader'
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
            <label for="showMode">- Default mode</label><br>
            <input type="radio" id="showMode" name="mode" value="show" '
            . ($kfc->mode === 'show' ? 'checked="checked"' : '')
            . ' /><label for="showMode">Show mode</label><br>
            <input type="radio" id="readerMode" name="mode"'
            . ' value="reader" '
            . ($kfc->mode === 'reader' ? 'checked="checked"' : '')
            . ' /><label for="readerMode">Reader mode</label><br>
            <label for="view">- Expanded or list view</label><br>
            <input type="radio" id="expandedView" name="view" value="expanded" '
            . ($kfc->view === 'expanded' ? 'checked="checked"' : '')
            . ' /><label for="expandedView">Expanded view</label><br>
            <input type="radio" id="listView" name="view"'
            . ' value="list" '
            . ($kfc->view === 'list' ? 'checked="checked"' : '')
            . ' /><label for="listView">List view</label><br>
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
            <label>- Number of entries to load when scroll</label><br>
            <input type="text" maxlength="3" name="byPage" value="'
            . (int) $kfc->byPage . '"><br>
            <label>- Maximum number of entries by channel</label><br>
            <input type="text" maxlength="3" name="maxItems" value="'
            . (int) $kfc->maxItems . '"><br>
            <label>- Maximum delay between channel update (in minutes)'
            . '</label><br>
            <input type="text" maxlength="4" name="maxUpdate" value="'
            . (int) $kfc->maxUpdate . '"><br>
            <label for="newitems">- Items selection '
            . '(only for <a href="?reader">reader</a> mode)</label><br>
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
          <fieldset>
            <legend>Configuration du cron</legend>
            '.MyTool::getUrl().'?update&cron='.sha1($kfc->salt.$kfc->hash).'
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
          <ul id="list-feeds">
          <li><h3 class="title">'
            . '<button onclick="toggle(\'all-subscriptions\', this)">-</button>'
            .' <a href="?reader=all">Subscriptions</a>'.$unread.'</h3>
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
            <li> - <strong><a id="feed-'
                . $hashUrl . '" href="?reader=' . $hashUrl . '"'
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
              <li> - <strong><a id="feed-'
                    . $hashUrl . '" href="?reader=' . $hashUrl . '"'
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
        $pagination = '';
        $expanded = $kf->kfc->getView();
        if (isset($_GET['expanded']) && !isset($_GET['list'])) {
            $expanded = true;
        }
        if (isset($_GET['list']) && !isset($_GET['expanded'])) {
            $expanded = false;
        }

        // create menu
        $menu = $this->menu('reader', $kf->kfc, $hash);

        // create status
        $status = $this->status();

        // select items on the page
        $newItems = '';
        $listItems = '';
        $list = $kf->getItems($hash);
        $newItems .= '
        <div id="new-items">
          <button id="butplusmenu" onclick="loadUnreadItems();">
             0 new item(s)
          </button>
        </div>
';
        $listItems .= '
        <ul id="list-items">
        </ul>
';
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
           <input type="text" name="newfeed" id="newfeed"
              onfocus="removeEvent(window, \'keypress\', checkKey);"
              onblur="addEvent(window, \'keypress\', checkKey);">
          </form>
        </div>';
        }

        // ajaxscript
        $ajaxScript = $this->ajaxScript($kf);

        return <<<HTML
    <div id="reader">
      <div id="feeds" class="nomobile">
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
$newItems
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
        $section = '';
        $expanded = $kf->kfc->getView();
        if (isset($_GET['expanded']) && !isset($_GET['list'])) {
            $expanded = true;
        }
        if (isset($_GET['list']) && !isset($_GET['expanded'])) {
            $expanded = false;
        }

        if ($expanded) {
$section .= '
        <div id="article">
          <h3 id="title"></h3>
          <h4 id="subtitle"></h4>
          <div id="content"></div>
        </div>
';
        } else {
         $section .= '
        <ul id="list-items">
        </ul>
';
        }

        return <<<HTML
    <div id="show">
      <div id="status">
$status
      </div>
      <div id="nav">
$menu
      </div>
      <div id="section">
$section
      </div>
    </div>
$ajaxscript
HTML;
    }

    public function ajaxScript($kf)
    {
        $redir = $kf->kfc->redirector;
        $shaarli = $kf->kfc->shaarli;
        $mode = $kf->kfc->getMode();
        $view = $kf->kfc->getView();
        $currentHash = 'all';
        if ($mode === 'reader' && isset($_GET['reader'])) {
            $currentHash = $_GET['reader'];
        }
        $currentFilter = '';
        if ($mode === 'show' || $kf->kfc->newItems) {
            $currentFilter = 'unread';
        }
        $unreadItems = array_keys($kf->getItems($currentHash, $currentFilter));
        $numInitItems = 0;
        if ($mode === 'show') {
            $numInitItems = count($unreadItems);
        } else {
            $numInitItems = count(array_keys($kf->getItems($currentHash)));
        }
        $listUnreadItems = json_encode($unreadItems);
        $listFeeds = json_encode($kf->getListFeeds());
        $doUpdate = (Session::isLogged() ? 'true' : 'false');

        return <<<JAVASCRIPT
<script>
var redirector = '$redir',
    shaarli = '$shaarli',
    mode = '$mode',
    view = '$view',
    currentHash = '$currentHash',
    currentFilter = '$currentFilter',
    numInitItems = $numInitItems,
    numLoadedItems = 0,
    titlePage = '',
    updatingFeeds = false,
    updatingCurrentFeed = false,
    updateFeedTimer = null,
    listItems = [],
    currentItemInd = -1,
    listFeeds = [],
    currentFeedInd = -1,
    unreadHashItems = [],
    currentUnreadItems = 0,
    status = '',
    working = false,
    workingsave = '',
    currentPage = 1,
    hist = [],
    cache = {};

function removeElement(elt) {
    if (elt && elt.parentNode) {
        elt.parentNode.removeChild(elt);
    }
}

function removeAllChild(elt) {
    while (elt && elt.firstChild) {
        elt.removeChild(elt.firstChild);
    }
}

function addClass(elt, cls) {
    if (!hasClass(elt, cls)) {
        if (elt.className === '') {
            elt.className = cls;
        } else {
            elt.className += ' ' + cls;
        }
    }
}

function hasClass(elt, cls) {
    if ((' ' + elt.className + ' ').indexOf(' ' + cls + ' ') > -1) {
        return true;
    }
    return false;
}

function toggle(id, el) {
  var e = document.getElementById(id);

  if (e.style.display == '') {
      e.style.display = 'none';
      if (el) {
          el.innerHTML = '+';
      }
  } else {
      e.style.display = '';
      if (el) {
          el.innerHTML = '-';
      }
  }
}

// http://stackoverflow.com/questions/1912501
function htmlDecode(input) {
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes.length === 0 ? "" : e.firstChild.nodeValue;
}

function anonymize(elt) {
    var a_to_anon = elt.getElementsByTagName("a");
    for (var i = 0; i < a_to_anon.length; i++) {
        a_to_anon[i].href = redirector+a_to_anon[i].href;
    }
}

function shareItem(){
    if (shaarli != ''){
        var current = cache['item-' + listItems[currentItemInd]];
        if (current) {
	    var url = current['link'];
	    var title = current['title'];
	    window.open(shaarli+'/index.php?post=' + encodeURIComponent(url) + '&title='
                        + encodeURIComponent(title)
                        + '&source=bookmarklet'
                        ,'_blank'
                        ,'menubar=no,height=390,width=600,toolbar=no,scrollbars=no,status=no');
        } else {
            alert('Problem with current article !');
        }
    } else {
	alert('please configure your shaarli on config page !');
    }
}

function toggleKeepUnread() {
    if (document.getElementById('keepunread').checked) {
        document.getElementById('keepunread').checked = false;
    } else {
        document.getElementById('keepunread').checked = true;
    }
}

function plusMenu(openMenu) {;
    if (mode === 'show' && view === 'expanded' || openMenu) {
        var pm = document.getElementById('plusmenu');
        var but = document.getElementById('butplusmenu');
        if (pm.getAttribute('style') == 'display:none') {
            pm.setAttribute('style', 'display:block;list-style:none;');
        } else {
            pm.setAttribute('style', 'display:none');
        }
    } else {
        loadUnreadItems();
    }
}

function setStatus(text) {
    document.getElementById('status').innerHTML = text;
}

function setTitle() {
    if (titlePage === '') {
        titlePage = document.title;
    }
    document.title = titlePage + ' ('+currentUnreadItems+')';

    var but = document.getElementById('butplusmenu');
    if  (but) {
        if (mode === 'show' && view === 'expanded') {
            but.textContent = unreadHashItems.length + ' new item(s)';
        } else {
            but.textContent = currentUnreadItems + ' new item(s)';
        }
    }
}

function addUnreadItems(listHashItems) {
    for (var i = 0; i < listHashItems.length; i++) {
        if (unreadHashItems.indexOf(listHashItems[i]) == -1) {
            unreadHashItems.push(listHashItems[i]);
            if (mode === 'show' && view === 'expanded') {
                listItems.push(listHashItems[i]);
            }
            currentUnreadItems++;
        }
    }
    setTitle();
}

function forceUpdate(i) {
    if (typeof(i) == "undefined") {
        i = 0;
    }
    if (i < listFeeds.length) {
        setStatus("updating : "+listFeeds[i][1]);
        var xhr     = new XMLHttpRequest(),
            timeout = null,
            aborted = false
            url     = '?ajaxupdate='+listFeeds[i][0];

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                clearTimeout(timeout);
                if (aborted) {
                    listFeeds[i][2] = getTimeMin();
                    setTimeout(forceUpdate, 1000, i+1);
                } else {
                    if (xhr.status == 200) {
                        res = JSON.parse(xhr.responseText);
                        addUnreadItems(res);
                        listFeeds[i][2] = getTimeMin();
                        setTimeout(forceUpdate, 200, i+1);
                    } else if (xhr.status == 500) {

                    }
                }
            }
        }
        xhr.send(null);
        timeout = setTimeout(function() {
            aborted = true;
            xhr.abort();
          }, 3000);
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
                addUnreadItems(res);
            }
            listFeeds[currentFeedInd][2] = getTimeMin();
            updatingCurrentFeed = false;
            currentFeedInd++;
        }
    }
    xhr.send(null);
}

function updateCurrentFeed() {
    if (!updatingCurrentFeed) {
        if (currentFeedInd >= listFeeds.length) {
            updatingFeeds = false;
            currentFeedInd = 0;
            window.clearInterval(updateFeedTimer);
            setStatus(status);
        } else {
            updatingCurrentFeed = true;
            if (getTimeMin() - listFeeds[currentFeedInd][2] > listFeeds[currentFeedInd][3]) {
                setStatus("updating : "+listFeeds[currentFeedInd][1]);
                update(listFeeds[currentFeedInd][0]);
            } else {
                updatingCurrentFeed = false;
                currentFeedInd++;
            }
        }
    }
}

function updateFeeds() {
    if (!updatingFeeds) {
        currentFeedInd = 0;
        updateTimer = window.setInterval(updateCurrentFeed, 200);
    }
}

function getTimeMin() {
    return Math.round((new Date().getTime()) / 1000 / 60);
}

function hideArticle() {
    removeAllChild(document.getElementById('title'));
    removeAllChild(document.getElementById('subtitle'));
    removeAllChild(document.getElementById('content'));
    removeElement(document.getElementById('title'));
    removeElement(document.getElementById('subtitle'));
    removeElement(document.getElementById('content'));
}

function loadArticle(item) {
    hideArticle();
    var article = document.getElementById('article');
    if (article === null) {
        article = document.createElement('div');
        article.id = 'article';
    }
    var title = document.createElement('div'),
        subtitle = document.createElement('div'),
        content = document.createElement('div');
    title.id = 'title';
    subtitle.id = 'subtitle';
    content.id = 'content';
    article.appendChild(title);
    article.appendChild(subtitle);
    article.appendChild(content);

    if (item['read'] === 1 && view !== 'list') {
        addClass(article, 'read');
    }
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
    return article;
}

function showArticle(hashItem, markAsReadWhenLoad) {
    if (cache['item-' + hashItem]) {
        // load item
        var article = loadArticle(cache['item-' + hashItem]);

        if (document.getElementById('keepunread')) {
            if (cache['item-' + hashItem]['read']===-1) {
                document.getElementById('keepunread').checked = true;
            } else {
                document.getElementById('keepunread').checked = false;
            }
        }

        currentItemInd = listItems.indexOf(hashItem);
        if (mode === 'show') {
            if (view === 'expanded') {
                if (cache['item-' + hashItem]['read'] === 1) {
                    article.className = 'read';
                } else {
                    article.className = '';
                }
                window.scrollTo(0,0);
            } else { // list
                addClass(document.getElementById('item-'+hashItem).parentNode.firstChild, 'read');
                cache['item-' + hashItem]
                if (!document.getElementById('item-'+hashItem).hasChildNodes()) {
                    document.getElementById('item-' + hashItem).appendChild(article);
                    if (working) {
                        var rect = document.getElementById('item-' + listItems[currentItemInd]).getBoundingClientRect();
                        window.scrollTo(0, rect.top + window.scrollY);
                    }
                } else {
                    removeElement(article);
                }
            }
        } else { // reader
            if (view === 'expanded') {
                // if (!document.getElementById('item-'+hashItem).hasChildNodes()) {
                    var info = document.getElementById('item-'+hashItem).previousSibling;
                    info.className = 'item-info';
                    if (cache['item-' + hashItem]['read'] === -1) {
                        info.innerHTML = 'mark as <button onclick="readItem(\''
                            + hashItem + '\')">read</button>';
                    } else if (cache['item-' + hashItem]['read'] === 0) {
                        info.innerHTML = 'mark as <button onclick="readItem(\''
                            + hashItem + '\')">read</button>, <button onclick="keepUnreadItem(\''
                            + hashItem + '\')">keepunread</button>';
                    } else if (cache['item-' + hashItem]['read'] === 1) {
                        info.innerHTML = 'mark as <button onclick="keepUnreadItem(\''
                            + hashItem + '\')">keepunread</button>';
                    }

                    removeAllChild(document.getElementById('item-' + hashItem));
                    document.getElementById('item-' + hashItem).appendChild(article);
                    article.id = '';
                    addClass(article, 'article');
                    article = document.getElementById('title');
                    article.id = '';
                    addClass(article, 'article-title');
                    article = document.getElementById('subtitle');
                    article.id = '';
                    addClass(article, 'article-subtitle');
                    article = document.getElementById('content');
                    article.id = '';

                    // }
            } else { // list
                addClass(document.getElementById('item-'+hashItem).parentNode.firstChild, 'read');
                if (!document.getElementById('item-'+hashItem).hasChildNodes()) {
                    document.getElementById('item-' + hashItem).appendChild(article);
                    if (working) {
                        var rect = document.getElementById('item-' + listItems[currentItemInd]).getBoundingClientRect();
                        document.getElementById('container').scrollTop += rect.top;
                    }
                } else {
                    removeElement(article);
                }
            }
        }
    } else {
        if (markAsReadWhenLoad) {
            loadItem(hashItem, false, hashItem, false, true);
        } else {
            loadItem(hashItem, false, false, false, true);
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

function markAsRead(hashItem) {
    var index = unreadHashItems.indexOf(hashItem);
    if (index > -1) {
        unreadHashItems.splice(index, 1);
    }
    if (mode === 'show' && view === 'expanded') {
        currentUnreadItems = unreadHashItems.length;
        setTitle();
    }
    if (cache['item-' + hashItem]) {
        cache['item-' + hashItem]['read'] = 1;
    }
}

function markAsKeepUnread(hashItem) {
    var index = unreadHashItems.indexOf(hashItem);
    if (index === -1) {
        unreadHashItems.push(hashItem);
    }
    if (mode === 'show' && view === 'expanded') {
        currentUnreadItems = unreadHashItems.length;
        setTitle();
    }
    if (cache['item-' + hashItem]) {
        cache['item-' + hashItem]['read'] = -1;
    }
}

function readItem(hashItem) {
    loadItem(false, false, hashItem, false, false);
}

function keepUnreadItem(hashItem) {
    loadItem(false, false, false, hashItem, false);
}

function loadItem(hashItemToLoad, hashItemToPreload, hashItemToRead, hashItemToKeepUnread, show) {
    var url = '?';

    if (hashItemToLoad) {
        if (cache['item-' + hashItemToLoad]) {
            if (show) {
                showArticle(hashItemToLoad, false);
                show = false;
            }
            hashItemToLoad = false;
        } else {
            url += (url !== '?' ? '&' : '');
            url += 'ajaxitem=' + hashItemToLoad;
        }
    } else {
        if (show) {
            hideArticle();
            if (view === 'list') {
                removeElement(document.getElementById('article'));
            }
        }
    }
    if (hashItemToLoad === false && hashItemToPreload !== false) {
        if (cache['item-' + hashItemToPreload]) {
            hashItemToPreload = false;
        } else {
            hashItemToLoad = hashItemToPreload;
            hashItemToPreload = false;
            url += (url !== '?' ? '&' : '');
            url += 'ajaxitem=' + hashItemToLoad;
        }
    }
    // if (hashItemToLoad === false && hashItemToPreload === false) {
    if (hashItemToLoad === false && hashItemToPreload === false) {
        if (hashItemToRead && !hashItemToKeepUnread) {
            if (cache['item-' + hashItemToRead]) {
                if (cache['item-' + hashItemToRead]['read'] !== 1) {
                    cache['item-' + hashItemToRead]['read'] = 1;
                    url += (url !== '?' ? '&' : '');
                    url += 'ajaxread=' + hashItemToRead;
                } else {
                    hashItemToRead = false;
                }
            } else {
                if (unreadHashItems.indexOf(hashItemToRead) !== -1) {
                    url += (url !== '?' ? '&' : '');
                    url += 'ajaxread=' + hashItemToRead;
                } else {
                    hashItemToRead = false;
                }
            }
        }
        if (hashItemToKeepUnread) {
            if (cache['item-' + hashItemToKeepUnread]) {
                if (cache['item-' + hashItemToKeepUnread]['read'] !== -1) {
                    cache['item-' + hashItemToKeepUnread]['read'] = -1;
                    url += (url !== '?' ? '&' : '');
                    url += 'ajaxkeepunread=' + hashItemToKeepUnread;
                } else {
                    hashItemToKeepUnread = false;
                }
            } else {
                url += (url !== '?' ? '&' : '');
                url += 'ajaxkeepunread=' + hashItemToKeepUnread;
            }
        }
        //    }
    }
    if (url !== '?') {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            // Everything OK
            if (xhr.readyState == 4) {
                if (xhr.status == 200) {
                    if (hashItemToLoad) {
                        if (hist.indexOf(hashItemToLoad) == -1) {
                            hist.push(hashItemToLoad);
                            if (hist.length > 10) {
                                delete cache['item-' + hist.shift()];
                            }
                        }
                        cache['item-' + hashItemToLoad] = JSON.parse(xhr.responseText);
                        if (show) {
                            showArticle(hashItemToLoad, false);
                        }
                    }
                    if (hashItemToRead && /ajaxread/.test(url)) {
                        markAsRead(hashItemToRead);
                        if (mode === 'reader' && view === 'expanded') {
                            showArticle(hashItemToRead, false);
                        }
                    }
                    if (hashItemToKeepUnread && /ajaxkeepunread/.test(url)) {
                        markAsKeepUnread(hashItemToKeepUnread);
                        if (mode === 'reader' && view === 'expanded') {
                            showArticle(hashItemToKeepUnread, false);
                        }
                    }
                    if (hashItemToPreload !== false) {
                        // loadItem(hashItemToPreload, false, hashItemToRead, hashItemToKeepUnread, false);
                        loadItem(hashItemToPreload, false, hashItemToRead, hashItemToKeepUnread, false);
                        //loadItem(hashItemToPreload, false, false, false, false);
                    } else {
                        // loadItem(false, false, hashItemToRead, hashItemToKeepUnread, false);
                        loadItem(false, false, hashItemToRead, hashItemToKeepUnread, false);
                    }
                }
                // finishWorking();
            }
        }
        xhr.send(null);
    } else {
        finishWorking();
    }
}

// next/previous item Show/Reader list
function nextItemList() {
    var hashCurrentToRead = false,
        hashCurrentToKeepUnread = false,
        hashNextToLoad = false,
        hashNextToPreload = false;
    if (currentItemInd < listItems.length) {
        currentItemInd++;
        hashCurrentToRead = listItems[currentItemInd];
        hashNextToLoad = listItems[currentItemInd];
    }
    loadItem(hashNextToLoad, hashNextToPreload, hashCurrentToRead, hashCurrentToKeepUnread, true);
}

function previousItemList() {
    var hashCurrentToRead = false,
        hashCurrentToKeepUnread = false,
        hashPreviousToLoad = false,
        hashPreviousToPreload = false;
    if (currentItemInd > 0) {
        currentItemInd--;
        hashCurrentToRead = listItems[currentItemInd];
        hashPreviousToLoad = listItems[currentItemInd];
    } else {
        currentItemInd = -1;
    }
    loadItem(hashPreviousToLoad, hashPreviousToPreload, hashCurrentToRead, hashCurrentToKeepUnread, true);
}

// Show expanded
function nextItemShowExpanded() {
    var hashCurrentToRead = false,
        hashCurrentToKeepUnread = false,
        hashNextToLoad = false,
        hashNextToPreload = false;
    if (currentItemInd >= 0 && currentItemInd < listItems.length) {
        hashCurrentToRead = listItems[currentItemInd];
    }
    currentItemInd++;
    if (currentItemInd < listItems.length) {
        hashNextToLoad = listItems[currentItemInd];
        if (currentItemInd + 1 < listItems.length) {
            hashNextToPreload = listItems[currentItemInd + 1];
        }
    } else {
        currentItemInd--;
        // uncomment if you don't want to hide the last article
        // hashNextToLoad = listItems[currentItemInd];
    }
    if (document.getElementById('keepunread')!= null) {
        if (document.getElementById('keepunread').checked) {
            hashCurrentToKeepUnread = hashCurrentToRead;
        }
    }
    loadItem(hashNextToLoad, hashNextToPreload, hashCurrentToRead, hashCurrentToKeepUnread, true);
}


function previousItemShowExpanded() {
    var hashCurrentToRead = false,
        hashCurrentToKeepUnread = false,
        hashPreviousToLoad = false,
        hashPreviousToPreload = false;
    if (currentItemInd >= 0 && currentItemInd < listItems.length) {
        hashCurrentToRead = listItems[currentItemInd];
    }
    currentItemInd--;
    if (currentItemInd >= 0) {
        hashPreviousToLoad = listItems[currentItemInd];
        if (currentItemInd - 1 >= 0) {
            hashPreviousToPreload = listItems[currentItemInd - 1];
        }
    } else {
        currentItemInd++;
        hashPreviousToLoad = listItems[currentItemInd];
    }
    if (document.getElementById('keepunread')!= null) {
        if (document.getElementById('keepunread').checked) {
            hashCurrentToKeepUnread = hashCurrentToRead;
        }
    }
    loadItem(hashPreviousToLoad, hashPreviousToPreload, hashCurrentToRead, hashCurrentToKeepUnread, true);
}

// next/previous item
function nextItem() {
    if (!working) {
        working = true;
        if (mode === 'show') {
            if (view === 'list') {
                nextItemList();
            } else {
                nextItemShowExpanded();
            }
        } else if (mode === 'reader') {
            if (view === 'list') {
                nextItemList();
            } else {
                nextItemReaderExpanded();
            }
        }
    } else {
        workingsave = 'next';
    }
}

function previousItem() {
    if (!working) {
        working = true;
        if (mode === 'show') {
            if (view === 'list') {
                previousItemList();
            } else {
                previousItemShowExpanded();
            }
        } else if (mode === 'reader') {
            if (view === 'list') {
                previousItemList();
            } else {
                previousItemReaderExpanded();
            }
        }
    } else {
        workingsave = 'previous';
    }
}

function getAuthor(feedHash) {
    for (feed in listFeeds) {
        if (listFeeds[feed][0] === feedHash) {
            return listFeeds[feed][1];
        }
    }
    return '';
}

function addItemsToList(list, where) {
    var newList = [],
        listUl = document.getElementById('list-items');

    for (item in list) {
        var itemHash = item.substr(5,12);
        if (listItems.indexOf(itemHash) === -1) {
            listItems.push(itemHash);
            if (where === 'bottom') {
                newList.push(itemHash);
            } else {
                newList.unshift(itemHash);
            }
        }
    }

    for (var i = 0; i < newList.length; i++) {
        var item = 'item-' + newList[i];
        feedHash = item.substr(5,6);
        itemLi = document.createElement('li');
        itemLi.className = 'item';
        span = document.createElement('span');
        span.className = 'item-info';
        if (list[item][3]) {
            addClass(span, 'read');
        }
        if (view === 'list') {
            span.onclick= function () { showArticle(this.parentNode.lastChild.id.substr(5,12), true); };
        }
        spanfeed = document.createElement('span');
        spanfeed.className = 'item-feed nomobile';
        spanfeed.innerHTML = ' - ' + getAuthor(feedHash);
        spantitle = document.createElement('span');
        spantitle.className = 'item-title';
        spantitle.innerHTML = '&nbsp;' + list[item][0];
        spandescription = document.createElement('span');
        spandescription.className = 'item-description nomobile';
        spandescription.innerHTML = '&nbsp;' + list[item][1];
        divitem = document.createElement('div');
        divitem.id = item;
        span.appendChild(spanfeed);
        span.appendChild(spantitle);
        span.appendChild(spandescription);
        itemLi.appendChild(span);
        itemLi.appendChild(divitem);
        if (where === 'bottom') {
            listUl.appendChild(itemLi);
        } else {
            listUl.insertBefore(itemLi, listUl.firstChild);
        }
        if (mode === 'reader' && view === 'expanded') {
            var cacheItem = {};
            cacheItem['title'] = list[item][0];
            cacheItem['description'] = list[item][1];
            cacheItem['time'] = list[item][2];
            cacheItem['read'] = list[item][3];
            cacheItem['link'] = list[item][4];
            cacheItem['author'] = list[item][5];
            cacheItem['content'] = list[item][6];
            cacheItem['xmlUrl'] = list[item][7];

            cache[item] = cacheItem;
            showArticle(newList[i], false);
        }
    }
    return newList.length;
}

function loadUnreadItems() {
    if (currentUnreadItems !== 0) {
        loadItems('', '', 'top', 'unread');
    }
}

function loadItems(page, numInitItems, where, filter) {
    var xhr = new XMLHttpRequest();
    var url = '?ajaxlist';
    if (currentHash !== '') {
        url += '=' + currentHash;
    }
    if (currentFilter !== '' && filter === '') {
        url += '&filter=' + currentFilter;
    }
    if (filter !== '') {
        url += '&filter=' + filter;
    }
    if (view !== '') {
        url += '&view=' + view;
    }
    if (page !== '') {
        url += '&page=' + page;
    }
    if (numInitItems !== '') {
        url += '&numInitItems=' + numInitItems;
    }
    xhr.open('GET', url, true);
    xhr.onreadystatechange = function() {
        // Everything OK
        if (xhr.readyState == 4 && xhr.status == 200) {
            var numAddedItems = addItemsToList(JSON.parse(xhr.responseText), where);
            if (page !== '') {
                numLoadedItems += numAddedItems;
                currentPage = page;
                checkScroll();
            } else {
                currentUnreadItems = 0;
            }
            setTitle();
        }
    }
    xhr.send(null);
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

function checkKey(e) {
    if (!e.ctrlKey && !e.altKey && !e.shiftKey) {
        var code;
        if (!e) var e = window.event;
        if (e.keyCode) code = e.keyCode;
        else if (e.which) code = e.which;
        switch(code) {
        case 85: // 'U'
        case 117: // 'u'
            if (mode === 'show') {
                toggleKeepUnread();
            }
            break;
        case 39: // right arrow
        case 110: // 'n'
        case 78: // 'N'
        case 106: // 'j'
        case 74: // 'J'
            nextItem();
            break;
        case 37: // left arrow
        case 112: // 'p'
        case 80 : // 'P'
        case 107: // 'k'
        case 75: // 'K'
            previousItem();
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

function checkScroll() {
    var elt = null;
    if (mode === 'show') {
        if (view === 'list') {
            elt = document.documentElement;
        }
    } else {
        var container = document.getElementById('container');
        if (container) {
            elt = container;
        }

        if (document.getElementById('feeds')
            && window.getComputedStyle(document.getElementById('feeds'), null).getPropertyValue('display') === 'none') {
            // mobile version
            elt = document.documentElement;
        }
    }
    if (elt != null) {
        if (elt.scrollTop == 0) {
            //console.log('top');
        }
        if (elt.scrollHeight
            <=
            elt.scrollTop
            + elt.offsetHeight
            + Math.round(elt.scrollHeight / 10)) {
            //console.log('bottom');
            if (currentPage != -1) {
                var page = currentPage;
                currentPage = -1;
                if (numLoadedItems < numInitItems) {
                    loadItems(page + 1, numInitItems, 'bottom', '');
                }
            }
        }
    }
}

// add some usefful events
if (document.getElementById && document.createTextNode) {
    addEvent(window, 'load', _init);
    addEvent(window, 'keypress', checkKey);
    addEvent(window, 'touchstart', checkMove);
    addEvent(window, 'scroll', checkScroll);
    var container = document.getElementById('container');
    if (container != null) {
        addEvent(container, 'scroll', checkScroll);
    }
}


// init function when document is loaded
function _init() {
    window.scrollTo(0,0);
    currentPage = 1;
    unreadHashItems = $listUnreadItems;
    currentUnreadItems = unreadHashItems.length;
    listFeeds = $listFeeds;
    status = document.getElementById('status').innerHTML;

    for (var i = 0; i < listFeeds.length; i++) {
        // if time differs between server and client
        listFeeds[i][2] = getTimeMin() - listFeeds[i][2];
    }

    if (mode === 'show') { // show mode
        if (view === 'list') { // list view
            plusMenu(true);
            if (currentUnreadItems !== 0) {
                loadItems(currentPage, numInitItems, 'top', '');
            }
            currentUnreadItems = 0;
        } else { // expanded view
            var load = (unreadHashItems.length > 0)
                ? unreadHashItems[0]
                : false,
                preload = (unreadHashItems.length > 1)
                ? unreadHashItems[1]
                : false;
            loadItem(load, preload, false, false, true);
            if (load) {
                currentItemInd = 0;
            }
            listItems = unreadHashItems.slice(0);
        }
    } else { // reader mode
        if (view === 'list') { // list view
            loadItems(currentPage, numInitItems, 'top', '');
            currentUnreadItems = 0;
        } else { // expanded view
            loadItems(currentPage, numInitItems, 'top', '');
            currentUnreadItems = 0;
        }
    }
    setTitle();

    if ($doUpdate) {
        updateFeeds();
        window.setInterval(updateFeeds, 60000);
    }
}

</script>
JAVASCRIPT;
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
                $timeUpdate = 'auto';
                $lastUpdate = 0;
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

        if (!empty($channel)){ 
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
                mb_internal_encoding("UTF-8");
                $newItems[$hashUrl]['description'] = mb_substr(
                    strip_tags($tmpItem['description']), 0, 500
                );
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
                    $items[$feedHash . $itemHash] = $items[$itemHash];
                    unset($items[$itemHash]);
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

    public function getListFeeds()
    {
        $list = array();
        foreach (array_keys($this->_data) as $feedHash) {
            $item = current($this->_data[$feedHash]['items']);
            $sortInfo = time()-$item['time'];
            $list[] = array(
                $sortInfo,
                $feedHash,
                $this->_data[$feedHash]['title'],
                (int) ((time() - $this->_data[$feedHash]['lastUpdate']) / 60),
                $this->getTimeUpdate($this->_data[$feedHash])
            );
        }

        sort($list);

        // Remove sortInfo
        $shift = function(&$array) {
            array_shift($array); return $array;
        };
        $list = array_map($shift, $list);

        return $list;
    }

    public function getTimeUpdate($feed)
    {
        $max = $feed['timeUpdate'];

        if ($max == 'auto') {
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
            if ($freq >= MIN_TIME_UPDATE && $freq < $this->kfc->maxUpdate) {
                $max = $freq;
            } else {
                $max = $this->kfc->maxUpdate;
            }
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
                    $newItems[$feedHash . $itemHash] = $newItems[$itemHash];
                    unset($newItems[$itemHash]);
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

        foreach ($list as $itemHash) {
            $feedHash = substr($itemHash, 0, 6);
            if (isset($this->_data[$feedHash]['items'][$itemHash])) {
                $current = &$this->_data[$feedHash]['items'][$itemHash];
                if ($force) {
                    $current['read'] = $read;
                } else {
                    if ($read == 1) {
                        $isRead = $current['read'];
                        if ($isRead != -1) {
                            $current['read'] = $read;
                        }
                    } else {
                        $current['read'] = $read;
                    }
                }
            }
        }

        $this->writeData();
    }

    public function hashType($hash)
    {
        $type = '';
        if (empty($hash) || $hash=='all') {
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
                    $feedHash = substr($hash, 0, 6);
                    if (isset($this->_data[$feedHash]['items'][$hash])) {
                        $list[$hash] = $this->_data[$feedHash]['items'][$hash];
                    }
                }
            }
        }

        // remove useless items
        if (($filter === true && $this->kfc->newItems)
            || $filter === 'unread') {
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
            || $_SERVER["SERVER_PORT"] == '443'; // HTTPS detection.
        $serverport = ($_SERVER["SERVER_PORT"] == '80'
                       || ($https && $_SERVER["SERVER_PORT"] == '443')
                       ? ''
                       : ':' . $_SERVER["SERVER_PORT"]);

        return 'http' . ($https ? 's' : '') . '://'
            . $_SERVER["SERVER_NAME"] . $serverport . $_SERVER['SCRIPT_NAME'];

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
        if (!Session::isToken($_POST['token'])) {
            die('Wrong token.');
        }
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

            $rurl = $_POST['returnurl'];
            if (empty($rurl) || strpos($rurl, '?login') !== false) {
                $rurl = MyTool::getUrl();
            }
            header('Location: '.$rurl);
            exit();
        }
        die("Login failed !");
    } else {
        echo $kfp->htmlPage('Login', $kfp->loginPage($kfc));
    }
} elseif (isset($_GET['logout'])) {
    //Logout
    Session::logout();
    $rurl = (empty($_SERVER['HTTP_REFERER']) ? '?' : $_SERVER['HTTP_REFERER']);
    header('Location: '.$rurl);
    exit();
} elseif (isset($_GET['update'])
          && (Session::isLogged()
              || (isset($_GET['cron'])
                  && $_GET['cron'] === sha1($kfc->salt.$kfc->hash)))) {
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
            'Location: ' . MyTool::getUrl() . '/?' . $kfc->getView()
            . '=' . MyTool::smallHash($_GET['newfeed'])
        );
        exit();
    } else {
        $returnurl = empty($_SERVER['HTTP_REFERER'])
            ? MyTool::getUrl() . '/?' . $kfc->getView()
            : $_SERVER['HTTP_REFERER'];
        echo '<script>alert("The feed you are trying to add already exists'
            . ' or is wrong. Check your feed or try again later.");'
            . 'document.location=\'' . htmlspecialchars($returnurl)
            . '\';</script>';
        exit;
        // Add fail
    }
} elseif ((isset($_GET['read'])
           || isset($_GET['unread'])
           || isset($_GET['keepunread']))
          && Session::isLogged()) {
    // mark all as read : item, feed, folder, all
    $kf->loadData();
    $hash = '';
    $read = 1;
    if (isset($_GET['read'])) {
        $hash = $_GET['read'];
        $read = 1;
    } elseif (isset($_GET['unread'])) {
        $hash = $_GET['unread'];
        $read = 0;
    } else {
        $hash = $_GET['keepunread'];
        $read = -1;
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

            header('Location: ?'.$kfc->getMode());
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
} elseif ((isset($_GET['ajaxlist'])
           || isset($_GET['ajaxupdate'])
           || isset($_GET['ajaxitem'])
           || isset($_GET['ajaxread'])
           || isset($_GET['ajaxkeepunread']))
          && (Session::isLogged() || $kfc->public)
) {
    // Ajax
    $kf->loadData();

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json; charset=UTF-8');

    if (isset($_GET['ajaxlist'])) {
        $hash = $_GET['ajaxlist'];
        if (empty($hash)) {
            $hash = 'all';
        }
        $filter = $kfc->newItems ? 'unread' : 'all';
        if (isset($_GET['filter'])) {
            $filter = $_GET['filter'];
        }

        $list = $kf->getItems($hash, $filter);

        if (isset($_GET['numInitItems'])) {
            $num = $_GET['numInitItems'];
            $list = array_slice($list, count($list)-$num, $num, true);
        }

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
            $begin = ($page - 1) * $kf->kfc->byPage;
            $list = array_slice($list, $begin, $kf->kfc->byPage, true);
        }

        $listInfo = array();
        foreach (array_keys($list) as $hashItem) {
            $feedHash = substr($hashItem, 0, 6);
            if (isset($_GET['view']) && $_GET['view'] === 'expanded') {
                $listInfo['item-' . $hashItem] =
                    array(
                        $list[$hashItem]['title'],
                        $list[$hashItem]['description'],
                        $list[$hashItem]['time'],
                        $list[$hashItem]['read'],
                        $list[$hashItem]['link'],
                        $list[$hashItem]['author'],
                        $list[$hashItem]['content'],
                        $list[$hashItem]['xmlUrl']
                    );
            } else {
                $listInfo['item-' . $hashItem] =
                    array(
                        $list[$hashItem]['title'],
                        $list[$hashItem]['description'],
                        $list[$hashItem]['time'],
                        $list[$hashItem]['read']
                    );
            }
        }
        echo json_encode($listInfo);
        exit();
    } elseif (isset($_GET['ajaxupdate']) && Session::isLogged()) {
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
        $list = $kf->getItems($hash, 'unread');
        echo json_encode(array_keys($list));
        exit;
    }

    if (isset($_GET['ajaxitem'])) {
        $hash = $_GET['ajaxitem'];
        if ($kf->hashType($hash)=='item') {
            $list = $kf->getItems($hash, false);
            if (isset($list[$hash])) {
                echo json_encode($list[$hash]);
            }
        }
    }
    if (isset($_GET['ajaxread']) && Session::isLogged()) {
        if (!empty($_GET['ajaxread'])) {
             $kf->mark($_GET['ajaxread'], 1, true);
        }
    }
    if (isset($_GET['ajaxkeepunread']) && Session::isLogged()) {
        if (!empty($_GET['ajaxkeepunread'])) {
             $kf->mark($_GET['ajaxkeepunread'], -1, true);
        }
    }
    exit;
} elseif ((isset($_GET['reader']) || isset($_GET['page']))
          && (Session::isLogged() || $kfc->public)) {
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
} elseif ((isset($_GET['show'])) && (Session::isLogged() || $kfc->public)) {
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
    if (Session::isLogged() || $kfc->public) {
        header('Location: ?'.$kfc->getMode());
    } else {
        header('Location: ?login');
    }
    exit();
}
