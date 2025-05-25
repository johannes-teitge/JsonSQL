<?php
function get_settings() {
    $default = include __DIR__ . '/../config.php';
    $file = __DIR__ . '/../settings.json';

    if (file_exists($file)) {
        $json = json_decode(file_get_contents($file), true);
        return array_merge($default, $json);
    }

    return $default;
}

function save_settings($settings) {
    $file = __DIR__ . '/../settings.json';
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
}
