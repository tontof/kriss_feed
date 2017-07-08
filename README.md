kriss_feed (version 8)
======================

A simple and smart (or stupid) feed reader.

An example is available on [tontof.net](http://tontof.net/feed).

This is an alternative to Google Reader or other RSS feed readers:
- It does not require SQL or database.
- It works with PHP 5.2

More information here: [KrISS feed](http://tontof.net/kriss/feed).

Installation
============
* If you just want to use kriss feed, download [index.php](https://raw.github.com/tontof/kriss_feed/master/index.php) file and upload it
on your server. Enjoy !

* If you want to look at the source, look at the src directory.
To generate index.php file, just run the command :
bash generateIndex > index.php

More information here: [KrISS feed](http://tontof.net/kriss/feed).

Features
========
* version 1
  * add/remove feed
  * import/export opml file
  * update manually feed/folder/all
  * mark as read feed/folder/all

* version 2
  * show and reader view
  * anonymize link (not image or media)
  * simple share with shaarli
  * auto update in show view

* version 3
  * new format : item hash -> feedHash + itemHash
  * list/expanded view
  * autoupdate in reader view
  * auto cache 10 last downloaded articles
  * automatic load when scroll

* version 4
  * edit feeds
  * add via url (shaarli, blogotext links)
  * automatic save mode/view

* version 5
  * new data structure
  * bootstrap css
  * fully usable without javascript

* version 6
  * security is increased
  * more functionnalities (thanks to your feedback)

* version 7
  * starred items
  * order list of feeds
  * list of feeds are updated with javascript

* version 8
  * internationalization
  * plugins

Licence
=======
Copyleft (ɔ) - Tontof - http://tontof.net

Use KrISS feed at your own risk.

[Free software means users have the four essential freedoms](http://www.gnu.org/philosophy/philosophy.html):
* to run the program
* to study and change the program in source code form
* to redistribute exact copies, and
* to distribute modified versions.
