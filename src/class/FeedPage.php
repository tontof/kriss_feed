<?php
/**
 * FeedPage
 */
class FeedPage
{
    public static $var = array();
    private static $_instance;

    /**
     * initialize private instance of FeedPage class
     *
     * @param array $var list of useful variables for template
     */
    public static function init($var)
    {
        FeedPage::$var = $var;
    }

    /**
     * includesTpl
     * 
     */
    public static function includesTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/includes.tpl.php"); ?>
<?php
    }

    /**
     * installTpl
     * 
     */
    public static function installTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/install.tpl.php"); ?>
<?php
    }

    /**
     * loginTpl
     * 
     */
    public static function loginTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/login.tpl.php"); ?>
<?php
    }

    /**
     * navTpl
     * 
     */
    public static function navTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/nav.tpl.php"); ?>
<?php
    }

    /**
     * statusTpl
     * 
     */
    public static function statusTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/status.tpl.php"); ?>
<?php
    }

    /**
     * configTpl
     * 
     */
    public static function configTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/config.tpl.php"); ?>
<?php
    }

    /**
     * configTpl
     * 
     */
    public static function helpTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/help.tpl.php"); ?>
<?php
    }

    /**
     * addTpl : Add a new feed
     * 
     */
    public static function addFeedTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/add_feed.tpl.php"); ?>
<?php
    }

    /**
     * editAllTpl : Edit all feed page
     * 
     */
    public static function editAllTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/edit_all.tpl.php"); ?>
<?php
    }

    /**
     * editFolderTpl : Edit folder page
     * 
     */
    public static function editFolderTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/edit_folder.tpl.php"); ?>
<?php
    }

    /**
     * editFeedTpl : Edit feed page
     * 
     */
    public static function editFeedTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/edit_feed.tpl.php"); ?>
<?php
    }

    /**
     * updateTpl : update page
     * 
     */
    public static function updateTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/update.tpl.php"); ?>
<?php
    }

    /**
     * importTpl : import page
     * 
     */
    public static function importTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/import.tpl.php"); ?>
<?php
    }

    /**
     * listFeedsTpl : list feeds ul
     * 
     */
    public static function listFeedsTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/list_feeds.tpl.php"); ?>
<?php
    }

    /**
     * listFeedsTpl : list feeds ul
     * 
     */
    public static function listItemsTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/list_items.tpl.php"); ?>
<?php
    }

    /**
     * pagingTpl : pagination div
     * 
     */
    public static function pagingTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/paging.tpl.php"); ?>
<?php
    }

    /**
     * indexTpl : index page
     * 
     */
    public static function indexTpl()
    {
        extract(FeedPage::$var);
?>
<?php include("tpl/index.tpl.php"); ?>
<?php
    }
}
