<?php

// Simulate a hosting environment with the PECL event extension enabled. That
// extension registers this global class before the application is loaded.
if (!class_exists('Event', false)) {
    class Event
    {
    }
}

require_once __DIR__ . '/../app/models/Event.php';
require_once __DIR__ . '/../app/controllers/BaseController.php';
require_once __DIR__ . '/../app/controllers/EventController.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(class_exists('EventModel', false), 'The application event model must load under its collision-safe name.');
$assert(class_exists('EventController', false), 'The event controller must load after the event model.');

fwrite(STDOUT, "Event model name-collision regression test passed.\n");
