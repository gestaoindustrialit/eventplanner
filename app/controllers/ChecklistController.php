<?php

class ChecklistController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $templates = (new Checklist($this->db))->allTemplates();
        $this->render('checklists/index', compact('templates'));
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
}
