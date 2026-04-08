<?php

class PressContactController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $contacts = (new PressContact($this->db))->all();
        $this->render('press_contacts/index', compact('contacts'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('press_contacts/form', ['contact' => null]);
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
}
