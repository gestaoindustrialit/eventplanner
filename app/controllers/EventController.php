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

    public function openSchedule(): void
    {
        requireAdmin();
        $events = (new Event($this->db))->openEvents();
        $this->render('events/open_schedule', compact('events'));
    }

    public function schedule(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);
        $event = $eventModel->find($id);
        $scheduleItems = $eventModel->scheduleItems($id);
        $lineup = $eventModel->lineup($id);

        if (!$event) {
            flash('error', 'Evento não encontrado.');
            $this->redirect(BASE_URL . '?controller=event&action=openSchedule');
        }

        $this->render('events/schedule', compact('event', 'scheduleItems', 'lineup'));
    }

    public function saveSchedule(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);

        if (!$eventModel->find($id)) {
            flash('error', 'Evento não encontrado.');
            $this->redirect(BASE_URL . '?controller=event&action=openSchedule');
        }

        $eventModel->saveScheduleItems($id, $this->scheduleData());
        flash('success', 'Alinhamento guardado com sucesso.');
        $this->redirect(BASE_URL . '?controller=event&action=schedule&id=' . $id);
    }

    public function schedulePdf(): void
    {
        requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $eventModel = new Event($this->db);
        $event = $eventModel->find($id);
        $scheduleItems = $eventModel->scheduleItems($id);

        if (!$event) {
            http_response_code(404);
            echo 'Evento não encontrado.';
            exit;
        }

        include __DIR__ . '/../views/events/schedule_pdf.php';
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Event($this->db))->delete($id);
        flash('success', 'Evento eliminado.');
        $this->redirect(BASE_URL . '?controller=event&action=index');
    }

    public function toggleVisibility(): void
    {
        requireAdmin();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(BASE_URL . '?controller=event&action=index');
        }

        $id = (int)($_GET['id'] ?? 0);
        $isVisible = isset($_POST['is_visible']) && (int)$_POST['is_visible'] === 1;

        (new Event($this->db))->setVisibility($id, $isVisible);
        flash('success', $isVisible ? 'Evento visível no site público.' : 'Evento ocultado do site público.');
        $this->redirect(BASE_URL . '?controller=event&action=index');
    }

    public function duplicate(): void
    {
        requireAdmin();

        $id = (int)($_GET['id'] ?? 0);
        $newDate = $_POST['date'] ?? '';

        if (!$newDate) {
            flash('error', 'Selecione a nova data para duplicar o evento.');
            $this->redirect(BASE_URL . '?controller=event&action=index');
        }

        $eventModel = new Event($this->db);
        $newEventId = $eventModel->duplicate($id, $newDate);

        if (!$newEventId) {
            flash('error', 'Não foi possível duplicar o evento.');
            $this->redirect(BASE_URL . '?controller=event&action=index');
        }

        flash('success', 'Evento duplicado com sucesso.');
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
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'reservations_open' => isset($_POST['reservations_open']) ? 1 : 0,
            'reservation_capacity' => max(0, (int)($_POST['reservation_capacity'] ?? 0)),
            'cachet_total' => (float)($_POST['cachet_total'] ?? 0),
            'artist_map_link' => trim($_POST['artist_map_link'] ?? ''),
            'artist_details' => trim($_POST['artist_details'] ?? ''),
            'external_ticket_url' => trim($_POST['external_ticket_url'] ?? ''),
            'poster_url' => trim($_POST['poster_url'] ?? ''),
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

    private function scheduleData(): array
    {
        $startsAt = $_POST['schedule_starts_at'] ?? [];
        $durations = $_POST['schedule_duration'] ?? [];
        $types = $_POST['schedule_type'] ?? [];
        $titles = $_POST['schedule_title'] ?? [];
        $responsibles = $_POST['schedule_responsible'] ?? [];
        $notes = $_POST['schedule_notes'] ?? [];

        $schedule = [];
        foreach ($titles as $i => $title) {
            $schedule[] = [
                'starts_at' => $startsAt[$i] ?? '',
                'duration_minutes' => $durations[$i] ?? 15,
                'item_type' => $types[$i] ?? 'other',
                'title' => trim((string)$title),
                'responsible' => trim((string)($responsibles[$i] ?? '')),
                'notes' => trim((string)($notes[$i] ?? '')),
            ];
        }

        return $schedule;
    }
}
