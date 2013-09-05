<?php
/**
 * Rss class
 *
 * Features:
 * - Only depends on libxml2 library (no SimpleXML)
 * - Xml validity is performed by libxml2
 * - Parse rdf:RDF, rss, atom feeds
 * - Let you customize what information you want with simple syntax
 * - Created for rss/atom feed but useful for common xml format
 * 
 * How to use:
 * - http://tontof.net/kriss/php5/rss
 */
class Rss
{
    const UNKNOWN = 0;
    const RSS = 1;
    const ATOM = 2;

    public static $feedFormat = array(
       'title' => array('>title'),
       'description' => array('>description', '>subtitle'),
       'htmlUrl' => array('>link', '>link[rel=self][href]', '>link[href]', '>id')
    );

    public static $itemFormat = array(
        'author' => array('>author>name', '>author', '>dc:creator', 'feed>author>name', '>dc:author', '>creator'),
        'content' => array('>content:encoded', '>content', '>description', '>summary', '>subtitle'),
        'description' => array('>description', '>summary', '>subtitle', '>content', '>content:encoded'),
        'via' => array('>guid', '>id'),
        'link' => array('>feedburner:origLink', '>link[rel=alternate][href]', '>link[href]', '>link', '>guid', '>id'),
        'time' => array('>pubDate', '>updated', '>lastBuildDate', '>published', '>dc:date', '>date', '>created', '>modified'),
        'title' => array('>title')
    );

    /**
     * Check for a list of attributes if current node is valid
     *
     * @param DOMNode $node  to check if valid
     * @param array   $attrs to test if in $node
     *
     * @return boolean true if $node is valid for $attrs, false otherwise
     */
    public static function isValidNodeAttrs($node, $attrs)
    {
        foreach ($attrs as $attr) {
            if (strpos($attr, '=') !== false) {
                list($attr, $val) = explode('=', $attr);
            }
            if (!$node->hasAttribute($attr)
                || (!empty($val) && $node->getAttribute($attr) !== $val)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if tagName from $nodes are correct depending on $name
     *
     * @param DOMNodeList $nodes to check
     * @param string      $name  to compare with tagName
     *
     * @return array of nodes with correct $name
     */
    public static function filterNodeListByName($nodes, $name)
    {
        $res = array();

        for ($i = 0; $i < $nodes->length; $i++) {
            if ($nodes->item($i)->tagName === $name) {
                $res[] = $nodes->item($i);
            }
        }

        return $res;
    }

    /**
     * Return array of descendant DOMNode of $node with tagName equals to $name
     *
     * @param DOMNode $node to starts with
     * @param string  $name of descendant
     *
     * @return array of descendant DOMNode with tagName equals to $name
     */
    public static function getNodesName($node, $name)
    {
        if (strpos($name, ':') !== false) {
            list(, $localname) = explode(':', $name);
            $nodes = $node->getElementsByTagNameNS('*', $localname);
        } else {
            $nodes = $node->getElementsByTagName($name);
        }

        return self::filterNodeListByName($nodes, $name);
    }

    /**
     * Return content of $node depending on defined $selectors
     *
     * @param DOMNode $node      to starts with
     * @param array   $selectors defined using node>node[attr=val][attr]
     *
     * @return string of the desired selection or empty string if not found
     */
    public static function getElement($node, $selectors)
    {
        $res = '';
        $selector = array_shift($selectors);
        $attributes = explode('[', trim($selector, ']'));
        $name = array_shift($attributes);
        if (substr($name, -1) == "*") {
            $name = substr($name, 0, -1);
            $res = array();
        }

        $nodes = self::getNodesName($node, $name);
        foreach ($nodes as $currentNode) {
            if ($currentNode->parentNode->isSameNode($node)
                && self::isValidNodeAttrs($currentNode, $attributes)) {
                if (empty($selectors)) {
                    $attr = end($attributes);
                    if (empty($attr) || strpos($attr, '=') !== false) {
                        if (is_array($res)) {
                            $res[] = $currentNode->textContent;
                        } else {
                            $res = $currentNode->textContent;
                        }
                    } else {
                        if (is_array($res)) {
                            $res[] = $currentNode->getAttribute($attr);
                        } else {
                            $res = $currentNode->getAttribute($attr);
                        }
                    }
                } else {
                    return self::getElement($currentNode, $selectors);
                }
            }
            if (!is_array($res) && !empty($res)) {
                break;
            }
        }

        return $res;
    }

    /**
     * Format $element depending on $formats
     *
     * @param DOMDocument $dom     of document
     * @param DOMNode     $element to starts with
     * @param array       $formats to use to extract information
     *
     * @return array of extracted information
     */
    public static function formatElement($dom, $element, $formats)
    {
        $newElement = array();
        foreach ($formats as $format => $list) {
            $newElement[$format] = '';
            for ($i = 0, $len = count($list);
                 $i < $len && empty($newElement[$format]);
                 $i++) {
                $selectors = explode('>', $list[$i]);
                $selector = array_shift($selectors);
                if (empty($selector)) {
                    $newElement[$format] = self::getElement($element, $selectors);
                } else if (strpos($selector, '[') === 0) {
                    $attributes = explode('[', trim($selector, ']'));
                    if (self::isValidNodeAttrs($element, $attributes)) {
                        $newElement[$format] = self::getElement($element, $selectors);
                    }
                } else {
                    $attributes = explode('[', trim($selector, ']'));
                    $name = array_shift($attributes);
                    $nodes = self::getNodesName($dom, $name);
                    foreach ($nodes as $node) {
                        if (self::isValidNodeAttrs($node, $attributes)) {
                            $newElement[$format] = self::getElement($node, $selectors);
                        }
                        if (!empty($newElement[$format])) {
                            break;
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

        return self::formatElement($dom, $feed, self::$feedFormat);
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
        $max = ($nb === -1 ? $items->length : min($nb, $items->length));
        for ($i = 0; $i < $max; $i++) {
            $newItems[] = self::formatElement($dom, $items->item($i), self::$itemFormat);
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
    public static function getType($dom)
    {
        $type = self::UNKNOWN;

        $feed = $dom->getElementsByTagName('channel');
        if ($feed->item(0)) {
            $type = self::RSS;
        } else {
            $feed = $dom->getElementsByTagName('feed');
            if ($feed->item(0)) {
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
        libxml_clear_errors();
        set_error_handler(array('Rss', 'silenceErrors'));
        $dom = new DOMDocument();
        $dom->loadXML($data);
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
     *
     * @param integer $num of errno
     * @param string  $str of errstr
     */
    public static function silenceErrors($num, $str)
    {
        // No-op                                                       
    }
}
