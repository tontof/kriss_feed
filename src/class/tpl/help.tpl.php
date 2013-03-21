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
              <h2>Keyboard shortcut</h2>
              <h3>Items navigation</h3>
              <dl class="dl-horizontal">
                <dt>'space' or 't'</dt>
                <dd>When viewing items as list, let you open or close current item ('t'oggle current item)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'m'</dt>
                <dd>'M'ark current item as read if unread or unread if read</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'n' or right arrow</dt>
                <dd>Go to 'n'ext item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'p' or left arrow</dt>
                <dd>Go to 'p'revious item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'n'</dt>
                <dd>Go to 'n'ext page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'p'</dt>
                <dd>Go to 'p'revious page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'j'</dt>
                <dd>Go to 'n'ext item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'k'</dt>
                <dd>Go to 'p'revious item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'o'</dt>
                <dd>'O'pen current item in new tab</dd>
                <dt>'shift' + 'o'</dt>
                <dd>'O'pen current item in current window</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'s'</dt>
                <dd>'S'hare current item (go in <a href="?config" title="configuration">configuration</a> to set up you link)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'a'</dt>
                <dd>Mark 'a'll items, 'a'll items from current feed or 'a'll items from current folder as read</dd>
              </dl>
              <h3>Menu navigation</h3>
              <dl class="dl-horizontal">
                <dt>'h'</dt>
                <dd>Go to 'H'ome page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'v'</dt>
                <dd>Change 'v'iew as list or expanded</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'f'</dt>
                <dd>Show or hide list of 'f'eeds/'f'olders</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'e'</dt>
                <dd>'E'dit current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'u'</dt>
                <dd>'U'pdate current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'r'</dt>
                <dd>'R'eload the page as the 'F5' key in most of browsers</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'?' or 'F1'</dt>
                <dd>Go to Help page (actually it's shortcut to go to this page)</dd>
              </dl>
            </div>

            <div id="section">
              <h2>Check configuration</h2>
              <dl class="dl-horizontal">
                <dt>open_ssl</dt>
                <dd>
                  <?php if (extension_loaded('openssl')) { ?>
                  <span class="text-success">You should be able to load https:// rss links.</span>
                  <?php } else { ?>
                  <span class="text-error">You may have problems using https:// rss links.</span>
                  <?php } ?>
                </dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
