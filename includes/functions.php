<?php
// Escape output (XSS protection)
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// ---------- Reading the URL (?key=value) safely ----------
// A visitor can send ?q[]=x, which PHP turns into an array. These helpers always return
// the expected type, so pages never crash (TypeError) or misread the value.

// Trimmed string; missing or array -> $default
function query_str(string $key, string $default = ''): string {
    $value = $_GET[$key] ?? null;
    return is_string($value) ? trim($value) : $default;
}

// Whole number >= 1 (ids, page numbers); missing, array, "abc", "1.5", "-3", "0" -> $default
function query_int(string $key, int $default = 0): int {
    $value = filter_var(query_str($key), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $value === false ? $default : $value;
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

// The 4 values of the entries.status ENUM, in display order
const ENTRY_STATUSES = ['backlog', 'playing', 'completed', 'dropped'];

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

// ---------- Small helpers ----------

// Empty form value -> NULL in the database (instead of saving '')
function empty_to_null(string $value): ?string {
    return $value === '' ? null : $value;
}

// "2026-03-15" or "2026-03-15 18:20:00" -> "15.03.2026"; NULL -> "—"
function format_date(?string $date): string {
    return $date ? date('d.m.Y', strtotime($date)) : '—';
}

// Show a friendly 404 page and stop
function not_found(string $message = 'Tražena stranica ne postoji.'): never {
    http_response_code(404);
    $pageTitle = 'Nije pronađeno';
    require __DIR__ . '/header.php';
    echo '<h1>Nije pronađeno</h1>';
    echo '<div class="empty-state"><p>' . e($message) . '</p>';
    echo '<a href="' . url('catalog.php') . '">Nazad na katalog</a></div>';
    require __DIR__ . '/footer.php';
    exit;
}
