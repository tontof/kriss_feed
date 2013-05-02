<?php
require('php-mo.php');
phpmo_convert( 'messages.po');
phpmo_parse_po_file($in);
?>