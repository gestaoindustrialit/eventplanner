<?php

require_once __DIR__ . '/../app/models/NewsletterSubscription.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$newsletter = new NewsletterSubscription($db);

$newsletter->subscribeFromReservation([
    'email' => 'cliente@example.com',
    'name' => 'Cliente Original',
    'consent_text' => 'Consentimento original',
    'source' => 'reserva: Evento A',
    'segment' => 'Braga',
]);

$subscriptionId = (int)$db->lastInsertId();
$newsletter->deactivate($subscriptionId);
$newsletter->subscribeFromReservation([
    'email' => 'cliente@example.com',
    'name' => '',
    'consent_text' => 'Consentimento atualizado',
    'source' => 'reserva: Evento B',
    'segment' => 'Porto',
]);

$subscriptions = $newsletter->all();
if (count($subscriptions) !== 1) {
    throw new RuntimeException('Importing the same reservation contact must not create a duplicate subscription.');
}

$subscription = $subscriptions[0];
if ($subscription['name'] !== 'Cliente Original'
    || (int)$subscription['gdpr_consent'] !== 1
    || $subscription['consent_text'] !== 'Consentimento atualizado'
    || $subscription['source'] !== 'reserva: Evento B'
    || $subscription['segment'] !== 'Porto'
    || $subscription['status'] !== 'active'
    || $subscription['unsubscribed_at'] !== null) {
    throw new RuntimeException('An existing subscription was not correctly reactivated and updated.');
}

$source = (string)file_get_contents(__DIR__ . '/../app/models/NewsletterSubscription.php');
if (stripos($source, 'ON CONFLICT(email)') !== false) {
    throw new RuntimeException('Reservation imports must remain compatible with SQLite versions older than 3.24.');
}

echo "Newsletter reservation import test passed.\n";
