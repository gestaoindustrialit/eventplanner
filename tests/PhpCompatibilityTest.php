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
    foreach ($tokens as $index => $token) {
        if (is_array($token) && token_name($token[0]) === 'T_FN') {
            fwrite(STDERR, 'FAIL: PHP 7.4 arrow function found in ' . substr($file, strlen($root) + 1) . ':' . $token[2] . "\n");
            exit(1);
        }
        if (!is_array($token) || !in_array(token_name($token[0]), ['T_PUBLIC', 'T_PROTECTED', 'T_PRIVATE'], true)) {
            continue;
        }
        for ($lookahead = $index + 1; isset($tokens[$lookahead]); $lookahead++) {
            $next = $tokens[$lookahead];
            if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_STATIC], true)) {
                continue;
            }
            if (is_array($next) && in_array(token_name($next[0]), ['T_ARRAY', 'T_STRING', 'T_CALLABLE'], true)) {
                fwrite(STDERR, 'FAIL: PHP 7.4 typed property found in ' . substr($file, strlen($root) + 1) . ':' . $next[2] . "\n");
                exit(1);
            }
            break;
        }
    }
}

fwrite(STDOUT, "PHP hosting compatibility test passed.\n");
