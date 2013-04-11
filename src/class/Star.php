<?php
/**
 * Star class corresponds to a model class for starred items manipulation.
 * Data corresponds to an array of feeds where each feeds contains
 * feed starred articles.
 */
class Star
{
    /**
     * The file containing the feeds information
     */
    public $starsFile = '';

    /**
     * Feed_Conf object
     */
    public $kfc;

    /**
     * Array with stars data
     */
    private $_stars = array();

    /**
     * constructor
     *
     * @param string    $starsFile File to store starred data
     * @param Feed_Conf $kfc      Object corresponding to feed reader config
     */
    public function __construct($starsFile, $kfc)
    {
        $this->kfc = $kfc;
        $this->starsFile = $starsFile;
    }

    /**
     * Load stars file or create one if not exists
     *
     * @return void
     */
    public function loadStars()
    {
        if (empty($this->_stars)) {
            if (file_exists($this->starsFile)) {
                $this->_stars = unserialize(
                    gzinflate(
                        base64_decode(
                            substr(
                                file_get_contents($this->starsFile),
                                strlen(PHPPREFIX),
                                -strlen(PHPSUFFIX)
                                )
                            )
                        )
                    );

                return true;
            } else {
                $this->_stars['feeds'] = array();
                $this->_stars['items'] = array();

                return false;
            }
        }

        // stars already loaded
        return true;
    }

    /**
     * Write stars file
     *
     * @return void
     */
    public function writeStars()
    {
        if ($this->kfc->isLogged()) {
            $write = @file_put_contents(
                $this->starsFile,
                PHPPREFIX
                . base64_encode(gzdeflate(serialize($this->_stars)))
                . PHPSUFFIX
                );
            if (!$write) {
                die("Can't write to " . $this->starsFile);
            }
        }
    }

    /**
     * Return feeds with folders and read/unread information
     * array('title', 'feeds', 'nbUnread', 'nbAll', 'folders')
     *
     * @return array of feeds with read/unread information
     */
    public function getFeedsView()
    {
        $feedsView = array('all' => array('title' => 'All feeds', 'nbAll' => 0, 'feeds' => array()));

        foreach ($this->_stars['feeds'] as $feedHash => $feed) {
            $feedsView['all']['nbAll'] += $feed['nbAll'];
            $feedsView['all']['feeds'][$feedHash] = $feed;
        }

        return $feedsView;
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
        $htmlUrl = $this->_stars['feeds'][$feedHash]['htmlUrl'];
        $url = 'http://getfavicon.appspot.com/'.$htmlUrl.'?defaulticon=bluepng';
        $file = FAVICON_DIR.'/favicon.'.$feedHash.'.ico';

        if ($this->kfc->isLogged()) {
            MyTool::grabToLocal($url, $file);
        }
        if (file_exists($file)) {
            return $file;
        } else {
            return $url;
        }
    }

    /**
     * Get array of items depending on hash and filter
     *
     * @param string $hash   Hash may represent an item, a feed, a folder
     *                       if empty or 'all', return all items
     * @param string $filter In order to specify a filter depending on newItems
     *                       in config, if 'unread' return all unread items.
     *                       if 'old' return stars['items'] (only with hash 'all')
     *                       if 'new' return stars['newItems'] (only with hash 'all')
     *
     * @return array of filtered items depending on hash
     */
    public function getItems($hash = 'all')
    {
        if (empty($hash) or $hash == 'all') {
            return $this->_stars['items'];
        }

        $list = array();

        if (empty($hash) || $hash == 'all') {
            // all items
            foreach ($this->_stars['items'] as $itemHash => $item) {
                    $list[$itemHash] = $item;
            }
        } else {
            if (strlen($hash) === 12) {
                // an item
                if (isset($this->_stars['items'][$hash])) {
                    $list[$hash] = $this->_stars['items'][$hash];
                }
            } else {
                // a feed
                foreach ($this->_stars['items'] as $itemHash => $item) {
                    if (substr($itemHash, 0, 6) === $hash) {
                        $list[$itemHash] = $item;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Load a specific item from feeds, load feed is necessary
     *
     * @param string $itemHash Hash corresponding to an item
     * 
     * @return array
     */
    public function loadItem($itemHash)
    {
        if (isset($this->_stars['items'][$itemHash])) {
            return $this->_stars['items'][$itemHash];
        }

        return array();
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
                $item['time'] = array('list' => utf8_encode(strftime('%H:%M', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
            } else {
                if (strftime('%Y', $time) == strftime('%Y', time())) {
                    $item['time'] = array('list' => utf8_encode(strftime('%b %d', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
                } else {
                    $item['time'] = array('list' => utf8_encode(strftime('%b %d, %Y', $time)), 'expanded' => utf8_encode(strftime('%A %d %B %Y - %H:%M', $time)));
                }
            }

            $item['author'] = htmlspecialchars(html_entity_decode(strip_tags($item['author']), ENT_QUOTES, 'utf-8'), ENT_NOQUOTES);
            $item['title'] = htmlspecialchars(html_entity_decode(strip_tags($item['title']), ENT_QUOTES, 'utf-8'), ENT_NOQUOTES);
            $item['link'] = htmlspecialchars($item['link']);
            $item['via'] = htmlspecialchars($item['via']);
            $item['favicon'] = $this->getFaviconFeed(substr($itemHash, 0, 6));
            $item['xmlUrl'] = htmlspecialchars($item['xmlUrl']);

            return $item;
        }

        return false;
    }

    /**
     * Mark an item as $read
     *
     * @param string  $itemHash
     *
     * @return boolean true if modified false otherwise
     */
    public function markItem($itemHash, $starred, $item = false, $feed = false) {
        $save = false;
        $feedHash = substr($itemHash, 0, 6);
        if (isset($this->_stars['items'][$itemHash]) && $starred === 0) {
            $save = true;
            unset($this->_stars['items'][$itemHash]);
            if (isset($this->_stars['feeds'][$feedHash])){
                $this->_stars['feeds'][$feedHash]['nbAll']--;
                if ($this->_stars['feeds'][$feedHash]['nbAll'] <= 0) {
                    unset($this->_stars['feeds'][$feedHash]);
                }
            }
        } else if ($starred === 1 && $item && $feed) {
            // didn't exists, want to star it
            $save = true;
            $this->_stars['items'][$itemHash] = $item;
            // remove useless item information
            $this->_stars['items'][$itemHash]['time'] = $item['time']['time'];
            unset($this->_stars['items'][$itemHash]['favicon']);
            if (!isset($this->_stars['feeds'][$feedHash])){
                $this->_stars['feeds'][$feedHash] = $feed;
                $this->_stars['feeds'][$feedHash]['nbAll'] = 0;
                // remove useless feed information
                unset($this->_stars['feeds'][$feedHash]['timeUpdate']);
                unset($this->_stars['feeds'][$feedHash]['nbUnread']);
                unset($this->_stars['feeds'][$feedHash]['lastUpdate']);
            }
            $this->_stars['feeds'][$feedHash]['nbAll']++;
        }

        return $save;
    }
}
