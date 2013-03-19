<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend>KrISS feed Reader information</legend>

                  <div class="control-group">
                    <label class="control-label" for="title">Feed reader title</label>
                    <div class="controls">
                      <input type="text" id="title" name="title" value="<?php echo $kfctitle; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Public/private reader</label>
                    <div class="controls">
                      <label for="publicReader">
                        <input type="radio" id="publicReader" name="public" value="1" <?php echo ($kfcpublic? 'checked="checked"' : ''); ?>/>
                        Public kriss feed
                      </label>
                      <label for="privateReader">
                        <input type="radio" id="privateReader" name="public" value="0" <?php echo (!$kfcpublic? 'checked="checked"' : ''); ?>/>
                        Private kriss feed
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="shaarli">Shaarli url</label>
                    <div class="controls">
                      <input type="text" id="shaarli" name="shaarli" value="<?php echo $kfcshaarli; ?>">
                      <span class="help-block">options :<br>
                        - ${url}: link of item<br>
                        - ${title}: title of item<br>
                        - ${via}: if domain of &lt;link&gt; and &lt;guid&gt; are different ${via} is equals to: <code>via &lt;guid&gt;</code><br>
                        - ${sel}: <strong>Only available</strong> with javascript: <code>« selected text »</code><br>
                        - example with shaarli : <code>http://your-shaarli/?post=${url}&title=${title}&description=${sel}%0A%0A${via}&source=bookmarklet</code>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="redirector">Feed reader redirector (only for links, media are not considered, <strong>item content is anonymize only with javascript</strong>)</label>
                    <div class="controls">
                      <input type="text" id="redirector" name="redirector" value="<?php echo $kfcredirector; ?>">
                      <span class="help-block">(e.g. http://anonym.to/? will mask the HTTP_REFERER)</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed reader preferences</legend>

                  <div class="control-group">
                    <label class="control-label" for="maxItems">Maximum number of items by feed</label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxItems" name="maxItems" value="<?php echo $kfcmaxitems; ?>">
                      <span class="help-block">Need update to be taken into consideration</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="maxUpdate">Maximum delay between feed update (in minutes)</label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxUpdate" name="maxUpdate" value="<?php echo $kfcmaxupdate; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto read next item option</label>
                    <div class="controls">
                      <label for="donotautoreaditem">
                        <input type="radio" id="donotautoreaditem" name="autoreadItem" value="0" <?php echo (!$kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        Do not mark as read when next item
                      </label>
                      <label for="autoread">
                        <input type="radio" id="autoread" name="autoreadItem" value="1" <?php echo ($kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        Auto mark current as read when next item
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto read next page option</label>
                    <div class="controls">
                      <label for="donotautoreadpage">
                        <input type="radio" id="donotautoreadpage" name="autoreadPage" value="0" <?php echo (!$kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        Do not mark as read when next page
                      </label>
                      <label for="autoreadpage">
                        <input type="radio" id="autoreadpage" name="autoreadPage" value="1" <?php echo ($kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        Auto mark current as read when next page
                      </label>
                      <span class="help-block"><strong>Not implemented yet</strong></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto hide option</label>
                    <div class="controls">
                      <label for="donotautohide">
                        <input type="radio" id="donotautohide" name="autohide" value="0" <?php echo (!$kfcautohide ? 'checked="checked"' : ''); ?>/>
                        Always show feed in feeds list
                      </label>
                      <label for="autohide">
                        <input type="radio" id="autohide" name="autohide" value="1" <?php echo ($kfcautohide ? 'checked="checked"' : ''); ?>/>
                        Automatically hide feed when 0 unread item
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto focus option</label>
                    <div class="controls">
                      <label for="donotautofocus">
                        <input type="radio" id="donotautofocus" name="autofocus" value="0" <?php echo (!$kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        Do not automatically jump to current item when it changes
                      </label>
                      <label for="autofocus">
                        <input type="radio" id="autofocus" name="autofocus" value="1" <?php echo ($kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        Automatically jump to the current item position
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Add favicon option</label>
                    <div class="controls">
                      <label for="donotaddfavicon">
                        <input type="radio" id="donotaddfavicon" name="addFavicon" value="0" <?php echo (!$kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        Do not add favicon next to feed on list of feeds
                      </label>
                      <label for="addfavicon">
                        <input type="radio" id="addfavicon" name="addFavicon" value="1" <?php echo ($kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        Add favicon next to feed on list of feeds<br><strong>Warning: It depends on http://getfavicon.appspot.com/ <?php if (in_array('curl', get_loaded_extensions())) { echo 'but it will cache favicon on your server'; } ?></strong>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto update with javascript</label>
                    <div class="controls">
                      <label for="donotautoupdate">
                        <input type="radio" id="donotautoupdate" name="autoUpdate" value="0" <?php echo (!$kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        Do not auto update with javascript
                      </label>
                      <label for="autoupdate">
                        <input type="radio" id="autoupdate" name="autoUpdate" value="1" <?php echo ($kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        Auto update with javascript
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed menu preferences</legend>
                  You can order or remove elements in the menu. Set a position or leave empty if you don't want the element to appear in the menu.
                  <div class="control-group">
                    <label class="control-label" for="menuView">View</label>
                    <div class="controls">
                      <input type="text" id="menuView" name="menuView" value="<?php echo empty($kfcmenu['menuView'])?'0':$kfcmenu['menuView']; ?>">
                      <span class="help-block">If you want to switch between list and expanded view</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuListFeeds">List of feeds</label>
                    <div class="controls">
                      <input type="text" id="menuListFeeds" name="menuListFeeds" value="<?php echo empty($kfcmenu['menuListFeeds'])?'0':$kfcmenu['menuListFeeds']; ?>">
                      <span class="help-block">If you want to show or hide list of feeds</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuFilter">Filter</label>
                    <div class="controls">
                      <input type="text" id="menuFilter" name="menuFilter" value="<?php echo empty($kfcmenu['menuFilter'])?'0':$kfcmenu['menuFilter']; ?>">
                      <span class="help-block">If you want to filter all or unread items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuOrder">Order</label>
                    <div class="controls">
                      <input type="text" id="menuOrder" name="menuOrder" value="<?php echo empty($kfcmenu['menuOrder'])?'0':$kfcmenu['menuOrder']; ?>">
                      <span class="help-block">If you want to order by newer or older items</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUpdate">Update</label>
                    <div class="controls">
                      <input type="text" id="menuUpdate" name="menuUpdate" value="<?php echo empty($kfcmenu['menuUpdate'])?'0':$kfcmenu['menuUpdate']; ?>">
                      <span class="help-block">If you want to update all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuRead">Read</label>
                    <div class="controls">
                      <input type="text" id="menuRead" name="menuRead" value="<?php echo empty($kfcmenu['menuRead'])?'0':$kfcmenu['menuRead']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as read</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUnread">Unread</label>
                    <div class="controls">
                      <input type="text" id="menuUnread" name="menuUnread" value="<?php echo empty($kfcmenu['menuUnread'])?'0':$kfcmenu['menuUnread']; ?>">
                      <span class="help-block">If you want to mark all, folder or a feed as unread</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuEdit">Edit</label>
                    <div class="controls">
                      <input type="text" id="menuEdit" name="menuEdit" value="<?php echo empty($kfcmenu['menuEdit'])?'0':$kfcmenu['menuEdit']; ?>">
                      <span class="help-block">If you want to edit all, folder or a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuAdd">Add</label>
                    <div class="controls">
                      <input type="text" id="menuAdd" name="menuAdd" value="<?php echo empty($kfcmenu['menuAdd'])?'0':$kfcmenu['menuAdd']; ?>">
                      <span class="help-block">If you want to add a feed</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuHelp">Help</label>
                    <div class="controls">
                      <input type="text" id="menuHelp" name="menuHelp" value="<?php echo empty($kfcmenu['menuHelp'])?'0':$kfcmenu['menuHelp']; ?>">
                      <span class="help-block">If you want to add a link to the help</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>KrISS feed paging menu preferences</legend>
                  <div class="control-group">
                    <label class="control-label" for="pagingItem">Item</label>
                    <div class="controls">
                      <input type="text" id="pagingItem" name="pagingItem" value="<?php echo empty($kfcpaging['pagingItem'])?'0':$kfcpaging['pagingItem']; ?>">
                      <span class="help-block">If you want to go previous and next item </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingPage">Page</label>
                    <div class="controls">
                      <input type="text" id="pagingPage" name="pagingPage" value="<?php echo empty($kfcpaging['pagingPage'])?'0':$kfcpaging['pagingPage']; ?>">
                      <span class="help-block">If you want to go previous and next page </span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingByPage">Items by page</label>
                    <div class="controls">
                      <input type="text" id="pagingByPage" name="pagingByPage" value="<?php echo empty($kfcpaging['pagingByPage'])?'0':$kfcpaging['pagingByPage']; ?>">
                      <span class="help-block">If you want to modify number of items by page</span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend>Cron configuration</legend>
                  <code><?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?></code>
                  You can use <code>&force</code> to force update.<br>
                  To update every 15 minutes
                  <code>*/15 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  To update every hour
                  <code>0 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  If you can not use wget, you may try php command line :
                  <code>0 * * * * php -f <?php echo $_SERVER["SCRIPT_FILENAME"].' update '.$kfccron; ?> > /tmp/kf.cron</code>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="Cancel"/>
                      <input class="btn" type="submit" name="save" value="Save" />
                    </div>
                  </div>
                </fieldset>
              </form><br>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
