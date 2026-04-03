<?php

class NewsletterController extends BaseController
{
    public function index(): void
    {
        requireAdmin();
        $subscriptions = (new NewsletterSubscription($this->db))->all();
        $this->render('newsletter/index', compact('subscriptions'));
    }

    public function unsubscribe(): void
    {
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            (new NewsletterSubscription($this->db))->deactivate($id);
            flash('success', 'Subscrição marcada como cancelada.');
        }

        $this->redirect(BASE_URL . '?controller=newsletter&action=index');
    }
}
