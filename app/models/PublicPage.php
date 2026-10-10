<?php

require_once __DIR__ . "/../helpers/public_pages.php";
require_once __DIR__ . "/../helpers/public_page_migration.php";

class PublicPage
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->ensureTable();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM public_pages ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll();
    }

    public function allPublished(): array
    {
        $stmt = $this->db->query('SELECT * FROM public_pages WHERE is_published = 1 ORDER BY sort_order ASC, title ASC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM public_pages WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $page = $stmt->fetch();
        return $page ?: null;
    }

    private function persist(?int $id, array $data): void
    {
        $defaults = ['menu_location'=>'main', 'menu_parent'=>'', 'menu_label'=>'', 'menu_order'=>null,
            'meta_title'=>'', 'meta_description'=>'', 'canonical_url'=>'', 'og_image_url'=>'',
            'allow_indexing'=>1, 'schema_type'=>'WebPage', 'service_area'=>'', 'related_json'=>'[]'];
        $fields = ['title','slug','excerpt','content','hero_image_url','display_mode','section_type',
            'section_style','section_config_json','is_published','sort_order'];
        $fields = array_merge($fields, array_keys($defaults));
        $values = [];
        foreach ($fields as $field) { $values[$field] = $data[$field] ?? ($defaults[$field] ?? null); }
        $values['updated_at'] = gmdate('Y-m-d H:i:s');
        $fields[] = 'updated_at';
        if (!$this->slugAvailable((string)$data['slug'], $id ?? 0)) {
            throw new InvalidArgumentException('O slug já existe ou está reservado.');
        }
        $this->db->beginTransaction();
        try {
            if ($id === null) {
                $sql = 'INSERT INTO public_pages (' . implode(',', $fields) . ') VALUES (:' . implode(',:', $fields) . ')';
            } else {
                $old = $this->find($id);
                if (!$old) { throw new InvalidArgumentException('Página não encontrada.'); }
                if ($old['slug'] !== $data['slug']) {
                    $redirect = $this->db->prepare('INSERT OR REPLACE INTO public_page_redirects (old_slug,page_id,old_mode) VALUES (:slug,:id,:mode)');
                    $redirect->execute(['slug'=>$old['slug'], 'id'=>$id, 'mode'=>$old['display_mode']]);
                }
                $assignments = [];
                foreach ($fields as $field) { $assignments[] = $field . ' = :' . $field; }
                $sql = 'UPDATE public_pages SET ' . implode(',', $assignments) . ' WHERE id = :id';
                $values['id'] = $id;
            }
            $this->db->prepare($sql)->execute($values);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function create(array $data): void { $this->persist(null, $data); }
    public function update(int $id, array $data): void { $this->persist($id, $data); }

    public function slugAvailable(string $slug, int $id = 0): bool
    {
        $current = $id ? $this->find($id) : null;
        if (in_array($slug, public_page_reserved_slugs(), true) && (!$current || $current['slug'] !== $slug)) { return false; }
        $stmt = $this->db->prepare('SELECT id FROM public_pages WHERE slug = :slug AND id != :id UNION ALL SELECT page_id FROM public_page_redirects WHERE old_slug = :slug');
        $stmt->execute(['slug'=>$slug, 'id'=>$id]);
        return !$stmt->fetch();
    }

    public function delete(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM public_page_redirects WHERE page_id = :id')->execute(['id'=>$id]);
            $this->db->prepare('DELETE FROM public_pages WHERE id = :id')->execute(['id'=>$id]);
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    private function ensureTable(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS public_pages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                excerpt TEXT DEFAULT NULL,
                content TEXT DEFAULT NULL,
                hero_image_url TEXT DEFAULT NULL,
                display_mode TEXT NOT NULL DEFAULT "section" CHECK (display_mode IN ("section", "page")),
                section_type TEXT NOT NULL DEFAULT "default" CHECK (section_type IN ("default", "about", "services", "contact_form")),
                section_style TEXT NOT NULL DEFAULT "card" CHECK (section_style IN ("card", "split", "icons", "highlight")),
                section_config_json TEXT DEFAULT NULL,
                is_published INTEGER NOT NULL DEFAULT 1,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        public_page_migrate($this->db);

        $columns = array_column($this->db->query('PRAGMA table_info(public_pages)')->fetchAll(), 'name');
        if (!in_array('display_mode', $columns, true)) {
            $this->db->exec('ALTER TABLE public_pages ADD COLUMN display_mode TEXT NOT NULL DEFAULT "section" CHECK (display_mode IN ("section", "page"))');
        }
        if (!in_array('section_type', $columns, true)) {
            $this->db->exec('ALTER TABLE public_pages ADD COLUMN section_type TEXT NOT NULL DEFAULT "default"');
        }
        if (!in_array('section_style', $columns, true)) {
            $this->db->exec('ALTER TABLE public_pages ADD COLUMN section_style TEXT NOT NULL DEFAULT "card"');
        }
        if (!in_array('section_config_json', $columns, true)) {
            $this->db->exec('ALTER TABLE public_pages ADD COLUMN section_config_json TEXT DEFAULT NULL');
        }
    }
}
