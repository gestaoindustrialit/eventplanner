<?php

require_once __DIR__ . '/../app/config/database.php';

$databasePath = tempnam(sys_get_temp_dir(), 'eventplanner-');
if ($databasePath === false) {
    fwrite(STDERR, "FAIL: Could not create the temporary database.\n");
    exit(1);
}

$previousPath = getenv('SQLITE_PATH');
putenv('SQLITE_PATH=' . $databasePath);

try {
    $db = (new Database())->getConnection();
    $indexes = $db->query(
        "SELECT sql FROM sqlite_master
         WHERE type = 'index'
           AND name IN ('idx_user_admission_event', 'idx_user_admission_group')"
    )->fetchAll(PDO::FETCH_COLUMN);

    if (count($indexes) !== 2) {
        throw new RuntimeException('The admission access indexes were not created.');
    }

    foreach ($indexes as $indexSql) {
        if (stripos((string)$indexSql, ' WHERE ') !== false) {
            throw new RuntimeException('Admission access indexes must not use unsupported partial-index syntax.');
        }
    }

    // A regular SQLite UNIQUE index still allows multiple NULL values, so
    // event-only and group-only grants can coexist for the same user.
    $db->exec('PRAGMA foreign_keys = OFF');
    $insert = $db->prepare(
        'INSERT INTO user_admission_access (user_id, event_id, event_group)
         VALUES (:user_id, :event_id, :event_group)'
    );
    $insert->execute(['user_id' => 1, 'event_id' => 10, 'event_group' => null]);
    $insert->execute(['user_id' => 1, 'event_id' => 11, 'event_group' => null]);
    $insert->execute(['user_id' => 1, 'event_id' => null, 'event_group' => 'north']);
    $insert->execute(['user_id' => 1, 'event_id' => null, 'event_group' => 'south']);

    try {
        $insert->execute(['user_id' => 1, 'event_id' => 10, 'event_group' => null]);
        throw new RuntimeException('Duplicate event grants must remain prohibited.');
    } catch (PDOException $e) {
        // Expected: the non-partial unique index preserves grant uniqueness.
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    if ($previousPath === false) {
        putenv('SQLITE_PATH');
    } else {
        putenv('SQLITE_PATH=' . $previousPath);
    }
    @unlink($databasePath);
}

fwrite(STDOUT, "Database compatibility regression test passed.\n");
