<?php
$file = file_get_contents('resources/sass/app.scss');
$lines = explode("\n", $file);
foreach ($lines as $i => $line) {
    if (preg_match('/dropdown-menu|\.dropdown\b/i', $line)) {
        echo ($i+1) . ': ' . $line . "\n";
    }
}

