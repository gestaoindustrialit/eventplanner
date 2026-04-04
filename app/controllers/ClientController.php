<?php

class ClientController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $clients = (new Client($this->db))->all();
        $this->render('clients/index', compact('clients'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('clients/form', ['client' => null]);
    }

    public function store(): void
    {
        requireAdmin();
        (new Client($this->db))->create($this->validatedData());
        flash('success', 'Cliente criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=client&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $client = (new Client($this->db))->find($id);
        $this->render('clients/form', compact('client'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Client($this->db))->update($id, $this->validatedData());
        flash('success', 'Cliente atualizado.');
        $this->redirect(BASE_URL . '?controller=client&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $clientModel = new Client($this->db);

        if ($clientModel->hasEvents($id)) {
            flash('error', 'Não foi possível eliminar o cliente porque existem eventos associados.');
            $this->redirect(BASE_URL . '?controller=client&action=index');
            return;
        }

        $clientModel->delete($id);
        flash('success', 'Cliente eliminado com sucesso.');
        $this->redirect(BASE_URL . '?controller=client&action=index');
    }

    private function validatedData(): array
    {
        return [
            'name' => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }
}
