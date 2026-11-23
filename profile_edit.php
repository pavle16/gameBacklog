<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();

// One error list per form, so each form shows only its own errors
$errors = ['profile' => [], 'privacy' => [], 'password' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    // ---------- 1. Basic info: username + email ----------
    if ($action === 'profile') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors['profile'][] = 'Korisničko ime: 3–30 karaktera (slova, brojevi, _).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['profile'][] = 'Unesite ispravnu e-mail adresu.';
        }

        // Uniqueness check – ignore the current user's own row
        if (!$errors['profile']) {
            $stmt = $pdo->prepare('SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id <> ?');
            $stmt->execute([$username, $email, $user['id']]);
            foreach ($stmt->fetchAll() as $row) {
                if (strcasecmp($row['username'], $username) === 0) $errors['profile'][] = 'Korisničko ime je zauzeto.';
                if (strcasecmp($row['email'], $email) === 0)       $errors['profile'][] = 'E-mail je već registrovan.';
            }
        }

        if (!$errors['profile']) {
            $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
            $stmt->execute([$username, $email, $user['id']]);
            flash('success', 'Podaci su sačuvani.');
            redirect('profile_edit.php');
        }
    }

    // ---------- 2. Privacy ----------
    if ($action === 'privacy') {
        $isPublic = isset($_POST['is_public']) ? 1 : 0;   // unchecked checkbox is not sent at all
        $stmt = $pdo->prepare('UPDATE users SET is_public = ? WHERE id = ?');
        $stmt->execute([$isPublic, $user['id']]);
        flash('success', $isPublic ? 'Profil je sada javan.' : 'Profil je sada privatan.');
        redirect('profile_edit.php');
    }

    // ---------- 3. Change password ----------
    if ($action === 'password') {
        $current   = $_POST['current_password'] ?? '';
        $new       = $_POST['new_password'] ?? '';
        $new2      = $_POST['new_password_confirm'] ?? '';

        // password_hash is not in current_user(), so load it separately
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors['password'][] = 'Trenutna lozinka nije ispravna.';
        }
        if (strlen($new) < 8) {
            $errors['password'][] = 'Nova lozinka mora imati najmanje 8 karaktera.';
        }
        if ($new !== $new2) {
            $errors['password'][] = 'Nove lozinke se ne poklapaju.';
        }

        if (!$errors['password']) {
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            session_regenerate_id(true);   // new session id after a credential change
            flash('success', 'Lozinka je promenjena.');
            redirect('profile_edit.php');
        }
    }
}

// Form values: after a failed submit show what was typed, otherwise the saved values
$formUsername = $_POST['username'] ?? $user['username'];
$formEmail    = $_POST['email'] ?? $user['email'];

$roleNames = ['user' => 'Korisnik', 'moderator' => 'Moderator', 'admin' => 'Administrator'];

$pageTitle = 'Moj profil';
require __DIR__ . '/includes/header.php';
?>
<h1>Moj profil</h1>

<section class="profile-section">
    <h2>Osnovni podaci</h2>
    <?php if ($errors['profile']): ?>
        <div class="flash flash-error">
            <ul><?php foreach ($errors['profile'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="profile">
        <label>Korisničko ime
            <input name="username" value="<?= e($formUsername) ?>" required maxlength="30">
        </label>
        <label>E-mail
            <input type="email" name="email" value="<?= e($formEmail) ?>" required>
        </label>
        <button type="submit">Sačuvaj</button>
    </form>
</section>

<section class="profile-section">
    <h2>Privatnost</h2>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="privacy">
        <label class="checkbox-label">
            <input type="checkbox" name="is_public" value="1" <?= $user['is_public'] ? 'checked' : '' ?>>
            Javni profil
        </label>
        <button type="submit">Sačuvaj</button>
    </form>
</section>

<section class="profile-section">
    <h2>Promena lozinke</h2>
    <?php if ($errors['password']): ?>
        <div class="flash flash-error">
            <ul><?php foreach ($errors['password'] as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">
        <label>Trenutna lozinka
            <input type="password" name="current_password" required>
        </label>
        <label>Nova lozinka
            <input type="password" name="new_password" required minlength="8">
        </label>
        <label>Ponovi novu lozinku
            <input type="password" name="new_password_confirm" required>
        </label>
        <button type="submit">Promeni lozinku</button>
    </form>
</section>

<section class="profile-section">
    <h2>Informacije o nalogu</h2>
    <dl class="info-list">
        <dt>Uloga</dt>
        <dd><?= e($roleNames[$user['role']] ?? $user['role']) ?></dd>
        <dt>Registrovan</dt>
        <dd><?= e(date('d.m.Y', strtotime($user['created_at']))) ?></dd>
    </dl>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
