<?php

$file = 'data/data.php';
$type = 'raw';
$save = false;
if (php_sapi_name() === 'cli') {
    if ($argc < 3) {
        die('php readData.php data.php [zip|raw|data]'."\n");
    }
    $file = $argv[1];
    $type = $argv[2];
    if ($argc > 3) {
        $save = (int)$argv[3];
    }
} else {
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
    }
    if (isset($_GET['save'])) {
        $save = true;
    }
}

define('PHPPREFIX', '<?php /* '); // Prefix to encapsulate data in php code.
define('PHPSUFFIX', ' */ ?>'); // Suffix to encapsulate data in php code.

$data = base64_decode(
    substr(
        file_get_contents($file),
        strlen(PHPPREFIX),
        -strlen(PHPSUFFIX)
    )
);

if ($type !== 'zip') {
    $data = gzinflate($data);
    if ($type === 'data') {
        $data = unserialize($data);
    }
}

if ($save) {
    echo 'writing data...';
    file_put_contents($file.'.bak', $data);
}

var_dump($data);
