<?php
/**
 * Feed class corresponds to a model class for feed manipulation.
 * Data corresponds to an array of feeds where each feeds contains
 * feed articles.
 */
class Feed
{
    /**
     * The file containing the feeds information
     */
    public $dataFile = '';

    /**
     * The directory containing the feed entries
     */
    public $cacheDir = '';

    /**
     * Feed_Conf object
     */
    public $kfc;

    /**
     * Array with data
     */
    private $_data = array();

    /**
     * Array with the headers parsed from the XHR call
     */
    private $_headers = array();

    /**
     * constructor
     *
     * @param string    $dataFile File to store feed data
     * @param Feed_Conf $kfc      Object corresponding to feed reader config
     */
    public function __construct($dataFile, $cacheDir, $kfc)
    {
        $this->kfc = $kfc;
        $this->dataFile = $dataFile;
        $this->cacheDir = $cacheDir;
    }

    /**
     * Return data
     *
     * @return array List of feeds, folders and items stored into data
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Set data
     *
     * @return void
     */
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

    /**
     * Load data file or create one if not exists
     *
     * @return void
     */
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

    /**
     * Write data file
     *
     * @return void
     */
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

    /**
     * Return all feeds
     *
     * @return array of feeds
     */
    public function getFeeds()
    {
        return $this->_data['feeds'];
    }

    /**
     * Sort alphabetically  list of feeds
     */
    public function sortFeeds()
    {
        uasort(
            $this->_data['feeds'],
            'Feed::sortByTitle'
            );
    }

    /**
     * Sort by order  list of folder
     */
    public function sortFolders()
    {
        uasort(
            $this->_data['folders'],
            'Feed::sortByOrder'
            );
    }

    /**
     * Return feeds with folders and read/unread information
     * array('title', 'feeds', 'nbUnread', 'nbAll', 'folders')
     *
     * @return array of feeds with read/unread information
     */
    public function getFeedsView()
    {
        $feedsView = array('all' => array('title' => 'All feeds', 'nbUnread' => 0, 'nbAll' => 0, 'feeds' => array()), 'folders' => array());
        
        foreach ($this->_data['feeds'] as $feedHash => $feed) {
            if (isset($feed['error'])) {
                $feed['error'] = $this->getError($feed['error']);
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

    /**
     * Return a particular feed from a given hash feed
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return array|false Feed array if exists, false otherwise
     */
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

    /**
     * Return a link to favicon
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return string that corresponds to favicon of feed
     */
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

    /**
     * Return html url of the feed
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return string|false html url if exists, false otherwise
     */
    public function getFeedHtmlUrl($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash]['htmlUrl'])) {
            return $this->_data['feeds'][$feedHash]['htmlUrl'];
        }

        return false;
    }

    /**
     * Return title of a particular feed from a given hash feed
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return array|false Feed array if exists, false otherwise
     */
    public function getFeedTitle($feedHash)
    {
        if (isset($this->_data['feeds'][$feedHash]['title'])) {
            return $this->_data['feeds'][$feedHash]['title'];
        }

        return false;
    }

    /**
     * Load a specific feed
     */
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

    /**
     * Edit a feed from given information
     *
     * @param string $feedHash    Hash corresponding to a feed
     * @param string $title       New title of the feed
     * @param string $description New description of the feed
     * @param array  $folders     List of associated folders to the feed
     * @param string $timeUpdate  Update config ('auto', 'max' or number of min)
     */
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

    /**
     * Remove feed from a given hash feed
     *
     * @param string $feedHash Hash corresponding to a feed
     */
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

    /**
     * Write feed file
     *
     * @return void
     */
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

    /**
     * Order feeds to update active feeds first
     */
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

    /**
     * return list of feeds hash which are in a specific folder
     *
     * @param string $folderHash Hash corresponding to a folder
     *
     * @return array List of feed hashes associated to the given folder
     */
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

    /**
     * Return list of folders used to categorize feeds
     *
     * @return array List of folders info (feedHash => (title, isOpen))
     */
    public function getFolders()
    {
        return $this->_data['folders'];
    }

    /**
     * Return folder from a given folder hash
     *
     * @param string $hash Hash corresponding to a folder
     *
     * @return array|false array of folder if exists, false otherwise
     */
    public function getFolder($folderHash)
    {
        if (isset($this->_data['folders'][$folderHash])) {
            return $this->_data['folders'][$folderHash];
        }

        return false;
    }

    /**
     * Add new folder
     *
     * @param string $folderTitle New folder title
     */
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

    /**
     * Rename folder into items (delete folder is newFolder is empty)
     *
     * @param string $oldFolder Old folder name
     * @param string $newFolder New folder name
     */
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

    /**
     * Toggle isOpen folder to open or close a folder
     *
     * @param string $hash Hash corresponding to a folder
     */
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

    /**
     * Return folder title from a given folder hash
     *
     * @param string $hash Hash corresponding to a folder
     *
     * @return string|false string of folder title if exists, false otherwise
     */
    public function getFolderTitle($folderHash)
    {
        if (isset($this->_data['folders'][$folderHash])) {
            return $this->_data['folders'][$folderHash]['title'];
        }

        return false;
    }

