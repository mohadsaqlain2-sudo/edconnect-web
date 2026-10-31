<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$classSpaces = 0;
foreach ($files as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;
    $content = file_get_contents($file->getPathname());
    
    // Check multiple spaces in class attributes
    if (preg_match_all('/class="[^"]*  +[^"]*"/', $content, $matches)) {
        foreach ($matches[0] as $m) {
            echo "Class double space in " . $file->getPathname() . ": " . $m . "\n";
            $classSpaces++;
        }
    }
}
echo "Total class attribute double spaces: $classSpaces\n";

