<?php
/**
 * Feed Page corresponds to a view class for html page generation.
 */
class Feed_Page
{
    /**
     * Default stylesheet
     */
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

    /**
     * Constructor
     *
     * @param string $cssFile String of the css file to check
     */
    public function __construct($cssFile)
    {
        // We allow the user to have its own stylesheet
        if (file_exists($cssFile)) {
            $this->_css = '<link rel="stylesheet" href="'.$cssFile.'">';
        }
    }


    /**
     * Login page
     *
     * @return string HTML corresponding to the login page
     */
    public function loginPage()
    {
        $ref = '';
        if (isset($_SERVER['HTTP_REFERER'])) {
            $ref = $_SERVER['HTTP_REFERER'];
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

    /**
     * Paragraph of the menu
     *
     * @param string    $type Type of the menu
     * @param Feed_Conf $kfc  Kriss Feed Conf object
     * @param string    $hash Current hash
     *
     * @return string HTML menu
     */
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

    /**
     * Status information (update, footer)
     *
     * @return string HTML corresponding to default status
     */
    public function status()
    {
        return '<a href="http://github.com/tontof/kriss_feed">KrISS feed'
            . ' ' . FEED_VERSION . '</a><span class="nomobile">'
            . ' - A simple and smart (or stupid) feed reader'
            . '</span>. By <a href="http://tontof.net">Tontof</a>';
    }

    /**
     * Html page template
     *
     * @param string $title Title of the page
     * @param string $body  Body of the page
     *
     * @return string HTML page
     */
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

    /**
     * Config page
     *
     * @param Feed_Conf $kfc Kriss Feed Conf object
     *
     * @return string HTML config page
     */
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

    /**
     * Edit folder page
     *
     * @param Feed   $kf     Kriss Feed object
     * @param string $folder Folder name to edit
     *
     * @return string HTML edit folder page
     */
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

    /**
     * Edit feed page
     *
     * @param Feed  $kf   Kriss Feed object
     * @param array $feed Feed to edit
     *
     * @return string HTML edit feed page
     */
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


    /**
     * List of the feeds into folders
     *
     * @param Feed $kf Kriss Feed object
     *
     * @return string HTML ul listing feeds
     */
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


    /**
     * Reader page
     *
     * @param Feed    $kf   Kriss Feed object
     * @param string  $hash Hash representing item/feed/folder
     * @param integer $page Page to show
     *
     * @return string HTML reader page
     */
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

    /**
     * Show page
     *
     * @param Feed $kf Kriss Feed object
     *
     * @return string HTML show page
     */
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

    /**
     * Javascript for ajax request
     *
     * @param Feed $kf Kriss Feed object
     *
     * @return string Javascript
     */
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

/**
 * Some javascript snippets
 */
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

/**
 * Specific kriss feed functions
 */
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

    /**
     * Get the import page
     *
     * @return string HTML import page
     */
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