    /**
     * Get array of items depending on hash and filter
     *
     * @param string $hash   Hash may represent an item, a feed, a folder
     *                       if empty or 'all', return all items
     * @param string $filter In order to specify a filter depending on newItems
     *                       in config, if 'unread' return all unread items.
     *                       if 'old' return data['items'] (only with hash 'all')
     *                       if 'new' return data['newItems'] (only with hash 'all')
     *
     * @return array of filtered items depending on hash
     */
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

    /**
     * Load a specific item from feeds, load feed is necessary
     *
     * @param string $itemHash Hash corresponding to an item
     *
     * @return array
     */
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

    /**
     * Load a specific item from feeds, load feed is necessary
     *
     * @param string $itemHash Hash corresponding to an item
     *
     * @return false|array corresponding to itemHash, false otherwise
     */
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

    /**
     * update Items with new Items
     *
     * @return bool true is modification, false otherwise
     */
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

    /**
     * Format xml channel into array
     *
     * @param DOMDocument $channel DOMDocument of the channel feed
     *
     * @return array Array with extracted information channel
     */
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

    /**
     * return channel from xmlUrl
     *
     * @param DOMDocument $xml DOMDocument of the feed
     *
     * @return array Array with extracted information channel
     */
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

    /**
     * format items into array
     *
     * @param DOMNodeList $items   DOMNodeList of items in a feed
     * @param array       $formats List of information to extract
     *
     * @return array List of items with information
     */
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

    /**
     * Return array of items from xml
     *
     * @param DOMDocument $xml DOMDocument where to extract items
     *
     * @return array Array of items extracted from the DOMDocument
     */
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

    /**
     * Search a namespaceURI into tags
     * (used when namespaceURI are not defined in the root tag)
     *
     * @param DOMNode $feed DOMNode to look into
     * @param string  $name String of the namespace to look for
     *
     * @return string The namespaceURI or empty string if not found
     */
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

    /**
     * Add a namespaceURI when format corresponds to a rdf tag.
     *
     * @param array   $formats Array of formats
     * @param DOMNode $feed    DOMNode corresponding to the channel root
     *
     * @return array Array of new formated format with namespaceURI
     */
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

