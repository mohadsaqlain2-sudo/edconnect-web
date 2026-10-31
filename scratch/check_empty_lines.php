<?php
$dirs = ['resources/views', 'resources/sass', 'resources/js'];
$multiBlank = 0;
$trailingSpaces = 0;

foreach ($dirs as $dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if (!$file->isFile()) continue;
        $content = file_get_contents($file->getPathname());
        
        if (preg_match('/[ \t]+$/m', $content)) {
            $trailingSpaces++;
        }
        if (preg_match('/(\r?\n){3,}/', $content)) {
            $multiBlank++;
        }
    }
}
echo "Files with trailing spaces: $trailingSpaces\n";
echo "Files with 3+ consecutive newlines: $multiBlank\n";

