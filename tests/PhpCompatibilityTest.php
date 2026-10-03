<?php

$root = dirname(__DIR__);
$directories = ['app', 'includes', 'public', 'eventos'];
$files = [];

foreach ($directories as $directory) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

foreach (['index.php', 'install.php'] as $entryPoint) {
    $files[] = $root . '/' . $entryPoint;
}

foreach ($files as $file) {
    $tokens = token_get_all((string)file_get_contents($file));
    foreach ($tokens as $token) {
        if (is_array($token) && token_name($token[0]) === 'T_FN') {
            fwrite(STDERR, 'FAIL: PHP 7.4 arrow function found in ' . substr($file, strlen($root) + 1) . ':' . $token[2] . "\n");
            exit(1);
        }
    }
}

fwrite(STDOUT, "PHP hosting compatibility test passed.\n");
