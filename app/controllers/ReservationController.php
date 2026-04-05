<?php

class ReservationController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $reservationModel = new Reservation($this->db);
        $reservations = $reservationModel->all();
        $eventOverview = $reservationModel->eventOverview();
        $this->render('reservations/index', compact('reservations', 'eventOverview'));
    }

    public function updateStatus(): void
    {
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'new');

        if ($id > 0) {
            (new Reservation($this->db))->updateStatus($id, $status);
            flash('success', 'Estado da reserva atualizado.');
        }

        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }

    public function updateEventAvailability(): void
    {
        requireAdmin();
        $eventId = (int)($_POST['event_id'] ?? 0);

        if ($eventId > 0) {
            (new Reservation($this->db))->updateEventAvailability(
                $eventId,
                isset($_POST['reservations_open']),
                (int)($_POST['reservation_capacity'] ?? 0)
            );
            flash('success', 'Disponibilidade de reservas atualizada.');
        }

        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }
}
