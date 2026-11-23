<?php
require_once __DIR__ . '/includes/init.php';
if (is_logged_in()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password_confirm'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Korisničko ime: 3–30 karaktera (slova, brojevi, _).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Unesite ispravnu e-mail adresu.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Lozinka mora imati najmanje 8 karaktera.';
    }
    if ($password !== $password2) {
        $errors[] = 'Lozinke se ne poklapaju.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        foreach ($stmt->fetchAll() as $row) {
            if (strcasecmp($row['username'], $username) === 0) $errors[] = 'Korisničko ime je zauzeto.';
            if (strcasecmp($row['email'], $email) === 0)       $errors[] = 'E-mail je već registrovan.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        login_user((int) $pdo->lastInsertId());
        flash('success', 'Dobrodošli, ' . $username . '!');
        redirect('index.php');
    }
}

$pageTitle = 'Registracija';
require __DIR__ . '/includes/header.php';
?>
<h1>Registracija</h1>

<?php if ($errors): ?>
    <div class="flash flash-error">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="form">
    <?= csrf_field() ?>
    <label>Korisničko ime
        <input name="username" value="<?= old('username') ?>" required maxlength="30">
    </label>
    <label>E-mail
        <input type="email" name="email" value="<?= old('email') ?>" required>
    </label>
    <label>Lozinka
        <input type="password" name="password" required minlength="8">
    </label>
    <label>Ponovi lozinku
        <input type="password" name="password_confirm" required>
    </label>
    <button type="submit">Registruj se</button>
</form>
<p>Već imate nalog? <a href="<?= url('login.php') ?>">Prijavite se</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>
