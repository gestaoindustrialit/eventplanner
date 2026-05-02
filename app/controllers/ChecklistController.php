<?php

class ChecklistController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $templates = [];

        try {
            $templates = (new Checklist($this->db))->allTemplates();
        } catch (Throwable $e) {
            error_log('ChecklistController::index failed (first try): ' . $e->getMessage());

            try {
                $this->ensureChecklistSchema();
                $templates = (new Checklist($this->db))->allTemplates();
            } catch (Throwable $retryException) {
                error_log('ChecklistController::index failed (retry): ' . $retryException->getMessage());
                flash('error', 'Não foi possível carregar os templates de checklist. Verifica se a base de dados está atualizada.');
            }
        }


        $template = null;
        $fields = [['label' => '', 'field_type' => 'checkbox', 'is_required' => 0]];

        $this->render('checklists/index', compact('templates', 'template', 'fields'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('checklists/form', [
            'template' => null,
            'fields' => [['label' => '', 'field_type' => 'checkbox', 'is_required' => 0]],
        ]);
    }

    public function store(): void
    {
        requireAdmin();
        (new Checklist($this->db))->createTemplate($this->validatedTemplateData(), $this->templateFieldsData());
        flash('success', 'Template de checklist criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=checklist&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $model = new Checklist($this->db);
        $template = $model->findTemplate($id);

        if (!$template) {
            flash('error', 'Template não encontrado.');
            $this->redirect(BASE_URL . '?controller=checklist&action=index');
        }

        $fields = $model->templateFields($id);
        if (!$fields) {
            $fields = [['label' => '', 'field_type' => 'checkbox', 'is_required' => 0]];
        }

        $this->render('checklists/form', compact('template', 'fields'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Checklist($this->db))->updateTemplate($id, $this->validatedTemplateData(), $this->templateFieldsData());
        flash('success', 'Template atualizado com sucesso.');
        $this->redirect(BASE_URL . '?controller=checklist&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Checklist($this->db))->deleteTemplate($id);
        flash('success', 'Template eliminado.');
        $this->redirect(BASE_URL . '?controller=checklist&action=index');
    }

    public function event(): void
    {
        requireLogin();
        $eventId = (int)($_GET['event_id'] ?? 0);
        $event = (new Event($this->db))->find($eventId);

        if (!$event) {
            flash('error', 'Evento não encontrado.');
            $this->redirect(BASE_URL . '?controller=event&action=index');
        }

        $model = new Checklist($this->db);
        $templates = $model->allTemplates();
        $checklist = $model->getEventChecklist($eventId);

        $this->render('events/checklist', compact('event', 'templates', 'checklist'));
    }

    public function assignTemplate(): void
    {
        requireLogin();
        $eventId = (int)($_GET['event_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);

        if ($eventId <= 0 || $templateId <= 0) {
            flash('error', 'Seleciona um evento e um template válido.');
            $this->redirect(BASE_URL . '?controller=event&action=index');
        }

        (new Checklist($this->db))->assignTemplateToEvent($eventId, $templateId);
        flash('success', 'Template aplicado ao evento. Já podes preencher e adicionar novos campos.');
        $this->redirect(BASE_URL . '?controller=checklist&action=event&event_id=' . $eventId);
    }

    public function saveEventChecklist(): void
    {
        requireLogin();
        $eventId = (int)($_GET['event_id'] ?? 0);
        $templateId = isset($_POST['template_id']) && $_POST['template_id'] !== '' ? (int)$_POST['template_id'] : null;
        $checklistName = trim((string)($_POST['checklist_name'] ?? 'Checklist do evento'));

        (new Checklist($this->db))->saveEventChecklist($eventId, $templateId, $checklistName, $this->eventChecklistItemsData());

        flash('success', 'Checklist do evento guardada com sucesso.');
        $this->redirect(BASE_URL . '?controller=checklist&action=event&event_id=' . $eventId);
    }

    private function validatedTemplateData(): array
    {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'O nome do template é obrigatório.');
            $this->redirect(BASE_URL . '?controller=checklist&action=index');
        }

        return [
            'name' => $name,
            'description' => trim((string)($_POST['description'] ?? '')),
        ];
    }

    private function templateFieldsData(): array
    {
        $labels = $_POST['field_label'] ?? [];
        $types = $_POST['field_type'] ?? [];
        $requiredRows = $_POST['field_required'] ?? [];

        $fields = [];
        foreach ($labels as $i => $label) {
            $fields[] = [
                'label' => trim((string)$label),
                'field_type' => ($types[$i] ?? 'checkbox') === 'text' ? 'text' : 'checkbox',
                'is_required' => in_array((string)$i, $requiredRows, true) ? 1 : 0,
            ];
        }

        return $fields;
    }

    private function eventChecklistItemsData(): array
    {
        $labels = $_POST['item_label'] ?? [];
        $types = $_POST['item_type'] ?? [];
        $requiredRows = $_POST['item_required'] ?? [];
        $checkedRows = $_POST['item_checked'] ?? [];
        $values = $_POST['item_value'] ?? [];

        $items = [];
        foreach ($labels as $i => $label) {
            $items[] = [
                'label' => trim((string)$label),
                'field_type' => ($types[$i] ?? 'checkbox') === 'text' ? 'text' : 'checkbox',
                'is_required' => in_array((string)$i, $requiredRows, true) ? 1 : 0,
                'is_checked' => in_array((string)$i, $checkedRows, true) ? 1 : 0,
                'value' => trim((string)($values[$i] ?? '')),
            ];
        }

        return $items;
    }

    private function ensureChecklistSchema(): void
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS checklist_templates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS checklist_template_fields (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                template_id INTEGER NOT NULL,
                label TEXT NOT NULL,
                field_type TEXT NOT NULL DEFAULT \'checkbox\' CHECK (field_type IN (\'checkbox\', \'text\')),
                is_required INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE CASCADE
            )'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS event_checklists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL UNIQUE,
                template_id INTEGER DEFAULT NULL,
                name TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
                FOREIGN KEY (template_id) REFERENCES checklist_templates(id) ON DELETE SET NULL
            )'
        );

        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS event_checklist_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_checklist_id INTEGER NOT NULL,
                label TEXT NOT NULL,
                field_type TEXT NOT NULL DEFAULT \'checkbox\' CHECK (field_type IN (\'checkbox\', \'text\')),
                is_required INTEGER NOT NULL DEFAULT 0,
                value TEXT DEFAULT NULL,
                is_checked INTEGER NOT NULL DEFAULT 0,
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_checklist_id) REFERENCES event_checklists(id) ON DELETE CASCADE
            )'
        );
    }
}
