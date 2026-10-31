<?php
$file = file_get_contents('resources/sass/app.scss');
$lines = explode("\n", $file);
foreach ($lines as $i => $line) {
    if (preg_match('/edu-body|edu-skip|skip-link|body\s*{|\.body/i', $line)) {
        echo ($i+1) . ': ' . $line . "\n";
    }
}

