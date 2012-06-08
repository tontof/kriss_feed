<?php
/**
 * kriss_feed simple and smart (or stupid) feed reader
 * Copyleft (C) 2012 Tontof - http://tontof.net
 * use KrISS feed at your own risk
 */

define('DATA_DIR','data');

define('DATA_FILE',DATA_DIR.'/data.php');
define('CONFIG_FILE',DATA_DIR.'/config.php');
define('STYLE_FILE','style.css');

define('FEED_VERSION',1);

define('PHPPREFIX','<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX',' */ ?>'); // Suffix to encapsulate data in php code.


class Feed_Conf
{
    private $_file = '';
    public $login = '';
    public $hash = '';
    public $salt = '';

    // Feed title
    public $title = "Kriss feed";

    // Redirector (e.g. http://anonym.to/? will mask the HTTP_REFERER)
    public $redirector = '';

    // Number of entries to display per page
    public $byPage = "10";

    // Max number of minutes between each update of channel
    public $maxUpdate = 60;

    // Reversed order ?
    public $reverseOrder = true;

    // Feed url (leave empty to autodetect)
    public $url = '';

    // kriss_feed version
    public $version;

    public function __construct($config_file,$version)
    {
        $this->_file = $config_file;
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
		if (!@mkdir(DATA_DIR,0755)) {
		    echo '
<script>
 alert("Error: can not create '.DATA_DIR.' directory, check permissions");
 document.location=window.location.href;
</script>';
		}
		@chmod(DATA_DIR,0755);
		if (!is_file(DATA_DIR.'/.htaccess')) {
		    if (!@file_put_contents(
			    DATA_DIR.'/.htaccess',
			    "Allow from none\nDeny from all\n")){
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
 alert("Your simple and smart (or stupid) feed reader is now configured. Enjoy !");
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

    public function hydrate(array $donnees)
    {
        foreach ($donnees as $key => $value) {
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
        $this->login=$login;
    }

    public function setHash($pass)
    {
        $this->hash=sha1($pass.$this->login.$this->salt);
    }

    public function setSalt($salt)
    {
        $this->salt=$salt;
    }
        
    public function setTitle($title)
    {
        $this->title=$title;
    }

    public function setRedirector($redirector)
    {
        $this->redirector=$redirector;
    }

    public function setByPage($byPage)
    {
        $this->byPage=$byPage;
    }

    public function setMaxUpdate($maxUpdate)
    {
        $this->maxUpdate=$maxUpdate;
    }

    public function setReverseOrder($reverseOrder)
    {
        $this->reverseOrder=$reverseOrder;
    }

    public function write()
    {	
        $data = array('login', 'hash', 'salt', 'title', 'redirector',
                      'byPage', 'reverseOrder', 'maxUpdate');
        $out = '<?php';
        $out.= "\n";

        foreach ($data as $key) {
            $value = strtr($this->$key, array('$' => '\\$', '"' => '\\"'));
            $out .= '$this->'.$key.' = "'.$value."\";\n";
        }

        $out.= '?>';

        if (!@file_put_contents($this->_file, $out)) {
            return false;
        }

        return true;
    }
}
?>
<?php

class Feed_Page
{
  // Default stylesheet
  private $css = '<style>
.admin {
  color: red !important;
}

body {
  font-family: Arial, Helvetica, sans-serif;
  background: #eee;
  color: #000;
  width:800px;
  margin:auto;
  height:100%;
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

#global {
  border: 2px solid #999;
  border-top: none;
  padding: 1em 1.5em 0;
  background: #fff;
}

#footer {
  margin: 0;
  font-size: 0.7em;
  text-align: center;
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

#nav {
  border: 1px dashed #999;
  padding-left: .5em;
  font-size: .9em;
  color: #666;
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

.article, .comment {
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

.link {
  font-size: .9em;
  float: right;
  border: 1px dotted #999;
  padding: .3em;
}

.read {
  opacity: 0.4;
}

#new_comment button { 
  border: 1px solid #000;
  border-radius: 4px;
  margin: 0 .2em;
  background: #fff;
  height:32px;
  width:32px;
}

#new_comment button:hover { 
  border: 1px solid #000;
  background: #999;
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

a:active, a:visited, a:link {
  text-decoration: underline;
  color: #666;
}

a:hover { 
  text-decoration: none;
}

@media (max-width: 800px) {
 body{
  width:100%;
  height:100%;
 }

 .nomobile{
  display:none;
 }
}
</style>
';

    public function __construct($css_file){
        // We allow the user to have its own stylesheet
	if (file_exists($css_file))
	    $this->css = '<link rel="stylesheet" href="'.$css_file.'">';
    }

    public function menuDiv($type){
	$menu = '
      <div id="nav"><p>';
	switch($type){
	case 'index':
	case 'config':
	case 'edit':
	    $menu .= '
        <a href="?">All items</a>
      | <a href="?feeds">Feeds</a>';
	    if (Session::isLogged()) {
		$menu .= '
      | <a href="?config" class="admin">Configuration</a>
      | <a href="?logout" class="admin">Logout</a>';
	    }
	    else{
		$menu .= '
      | <a href="?login">login</a>';
	    }
	    break;
	case 'feeds':
	    $menu .= '
        <a href="?">All items</a>
      | <a href="?feeds">Feeds</a>';
	if (Session::isLogged()) {
	    $menu .= '
      | <a href="?import" class="admin">Import</a>
      | <a href="?export" class="admin">Export</a>
      | <a href="?config" class="admin">Configuration</a>
      | <a href="?logout" class="admin">Logout</a>';
	}
	else{
	    $menu .= '
      | <a href="?login">login</a>';
	}
	    break;
	default:
	    break;
	}
	$menu .= '
      </p></div>'; 
	return $menu;
    }

    public function htmlPage($title,$body)
    {
	return '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=yes" />
    <title>'.$title.'</title>
    '.$this->css.'
    <link rel="alternate" type="application/rss+xml" title="'.$title.' RSS" href="?rss">
  </head>
  <body>'.$body.'
  </body>
</html>';
    }

    public function configPage($kfc){
	$ref = '';
	if (isset($_SERVER['HTTP_REFERER'])){
	    $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
	}
	$menu = $this->menuDiv('config');

	return '
    <div id="global">
      <div id="header">
        <h1 id="title">Configuration (version '.$kfc->version.')</h1>
        <h2 id="subtitle">Why don\'t you <a href="http://github.com/tontof/kriss_feed/">check for a new version</a> ?</h2>
      </div>'.$menu.'
      <div id="section">
        <form method="post" action="">
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <fieldset>
            <legend>Feed Reader information</legend>
            <label>- Feed reader title</label><br>
            <input type="text" name="title" value="'.htmlspecialchars($kfc->title).'"><br>
            <label>- Feed reader redirector (<strong>not implemented yet</strong>)</label><br>
            <input type="text" name="redirector" value="'.htmlspecialchars($kfc->redirector).'"><br>
            <p>(e.g. http://anonym.to/? will mask the HTTP_REFERER)</p>
          </fieldset>
          <fieldset>
            <legend>Feed reader preferences</legend>
            <label>- Number of entries by page</label><br>
            <input type="text" maxlength="3" name="byPage" value="'.(int) $kfc->byPage.'"><br>
            <label>- Maximum delay between channel update (in minutes) (<strong>not implemented yet</strong>)</label><br>
            <input type="text" maxlength="3" name="maxUpdate" value="'.(int) $kfc->maxUpdate.'"><br>
            <label for="reverse">- Order of entries (<strong>not implemented yet</strong>)</label><br>
            <input type="radio" id="normalorder" name="reverseorder" value="0" '.(!$kfc->reverseOrder ? 'checked="checked"' : '').' /> <label for="normalorder">From the latest to the newest</label><br>
            <input type="radio" id="reverseorder" name="reverseOrder" value="1" '.($kfc->reverseOrder ? 'checked="checked"' : '').' /><label for="reverseorder"><strong>Reverse order:</strong> from the newest to the latest</label><br>
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
	if (isset($_SERVER['HTTP_REFERER'])){
	    $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
	}

	$menu = $this->menuDiv('edit');

	return '
    <div id="global">
      <div id="header">
        <h1 id="title">Edit</h1>
        <h2 id="subtitle">folder</h2>
      </div>'.$menu.'
      <div id="section">
        <div class="article">
        <form method="post" action="">
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <fieldset>
            <label>- Folder name (leave empty to delete)</label><br>
            <input type="text" name="foldername" value="'.htmlspecialchars($folder).'"><br>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form>
        </div>
      </div>
    </div>';
    }

    public function editFeedPage($kf, $feed){
	$ref = '';
	if (isset($_SERVER['HTTP_REFERER'])){
	    $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
	}

	$menu = $this->menuDiv('edit');
	$lastUpdate = 'need update';
	$diff = (int)(time()-$feed['lastUpdate']);
	if($diff < $kf->kfc->maxUpdate * 60){
	    $lastUpdate = (int)($diff/60).' m '.(int)($diff%60).' s';
	}

	$folders = $kf->getFolders();
	$inputFolders = '';
	foreach ($folders as $hash => $folder){
	    $checked = '';
	    if (in_array($folder,$feed['folders'])){
		$checked = ' checked="checked"';
	    }
	    $inputFolders .= '<input type="checkbox" name="folders[]"'.$checked.' value="'.$hash.'"><label>- '.htmlspecialchars($folder).'</label><br>';
	}
	$inputFolders .= '<input type="text" name="newfolder" value="" placeholder="New folder"><br>';
            

	return '
    <div id="global">
      <div id="header">
        <h1 id="title">Edit</h1>
        <h2 id="subtitle">feed</h2>
      </div>'.$menu.'
      <div id="section">
        <form method="post" action="">
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <fieldset>
            <legend>Feed main information</legend>
            <label>- Feed title</label><br>
            <input type="text" name="title" value="'.htmlspecialchars($feed['title']).'"><br>
            <label>- Feed XML url (<em>read only</em>)</label><br>
            <input type="text" readonly="readonly" name="xmlUrl" value="'.htmlspecialchars($feed['xmlUrl']).'"><br>
            <label>- Feed main url (<em>read only</em>)</label><br>
            <input type="text" readonly="readonly" name="htmlUrl" value="'.htmlspecialchars($feed['htmlUrl']).'"><br>
            <label>- Feed description</label><br>
            <input type="text" name="description" value="'.htmlspecialchars($feed['description']).'"><br>
          </fieldset>
          <fieldset>
            <legend>Feed folders</legend>'.$inputFolders.'
          </fieldset>
          <fieldset>
            <legend>Feed preferences</legend>
            <label>- Time update (\'auto\', \'max\' or a number of minutes less than \'max\' define in <a href="?config">config</a>)</label><br>
            <input type="text" name="timeUpdate" value="'.$feed['timeUpdate'].' (not implemented yet)"><br>
            <label>- Last update (<em>read only</em>)</label><br>
            <input type="text" name="lastUpdate" value="'.$lastUpdate.'"><br>
            <input type="submit" name="delete" value="Delete"/>
            <input type="submit" name="cancel" value="Cancel"/>
            <input type="submit" name="save" value="Save" />
          </fieldset>
        </form><br>
      </div>
    </div>';
    }
    
    public function feedsDiv($kf, $action)
    {
	$str = '';
	$feeds = $kf->getFeeds();
	$folders = $kf->getFolders();
	$unread = $kf->getUnread('');
	if ($unread == 0){
	    $unread = '';
	} else {
	    $unread = ' ('.$unread.')';
	}
	$str .= '
        <div class="article">
          <ul>
          <li><strong><a href="?show">Subscriptions</a>'.$unread.'</strong>
            <ul>';
	foreach ($feeds as $hashUrl => $arrayInfo){
	    if (empty($arrayInfo['folders'])){
		$unread = $kf->getUnread($hashUrl);
		if ($unread == 0){
		    $unread = '';
		} else {
		    $unread = ' ('.$unread.')';
		}
		$str .= '
            <li><strong><a href="?'.$action.'='.$hashUrl.'" title="'.htmlspecialchars($arrayInfo['xmlUrl']).'">'.htmlspecialchars($arrayInfo['title']).'</a>'.$unread.'</strong> : '.htmlspecialchars($arrayInfo['description']).'</li>';
	    }
	}
	$str .= '
            </ul>';
	foreach ($folders as $hashFold => $folder){
	    $unread = $kf->getUnread($hashFold);
	    if ($unread == 0){
		$unread = '';
	    } else {
		$unread = ' ('.$unread.')';
	    }
	    $str .= '
            <ul>
            <li><h3 class="title"><a href="?'.$action.'='.$hashFold.'">'.htmlspecialchars($folder).'</a>'.$unread.'</h3>
              <ul>';
	    foreach ($feeds as $hashUrl => $arrayInfo){
		if (in_array($folder, $arrayInfo['folders'])){
		    $unread = $kf->getUnread($hashUrl);
		    if ($unread == 0){
			$unread = '';
		    } else {
			$unread = ' ('.$unread.')';
		    }
		    $str .= '
              <li><strong><a href="?'.$action.'='.$hashUrl.'" title="'.htmlspecialchars($arrayInfo['xmlUrl']).'">'.htmlspecialchars($arrayInfo['title']).'</a>'.$unread.'</strong> : '.htmlspecialchars($arrayInfo['description']).'</li>';
		}
	    }
	    $str .= '
              </ul>
            </li>
            </ul>';
	}
        $str .= '
          </li>
          </ul>
        </div>';
	return $str;
    }

    public function feedsPage($kf)
    {
	$menu = $this->menuDiv('feeds');

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

	$str = '
    <div id="global">'.$menu.'
      <div id="section">';
	$str .= $addNewFeed.$this->feedsDiv($kf,'edit').'
      </div>
    </div>
';
	return $str;
    }

    public function itemsOptionDiv($kf, $hash){
	$str = '';
	$type = $kf->hashType($hash);
	if (empty($type)){
	    $type = 'all';
	}
	$sep= '';
	if (!empty($hash)){
	    $sep = '=';
	}
	
	$str .= '
        <div class="article">
          '.$type.': <a href="?update'.$sep.$hash.'">Update</a> <a href="?read'.$sep.$hash.'">Mark all as read</a>
        </div>';
	return $str;
    }

    public function indexPage($kf, $hash = '', $page = 1)
    {
	$begin = ($page - 1) * $kf->kfc->byPage;
	$list = $kf->getItems($hash);

	$menu = $this->menuDiv('index');

	$type = $kf->hashType($hash);


        $pages = (count($list) <= $kf->kfc->byPage)?'':ceil(count($list) / $kf->kfc->byPage);
	
	$pagination = '';
	if (!empty($pages))
	{
	    $pagination .= '
        <p class="pagination">
        ';
	    $show='';
	    if (!empty($hash)){
		$show='show='.$hash.'&';
	    }
	    for ($p = 1; $p <= $pages; $p++)
	    {
		$pagination .= ' <a href="?'.$show.'page='.$p.'" '.($page == $p ? ' class="selected"' : '').'>'.$p.'</a> ';
	    }

	    $pagination .= '
        </>';
	}

	$list = array_slice($list,$begin,$kf->kfc->byPage,true);

	$str = '
    <div id="global">'.$menu.'
      <div id="feeds">'.$this->itemsOptionDiv($kf, $hash).'
      </div>
      <div id="feeds">'.$this->feedsDiv($kf,'show').'
      </div>
      <div id="section">'.$pagination;

	if (empty($list)) {
	    $str .= '
        <div class="article"><p>No item.</p></div>';
	} else {
	    $i=0;
	    foreach ($list as $itemHash => $item){
		$read = '';
		$markAs = ' (<a href="?read='.$itemHash.'">Mark as read</a>)';
		if ($item['read']==1){
		    $read = ' read';
		    $markAs = ' (<a href="?unread='.$itemHash.'">Mark as unread</a>)';
		}
		$str .= '
        <div class="article'.$read.'">
          <h3 class="title"><a href="'.htmlspecialchars($item['link']).'">'.htmlspecialchars($item['title']).'</a>'.$markAs.'</h3>
          <h4 class="subtitle">from <a href="'.htmlspecialchars($item['xmlUrl']).'">'.htmlspecialchars($item['author']).'</a></h4>
          <div class="content">
           '.preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $item['content']).'
          </div>
        </div>';
		$i++;
	    }
	}
	
	$str .= $pagination.'
      </div>
      <div id="footer">
        <a href="http://github.com/tontof/kriss_feed">KrISS feed</a> - A simple and smart (or stupid) feed reader.
        By <a href="http://tontof.net">Tontof</a>
      </div>
    </div>';
	return $str;
    }

    public function importPage()
    {
	$ref = '';
	if (isset($_SERVER['HTTP_REFERER'])){
	    $ref = htmlspecialchars($_SERVER['HTTP_REFERER']);
	}
	return '
    <div id="global">
      <div id="section">
        <form method="post" action="?import" enctype="multipart/form-data">
          Import Opml file (as exported by Google Reader, Tiny Tiny RSS, RSS lounge...) (Max: '.MyTool::humanBytes(MyTool::getMaxFileSize()).')
          <input type="hidden" name="returnurl" value="'.$ref.'" />
          <input type="hidden" name="token" value="'.Session::getToken().'">
          <input type="file" name="filetoupload" size="80">
          <input type="hidden" name="MAX_FILE_SIZE" value="'.MyTool::getMaxFileSize().'">
          <input type="checkbox" name="overwrite" id="overwrite"><label for="overwrite">Overwrite existing links</label>
	  <input type="submit" name="import" value="Import"><br>
        </form>
      </div>
    </div>';
    }
}


// compare two items depending on time
function compItems($a, $b){
    if ($a['time'] == $b['time']) {
	return 0;
    } else if ($a['time'] > $b['time']) {
	return -1;
    } else {
	return 1;
    }
}

class Feed
{
    // The file containing the feed entries
    public $file = '';

    // Feed_Conf object
    public $kfc;

    // Array with data
    private $_data = array();

    public function __construct($dataFile, $kfc)
    {
        $this->kfc = $kfc;
        $this->file = $dataFile;
    }

    // import feed from opml file (as exported by google reader, tiny tiny rss, rss lounge...
    public function importOpml()
    {
	$filename=$_FILES['filetoupload']['name'];
	$filesize=$_FILES['filetoupload']['size'];
	$data=file_get_contents($_FILES['filetoupload']['tmp_name']);
	$overwrite = isset($_POST['overwrite']);

	$opml = new DOMDocument('1.0', 'UTF-8');

	$import_count=0;
	if ($opml->loadXML($data)){
	    $body = $opml->getElementsByTagName('body');
	    $xmlArray = $this->getArrayFromXml($body->item(0));
	    $array = $this->convertOpmlArray($xmlArray['outline']);

	    $this->loadData();
	    foreach ($array as $hashUrl => $arrayInfo){
		$title = '';
		if (isset($arrayInfo['title'])) {
		    $title = $arrayInfo['title'];
		} else if (isset($arrayInfo['text'])) {
		    $title = $arrayInfo['text'];
		}
		$folders = array();
		if (isset($arrayInfo['folders'])) {
		    foreach ($arrayInfo['folders'] as $folder){
			$folders[] = html_entity_decode($folder);
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
		if (!empty($xmlUrl)){
		    $current = array('title' => html_entity_decode($title),
				     'description' => html_entity_decode($description),
				     'htmlUrl' => html_entity_decode($htmlUrl),
				     'xmlUrl' => html_entity_decode($xmlUrl),
				     'folders' => $folders,
				     'timeUpdate' => $timeUpdate,
				     'lastUpdate' => $lastUpdate,
				     'items' => $items);

		    if ($overwrite || !isset($this->_data[$hashUrl])){
			$this->_data[$hashUrl] = $current;
			$import_count++;
		    }
		}
	    }
	    $this->writeData();

	    echo '<script>alert("File '.$filename.' ('.MyTool::humanBytes($filesize).') was successfully processed: '.$import_count.' links imported.");document.location=\'?\';</script>';
	} else {
	    echo '<script>alert("File '.$filename.' ('.MyTool::humanBytes($filesize).') has an unknown file format. Nothing was imported.");document.location=\'?\';</script>';
	    exit;
	}
    }

    // export feeds to an opml file
    public function exportOpml()
    {
	$withoutFolder = array();
	$withFolder = array();
	$folders = array_values($this->getFolders());

	// get a new representation of data using folders as key
	foreach ($this->_data as $hashUrl => $arrayInfo){
	    if (empty($arrayInfo['folders'])){
		$withoutFolder[] = $hashUrl;
	    } else {
		foreach ($arrayInfo['folders'] as $folder) {
		    $withFolder[$folder][] = $hashUrl;
		}
	    }
	}

	// generate opml file
	header('Content-Type: text/xml; charset=utf-8');
	header('Content-disposition: attachment; filename=kriss_feed_'.strval(date('Ymd_His')).'.opml');
	$opml_data = new DOMDocument('1.0', 'UTF-8');

	// we want a nice output
	$opml_data->formatOutput = true;

	// opml node creation
	$opml = $opml_data->createElement('opml');
	$opmlVersion = $opml_data->createAttribute('version');
	$opmlVersion->value = '1.0';
	$opml->appendChild($opmlVersion);

	// head node creation
	$head = $opml_data->createElement('head');
	$title = $opml_data->createElement('title','KrISS Feed');
	$head->appendChild($title);
	$opml->appendChild($head);

	// body node creation
	$body = $opml_data->createElement('body');

	// without folder outline node
	foreach ($withoutFolder as $hashUrl){
	    $outline = $opml_data->createElement('outline');
	    $outlineTitle = $opml_data->createAttribute('title');
	    $outlineTitle->value = htmlspecialchars($this->_data[$hashUrl]['title']);
	    $outline->appendChild($outlineTitle);
	    $outlineText = $opml_data->createAttribute('text');
	    $outlineText->value = htmlspecialchars($this->_data[$hashUrl]['title']);
	    $outline->appendChild($outlineText);
	    if (!empty($this->_data[$hashUrl]['description'])){
		$outlineDescription = $opml_data->createAttribute('description');
		$outlineDescription->value = htmlspecialchars($this->_data[$hashUrl]['description']);
		$outline->appendChild($outlineDescription);
	    }
	    $outlineXmlUrl = $opml_data->createAttribute('xmlUrl');
	    $outlineXmlUrl->value = htmlspecialchars($this->_data[$hashUrl]['xmlUrl']);
	    $outline->appendChild($outlineXmlUrl);
	    $outlineHtmlUrl = $opml_data->createAttribute('htmlUrl');
	    $outlineHtmlUrl->value = htmlspecialchars($this->_data[$hashUrl]['htmlUrl']);
	    $outline->appendChild($outlineHtmlUrl);
	    $body->appendChild($outline);
	}

	// with folder outline node
	foreach ($withFolder as $folder => $arrayHashUrl){
	    $outline = $opml_data->createElement('outline');
	    $outlineTitle = $opml_data->createAttribute('title');
	    $outlineTitle->value = htmlspecialchars($folder);
	    $outline->appendChild($outlineTitle);
	    $outlineText = $opml_data->createAttribute('text');
	    $outlineText->value = htmlspecialchars($folder);
	    $outline->appendChild($outlineText);

	    foreach ($arrayHashUrl as $hashUrl){
		$outlineKF = $opml_data->createElement('outline');
		$outlineTitle = $opml_data->createAttribute('title');
		$outlineTitle->value = htmlspecialchars($this->_data[$hashUrl]['title']);
		$outlineKF->appendChild($outlineTitle);
		$outlineText = $opml_data->createAttribute('text');
		$outlineText->value = htmlspecialchars($this->_data[$hashUrl]['title']);
		$outlineKF->appendChild($outlineText);
		if (!empty($this->_data[$hashUrl]['description'])){
		    $outlineDescription = $opml_data->createAttribute('description');
		    $outlineDescription->value = htmlspecialchars($this->_data[$hashUrl]['description']);
		    $outlineKF->appendChild($outlineDescription);
		}
		$outlineXmlUrl = $opml_data->createAttribute('xmlUrl');
		$outlineXmlUrl->value = htmlspecialchars($this->_data[$hashUrl]['xmlUrl']);
		$outlineKF->appendChild($outlineXmlUrl);
		$outlineHtmlUrl = $opml_data->createAttribute('htmlUrl');
		$outlineHtmlUrl->value = htmlspecialchars($this->_data[$hashUrl]['htmlUrl']);
		$outlineKF->appendChild($outlineHtmlUrl);
		$outline->appendChild($outlineKF);
	    }
	    $body->appendChild($outline);
	}

	$opml->appendChild($body);
	$opml_data->appendChild($opml);

	echo $opml_data->saveXML();
	exit();
    }

    // convert opml xml node into array for import 
    // http://www.php.net/manual/en/class.domdocument.php#101014
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
		$array[$node->firstChild->nodeName] = $node->firstChild->nodeValue;
	    } else {
		foreach ($node->childNodes as $childNode) {
		    if ($childNode->nodeType != XML_TEXT_NODE) {
			$array[$childNode->nodeName][] = $this->getArrayFromXml($childNode); 
		    }
		}
	    }
	}

	return $array;
    } 

    // convert opml array into more convenient array with xmlUrl as key
    public function convertOpmlArray($array, $listFolders = array())
    {
	$newArray = array();

	for ($i = 0, $len = count($array); $i < $len; $i++) {
	    if (isset($array[$i]['outline'])
		&& (isset($array[$i]['text'])
		    || isset($array[$i]['title']))) {
		// here is a folder
		if (isset($array[$i]['text'])){
		    $listFolders[] = $array[$i]['text'];
		} else {
		    $listFolders[] = $array[$i]['title'];
		}
		$newArray = array_merge($newArray,$this->convertOpmlArray($array[$i]['outline'], $listFolders));
		array_pop($listFolders);
	    } else {
		if (isset($array[$i]['xmlUrl'])) {
		    // here is a feed
		    $xmlUrl = MyTool::smallHash($array[$i]['xmlUrl']);
		    if (isset($newArray[$xmlUrl])) {
			//feed already exists
			foreach ($listFolders as $val) {
			    // add folder to the feed
			    if (!in_array($val,$newArray[$xmlUrl]['folders'])){
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

    // rename folder into items (delete folder is newFolder is empty)
    public function renameFolder($oldFolder, $newFolder)
    {
	$k = 0;
	foreach ($this->_data as $feedHash => $feed) {
	    $i = array_search($oldFolder,$feed['folders']);
	    if ($i !== false){
		unset($this->_data[$feedHash]['folders'][$i]);
		if (!empty($newFolder)){
		    $this->_data[$feedHash]['folders'][]=$newFolder;
		}
	    }
	}
	if (!$this->writeData())
	    die("Can't write to ".$pb->file);
    }

    // return list of folders used to categorize feeds
    public function getFolders()
    {
	$folders = array();
	foreach ($this->_data as $xmlUrl => $arrayInfo){
	    foreach ($this->_data[$xmlUrl]['folders'] as $folder) {
		if (!in_array($folder,$folders)){
		    $folders[MyTool::smallHash($folder)] = $folder;
		}
	    }
	}
	return $folders;
    }

    // return folder name
    public function getFolder($hash)
    {
	$folders = $this->getFolders();
	if (isset($folders[$hash])) {
	    return $folders[$hash];
	}
	return false;
    }

    // return list of feeds
    public function getFeeds()
    {
	return $this->_data;
    }

    // return list of feed
    public function getFeed($hash)
    {
	if (isset($this->_data[$hash])){
	    return $this->_data[$hash];
	}
	return false;
    }

    // remove feed
    public function removeFeed($hash)
    {
	if (isset($this->_data[$hash])){
	    unset($this->_data[$hash]);
	    if (!$this->writeData())
		die("Can't write to ".$pb->file);
	}
    }

    // edit a feed
    public function editFeed($feedHash, $title, $description, $folders, $timeUpdate)
    {
	if (isset($this->_data[$feedHash])){
	    if (!empty($title)){
		$this->_data[$feedHash]['title'] = $title;
	    }
	    if (!empty($description)){
		$this->_data[$feedHash]['description'] = $description;
	    }
	    unset($this->_data[$feedHash]['folders']);
	    $this->_data[$feedHash]['folders'] = array();
	    if (!empty($folders)){
		foreach ($folders as $folder){
		    $this->_data[$feedHash]['folders'][] = $folder;
		}
	    }
	    if (!empty($timeUpdate)){
		if ($timeUpdate == 'auto'
		    || $timeUpdate == 'max'
		    || ($timeUpdate >=5 && $timeUpdate < $this->kfc->maxUpdate)){
		    $this->_data[$feedHash]['timeUpdate'] = $timeUpdate;
		} else {
		    $this->_data[$feedHash]['timeUpdate'] = 'auto';
		}
	    }

	    if (!$this->writeData())
		die("Can't write to ".$pb->file);
	}
    }

    // format xml channel into array
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
		if ($channel->hasChildNodes()){
		    $child = $channel->childNodes;
		    for ($j = 0, $lenChannel = $child->length; $j<$lenChannel; $j++){
			if (isset($child->item($j)->tagName)
			    && $child->item($j)->tagName == $list[$i]){
			    $newChannel[$format] = $child->item($j)->textContent;
			}
		    }
		}
	    }
	}
	return $newChannel;
    }

    // return channel from xmlUrl
    public function getChannelFromXml($xml)
    {
	$channel = array();

	// find feed type RSS, Atom
	$feed = $xml->getElementsByTagName('channel');
	if ($feed->item(0)){
	    // RSS/rdf:RDF feed
	    $channel = $feed->item(0);
	} else {
	    $feed = $xml->getElementsByTagName('feed');
	    if ($feed->item(0)){
		// Atom feed
		$channel = $feed->item(0);
	    } else {
		// unknown feed
	    }
	}
	return $this->formatChannel($channel);
    }

    // format items into
    public function formatItems($items)
    {
	$newItems = array();

	// list of format for each info in order of importance
	$formats = array('title' => array('title'),
			 'time' => array('pubDate', 'updated', 'lastBuildDate', 'published', 'dc:date', 'date'),
			 'author' => array('author', 'creator', 'dc:author', 'dc:creator'),
			 'link' => array('link', 'guid', 'id'),
			 'description' => array('description', 'summary', 'subtitle', 'content', 'content:encoded'),
			 'content' => array('content:encoded', 'content', 'description', 'summary', 'subtitle'));

	foreach ($items as $item) {
	    $tmp = array();
	    foreach ($formats as $format => $list) {
		$tmp[$format] = '';
		$len = count($list);
		for ($i = 0; $i < $len; $i++) {
		    $tag = $item->getElementsByTagName($list[$i]);
		    if ($tag->length != 0){
			// we find a correspondence for the current format
			// select first item (item(0)), may not work
			// stop to search for another one
			if ($format == 'link'){
			    $tmp[$format]=$tag->item(0)->getAttribute('href');
			}
			if (empty($tmp[$format])){
			    $tmp[$format] = $tag->item(0)->textContent;
			}
			$i = $len;
		    }
		}
	    }
	    if (!empty($tmp['link'])){
		$hashUrl = MyTool::smallHash($tmp['link']);
		$newItems[$hashUrl] = array();
		$newItems[$hashUrl]['title'] = $tmp['title'];
		if (empty($tmp['time'])) {
		    $tmp['time']=time();
		}
		// convert time to order feed items
		$newItems[$hashUrl]['time']=strtotime($tmp['time']);
		$newItems[$hashUrl]['link'] = $tmp['link'];
		$newItems[$hashUrl]['author'] = $tmp['author'];
		$newItems[$hashUrl]['description'] = $tmp['description'];
		$newItems[$hashUrl]['content'] = $tmp['content'];
		$newItems[$hashUrl]['read'] = 0;
	    }
	}
	return $newItems;
    }

    // return list of items from xmlUrl
    public function getItemsFromXml ($xml)
    {
	$items = array();

	// find feed type RSS, Atom
	$feed = $xml->getElementsByTagName('channel');
	if ($feed->item(0)){
	    // RSS/rdf:RDF feed
	    $feed = $xml->getElementsByTagName('item');
	    $len = $feed->length;
	    for ($i = 0; $i < $len; $i++) {
		$items[$i] = $feed->item($i);
	    }
	} else {
	    $feed = $xml->getElementsByTagName('feed');
	    if ($feed->item(0)){
		// Atom feed
		$feed = $xml->getElementsByTagName('entry');
		$len = $feed->length;
		for ($i = 0; $i < $len; $i++) {
		    $items[$i] = $feed->item($i);
		}
	    } else {
		// unknown feed
	    }
	}

	return $this->formatItems($items);
    }

    // loadXml 
    public function loadXml($xmlUrl)
    {
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

    // update channel
    public function addChannel($xmlUrl)
    {
	$feedHash = MyTool::smallHash($xmlUrl);
	if (!isset($this->_data[$feedHash])){
	    $xml = $this->loadXml($xmlUrl);

	    if (!$xml){
		return false;
	    } else {
		$channel = $this->getChannelFromXml($xml);
		$items = $this->getItemsFromXml($xml);
		foreach (array_keys($items) as $itemHash){
		    if (empty($items[$itemHash]['author'])){
			$items[$itemHash]['author'] = $channel['title'];
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

    // update channel
    public function updateChannelItems($feedHash)
    {
	if (isset($this->_data[$feedHash])){
	    $xmlUrl = $this->_data[$feedHash]['xmlUrl'];
	    $xml = $this->loadXml($xmlUrl);

	    if (!$xml){
		return false;
	    } else {
		// if feed description is empty try to update description
		// (after opml import, description is often empty)
		if (empty($this->_data[$feedHash]['description'])){
		    $this->_data[$feedHash]['description'] = ' ';
		    $channel = $this->getChannelFromXml($xml);
		    $this->_data[$feedHash]['description'] = $channel['description'];
		}
		

		$oldItems = array();
		if (isset($this->_data[$feedHash]['items'])) {
		    $oldItems = $this->_data[$feedHash]['items'];
		}

		$newItems = $this->getItemsFromXml($xml);
		foreach (array_keys($newItems) as $itemHash){
		    if (empty($newItems[$itemHash]['author'])){
			$newItems[$itemHash]['author'] = $this->_data[$feedHash]['title'];
		    }
		    $newItems[$itemHash]['xmlUrl'] = $xmlUrl;
		}

		$this->_data[$feedHash]['items'] = array_merge($newItems, $oldItems);
		$this->_data[$feedHash]['lastUpdate'] = time();

		foreach ($this->_data[$feedHash]['items'] as $itemHash => $item){
		    if ($item['read'] == 1 && !in_array($itemHash,array_keys($newItems))){
			unset($this->_data[$feedHash]['items'][$itemHash]);
		    }
		}

		$this->writeData();
		return true;
	    }
	}
	return false;
    }

    // return feeds hash in folder
    public function getFeedsHashFromFolderHash($folderHash)
    {
	$list = array();
	$folders = $this->getFolders();

	if (isset($folders[$folderHash])){
	    foreach ($this->_data as $feedHash => $feed) {
		if (in_array($folders[$folderHash],$feed['folders'])){
		    $list[] = $feedHash;
		}
	    }
	}
	return array_unique($list);
    }

    // return number of unread items 
    public function getUnread($hash)
    {
	$list = $this->getItems($hash);
	$unread = 0;
	foreach (array_values($list) as $item){
	    if ($item['read']==0){
		$unread++;
	    }
	}
	return $unread;
    }

    // mark read/unread items depending on the hash : item, feed, folder or ''
    public function mark($hash,$read)
    {
	$list = array_keys($this->getItems($hash));
	foreach ($this->_data as $feedHash => $feed) {
	    foreach ($feed['items'] as $itemHash => $item) {
		if (in_array($itemHash,$list)){
		    $this->_data[$feedHash]['items'][$itemHash]['read'] = $read;
		}
	    }
	}
	
	if (!$this->writeData())
	    die("Can't write to ".$pb->file);
    }

    // return type of the hash : item, feed, folder or ''
    public function hashType($hash)
    {
	$type = '';
	if (empty($hash)){
	    $type = '';
	} else {
	    if (isset($this->_data[$hash])){
		// a feed
		$type = 'feed';
	    } else {
		$folders = $this->getFolders();
		if (isset($folders[$hash])){
		    // a folder
		    $type = 'folder';
		}
		else {
		    // should be an item
		    $type = 'item';
		}
	    }
	}
	return $type;
    }

    // return list of items
    // hash may represent an item, a feed, a folder
    // if hash is empty return all items
    public function getItems($hash)
    {
	$list = array();
      
	if (empty($hash)){
	    // all items
	    foreach (array_values($this->_data) as $arrayInfo) {
		$list = array_merge($list,$arrayInfo['items']);
	    }
	} else {
	    if (isset($this->_data[$hash])){
		// a feed
		$list = $this->_data[$hash]['items'];
	    } else {
		$folders = $this->getFolders();
		if (isset($folders[$hash])){
		    // a folder
		    foreach ($this->_data as $feedHash => $arrayInfo) {
			if (in_array($folders[$hash],$arrayInfo['folders'])){
			    $list = array_merge($list,$arrayInfo['items']);
			}
		    }
		}
		else {
		    // should be an item
		    foreach ($this->_data as $xmlUrl => $arrayInfo) {
			if (isset($arrayInfo['items'][$hash])){
			    $list[$hash] = $arrayInfo['items'][$hash];
			}
		    }
		}
	    }
	}
	uasort($list,"compItems");

	return $list;
    }

    // load data file or create one if not exists
    public function loadData()
    {
        if (file_exists($this->file)){
	    $this->_data = unserialize(
                gzinflate(
                    base64_decode(
                        substr(
                            file_get_contents($this->file),
                            strlen(PHPPREFIX),
                            -strlen(PHPSUFFIX)))));
        }
	else{
	    $this->_data[MyTool::smallHash('http://tontof.net/?rss')] = array('title' => 'Tontof',
							   'folders' => array(),
							   'timeUpdate' => 'auto',
							   'lastUpdate' => 0,
							   'htmlUrl' => 'http://tontof.net',
							   'xmlUrl' => 'http://tontof.net/?rss',
							   'description' => 'A simple and smart (or stupid) kriss blog',
							   'items' => array());
	    
	    if (!$this->writeData())
		die("Can't write to ".$pb->file);

	    header('Location: '.MyTool::getUrl());
	    exit();  
	}
    }

    // write data file
    public function writeData()
    {
        $out = PHPPREFIX.
            base64_encode(gzdeflate(serialize($this->_data))).
            PHPSUFFIX;

        if (!@file_put_contents($this->file, $out))
            return false;

        return true;
    }
}


class MyTool
{
    public static function initPHP()
    {
        if (phpversion() < 5){
            die("Argh you don't have PHP 5 ! Please install it right now !");
        }

        error_reporting(E_ALL);
    
        if (get_magic_quotes_gpc()){
            function stripslashes_deep($value)
            {
                return is_array($value)
                    ? array_map('stripslashes_deep', $value)
                    : stripslashes($value);
            }
            $_POST = array_map('stripslashes_deep', $_POST);
            $_GET = array_map('stripslashes_deep', $_GET);
            $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        }

        /* ob_start('ob_gzhandler');
         * register_shutdown_function('ob_end_flush'); */
    }
    public static function isUrl($url)
    {
        $pattern= "/^(https?:\/\/)(w{0}|w{3})\.?[A-Z0-9._-]+\.[A-Z]{2,3}\$/i";
        return preg_match($pattern, $url);
    }

    public static function isEmail($mail)
    {
        $pattern = "/^[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i";
        return (preg_match($pattern, $mail));
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
        $text = preg_replace(array_keys($replace),array_values($replace),$text);
        return $text;
    }

    public static function formatText($text){
        $text = preg_replace_callback(
            '/<code_html>(.*?)<\/code_html>/is',
            create_function(
                '$matches',
                'return htmlspecialchars($matches[1]);'),
            $text);
        $text = preg_replace_callback(
            '/<code_php>(.*?)<\/code_php>/is',
            create_function(
                '$matches',
                'return highlight_string("<?php $matches[1] ?>",true);'),
            $text);
        $text = preg_replace('/<br \/>/is','',$text);

        $text = preg_replace(
            '#(^|\s)([a-z]+://([^\s\w/]?[\w/])*)(\s|$)#im',
            '\\1<a href="\\2">\\2</a>\\4',
            $text);
        $text = preg_replace(
            '#(^|\s)wp:?([a-z]{2}|):([\w]+)#im',
            '\\1<a href="http://\\2.wikipedia.org/wiki/\\3">\\3</a>',
            $text);
        $text = str_replace(
            'http://.wikipedia.org/wiki/',
            'http://www.wikipedia.org/wiki/',
            $text);
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

    public static function rrmdir($dir) {
        if (is_dir($dir) && ($d = @opendir($dir))) {
	    while (($file = @readdir($d)) !== false) {
		if( $file == '.' || $file == '..' ){
		    continue;
		}
		else{
		    unlink($dir.'/'.$file);
		}
	    }
        }
    }

    //http://www.php.net/manual/fr/function.disk-free-space.php#103382
    public static function humanBytes($bytes){
	$si_prefix = array( 'bytes', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
	$base = 1024;
	$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
	return sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
    }

    // Convert post_max_size/upload_max_filesize (eg.'16M') parameters to bytes.
    public static function return_bytes($val)
    {
	$val = trim($val); $last=strtolower($val[strlen($val)-1]);
	switch($last)
	{
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
	}
	return $val;
    }

    // http://sebsauvage.net/wiki/doku.php?id=php:shaarli
    // Try to determine max file size for uploads (POST).
    // Returns an integer (in bytes)
    public static function getMaxFileSize()
    {
	$size1 = MyTool::return_bytes(ini_get('post_max_size'));
	$size2 = MyTool::return_bytes(ini_get('upload_max_filesize'));
	// Return the smaller of two:
	return min($size1,$size2);
    }

    // http://sebsauvage.net/wiki/doku.php?id=php:shaarli
    /* Returns the small hash of a string
       eg. smallHash('20111006_131924') --> yZH23w
       Small hashes:
       - are unique (well, as unique as crc32, at last)
       - are always 6 characters long.
       - only use the following characters: a-z A-Z 0-9 - _ @
       - are NOT cryptographically secure (they CAN be forged)
    */
    function smallHash($text)
    {
	$t = rtrim(base64_encode(hash('crc32',$text,true)),'=');
	$t = str_replace('+','-',$t); // Get rid of characters which need encoding in URLs.
	$t = str_replace('/','_',$t);
	$t = str_replace('=','@',$t);
	return $t;
    }

}


/**
 * Session management class
 * http://www.developpez.net/forums/d51943/php/langage/sessions/
 * http://sebsauvage.net/wiki/doku.php?id=php:session
 * http://sebsauvage.net/wiki/doku.php?id=php:shaarli
 *
 * Features:
 * - Everything is stored on server-side (we do not trust client-side data,
 *   such as cookie expiration)
 * - IP addresses + user agent are checked on each access to prevent session
 *   cookie hijacking (such as Firesheep)
 * - Session expires on user inactivity (Session expiration date is
 *   automatically updated everytime the user accesses a page.)
 * - A unique secret key is generated on server-side for this session
 *   (and never sent over the wire) which can be used
 *   to sign forms (HMAC) (See $_SESSION['uid'] )
 * - Token management to prevent XSRF attacks.
 * 
 * TODO:
 * - log login fail
 * - prevent brute force (ban IP)
 *
 * HOWTOUSE:
 * - Just call Session::init(); to initialize session and
 *   check if connected with Session::isLogged()
 */

class Session
{  
    // If the user does not access any page within this time,
    // his/her session is considered expired (in seconds).
    public static $inactivity_timeout = 3600;
    private static $_instance;
 
    // constructor
    private function __construct()
    {
        // Use cookies to store session.
        ini_set('session.use_cookies', 1);
        // Force cookies for session  (phpsessionID forbidden in URL)
        ini_set('session.use_only_cookies', 1);
        if (!session_id()){
            // Prevent php to use sessionID in URL if cookies are disabled.
            ini_set('session.use_trans_sid', false);
            session_start(); 
        }
    } 

    // initialize session
    public static function init()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Session();
        }
    }

    // Returns the IP address, user agent and language of the client
    // (Used to prevent session cookie hijacking.)
    private static function _allInfos()
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
 
    // Check that user/password is correct and then init some SESSION variables.
    public static function login($login,$password,$login_test,$password_test,
                                 $pValues = array())
    {
        if ($login==$login_test && $password==$password_test){
            // generate unique random number to sign forms (HMAC)
            $_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand());
            $_SESSION['info']=Session::_allInfos(); 
            $_SESSION['username']=$login;
            // Set session expiration.
            $_SESSION['expires_on']=time()+Session::$inactivity_timeout;

	    foreach ($pValues as $key => $value) { 
		$_SESSION[$key] = $value; 
	    } 
            return true;
        }
	Session::logout();
        return false;
    }
 
    // Force logout
    public static function logout()
    {  
	unset($_SESSION['uid'],$_SESSION['info'],$_SESSION['expires_on']);
    } 

    // Make sure user is logged in.
    public static function isLogged()
    {
        if (!isset ($_SESSION['uid'])
            || $_SESSION['info']!=Session::_allInfos()
            || time()>=$_SESSION['expires_on']){
	    Session::logout();
            return false;
        }
        // User accessed a page : Update his/her session expiration date.
        $_SESSION['expires_on']=time()+Session::$inactivity_timeout;  
        return true;
    }

    // Returns a token.
    public static function getToken()
    {
        if (!isset($_SESSION['tokens'])){
            $_SESSION['tokens']=array();
        }
        // We generate a random string and store it on the server side.
        $rnd = sha1(uniqid('',true).'_'.mt_rand());
        $_SESSION['tokens'][$rnd]=1;  
        return $rnd;
    }

    // Tells if a token is ok. Using this function will destroy the token.
    // return true if token is ok.
    public static function isToken($token)
    {
        if (isset($_SESSION['tokens'][$token]))
        {
            unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
            return true; // Token is ok.
        }
        return false; // Wrong token, or already used.
    }
}
?>
<?php
MyTool::initPHP();
Session::init();

$kfc = new Feed_Conf(CONFIG_FILE, FEED_VERSION);
$kfp = new Feed_Page(STYLE_FILE);
$kf = new Feed(DATA_FILE, $kfc);

if (isset($_GET['login'])) {
// Login
    if (!empty($_POST['login'])
        && !empty($_POST['password'])) {
        if (Session::login(
                $kfc->login,
                $kfc->hash,
                $_POST['login'],
                sha1($_POST['password'].$_POST['login'].$kfc->salt))) {
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
	if (isset($_SERVER['HTTP_REFERER'])){
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
    $rurl = ( empty($_SERVER['HTTP_REFERER']) ? '?' : $_SERVER['HTTP_REFERER'] );
    header('Location: '.$rurl);
    exit(); 
} elseif (isset($_GET['config']) && Session::isLogged()) {
// Config
    if (isset($_POST['save'])){
	if (!Session::isToken($_POST['token'])) die('Wrong token.');
        $kfc->hydrate($_POST);
        if (!$kfc->write())
            die("Can't write to ".CONFIG_FILE);

	$rurl = MyTool::getUrl();
	if (isset($_POST['returnurl'])){
	    $rurl = $_POST['returnurl'];
	}
        header('Location: '.$rurl);
        exit();
    } elseif (isset($_POST['cancel'])) {
	if (!Session::isToken($_POST['token'])) die('Wrong token.');

	$rurl = MyTool::getUrl();
	if (isset($_POST['returnurl'])){
	    $rurl = $_POST['returnurl'];
	}
        header('Location: '.$rurl);
        exit();
    } else {
        echo $kfp->htmlPage('Configuration',$kfp->configPage($kfc));
        exit();
    }
} elseif (isset($_GET['import']) && Session::isLogged()) {
// Import
    if (isset($_POST['import'])){
	// If file is too big, some form field may be missing.
        if (!isset($_POST['token'])
	    || (!isset($_FILES))
	    || (isset($_FILES['filetoupload']['size'])
		&& $_FILES['filetoupload']['size']==0)) {
            $rurl = ( empty($_SERVER['HTTP_REFERER']) ? '?' : $_SERVER['HTTP_REFERER'] );
            echo '<script>alert("The file you are trying to upload is probably bigger than what this webserver can accept ('.MyTool::humanBytes(MyTool::getMaxFileSize()).' bytes). Please upload in smaller chunks.");document.location=\''.htmlspecialchars($rurl).'\';</script>';
            exit;
        }
	if (!Session::isToken($_POST['token'])) die('Wrong token.');
        $kf->importOpml();
        exit;
    } else {
        echo $kfp->htmlPage('Import',$kfp->importPage());
        exit();
    }
} elseif (isset($_GET['export']) && Session::isLogged()) {
// Export
    $kf->loadData();
    $kf->exportOpml();
} elseif (isset($_GET['newfeed']) && !empty($_GET['newfeed']) && Session::isLogged()) {
// Add channel
    $kf->loadData();
    if ($kf->addChannel(urldecode($_GET['newfeed']))){
	// Add success
        header('Location: '.MyTool::getUrl().'?show='.MyTool::smallHash(urldecode($_GET['newfeed'])));
        exit();
    } else {
	$returnurl = ( empty($_SERVER['HTTP_REFERER']) ? '?feeds' : $_SERVER['HTTP_REFERER'] );
	echo '<script>alert("The feed you are trying to add already exists or is wrong. Check your feed or try again later.");document.location=\''.htmlspecialchars($returnurl).'\';</script>';
	exit;
	// Add fail
    }
} elseif ((isset($_GET['read']) || isset($_GET['unread'])) && Session::isLogged()) {
// mark all as read : item, feed, folder, all
    $kf->loadData();
    $rurl = ( empty($_SERVER['HTTP_REFERER']) ? '?' : $_SERVER['HTTP_REFERER'] );
    $hash = '';
    $read = 1;
    if (isset($_GET['read'])){
	$hash = substr(trim($_GET['read'], '/'),0,6);
	$read = 1;
    }
    else {
	$hash = substr(trim($_GET['unread'], '/'),0,6);
	$read = 0;
    }
    $kf->mark($hash, $read);
    header('Location: '.$rurl);
    exit;
} elseif (isset($_GET['edit']) && !empty($_GET['edit']) && Session::isLogged()) {
// Edit feed, folder
    $kf->loadData();
    $hash = substr(trim($_GET['edit'], '/'),0,6);
    $type = $kf->hashType($hash);
    switch($type){
    case 'feed':
	if (isset($_POST['save'])){
	    if (!Session::isToken($_POST['token'])) die('Wrong token.');
	    
	    $title = $_POST['title'];
	    $description = $_POST['description'];
	    $folders = array();
	    foreach($_POST['folders'] as $hashFolder){
		$folders[] = $kf->getFolder($hashFolder);
	    }
	    if (!empty($_POST['newfolder'])){
		$folders[] = $_POST['newfolder'];
	    }
	    $timeUpdate = $_POST['timeUpdate'];

	    $kf->editFeed($hash,$title,$description,$folders,$timeUpdate);

	    $rurl = MyTool::getUrl();
	    if (isset($_POST['returnurl'])){
		$rurl = $_POST['returnurl'];
	    }
	    header('Location: '.$rurl);
	    exit();
	} elseif (isset($_POST['delete'])) {
	    if (!Session::isToken($_POST['token'])) die('Wrong token.');

	    $kf->removeFeed($hash);

	    $rurl = MyTool::getUrl();
	    if (isset($_POST['returnurl'])){
		$rurl = $_POST['returnurl'];
	    }
	    header('Location: '.$rurl);
	    exit();
	} elseif (isset($_POST['cancel'])) {
	    if (!Session::isToken($_POST['token'])) die('Wrong token.');

	    $rurl = MyTool::getUrl();
	    if (isset($_POST['returnurl'])){
		$rurl = $_POST['returnurl'];
	    }
	    header('Location: '.$rurl);
	    exit();
	} else {
	    $feed = $kf->getFeed($hash);
	    if (!empty($feed)){
		echo $kfp->htmlPage(strip_tags(MyTool::formatText($kf->kfc->title)),$kfp->editFeedPage($kf,$feed));
		exit;
	    }
	}
	break;
    case 'folder':
	if (isset($_POST['save'])){
	    if (!Session::isToken($_POST['token'])) die('Wrong token.');

	    $oldFolder = $kf->getFolder($hash);
	    $newFolder = $_POST['foldername'];
	    if ($oldFolder != $newFolder){
		$kf->renameFolder($oldFolder,$newFolder);
	    }

	    $rurl = MyTool::getUrl();
	    if (isset($_POST['returnurl'])){
		$rurl = $_POST['returnurl'];
	    }
	    header('Location: '.$rurl);
	    exit();
	} elseif (isset($_POST['cancel'])) {
	    if (!Session::isToken($_POST['token'])) die('Wrong token.');

	    $rurl = MyTool::getUrl();
	    if (isset($_POST['returnurl'])){
		$rurl = $_POST['returnurl'];
	    }
	    header('Location: '.$rurl);
	    exit();
	} else {
	    $folder = $kf->getFolder($hash);
	    echo $kfp->htmlPage(strip_tags(MyTool::formatText($kf->kfc->title)),$kfp->editFolderPage($kf,$folder));
	    exit;
	}
	break;
    case 'item':
    default:
	break;
    }
    echo $kfp->htmlPage(strip_tags(MyTool::formatText($kf->kfc->title)),$kfp->indexPage($kf));
    exit;
} elseif (isset($_GET['update']) && Session::isLogged()) {
// Update
    $kf->loadData();
    $hash = substr(trim($_GET['update'], '/'),0,6);
    $type = $kf->hashType($hash);
    switch($type){
    case 'feed':
	$kf->updateChannelItems($hash);
	break;
    case 'folder':
	$feedsHash = $kf->getFeedsHashFromFolderHash($hash);
	foreach($feedsHash as $feedHash){
	    $kf->updateChannelItems($feedHash);
	}
	break;
    case '':
	$feedsHash = array_keys($kf->getFeeds());
	foreach($feedsHash as $feedHash){
	    $kf->updateChannelItems($feedHash);
	}
	break;
    case 'item':
    default:
	break;
    }
    $sep = empty($hash)?'':'=';
    header('Location: '.MyTool::getUrl().'?show'.$sep.$hash);
    exit();
} elseif (isset($_GET['feeds'])) {
// List feeds
    $kf->loadData();
    echo $kfp->htmlPage('List of the feeds',$kfp->feedsPage($kf));
} elseif (isset($_GET['show']) || isset($_GET['page'])) {
// List items : all, folder, feed or entry
    $hash = '';
    if (!empty($_GET['show'])){
	$hash = substr(trim($_GET['show'], '/'),0,6);
    }
    $page = 1;
    if (isset($_GET['page']) && !empty($_GET['page'])){
	$page = (int)$_GET['page'];
    }
    $kf->loadData();
    echo $kfp->htmlPage(strip_tags(MyTool::formatText($kf->kfc->title)),$kfp->indexPage($kf,$hash,$page));
} else {
// TODO : 
// Magical page, read article by article as Google reader
// using 'n' and 'p' with ajax
    $kf->loadData();
    echo $kfp->htmlPage(strip_tags(MyTool::formatText($kf->kfc->title)),$kfp->indexPage($kf));
    exit();
}