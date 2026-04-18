<?php

class PartnerController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $partners = (new Partner($this->db))->all();
        $this->render('partners/index', compact('partners'));
    }

    public function create(): void
    {
        requireAdmin();
        $this->render('partners/form', ['partner' => null]);
    }

    public function store(): void
    {
        requireAdmin();
        (new Partner($this->db))->create($this->validatedData());
        flash('success', 'Parceiro criado com sucesso.');
        $this->redirect(BASE_URL . '?controller=partner&action=index');
    }

    public function edit(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $partner = (new Partner($this->db))->find($id);
        $this->render('partners/form', compact('partner'));
    }

    public function update(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Partner($this->db))->update($id, $this->validatedData());
        flash('success', 'Parceiro atualizado com sucesso.');
        $this->redirect(BASE_URL . '?controller=partner&action=index');
    }

    public function delete(): void
    {
        requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        (new Partner($this->db))->delete($id);
        flash('success', 'Parceiro eliminado com sucesso.');
        $this->redirect(BASE_URL . '?controller=partner&action=index');
    }

    private function validatedData(): array
    {
        return [
            'company_name' => trim((string)($_POST['company_name'] ?? '')),
            'logo_url' => trim((string)($_POST['logo_url'] ?? '')),
            'company_url' => trim((string)($_POST['company_url'] ?? '')),
            'partnership_start_date' => trim((string)($_POST['partnership_start_date'] ?? '')),
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
    }
}
