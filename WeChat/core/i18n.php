<?php
// core/i18n.php

function load_language() {
    $lang = $_SESSION['lang'] ?? 'zh';
    $file = __DIR__ . "/../lang/{$lang}.php";
    if (file_exists($file)) {
        return require $file;
    }
    return require __DIR__ . "/../lang/zh.php";
}

function trans($key) {
    global $LOCALE;
    return $LOCALE[$key] ?? $key;
}
?>
