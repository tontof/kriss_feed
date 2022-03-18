/*jshint sub:true, evil:true */

(function () {
  "use strict";
  var view = '', // data-view
      listFeeds = '', // data-list-feeds
      filter = '', // data-filter
      order = '', // data-order
      autoreadItem = '', // data-autoread-item
      autoreadPage = '', // data-autoread-page
      autohide = '', // data-autohide
      byPage = -1, // data-by-page
      shaarli = '', // data-shaarli
      redirector = '', // data-redirector
      currentHash = '', // data-current-hash
      currentPage = 1, // data-current-page
      currentNbItems = 0, // data-nb-items
      autoupdate = false, // data-autoupdate
      autofocus = false, // data-autofocus
      addFavicon = false, // data-add-favicon
      preload = false, // data-preload
      stars = false, // data-stars
      isLogged = false, // data-is-logged
      blank = false, // data-blank
      swipe = '', // data-swipe
      status = '',
      listUpdateFeeds = [],
      listItemsHash = [],
      currentItemHash = '',
      currentUnread = 0,
      title = '',
      cache = {},
      intlTop = 'top',
      intlShare = 'share',
      intlRead = 'read',
      intlUnread = 'unread',
      intlStar = 'star',
      intlUnstar = 'unstar',
      intlFrom = 'from';

  /**
   * trim function
   * https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/String/Trim
   */
  if(!String.prototype.trim) {
    String.prototype.trim = function () {
      return this.replace(/^\s+|\s+$/g,'');
    };
  }
  /**
   * http://javascript.info/tutorial/bubbling-and-capturing
   */
  function stopBubbling(event) {
    if(event.stopPropagation) {
      event.stopPropagation();
    }
    else {
      event.cancelBubble = true;
    }
  }

  /**
   * JSON Object
   * https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/JSON
   */
  if (!window.JSON) {
    window.JSON = {
      parse: function (sJSON) { return eval("(" + sJSON + ")"); },
      stringify: function (vContent) {
        if (vContent instanceof Object) {
          var sOutput = "";
          if (vContent.constructor === Array) {
            for (var nId = 0; nId < vContent.length; sOutput += this.stringify(vContent[nId]) + ",", nId++);
            return "[" + sOutput.substr(0, sOutput.length - 1) + "]";
          }
          if (vContent.toString !== Object.prototype.toString) { return "\"" + vContent.toString().replace(/"/g, "\\$&") + "\""; }
          for (var sProp in vContent) { sOutput += "\"" + sProp.replace(/"/g, "\\$&") + "\":" + this.stringify(vContent[sProp]) + ","; }
          return "{" + sOutput.substr(0, sOutput.length - 1) + "}";
        }
        return typeof vContent === "string" ? "\"" + vContent.replace(/"/g, "\\$&") + "\"" : String(vContent);
      }
    };
  }

  /**
   * https://developer.mozilla.org/en-US/docs/AJAX/Getting_Started
   */
  function getXHR() {
    var httpRequest = false;

    if (window.XMLHttpRequest) { // Mozilla, Safari, ...
      httpRequest = new XMLHttpRequest();
    } else if (window.ActiveXObject) { // IE
      try {
        httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
      }
      catch (e) {
        try {
          httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (e2) {}
      }
    }

    return httpRequest;
  }

  /**
   * http://www.sitepoint.com/xhrrequest-and-javascript-closures/
   */
  // Constructor for generic HTTP client
  function HTTPClient() {}
  HTTPClient.prototype = {
    url: null,
    xhr: null,
    callinprogress: false,
    userhandler: null,
    init: function(url, obj) {
      this.url = url;
      this.obj = obj;
      this.xhr = new getXHR();
    },
    asyncGET: function (handler) {
      // Prevent multiple calls
      if (this.callinprogress) {
        throw "Call in progress";
      }
      this.callinprogress = true;
      this.userhandler = handler;
      // Open an async request - third argument makes it async
      this.xhr.open('GET', this.url, true);
      var self = this;
      // Assign a closure to the onreadystatechange callback
      this.xhr.onreadystatechange = function() {
        self.stateChangeCallback(self);
      };
      this.xhr.send(null);
    },
    stateChangeCallback: function(client) {
      switch (client.xhr.readyState) {
        // Request not yet made
        case 1:
        try { client.userhandler.onInit(); }
        catch (e) { /* Handler method not defined */ }
        break;
        // Contact established with server but nothing downloaded yet
        case 2:
        try {
          // Check for HTTP status 200
          if ( client.xhr.status != 200 ) {
            client.userhandler.onError(
              client.xhr.status,
              client.xhr.statusText
            );
            // Abort the request
            client.xhr.abort();
            // Call no longer in progress
            client.callinprogress = false;
          }
        }
        catch (e) { /* Handler method not defined */ }
        break;
        // Called multiple while downloading in progress
        case 3:
        // Notify user handler of download progress
        try {
          // Get the total content length
          // -useful to work out how much has been downloaded
          var contentLength;
          try {
            contentLength = client.xhr.getResponseHeader("Content-Length");
          }
          catch (e) { contentLength = NaN; }
          // Call the progress handler with what we've got
          client.userhandler.onProgress(
            client.xhr.responseText,
            contentLength
          );
        }
        catch (e) { /* Handler method not defined */ }
        break;
        // Download complete
        case 4:
        try {
          client.userhandler.onSuccess(client.xhr.responseText, client.obj);
        }
        catch (e) { /* Handler method not defined */ }
        finally { client.callinprogress = false; }
        break;
      }
    }
  };

  /**
   * Handler
   */
  var ajaxHandler = {
    onInit: function() {},
    onError: function(status, statusText) {},
    onProgress: function(responseText, length) {},
    onSuccess: function(responseText, noFocus) {
      var result = JSON.parse(responseText);

      if (result['logout'] && isLogged) {
        alert('You have been disconnected');
      }
      if (result['item']) {
        cache['item-' + result['item']['itemHash']] = result['item'];
        loadDivItem(result['item']['itemHash'], noFocus);
      }
      if (result['page']) {
        updateListItems(result['page']);
        setCurrentItem();
        if (preload) {
          preloadItems();
        }
      }
      if (result['read']) {
        markAsRead(result['read']);
      }
      if (result['unread']) {
        markAsUnread(result['unread']);
      }
      if (result['update']) {
        updateNewItems(result['update']);
      }
    }
  };

  /**
   * http://stackoverflow.com/questions/4652734/return-html-from-a-user-selection/4652824#4652824
   */
  function getSelectionHtml() {
    var html = '';
    if (typeof window.getSelection != 'undefined') {
      var sel = window.getSelection();
      if (sel.rangeCount) {
        var container = document.createElement('div');
        for (var i = 0, len = sel.rangeCount; i < len; ++i) {
          container.appendChild(sel.getRangeAt(i).cloneContents());
        }
        html = container.innerHTML;
      }
    } else if (typeof document.selection != 'undefined') {
      if (document.selection.type == 'Text') {
        html = document.selection.createRange().htmlText;
      }
    }
    return html;
  }

  /**
   * Some javascript snippets
   */
  function removeChildren(elt) {
    while (elt.hasChildNodes()) {
      elt.removeChild(elt.firstChild);
    }
  }

  function removeElement(elt) {
    if (elt && elt.parentNode) {
      elt.parentNode.removeChild(elt);
    }
  }

  function addClass(elt, cls) {
    if (elt) {
      elt.className = (elt.className + ' ' + cls).trim();
    }
  }

  function removeClass(elt, cls) {
    if (elt) {
      elt.className = (' ' + elt.className + ' ').replace(cls, ' ').trim();
    }
  }

  function hasClass(elt, cls) {
    if (elt && (' ' + elt.className + ' ').indexOf(' ' + cls + ' ') > -1) {
      return true;
    }
    return false;
  }

  /**
   * Add redirector to link
   */
  function anonymize(elt) {
    if (redirector !== '') {
      var domain, a_to_anon = elt.getElementsByTagName("a");
      for (var i = 0; i < a_to_anon.length; i++) {
        domain = a_to_anon[i].href.replace('http://','').replace('https://','').split(/[/?#]/)[0];
        if (domain !== window.location.host) {
          if (redirector !== 'noreferrer') {
            a_to_anon[i].href = redirector+a_to_anon[i].href;
          } else {
            a_to_anon[i].setAttribute('rel', 'noreferrer');
          }
        }
      }
    }
  }

  function initAnonyme() {
    if (redirector !== '') {
      var i = 0, elements = document.getElementById('list-items');
      elements = elements.getElementsByTagName('div');
      for (i = 0; i < elements.length; i += 1) {
        if (hasClass(elements[i], 'item-content')) {
          anonymize(elements[i]);
        }
      }
    }
  }

  /**
   * Replace collapse bootstrap function
   */
  function collapseElement(element) {
    if (element !== null) {
      var targetElement = document.getElementById(
        element.getAttribute('data-target').substring(1)
      );

      if (hasClass(targetElement, 'in')) {
        removeClass(targetElement, 'in');
        targetElement.style.height = 0;
      } else {
        addClass(targetElement, 'in');
        targetElement.style.height = 'auto';
      }
    }
  }

  function collapseClick(event) {
    event = event || window.event;
    stopBubbling(event);

    collapseElement(this);
  }

  function initCollapse(list) {
    var i = 0;

    for (i = 0; i < list.length; i += 1) {
      if (list[i].hasAttribute('data-toggle') && list[i].hasAttribute('data-target')) {
        addEvent(list[i], 'click', collapseClick);
      }
    }
  }

  /**
   * Shaarli functions
   */
  function htmlspecialchars_decode(string) {
    return string
           .replace(/&lt;/g, '<')
           .replace(/&gt;/g, '>')
           .replace(/&quot;/g, '"')
           .replace(/&amp;/g, '&')
           .replace(/&#0*39;/g, "'")
           .replace(/&nbsp;/g, " ");
  }

  function shaarliItem(itemHash) {
    var domainUrl, url, domainVia, via, title, sel, element;

   element = document.getElementById('item-div-'+itemHash);
    if (element.childNodes.length > 1) {
      title = getTitleItem(itemHash);
      url = getUrlItem(itemHash);
      via = getViaItem(itemHash);
      if (redirector != 'noreferrer') {
        url = url.replace(redirector,'');
        via = via.replace(redirector,'');
      }
      domainUrl = url.replace('http://','').replace('https://','').split(/[/?#]/)[0];
      domainVia = via.replace('http://','').replace('https://','').split(/[/?#]/)[0];
      if (domainUrl !== domainVia) {
        via = 'via ' + via;
      } else {
        via = '';
      }
      sel = getSelectionHtml();
      if (sel !== '') {
        sel = '«' + sel + '»';
      }

      if (shaarli !== '') {
        window.open(
          shaarli
          .replace('${url}', encodeURIComponent(htmlspecialchars_decode(url)))
          .replace('${title}', encodeURIComponent(htmlspecialchars_decode(title)))
          .replace('${via}', encodeURIComponent(htmlspecialchars_decode(via)))
          .replace('${sel}', encodeURIComponent(htmlspecialchars_decode(sel))),
          '_blank',
          'height=390, width=600, menubar=no, toolbar=no, scrollbars=yes, status=no, dialog=1'
        );
      } else {
        alert('Please configure your share link first');
      }
    } else {
      loadDivItem(itemHash);
      alert('Sorry ! This item is not loaded, try again !');
    }
  }

  function shaarliCurrentItem() {
    shaarliItem(currentItemHash);
  }

  function shaarliClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    shaarliItem(getItemHash(this));

    return false;
  }

  /**
   * Folder functions
   */
  function getFolder(element) {
    var folder = null;

    while (folder === null && element !== null) {
      if (element.tagName === 'LI' && element.id.indexOf('folder-') === 0) {
        folder = element;
      }
      element = element.parentNode;
    }

    return folder;
  }

  function getLiParentByClassName(element, classname) {
    var li = null;

    while (li === null && element !== null) {
      if (element.tagName === 'LI' && hasClass(element, classname)) {
        li = element;
      }
      element = element.parentNode;
    }

    if (classname === 'folder' && li.id === 'all-subscriptions') {
      li = null;
    }

    return li;
  }

  function getFolderHash(element) {
    var folder = getFolder(element);

    if (folder !== null) {
      return folder.id.replace('folder-','');
    }

    return null;
  }

  function toggleFolder(folderHash) {
    var i, listElements, url, client;

    listElements = document.getElementById('folder-' + folderHash);
    listElements = listElements.getElementsByTagName('h5');
    if (listElements.length > 0) {
      listElements = listElements[0].getElementsByTagName('span');

      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'folder-toggle-open')) {
          removeClass(listElements[i], 'folder-toggle-open');
          addClass(listElements[i], 'folder-toggle-close');
        } else if (hasClass(listElements[i], 'folder-toggle-close')) {
          removeClass(listElements[i], 'folder-toggle-close');
          addClass(listElements[i], 'folder-toggle-open');
        }
      }
    }

    url = '?toggleFolder=' + folderHash + '&ajax';
    client = new HTTPClient();

    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function toggleClickFolder(event) {
    event = event || window.event;
    stopBubbling(event);

    toggleFolder(getFolderHash(this));

    return false;
  }

  function initLinkFolders(listFolders) {
    var i = 0;

    for (i = 0; i < listFolders.length; i += 1) {
      if (listFolders[i].hasAttribute('data-toggle') && listFolders[i].hasAttribute('data-target')) {
        listFolders[i].onclick = toggleClickFolder;
      }
    }
  }

  function getListLinkFolders() {
    var i = 0,
        listFolders = [],
        listElements = document.getElementById('list-feeds');

    if (listElements) {
      listElements = listElements.getElementsByTagName('a');

      for (i = 0; i < listElements.length; i += 1) {
        listFolders.push(listElements[i]);
      }
    }

    return listFolders;
  }

  /**
   * MarkAs functions
   */
  function toggleMarkAsLinkItem(itemHash) {
    var i, item = getItem(itemHash), listLinks;

    if (item !== null) {
      listLinks = item.getElementsByTagName('a');

      for (i = 0; i < listLinks.length; i += 1) {
        if (hasClass(listLinks[i], 'item-mark-as')) {
          if (listLinks[i].href.indexOf('unread=') > -1) {
            listLinks[i].href = listLinks[i].href.replace('unread=','read=');
            listLinks[i].firstChild.innerHTML = intlRead;
          } else {
            listLinks[i].href = listLinks[i].href.replace('read=','unread=');
            listLinks[i].firstChild.innerHTML = intlUnread;
          }
        }
      }
    }
  }

  function getUnreadLabelItems(itemHash) {
    var i, listLinks, regex = new RegExp('read=' + itemHash.substr(0,6)), items = [];
    listLinks = getListLinkFolders();
    for (i = 0; i < listLinks.length; i += 1) {
      if (regex.test(listLinks[i].href)) {
        items.push(listLinks[i].children[0]);
      }
    }
    return items;
  }

  function addToUnreadLabel(unreadLabelItem, value) {
      var unreadLabel = -1;
      if (unreadLabelItem !== null) {
        unreadLabel = parseInt(unreadLabelItem.innerHTML, 10) + value;
        unreadLabelItem.innerHTML = unreadLabel;
      }
      return unreadLabel;
  }

  function getUnreadLabel(folder) {
    var element = null;
    if (folder !== null) {
      element = folder.getElementsByClassName('label')[0];
    }
    return element;
  }

  function markAsItem(itemHash) {
    var item, url, client, indexItem, i, unreadLabelItems, nb, feed, folder, addRead = 1;

    item = getItem(itemHash);

    if (item !== null) {
      unreadLabelItems = getUnreadLabelItems(itemHash);
      if (!hasClass(item, 'read')) {
        addRead = -1;
      }
      for (i = 0; i < unreadLabelItems.length; i += 1) {
        nb = addToUnreadLabel(unreadLabelItems[i], addRead);
        if (nb === 0) {
          feed = getLiParentByClassName(unreadLabelItems[i], 'feed');
          removeClass(feed, 'has-unread');
          if (autohide) {
            addClass(feed, 'autohide-feed');
          }
        }
        folder = getLiParentByClassName(unreadLabelItems[i], 'folder');
        nb = addToUnreadLabel(getUnreadLabel(folder), addRead);
        if (nb === 0 && autohide) {
          addClass(folder, 'autohide-folder');
        }
      }
      addToUnreadLabel(getUnreadLabel(document.getElementById('all-subscriptions')), addRead);

      if (hasClass(item, 'read')) {
        url = '?unread=' + itemHash;
        removeClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
      } else {
        url = '?read=' + itemHash;
        addClass(item, 'read');
        toggleMarkAsLinkItem(itemHash);
        if (filter === 'unread') {
          url += '&currentHash=' + currentHash +
            '&page=' + currentPage +
            '&last=' + listItemsHash[listItemsHash.length - 1];

          removeElement(item);
          indexItem = listItemsHash.indexOf(itemHash);
          listItemsHash.splice(listItemsHash.indexOf(itemHash), 1);
          if (listItemsHash.length <= byPage) {
            appendItem(listItemsHash[listItemsHash.length - 1]);
          }
          setCurrentItem(listItemsHash[indexItem]);
        }
      }
    } else {
      url = '?currentHash=' + currentHash +
        '&page=' + currentPage;
    }

    client = new HTTPClient();
    client.init(url + '&ajax');
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function markAsCurrentItem() {
    markAsItem(currentItemHash);
  }

  function markAsClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    markAsItem(getItemHash(this));

    return false;
  }

  function toggleMarkAsStarredLinkItem(itemHash) {
    var i, item = getItem(itemHash), listLinks, url = '';

    if (item !== null) {
      listLinks = item.getElementsByTagName('a');

      for (i = 0; i < listLinks.length; i += 1) {
        if (hasClass(listLinks[i], 'item-starred')) {
          url = listLinks[i].href;
          if (listLinks[i].href.indexOf('unstar=') > -1) {
            listLinks[i].href = listLinks[i].href.replace('unstar=','star=');
            listLinks[i].firstChild.innerHTML = intlStar;
          } else {
            listLinks[i].href = listLinks[i].href.replace('star=','unstar=');
            listLinks[i].firstChild.innerHTML = intlUnstar;
          }
        }
      }
    }

    return url;
  }

  function markAsStarredCurrentItem() {
    markAsStarredItem(currentItemHash);
  }

  function markAsStarredItem(itemHash) {
    var url, client, indexItem;

    url = toggleMarkAsStarredLinkItem(itemHash);
    if (url.indexOf('unstar=') > -1 && stars) {
      removeElement(getItem(itemHash));
      indexItem = listItemsHash.indexOf(itemHash);
      listItemsHash.splice(listItemsHash.indexOf(itemHash), 1);
      if (listItemsHash.length <= byPage) {
        appendItem(listItemsHash[listItemsHash.length - 1]);
      }
      setCurrentItem(listItemsHash[indexItem]);

      url += '&page=' + currentPage;
    }
    if (url !== '') {
      client = new HTTPClient();
      client.init(url + '&ajax');
      try {
        client.asyncGET(ajaxHandler);
      } catch (e) {
        alert(e);
      }
    }
  }

  function markAsStarredClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    markAsStarredItem(getItemHash(this));

    return false;
  }

  function markAsRead(itemHash) {
    setNbUnread(currentUnread - 1);
  }

  function markAsUnread(itemHash) {
    setNbUnread(currentUnread + 1);
  }

  /**
   * Div item functions
   */
  function loadDivItem(itemHash, noFocus) {
    var element, url, client, cacheItem;
    element = document.getElementById('item-div-'+itemHash);
    if (element.childNodes.length <= 1) {
      cacheItem = getCacheItem(itemHash);
      if (cacheItem !== null) {
        setDivItem(element, cacheItem);
        if(!noFocus) {
          setItemFocus(element);
        }
        removeCacheItem(itemHash);
      } else {
        url = '?'+(stars?'stars&':'')+'currentHash=' + currentHash +
          '&current=' + itemHash +
          '&ajax';
        client = new HTTPClient();
        client.init(url, noFocus);
        try {
          client.asyncGET(ajaxHandler);
        } catch (e) {
          alert(e);
        }
      }
    }
  }

  function toggleItem(itemHash) {
    var i, listElements, element, targetElement;

    if (view === 'expanded') {
      return;
    }

    if (currentItemHash != itemHash) {
      closeCurrentItem();
    }

    // looking for ico + or -
    listElements = document.getElementById('item-toggle-' + itemHash);
    listElements = listElements.getElementsByTagName('span');
    for (i = 0; i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-toggle-open')) {
        removeClass(listElements[i], 'item-toggle-open');
        addClass(listElements[i], 'item-toggle-close');
      } else if (hasClass(listElements[i], 'item-toggle-close')) {
        removeClass(listElements[i], 'item-toggle-close');
        addClass(listElements[i], 'item-toggle-open');
      }
    }

    element = document.getElementById('item-toggle-'+itemHash);
    targetElement = document.getElementById(
      element.getAttribute('data-target').substring(1)
    );
    if (element.href.indexOf('&open') > -1) {
      element.href = element.href.replace('&open','');
      addClass(targetElement, 'well');
      setCurrentItem(itemHash);
      loadDivItem(itemHash);
    } else {
      element.href = element.href + '&open';
      removeClass(targetElement, 'well');
    }
  }

  function toggleCurrentItem() {
    toggleItem(currentItemHash);
    collapseElement(document.getElementById('item-toggle-' + currentItemHash));
  }

  function toggleClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    toggleItem(getItemHash(this));

    return false;
  }

  /**
   * Item management functions
   */
  function getItem(itemHash) {
    return document.getElementById('item-' + itemHash);
  }

  function getTitleItem(itemHash) {
    var i = 0, element = document.getElementById('item-div-'+itemHash), listElements = element.getElementsByTagName('a'), title = '';

    for (i = 0; title === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-link')) {
        title = listElements[i].innerHTML;
      }
    }

    return title;
  }

  function getUrlItem(itemHash) {
    var i = 0, element = document.getElementById('item-'+itemHash), listElements = element.getElementsByTagName('a'), url = '';

    for (i = 0; url === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-link')) {
        url = listElements[i].href;
      }
    }

    return url;
  }

  function getViaItem(itemHash) {
    var i = 0, element = document.getElementById('item-div-'+itemHash), listElements = element.getElementsByTagName('a'), via = '';

    for (i = 0; via === '' && i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-via')) {
        via = listElements[i].href;
      }
    }

    return via;
  }

  function getLiItem(element) {
    var item = null;

    while (item === null && element !== null) {
      if (element.tagName === 'LI' && element.id.indexOf('item-') === 0) {
        item = element;
      }
      element = element.parentNode;
    }

    return item;
  }

  function getItemHash(element) {
    var item = getLiItem(element);

    if (item !== null) {
      return item.id.replace('item-','');
    }

    return null;
  }

  function getCacheItem(itemHash) {
    if (typeof cache['item-' + itemHash] !== 'undefined') {
      return cache['item-' + itemHash];
    }

    return null;
  }

  function removeCacheItem(itemHash) {
    if (typeof cache['item-' + itemHash] !== 'undefined') {
      delete cache['item-' + itemHash];
    }
  }

  function isCurrentUnread() {
    var item = getItem(currentItemHash);

    if (hasClass(item, 'read')) {
      return false;
    }

    return true;
  }

  function setDivItem(div, item) {
    var markAs = intlRead, starred = intlStar, target = ' target="_blank"', linkMarkAs = 'read', linkStarred = 'star';

    if (item['read'] == 1) {
      markAs = intlUnread;
      linkMarkAs = 'unread';
    }

    if (item['starred'] == 1) {
      starred = intlUnstar;
      linkStarred = 'unstar';
    }

    if (!blank) {
      target = '';
    }

    div.innerHTML = '<div class="item-title">' +
      '<a class="item-shaarli" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label">' + intlShare + '</span></a> ' +
      (stars?'':
      '<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + linkMarkAs + '=' + item['itemHash'] + '"><span class="label item-label-mark-as">' + markAs + '</span></a> ') +
      '<a class="item-starred" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + linkStarred + '=' + item['itemHash'] + '"><span class="label item-label-starred">' + starred + '</span></a> ' +
      '<a' + target + ' class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a>' +
      '</div>' +
      '<div class="clear"></div>' +
      '<div class="item-info-end item-info-time">' +
      item['time']['expanded'] +
      '</div>' +
      '<div class="item-info-end item-info-authors">' +
      intlFrom + ' <a class="item-via" href="' + item['via'] + '">' +
      item['author'] +
      '</a> ' +
      '<a class="item-xml" href="' + item['xmlUrl'] + '">' +
      '<span class="ico">' +
      '<span class="ico-feed-dot"></span>' +
      '<span class="ico-feed-circle-1"></span>' +
      '<span class="ico-feed-circle-2"></span>'+
      '</span>' +
      '</a>' +
      '</div>' +
      '<div class="clear"></div>' +
      '<div class="item-content"><article>' +
      item['content'] +
      '</article></div>' +
      '<div class="clear"></div>' +
      '<div class="item-info-end">' +
      '<a class="item-top" href="#status"><span class="label label-expanded">' + intlTop + '</span></a> ' +
      '<a class="item-shaarli" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&shaarli=' + item['itemHash'] + '"><span class="label label-expanded">' + intlShare + '</span></a> ' +
      (stars?'':
      '<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + linkMarkAs + '=' + item['itemHash'] + '"><span class="label item-label-mark-as label-expanded">' + markAs + '</span></a> ') +
      '<a class="item-starred" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + linkStarred + '=' + item['itemHash'] + '"><span class="label label-expanded">' + starred + '</span></a>' +
      (view=='list'?
      '<a id="item-toggle-'+ item['itemHash'] +'" class="item-toggle item-toggle-plus" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] +'&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '"> ' +
      '<span class="ico ico-toggle-item">' +
      '<span class="ico-b-disc"></span>' +
      '<span class="ico-w-line-h"></span>' +
      '</span>' +
      '</a>':'') +
      '</div>' +
      '<div class="clear"></div>';

    initLinkItems(div.getElementsByTagName('a'));
    initCollapse(div.getElementsByTagName('a'));
    anonymize(div);
  }

  function setLiItem(li, item) {
    var markAs = intlRead, target = ' target="_blank"';

    if (item['read'] == 1) {
      markAs = intlUnread;
    }

    if (!blank) {
      target = '';
    }

    li.innerHTML = '<a id="item-toggle-'+ item['itemHash'] +'" class="item-toggle item-toggle-plus" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] +'&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '"> ' +
      '<span class="ico ico-toggle-item">' +
      '<span class="ico-b-disc"></span>' +
      '<span class="ico-w-line-h"></span>' +
      '<span class="ico-w-line-v item-toggle-close"></span>' +
      '</span>' +
      item['time']['list'] +
      '</a>' +
      '<dl class="dl-horizontal item">' +
      '<dt class="item-feed">' +
      (addFavicon?
      '<span class="item-favicon">' +
      '<img src="' + item['favicon'] + '" height="16" width="16" title="favicon" alt="favicon"/>' +
      '</span>':'' ) +
      '<span class="item-author">' +
      '<a class="item-feed" href="?'+(stars?'stars&':'')+'currentHash=' + item['itemHash'].substring(0, 6) + '">' +
      item['author'] +
      '</a>' +
      '</span>' +
      '</dt>' +
      '<dd class="item-info">' +
      '<span class="item-title">' +
      (stars?'':'<a class="item-mark-as" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&' + markAs + '=' + item['itemHash'] + '"><span class="label">' + markAs + '</span></a> ') +
      '<a' + target + ' class="item-link" href="' + item['link'] + '">' +
      item['title'] +
      '</a> ' +
      '</span>' +
      '<span class="item-description">' +
      '<a class="item-toggle muted" href="' + '?'+(stars?'stars&':'')+'currentHash=' + currentHash + '&current=' + item['itemHash'] + '&open" data-toggle="collapse" data-target="#item-div-'+ item['itemHash'] + '">' +
      item['description'] +
      '</a> ' +
      '</span>' +
      '</dd>' +
      '</dl>' +
      '<div class="clear"></div>';

    initCollapse(li.getElementsByTagName('a'));
    initLinkItems(li.getElementsByTagName('a'));

    anonymize(li);
  }

  function createLiItem(item) {
    var li = document.createElement('li'),
        div = document.createElement('div');

    div.id = 'item-div-'+item['itemHash'];
    div.className= 'item collapse'+(view === 'expanded' ? ' in well' : '');

    li.id = 'item-'+item['itemHash'];
    if (view === 'list') {
      li.className = 'item-list';
      setLiItem(li, item);
    } else {
      li.className = 'item-expanded';
      setDivItem(div, item);
    }
    li.className += (item['read'] === 1)?' read':'';
    li.appendChild(div);

    return li;
  }

  /**
   * List items management functions
   */
  function getListItems() {
    return document.getElementById('list-items');
  }

  function updateListItems(itemsList) {
    var i;

    for (i = 0; i < itemsList.length; i++) {
      if (listItemsHash.indexOf(itemsList[i]['itemHash']) === -1 && listItemsHash.length <= byPage) {
        cache['item-' + itemsList[i]['itemHash']] = itemsList[i];
        listItemsHash.push(itemsList[i]['itemHash']);
        if (listItemsHash.length <= byPage) {
          appendItem(itemsList[i]['itemHash']);
        }
      }
    }
  }

  function appendItem(itemHash) {
    var listItems = getListItems(),
        item = getCacheItem(itemHash),
        li;

    if (item !== null) {
      li = createLiItem(item);
      listItems.appendChild(li);
      removeCacheItem(itemHash);
    }
  }

  function getListLinkItems() {
    var i = 0,
        listItems = [],
        listElements = document.getElementById('list-items');

    listElements = listElements.getElementsByTagName('a');

    for (i = 0; i < listElements.length; i += 1) {
      listItems.push(listElements[i]);
    }

    return listItems;
  }

  function initListItemsHash() {
    var i,
        listElements = document.getElementById('list-items');

    listElements = listElements.getElementsByTagName('li');
    for (i = 0; i < listElements.length; i += 1) {
      if (hasClass(listElements[i], 'item-list') || hasClass(listElements[i], 'item-expanded')) {
        if (hasClass(listElements[i], 'current')) {
          currentItemHash = getItemHash(listElements[i]);
        }
        listItemsHash.push(listElements[i].id.replace('item-',''));
      }
    }
  }

  function initLinkItems(listItems) {
    var i = 0;

    for (i = 0; i < listItems.length; i += 1) {
      if (hasClass(listItems[i], 'item-toggle')) {
        listItems[i].onclick = toggleClickItem;
      }
      if (hasClass(listItems[i], 'item-mark-as')) {
        listItems[i].onclick = markAsClickItem;
      }
      if (hasClass(listItems[i], 'item-starred')) {
        listItems[i].onclick = markAsStarredClickItem;
      }
      if (hasClass(listItems[i], 'item-shaarli')) {
        listItems[i].onclick = shaarliClickItem;
      }
    }
  }

  function initListItems() {
    var url, client;

    url = '?currentHash=' + currentHash +
      '&page=' + currentPage +
      '&last=' + listItemsHash[listItemsHash.length -1] +
      '&ajax' +
      (stars?'&stars':'');

    client = new HTTPClient();
    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function preloadItems()
  {
    // Pre-fetch items from top to bottom
    for(var i = 0, len = listItemsHash.length; i < len; ++i)
    {
      loadDivItem(listItemsHash[i], true);
    }
  }

  /**
   * Update
   */
  function setStatus(text) {
    if (text === '') {
      document.getElementById('status').innerHTML = status;
    } else {
      document.getElementById('status').innerHTML = text;
    }
  }

  function getTimeMin() {
    return Math.round((new Date().getTime()) / 1000 / 60);
  }

  function updateFeed(feedHashIndex) {
    var i = 0, url, client, feedHash = '';

    if (feedHashIndex !== '') {
      setStatus('updating ' + listUpdateFeeds[feedHashIndex][1]);
      feedHash = listUpdateFeeds[feedHashIndex][0];
      listUpdateFeeds[feedHashIndex][2] = getTimeMin();
    }

    url = '?update'+(feedHash === ''?'':'='+feedHash)+'&ajax';

    client = new HTTPClient();
    client.init(url);
    try {
      client.asyncGET(ajaxHandler);
    } catch (e) {
      alert(e);
    }
  }

  function updateNextFeed() {
    var i = 0, nextTimeUpdate = 0, currentMin, diff, minDiff = -1, feedToUpdateIndex = '', minFeedToUpdateIndex = '';
    if (listUpdateFeeds.length !== 0) {
      currentMin = getTimeMin();
      for (i = 0; feedToUpdateIndex === '' && i < listUpdateFeeds.length; i++) {
        diff = currentMin - listUpdateFeeds[i][2];
        if (diff >= listUpdateFeeds[i][3]) {
          //need update
          feedToUpdateIndex = i;
        } else {
          if (minDiff === -1 || diff < minDiff) {
            minDiff = diff;
            minFeedToUpdateIndex = i;
          }
        }
      }
      if (feedToUpdateIndex === '') {
        feedToUpdateIndex = minFeedToUpdateIndex;
      }
      updateFeed(feedToUpdateIndex);
    } else {
      updateFeed('');
    }
  }

  function updateTimeout() {
    var i = 0, nextTimeUpdate = 0, currentMin, diff, minDiff = -1, feedToUpdateIndex = '';

    if (listUpdateFeeds.length !== 0) {
      currentMin = getTimeMin();
      for (i = 0; minDiff !== 0 && i < listUpdateFeeds.length; i++) {
        diff = currentMin - listUpdateFeeds[i][2];
        if (diff >= listUpdateFeeds[i][3]) {
          //need update
          minDiff = 0;
        } else {
          if (minDiff === -1 || (listUpdateFeeds[i][3] - diff) < minDiff) {
            minDiff = listUpdateFeeds[i][3] - diff;
          }
        }
      }
      window.setTimeout(updateNextFeed, minDiff * 1000 * 60 + 200);
    }
  }

  function updateNewItems(result) {
    var i = 0, list, currentMin, folder, feed, unreadLabelItems, nbItems;
    setStatus('');
    if (result !== false) {
      if (result['feeds']) {
        // init list of feeds information for update
        listUpdateFeeds = result['feeds'];
        currentMin = getTimeMin();
        for (i = 0; i < listUpdateFeeds.length; i++) {
          listUpdateFeeds[i][2] = currentMin - listUpdateFeeds[i][2];
        }
      }
      if (result.newItems && result.newItems.length > 0) {
        nbItems = result.newItems.length;
        currentNbItems += nbItems;
        setNbUnread(currentUnread + nbItems);
        addToUnreadLabel(getUnreadLabel(document.getElementById('all-subscriptions')), nbItems);
        unreadLabelItems = getUnreadLabelItems(result.newItems[0].substr(0,6));
        for (i = 0; i < unreadLabelItems.length; i += 1) {
          feed = getLiParentByClassName(unreadLabelItems[i], 'feed');
          folder = getLiParentByClassName(feed, 'folder');
          addClass(feed, 'has-unread');
          if (autohide) {
            removeClass(feed, 'autohide-feed');
            removeClass(folder, 'autohide-folder');
          }
          addToUnreadLabel(getUnreadLabel(feed), nbItems);
          addToUnreadLabel(getUnreadLabel(folder), nbItems);
        }
      }
      updateTimeout();
    }
  }

  function initUpdate() {
    window.setTimeout(updateNextFeed, 1000);
  }

  /**
   * Navigation
   */
  function setItemFocus(item) {
    if(autofocus) {
      // First, let the browser do some rendering
      // Indeed, the div might not be visible yet, so there is no scroll
      setTimeout(function()
      {
        // Dummy implementation
        var container = document.getElementById('main-container'),
          scrollPos = container.scrollTop,
          itemPos = item.offsetTop,
          temp = item;
        while(temp.offsetParent != document.body) {
          temp = temp.offsetParent;
          itemPos += temp.offsetTop;
        }
        var current = itemPos - scrollPos;
        // Scroll only if necessary
        // current < 0: Happens when asking for previous item and displayed item is filling the screen
        // Or check if item bottom is outside screen
        if(current < 0 || current + item.offsetHeight > container.clientHeight) {
          container.scrollTop = itemPos;
        }
      }, 0);
      
      window.location.hash = '#item-' + currentItemHash;
    }
  }

  function previousClickPage(event) {
    event = event || window.event;
    stopBubbling(event);

    previousPage();

    return false;
  }

  function nextClickPage(event) {
    event = event || window.event;
    stopBubbling(event);

    nextPage();

    return false;
  }

  function nextPage() {
    currentPage = currentPage + 1;
    if (currentPage > Math.ceil(currentNbItems / byPage)) {
      currentPage = Math.ceil(currentNbItems / byPage);
    }
    if (listItemsHash.length === 0) {
      currentPage = 1;
    }
    listItemsHash = [];
    initListItems();
    removeChildren(getListItems());
  }

  function previousPage() {
    currentPage = currentPage - 1;
    if (currentPage < 1) {
      currentPage = 1;
    }
    listItemsHash = [];
    initListItems();
    removeChildren(getListItems());
  }

  function previousClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    previousItem();

    return false;
  }

  function nextClickItem(event) {
    event = event || window.event;
    stopBubbling(event);

    nextItem();

    return false;
  }

  function nextItem() {
    var nextItemIndex = listItemsHash.indexOf(currentItemHash) + 1, nextCurrentItemHash;

    closeCurrentItem();
    if (autoreadItem && isCurrentUnread()) {
      markAsCurrentItem();
      if (filter == 'unread') {
        nextItemIndex -= 1;
      }
    }

    if (nextItemIndex < 0) { nextItemIndex = 0; }

    if (nextItemIndex < listItemsHash.length) {
      nextCurrentItemHash = listItemsHash[nextItemIndex];
    }

    if (nextItemIndex >= byPage) {
      nextPage();
    } else {
      setCurrentItem(nextCurrentItemHash);
    }
  }

  function previousItem() {
    var previousItemIndex = listItemsHash.indexOf(currentItemHash) - 1, previousCurrentItemHash;

    if (previousItemIndex < listItemsHash.length && previousItemIndex >= 0) {
      previousCurrentItemHash = listItemsHash[previousItemIndex];
    }

    closeCurrentItem();
    if (previousItemIndex < 0) {
      previousPage();
    } else {
      setCurrentItem(previousCurrentItemHash);
    }
  }

  function closeCurrentItem() {
    var element = document.getElementById('item-toggle-' + currentItemHash);

    if (element && view === 'list') {
      var targetElement = document.getElementById(
            element.getAttribute('data-target').substring(1)
          );

      if (element.href.indexOf('&open') < 0) {
        element.href = element.href + '&open';
        removeClass(targetElement, 'well');
        collapseElement(element);
      }

      var i = 0,
          listElements = element.getElementsByTagName('span');

      // looking for ico + or -
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'item-toggle-open')) {
          removeClass(listElements[i], 'item-toggle-open');
          addClass(listElements[i], 'item-toggle-close');
        }
      }
    }
  }

  function setCurrentItem(itemHash) {
    var currentItemIndex;

    if (itemHash !== currentItemHash) {
      removeClass(document.getElementById('item-'+currentItemHash), 'current');
      removeClass(document.getElementById('item-div-'+currentItemHash), 'current');
      if (typeof itemHash !== 'undefined') {
        currentItemHash = itemHash;
      }
      currentItemIndex = listItemsHash.indexOf(currentItemHash);
      if (currentItemIndex === -1) {
        if (listItemsHash.length > 0) {
          currentItemHash = listItemsHash[0];
        } else {
          currentItemHash = '';
        }
      } else {
        if (currentItemIndex >= byPage) {
          currentItemHash = listItemsHash[byPage - 1];
        }
      }

      if (currentItemHash !== '') {
        var item = document.getElementById('item-'+currentItemHash),
          itemDiv = document.getElementById('item-div-'+currentItemHash);
        addClass(item, 'current');
        addClass(itemDiv, 'current');
        setItemFocus(itemDiv);
        updateItemButton();
      }
    }
    updatePageButton();
  }

  function openCurrentItem(blank) {
    var url;

    url = getUrlItem(currentItemHash);
    if (blank) {
      window.location.href = url;
    } else {
      window.open(url);
    }
  }

  // http://code.jquery.com/mobile/1.1.0/jquery.mobile-1.1.0.js (swipe)
  function checkMove(e) {
    // More than this horizontal displacement, and we will suppress scrolling.
    var scrollSupressionThreshold = 10,
    // More time than this, and it isn't a swipe.
        durationThreshold = 500,
    // Swipe horizontal displacement must be more than this.
        horizontalDistanceThreshold = 30,
    // Swipe vertical displacement must be less than this.
        verticalDistanceThreshold = 75;

    if (e.targetTouches.length == 1) {
      var touch = e.targetTouches[0],
      start = { time: ( new Date() ).getTime(),
                coords: [ touch.pageX, touch.pageY ] },
      stop;
      var moveHandler = function ( e ) {

        if ( !start ) {
          return;
        }

        if (e.targetTouches.length == 1) {
          var touch = e.targetTouches[0];
          stop = { time: ( new Date() ).getTime(),
                   coords: [ touch.pageX, touch.pageY ] };
        }
      };

      if (swipe) {
          addEvent(window, 'touchmove', moveHandler);
          addEvent(window, 'touchend', function (e) {
            removeEvent(window, 'touchmove', moveHandler);
            if ( start && stop ) {
              if ( stop.time - start.time < durationThreshold &&
                Math.abs( start.coords[ 0 ] - stop.coords[ 0 ] ) > horizontalDistanceThreshold &&
                Math.abs( start.coords[ 1 ] - stop.coords[ 1 ] ) < verticalDistanceThreshold
                 ) {
                if ( start.coords[0] > stop.coords[ 0 ] ) {
                  nextItem();
                }
                else {
                  previousItem();
                }
              }
              start = stop = undefined;
            }
          });
      }
    }
  }

  function checkKey(e) {
    var code;
    if (!e) e = window.event;
    if (e.keyCode) code = e.keyCode;
    else if (e.which) code = e.which;

    if (!e.ctrlKey && !e.altKey) {
      switch(code) {
        case 32: // 'space'
        toggleCurrentItem();
        break;
        case 65: // 'A'
        if (window.confirm('Mark all current as read ?')) {
          window.location.href = '?read=' + currentHash;
        }
		break;
        case 67: // 'C'
        window.location.href = '?config';
        break;
        case 69: // 'E'
        window.location.href = (currentHash===''?'?edit':'?edit='+currentHash);
        break;
        case 70: // 'F'
        if (listFeeds =='show') {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'listFeeds=hide';
        } else {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'listFeeds=show';
        }
        break;
        case 72: // 'H'
        window.location.href = document.getElementById('nav-home').href;
        break;
        case 74: // 'J'
        nextItem();
        toggleCurrentItem();
        break;
        case 75: // 'K'
        previousItem();
        toggleCurrentItem();
        break;
        case 77: // 'M'
        if (e.shiftKey) {
          markAsCurrentItem();
          toggleCurrentItem();
        } else {
          markAsCurrentItem();
        }
        break;
        case 39: // right arrow
        case 78: // 'N'
        if (e.shiftKey) {
          nextPage();
        } else {
          nextItem();
        }
        break;
        case 79: // 'O'
        if (e.shiftKey) {
          openCurrentItem(true);
        } else {
          openCurrentItem(false);
        }
        break;
        case 37: // left arrow
        case 80 : // 'P'
        if (e.shiftKey) {
          previousPage();
        } else {
          previousItem();
        }
        break;
        case 82: // 'R'
        window.location.reload(true);
        break;
        case 83: // 'S'
        shaarliCurrentItem();
        break;
        case 84: // 'T'
        toggleCurrentItem();
        break;
        case 85: // 'U'
        window.location.href = (currentHash===''?'?update':'?currentHash=' + currentHash + '&update='+currentHash);
        break;
        case 86: // 'V'
        if (view == 'list') {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'view=expanded';
        } else {
          window.location.href = (currentHash===''?'?':'?currentHash='+currentHash+'&')+'view=list';
        }
        break;
        case 90: // 'z'
          for (var i=0;i<listItemsHash.length;i++){
	      if (!hasClass(getItem(listItemsHash[i]), 'read')){
		  window.open(getUrlItem(currentItemHash),'_blank');
		  markAsCurrentItem();
	      }
	      nextItem();
          }
        break;
        case 170: // '*'
          markAsStarredCurrentItem();
          break;
        case 112: // 'F1'
        case 188: // '?'
        case 191: // '?'
        window.location.href = '?help';
        break;
        default:
        break;
      }
    }
    // e.ctrlKey e.altKey e.shiftKey
  }

  function initPageButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].onclick = previousClickPage;
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].onclick = nextClickPage;
        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].onclick = previousClickPage;
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].onclick = nextClickPage;
        }
      }
    }
  }

  function updatePageButton() {
    var i = 0, paging, listElements, maxPage;

    if (filter == 'unread') {
      currentNbItems = currentUnread;
    }

    if (currentNbItems < byPage) {
      maxPage = 1;
    } else {
      maxPage = Math.ceil(currentNbItems / byPage);
    }

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previousPage=' + currentPage;
          if (currentPage === 1) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&nextPage=' + currentPage;
          if (currentPage === maxPage) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
      }
      listElements = paging.getElementsByTagName('button');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'current-max-page')) {
          listElements[i].innerHTML = currentPage + ' / ' + maxPage;
        }
      }
    }
    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previousPage=' + currentPage;
          if (currentPage === 1) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
        if (hasClass(listElements[i], 'next-page')) {
          listElements[i].href = '?currentHash=' + currentHash + '&nextPage=' + currentPage;
          if (currentPage === maxPage) {
            if (!hasClass(listElements[i], 'disabled')) {
              addClass(listElements[i], 'disabled');
            }
          } else {
            if (hasClass(listElements[i], 'disabled')) {
              removeClass(listElements[i], 'disabled');
            }
          }
        }
      }
      listElements = paging.getElementsByTagName('button');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'current-max-page')) {
          listElements[i].innerHTML = currentPage + ' / ' + maxPage;
        }
      }
    }
  }

  function initItemButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].onclick = previousClickItem;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].onclick = nextClickItem;
        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].onclick = previousClickItem;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].onclick = nextClickItem;
        }
      }
    }
  }

  function updateItemButton() {
    var i = 0, paging, listElements;

    paging = document.getElementById('paging-up');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&next=' + currentItemHash;

        }
      }
    }

    paging = document.getElementById('paging-down');
    if (paging) {
      listElements = paging.getElementsByTagName('a');
      for (i = 0; i < listElements.length; i += 1) {
        if (hasClass(listElements[i], 'previous-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&previous=' + currentItemHash;
        }
        if (hasClass(listElements[i], 'next-item')) {
          listElements[i].href = '?currentHash=' + currentHash + '&next=' + currentItemHash;
        }
      }
    }
  }

  /**
   * init KrISS feed javascript
   */
  function initUnread() {
    var element = document.getElementById((stars?'nb-starred':'nb-unread'));

    currentUnread = parseInt(element.innerHTML, 10);

    title = document.title;
    setNbUnread(currentUnread);
  }

  function setNbUnread(nb) {
    var element = document.getElementById((stars?'nb-starred':'nb-unread'));

    if (nb < 0) {
      nb = 0;
    }

    currentUnread = nb;
    element.innerHTML = currentUnread;
    document.title = title + ' (' + currentUnread + ')';
  }

  function initOptions() {
    var elementIndex = document.getElementById('index');

    if (elementIndex.hasAttribute('data-view')) {
      view = elementIndex.getAttribute('data-view');
    }
    if (elementIndex.hasAttribute('data-list-feeds')) {
      listFeeds = elementIndex.getAttribute('data-list-feeds');
    }
    if (elementIndex.hasAttribute('data-filter')) {
      filter = elementIndex.getAttribute('data-filter');
    }
    if (elementIndex.hasAttribute('data-order')) {
      order = elementIndex.getAttribute('data-order');
    }
    if (elementIndex.hasAttribute('data-autoread-item')) {
      autoreadItem = parseInt(elementIndex.getAttribute('data-autoread-item'), 10);
      autoreadItem = (autoreadItem === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autoread-page')) {
      autoreadPage = parseInt(elementIndex.getAttribute('data-autoread-page'), 10);
      autoreadPage = (autoreadPage === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autohide')) {
      autohide = parseInt(elementIndex.getAttribute('data-autohide'), 10);
      autohide = (autohide === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autofocus')) {
      autofocus = parseInt(elementIndex.getAttribute('data-autofocus'), 10);
      autofocus = (autofocus === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-autoupdate')) {
      autoupdate = parseInt(elementIndex.getAttribute('data-autoupdate'), 10);
      autoupdate = (autoupdate === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-by-page')) {
      byPage = parseInt(elementIndex.getAttribute('data-by-page'), 10);
    }
    if (elementIndex.hasAttribute('data-shaarli')) {
      shaarli = elementIndex.getAttribute('data-shaarli');
    }
    if (elementIndex.hasAttribute('data-redirector')) {
      redirector = elementIndex.getAttribute('data-redirector');
    }
    if (elementIndex.hasAttribute('data-current-hash')) {
      currentHash = elementIndex.getAttribute('data-current-hash');
    }
    if (elementIndex.hasAttribute('data-current-page')) {
      currentPage = parseInt(elementIndex.getAttribute('data-current-page'), 10);
    }
    if (elementIndex.hasAttribute('data-nb-items')) {
      currentNbItems = parseInt(elementIndex.getAttribute('data-nb-items'), 10);
    }
    if (elementIndex.hasAttribute('data-add-favicon')) {
      addFavicon = parseInt(elementIndex.getAttribute('data-add-favicon'), 10);
      addFavicon = (addFavicon === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-preload')) {
      preload = parseInt(elementIndex.getAttribute('data-preload'), 10);
      preload = (preload === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-stars')) {
      stars = parseInt(elementIndex.getAttribute('data-stars'), 10);
      stars = (stars === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-blank')) {
      blank = parseInt(elementIndex.getAttribute('data-blank'), 10);
      blank = (blank === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-is-logged')) {
      isLogged = parseInt(elementIndex.getAttribute('data-is-logged'), 10);
      isLogged = (isLogged === 1)?true:false;
    }
    if (elementIndex.hasAttribute('data-intl-top')) {
      intlTop = elementIndex.getAttribute('data-intl-top');
    }
    if (elementIndex.hasAttribute('data-intl-share')) {
      intlShare = elementIndex.getAttribute('data-intl-share');
    }
    if (elementIndex.hasAttribute('data-intl-read')) {
      intlRead = elementIndex.getAttribute('data-intl-read');
    }
    if (elementIndex.hasAttribute('data-intl-unread')) {
      intlUnread = elementIndex.getAttribute('data-intl-unread');
    }
    if (elementIndex.hasAttribute('data-intl-star')) {
      intlStar = elementIndex.getAttribute('data-intl-star');
    }
    if (elementIndex.hasAttribute('data-intl-unstar')) {
      intlUnstar = elementIndex.getAttribute('data-intl-unstar');
    }
    if (elementIndex.hasAttribute('data-intl-from')) {
      intlFrom = elementIndex.getAttribute('data-intl-from');
    }
    if (elementIndex.hasAttribute('data-swipe')) {
      swipe = parseInt(elementIndex.getAttribute('data-swipe'), 10);
      swipe = (swipe === 1)?true:false;
    }

    status = document.getElementById('status').innerHTML;
  }

  function initKF() {
    var listItems,
        listLinkFolders = [],
        listLinkItems = [];

    initOptions();

    listLinkFolders = getListLinkFolders();
    listLinkItems = getListLinkItems();
    if (!window.jQuery || (window.jQuery && !window.jQuery().collapse)) {
      document.getElementById('menu-toggle'). onclick = collapseClick;
      initCollapse(listLinkFolders);
      initCollapse(listLinkItems);
    }
    initLinkFolders(listLinkFolders);
    initLinkItems(listLinkItems);

    initListItemsHash();
    initListItems();

    initUnread();

    initItemButton();
    initPageButton();

    initAnonyme();

    addEvent(window, 'keydown', checkKey);
    addEvent(window, 'touchstart', checkMove);

    if (autoupdate && !stars) {
      initUpdate();
    }

    listItems = getListItems();
    listItems.focus();
  }

  //http://scottandrew.com/weblog/articles/cbs-events
  function addEvent(obj, evType, fn, useCapture) {
    if (obj.addEventListener) {
      obj.addEventListener(evType, fn, useCapture);
    } else {
      if (obj.attachEvent) {
        obj.attachEvent('on' + evType, fn);
      } else {
        window.alert('Handler could not be attached');
      }
    }
  }

  function removeEvent(obj, evType, fn, useCapture) {
    if (obj.removeEventListener) {
      obj.removeEventListener(evType, fn, useCapture);
    } else if (obj.detachEvent) {
      obj.detachEvent("on"+evType, fn);
    } else {
      alert("Handler could not be removed");
    }
  }

  // when document is loaded init KrISS feed
  if (document.getElementById && document.createTextNode) {
    addEvent(window, 'load', initKF);
  }

  window.checkKey = checkKey;
  window.removeEvent = removeEvent;
  window.addEvent = addEvent;
})();

// unread count for favicon part
if(typeof GM_getValue == 'undefined') {
	GM_getValue = function(name, fallback) {
		return fallback;
	};
}

// Register GM Commands and Methods
if(typeof GM_registerMenuCommand !== 'undefined') {
  var setOriginalFavicon = function(val) { GM_setValue('originalFavicon', val); };
	GM_registerMenuCommand( 'GReader Favicon Alerts > Use Current Favicon', function() { setOriginalFavicon(false); } );
	GM_registerMenuCommand( 'GReader Favicon Alerts > Use Original Favicon', function() { setOriginalFavicon(true); } );
}

(function FaviconAlerts() {
	var self = this;

	this.construct = function() {
		this.head = document.getElementsByTagName('head')[0];
		this.pixelMaps = {numbers: {0:[[1,1,1],[1,0,1],[1,0,1],[1,0,1],[1,1,1]],1:[[0,1,0],[1,1,0],[0,1,0],[0,1,0],[1,1,1]],2:[[1,1,1],[0,0,1],[1,1,1],[1,0,0],[1,1,1]],3:[[1,1,1],[0,0,1],[0,1,1],[0,0,1],[1,1,1]],4:[[0,0,1],[0,1,1],[1,0,1],[1,1,1],[0,0,1]],5:[[1,1,1],[1,0,0],[1,1,1],[0,0,1],[1,1,1]],6:[[0,1,1],[1,0,0],[1,1,1],[1,0,1],[1,1,1]],7:[[1,1,1],[0,0,1],[0,0,1],[0,1,0],[0,1,0]],8:[[1,1,1],[1,0,1],[1,1,1],[1,0,1],[1,1,1]],9:[[1,1,1],[1,0,1],[1,1,1],[0,0,1],[1,1,0]],'+':[[0,0,0],[0,1,0],[1,1,1],[0,1,0],[0,0,0]],'k':[[1,0,1],[1,1,0],[1,1,0],[1,0,1],[1,0,1]]}};

		this.timer = setInterval(this.poll, 500);
		this.poll();

		return true;
	};

	this.drawUnreadCount = function(unread, callback) {
		if(!self.textedCanvas) {
			self.textedCanvas = [];
		}

		if(!self.textedCanvas[unread]) {
			self.getUnreadCanvas(function(iconCanvas) {
				var textedCanvas = document.createElement('canvas');
				textedCanvas.height = textedCanvas.width = iconCanvas.width;
				var ctx = textedCanvas.getContext('2d');
				ctx.drawImage(iconCanvas, 0, 0);

				ctx.fillStyle = '#b7bfc9';
				ctx.strokeStyle = '#7792ba';
				ctx.strokeWidth = 1;

				var count = unread.length;

				if(count > 4) {
					unread = '1k+';
					count = unread.length;
				}

				var bgHeight = self.pixelMaps.numbers[0].length;
				var bgWidth = 0;
				var padding = count < 4 ? 1 : 0;
				var topMargin = 0;

				for(var index = 0; index < count; index++) {
					bgWidth += self.pixelMaps.numbers[unread[index]][0].length;
					if(index < count-1) {
						bgWidth += padding;
					}
				}
				bgWidth = bgWidth > textedCanvas.width-4 ? textedCanvas.width-4 : bgWidth;

				ctx.fillRect(textedCanvas.width-bgWidth-4,topMargin,bgWidth+4,bgHeight+4);

				var digit;
				var digitsWidth = bgWidth;
				for(index = 0; index < count; index++) {
					digit = unread[index];

					if (self.pixelMaps.numbers[digit]) {
						var map = self.pixelMaps.numbers[digit];
						var height = map.length;
						var width = map[0].length;

						ctx.fillStyle = '#2c3323';

						for (var y = 0; y < height; y++) {
							for (var x = 0; x < width; x++) {
								if(map[y][x]) {
									ctx.fillRect(14- digitsWidth + x, y+topMargin+2, 1, 1);
								}
							}
						}

						digitsWidth -= width + padding;
					}
				}

				ctx.strokeRect(textedCanvas.width-bgWidth-3.5,topMargin+0.5,bgWidth+3,bgHeight+3);

				self.textedCanvas[unread] = textedCanvas;

				callback(self.textedCanvas[unread]);
			});
      callback(self.textedCanvas[unread]);
		}
	};
	this.getIcon = function(callback) {
		self.getUnreadCanvas(function(canvas) {
			callback(canvas.toDataURL('image/png'));
		});
	};
  this.getIconSrc = function() {
    var links = document.getElementsByTagName('link');
    for (var i = 0; i < links.length; i++) {
      if (links[i].rel === 'icon') {
        return links[i].href;
      }
    }
    return false;
  };
	this.getUnreadCanvas = function(callback) {
		if(!self.unreadCanvas) {
			self.unreadCanvas = document.createElement('canvas');
			self.unreadCanvas.height = self.unreadCanvas.width = 16;

			var ctx = self.unreadCanvas.getContext('2d');
			var img = new Image();

			img.addEventListener('load', function() {
				ctx.drawImage(img, 0, 0);
				callback(self.unreadCanvas);
			}, true);

		//	if(GM_getValue('originalFavicon', false)) {
		//		img.src = self.icons.original;
		//	} else {
		//		img.src = self.icons.current;
		//	}
		// img.src = 'inc/favicon.ico';
                  img.src = self.getIconSrc();
		} else {
			callback(self.unreadCanvas);
		}
	};
	this.getUnreadCount = function() {
		matches = self.getSearchText().match(/\((.*)\)/);
		return matches ? matches[1] : false;
	};
	this.getUnreadCountIcon = function(callback) {
		var unread = self.getUnreadCount();
    self.drawUnreadCount(unread, function(icon) {
      if(icon) {
        callback(icon.toDataURL('image/png'));
      }
    });
	};
	this.getSearchText = function() {
          var elt = document.getElementById('nb-unread');
          
          if (!elt) {
	    elt = document.getElementById('nb-starred');
          }
          if (elt) {
	    return 'Kriss feed (' + parseInt(elt.innerHTML, 10) + ')';
          }
          return '';
	};
	this.poll = function() {
		if(self.getUnreadCount() != "0") {
			self.getUnreadCountIcon(function(icon) {
				self.setIcon(icon);
			});
		} else {
			self.getIcon(function(icon) {
				self.setIcon(icon);
			});
		}
	};

	this.setIcon = function(icon) {
		var links = self.head.getElementsByTagName('link');
		for (var i = 0; i < links.length; i++)
			if ((links[i].rel == 'shortcut icon' || links[i].rel=='icon') &&
        links[i].href != icon)
				self.head.removeChild(links[i]);
			else if(links[i].href == icon)
				return;

		var newIcon = document.createElement('link');
		newIcon.type = 'image/png';
		newIcon.rel = 'shortcut icon';
		newIcon.href = icon;
		self.head.appendChild(newIcon);

		// Chrome hack for updating the favicon
		//var shim = document.createElement('iframe');
		//shim.width = shim.height = 0;
		//document.body.appendChild(shim);
		//shim.src = 'icon';
		//document.body.removeChild(shim);
	};

	this.toString = function() { return '[object FaviconAlerts]'; };

	return this.construct();
}());
