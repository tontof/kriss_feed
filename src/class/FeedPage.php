<?php
/**
 * FeedPage
 */
class FeedPage
{
    public static $pb; // PageBuilder

    public static $var = array();

    /**
     * initialize private instance of FeedPage class
     *
     * @param array $var list of useful variables for template
     */
    public static function init($var)
    {
        FeedPage::$var = $var;
    }
}
