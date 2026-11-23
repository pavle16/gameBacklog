<?php
// Escape output (XSS protection)
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Build a URL inside the project: url('login.php') -> /gameBacklog/login.php
function url(string $path = ''): string {
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): never {
    header('Location: ' . url($path));
    exit;
}

// ---------- CSRF protection ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void {
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Nevažeći CSRF token.');
    }
}

// ---------- Flash messages (shown once, after a redirect) ----------
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function get_flashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// Refill a form field after a failed submit
function old(string $key): string {
    return e($_POST[$key] ?? '');
}

// ---------- Games ----------

// cover_path stores only the file name (e.g. "witcher3.jpg"); NULL means no cover
function cover_url(?string $coverPath): string {
    return $coverPath
        ? url('public/uploads/covers/' . $coverPath)
        : url('public/img/placeholder.svg');
}

// Serbian label for an entries.status value
function status_label(string $status): string {
    $labels = [
        'backlog'   => 'Na listi',
        'playing'   => 'Igram',
        'completed' => 'Završeno',
        'dropped'   => 'Odustao',
    ];
    return $labels[$status] ?? $status;
}
