<?php

class CrmController extends BaseController
{
    public function index(): void
    {
        requireAdmin();

        $crm = new CrmContact($this->db);
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'contact_type' => trim($_GET['contact_type'] ?? ''),
            'market' => trim($_GET['market'] ?? ''),
            'priority' => trim($_GET['priority'] ?? ''),
            'country' => trim($_GET['country'] ?? ''),
            'last_contact_from' => trim($_GET['last_contact_from'] ?? ''),
            'last_contact_to' => trim($_GET['last_contact_to'] ?? ''),
        ];

        $sortBy = trim($_GET['sort_by'] ?? 'last_contact_at');
        $sortDir = strtoupper(trim($_GET['sort_dir'] ?? 'DESC'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 15;

        $results = $crm->listPaginated($filters, $sortBy, $sortDir, $page, $perPage);
        $totalPages = max(1, (int)ceil($results['total'] / $perPage));
        $metrics = $crm->metrics();

        $this->render('crm/index', [
            'contacts' => $results['rows'],
            'filters' => $filters,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'page' => min($page, $totalPages),
            'totalPages' => $totalPages,
            'total' => $results['total'],
            'metrics' => $metrics,
            'statuses' => CrmContact::statuses(),
            'types' => CrmContact::types(),
            'markets' => CrmContact::markets(),
            'priorities' => CrmContact::priorities(),
        ]);
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('crm/form', [
            'contact' => null,
            'statuses' => CrmContact::statuses(),
            'types' => CrmContact::types(),
            'markets' => CrmContact::markets(),
            'priorities' => CrmContact::priorities(),
        ]);
    }

    public function store(): void
    {
        requireAdmin();
        $data = $this->validatedData();
        $id = (new CrmContact($this->db))->create($data);

        flash('success', 'Contacto CRM criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=crm&action=show&id=' . $id);
    }

    public function show(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $contact = (new CrmContact($this->db))->find($id);

        if (!$contact) {
            http_response_code(404);
            echo 'Contacto CRM não encontrado.';
            return;
        }

        $this->render('crm/show', [
            'contact' => $contact,
            'statuses' => CrmContact::statuses(),
            'types' => CrmContact::types(),
            'markets' => CrmContact::markets(),
            'priorities' => CrmContact::priorities(),
        ]);
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $contact = (new CrmContact($this->db))->find($id);

        if (!$contact) {
            http_response_code(404);
            echo 'Contacto CRM não encontrado.';
            return;
        }

        $this->render('crm/form', [
            'contact' => $contact,
            'statuses' => CrmContact::statuses(),
            'types' => CrmContact::types(),
            'markets' => CrmContact::markets(),
            'priorities' => CrmContact::priorities(),
        ]);
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new CrmContact($this->db))->update($id, $this->validatedData());

        flash('success', 'Contacto CRM atualizado com sucesso.');
        $this->redirect(BASE_URL . '?controller=crm&action=show&id=' . $id);
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new CrmContact($this->db))->delete($id);

        flash('success', 'Contacto eliminado.');
        $this->redirect(BASE_URL . '?controller=crm&action=index');
    }

    public function archive(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new CrmContact($this->db))->archive($id);

        flash('success', 'Contacto arquivado.');
        $this->redirect(BASE_URL . '?controller=crm&action=index');
    }

    public function quickAction(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $type = trim($_GET['type'] ?? '');
        $crm = new CrmContact($this->db);

        switch ($type) {
            case 'mark_contacted':
                $crm->quickUpdate($id, [
                    'status' => 'contactado',
                    'last_contact_at' => date('Y-m-d'),
                ]);
                flash('success', 'Contacto marcado como contactado.');
                break;
            case 'change_status':
                $status = trim($_POST['status'] ?? '');
                if (in_array($status, CrmContact::statuses(), true)) {
                    $crm->quickUpdate($id, ['status' => $status]);
                    flash('success', 'Estado atualizado.');
                } else {
                    flash('error', 'Estado inválido.');
                }
                break;
            case 'schedule_follow_up':
                $followUp = trim($_POST['next_follow_up_at'] ?? '');
                $crm->quickUpdate($id, ['next_follow_up_at' => $followUp !== '' ? $followUp : null]);
                flash('success', 'Follow-up atualizado.');
                break;
            case 'duplicate':
                $newId = $crm->duplicate($id);
                if ($newId !== null) {
                    flash('success', 'Contacto duplicado com sucesso.');
                    $this->redirect(BASE_URL . '?controller=crm&action=show&id=' . $newId);
                    return;
                }
                flash('error', 'Não foi possível duplicar o contacto.');
                break;
            case 'archive':
                $crm->archive($id);
                flash('success', 'Contacto arquivado.');
                break;
            default:
                flash('error', 'Ação rápida inválida.');
        }

        $back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '?controller=crm&action=index');
        $this->redirect($back);
    }

    private function validatedData(): array
    {
        $entityName = trim($_POST['entity_name'] ?? '');
        if ($entityName === '') {
            flash('error', 'O nome da entidade é obrigatório.');
            $this->redirect(BASE_URL . '?controller=crm&action=index');
        }

        $status = trim($_POST['status'] ?? 'novo');
        if (!in_array($status, CrmContact::statuses(), true)) {
            $status = 'novo';
        }

        $contactType = trim($_POST['contact_type'] ?? 'outro');
        if (!in_array($contactType, CrmContact::types(), true)) {
            $contactType = 'outro';
        }

        $market = trim($_POST['market'] ?? 'nacional');
        if (!in_array($market, CrmContact::markets(), true)) {
            $market = 'nacional';
        }

        $priority = trim($_POST['priority'] ?? 'media');
        if (!in_array($priority, CrmContact::priorities(), true)) {
            $priority = 'media';
        }

        $email = trim($_POST['email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Email inválido.');
            $this->redirect($_SERVER['HTTP_REFERER'] ?? (BASE_URL . '?controller=crm&action=index'));
        }

        return [
            'entity_name' => $entityName,
            'contact_name' => trim($_POST['contact_name'] ?? ''),
            'email' => $email,
            'phone' => trim($_POST['phone'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'social_profile' => trim($_POST['social_profile'] ?? ''),
            'country' => trim($_POST['country'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'market' => $market,
            'contact_type' => $contactType,
            'status' => $status,
            'priority' => $priority,
            'lead_source' => trim($_POST['lead_source'] ?? ''),
            'first_contact_at' => $this->normalizeDate($_POST['first_contact_at'] ?? null),
            'last_contact_at' => $this->normalizeDate($_POST['last_contact_at'] ?? null),
            'next_follow_up_at' => $this->normalizeDate($_POST['next_follow_up_at'] ?? null),
            'potential_value' => $this->normalizeCurrency($_POST['potential_value'] ?? null),
            'observations' => trim($_POST['observations'] ?? ''),
            'internal_notes' => trim($_POST['internal_notes'] ?? ''),
        ];
    }

    private function normalizeDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function normalizeCurrency($value): ?float
    {
        $raw = str_replace(',', '.', trim((string)$value));
        if ($raw === '') {
            return null;
        }
        if (!is_numeric($raw)) {
            return null;
        }

        return round((float)$raw, 2);
    }
}
