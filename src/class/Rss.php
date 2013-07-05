<?php
/**
 * Rss class let you download and parse RSS
 */
class Rss
{
    /**
     * Format xml channel into array
     *
     * @param DOMDocument $channel DOMDocument of the channel feed
     *
     * @return array Array with extracted information channel
     */
    public static function formatChannel($channel)
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
     * format items into array
     *
     * @param DOMNodeList $items   DOMNodeList of items in a feed
     * @param array       $formats List of information to extract
     *
     * @return array List of items with information
     */
    public static function formatItems($items, $formats)
    {
        $newItems = array();

        for ($k = 0; $k < $items->length; $k++) {
            $item = $items->item($k);
            $tmpItem = array();
            foreach ($formats as $format => $list) {
                $tmpItem[$format] = '';
                $len = count($list);
                for ($i = 0; $i < $len; $i++) {
                    $name = explode(':', $list[$i]);
                    if (count($name) > 1) {
                        $tag = $item->getElementsByTagNameNS('*', $name[1]);
                    } else {
                        $tag = $item->getElementsByTagName($list[$i]);
                    }
                    for ($j = $tag->length; --$j >= 0;) {
                        $elt = $tag->item($j);
                        if ($tag->item($j)->tagName != $list[$i]) {
                            $elt->parentNode->removeChild($elt);
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
     * return channel from xmlUrl
     *
     * @param DOMDocument $xml DOMDocument of the feed
     *
     * @return array Array with extracted information channel
     */
    public static function getChannelFromXml($xml)
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
            $channel = self::formatChannel($channel);
        }

        return $channel;
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
    public static function getAttributeNS ($feed, $name)
    {
        $res = '';
        if ($feed->nodeName === $name) {
            $ns = explode(':', $name);
            $res = $feed->getAttribute('xmlns:'.$ns[0]);
        } else {
            if ($feed->hasChildNodes()) {
                foreach ($feed->childNodes as $childNode) {
                    if ($res === '') {
                        $res = self::getAttributeNS($childNode, $name);
                    } else {
                        break;
                    }
                }
            }
        }

        return $res;
    }

    /**
     * Return array of items from xml
     *
     * @param DOMDocument $xml DOMDocument where to extract items
     *
     * @return array Array of items extracted from the DOMDocument
     */
    public static function getItemsFromXml ($xml)
    {
        $items = new DOMNodelist;

        // find feed type RSS, Atom
        $feed = $xml->getElementsByTagName('channel');
        if ($feed->item(0)) { // RSS/rdf:RDF feed
            $items = $xml->getElementsByTagName('item');
        } else {
            $feed = $xml->getElementsByTagName('feed');
            if ($feed->item(0)) { // Atom feed
                $items = $xml->getElementsByTagName('entry');
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

        return self::formatItems($items, $formats);
    }

    public static function loadDom($data)
    {
        $error = '';
        set_error_handler(array('MyTool', 'silenceErrors'));
        $dom = new DOMDocument();
        $isValid = $dom->loadXML($data);
        restore_error_handler();
        
        if (!$isValid) {
            $error = self::getError(libxml_get_last_error());
        }

        return array(
            'dom' => $dom,
            'error' => $error
        );
    }

    public static function getError($error)
    {
        $return = '';
        
        if ($error === false) {
            $return = Intl::msg('Unknown XML error');
        } else {
            switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning XML $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error XML $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error XML $error->code: ";
                break;
            }
            $return .= trim($error->message);
        }

        return $return;
    }
}
