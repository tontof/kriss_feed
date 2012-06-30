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

    /**
     * Status information (update, footer)
     *
     * @return string HTML corresponding to default status
     */
    public function status()
    {
        return '<a href="http://github.com/tontof/kriss_feed">KrISS feed'
            . ' ' . FEED_VERSION . '</a><span class="nomobile">'
            . '- A simple and smart (or stupid) feed reader'
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
/*
   Provide the XMLHttpRequest constructor for Internet Explorer 5.x-6.x:
   Other browsers (including Internet Explorer 7.x-9.x) do not redefine
   XMLHttpRequest if it already exists.

   This example is based on findings at:
   http://blogs.msdn.com/xmlteam/archive/
   2006/10/23/using-the-right-version-of-msxml-in-internet-explorer.aspx
*/
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
