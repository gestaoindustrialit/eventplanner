<?php

class ReservationController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $reservationModel = new Reservation($this->db);
        $reservations = $reservationModel->all();
        $eventOverview = $reservationModel->eventOverview();
        $validationResult = $_SESSION['reservation_validation_result'] ?? null;
        unset($_SESSION['reservation_validation_result']);
        $settings = new SiteSetting($this->db);
        $emailTemplateA = $settings->get(
            'reservation_email_template_a',
            "Olá {customer_name},\n\nRecebemos a tua reserva para \"{event_title}\" no dia {event_date} às {event_time}.\nBilhetes reservados: {tickets}.\n\nObrigado!"
        ) ?? '';
        $emailTemplateB = $settings->get(
            'reservation_email_template_b',
            "Olá {customer_name},\n\nA tua reserva para \"{event_title}\" foi submetida com sucesso.\nData: {event_date} às {event_time}\nNº de bilhetes: {tickets}\n\nEntraremos em contacto em breve para confirmação final."
        ) ?? '';
        $selectedEmailTemplate = $settings->get('reservation_email_template_selected', 'a') ?? 'a';
        $validationBaseUrl = $settings->get(
            'reservation_validation_base_url',
            BASE_URL . '?controller=reservation&action=validateTicket&token='
        ) ?? '';

        $this->render('reservations/index', compact(
            'reservations',
            'eventOverview',
            'emailTemplateA',
            'emailTemplateB',
            'selectedEmailTemplate',
            'validationBaseUrl',
            'validationResult'
        ));
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

    public function updateEmailTemplates(): void
    {
        requireAdmin();
        $settings = new SiteSetting($this->db);

        $templateA = trim((string)($_POST['reservation_email_template_a'] ?? ''));
        $templateB = trim((string)($_POST['reservation_email_template_b'] ?? ''));
        $selected = (string)($_POST['reservation_email_template_selected'] ?? 'a');
        $validationBaseUrl = trim((string)($_POST['reservation_validation_base_url'] ?? ''));

        $settings->set(
            'reservation_email_template_a',
            $templateA !== '' ? $templateA : "Olá {customer_name},\n\nRecebemos a tua reserva para \"{event_title}\" no dia {event_date} às {event_time}.\nBilhetes reservados: {tickets}.\n\nObrigado!"
        );
        $settings->set(
            'reservation_email_template_b',
            $templateB !== '' ? $templateB : "Olá {customer_name},\n\nA tua reserva para \"{event_title}\" foi submetida com sucesso.\nData: {event_date} às {event_time}\nNº de bilhetes: {tickets}\n\nEntraremos em contacto em breve para confirmação final."
        );
        $settings->set('reservation_email_template_selected', $selected === 'b' ? 'b' : 'a');
        $settings->set(
            'reservation_validation_base_url',
            $validationBaseUrl !== '' ? $validationBaseUrl : BASE_URL . '?controller=reservation&action=validateTicket&token='
        );

        flash('success', 'Modelos de e-mail de confirmação guardados.');
        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Reserva inválida.');
            $this->redirect(BASE_URL . '?controller=reservation&action=index');
        }

        $settings = new SiteSetting($this->db);
        $validationBaseUrl = $settings->get(
            'reservation_validation_base_url',
            BASE_URL . '?controller=reservation&action=validateTicket&token='
        ) ?? '';

        (new Reservation($this->db))->updateReservation($id, [
            'customer_name' => (string)($_POST['customer_name'] ?? ''),
            'customer_email' => (string)($_POST['customer_email'] ?? ''),
            'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
            'tickets' => (int)($_POST['tickets'] ?? 1),
            'notes' => (string)($_POST['notes'] ?? ''),
            'status' => (string)($_POST['status'] ?? 'new'),
            'validation_base_url' => $validationBaseUrl,
        ]);

        flash('success', 'Reserva atualizada.');
        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new Reservation($this->db))->delete($id);
            flash('success', 'Reserva eliminada.');
        }
        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }

    public function validateTicket(): void
    {
        requireLogin();
        $token = trim((string)($_REQUEST['token'] ?? ''));
        $result = (new Reservation($this->db))->validateTicket($token, (int)(currentUser()['id'] ?? 0));
        $_SESSION['reservation_validation_result'] = $result;
        $this->redirect(BASE_URL . '?controller=reservation&action=index#validacao-qr');
    }
}
