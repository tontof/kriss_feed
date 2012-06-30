<?php
/**
 * Feed class corresponds to a model class for feed manipulation.
 * Data corresponds to an array of feeds where each feeds contains
 * feed articles.
 */
class Feed
{
    /**
     * The file containing the feed entries
     */
    public $file = '';

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
    public function __construct($dataFile, $kfc)
    {
        $this->kfc = $kfc;
        $this->file = $dataFile;
    }

    /**
     * Import feed from opml file (as exported by google reader,
     * tiny tiny rss, rss lounge... using 
     */
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

    /**
     * Export feeds to an opml file
     */
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

    /** 
     * Convert opml xml node into array for import
     * http://www.php.net/manual/en/class.domdocument.php#101014
     *
     * @param DOMDocument $node Node to convert into array
     *
     * @return array            Array corresponding to the given node
     */
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

    /**
     * Convert opml array into more convenient array with xmlUrl as key
     *
     * @param array $array       Array obtained from Opml file
     * @param array $listFolders List of current folders
     *
     * @return array             New formated array
     */
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

    /**
     * Rename folder into items (delete folder is newFolder is empty)
     *
     * @param string $oldFolder Old folder name
     * @param string $newFolder New folder name
     */
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

    /**
     * Return list of folders used to categorize feeds
     *
     * @return array List of folders name
     */
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

    /**
     * Return folder name from a given folder hash
     *
     * @param string $hash Hash corresponding to a folder
     * 
     * @return string|false Folder name if exists, false otherwise
     */
    public function getFolder($hash)
    {
        $folders = $this->getFolders();
        if (isset($folders[$hash])) {
            return $folders[$hash];
        }

        return false;
    }

    /**
     * Return list of feeds
     *
     * @return array List of feeds stored into data
     */
    public function getFeeds()
    {
        return $this->_data;
    }

    /**
     * Return a particular feed from a given hash feed
     *
     * @param string $hash Hash corresponding to a feed
     *
     * @return array|false Feed array if exists, false otherwise
     */
    public function getFeed($hash)
    {
        if (isset($this->_data[$hash])) {
            return $this->_data[$hash];
        }

        return false;
    }

    /**
     * Remove feed from a given hash feed
     *
     * @param string $feedHash Hash corresponding to a feed
     */
    public function removeFeed($feedHash)
    {
        if (isset($this->_data[$feedHash])) {
            unset($this->_data[$feedHash]);
            $this->writeData();
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

        return $this->formatChannel($channel);
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
            'link'        => array('feedburner:origLink', 'link', 'guid', 'id'),
            'time'        => array('pubDate', 'updated', 'lastBuildDate',
                                   'published', 'dc:date', 'date'),
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
     * Load an xml file through HTTP
     *
     * @param string $xmlUrl String corresponding to the XML URL
     *
     * @return DOMDocument DOMDocument corresponding to the XML URL
     */
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

    /**
     * List of feeds with update information and title
     *
     * @return array List of feeds for ajaxlist
     */
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

    /**
     * Calculate automatic update (need improvements)
     * 
     * @param array      $feed Array of a feed information
     * @param true|false $auto Used for old feed with no new items
     *
     * @return integer Number of automatic minute for update
     */
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
     * Get human readable error
     *
     * @param integer $error Number of error occured during a feed update
     *
     * @return string String of the corresponding error
     */
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

    /**
     * Update items from a channel channel
     *
     * @param string $feedHash Hash corresponding to a feed
     *
     * @return true|false True is update success, false otherwise
     */
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

    /**
     * return feeds hash in folder
     *
     * @param string $folderHash Hash corresponding to a folder
     *
     * @return array List of feed hash associated to the given folder
     */
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

    /**
     * Get number of unread items depending on hash
     *
     * @param string $hash Hash may represent an item, a feed, a folder
     *                     or all is ''
     *
     * @return integer Number of unread items depending on hash
     */
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

    /**
     * Mark items read/unread depending on the hash : item, feed, folder or ''
     * force is used for not overwriting keep unread when mark as read.
     *
     * @param string  $hash  Hash may represent an item, a feed, a folder
     * @param integer $read  KEEPUNREAD, UNREAD, READ
     * @param boolean $force Force read setting
     *
     * @return void
     */
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

    /**
     * Get array of items depending on hash and filter
     *
     * @param string $hash   Hash may represent an item, a feed, a folder
     *                       if empty or 'all', return all items
     * @param bool   $filter In order to specify a filter depending on newItems
     *                       in config, if 'new' return all new items.
     *
     * @return array of filtered items depending on hash
     */
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

    /**
     * Compare two items depending on time (reverse order : newest first)
     *
     * @param array $a Array reprensenting the first item to compare
     * @param array $b Array reprensenting the second item to compare
     *
     * @return -1|0|1
     */
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

    /**
     * Compare two items depending on time (oldest first)
     *
     * @param array $a Array reprensenting the first item to compare
     * @param array $b Array reprensenting the second item to compare
     *
     * @return -1|0|1
     */
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

    /**
     * Load data file or create one if not exists
     *
     * @return void
     */
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

    /**
     * Write data file
     *
     * @return void
     */
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
