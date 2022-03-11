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
                . PHPSUFFIX,
                LOCK_EX
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
        $feedsView = array('all' => array('title' => Intl::msg('All feeds'), 'nbUnread' => 0, 'nbAll' => 0, 'feeds' => array()), 'folders' => array());
        
        foreach ($this->_data['feeds'] as $feedHash => $feed) {
            if (isset($feed['error'])) {
                $feed['error'] = $feed['error'];
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
     * TODO: Try using Bronco one :
     * http://www.warriordudimanche.net/article164/hands-up-give-me-your-favicon-right-now
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return string that corresponds to favicon of feed
     */
    public function getFaviconFeed($feedHash)
    {
        $htmlUrl = $this->_data['feeds'][$feedHash]['htmlUrl'];
        //$url = 'https://s2.googleusercontent.com/s2/favicons?domain='.parse_url($htmlUrl, PHP_URL_HOST);
        $url = 'https://icons.duckduckgo.com/ip3/'.parse_url($htmlUrl, PHP_URL_HOST).'.ico';
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
                . PHPSUFFIX,
                LOCK_EX
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
            if (date('Y-m-d', $time) == date('Y-m-d', time())) {
                // Today
                $item['time'] = array('time' => $time, 'list' => date('H:i', $time), 'expanded' => date('l jS F Y H:i:s', $time));
            } else {
                if (date('Y', $time) == date('Y', time())) {
                    $item['time'] = array('time' => $time, 'list' => date('F d', $time), 'expanded' => date('l jS F Y H:i:s', $time));
                } else {
                    $item['time'] = array('time' => $time, 'list' => date('Y-m-d', $time), 'expanded' => date('l jS F Y H:i:s', $time));
                }
            }
            if (isset($this->_data['items'][$itemHash])) {
                $item['read'] = is_array($this->_data['items'][$itemHash])?$this->_data['items'][$itemHash][1]:0;
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
            if (empty($item['title'])) {
                $item['title'] = $item['link'];
            }
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

    public function initFeedCache($feed, $force)
    {
        if (!empty($feed)) {
            if ($force) {
                $feed['etag'] = '';
                $feed['lastModified'] = '';
            }

            $headers = [];
            foreach(MyTool::$opts['http']['headers'] as $header) {
                if (strpos($header, 'If-Modified-Since:') === false && strpos($header, 'If-None-Match:') === false) {
                    $headers[] = $header;
                }
            }
            MyTool::$opts['http']['headers'] = $headers;
            if (!empty($feed['lastModified'])) {
                MyTool::$opts['http']['headers'][] = 'If-Modified-Since: ' . $feed['lastModified'];
            }
            if (!empty($feed['etag'])) {
                MyTool::$opts['http']['headers'][] = 'If-None-Match: ' . $feed['etag'];
            }
        }
        
        return $feed;
    }

    public function updateFeedCache($feed, $outputUrl)
    {
        // really new (2XX) and errors (4XX and 5XX) are considered new
        if ($outputUrl['code'] != 304) {
            if (preg_match('/^ETag: ([^\r\n]*)[\r\n]*$/im', $outputUrl['header'], $matches)) {
                $feed['etag'] = $matches[1];
            }
            if (preg_match('/^Last-Modified: ([^\r\n]*)[\r\n]*$/im', $outputUrl['header'], $matches)) {
                $feed['lastModified'] = $matches[1];
            }
        }

        if (empty($feed['etag'])) {
            unset($feed['etag']);
        }
        if (empty($feed['lastModified'])) {
            unset($feed['lastModified']);
        }

        return $feed;
    }

    public function updateFeedFromDom($feed, $dom) {
        if (empty($feed)) {
            // addFeed
            $feed = Rss::getFeed($dom);

            if (!MyTool::isUrl($feed['htmlUrl'])) {
                $feed['htmlUrl'] = ' ';
            }
            if (empty($feed['description'])) {
                $feed['description'] = ' ';
            }
            $feed['foldersHash'] = array();
            $feed['timeUpdate'] = 'auto';            
        } else if (empty($feed['description']) || empty($feed['htmlUrl'])) {
            // if feed description/htmlUrl is empty try to update
            // (after opml import, description/htmlUrl are often empty)
            $rssFeed = Rss::getFeed($dom);
            if (empty($feed['description'])) {
                if (empty($rssFeed['description'])) {
                    $rssFeed['description'] = ' ';
                }
                $feed['description'] = $rssFeed['description'];
            }
            if (empty($feed['htmlUrl'])) {
                if (empty($rssFeed['htmlUrl'])) {
                    $rssFeed['htmlUrl'] = ' ';
                }
                $feed['htmlUrl'] = $rssFeed['htmlUrl'];
            }
        }

        return $feed;
    }

    private function showEnclosure($enclosure) {
        $path = parse_url($enclosure, PHP_URL_PATH);
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $link = '<a href="'.$enclosure.'">'.$enclosure.'</a>';
        switch(strtolower($ext)) {
        case '':
            if (strpos($enclosure, 'https://www.youtube.com') === 0) {
                $link = '<iframe src="'.str_replace('/v/','/embed/', $enclosure).'" width="640" height="360" allowfullscreen></iframe>';
            }
            break;
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            $link = '<img src="'.$enclosure.'">';
            break;
        case 'mp3':
        case 'oga':
        case 'wav':
            $link = '<audio controls><source src="'.$enclosure.'">'.$link.'</audio>';
            break;
        case 'mp4':
        case 'ogg':
        case 'webm':
            $link = '<video controls><source src="'.$enclosure.'">'.$link.'</video>';
            break;
        }

        return $link;
    }

    public function updateItemsFromDom($dom) {
        $items = Rss::getItems($dom);

        $newItems = array();
        foreach($items as $item) {
            if (!empty($item['link'])) {
                $hashUrl = MyTool::smallHash($item['link']);
                $newItems[$hashUrl] = array();
                $newItems[$hashUrl]['title'] = empty($item['title'])?$item['link']:$item['title'];
                $newItems[$hashUrl]['time']  = strtotime($item['time'])
                    ? strtotime($item['time'])
                    : time();
                if (MyTool::isUrl($item['via']) &&
                    parse_url($item['via'], PHP_URL_HOST)
                    != parse_url($item['link'], PHP_URL_HOST)) {
                    $newItems[$hashUrl]['via'] = $item['via'];
                } else {
                    $newItems[$hashUrl]['via'] = '';
                }
                $newItems[$hashUrl]['link'] = $item['link'];
                $newItems[$hashUrl]['author'] = $item['author'];
                mb_internal_encoding("UTF-8");
                $newItems[$hashUrl]['description'] = mb_substr(
                    strip_tags($item['description']), 0, 500
                );
                if(!empty($item['enclosure'])) {
                    foreach($item['enclosure'] as $enclosure) {
                        $item['content'] .= '<br><br>'.$this->showEnclosure($enclosure);
                    }
                }
                if(!empty($item['thumbnail'])) {
                        $item['content'] .= '<br>'.$this->showEnclosure($item['thumbnail']);
                }
                $newItems[$hashUrl]['content'] = $item['content'];
            }
        }

        return $newItems;
    }

    /**
     * Load an xml file through HTTP
     *
     * @param string  $xmlUrl String corresponding to the XML URL
     * @param array   $feed   Feed
     * @param array   $items  Items
     * @param boolean $force  Force update
     *
     * @return array containing feed and items
     */
    public function loadRss($xmlUrl, $feed = array(), $force = false)
    {
        $items = array();
        $feed = $this->initFeedCache($feed, $force);

        if( !ini_get('safe_mode') && isset(MyTool::$opts['http']['timeout'])){
            set_time_limit(MyTool::$opts['http']['timeout']+1);
        } 
        $outputUrl = MyTool::loadUrl($xmlUrl);
        
        if (!empty($outputUrl['error'])) {
            $feed['error'] = $outputUrl['error'];
        } else if (empty($outputUrl['data'])) {
            if ($outputUrl['code'] != 304) { // 304 Not modified
                $feed['error'] = Intl::msg('Empty output data');;
            }
        } else {
            $outputDom = Rss::loadDom($outputUrl['data']);
            if (!empty($outputDom['error'])) {
                $feed['error'] = $outputDom['error'];
            } else {
                unset($feed['error']);
                $feed = $this->updateFeedFromDom($feed, $outputDom['dom']);
                $feed = $this->updateFeedCache($feed, $outputUrl);
                $items = $this->updateItemsFromDom($outputDom['dom']);
            }
        }
        $feed['lastUpdate'] = time();

        return array(
            'feed' => $feed,
            'items' => $items,
        );
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
        $error = '';
        if (!isset($this->_data['feeds'][$feedHash])) {
            $output = $this->loadRss($xmlUrl);

            if (empty($output['feed']['error'])) {
                $output['feed']['xmlUrl'] = $xmlUrl;
                $output['feed']['nbUnread'] = count($output['items']);
                $output['feed']['nbAll'] = count($output['items']);
                $this->_data['feeds'][$feedHash] = $output['feed'];
                $this->_data['needSort'] = true;

                $items = $output['items'];
                foreach (array_keys($items) as $itemHash) {
                    if (empty($items[$itemHash]['via'])) {
                        $items[$itemHash]['via'] = $output['feed']['htmlUrl'];
                    }
                    if (empty($items[$itemHash]['author'])) {
                        $items[$itemHash]['author'] = $output['feed']['title'];
                    } else {
                        $items[$itemHash]['author']
                            = $output['feed']['title'] . ' ('
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
                $this->writeFeed($feedHash, $items);
            } else {
                $error = $output['feed']['error'];
            }
        } else {
            $error = Intl::msg('Duplicated feed');
        }

        return array('error' => $error);
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
    public function updateChannel($feedHash, $force = false)
    {
        $error = '';
        $newItems = array();

        if (!isset($this->_data['feeds'][$feedHash])) {
            return array(
                'error' => Intl::msg('Unknown feedhash'),
                'newItems' => $newItems
            );
        }

        $xmlUrl = $this->_data['feeds'][$feedHash]['xmlUrl'];

        $output = $this->loadRss($xmlUrl, $this->_data['feeds'][$feedHash], $force);
        // Update feed information
        $this->_data['feeds'][$feedHash] = $output['feed'];
        if (empty($output['feed']['error'])) {
            $this->loadFeed($feedHash);
            $oldItems = array();
            if (!empty($this->_data['feeds'][$feedHash]['items']) && is_array($this->_data['feeds'][$feedHash]['items'])) {
                $oldItems = $this->_data['feeds'][$feedHash]['items'];
            }

            $lastTime = 0;
            if (isset($this->_data['feeds'][$feedHash]['lastTime'])) {
                $lastTime = $this->_data['feeds'][$feedHash]['lastTime'];
            }
            if (!empty($oldItems)) {
                $lastTime = current($oldItems);
                $lastTime = $lastTime['time'];
            }
            $newLastTime = $lastTime;
        
            $rssItems = $output['items'];
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
                    $error = Intl::msg('Items may have been missed since last update');
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
            }

            if (empty($this->_data['feeds'][$feedHash]['items'])) {
                $this->_data['feeds'][$feedHash]['lastTime'] = $newLastTime;
            } else {
                unset($this->_data['feeds'][$feedHash]['lastTime']);
            }
            $this->writeFeed($feedHash, $this->_data['feeds'][$feedHash]['items']);
            unset($this->_data['feeds'][$feedHash]['items']);

        } else {
            $error = $output['feed']['error'];
        }

        if (!empty($error)) {
            $this->_data['feeds'][$feedHash]['error'] = $error;
        }

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
     * @return empty string
     */
    public function updateFeedsHash($feedsHash, $force, $format = '')
    {
        $i = 0;
        $errorCount = 0;
        $noUpdateCount = 0;
        $successCount = 0;
        $nbItemsAdded = 0;

        $feedsHash = $this->orderFeedsForUpdate($feedsHash);
        $nbFeeds = count($feedsHash);

        ob_end_flush();
        if (ob_get_level() == 0) ob_start();

        if ($format === 'html') {
            echo '<table class="table table-striped">
              <thead>
                <tr>
                  <th>#</th>
                  <th>'.Intl::msg('Feed').'</th>
                  <th>'.Intl::msg('New items').'</th>
                  <th>'.Intl::msg('Time').'</th>
                  <th>'.Intl::msg('Status').'</th>
                </tr>
              </thead>
              <tbody>';
        }
        $start = microtime(true);
        $lastTime = $start;
        foreach ($feedsHash as $feedHash) {
            $i++;
            $feed = $this->getFeed($feedHash);
            $strBegin = "\n".'<tr><td>'.str_pad($i.'/'.$nbFeeds, 7, ' ', STR_PAD_LEFT).'</td><td> <a href="?currentHash='.$feedHash.'">'.substr(str_pad($feed['title'], 50), 0, 50).'</a> </td><td>';
            if ($format === 'html') {
                echo str_pad($strBegin, 4096);
                ob_flush();
                flush();
            }

            $strEnd = '';
            $time = microtime(true) - $lastTime;
            $lastTime += $time;
            if ($force or $this->needUpdate($feed)) {
                $info = $this->updateChannel($feedHash, $force);
                $countItems = count($info['newItems']);
                $strEnd .= '<span class="text-success">'.str_pad($countItems, 3, ' ', STR_PAD_LEFT).'</span> </td><td>'.str_pad(number_format($time, 1), 6, ' ', STR_PAD_LEFT).'s </td><td>';
                if (empty($info['error'])) {
                    $strEnd .= Intl::msg('Successfully updated').'</td></tr>';
                    $successCount++;
                    $nbItemsAdded += $countItems;
                } else {
                    $strEnd .= '<span class="text-error">'.$info['error'].'</span></td></tr>';
                    $errorCount++;
                }
            } else {
                $strEnd .= str_pad('0', 3, ' ', STR_PAD_LEFT).' </td><td>'.str_pad(number_format($time, 1), 6, ' ', STR_PAD_LEFT).'s </td><td><span class="text-warning">'.Intl::msg('Already up-to-date').'</span></td></tr>';
                $noUpdateCount++;
            }
            if ($format==='html') {
                echo str_pad($strEnd, 4096);
                ob_flush();
                flush();
            } else {
                echo strip_tags($strBegin.$strEnd);
            }
        }

        // summary
        $strBegin = "\n".'<tr><td></td><td> '.Intl::msg('Total:').' '.$nbFeeds.' '.($nbFeeds > 1 ? Intl::msg('items') : Intl::msg('item')).'</td><td>';
        if ($format === 'html') {
            echo str_pad($strBegin, 4096);
            ob_flush();
            flush();
        }

        $strEnd = str_pad($nbItemsAdded, 3, ' ', STR_PAD_LEFT).' </td><td>'.str_pad(number_format(microtime(true) - $start, 1), 6, ' ', STR_PAD_LEFT).'s </td><td>'
            .'<span class="text-success">'.$successCount.' '.($successCount > 1 ? Intl::msg('Successes') : Intl::msg('Success')).' </span><br />'
            .'<span class="text-warning">'.$noUpdateCount.' '.Intl::msg('Up-to-date').' </span><br />'
            .'<span class="text-error">'.$errorCount.' '.($errorCount > 1 ? Intl::msg('Errors') : Intl::msg('Error')).' </span></td></tr>';
        if ($format === 'html') {
            echo str_pad($strEnd, 4096);
            ob_flush();
            flush();
        } else {
            echo strip_tags($strBegin.$strEnd);
        }

        if ($format === 'html') {
            echo '</tbody></table>';
        }

        return '';
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
}

