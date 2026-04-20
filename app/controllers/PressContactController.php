<?php

class PressContactController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $contacts = [];

        try {
            $contacts = (new PressContact($this->db))->all();
        } catch (Exception $e) {
            error_log('PressContactController::index failed: ' . $e->getMessage());
            flash('error', 'Não foi possível carregar os contactos de imprensa. Verifica se a base de dados está atualizada.');
        }

        $this->render('press_contacts/index', compact('contacts'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('press_contacts/form', ['contact' => null]);
    }

    public function downloadTemplate(): void
    {
        requireAdmin();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="press_contacts_import_template.csv"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF";
        echo "name,email,locality,district,website\n";
        echo "\"Jornal Exemplo\",\"agenda@jornalexemplo.pt\",\"Lisboa\",\"Lisboa\",\"https://jornalexemplo.pt\"\n";
        echo "\"Rádio Exemplo\",\"cultura@radioexemplo.pt\",\"Porto\",\"Porto\",\"https://radioexemplo.pt\"\n";
        exit;
    }

    public function uploadTemplate(): void
    {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '?controller=presscontact&action=index');
            return;
        }

        if (empty($_FILES['contacts_csv']) || !is_uploaded_file($_FILES['contacts_csv']['tmp_name'])) {
            flash('error', 'Seleciona um ficheiro CSV para importar.');
            $this->redirect(BASE_URL . '?controller=presscontact&action=index');
            return;
        }

        $file = $_FILES['contacts_csv'];
        $handle = fopen($file['tmp_name'], 'rb');
        if ($handle === false) {
            flash('error', 'Não foi possível ler o ficheiro enviado.');
            $this->redirect(BASE_URL . '?controller=presscontact&action=index');
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            flash('error', 'Ficheiro CSV vazio.');
            $this->redirect(BASE_URL . '?controller=presscontact&action=index');
            return;
        }

        if (!empty($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]) ?? (string)$header[0];
        }

        $normalizedHeader = array_map(static function ($value): string {
            return strtolower(trim((string)$value));
        }, $header);
        $expected = ['name', 'email', 'locality', 'district', 'website'];
        if ($normalizedHeader !== $expected) {
            fclose($handle);
            flash('error', 'Cabeçalho inválido. Usa o template oficial: name,email,locality,district,website.');
            $this->redirect(BASE_URL . '?controller=presscontact&action=index');
            return;
        }

        $model = new PressContact($this->db);
        $imported = 0;
        $ignored = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === 1 && trim((string)$row[0]) === '') {
                continue;
            }

            $row = array_pad($row, 5, '');
            $data = [
                'name' => trim((string)$row[0]),
                'email' => trim((string)$row[1]),
                'locality' => trim((string)$row[2]),
                'district' => trim((string)$row[3]),
                'website' => trim((string)$row[4]),
            ];

            if ($data['name'] === '') {
                $ignored++;
                continue;
            }

            $model->create($data);
            $imported++;
        }
        fclose($handle);

        flash('success', sprintf('Importação concluída: %d contactos importados, %d ignorados.', $imported, $ignored));
        $this->redirect(BASE_URL . '?controller=presscontact&action=index');
    }

    public function outreach(): void
    {
        requireAdmin();

        $eventId = (int)($_GET['event_id'] ?? 0);
        $district = trim((string)($_GET['district'] ?? ''));
        $locality = trim((string)($_GET['locality'] ?? ''));

        $eventModel = new Event($this->db);
        $events = $eventModel->all(date('Y-m-d'));
        $event = $eventId > 0 ? $eventModel->find($eventId) : null;

        $pressContactModel = new PressContact($this->db);
        $districts = $pressContactModel->districts();
        $localities = $pressContactModel->localities($district ?: null);
        $contacts = $pressContactModel->filterByLocation($district ?: null, $locality ?: null);
        $emails = array_values(array_filter(array_map(static function (array $contact): string {
            return trim((string)$contact['email']);
        }, $contacts)));

        $emailList = implode(';', $emails);
        $subject = $event ? sprintf('Divulgação: %s', $event['title']) : 'Divulgação de evento';
        $body = $event ? $this->eventPressTemplate($event) : "Olá,\n\nPartilhamos o próximo evento para divulgação.\n\nObrigado.";
        $mailtoLink = 'mailto:?bcc=' . rawurlencode($emailList) . '&subject=' . rawurlencode($subject) . '&body=' . rawurlencode($body);

        $this->render('press_contacts/outreach', compact(
            'events',
            'event',
            'eventId',
            'districts',
            'district',
            'localities',
            'locality',
            'contacts',
            'emails',
            'mailtoLink'
        ));
    }

    public function store(): void
    {
        requireAdmin();
        (new PressContact($this->db))->create($this->validatedData());
        flash('success', 'Contacto de imprensa criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=presscontact&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $contact = (new PressContact($this->db))->find($id);
        $this->render('press_contacts/form', compact('contact'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new PressContact($this->db))->update($id, $this->validatedData());
        flash('success', 'Contacto de imprensa atualizado.');
        $this->redirect(BASE_URL . '?controller=presscontact&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new PressContact($this->db))->delete($id);
        flash('success', 'Contacto de imprensa eliminado com sucesso.');
        $this->redirect(BASE_URL . '?controller=presscontact&action=index');
    }

    private function validatedData(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'locality' => trim($_POST['locality'] ?? ''),
            'district' => trim($_POST['district'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
        ];
    }

    private function eventPressTemplate(array $event): string
    {
        $eventDate = !empty($event['date']) ? date('d/m/Y', strtotime((string)$event['date'])) : '';
        $eventTime = !empty($event['time']) ? substr((string)$event['time'], 0, 5) : '';

        return "Olá,\n\nSegue sugestão para agenda cultural:\n\n"
            . $event['title'] . "\n"
            . 'Data: ' . $eventDate . "\n"
            . 'Hora: ' . $eventTime . "\n"
            . 'Local: ' . $event['location'] . "\n\n"
            . "Agradecemos a divulgação.\n\n"
            . "Cumprimentos,";
    }
}
