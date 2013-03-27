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
                <dd>When viewing items as list, let you open or close current item (<strong>t</strong>oggle current item)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'m'</dt>
                <dd><strong>M</strong>ark current item as read if unread or unread if read</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'm'</dt>
                <dd><strong>M</strong>ark current item as read if unread or unread if read and open current (useful in list view and unread filter)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'n' or right arrow</dt>
                <dd>Go to <strong>n</strong>ext item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'p' or left arrow</dt>
                <dd>Go to <strong>p</strong>revious item</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'n'</dt>
                <dd>Go to <strong>n</strong>ext page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'shift' + 'p'</dt>
                <dd>Go to <strong>p</strong>revious page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'j'</dt>
                <dd>Go to <strong>n</strong>ext item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'k'</dt>
                <dd>Go to <strong>p</strong>revious item and open it (in list view)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'o'</dt>
                <dd><strong>O</strong>pen current item in new tab</dd>
                <dt>'shift' + 'o'</dt>
                <dd><strong>O</strong>pen current item in current window</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'s'</dt>
                <dd><strong>S</strong>hare current item (go in <a href="?config" title="configuration">configuration</a> to set up your link)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'a'</dt>
                <dd>Mark <strong>a</strong>ll items, <strong>a</strong>ll items from current feed or <strong>a</strong>ll items from current folder as read</dd>
              </dl>
              <h3>Menu navigation</h3>
              <dl class="dl-horizontal">
                <dt>'h'</dt>
                <dd>Go to <strong>H</strong>ome page</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'v'</dt>
                <dd>Change <strong>v</strong>iew as list or expanded</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'f'</dt>
                <dd>Show or hide list of <strong>f</strong>eeds/<strong>f</strong>olders</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'e'</dt>
                <dd><strong>E</strong>dit current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'u'</dt>
                <dd><strong>U</strong>pdate current selection (all, folder or feed)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'r'</dt>
                <dd><strong>R</strong>eload the page as the 'F5' key in most of browsers</dd>
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
