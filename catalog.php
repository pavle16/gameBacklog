<?php
require_once __DIR__ . '/includes/init.php';

$perPage = 12;

// ---------- 1. Read filters from the URL (GET) ----------
$q        = trim($_GET['q'] ?? '');
$genreId  = (int) ($_GET['genre'] ?? 0);      // 0 = no filter
$platform = (int) ($_GET['platform'] ?? 0);
$year     = (int) ($_GET['year'] ?? 0);
$sort     = $_GET['sort'] ?? 'title';
$page     = max(1, (int) ($_GET['page'] ?? 1));

// Allowed sort options. User input only picks a key – the SQL text comes from this array.
$sortOptions = [
    'title'  => ['label' => 'Naziv A–Z',          'sql' => 'g.title ASC'],
    'newest' => ['label' => 'Najnovije',          'sql' => 'g.release_year DESC, g.title ASC'],
    'rating' => ['label' => 'Najbolje ocenjene',  'sql' => 'avg_rating DESC, rating_count DESC, g.title ASC'],
];
if (!isset($sortOptions[$sort])) {
    $sort = 'title';
}

// ---------- 2. Dropdown options from the DB ----------
$genres    = $pdo->query('SELECT id, name FROM genres ORDER BY name')->fetchAll();
$platforms = $pdo->query('SELECT id, name FROM platforms ORDER BY name')->fetchAll();
$years     = $pdo->query('SELECT DISTINCT release_year FROM games
                          WHERE release_year IS NOT NULL
                          ORDER BY release_year DESC')->fetchAll(PDO::FETCH_COLUMN);

// ---------- 3. Build the WHERE part from active filters ----------
$where  = [];
$params = [];

if ($q !== '') {
    $where[]  = 'g.title LIKE ?';
    $params[] = '%' . addcslashes($q, '%_\\') . '%';   // % and _ typed by the user are searched literally
}
if ($genreId > 0) {
    // EXISTS only checks "does such a row exist?" – it never adds rows, so no duplicate games
    $where[]  = 'EXISTS (SELECT 1 FROM game_genres gg WHERE gg.game_id = g.id AND gg.genre_id = ?)';
    $params[] = $genreId;
}
if ($platform > 0) {
    $where[]  = 'EXISTS (SELECT 1 FROM game_platforms gp WHERE gp.game_id = g.id AND gp.platform_id = ?)';
    $params[] = $platform;
}
if ($year > 0) {
    $where[]  = 'g.release_year = ?';
    $params[] = $year;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---------- 4. Count matches (for pagination) ----------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM games g $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$totalPages = max(1, (int) ceil($total / $perPage));
$page       = min($page, $totalPages);            // ?page=999 -> last page
$offset     = ($page - 1) * $perPage;

// ---------- 5. Fetch one page of games ----------
$sql = "
    SELECT g.id, g.title, g.release_year, g.cover_path,
           (SELECT GROUP_CONCAT(gr.name ORDER BY gr.name SEPARATOR ', ')
              FROM game_genres gg
              JOIN genres gr ON gr.id = gg.genre_id
             WHERE gg.game_id = g.id)                       AS genre_names,
           (SELECT ROUND(AVG(e.rating), 1)
              FROM entries e WHERE e.game_id = g.id)        AS avg_rating,
           (SELECT COUNT(e.rating)
              FROM entries e WHERE e.game_id = g.id)        AS rating_count
      FROM games g
      $whereSql
     ORDER BY {$sortOptions[$sort]['sql']}
     LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([...$params, $perPage, $offset]);
$games = $stmt->fetchAll();

// ---------- 6. Helpers for the template ----------
// Active filters only (empty values dropped), reused for pagination links
$activeFilters = array_filter([
    'q'        => $q,
    'genre'    => $genreId,
    'platform' => $platform,
    'year'     => $year,
    'sort'     => $sort === 'title' ? '' : $sort,
]);
$hasFilters = (bool) array_diff_key($activeFilters, ['sort' => true]);   // sort alone is not a filter

function page_url(int $p, array $filters): string {
    return url('catalog.php') . '?' . http_build_query($filters + ['page' => $p]);
}

$pageTitle = 'Katalog';
require __DIR__ . '/includes/header.php';
?>
<h1>Katalog</h1>

<form method="get" class="filter-bar">
    <label>Pretraga
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Naziv igre">
    </label>
    <label>Žanr
        <select name="genre">
            <option value="">Svi</option>
            <?php foreach ($genres as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $g['id'] == $genreId ? 'selected' : '' ?>><?= e($g['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Platforma
        <select name="platform">
            <option value="">Sve</option>
            <?php foreach ($platforms as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $platform ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Godina
        <select name="year">
            <option value="">Sve</option>
            <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Sortiranje
        <select name="sort">
            <?php foreach ($sortOptions as $key => $opt): ?>
                <option value="<?= $key ?>" <?= $key === $sort ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="filter-actions">
        <button type="submit">Primeni</button>
        <?php if ($hasFilters): ?>
            <a href="<?= url('catalog.php') ?>">Poništi filtere</a>
        <?php endif; ?>
    </div>
</form>

<p class="meta">Pronađeno igara: <?= $total ?></p>

<?php if (!$games): ?>
    <div class="empty-state">
        <p>Nema rezultata.</p>
        <a href="<?= url('catalog.php') ?>">Poništi filtere</a>
    </div>
<?php else: ?>
    <ul class="game-grid">
        <?php foreach ($games as $game): ?>
            <li class="game-card">
                <a href="<?= url('game.php?id=' . $game['id']) ?>" class="game-cover-link">
                    <img class="game-cover" src="<?= e(cover_url($game['cover_path'])) ?>"
                         alt="<?= e($game['title']) ?>" loading="lazy">
                </a>
                <h3><a href="<?= url('game.php?id=' . $game['id']) ?>"><?= e($game['title']) ?></a></h3>
                <p class="meta"><?= e($game['release_year'] ?? '—') ?></p>
                <?php if ($game['genre_names']): ?>
                    <p class="meta"><?= e($game['genre_names']) ?></p>
                <?php endif; ?>
                <p class="rating">
                    <?php if ($game['rating_count'] > 0): ?>
                        ★ <?= e($game['avg_rating']) ?> <span class="meta">(<?= $game['rating_count'] ?>)</span>
                    <?php else: ?>
                        <span class="meta">Bez ocena</span>
                    <?php endif; ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Stranice">
            <?php if ($page > 1): ?>
                <a href="<?= e(page_url($page - 1, $activeFilters)) ?>">&laquo; Prethodna</a>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current" aria-current="page"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= e(page_url($p, $activeFilters)) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= e(page_url($page + 1, $activeFilters)) ?>">Sledeća &raquo;</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
