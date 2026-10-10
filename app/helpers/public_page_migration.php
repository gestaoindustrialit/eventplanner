<?php

function public_page_migrate(PDO $db): void
{
    $definitions = [
        'menu_location'=>"TEXT NOT NULL DEFAULT 'main'",
        'menu_parent'=>"TEXT NOT NULL DEFAULT ''", 'menu_label'=>"TEXT NOT NULL DEFAULT ''",
        'menu_order'=>'INTEGER DEFAULT NULL', 'meta_title'=>"TEXT NOT NULL DEFAULT ''",
        'meta_description'=>"TEXT NOT NULL DEFAULT ''", 'canonical_url'=>"TEXT NOT NULL DEFAULT ''",
        'og_image_url'=>"TEXT NOT NULL DEFAULT ''", 'allow_indexing'=>'INTEGER NOT NULL DEFAULT 1',
        'schema_type'=>"TEXT NOT NULL DEFAULT 'WebPage'", 'service_area'=>"TEXT NOT NULL DEFAULT ''",
        'related_json'=>"TEXT NOT NULL DEFAULT '[]'", 'updated_at'=>'TEXT DEFAULT NULL',
    ];
    $columns = array_column($db->query('PRAGMA table_info(public_pages)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    foreach ($definitions as $name=>$definition) {
        if (!in_array($name, $columns, true)) { $db->exec('ALTER TABLE public_pages ADD COLUMN ' . $name . ' ' . $definition); }
    }
    $db->exec("CREATE TABLE IF NOT EXISTS public_page_redirects (
        old_slug TEXT PRIMARY KEY, page_id INTEGER NOT NULL REFERENCES public_pages(id) ON DELETE CASCADE,
        old_mode TEXT NOT NULL DEFAULT 'page')");
}
