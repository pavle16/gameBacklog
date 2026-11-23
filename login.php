<?php
require_once __DIR__ . '/includes/init.php';
if (is_logged_in()) redirect('index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$login, $login]);
    $row = $stmt->fetch();

    if ($row && password_verify($password, $row['password_hash'])) {
        login_user((int) $row['id']);
        flash('success', 'Uspešno ste se prijavili.');
        redirect('index.php');
    }
    // Same message for wrong username or wrong password – doesn't reveal which accounts exist
    $error = 'Pogrešno korisničko ime ili lozinka.';
}

$pageTitle = 'Prijava';
require __DIR__ . '/includes/header.php';
?>
<h1>Prijava</h1>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <label>Korisničko ime ili e-mail
        <input name="login" value="<?= old('login') ?>" required>
    </label>
    <label>Lozinka
        <input type="password" name="password" required>
    </label>
    <button type="submit">Prijavi se</button>
</form>
<p>Nemate nalog? <a href="<?= url('register.php') ?>">Registrujte se</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
