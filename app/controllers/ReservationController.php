<?php

class ReservationController extends BaseController
{
    public function eventos(): void
    {
        requireLogin();
        if (!can('reservation')) {
            http_response_code(403);
            echo 'Acesso negado.';
            return;
        }
        $reservationModel = new Reservation($this->db);
        $eventOverview = $reservationModel->eventOverview();
        $selectedEventId = (int)($_GET['event_id'] ?? 0);
        if ($selectedEventId <= 0) {
            $today = date('Y-m-d');
            foreach ($eventOverview as $event) {
                if ((string)$event['date'] === $today) {
                    $selectedEventId = (int)$event['id'];
                    break;
                }
            }
        }
        $validationResult = $_SESSION['reservation_validation_result'] ?? null;
        unset($_SESSION['reservation_validation_result']);
        $ticketsOverview = $reservationModel->ticketsOverview($selectedEventId > 0 ? $selectedEventId : null);
        $this->render('reservations/eventos', compact('eventOverview', 'validationResult', 'ticketsOverview', 'selectedEventId'));
    }

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

        $reservationModel = new Reservation($this->db);
        $beforeUpdate = $reservationModel->find($id);

        $reservationModel->updateReservation($id, [
            'customer_name' => (string)($_POST['customer_name'] ?? ''),
            'customer_email' => (string)($_POST['customer_email'] ?? ''),
            'customer_phone' => (string)($_POST['customer_phone'] ?? ''),
            'tickets' => (int)($_POST['tickets'] ?? 1),
            'notes' => (string)($_POST['notes'] ?? ''),
            'status' => (string)($_POST['status'] ?? 'new'),
            'validation_base_url' => $validationBaseUrl,
        ]);

        $afterUpdate = $reservationModel->find($id);
        if ($beforeUpdate && $afterUpdate) {
            $statusChangedToConfirmed = (string)$beforeUpdate['status'] !== 'confirmed'
                && (string)$afterUpdate['status'] === 'confirmed';
            if ($statusChangedToConfirmed) {
                $this->sendConfirmationEmail($reservationModel, $afterUpdate);
            }
        }

        flash('success', 'Reserva atualizada.');
        $this->redirect(BASE_URL . '?controller=reservation&action=index');
    }

    private function sendConfirmationEmail(Reservation $reservationModel, array $reservation): void
    {
        $customerEmail = trim((string)($reservation['customer_email'] ?? ''));
        if ($customerEmail === '') {
            return;
        }

        $subject = 'Reserva confirmada - ' . (string)($reservation['event_title'] ?? 'Evento');
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: noreply@chorarderir.com',
            'Reply-To: noreply@chorarderir.com',
        ];

        $eventDate = htmlspecialchars((string)($reservation['event_date'] ?? ''));
        $eventTime = htmlspecialchars(substr((string)($reservation['event_time'] ?? ''), 0, 5));
        $eventTitle = htmlspecialchars((string)($reservation['event_title'] ?? 'Evento'));
        $customerName = htmlspecialchars((string)($reservation['customer_name'] ?? ''));

        $tickets = $reservationModel->ticketsByReservation((int)$reservation['id']);
        $ticketHtml = '';
        foreach ($tickets as $ticket) {
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=' . rawurlencode((string)$ticket['qr_payload']);
            $ticketHtml .= '<div style="border:1px solid #dbe4f0;border-radius:12px;padding:16px;margin:12px 0;background:#f8fafc">'
                . '<p style="margin:0 0 8px;font-weight:700">Bilhete #' . (int)$ticket['ticket_no'] . '</p>'
                . '<p style="margin:0 0 8px">Evento: <strong>' . $eventTitle . '</strong><br>Data: ' . $eventDate . ' às ' . $eventTime . '</p>'
                . '<p style="margin:0 0 8px;font-size:12px;color:#475569">Token: ' . htmlspecialchars((string)$ticket['ticket_token']) . '</p>'
                . '<img src="' . htmlspecialchars($qrUrl) . '" alt="QR Bilhete #' . (int)$ticket['ticket_no'] . '" width="180" height="180">'
                . '</div>';
        }

        $htmlBody = '<div style="margin:0;padding:24px;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#0f172a">'
            . '<div style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden">'
            . '<div style="padding:24px;background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff">'
            . '<h1 style="margin:0;font-size:22px;">Reserva Confirmada ✅</h1>'
            . '<p style="margin:8px 0 0;opacity:.9">Evento: <strong>' . $eventTitle . '</strong></p>'
            . '</div>'
            . '<div style="padding:24px">'
            . '<p style="margin-top:0">Olá <strong>' . $customerName . '</strong>,</p>'
            . '<p>A tua reserva foi confirmada com sucesso. Em baixo seguem os dados do evento e os QR codes dos bilhetes.</p>'
            . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;margin:14px 0 20px">'
            . '<p style="margin:0 0 4px"><strong>Data:</strong> ' . $eventDate . ' às ' . $eventTime . '</p>'
            . '<p style="margin:0"><strong>Total de bilhetes:</strong> ' . count($tickets) . '</p>'
            . '</div>'
            . $ticketHtml
            . '<p style="margin-top:20px;color:#475569;font-size:12px">Cada QR code só pode ser validado uma vez. Guarda este e-mail até ao dia do evento.</p>'
            . '</div>'
            . '</div>'
            . '</div>';

        @mail($customerEmail, $subject, $htmlBody, implode("\r\n", $headers));
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
        $redirectTarget = (string)($_REQUEST['redirect'] ?? 'index');
        if ($redirectTarget === 'eventos') {
            $eventId = (int)($_REQUEST['event_id'] ?? 0);
            $redirectUrl = BASE_URL . '?controller=reservation&action=eventos';
            if ($eventId > 0) {
                $redirectUrl .= '&event_id=' . $eventId;
            }
            $this->redirect($redirectUrl);
        }
        $this->redirect(BASE_URL . '?controller=reservation&action=index#validacao-qr');
    }
}
