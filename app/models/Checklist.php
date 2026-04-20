<?php

class Checklist
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function allTemplates(): array
    {
        $stmt = $this->db->query('SELECT * FROM checklist_templates ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function findTemplate(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM checklist_templates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function templateFields(int $templateId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM checklist_template_fields WHERE template_id = :template_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['template_id' => $templateId]);
        return $stmt->fetchAll();
    }

    public function createTemplate(array $data, array $fields): int
    {
        $stmt = $this->db->prepare('INSERT INTO checklist_templates (name, description) VALUES (:name, :description)');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);

        $templateId = (int)$this->db->lastInsertId();
        $this->saveTemplateFields($templateId, $fields);

        return $templateId;
    }

    public function updateTemplate(int $id, array $data, array $fields): void
    {
        $stmt = $this->db->prepare('UPDATE checklist_templates SET name = :name, description = :description WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);

        $delete = $this->db->prepare('DELETE FROM checklist_template_fields WHERE template_id = :template_id');
        $delete->execute(['template_id' => $id]);

        $this->saveTemplateFields($id, $fields);
    }

    public function deleteTemplate(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM checklist_templates WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function getEventChecklist(int $eventId): ?array
    {
        $stmt = $this->db->prepare('SELECT ec.*, ct.name AS template_name FROM event_checklists ec LEFT JOIN checklist_templates ct ON ct.id = ec.template_id WHERE ec.event_id = :event_id LIMIT 1');
        $stmt->execute(['event_id' => $eventId]);
        $checklist = $stmt->fetch();

        if (!$checklist) {
            return null;
        }

        $itemsStmt = $this->db->prepare('SELECT * FROM event_checklist_items WHERE event_checklist_id = :event_checklist_id ORDER BY sort_order ASC, id ASC');
        $itemsStmt->execute(['event_checklist_id' => $checklist['id']]);
        $checklist['items'] = $itemsStmt->fetchAll();

        return $checklist;
    }

    public function assignTemplateToEvent(int $eventId, int $templateId): void
    {
        $template = $this->findTemplate($templateId);
        if (!$template) {
            return;
        }

        $fields = $this->templateFields($templateId);
        $items = [];

        foreach ($fields as $field) {
            $items[] = [
                'label' => $field['label'],
                'field_type' => $field['field_type'],
                'is_required' => (int)$field['is_required'],
                'value' => '',
                'is_checked' => 0,
            ];
        }

        $this->saveEventChecklist($eventId, $templateId, $template['name'], $items);
    }

    public function saveEventChecklist(int $eventId, ?int $templateId, string $name, array $items): void
    {
        $existing = $this->getEventChecklist($eventId);

        if ($existing) {
            $eventChecklistId = (int)$existing['id'];
            $update = $this->db->prepare('UPDATE event_checklists SET template_id = :template_id, name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $update->execute([
                'id' => $eventChecklistId,
                'template_id' => $templateId,
                'name' => $name,
            ]);
        } else {
            $insert = $this->db->prepare('INSERT INTO event_checklists (event_id, template_id, name) VALUES (:event_id, :template_id, :name)');
            $insert->execute([
                'event_id' => $eventId,
                'template_id' => $templateId,
                'name' => $name,
            ]);
            $eventChecklistId = (int)$this->db->lastInsertId();
        }

        $deleteItems = $this->db->prepare('DELETE FROM event_checklist_items WHERE event_checklist_id = :event_checklist_id');
        $deleteItems->execute(['event_checklist_id' => $eventChecklistId]);

        $insertItem = $this->db->prepare('INSERT INTO event_checklist_items (event_checklist_id, label, field_type, is_required, value, is_checked, sort_order) VALUES (:event_checklist_id, :label, :field_type, :is_required, :value, :is_checked, :sort_order)');

        foreach ($items as $index => $item) {
            $label = trim((string)($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $fieldType = ($item['field_type'] ?? 'checkbox') === 'text' ? 'text' : 'checkbox';
            $insertItem->execute([
                'event_checklist_id' => $eventChecklistId,
                'label' => $label,
                'field_type' => $fieldType,
                'is_required' => !empty($item['is_required']) ? 1 : 0,
                'value' => trim((string)($item['value'] ?? '')),
                'is_checked' => !empty($item['is_checked']) ? 1 : 0,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function saveTemplateFields(int $templateId, array $fields): void
    {
        $stmt = $this->db->prepare('INSERT INTO checklist_template_fields (template_id, label, field_type, is_required, sort_order) VALUES (:template_id, :label, :field_type, :is_required, :sort_order)');

        foreach ($fields as $index => $field) {
            $label = trim((string)($field['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $fieldType = ($field['field_type'] ?? 'checkbox') === 'text' ? 'text' : 'checkbox';

            $stmt->execute([
                'template_id' => $templateId,
                'label' => $label,
                'field_type' => $fieldType,
                'is_required' => !empty($field['is_required']) ? 1 : 0,
                'sort_order' => $index + 1,
            ]);
        }
    }

}
