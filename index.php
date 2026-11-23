<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Početna';
require __DIR__ . '/includes/header.php';
?>
<h1>GameBacklog</h1>
<?php if ($u = current_user()): ?>
    <p>Prijavljeni ste kao <strong><?= e($u['username']) ?></strong> (uloga: <?= e($u['role']) ?>).</p>
<?php else: ?>
    <p>Pratite igre koje igrate, završavate i planirate.</p>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
