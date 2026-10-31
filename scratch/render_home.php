<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/', 'GET')
);
$content = $response->getContent();
file_put_contents('scratch/home.html', $content);
echo "Length of home.html: " . strlen($content) . "\n";
