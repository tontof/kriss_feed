<?php
/**
 * Star class corresponds to a model class for starred items manipulation.
 * Data corresponds to an array of feeds where each feeds contains
 * feed starred articles.
 */
class Star extends Feed
{
    /**
     * star item file
     */
    public $starItemFile;

    /**
     * constructor
     *
     * @param string    $starsFile File to store starred data
     * @param Feed_Conf $kfc      Object corresponding to feed reader config
     */
    public function __construct($starFile, $starItemFile, $kfc)
    {
        parent::__construct($starFile, '', $kfc);

        $this->starItemFile = $starItemFile;
        if (is_file($this->starItemFile)) {
            include_once $this->starItemFile;
        }
    }


    public function initData()
    {
        $data = array();
        
        $data['feeds'] = array();
        $data['items'] = array();
        $GLOBALS['starredItems'] = array();

        $this->setData($data);
    }

    /**
     * Mark an item as $read
     *
     * @param string  $itemHash
     *
     * @return boolean true if modified false otherwise
     */
    public function markItem($itemHash, $starred, $feed = false, $item = false) {
        $save = false;
        $feeds = $this->getFeeds();
        $feedHash = substr($itemHash, 0, 6);
        $items = $this->getItems();

        if (isset($items[$itemHash]) && $starred === 0) {
            $save = true;
            unset($items[$itemHash]);
            unset($GLOBALS['starredItems'][$itemHash]);
            if (isset($feeds[$feedHash])){
                unset($feeds[$feedHash]['items'][$itemHash]);
                $feeds[$feedHash]['nbAll']--;
                if ($feeds[$feedHash]['nbAll'] <= 0) {
                    unset($feeds[$feedHash]);
                }
            }
        } else if (!isset($items[$itemHash]) && $starred === 1) {
            // didn't exists, want to star it
            $save = true;

            $items[$itemHash] = [time(), 0];

            if (!isset($feeds[$feedHash])){
                $feed['nbAll'] = 0;
                $feed['items'] = array();
                // remove useless feed information
                unset($feed['timeUpdate']);
                unset($feed['nbUnread']);
                unset($feed['lastUpdate']);
                unset($feed['foldersHash']);
                unset($feed['error']);
                $feeds[$feedHash] = $feed;
            }
            
            $feeds[$feedHash]['items'][$itemHash] = $item;
            $feeds[$feedHash]['nbAll']++;

            $GLOBALS['starredItems'][$itemHash] = 1;
        }

        if ($save) {
            arsort($items);
            $this->setItems($items);
            $this->setFeeds($feeds);
        }

        return $save;
    }
    
    public function writeStarredItems()
    {
        if ($this->kfc->isLogged()) {
            $data = array('starredItems');

            $out = '<?php '."\n";
            foreach ($data as $key) {
                $out .= '$GLOBALS[\''.$key.'\'] = '.var_export($GLOBALS[$key],true).';'."\n";
            }
            
            
            if (!@file_put_contents($this->starItemFile, $out)) {
                die("Can't write to ".$this->starItemFile." check permissions");
            }
        }
    }

    public function writeData()
    {
        parent::writeData();
        $this->writeStarredItems();
    }
}
