<?php

// Optional CLI entry point. The panel applies the same idempotent migration automatically.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../app/models/PublicPage.php';
$path = getenv('SQLITE_PATH') ?: dirname(__DIR__) . '/eventplanner.sqlite';
if (!is_file($path)) { fwrite(STDERR, "Base de dados existente não encontrada. Define SQLITE_PATH.\n"); exit(1); }
$db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
new PublicPage($db);
fwrite(STDOUT, "Migração de navegação e SEO aplicada sem alterar conteúdos existentes.\n");
