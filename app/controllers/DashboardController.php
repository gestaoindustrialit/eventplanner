<?php

class DashboardController extends BaseController
{
    public function index(): void
    {
        requireLogin();
        if (!can('dashboard')) {
            $destinations = [
                'event' => '?controller=event&action=index', 'comedian' => '?controller=comedian&action=index',
                'client' => '?controller=client&action=index', 'crm' => '?controller=crm&action=index',
                'checklist' => '?controller=checklist&action=index', 'reservation' => '?controller=reservation&action=index',
                'publicpage' => '?controller=publicpage&action=index', 'blogpost' => '?controller=blogpost&action=index',
                'partner' => '?controller=partner&action=index', 'publicsite' => '?controller=publicsite&action=index',
                'newsletter' => '?controller=newsletter&action=index', 'presscontact' => '?controller=presscontact&action=index',
            ];
            foreach ($destinations as $permission => $url) {
                if (can($permission)) {
                    $this->redirect(BASE_URL . $url);
                }
            }
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
