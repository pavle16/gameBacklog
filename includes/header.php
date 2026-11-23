<?php $user = current_user(); ?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'GameBacklog') ?></title>
    <link rel="stylesheet" href="<?= url('public/css/style.css') ?>">
</head>
<body>
<header class="site-header">
    <a class="logo" href="<?= url('index.php') ?>">GameBacklog</a>
    <nav>
        <a href="<?= url('catalog.php') ?>">Katalog</a>
        <?php if ($user): ?>
            <a href="<?= url('library.php') ?>">Moja biblioteka</a>
            <?php if (has_role('moderator', 'admin')): ?>
                <a href="<?= url('admin/moderation.php') ?>">Moderacija</a>
            <?php endif; ?>
            <?php if (has_role('admin')): ?>
                <a href="<?= url('admin/users.php') ?>">Administracija</a>
            <?php endif; ?>
            <a href="<?= url('profile_edit.php') ?>"><?= e($user['username']) ?></a>
            <form method="post" action="<?= url('logout.php') ?>" class="inline">
                <?= csrf_field() ?>
                <button type="submit" class="link-btn">Odjava</button>
            </form>
        <?php else: ?>
            <a href="<?= url('login.php') ?>">Prijava</a>
            <a href="<?= url('register.php') ?>">Registracija</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php foreach (get_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
