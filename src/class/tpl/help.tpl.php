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
                <dt>'o' or 'v'</dt>
                <dd>'O'pen/'V'iew current item in new tab</dd>
                <dt>'shift' + 'o' or 'shift' + 'v'</dt>
                <dd>'O'pen/'V'iew current item in current window</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'s'</dt>
                <dd>'S'hare current item (go in <a href="?config" title="configuration">configuration</a> to set up you link)</dd>
              </dl>
              <dl class="dl-horizontal">
                <dt>'h'</dt>
                <dd>Go to 'H'ome page</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
