<?php

class DashboardController extends BaseController
{
    public function index(): void
    {
        requireLogin();
        if (!isAdmin()) {
            $this->redirect(BASE_URL . '?controller=comedianarea&action=index');
        }

        $eventModel = new Event($this->db);

        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $events = $eventModel->all($dateFrom, $dateTo);
        $stats = [
            'totalEvents' => $eventModel->totalCount(),
            'upcomingEvents' => $eventModel->upcomingCount(),
            'totalCachet' => $eventModel->totalCachet(),
        ];

        $this->render('dashboard/index', compact('events', 'stats', 'dateFrom', 'dateTo'));
    }
}
