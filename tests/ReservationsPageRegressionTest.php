<?php

require_once __DIR__ . '/../app/models/Reservation.php';

$db = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$db->exec(
    'CREATE TABLE events (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        date TEXT NOT NULL,
        time TEXT NOT NULL,
        reservations_open INTEGER NOT NULL DEFAULT 1
    );
    CREATE TABLE event_reservations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL,
        customer_name TEXT NOT NULL,
        customer_email TEXT NOT NULL,
        customer_phone TEXT DEFAULT NULL,
        tickets INTEGER NOT NULL DEFAULT 1,
        notes TEXT DEFAULT NULL,
        status TEXT NOT NULL DEFAULT \'new\',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );'
);

$today = date('Y-m-d');
$past = date('Y-m-d', strtotime('-1 day'));
$insertEvent = $db->prepare(
    'INSERT INTO events (title, date, time, reservations_open)
     VALUES (:title, :date, :time, :reservations_open)'
);
$insertEvent->execute(['title' => 'Evento futuro', 'date' => $today, 'time' => '21:00', 'reservations_open' => 1]);
$futureEventId = (int)$db->lastInsertId();
$insertEvent->execute(['title' => 'Evento passado', 'date' => $past, 'time' => '20:00', 'reservations_open' => 0]);
$pastEventId = (int)$db->lastInsertId();

$insertReservation = $db->prepare(
    'INSERT INTO event_reservations (event_id, customer_name, customer_email)
     VALUES (:event_id, :customer_name, :customer_email)'
);
$insertReservation->execute(['event_id' => $futureEventId, 'customer_name' => 'Cliente A', 'customer_email' => 'a@example.com']);
$insertReservation->execute(['event_id' => $futureEventId, 'customer_name' => 'Cliente B', 'customer_email' => 'b@example.com']);
$insertReservation->execute(['event_id' => $pastEventId, 'customer_name' => 'Cliente C', 'customer_email' => 'c@example.com']);

$reservations = new Reservation($db);

if ($reservations->eventOverviewCount('upcoming') !== 1) {
    throw new RuntimeException('The reservations page cannot count upcoming events.');
}
if ($reservations->eventOverviewCount('open') !== 1) {
    throw new RuntimeException('The reservations page cannot count events with open reservations.');
}

$events = $reservations->reservationEvents();
if (count($events) !== 2
    || (int)$events[0]['id'] !== $futureEventId
    || (int)$events[1]['id'] !== $pastEventId) {
    throw new RuntimeException('The reservations page event filter is incomplete or incorrectly ordered.');
}

echo "Reservations page regression test passed.\n";
