<?php

if ($argc < 3) {
    die('php readData.php data.php [zip|raw|data]'."\n");
}

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

$data = base64_decode(
    substr(
        file_get_contents($argv[1]),
        strlen(PHPPREFIX),
        -strlen(PHPSUFFIX)
    )
);

if ($argv[2] !== 'zip') {
    $data = gzinflate($data);
    if ($argv[2] === 'data') {
        $data = unserialize($data);
    }
}

var_dump($data);

