<?php
require_once __DIR__ . '/includes/init.php';
require_login();

// Deleting changes data, so only POST is allowed (a GET link could be triggered by an <img> on another site)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metoda nije dozvoljena.');
}
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);

// "AND user_id = ?" – users can only delete their own entries
$stmt = $pdo->prepare('DELETE FROM entries WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user()['id']]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Igra je uklonjena iz biblioteke.');
} else {
    flash('error', 'Unos nije pronađen.');
}
redirect('library.php');
