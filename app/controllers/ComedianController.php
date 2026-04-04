<?php

class ComedianController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $comedianModel = new Comedian($this->db);
        $comedians = $comedianModel->all();
        $this->render('comedians/index', compact('comedians'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('comedians/form', ['comedian' => null, 'user' => null]);
    }

    public function store(): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(BASE_URL . '?controller=comedian&action=create');
        }

        $data = $this->validatedData();
        $userData = $this->validatedUserData();

        try {
            $this->db->beginTransaction();
            $data['attachment_path'] = $this->handleAttachmentUpload(null);
            $data['user_id'] = $this->syncUserAccess(null, $userData);
            (new Comedian($this->db))->create($data);
            $this->db->commit();
            flash('success', 'Comediante criado com sucesso.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', 'Não foi possível criar o comediante: ' . $e->getMessage());
            $this->redirect(BASE_URL . '?controller=comedian&action=create');
        }

        $this->redirect(BASE_URL . '?controller=comedian&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $comedianModel = new Comedian($this->db);
        $comedian = $comedianModel->find($id);
        $user = null;
        if ($comedian && !empty($comedian['user_id'])) {
            $user = (new User($this->db))->find((int)$comedian['user_id']);
        }
        $this->render('comedians/form', compact('comedian', 'user'));
    }

    public function update(): void
    {
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $id = (int)($_GET['id'] ?? 0);
            $this->redirect(BASE_URL . '?controller=comedian&action=edit&id=' . $id);
        }

        $id = (int)($_GET['id'] ?? 0);
        $comedianModel = new Comedian($this->db);
        $existing = $comedianModel->find($id);
        if (!$existing) {
            flash('error', 'Comediante não encontrado.');
            $this->redirect(BASE_URL . '?controller=comedian&action=index');
        }

        $data = $this->validatedData();
        $userData = $this->validatedUserData();

        try {
            $this->db->beginTransaction();
            $data['attachment_path'] = $this->handleAttachmentUpload($existing['attachment_path'] ?? null);
            $data['user_id'] = $this->syncUserAccess($existing, $userData);
            $comedianModel->update($id, $data);
            $this->db->commit();
            flash('success', 'Comediante atualizado.');
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            flash('error', 'Não foi possível atualizar o comediante: ' . $e->getMessage());
            $this->redirect(BASE_URL . '?controller=comedian&action=edit&id=' . $id);
        }

        $this->redirect(BASE_URL . '?controller=comedian&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Comedian($this->db))->delete($id);
        flash('success', 'Comediante eliminado.');
        $this->redirect(BASE_URL . '?controller=comedian&action=index');
    }

    private function validatedData(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'stage_name' => trim($_POST['stage_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'price_bar' => (float)($_POST['price_bar'] ?? 0),
            'price_auditorium' => (float)($_POST['price_auditorium'] ?? 0),
            'bio' => trim($_POST['bio'] ?? ''),
            'attachment_path' => null,
            'notes' => trim($_POST['notes'] ?? ''),
            'user_id' => null,
        ];
    }

    private function validatedUserData(): array
    {
        return [
            'enabled' => !empty($_POST['user_enabled']),
            'name' => trim($_POST['user_name'] ?? ''),
            'email' => trim($_POST['user_email'] ?? ''),
            'password' => (string)($_POST['user_password'] ?? ''),
        ];
    }

    private function syncUserAccess(?array $existingComedian, array $userData): ?int
    {
        $currentUserId = !empty($existingComedian['user_id']) ? (int)$existingComedian['user_id'] : null;
        if (!$userData['enabled']) {
            return null;
        }

        if ($userData['name'] === '' || $userData['email'] === '') {
            throw new RuntimeException('Preencha nome e email para ativar o acesso ao site.');
        }

        $userModel = new User($this->db);
        if ($currentUserId) {
            $userModel->updateComedian($currentUserId, $userData);
            return $currentUserId;
        }

        if ($userData['password'] === '') {
            throw new RuntimeException('Defina uma palavra-passe para criar o acesso ao site.');
        }

        return $userModel->createComedian($userData);
    }

    private function handleAttachmentUpload(?string $existingPath): ?string
    {
        if (!empty($_POST['remove_attachment']) && $existingPath) {
            $fullPath = dirname(__DIR__, 2) . '/public/' . ltrim($existingPath, '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $existingPath = null;
        }

        if (empty($_FILES['attachment']) || (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existingPath;
        }

        if ((int)$_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no upload do ficheiro do comediante.');
        }

        $tmpPath = (string)$_FILES['attachment']['tmp_name'];
        $originalName = (string)$_FILES['attachment']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Tipo de ficheiro inválido. Use: pdf, doc, docx, txt, jpg, jpeg, png ou webp.');
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/comedian_files';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Não foi possível criar a pasta de uploads.');
        }

        $fileName = 'comedian_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $uploadDir . '/' . $fileName;
        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new RuntimeException('Não foi possível guardar o ficheiro enviado.');
        }

        if ($existingPath) {
            $oldFile = dirname(__DIR__, 2) . '/public/' . ltrim($existingPath, '/');
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        return 'uploads/comedian_files/' . $fileName;
    }
}
