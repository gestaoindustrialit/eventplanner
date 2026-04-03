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
        $userModel = new User($this->db);
        $users = $userModel->allComedianUsers();
        $this->render('comedians/form', ['comedian' => null, 'users' => $users]);
    }

    public function store(): void
    {
        requireAdmin();
        $data = $this->validatedData();
        $comedianModel = new Comedian($this->db);
        $comedianModel->create($data);
        flash('success', 'Comediante criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=comedian&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $comedianModel = new Comedian($this->db);
        $comedian = $comedianModel->find($id);
        $users = (new User($this->db))->allComedianUsers();
        $this->render('comedians/form', compact('comedian', 'users'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $data = $this->validatedData();
        (new Comedian($this->db))->update($id, $data);
        flash('success', 'Comediante atualizado.');
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
            'instagram' => trim($_POST['instagram'] ?? ''),
            'price_bar' => (float)($_POST['price_bar'] ?? 0),
            'price_auditorium' => (float)($_POST['price_auditorium'] ?? 0),
            'notes' => trim($_POST['notes'] ?? ''),
            'user_id' => !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null,
        ];
    }
}