    /** 
     * loadUrl
     *
     * @param string url to load
     * @param $opt to create context
     *
     * @return string content
     */
    public function loadUrl($url, $opts = array()){
        $ch = curl_init($url);
        if (!empty($opts)) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $opts['http']['user_agent']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['http']['headers']);
        }
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $output = $this->curl_exec_follow($ch);

        curl_close($ch);

        return $output;
    }

    /**
     * @param resource $ch curl
     * @param integer  $redirects max number of redirects
     * @param boolean  $curloptHeader
     *
     * http://stackoverflow.com/questions/2511410/curl-follow-location-error
     */
    public function curl_exec_follow(&$ch, $redirects = 20, $curloptHeader = false) {
        if ((!ini_get('open_basedir') && !ini_get('safe_mode')) || $redirects < 1) {
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirects > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, readHeader));

            $data = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($data, 0, $header_size);

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $data = substr($data, strpos($data, "\r\n\r\n")+4);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, readHeader));

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

                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($data, 0, $header_size);
                if (!preg_match('/^(?:Location|URI): ([^\r\n]*)[\r\n]*$/im', $header, $matches)) {
                    break;
                }
                curl_setopt($ch, CURLOPT_URL, $matches[1]);
            } while (--$redirects);
            if (!$redirects)
                trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
            if (!$curloptHeader)
                $data = substr($data, strpos($data, "\r\n\r\n")+4);

        }

        return array(
            'data' => $data,
            'header' => $header,
            'code' => $code,
            'error' => curl_error(),
            'isnew' => $code != 304, // really new (2XX) and errors (4XX and 5XX) are considered new
            'etag' => $this->_headers['etag'],
            'last-modified' => $this->_headers['last-modified']
        );
    }

    public function readHeader($url, $str) {
        if (preg_match('/^ETag: ([^\r\n]*)[\r\n]*$/im', $str, $matches)) {
            $this->_headers['etag'] = $matches[1];
        } else if (preg_match('/^Last-Modified: ([^\r\n]*)[\r\n]*$/im', $str, $matches)) {
            $this->_headers['last-modified'] = $matches[1];
        }

        return strlen($str);
    }

    /**
     * Load an xml file through HTTP
     *
     * @param string $xmlUrl String corresponding to the XML URL
     *
     * @return DOMDocument DOMDocument corresponding to the XML URL
     */
    public function loadXml($xmlUrl, &$etag, &$lastModified)
    {
        // reinitialize cache headers
        $this->_headers = array();

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

        // http headers
        $opts['http']['headers'] = array();
        if (!empty($lastModified)) {
            $opts['http']['headers'][] = 'If-Modified-Since: ' . $lastModified;
        }
        if (!empty($etag)) {
            $opts['http']['headers'][] = 'If-None-Match: ' . $etag;
        }

        $document = new DOMDocument();

        if (in_array('curl', get_loaded_extensions())) {
            $output = $this->loadUrl($xmlUrl, $opts);
            if ($output['isnew']) {
                $etag = $output['etag'];
                $lastModified = $output['last-modified'];
            }

            $document->loadXML($output['data']);
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

    /**
     * add channel
     *
     * @param string $xmlUrl String corresponding to the XML URL
     *
     * @return true|false True if add is success, false otherwise
     */
    public function addChannel($xmlUrl)
    {
        $feedHash = MyTool::smallHash($xmlUrl);
        if (!isset($this->_data['feeds'][$feedHash])) {
            $xml = $this->loadXml($xmlUrl, $this->_data['feeds'][$feedHash]['etag'], $this->_data['feeds'][$feedHash]['lastModified']);
            if (empty($this->_data['feeds'][$feedHash]['etag'])) {
                unset($this->_data['feeds'][$feedHash]['etag']);
            }

            if (empty($this->_data['feeds'][$feedHash]['lastModified'])) {
                unset($this->_data['feeds'][$feedHash]['lastModified']);
            }

            if (!$xml) {
                return false;
            } else {
                $channel = $this->getChannelFromXml($xml);
                $items = $this->getItemsFromXml($xml);

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
                $channel['etag'] = $this->_data['feeds'][$feedHash]['etag'];
                $channel['lastModified'] = $this->_data['feeds'][$feedHash]['lastModified'];

                $this->_data['feeds'][$feedHash] = $channel;
                $this->_data['needSort'] = true;

                $this->writeFeed($feedHash, $items);

                return true;
            }
        }

        return false;
    }

    /**
     * Calculate updates depending on timeUpdate feed information
     *
     * @param array $feed Array of a feed information
     *
     * @return integer Number of minutes between each update
     */
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

    /**
     * Check if feed needs an update
     *
     * @param array $feed Array of a feed information
     *
     * @return true|false True if feed needs update, false otherwise
     */
    public function needUpdate($feed)
    {
        $diff = (int) (time()-$feed['lastUpdate']);
        if ($diff > $this->getTimeUpdate($feed) * 60) {
            return true;
        }

        return false;
    }

    /**
     * Update channel and return feed information error, lastUpdate
     * and newItems
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return array feed information, error, lastUpdate and newItems
     */
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
        $xml = $this->loadXml($xmlUrl, $this->_data['feeds'][$feedHash]['etag'], $this->_data['feeds'][$feedHash]['lastModified']);
        if (empty($this->_data['feeds'][$feedHash]['etag'])) {
            unset($this->_data['feeds'][$feedHash]['etag']);
        }
        
        if (empty($this->_data['feeds'][$feedHash]['lastModified'])) {
            unset($this->_data['feeds'][$feedHash]['lastModified']);
        }

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

    /**
     * Update channel and return feed information error, lastUpdate
     * and newItems
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return array feed information, error, lastUpdate and newItems
     */
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

    /**
     * Mark all items as $read
     *
     * @param integer $read
     *
     * @return boolean true if modified false otherwise
     */
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

    /**
     * Mark an item as $read
     *
     * @param string  $itemHash
     * @param integer $read
     *
     * @return boolean true if modified false otherwise
     */
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

    /**
     * Mark list of feeds as $read
     *
     * @param string  $feedsHash
     * @param integer $read
     *
     * @return boolean true if modified false otherwise
     */
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
        
    /**
     * Mark items read/unread depending on the hash : item, feed, folder or ''
     * force is used for not overwriting keep unread when mark as read.
     *
     * @param string  $hash  Hash may represent an item, a feed, a folder
     * @param integer $read  KEEPUNREAD, UNREAD, READ
     * @param boolean $force Force read setting
     *
     * @return boolean true if modified false otherwise
     */
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

        
    

    /**
     * Get type of a hash : item, feed, folder or ''
     *
     * @param string $hash Hash may represent an item, a feed, a folder
     *
     * @return string String corresponding to '' is hash is empty, 'feed'
     *                if hash corresponds to a feed hash, 'folder' if hash
     *                corresponds to a folder hash or 'item' otherwise
     *                (hash may not correspond to an item hash)
     */
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

    /** 
     * Sort function by order (feed, folder)
     * Used with uasort
     *
     * @param mixed $a a feed or a folder
     * @param mixed $b a feed or a folder
     */
    public static function sortByOrder($a, $b) {
        return strnatcasecmp($a['order'], $b['order']);
    }

    /** 
     * Sort function by title (feed, folder)
     * Used with uasort
     *
     * @param mixed $a a feed or a folder
     * @param mixed $b a feed or a folder
     */
    public static function sortByTitle($a, $b) {
        return strnatcasecmp($a['title'], $b['title']);
    }

    /**
     * Get human readable error
     *
     * @param integer $error Number of error occured during a feed update
     *
     * @return string String of the corresponding error
     */
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

