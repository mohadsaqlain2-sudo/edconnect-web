<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('resources/views'));
$found = [];
foreach ($files as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;
    $content = file_get_contents($file->getPathname());
    
    // Look for double spaces within text
    if (preg_match_all('/([a-zA-Z0-9.,;:!?)]\s{2,}[a-zA-Z0-9(])/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $m) {
            // Check if it's multiple spaces on the SAME line (not newlines)
            if (strpos($m[0], "\n") === false && strpos($m[0], "\r") === false) {
                $found[$file->getPathname()][] = $m[0];
            }
        }
    }
}

foreach ($found as $path => $items) {
    echo $path . ": " . count($items) . " occurrences\n";
    foreach (array_slice($items, 0, 5) as $it) {
        echo "   -> " . json_encode($it) . "\n";
    }
}
echo "Total files with double spaces on same line: " . count($found) . "\n";
