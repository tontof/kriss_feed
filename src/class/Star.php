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

        $this->setData($data);
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
        if (isset($this->_data['items'][$itemHash]) && $starred === 0) {
            $save = true;
            unset($this->_data['items'][$itemHash]);
            if (isset($this->_data['feeds'][$feedHash])){
                $this->_data['feeds'][$feedHash]['nbAll']--;
                if ($this->_data['feeds'][$feedHash]['nbAll'] <= 0) {
                    unset($this->_data['feeds'][$feedHash]);
                }
            }
        } else if ($starred === 1 && $item && $feed) {
            // didn't exists, want to star it
            $save = true;
            $this->_data['items'][$itemHash] = $item;
            // remove useless item information
            $this->_data['items'][$itemHash]['time'] = $item['time']['time'];
            unset($this->_data['items'][$itemHash]['favicon']);
            if (!isset($this->_data['feeds'][$feedHash])){
                $feed['nbAll'] = 0;
                // remove useless feed information
                unset($feed['timeUpdate']);
                unset($feed['nbUnread']);
                unset($feed['lastUpdate']);
            } else {
                $feedthis->_data['feeds'][$feedHash]['nbAll']++;
            }
            $this->updateFeed($feedHash, $feed);
        }

        return $save;
    }

    /**
     * Mark an item as $starred
     *
     * @param string  $itemHash
     * @param integer $starred
     *
     * @return boolean true if modified false otherwise
     */
    public function markItemAsStarred($itemHash, $starred) {
        $save = false;
        
        if (!isset($this->_data['items'][$itemHash]) && $starred == 1){
            $save = true;
            $this->_data['items'][$itemHash] = time();
        } elseif (isset($this->_data['items'][$itemHash]) && $starred == 0) {
            $save = true;
            unset($this->_data['items'][$itemHash]);
        }

        return $save;
    }

    
    public function writeStarredItems()
    {
        $data = array('starredItems');
        
        $out = '<?php ';
        foreach ($data as $key) {
                $out .= '<?php $GLOBALS[\''.$key.'\']='.var_export($GLOBALS[$key],true).';';
        }

        if (!@file_put_contents($this->starItemFile, $out)) {
            die("Can't write to ".$this->starItemFile." check permissions");
        }
    }
}
