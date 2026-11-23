<?php
require_once __DIR__ . '/includes/init.php';

// ---------- 1. Validate id and load the game ----------
// query_int() returns 0 for "abc", "1.5", "-3", a missing id...
$id = query_int('id');

$game = null;
if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT g.id, g.title, g.release_year, g.description, g.cover_path,
               (SELECT ROUND(AVG(e.rating), 1)
                  FROM entries e WHERE e.game_id = g.id)  AS avg_rating,
               (SELECT COUNT(e.rating)
                  FROM entries e WHERE e.game_id = g.id)  AS rating_count,
               (SELECT COUNT(*)
                  FROM entries e WHERE e.game_id = g.id)  AS library_count
          FROM games g
         WHERE g.id = ?");
    $stmt->execute([$id]);
    $game = $stmt->fetch();
}

if (!$game) {
    not_found('Tražena igra ne postoji ili je uklonjena.');
}

// ---------- 2. Genres and platforms ----------
$stmt = $pdo->prepare('SELECT gr.name
                         FROM genres gr
                         JOIN game_genres gg ON gg.genre_id = gr.id
                        WHERE gg.game_id = ?
                        ORDER BY gr.name');
$stmt->execute([$game['id']]);
$genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT p.name
                         FROM platforms p
                         JOIN game_platforms gp ON gp.platform_id = p.id
                        WHERE gp.game_id = ?
                        ORDER BY p.name');
$stmt->execute([$game['id']]);
$platforms = $stmt->fetchAll(PDO::FETCH_COLUMN);

// ---------- 3. Visible reviews (not removed by a moderator), newest first ----------
$stmt = $pdo->prepare('SELECT e.rating, e.status, e.review, e.created_at,
                              u.username, u.is_public
                         FROM entries e
                         JOIN users u ON u.id = e.user_id
                        WHERE e.game_id = ?
                          AND e.review IS NOT NULL
                          AND e.review_removed = 0
                        ORDER BY e.created_at DESC');
$stmt->execute([$game['id']]);
$reviews = $stmt->fetchAll();

// ---------- 4. Does the logged-in user already have this game? ----------
$myEntry = null;
if ($user = current_user()) {
    $stmt = $pdo->prepare('SELECT id, status FROM entries WHERE user_id = ? AND game_id = ?');
    $stmt->execute([$user['id'], $game['id']]);
    $myEntry = $stmt->fetch() ?: null;
}

$pageTitle = $game['title'];
require __DIR__ . '/includes/header.php';
?>
<p><a href="<?= url('catalog.php') ?>">&laquo; Katalog</a></p>

<article class="game-detail">
    <img class="game-detail-cover" src="<?= e(cover_url($game['cover_path'])) ?>" alt="<?= e($game['title']) ?>">

    <div class="game-detail-info">
        <h1><?= e($game['title']) ?></h1>
        <p class="meta"><?= e($game['release_year'] ?? 'Godina nepoznata') ?></p>

        <p class="rating rating-big">
            <?php if ($game['rating_count'] > 0): ?>
                ★ <?= e($game['avg_rating']) ?>
                <span class="meta">(ocena: <?= $game['rating_count'] ?>)</span>
            <?php else: ?>
                <span class="meta">Još nema ocena</span>
            <?php endif; ?>
        </p>
        <p class="meta">U biblioteci kod <?= $game['library_count'] ?> korisnika</p>

        <?php if ($genres): ?>
            <h3>Žanrovi</h3>
            <ul class="tag-list">
                <?php foreach ($genres as $name): ?><li><?= e($name) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($platforms): ?>
            <h3>Platforme</h3>
            <ul class="tag-list">
                <?php foreach ($platforms as $name): ?><li><?= e($name) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <div class="library-action">
                <?php if ($myEntry): ?>
                    <span class="meta">U vašoj biblioteci:</span>
                    <span class="status status-<?= e($myEntry['status']) ?>"><?= e(status_label($myEntry['status'])) ?></span>
                    <a class="btn btn-secondary btn-small" href="<?= url('entry_form.php?id=' . $myEntry['id']) ?>">Izmeni</a>
                <?php else: ?>
                    <a class="btn" href="<?= url('entry_form.php?game_id=' . $game['id']) ?>">Dodaj u biblioteku</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</article>

<?php if ($game['description']): ?>
    <section class="game-description">
        <h2>Opis</h2>
        <p><?= nl2br(e($game['description'])) ?></p>
    </section>
<?php endif; ?>

<section class="reviews">
    <h2>Recenzije (<?= count($reviews) ?>)</h2>

    <?php if (!$reviews): ?>
        <p class="meta">Još nema recenzija za ovu igru.</p>
    <?php endif; ?>

    <?php foreach ($reviews as $r): ?>
        <article class="review">
            <header class="review-header">
                <?php if ($r['is_public']): ?>
                    <a href="<?= url('profile.php?u=' . urlencode($r['username'])) ?>"><strong><?= e($r['username']) ?></strong></a>
                <?php else: ?>
                    <strong><?= e($r['username']) ?></strong>
                <?php endif; ?>
                <?php if ($r['rating'] !== null): ?>
                    <span class="rating">★ <?= $r['rating'] ?>/10</span>
                <?php endif; ?>
                <span class="status status-<?= e($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                <time datetime="<?= e(date('Y-m-d', strtotime($r['created_at']))) ?>">
                    <?= e(date('d.m.Y', strtotime($r['created_at']))) ?>
                </time>
            </header>
            <p><?= nl2br(e($r['review'])) ?></p>
        </article>
    <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
