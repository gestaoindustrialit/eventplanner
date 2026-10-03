<?php

class UserController extends BaseController
{
    public function index(): void { requireAdmin(); $users=(new User($this->db))->all(); $this->render('users/index', compact('users')); }
    public function create(): void { requireAdmin(); $this->renderForm(null); }
    public function edit(): void { requireAdmin(); $this->renderForm((new User($this->db))->find((int)($_GET['id']??0))); }
    public function store(): void { requireAdmin(); $data=$this->data(true); (new User($this->db))->createProfile($data); flash('success','Perfil criado com sucesso.'); $this->redirect(BASE_URL.'?controller=user'); }
    public function update(): void { requireAdmin(); $id=(int)($_GET['id']??0); (new User($this->db))->updateProfile($id,$this->data(false)); flash('success','Perfil atualizado.'); $this->redirect(BASE_URL.'?controller=user'); }
    public function delete(): void { requireAdmin(); (new User($this->db))->deleteProfile((int)($_GET['id']??0)); flash('success','Perfil eliminado.'); $this->redirect(BASE_URL.'?controller=user'); }
    private function data(bool $passwordRequired): array
    {
        $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $password=(string)($_POST['password']??'');
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || ($passwordRequired && $password==='')) throw new RuntimeException('Preencha nome, email válido e palavra-passe.');
        $allowed=array_keys(availablePermissions());
        return ['name'=>$name,'email'=>$email,'password'=>$password,'profile_type'=>in_array($_POST['profile_type']??'', ['comedian','partner','editor'],true)?$_POST['profile_type']:'editor','permissions'=>array_values(array_intersect($allowed,$_POST['permissions']??[])), 'admission_event_ids'=>(array)($_POST['admission_event_ids']??[]), 'admission_groups'=>(array)($_POST['admission_groups']??[])];
    }

    private function renderForm(?array $profile): void
    {
        $events=(new EventModel($this->db))->all();
        $groups=[];
        foreach ($events as $event) {
            $group=trim((string)($event['admission_group']??''));
            if ($group!=='') $groups[$group]=$group;
        }
        natcasesort($groups);
        $admissionAccess=$profile ? (new User($this->db))->admissionAccess((int)$profile['id']) : ['event_ids'=>[], 'groups'=>[]];
        $this->render('users/form', compact('profile','events','groups','admissionAccess'));
    }
}
