<?php
if (!class_exists('Intl_de')) {
    class Intl_de {
        public static function init(&$messages) {
            $messages['de'] = unserialize(gzinflate(base64_decode("
")));
        }
    }
    Intl::addLang('de', 'Deutsch', 'flag-de');
}

