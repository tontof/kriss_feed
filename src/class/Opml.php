<?php
/**
 * Opml class is used to import and export Feed class data. 
 */
class Opml
{
     /**
     * Import feed from opml file (as exported by google reader,
     * tiny tiny rss, rss lounge... using
     */
    public static function importOpml($kfData)
    {
        $feeds = $kfData['feeds'];
        $folders = $kfData['folders'];

        $filename  = $_FILES['filetoupload']['name'];
        $filesize  = $_FILES['filetoupload']['size'];
        $data      = file_get_contents($_FILES['filetoupload']['tmp_name']);
        $overwrite = isset($_POST['overwrite']);

        $opml = new DOMDocument('1.0', 'UTF-8');

        $importCount=0;
        if ($opml->loadXML($data)) {
            $body = $opml->getElementsByTagName('body');
            $xmlArray = Opml::getArrayFromXml($body->item(0));
            $array = Opml::convertOpmlArray($xmlArray['outline']);

            foreach ($array as $hashUrl => $arrayInfo) {
                $title = '';
                if (isset($arrayInfo['title'])) {
                    $title = $arrayInfo['title'];
                } else if (isset($arrayInfo['text'])) {
                    $title = $arrayInfo['text'];
                }
                $foldersHash = array();
                if (isset($arrayInfo['folders'])) {
                    foreach ($arrayInfo['folders'] as $folder) {
                        $folderTitle = html_entity_decode(
                            $folder,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        $folderHash = MyTool::smallHash($folderTitle);
                        if (!isset($folders[$folderHash])) {
                            $folders[$folderHash] = array('title' => $folderTitle, 'isOpen' => true);
                        }
                        $foldersHash[] = $folderHash;
                    }
                }
                $timeUpdate = 'auto';
                $lastUpdate = 0;
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
                    $oldFeed = array('nbUnread' => 0, 'nbAll' => 0);
                    if (isset($feeds[$hashUrl])) {
                        $oldFeed['nbUnread'] = $feeds[$hashUrl]['nbUnread'];
                        $oldFeed['nbAll'] = $feeds[$hashUrl]['nbAll'];
                    }
                    $currentFeed = array(
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
                        'nbUnread' => $oldFeed['nbUnread'],
                        'nbAll' => $oldFeed['nbAll'],
                        'foldersHash' => $foldersHash,
                        'timeUpdate' => $timeUpdate,
                        'lastUpdate' => $lastUpdate);

                    if ($overwrite || !isset($feeds[$hashUrl])) {
                        $feeds[$hashUrl] = $currentFeed;
                        $importCount++;
                    }
                }
            }

            echo '<script>alert("File '
                . htmlspecialchars($filename) . ' (' . MyTool::humanBytes($filesize)
                . ') was successfully processed: ' . $importCount
                . ' links imported.");document.location=\'?\';</script>';

            $kfData['feeds'] = $feeds;
            $kfData['folders'] = $folders;

            return $kfData;
        } else {
            echo '<script>alert("File ' . htmlspecialchars($filename) . ' ('
                . MyTool::humanBytes($filesize) . ') has an unknown'
                . ' file format. Check encoding, try to remove accents'
                . ' and try again. Nothing was imported.");'
                . 'document.location=\'?\';</script>';
            exit;
        }
    }

    /**
     * Export feeds to an opml file
     */
    public static function exportOpml($feeds, $folders)
    {
        $withoutFolder = array();
        $withFolder = array();

        // get a new representation of data using folders as key
        foreach ($feeds as $hashUrl => $arrayInfo) {
            if (empty($arrayInfo['foldersHash'])) {
                $withoutFolder[] = $hashUrl;
            } else {
                foreach ($arrayInfo['foldersHash'] as $folderHash) {
                    $withFolder[$folderHash][] = $hashUrl;
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
                $feeds[$hashUrl]['title']
            );
            $outline->appendChild($outlineTitle);
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value
                = htmlspecialchars($feeds[$hashUrl]['title']);
            $outline->appendChild($outlineText);
            if (!empty($feeds[$hashUrl]['description'])) {
                $outlineDescription
                    = $opmlData->createAttribute('description');
                $outlineDescription->value
                    = htmlspecialchars($feeds[$hashUrl]['description']);
                $outline->appendChild($outlineDescription);
            }
            $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
            $outlineXmlUrl->value
                = htmlspecialchars($feeds[$hashUrl]['xmlUrl']);
            $outline->appendChild($outlineXmlUrl);
            $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
            $outlineHtmlUrl->value = htmlspecialchars(
                $feeds[$hashUrl]['htmlUrl']
            );
            $outline->appendChild($outlineHtmlUrl);
            $body->appendChild($outline);
        }

        // with folder outline node
        foreach ($withFolder as $folderHash => $arrayHashUrl) {
            $outline = $opmlData->createElement('outline');
            $outlineTitle = $opmlData->createAttribute('title');
            $outlineTitle->value = htmlspecialchars($folders[$folderHash]['title']);
            $outline->appendChild($outlineTitle);
            $outlineText = $opmlData->createAttribute('text');
            $outlineText->value = htmlspecialchars($folders[$folderHash]['title']);
            $outline->appendChild($outlineText);

            foreach ($arrayHashUrl as $hashUrl) {
                $outlineKF = $opmlData->createElement('outline');
                $outlineTitle = $opmlData->createAttribute('title');
                $outlineTitle->value
                    = htmlspecialchars($feeds[$hashUrl]['title']);
                $outlineKF->appendChild($outlineTitle);
                $outlineText = $opmlData->createAttribute('text');
                $outlineText->value
                    = htmlspecialchars($feeds[$hashUrl]['title']);
                $outlineKF->appendChild($outlineText);
                if (!empty($feeds[$hashUrl]['description'])) {
                    $outlineDescription
                        = $opmlData->createAttribute('description');
                    $outlineDescription->value = htmlspecialchars(
                        $feeds[$hashUrl]['description']
                    );
                    $outlineKF->appendChild($outlineDescription);
                }
                $outlineXmlUrl = $opmlData->createAttribute('xmlUrl');
                $outlineXmlUrl->value
                    = htmlspecialchars($feeds[$hashUrl]['xmlUrl']);
                $outlineKF->appendChild($outlineXmlUrl);
                $outlineHtmlUrl = $opmlData->createAttribute('htmlUrl');
                $outlineHtmlUrl->value
                    = htmlspecialchars($feeds[$hashUrl]['htmlUrl']);
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
    public static function getArrayFromXml($node)
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
                            = Opml::getArrayFromXml($childNode);
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
    public static function convertOpmlArray($array, $listFolders = array())
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
                    Opml::convertOpmlArray(
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
}

