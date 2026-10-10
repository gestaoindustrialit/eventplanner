<?php

class PublicPageController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $pages = (new PublicPage($this->db))->all();
        $this->render('public_pages/index', compact('pages'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('public_pages/form', ['page' => null] + $this->formOptions());
    }

    public function store(): void
    {
        requireAdmin();
        $this->save(null);
        flash('success', 'Página criada com sucesso.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $page = (new PublicPage($this->db))->find($id);

        if (!$page) {
            flash('error', 'Página não encontrada.');
            $this->redirect(BASE_URL . '?controller=publicpage&action=index');
        }

        $this->render('public_pages/form', compact('page') + $this->formOptions((int)$page['id']));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $this->save($id);
        flash('success', 'Página atualizada.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new PublicPage($this->db))->delete($id);
        flash('success', 'Página eliminada.');
        $this->redirect(BASE_URL . '?controller=publicpage&action=index');
    }

    private function formOptions(int $id = 0): array
    {
        $pages = (new PublicPage($this->db))->allPublished();
        $settings = new SiteSetting($this->db);
        $agenda = (bool)$this->db->query("SELECT 1 FROM events WHERE is_visible = 1 AND date >= date('now') LIMIT 1")->fetchColumn();
        $partners = (bool)$this->db->query("SELECT 1 FROM partners WHERE date(partnership_start_date) <= date('now') AND date(partnership_start_date, '+1 year') > date('now') LIMIT 1")->fetchColumn();
        $menuParents = public_page_menu($pages, $agenda, $partners, $settings->get('corporate_events_enabled', '1') === '1');
        $menuParents = array_values(array_filter($menuParents, static function (array $item) use ($id): bool {
            return $item['key'] !== 'page:' . $id;
        }));
        $relatedOptions = [];
        foreach ($pages as $record) {
            if ((int)$record['id'] !== $id) { $relatedOptions['page:' . $record['id']] = 'Página: ' . $record['title']; }
        }
        foreach ($this->db->query('SELECT id,title FROM blog_posts WHERE is_published = 1')->fetchAll() as $record) {
            $relatedOptions['blog:' . $record['id']] = 'Blog: ' . $record['title'];
        }
        foreach ($this->db->query("SELECT id,title FROM events WHERE is_visible = 1 AND slug IS NOT NULL AND date >= date('now')")->fetchAll() as $record) {
            $relatedOptions['event:' . $record['id']] = 'Evento: ' . $record['title'];
        }
        if (empty($_SESSION['public_page_csrf'])) { $_SESSION['public_page_csrf'] = bin2hex(random_bytes(32)); }
        return compact('menuParents', 'relatedOptions');
    }

    public function checkSlug(): void
    {
        requireAdmin();
        header('Content-Type: application/json; charset=UTF-8');
        if (isset($_GET['slug']) && !is_string($_GET['slug'])) { http_response_code(400); echo json_encode(['error'=>'Slug inválido.']); return; }
        $slug = $this->slugify((string)($_GET['slug'] ?? ''));
        echo json_encode(['slug'=>$slug, 'available'=>$slug !== '' && (new PublicPage($this->db))->slugAvailable($slug, (int)($_GET['id'] ?? 0))]);
    }

    private function save(?int $id): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
            || empty($_SESSION['public_page_csrf'])
            || !isset($_POST['csrf_token']) || !is_string($_POST['csrf_token'])
            || !hash_equals($_SESSION['public_page_csrf'], $_POST['csrf_token'])) {
            http_response_code(403); echo 'Pedido inválido.'; exit;
        }
        try {
            $data = $this->validatedData();
            $model = new PublicPage($this->db);
            if ($id === null) { $model->create($data); } else { $model->update($id, $data); }
        } catch (InvalidArgumentException $e) {
            http_response_code(422);
            $page = array_filter($_POST, static function ($value): bool { return is_scalar($value); });
            $page['id'] = $id;
            $page['is_published'] = isset($_POST['is_published']) ? 1 : 0;
            $page['allow_indexing'] = isset($_POST['allow_indexing']) ? 1 : 0;
            $submittedRelated = $_POST['related'] ?? [];
            $page['related_json'] = json_encode(is_array($submittedRelated) ? array_values(array_filter($submittedRelated, 'is_string')) : []);
            $submittedServices = [];
            $names = isset($_POST['service_name']) && is_array($_POST['service_name']) ? $_POST['service_name'] : [];
            $icons = isset($_POST['service_icon']) && is_array($_POST['service_icon']) ? $_POST['service_icon'] : [];
            $descriptions = isset($_POST['service_description']) && is_array($_POST['service_description']) ? $_POST['service_description'] : [];
            foreach ($names as $i=>$name) {
                $icon = $icons[$i] ?? '';
                $description = $descriptions[$i] ?? '';
                if (is_string($name) && is_string($icon) && is_string($description)) { $submittedServices[] = ['name'=>$name, 'icon'=>$icon, 'description'=>$description]; }
            }
            $page['section_config_json'] = isset($data) ? $data['section_config_json'] : json_encode([
                'cta_text'=>$page['cta_text'] ?? '', 'cta_button_text'=>$page['cta_button_text'] ?? '',
                'contact_email_to'=>$page['contact_email_to'] ?? '', 'services'=>$submittedServices,
                'contact_fields'=>isset($_POST['contact_fields']) && is_array($_POST['contact_fields']) ? array_filter($_POST['contact_fields'], 'is_string') : [],
            ]);
            $this->render('public_pages/form', ['page'=>$page, 'formError'=>$e->getMessage(), 'isNew'=>$id === null] + $this->formOptions($id ?? 0));
            exit;
        }
    }

    private function validatedData(): array
    {
        foreach (['title','slug','excerpt','content','hero_image_url','display_mode','section_type','section_style',
            'cta_text','cta_button_text','contact_email_to','sort_order','menu_location','menu_parent','menu_label',
            'menu_order','meta_title','meta_description','canonical_url','og_image_url','schema_type','service_area'] as $field) {
            if (isset($_POST[$field]) && !is_scalar($_POST[$field])) { throw new InvalidArgumentException('Campo inválido: ' . $field); }
        }
        foreach (['service_name','service_icon','service_description','contact_fields','related'] as $field) {
            if (!isset($_POST[$field])) { continue; }
            if (!is_array($_POST[$field])) { throw new InvalidArgumentException('Campo inválido: ' . $field); }
            foreach ($_POST[$field] as $value) { if (!is_string($value)) { throw new InvalidArgumentException('Campo inválido: ' . $field); } }
        }
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $normalizedSlug = $this->slugify($slug !== '' ? $slug : $title);

        if ($title === '' || $normalizedSlug === '') {
            throw new InvalidArgumentException('Título e slug são obrigatórios.');
        }

        $sectionType = (string)($_POST['section_type'] ?? 'default');
        if (!in_array($sectionType, ['default', 'about', 'services', 'contact_form'], true)) {
            $sectionType = 'default';
        }
        $sectionStyle = (string)($_POST['section_style'] ?? 'card');
        if (!in_array($sectionStyle, ['card', 'split', 'icons', 'highlight'], true)) {
            $sectionStyle = 'card';
        }

        $serviceNames = $_POST['service_name'] ?? [];
        $serviceIcons = $_POST['service_icon'] ?? [];
        $serviceDescriptions = $_POST['service_description'] ?? [];
        $services = [];
        if (is_array($serviceNames) && is_array($serviceIcons) && is_array($serviceDescriptions)) {
            $count = min(count($serviceNames), count($serviceIcons), count($serviceDescriptions));
            for ($i = 0; $i < $count; $i++) {
                $name = trim((string)$serviceNames[$i]);
                $icon = trim((string)$serviceIcons[$i]);
                $description = trim((string)$serviceDescriptions[$i]);
                if ($name === '' && $icon === '' && $description === '') {
                    continue;
                }
                $services[] = [
                    'name' => $name,
                    'icon' => $icon,
                    'description' => $description,
                ];
            }
        }

        $contactFields = $_POST['contact_fields'] ?? [];
        if (!is_array($contactFields)) {
            $contactFields = [];
        }
        $allowedContactFields = ['name', 'email', 'phone', 'subject', 'message'];
        $contactFields = array_values(array_intersect($allowedContactFields, $contactFields));
        if (!in_array('email', $contactFields, true)) {
            $contactFields[] = 'email';
        }
        if (!in_array('message', $contactFields, true)) {
            $contactFields[] = 'message';
        }

        $sectionConfig = [
            'cta_text' => trim((string)($_POST['cta_text'] ?? '')),
            'cta_button_text' => trim((string)($_POST['cta_button_text'] ?? 'Enviar mensagem')),
            'contact_email_to' => trim((string)($_POST['contact_email_to'] ?? '')),
            'contact_fields' => $contactFields,
            'services' => $services,
        ];

        $location = (string)($_POST['menu_location'] ?? 'none');
        if (!in_array($location, ['none','main','submenu'], true)) { throw new InvalidArgumentException('Localização no menu inválida.'); }
        $options = $this->formOptions((int)($_GET['id'] ?? 0));
        $parent = $location === 'submenu' ? (string)($_POST['menu_parent'] ?? '') : '';
        if ($location === 'submenu' && !in_array($parent, array_column($options['menuParents'], 'key'), true)) {
            throw new InvalidArgumentException('Seleciona um item principal existente como menu-pai.');
        }
        foreach (['canonical_url','og_image_url','hero_image_url'] as $field) {
            $url = trim((string)($_POST[$field] ?? ''));
            if ($url !== '' && (!public_page_http_url($url) || ($field === 'canonical_url' && parse_url($url, PHP_URL_FRAGMENT)))) {
                throw new InvalidArgumentException('URL inválido: ' . $field);
            }
        }
        if (isset($_POST['menu_order']) && $_POST['menu_order'] !== '' && filter_var($_POST['menu_order'], FILTER_VALIDATE_INT) === false) { throw new InvalidArgumentException('A ordem do menu deve ser um número inteiro.'); }
        $related = $_POST['related'] ?? [];
        if (!is_array($related)) { throw new InvalidArgumentException('Conteúdos relacionados inválidos.'); }
        $related = array_values(array_intersect(array_keys($options['relatedOptions']), $related));
        return [
            'menu_location'=>$location, 'menu_parent'=>$parent,
            'menu_label'=>trim((string)($_POST['menu_label'] ?? '')),
            'menu_order'=>($_POST['menu_order'] ?? '') === '' ? null : (int)$_POST['menu_order'],
            'meta_title'=>trim((string)($_POST['meta_title'] ?? '')),
            'meta_description'=>trim((string)($_POST['meta_description'] ?? '')),
            'canonical_url'=>trim((string)($_POST['canonical_url'] ?? '')),
            'og_image_url'=>trim((string)($_POST['og_image_url'] ?? '')),
            'allow_indexing'=>isset($_POST['allow_indexing']) ? 1 : 0,
            'schema_type'=>($_POST['schema_type'] ?? '') === 'Service' ? 'Service' : 'WebPage',
            'service_area'=>trim((string)($_POST['service_area'] ?? '')),
            'related_json'=>json_encode($related, JSON_UNESCAPED_UNICODE),
            'title' => $title,
            'slug' => $normalizedSlug,
            'excerpt' => trim((string)($_POST['excerpt'] ?? '')),
            'content' => trim((string)($_POST['content'] ?? '')),
            'hero_image_url' => trim((string)($_POST['hero_image_url'] ?? '')),
            'display_mode' => (($_POST['display_mode'] ?? 'section') === 'page') ? 'page' : 'section',
            'section_type' => $sectionType,
            'section_style' => $sectionStyle,
            'section_config_json' => json_encode($sectionConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
    }

    private function slugify(string $value): string
    {
        $value = trim(function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value));
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?: '';
        return trim($value, '-');
    }
}
