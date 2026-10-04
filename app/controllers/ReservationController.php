<?php

require_once __DIR__ . '/../helpers/SimplePdf.php';

class ReservationController extends BaseController
{
    public function eventos(): void
    {
        requireLogin();
        $this->requireAdmissionAccess();
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: private, no-store, max-age=0');
        $reservationModel = new Reservation($this->db);
        $accessUserId = isAdmin() ? null : (int)(currentUser()['id'] ?? 0);
        $eventOverview = $reservationModel->admissionsEventOverview($accessUserId);
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
        $ticketsOverview = $reservationModel->ticketsOverview($selectedEventId > 0 ? $selectedEventId : null, $accessUserId);
        $this->render('reservations/eventos', compact('eventOverview', 'validationResult', 'ticketsOverview', 'selectedEventId'), 'admissions');
    }

    public function index(): void
    {
        requireAdmin();
        $reservationModel = new Reservation($this->db);
        $selectedEventId = max(0, (int)($_GET['event_id'] ?? 0));
        $admissionFilter = (string)($_GET['admission'] ?? 'all');
        if (!in_array($admissionFilter, ['all', 'pending', 'validated'], true)) {
            $admissionFilter = 'all';
        }
        $eventFilter = (string)($_GET['event_filter'] ?? 'upcoming');
        if (!in_array($eventFilter, ['upcoming', 'open'], true)) {
            $eventFilter = 'upcoming';
        }
        $eventPage = max(1, (int)($_GET['event_page'] ?? 1));
        $eventsPerPage = 10;
        $eventTotal = $reservationModel->eventOverviewCount($eventFilter);
        $eventPages = max(1, (int)ceil($eventTotal / $eventsPerPage));
        $eventPage = min($eventPage, $eventPages);

        $reservations = $reservationModel->all(
            $selectedEventId > 0 ? $selectedEventId : null,
            $admissionFilter === 'all' ? null : $admissionFilter
        );
        $reservationEvents = $reservationModel->reservationEvents();
        $eventOverview = $reservationModel->eventOverview($eventFilter, $eventsPerPage, ($eventPage - 1) * $eventsPerPage);
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
            'reservationEvents',
            'selectedEventId',
            'admissionFilter',
            'eventFilter',
            'eventPage',
            'eventPages'
        ));
    }

    public function export(): void
    {
        requireAdmin();
        $eventId = max(0, (int)($_GET['event_id'] ?? 0));
        $admission = (string)($_GET['admission'] ?? 'all');
        $rows = (new Reservation($this->db))->all(
            $eventId > 0 ? $eventId : null,
            in_array($admission, ['pending', 'validated'], true) ? $admission : null
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="reservas-' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Evento', 'Data', 'Local/Cidade', 'Cliente', 'Email', 'Telefone', 'Bilhetes', 'Estado', 'Admissão', 'Criada em'], ';');
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['event_title'], $row['event_date'] . ' ' . substr((string)$row['event_time'], 0, 5),
                $row['event_location'], $row['customer_name'], $row['customer_email'], $row['customer_phone'],
                $row['tickets'], $row['status'], $row['admission_status'], $row['created_at'],
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function addToNewsletter(): void
    {
        requireAdmin();
        $eventId = max(0, (int)($_POST['event_id'] ?? 0));
        if ($eventId <= 0) {
            flash('error', 'Selecione um evento para segmentar os contactos.');
            $this->redirect(BASE_URL . '?controller=reservation&action=index');
        }

        $reservations = (new Reservation($this->db))->all($eventId);
        $newsletter = new NewsletterSubscription($this->db);
        $added = 0;
        $skipped = 0;
        foreach ($reservations as $reservation) {
            if ((int)($reservation['gdpr_consent'] ?? 0) !== 1 || !filter_var($reservation['customer_email'], FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            $newsletter->subscribeFromReservation([
                'email' => $reservation['customer_email'],
                'name' => $reservation['customer_name'],
                'segment' => $this->cityFromLocation((string)$reservation['event_location']),
                'source' => 'reserva: ' . $reservation['event_title'],
                'consent_text' => (string)($reservation['gdpr_consent_text'] ?? 'Consentimento recolhido na reserva.'),
            ]);
            $added++;
        }
        flash('success', $added . ' contacto(s) adicionado(s)/atualizado(s) na newsletter por cidade.' . ($skipped ? ' ' . $skipped . ' sem consentimento válido foram ignorados.' : ''));
        $this->redirect(BASE_URL . '?controller=reservation&action=index&event_id=' . $eventId);
    }

    private function cityFromLocation(string $location): string
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
        return $parts ? (string)end($parts) : 'Sem cidade';
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
        $this->requireAdmissionAccess($this->wantsJson());
        $token = trim((string)($_REQUEST['token'] ?? ''));
        $eventId = (int)($_REQUEST['event_id'] ?? 0);
        $result = (new Reservation($this->db))->validateTicket(
            $token,
            (int)(currentUser()['id'] ?? 0),
            $eventId > 0 ? $eventId : null,
            isAdmin()
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
        $this->requireAdmissionAccess(true);

        $eventId = (int)($_GET['event_id'] ?? 0);
        $accessUserId = isAdmin() ? null : (int)(currentUser()['id'] ?? 0);
        // Keep this endpoint compatible with the PHP version used by the
        // production host. In particular, do not replace this callback-free
        // code with PHP 7.4 arrow functions (`fn (...) => ...`).
        $reservationModel = new Reservation($this->db);
        $tickets = $reservationModel->ticketsOverview($eventId > 0 ? $eventId : null, $accessUserId);
        $this->json(['ok' => true, 'tickets' => $tickets]);
    }

    public function exportAdmissions(): void
    {
        requireLogin();
        $this->requireAdmissionAccess();

        $eventId = max(0, (int)($_GET['event_id'] ?? 0));
        $format = strtolower((string)($_GET['format'] ?? 'pdf'));
        $accessUserId = isAdmin() ? null : (int)(currentUser()['id'] ?? 0);
        $model = new Reservation($this->db);
        $events = $model->admissionsEventOverview($accessUserId);
        $event = null;
        foreach ($events as $candidate) {
            if ((int)$candidate['id'] === $eventId) {
                $event = $candidate;
                break;
            }
        }
        if (!$event) {
            http_response_code(404);
            echo 'Evento não encontrado ou sem acesso.';
            exit;
        }

        $tickets = $model->ticketsOverview($eventId, $accessUserId);
        $safeTitle = preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$event['title']) ?: 'evento');
        $filename = 'admissoes-' . trim(strtolower((string)$safeTitle), '-') . '-' . (string)$event['date'];
        if ($format === 'excel') {
            $this->exportAdmissionsCsv($event, $tickets, $filename);
        }
        if ($format !== 'pdf') {
            http_response_code(400);
            echo 'Formato de exportação inválido.';
            exit;
        }
        $this->exportAdmissionsPdf($event, $tickets, $filename);
    }

    private function exportAdmissionsCsv(array $event, array $tickets, string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Evento', 'Data', 'Cliente', 'Email', 'Telefone', 'Bilhete', 'Estado', 'Entrada'], ';');
        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $event['title'], $event['date'] . ' ' . substr((string)$event['time'], 0, 5),
                $ticket['customer_name'], $ticket['customer_email'], $ticket['customer_phone'], '#' . $ticket['ticket_no'],
                (int)$ticket['is_used'] === 1 ? 'Validado' : 'A validar',
                (int)$ticket['is_used'] === 1 ? (string)$ticket['used_at'] : '',
            ], ';');
        }
        fclose($output);
        exit;
    }

    private function exportAdmissionsPdf(array $event, array $tickets, string $filename): void
    {
        if (!class_exists('SimplePdf', false)) {
            $helperPath = __DIR__ . '/../helpers/SimplePdf.php';
            if (!is_file($helperPath)) {
                http_response_code(500);
                echo 'Não foi possível carregar o gerador de PDF.';
                exit;
            }
            require_once $helperPath;
        }

        $pdf = new SimplePdf();
        $total = count($tickets);
        $admitted = $this->countAdmittedTickets($tickets);
        $chunks = array_chunk($tickets, 27);
        if (!$chunks) {
            $chunks = [[]];
        }
        $pageTotal = count($chunks);
        foreach ($chunks as $pageIndex => $rows) {
            $commands = [
                SimplePdf::rectangle(0, 760, 595.28, 81.89, [0.04, 0.04, 0.05]),
                SimplePdf::rectangle(0, 754, 595.28, 6, [0.70, 0.02, 0.04]),
                SimplePdf::text(32, 809, 'CHORAR DE RIR', 10, true, [1, 1, 1]),
                SimplePdf::text(32, 782, 'Relatório de admissões', 21, true, [1, 1, 1]),
                SimplePdf::text(32, 730, SimplePdf::truncate((string)$event['title'], 72), 16, true),
                SimplePdf::text(32, 711, 'Data: ' . $event['date'] . ' às ' . substr((string)$event['time'], 0, 5), 10),
                SimplePdf::text(32, 697, 'Local: ' . SimplePdf::truncate((string)($event['location'] ?? '—'), 70), 9, false, [0.35, 0.35, 0.35]),
                SimplePdf::text(312, 711, 'Emitido: ' . date('Y-m-d H:i'), 9, false, [0.35, 0.35, 0.35]),
                SimplePdf::rectangle(32, 657, 164, 38, [0.94, 0.95, 0.96]),
                SimplePdf::rectangle(206, 657, 164, 38, [0.90, 0.97, 0.92]),
                SimplePdf::rectangle(380, 657, 183, 38, [0.99, 0.94, 0.94]),
                SimplePdf::text(44, 678, 'TOTAL DE BILHETES', 7, true, [0.35, 0.35, 0.35]),
                SimplePdf::text(44, 662, (string)$total, 13, true),
                SimplePdf::text(218, 678, 'VALIDADOS', 7, true, [0.18, 0.45, 0.24]),
                SimplePdf::text(218, 662, $admitted . '  (' . ($total ? (int)round($admitted * 100 / $total) : 0) . '%)', 13, true, [0.10, 0.38, 0.18]),
                SimplePdf::text(392, 678, 'POR VALIDAR', 7, true, [0.55, 0.15, 0.16]),
                SimplePdf::text(392, 662, (string)($total - $admitted), 13, true, [0.55, 0.10, 0.12]),
                SimplePdf::rectangle(32, 623, 531, 22, [0.15, 0.16, 0.18]),
                SimplePdf::text(40, 630, 'Cliente', 8, true, [1, 1, 1]),
                SimplePdf::text(250, 630, 'Bilhete', 8, true, [1, 1, 1]),
                SimplePdf::text(310, 630, 'Estado', 8, true, [1, 1, 1]),
                SimplePdf::text(420, 630, 'Entrada', 8, true, [1, 1, 1]),
            ];
            $y = 604;
            foreach ($rows as $index => $ticket) {
                if ($index % 2 === 1) {
                    $commands[] = SimplePdf::rectangle(32, $y - 6, 531, 21, [0.97, 0.97, 0.97]);
                }
                $isUsed = (int)$ticket['is_used'] === 1;
                $commands[] = SimplePdf::text(40, $y, SimplePdf::truncate((string)$ticket['customer_name'], 37), 8);
                $commands[] = SimplePdf::text(250, $y, '#' . $ticket['ticket_no'], 8, true);
                $commands[] = SimplePdf::text(310, $y, $isUsed ? 'Validado' : 'A validar', 8, true, $isUsed ? [0.08, 0.45, 0.19] : [0.50, 0.25, 0.08]);
                $commands[] = SimplePdf::text(420, $y, $isUsed ? substr((string)$ticket['used_at'], 0, 16) : '—', 8);
                $y -= 21;
            }
            if (!$rows) {
                $commands[] = SimplePdf::text(40, 600, 'Não existem bilhetes para apresentar.', 10, false, [0.4, 0.4, 0.4]);
            }
            $commands[] = SimplePdf::text(32, 25, 'chorarderir.com  |  Relatório confidencial', 8, false, [0.45, 0.45, 0.45]);
            $commands[] = SimplePdf::text(515, 25, ($pageIndex + 1) . ' / ' . $pageTotal, 8, false, [0.45, 0.45, 0.45]);
            $pdf->addPage($commands);
        }

        $content = $pdf->render();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    public function markTicketPending(): void
    {
        requireLogin();
        $this->requireAdmissionAccess(true);

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $accessUserId = isAdmin() ? null : (int)(currentUser()['id'] ?? 0);
        $reservationModel = new Reservation($this->db);
        $ok = $ticketId > 0 && $reservationModel->markTicketPending($ticketId, $accessUserId);
        $this->json(['ok' => $ok], $ok ? 200 : 404);
    }

    private function countAdmittedTickets(array $tickets): int
    {
        $admitted = 0;
        foreach ($tickets as $ticket) {
            if ((int)($ticket['is_used'] ?? 0) === 1) {
                $admitted++;
            }
        }
        return $admitted;
    }

    private function wantsJson(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
            || strpos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    }

    private function requireAdmissionAccess(bool $json = false): void
    {
        if (isAdmin()) {
            return;
        }

        $userId = (int)(currentUser()['id'] ?? 0);
        if ($userId > 0 && (new User($this->db))->hasAdmissionAccess($userId)) {
            return;
        }

        if ($json) {
            $this->json(['ok' => false, 'reason' => 'not_found'], 404);
        }
        http_response_code(404);
        echo 'Página não encontrada.';
        exit;
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
