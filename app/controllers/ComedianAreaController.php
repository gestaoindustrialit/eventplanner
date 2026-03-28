<?php

class ComedianAreaController extends BaseController
{
    public function index(): void
    {
        requireLogin();

        $user = currentUser();
        if (($user['role'] ?? '') === 'admin') {
            $this->redirect(BASE_URL);
        }

        $comedian = (new Comedian($this->db))->findByUserId((int)$user['id']);

        if (!$comedian) {
            flash('error', 'Não existe comediante associado ao seu utilizador.');
            $this->render('comedian_area/index', ['events' => []]);
            return;
        }

        $events = (new Event($this->db))->forComedian((int)$comedian['id']);
        $this->render('comedian_area/index', compact('events'));
    }
}
