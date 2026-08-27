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
        $eventOverview = $reservationModel->admissionsEventOverview();
        $selectedEventId = (int)($_GET['event_id'] ?? 0);
        $availableEventIds = array_map('intval', array_column($eventOverview, 'id'));
        if ($selectedEventId <= 0 || !in_array($selectedEventId, $availableEventIds, true)) {
            $selectedEventId = 0;
            $today = date('Y-m-d');
            foreach ($eventOverview as $event) {
                if ((string)$event['date'] === $today) {
                    $selectedEventId = (int)$event['id'];
                    break;
                }
            }
            if ($selectedEventId <= 0 && !empty($eventOverview)) {
                $selectedEventId = (int)$eventOverview[0]['id'];
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
        $logoUrl = 'https://chorarderir.com/chorarderir-logo.svg';
        $ticketHtml = '';
        foreach ($tickets as $ticket) {
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=230x230&data=' . rawurlencode((string)$ticket['qr_payload']);
            $ticketHtml .= '<div style="border:1px solid #dedede;border-left:4px solid #b30000;border-radius:10px;padding:18px;margin:14px 0;background:#fafafa">'
                . '<p style="margin:0 0 10px;font-size:18px;font-weight:700">Bilhete #' . (int)$ticket['ticket_no'] . '</p>'
                . '<p style="margin:0 0 8px">Evento: <strong>' . $eventTitle . '</strong><br>Data: ' . $eventDate . ' às ' . $eventTime . '</p>'
                . '<p style="margin:0 0 8px;font-size:12px;color:#475569">Token: ' . htmlspecialchars((string)$ticket['ticket_token']) . '</p>'
                . '<img src="' . htmlspecialchars($qrUrl) . '" alt="QR Bilhete #' . (int)$ticket['ticket_no'] . '" width="200" height="200" style="display:block;max-width:100%;height:auto;margin:14px auto 0">'
                . '</div>';
        }

        $htmlBody = '<div style="margin:0;padding:20px;background:#f4f4f4;font-family:Arial,sans-serif;color:#151515">'
            . '<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #dddddd;border-radius:14px;overflow:hidden">'
            . '<div style="padding:22px 24px;background:#050505;color:#fff;border-bottom:5px solid #b30000">'
            . '<img src="' . htmlspecialchars($logoUrl) . '" alt="Chorar de Rir" width="190" style="display:block;max-width:55%;height:auto;margin-bottom:20px;filter:invert(1)">'
            . '<h1 style="margin:0;font-size:24px;">Reserva confirmada</h1>'
            . '<p style="margin:8px 0 0;opacity:.9">Evento: <strong>' . $eventTitle . '</strong></p>'
            . '</div>'
            . '<div style="padding:clamp(20px,5vw,32px)">'
            . '<p style="margin-top:0">Olá <strong>' . $customerName . '</strong>,</p>'
            . '<p>A tua reserva foi confirmada com sucesso. Em baixo seguem os dados do evento e os QR codes dos bilhetes.</p>'
            . '<div style="background:#f7f7f7;border:1px solid #dddddd;border-radius:10px;padding:16px;margin:16px 0 22px">'
            . '<p style="margin:0 0 4px"><strong>Data:</strong> ' . $eventDate . ' às ' . $eventTime . '</p>'
            . '<p style="margin:0"><strong>Total de bilhetes:</strong> ' . count($tickets) . '</p>'
            . '</div>'
            . $ticketHtml
            . '<p style="margin-top:22px;padding-top:16px;border-top:1px solid #e5e5e5;color:#666;font-size:13px">Cada QR code só pode ser validado uma vez. Guarda este e-mail até ao dia do evento.</p>'
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
        $eventId = (int)($_REQUEST['event_id'] ?? 0);
        $result = (new Reservation($this->db))->validateTicket(
            $token,
            (int)(currentUser()['id'] ?? 0),
            $eventId > 0 ? $eventId : null
        );
        if ($this->wantsJson()) {
            $this->json($result);
        }
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

    public function admissionsData(): void
    {
        requireLogin();
        if (!can('reservation')) {
            $this->json(['ok' => false, 'reason' => 'forbidden'], 403);
        }

        $eventId = (int)($_GET['event_id'] ?? 0);
        $tickets = (new Reservation($this->db))->ticketsOverview($eventId > 0 ? $eventId : null);
        $this->json(['ok' => true, 'tickets' => $tickets]);
    }

    public function markTicketPending(): void
    {
        requireLogin();
        if (!can('reservation')) {
            $this->json(['ok' => false, 'reason' => 'forbidden'], 403);
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ok = $ticketId > 0 && (new Reservation($this->db))->markTicketPending($ticketId);
        $this->json(['ok' => $ok], $ok ? 200 : 404);
    }

    private function wantsJson(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
