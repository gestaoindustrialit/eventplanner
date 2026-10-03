<?php

class EventSeriesController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $series = (new EventSeries($this->db))->all();
        $this->render('event_series/index', compact('series'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('event_series/form', ['seriesItem' => null, 'sessions' => []]);
    }

    public function store(): void
    {
        requireAdmin();
        try {
            $id = (new EventSeries($this->db))->save($this->data());
            flash('success', 'Série criada com sucesso.');
            $this->redirect(BASE_URL . '?controller=eventseries&action=edit&id=' . $id);
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
            $this->redirect(BASE_URL . '?controller=eventseries&action=create');
        }
    }

    public function edit(): void
    {
        requireAdmin();
        $id = max(0, (int)($_GET['id'] ?? 0));
        $model = new EventSeries($this->db);
        $seriesItem = $model->find($id);
        if (!$seriesItem) {
            flash('error', 'Série não encontrada.');
            $this->redirect(BASE_URL . '?controller=eventseries');
        }
        $sessions = $model->sessions($id);
        $this->render('event_series/form', compact('seriesItem', 'sessions'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = max(0, (int)($_GET['id'] ?? 0));
        try {
            (new EventSeries($this->db))->save($this->data(), $id);
            flash('success', 'Série atualizada.');
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
        }
        $this->redirect(BASE_URL . '?controller=eventseries&action=edit&id=' . $id);
    }

    public function toggle(): void
    {
        requireAdmin();
        (new EventSeries($this->db))->setActive((int)($_GET['id'] ?? 0), (int)($_POST['active'] ?? 0) === 1);
        flash('success', 'Estado da série atualizado.');
        $this->redirect(BASE_URL . '?controller=eventseries');
    }

    private function data(): array
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = EventSeries::slugify((string)($_POST['slug'] ?? $name));
        if ($name === '' || $slug === '') {
            throw new InvalidArgumentException('Nome e slug são obrigatórios.');
        }
        return [
            'name' => $name,
            'slug' => $slug,
            'location' => trim((string)($_POST['location'] ?? '')) ?: null,
            'description' => trim((string)($_POST['description'] ?? '')) ?: null,
            'cover_image_url' => trim((string)($_POST['cover_image_url'] ?? '')) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }
}
