<?php

// Embedded in both exported PHP entry points; no dependency on the admin session.
function reservation_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('cdr_reservations');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        if (!session_start(['use_strict_mode' => true])) {
            throw new RuntimeException('Reservation session unavailable.');
        }
    }
    header('Cache-Control: private, no-store, max-age=0');
}

function reservation_form_token(): string
{
    reservation_session();
    if (empty($_SESSION['reservation_token']) || (int)($_SESSION['reservation_token_at'] ?? 0) < time() - 7200) {
        $_SESSION['reservation_token'] = bin2hex(random_bytes(32));
        $_SESSION['reservation_token_at'] = time();
    }
    $token = (string)$_SESSION['reservation_token'];
    session_write_close();
    return $token;
}

function reservation_post_string(array $post, string $key): string
{
    return isset($post[$key]) && is_string($post[$key]) ? trim($post[$key]) : '';
}

function reservation_consume_token(array $post): bool
{
    reservation_session();
    $token = reservation_post_string($post, 'reservation_token');
    $valid = $token !== '' && isset($_SESSION['reservation_token'])
        && hash_equals((string)$_SESSION['reservation_token'], $token)
        && (int)($_SESSION['reservation_token_at'] ?? 0) >= time() - 7200
        && (!isset($post['reservation_website']) || is_string($post['reservation_website']))
        && reservation_post_string($post, 'reservation_website') === '';
    // Consume while holding the session lock to prevent duplicate/replayed POSTs.
    if ($valid) {
        unset($_SESSION['reservation_token'], $_SESSION['reservation_token_at']);
    }
    session_write_close();
    return $valid;
}

function reservation_valid_input(array $post): bool
{
    foreach (['customer_name', 'customer_email', 'customer_phone', 'tickets', 'notes', 'event_id'] as $key) {
        if (isset($post[$key]) && !is_string($post[$key])) {
            return false;
        }
    }
    $name = reservation_post_string($post, 'customer_name');
    $email = reservation_post_string($post, 'customer_email');
    $phone = reservation_post_string($post, 'customer_phone');
    $tickets = reservation_post_string($post, 'tickets');
    return strlen($name) >= 2 && strlen($name) <= 200
        && preg_match("/^[\p{L}\p{M} .\x{2019}'-]+$/u", $name) === 1
        && preg_match('/\p{L}/u', $name) === 1
        && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && ($phone === '' || (strlen($phone) <= 40
            && preg_match('/^[+0-9 ().-]+$/D', $phone) === 1
            && strlen(preg_replace('/\D/', '', $phone)) >= 6))
        && preg_match('/^[1-9][0-9]*$/D', $tickets) === 1 && (int)$tickets <= 20
        && strlen(reservation_post_string($post, 'notes')) <= 2000
        && preg_match('/^[1-9][0-9]*$/D', reservation_post_string($post, 'event_id')) === 1;
}

function reservation_rate_allowed(PDO $db, string $address, string $email): bool
{
    // Keep limits independent of reservations: deleting spam must not reset them.
    // REMOTE_ADDR is supplied by the server; do not trust client forwarding headers.
    $db->exec('CREATE TABLE IF NOT EXISTS reservation_attempts (ip_hash TEXT NOT NULL, email_hash TEXT NOT NULL, attempted_at INTEGER NOT NULL)');
    $db->exec('CREATE INDEX IF NOT EXISTS reservation_attempts_ip ON reservation_attempts (ip_hash, attempted_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS reservation_attempts_email ON reservation_attempts (email_hash, attempted_at)');
    $now = time();
    $ipHash = hash('sha256', $address);
    $emailHash = hash('sha256', strtolower(trim($email)));
    $db->exec('BEGIN IMMEDIATE');
    try {
        $db->prepare('DELETE FROM reservation_attempts WHERE attempted_at < :cutoff')->execute(['cutoff' => $now - 3600]);
        $count = $db->prepare('SELECT COUNT(*) FROM reservation_attempts WHERE (ip_hash = :ip AND attempted_at >= :recent) OR email_hash = :email');
        $count->execute(['ip' => $ipHash, 'recent' => $now - 600, 'email' => $emailHash]);
        $allowed = (int)$count->fetchColumn() < 5;
        if ($allowed) {
            $db->prepare('INSERT INTO reservation_attempts (ip_hash, email_hash, attempted_at) VALUES (:ip, :email, :now)')
                ->execute(['ip' => $ipHash, 'email' => $emailHash, 'now' => $now]);
        }
        $db->exec('COMMIT');
        return $allowed;
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        throw $e;
    }
}
