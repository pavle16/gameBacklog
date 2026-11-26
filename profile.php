<?php
require_once __DIR__ . '/includes/init.php';

// ---------- 1. Find the user from ?u=username ----------
$username = query_str('u');

$profile = null;
if ($username !== '') {
    $stmt = $pdo->prepare('SELECT id, username, is_public, created_at FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $profile = $stmt->fetch();
}
if (!$profile) {
    not_found('Korisnik ne postoji.');
}

// ---------- 2. Privacy check: who may see this profile? ----------
$viewer  = current_user();                                       // null for guests
$isOwner = $viewer !== null && $viewer['id'] === $profile['id'];
$isAdmin = has_role('admin');
$canSee  = $profile['is_public'] || $isOwner || $isAdmin;

$pageTitle = $profile['username'];

if (!$canSee) {
    // Private profile seen by someone else: username + message only. No queries for library/reviews/stats.
    require __DIR__ . '/includes/header.php';
    ?>
    <h1><?= e($profile['username']) ?></h1>
    <div class="empty-state">
        <p>Ovaj profil je privatan.</p>
        <a href="<?= url('catalog.php') ?>">Nazad na katalog</a>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---------- 3. Stats + tab counts (same approach as library.php) ----------
$stmt = $pdo->prepare('SELECT status, COUNT(*) FROM entries WHERE user_id = ? GROUP BY status');
$stmt->execute([$profile['id']]);
$counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);    // e.g. ['backlog' => 2, 'completed' => 3]
$counts['all'] = array_sum($counts);

$stmt = $pdo->prepare('SELECT COALESCE(SUM(hours), 0) FROM entries WHERE user_id = ?');
$stmt->execute([$profile['id']]);
$totalHours = $stmt->fetchColumn();               // SUM of no rows is NULL -> COALESCE makes it 0

// ---------- 4. Library list (read-only) ----------
$tabs = status_tabs();
$tab  = query_str('status', 'all');
if (!isset($tabs[$tab])) {
    $tab = 'all';
}

// Columns are listed one by one: the private "notes" column is never loaded here
$sql = 'SELECT e.status, e.rating, e.hours, e.started_at, e.finished_at,
               g.id AS game_id, g.title, g.cover_path
          FROM entries e
          JOIN games g ON g.id = e.game_id
         WHERE e.user_id = ?';
$params = [$profile['id']];
if ($tab !== 'all') {
    $sql .= ' AND e.status = ?';
    $params[] = $tab;
}
$sql .= ' ORDER BY e.updated_at DESC, g.title ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

// ---------- 5. Reviews (same visibility rule as game.php) ----------
$stmt = $pdo->prepare('SELECT e.rating, e.review, e.created_at, g.id AS game_id, g.title
                         FROM entries e
                         JOIN games g ON g.id = e.game_id
                        WHERE e.user_id = ?
                          AND e.review IS NOT NULL
                          AND e.review_removed = 0
                        ORDER BY e.created_at DESC');
$stmt->execute([$profile['id']]);
$reviews = $stmt->fetchAll();

// Tab links keep ?u= and add ?status=
function profile_tab_url(string $username, string $tab): string {
    $query = ['u' => $username];
    if ($tab !== 'all') {
        $query['status'] = $tab;
    }
    return url('profile.php') . '?' . http_build_query($query);
}

require __DIR__ . '/includes/header.php';
?>
<?php if (!$profile['is_public'] && $isOwner): ?>
    <div class="flash flash-error">
        Vaš profil je privatan – samo vi ga vidite.
        <a href="<?= url('profile_edit.php') ?>">Promeni u podešavanjima</a>
    </div>
<?php elseif (!$profile['is_public'] && $isAdmin): ?>
    <div class="flash flash-error">Ovaj profil je privatan – vidite ga kao administrator.</div>
<?php endif; ?>

<header class="profile-head">
    <h1><?= e($profile['username']) ?></h1>
    <p class="meta">Član od <?= format_date($profile['created_at']) ?></p>
    <ul class="profile-stats">
        <li><strong><?= $counts['all'] ?></strong> <span class="meta">igara</span></li>
        <li><strong><?= $counts['completed'] ?? 0 ?></strong> <span class="meta">završeno</span></li>
        <li><strong><?= e($totalHours) ?></strong> <span class="meta">sati</span></li>
    </ul>
    <?php if ($isOwner): ?>
        <a class="btn btn-secondary btn-small" href="<?= url('library.php') ?>">Uredi biblioteku</a>
    <?php endif; ?>
</header>

<section class="profile-section">
    <h2>Biblioteka</h2>

    <ul class="tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <li>
                <a href="<?= e(profile_tab_url($profile['username'], $key)) ?>"
                   class="<?= $key === $tab ? 'active' : '' ?>">
                    <?= e($label) ?> <span class="count"><?= $counts[$key] ?? 0 ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($counts['all'] === 0): ?>
        <div class="empty-state"><p>Biblioteka je prazna.</p></div>
    <?php elseif (!$entries): ?>
        <div class="empty-state">
            <p>Nema igara sa statusom „<?= e($tabs[$tab]) ?>“.</p>
            <a href="<?= e(profile_tab_url($profile['username'], 'all')) ?>">Prikaži sve igre</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Igra</th>
                        <th>Status</th>
                        <th>Ocena</th>
                        <th>Sati</th>
                        <th>Početak</th>
                        <th>Završetak</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $en): ?>
                    <tr>
                        <td>
                            <a class="entry-title" href="<?= url('game.php?id=' . $en['game_id']) ?>">
                                <img class="entry-thumb" src="<?= e(cover_url($en['cover_path'])) ?>" alt="">
                                <?= e($en['title']) ?>
                            </a>
                        </td>
                        <td><span class="status status-<?= e($en['status']) ?>"><?= e(status_label($en['status'])) ?></span></td>
                        <td><?= $en['rating'] !== null ? '★ ' . $en['rating'] : '—' ?></td>
                        <td><?= (float) $en['hours'] > 0 ? e($en['hours']) : '—' ?></td>
                        <td><?= format_date($en['started_at']) ?></td>
                        <td><?= format_date($en['finished_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="reviews">
    <h2>Recenzije (<?= count($reviews) ?>)</h2>

    <?php if (!$reviews): ?>
        <p class="meta">Još nema recenzija.</p>
    <?php endif; ?>

    <?php foreach ($reviews as $r): ?>
        <article class="review">
            <header class="review-header">
                <a href="<?= url('game.php?id=' . $r['game_id']) ?>"><strong><?= e($r['title']) ?></strong></a>
                <?php if ($r['rating'] !== null): ?>
                    <span class="rating">★ <?= $r['rating'] ?>/10</span>
                <?php endif; ?>
                <time datetime="<?= e(date('Y-m-d', strtotime($r['created_at']))) ?>"><?= format_date($r['created_at']) ?></time>
            </header>
            <p><?= nl2br(e($r['review'])) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
