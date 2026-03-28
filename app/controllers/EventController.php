<?php

class EventController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $events = (new Event($this->db))->all();
        $this->render('events/index', compact('events'));
    }

    public function create(): void
    {
        requireAdmin();
        $clients = (new Client($this->db))->all();
        $comedians = (new Comedian($this->db))->all();
        $this->render('events/form', ['event' => null, 'clients' => $clients, 'comedians' => $comedians, 'lineup' => []]);
    }

    public function store(): void
    {
        requireAdmin();
        $eventModel = new Event($this->db);
        $eventModel->create($this->validatedData(), $this->lineupData());
        flash('success', 'Evento criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=event&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);
        $event = $eventModel->find($id);
        $lineup = $eventModel->lineup($id);
        $clients = (new Client($this->db))->all();
        $comedians = (new Comedian($this->db))->all();

        $this->render('events/form', compact('event', 'lineup', 'clients', 'comedians'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);
        $eventModel->update($id, $this->validatedData(), $this->lineupData());
        flash('success', 'Evento atualizado.');
        $this->redirect(BASE_URL . '?controller=event&action=index');
    }

    public function show(): void
    {
        requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);
        $event = $eventModel->find($id);
        $lineup = $eventModel->lineup($id);

        $this->render('events/show', compact('event', 'lineup'));
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Event($this->db))->delete($id);
        flash('success', 'Evento eliminado.');
        $this->redirect(BASE_URL . '?controller=event&action=index');
    }

    private function validatedData(): array
    {
        return [
            'title' => trim($_POST['title'] ?? ''),
            'date' => $_POST['date'] ?? date('Y-m-d'),
            'time' => $_POST['time'] ?? '20:00',
            'location' => trim($_POST['location'] ?? ''),
            'client_id' => (int)($_POST['client_id'] ?? 0),
            'cachet_total' => (float)($_POST['cachet_total'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function lineupData(): array
    {
        $comedianIds = $_POST['lineup_comedian_id'] ?? [];
        $roles = $_POST['lineup_role'] ?? [];
        $cachets = $_POST['lineup_cachet'] ?? [];
        $notes = $_POST['lineup_notes'] ?? [];

        $lineup = [];
        foreach ($comedianIds as $i => $comedianId) {
            $lineup[] = [
                'comedian_id' => $comedianId,
                'role' => $roles[$i] ?? 'opener',
                'cachet' => $cachets[$i] ?? 0,
                'notes' => $notes[$i] ?? null,
            ];
        }

        return $lineup;
    }
}
