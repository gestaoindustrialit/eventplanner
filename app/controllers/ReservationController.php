<?php

class ReservationController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $reservations = (new Reservation($this->db))->all();
        $this->render('reservations/index', compact('reservations'));
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
}
