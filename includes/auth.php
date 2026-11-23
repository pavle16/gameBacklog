<?php
// Loads the logged-in user from the DB once per request.
// Reading from the DB (not caching role in session) means role changes apply immediately.
function current_user(): ?array {
    static $user = null, $loaded = false;
    if (!$loaded) {
        $loaded = true;
        if (!empty($_SESSION['user_id'])) {
            global $pdo;
            $stmt = $pdo->prepare('SELECT id, username, email, role, is_public, created_at FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if (!$user) unset($_SESSION['user_id']);   // user was deleted
        }
    }
    return $user;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function has_role(string ...$roles): bool {
    $user = current_user();
    return $user !== null && in_array($user['role'], $roles, true);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('error', 'Morate biti prijavljeni.');
        redirect('login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!has_role(...$roles)) {
        http_response_code(403);
        exit('403 – Nemate pristup ovoj stranici.');
    }
}

function login_user(int $id): void {
    session_regenerate_id(true);   // prevents session fixation
    $_SESSION['user_id'] = $id;
}

function logout_user(): void {
    $_SESSION = [];
    session_destroy();
}
