<!DOCTYPE html>
<html>
  <head><?php FeedPage::includesTpl(); ?></head>
  <body>
    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span6 offset3">
          <div id="config">
            <?php FeedPage::statusTpl(); ?>
            <?php FeedPage::navTpl(); ?>
            <div id="section">
              <form class="form-horizontal" method="post" action="">
                <input type="hidden" name="token" value="<?php echo Session::getToken(); ?>">
                <input type="hidden" name="returnurl" value="<?php echo $referer; ?>" />
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed main information'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="title"><?php echo Intl::msg('KrISS feed title'); ?></label>
                    <div class="controls">
                      <input type="text" id="title" name="title" value="<?php echo $kfctitle; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('KrISS feed visibility'); ?></label>
                    <div class="controls">
                      <label for="publicReader">
                        <input type="radio" id="publicReader" name="visibility" value="public" <?php echo ($kfcvisibility==='public'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Public KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('No restriction. Anyone can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                      <label for="protectedReader">
                        <input type="radio" id="protectedReader" name="visibility" value="protected" <?php echo ($kfcvisibility==='protected'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Protected KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('Anyone can access feeds and items but only you can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                      <label for="privateReader">
                        <input type="radio" id="privateReader" name="visibility" value="private" <?php echo ($kfcvisibility==='private'? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Private KrISS feed'); ?>
                      </label>
                      <span class="help-block">
                        <?php echo Intl::msg('Only you can access feeds and items and only you can modify configuration, mark as read items, update feeds...'); ?>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="shaarli"><?php echo Intl::msg('Shaarli URL'); ?></label>
                    <div class="controls">
                      <input type="text" id="shaarli" name="shaarli" value="<?php echo $kfcshaarli; ?>">
                      <span class="help-block"><?php echo Intl::msg('Options:'); ?><br>
                        - <?php echo Intl::msg('${url}: item link'); ?><br>
                        - <?php echo Intl::msg('${title}: item title'); ?><br>
                        - <?php echo Intl::msg('${via}: if domain of &lt;link&gt; and &lt;guid&gt; are different ${via} is equals to: <code>via &lt;guid&gt;</code>'); ?><br>
                        - <?php echo Intl::msg('${sel}: <strong>Only available</strong> with javascript: <code>selected text</code>'); ?><br>
                        - <?php echo Intl::msg('example with shaarli:'); ?> <code>http://your-shaarli/?post=${url}&title=${title}&description=${sel}%0A%0A${via}&source=bookmarklet</code>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="redirector"><?php echo Intl::msg('KrISS feed redirector (only for links, media are not considered, <strong>item content is anonymize only with javascript</strong>)'); ?></label>
                    <div class="controls">
                      <input type="text" id="redirector" name="redirector" value="<?php echo $kfcredirector; ?>">
                      <span class="help-block"><?php echo Intl::msg('<strong>http://anonym.to/?</strong> will mask the HTTP_REFERER, you can also use <strong>noreferrer</strong> to use HTML5 property'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="disablesessionprotection">Session protection</label>
                    <div class="controls">
                      <label><input type="checkbox" id="disablesessionprotection" name="disableSessionProtection"<?php echo ($kfcdisablesessionprotection ? ' checked="checked"' : ''); ?>><?php echo Intl::msg('Disable session cookie hijacking protection'); ?></label>
                      <span class="help-block"><?php echo Intl::msg('Check this if you get disconnected often or if your IP address changes often.'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed preferences'); ?></legend>

                  <div class="control-group">
                    <label class="control-label" for="maxItems"><?php echo Intl::msg('Maximum number of items by feed'); ?></label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxItems" name="maxItems" value="<?php echo $kfcmaxitems; ?>">
                      <span class="help-block"><?php echo Intl::msg('Need update to be taken into consideration'); ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="maxUpdate"><?php echo Intl::msg('Maximum delay between feed update (in minutes)'); ?></label>
                    <div class="controls">
                      <input type="text" maxlength="3" id="maxUpdate" name="maxUpdate" value="<?php echo $kfcmaxupdate; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto read next item option'); ?></label>
                    <div class="controls">
                      <label for="donotautoreaditem">
                        <input type="radio" id="donotautoreaditem" name="autoreadItem" value="0" <?php echo (!$kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not mark as read when next item'); ?>
                      </label>
                      <label for="autoread">
                        <input type="radio" id="autoread" name="autoreadItem" value="1" <?php echo ($kfcautoreaditem ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto mark current as read when next item'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto read next page option'); ?></label>
                    <div class="controls">
                      <label for="donotautoreadpage">
                        <input type="radio" id="donotautoreadpage" name="autoreadPage" value="0" <?php echo (!$kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not mark as read when next page'); ?>
                      </label>
                      <label for="autoreadpage">
                        <input type="radio" id="autoreadpage" name="autoreadPage" value="1" <?php echo ($kfcautoreadpage ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto mark current as read when next page'); ?>
                      </label>
                      <span class="help-block"><strong><?php echo Intl::msg('Not implemented yet'); ?></strong></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto hide option'); ?></label>
                    <div class="controls">
                      <label for="donotautohide">
                        <input type="radio" id="donotautohide" name="autohide" value="0" <?php echo (!$kfcautohide ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Always show feed in feeds list'); ?>
                      </label>
                      <label for="autohide">
                        <input type="radio" id="autohide" name="autohide" value="1" <?php echo ($kfcautohide ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically hide feed when 0 unread item'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto focus option'); ?></label>
                    <div class="controls">
                      <label for="donotautofocus">
                        <input type="radio" id="donotautofocus" name="autofocus" value="0" <?php echo (!$kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not automatically jump to current item when it changes'); ?>
                      </label>
                      <label for="autofocus">
                        <input type="radio" id="autofocus" name="autofocus" value="1" <?php echo ($kfcautofocus ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically jump to the current item position'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Add favicon option'); ?></label>
                    <div class="controls">
                      <label for="donotaddfavicon">
                        <input type="radio" id="donotaddfavicon" name="addFavicon" value="0" <?php echo (!$kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not add favicon next to feed on list of feeds/items'); ?>
                      </label>
                      <label for="addfavicon">
                        <input type="radio" id="addfavicon" name="addFavicon" value="1" <?php echo ($kfcaddfavicon ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Add favicon next to feed on list of feeds/items'); ?><br><strong><?php echo Intl::msg('Warning: It depends on http://getfavicon.appspot.com/'); ?> <?php if (in_array('curl', get_loaded_extensions())) { echo Intl::msg('but it will cache favicon on your server'); } ?></strong>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Preload option'); ?></label>
                    <div class="controls">
                      <label for="donotpreload">
                        <input type="radio" id="donotpreload" name="preload" value="0" <?php echo (!$kfcpreload ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not preload items.'); ?>
                      </label>
                      <label for="preload">
                        <input type="radio" id="preload" name="preload" value="1" <?php echo ($kfcpreload ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Preload current page items in background. This greatly enhance speed sensation when opening a new item. Note: It uses your bandwith more than needed if you do not read all the page items.'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label">Auto target="_blank"</label>
                    <div class="controls">
                      <label for="donotblank">
                        <input type="radio" id="donotblank" name="blank" value="0" <?php echo (!$kfcblank ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not open link in new tab'); ?>
                      </label>
                      <label for="doblank">
                        <input type="radio" id="doblank" name="blank" value="1" <?php echo ($kfcblank ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Automatically open link in new tab'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label"><?php echo Intl::msg('Auto update with javascript'); ?></label>
                    <div class="controls">
                      <label for="donotautoupdate">
                        <input type="radio" id="donotautoupdate" name="autoUpdate" value="0" <?php echo (!$kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Do not auto update with javascript'); ?>
                      </label>
                      <label for="autoupdate">
                        <input type="radio" id="autoupdate" name="autoUpdate" value="1" <?php echo ($kfcautoupdate ? 'checked="checked"' : ''); ?>/>
                        <?php echo Intl::msg('Auto update with javascript'); ?>
                      </label>
                    </div>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed menu preferences'); ?></legend>
                  <?php echo Intl::msg('You can order or remove elements in the menu. Set a position or leave empty if you do not want the element to appear in the menu.'); ?>
                  <div class="control-group">
                    <label class="control-label" for="menuView"><?php echo Intl::msg('View'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuView" name="menuView" value="<?php echo empty($kfcmenu['menuView'])?'0':$kfcmenu['menuView']; ?>">
                      <span class="help-block"><?php echo Intl::msg('View as list'); ?>/<?php echo Intl::msg('View as expanded'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuListFeeds"><?php echo Intl::msg('Feeds'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuListFeeds" name="menuListFeeds" value="<?php echo empty($kfcmenu['menuListFeeds'])?'0':$kfcmenu['menuListFeeds']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Hide feeds list'); ?>/<?php echo Intl::msg('Show feeds list'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuFilter"><?php echo Intl::msg('Filter'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuFilter" name="menuFilter" value="<?php echo empty($kfcmenu['menuFilter'])?'0':$kfcmenu['menuFilter']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Show all items'); ?>/<?php echo Intl::msg('Show unread items'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuOrder"><?php echo Intl::msg('Order'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuOrder" name="menuOrder" value="<?php echo empty($kfcmenu['menuOrder'])?'0':$kfcmenu['menuOrder']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Show older first'); ?>/<?php echo Intl::msg('Show newer first'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUpdate"><?php echo Intl::msg('Update'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuUpdate" name="menuUpdate" value="<?php echo empty($kfcmenu['menuUpdate'])?'0':$kfcmenu['menuUpdate']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Update all'); ?>/<?php echo Intl::msg('Update folder'); ?>/<?php echo Intl::msg('Update feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuRead"><?php echo Intl::msg('Mark as read'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuRead" name="menuRead" value="<?php echo empty($kfcmenu['menuRead'])?'0':$kfcmenu['menuRead']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Mark all as read'); ?>/<?php echo Intl::msg('Mark folder as read'); ?>/<?php echo Intl::msg('Mark feed as read'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuUnread"><?php echo Intl::msg('Mark as unread'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuUnread" name="menuUnread" value="<?php echo empty($kfcmenu['menuUnread'])?'0':$kfcmenu['menuUnread']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Mark all as unread'); ?>/<?php echo Intl::msg('Mark folder as unread'); ?>/<?php echo Intl::msg('Mark feed as unread'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuEdit"><?php echo Intl::msg('Edit'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuEdit" name="menuEdit" value="<?php echo empty($kfcmenu['menuEdit'])?'0':$kfcmenu['menuEdit']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Edit all'); ?>/<?php echo Intl::msg('Edit folder'); ?>/<?php echo Intl::msg('Edit feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuAdd"><?php echo Intl::msg('Add a new feed'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuAdd" name="menuAdd" value="<?php echo empty($kfcmenu['menuAdd'])?'0':$kfcmenu['menuAdd']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Add a new feed'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuHelp"><?php echo Intl::msg('Help'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuHelp" name="menuHelp" value="<?php echo empty($kfcmenu['menuHelp'])?'0':$kfcmenu['menuHelp']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Help'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="menuStars"><?php echo Intl::msg('Starred items'); ?></label>
                    <div class="controls">
                      <input type="text" id="menuStars" name="menuStars" value="<?php echo empty($kfcmenu['menuStars'])?'0':$kfcmenu['menuStars']; ?>">
                      <span class="help-block"><?php echo Intl::msg('Starred items'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('KrISS feed paging menu preferences'); ?></legend>
                  <div class="control-group">
                    <label class="control-label" for="pagingItem"><?php echo Intl::msg('Item'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingItem" name="pagingItem" value="<?php echo empty($kfcpaging['pagingItem'])?'0':$kfcpaging['pagingItem']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to go previous and next item'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingPage"><?php echo Intl::msg('Page'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingPage" name="pagingPage" value="<?php echo empty($kfcpaging['pagingPage'])?'0':$kfcpaging['pagingPage']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to go previous and next page'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingByPage"><?php echo Intl::msg('Items by page'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingByPage" name="pagingByPage" value="<?php echo empty($kfcpaging['pagingByPage'])?'0':$kfcpaging['pagingByPage']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to modify number of items by page'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="pagingMarkAs"><?php echo Intl::msg('Mark as read'); ?></label>
                    <div class="controls">
                      <input type="text" id="pagingMarkAs" name="pagingMarkAs" value="<?php echo empty($kfcpaging['pagingMarkAs'])?'0':$kfcpaging['pagingMarkAs']; ?>">
                      <span class="help-block"><?php echo Intl::msg('If you want to add a mark as read button into paging'); ?></span>
                    </div>
                  </div>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
                    </div>
                  </div>
                </fieldset>
                <fieldset>
                  <legend><?php echo Intl::msg('Cron configuration'); ?></legend>
                  <code><?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?></code>
                  <?php echo Intl::msg('You can use <code>&force</code> to force update.'); ?><br>
                  <?php echo Intl::msg('To update every hour:'); ?><br>
                  <code>0 * * * * wget "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>" -O /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('If you can not use wget, you may try php command line:'); ?><br>
                  <code>0 * * * * php -f <?php echo $_SERVER["SCRIPT_FILENAME"].' update '.$kfccron; ?> > /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('If previous solutions do not work, try to create an update.php file into data directory containing:'); ?><br>
                  <code>
                  &lt;?php<br>
                  $url = "<?php echo MyTool::getUrl().'?update&cron='.$kfccron; ?>";<br>
                  $options = array('http'=>array('method'=>'GET'));<br>
                  $context = stream_context_create($options);<br>
                  $data=file_get_contents($url,false,$context);<br>
                  print($data);
                  </code><br>
                  <?php echo Intl::msg('Then set up your cron with:'); ?><br>
                  <code>0 * * * * php -f <?php echo dirname($_SERVER["SCRIPT_FILENAME"]).'/data/update.php'; ?> > /tmp/kf.cron</code><br>
                  <?php echo Intl::msg('Do not forget to check right permissions!'); ?><br>
                  <div class="control-group">
                    <div class="controls">
                      <input class="btn" type="submit" name="cancel" value="<?php echo Intl::msg('Cancel'); ?>"/>
                      <input class="btn" type="submit" name="save" value="<?php echo Intl::msg('Save modifications'); ?>" />
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
