<?php
/**
 * Rss class let you download and parse RSS
 */
class Rss
{
    const UNKNOWN = 0;
    const RSS = 1;
    const ATOM = 2;

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
     * Return array of feed from a DOMDocument
     *
     * @param DOMDocument $dom
     *
     * @return array of feed info extracted from $dom
     */
    public static function getFeed($dom)
    {
        $feed = new DOMNodelist;

        $type = self::getType($dom);
        if ($type === self::RSS) {
            $feed = $dom->getElementsByTagName('channel')->item(0);
        } elseif ($type === self::ATOM) {
            $feed = $dom->getElementsByTagName('feed')->item(0);
        }

        return self::formatElement($feed, self::$feedFormat);
    }

    /**
     * Return array of items from a DOMDocument
     *
     * @param DOMDocument $dom
     * @param integer     $nb of items to select
     *
     * @return array of items extracted from the $dom
     */
    public static function getItems($dom, $nb = -1)
    {
        $items = new DOMNodelist;

        $type = self::getType($dom);
        if ($type === self::RSS) {
            $items = $dom->getElementsByTagName('item');
        } elseif ($type === self::ATOM) {
            $items = $dom->getElementsByTagName('entry');
        }
        
        $newItems = array();
        $max = $nb === -1 ? $items->length : max($nb, $item->length);
        for ($i = 0; $i < $max; $i++) {
            $item = self::formatElement($items->item($i), self::$itemFormat);
            if (!empty($item)) {
                $newItems[] = $item;
            }
        }

        return $newItems;
    }

    /**
     * Return type of a DOMDocument
     *
     * @param DOMDocument $dom
     *
     * @return const corresponding to the type of $dom
     */
    public static function getType($dom) {
        $type = self::UNKNOWN;

        $feed = $dom->getElementsByTagName('channel');
        if ($feed->item(0)) { // RSS/rdf:RDF feed
            $type = self::RSS;
        } else {
            $feed = $dom->getElementsByTagName('feed');
            if ($feed->item(0)) { // Atom feed
                $type = self::ATOM;
            }
        }

        return $type;
    }

    /**
     * Load a XML string into DOMDocument
     *
     * @param string $data
     *
     * @return array with a DOMDocument and a string error
     */
    public static function loadDom($data)
    {
        $error = '';
        set_error_handler(array('Rss', 'silenceErrors'));
        libxml_clear_errors();
        $dom = new DOMDocument();
        $isValid = $dom->loadXML($data);
        restore_error_handler();

        return array(
            'dom' => $dom,
            'error' => self::getError(libxml_get_last_error())
        );
    }

    /**
     * Explicit libxml2 error
     *
     * @param LibXMLError $error
     *
     * @return string of the error
     */
    public static function getError($error)
    {
        $return = '';

        if ($error !== false) {
            switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return = "Warning XML $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return = "Error XML $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return = "Fatal Error XML $error->code: ";
                break;
            }
            $return .= $return.trim($error->message);
        }

        return $return;
    }

    /**
     * From Simplie Pie
     */
    public static function silenceErrors($num, $str)
    {
        // No-op                                                       
    }
}
