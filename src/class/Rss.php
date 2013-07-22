<?php
/**
 * Rss class let you download and parse RSS
 */
class Rss
{
    static $feedFormat = array(
        'title' => array('title'),
        'description' => array('description', 'subtitle'),
        'htmlUrl' => array('link', 'id', 'guid')
    );

    static $itemFormat = array(
        'author' => array('author', 'creator', 'dc:author', 'dc:creator'),
        'content' => array('content:encoded', 'content', 'description', 'summary', 'subtitle'),
        'description' => array('description', 'summary', 'subtitle', 'content', 'content:encoded'),
        'via' => array('guid', 'id'),
        'link' => array('feedburner:origLink', array('link', 'href', array('rel=alternate')), array('link', 'href'), 'link', 'guid', 'id'),
        'time' => array('pubDate', 'updated', 'lastBuildDate', 'published', 'dc:date', 'date', 'created', 'modified'),
        'title' => array('title')
    );

    /**
     *
     */
    public static function formatElement($element, $formats)
    {
        $newElement = array();
        foreach ($formats as $format => $list) {
            $newElement[$format] = '';
            $len = count($list);
            for ($i = 0; $i < $len && empty($newElement[$format]); $i++) {
                $selector = $list[$i];
                if (is_array($list[$i])) {
                    $selector = $list[$i][0];
                    if (count($list[$i]) === 2) {
                        $list[$i][] = array();
                    }
                } else {
                    $list[$i] = array($list[$i], '', array());
                }

                $name = explode(':', $selector);

                if (count($name) > 1) {
                    $elements = $element->getElementsByTagNameNS('*', $name[1]);
                } else {
                    $elements = $element->getElementsByTagName($name[0]);
                }

                for ($j = 0; $j < $elements->length && empty($newElement[$format]); $j++) {
                    $elt = $elements->item($j);
                    $isCorrect = true;
                    if ($elements->item($j)->tagName != $selector) {
                        $isCorrect = false;
                    } else {
                        foreach($list[$i][2] as $attr) {
                            $attrs = explode('=', $attr);
                            if (count($attrs) !== 2 || 
                                !$elements->item($j)->hasAttribute($attrs[0]) ||
                                $elements->item($j)->getAttribute($attrs[0]) !== $attrs[1]) {
                                $isCorrect = false;
                            }
                        }
                    }

                    if (!$elt->parentNode->isSameNode($element)) {
                        $isCorrect = false;
                    }

                    if ($isCorrect) {
                        if (empty($list[$i][1])) {
                            $newElement[$format] = $elt->textContent;
                        } else {
                            if ($elements->item($j)->hasAttribute($list[$i][1])) {
                                $newElement[$format] = $elt->getAttribute($list[$i][1]);
                            }
                        }
                    }
                }
            }
        }

        return $newElement;
    }

    /**
     * return feed from dom
     *
     * @param DOMDocument $dom DOMDocument of the feed
     *
     * @return array Array with extracted information channel
     */
    public static function getFeed($dom)
    {
        $feed = new DOMNodelist;

        // find feed type RSS, Atom
        $feed = $dom->getElementsByTagName('channel');
        if ($feed->item(0)) { // RSS/rdf:RDF feed
            $feed = $feed->item(0);
        } else {
            $feed = $dom->getElementsByTagName('feed');
            if ($feed->item(0)) { // Atom feed
                $feed = $feed->item(0);
            }
        }

        return self::formatElement($feed, self::$feedFormat);
    }

    /**
     * Return array of items from dom
     *
     * @param DOMDocument $dom DOMDocument where to extract items
     *
     * @return array Array of items extracted from the DOMDocument
     */
    public static function getItems($dom)
    {
        $items = new DOMNodelist;

        // find feed type RSS, Atom
        $feed = $dom->getElementsByTagName('channel');
        if ($feed->item(0)) { // RSS/rdf:RDF feed
            $items = $dom->getElementsByTagName('item');
        } else {
            $feed = $dom->getElementsByTagName('feed');
            if ($feed->item(0)) { // Atom feed
                $items = $dom->getElementsByTagName('entry');
            }
        }

        $newItems = array();
        for ($i = 0; $i < $items->length; $i++) {
            $item = $items->item($i);
            $item = self::formatElement($item, self::$itemFormat);
            if (!empty($item)) {
                $newItems[] = $item;
            }
        }

        return $newItems;
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
