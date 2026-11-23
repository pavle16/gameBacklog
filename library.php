<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();

// ---------- 1. Tabs: "all" + one per status ----------
$tabs = ['all' => 'Sve'];
foreach (ENTRY_STATUSES as $s) {
    $tabs[$s] = status_label($s);
}
// Unknown value -> show "Sve", no error
$tab = query_str('status', 'all');
if (!isset($tabs[$tab])) {
    $tab = 'all';
}

// Number of games per status, e.g. ['playing' => 3, 'completed' => 7]
$stmt = $pdo->prepare('SELECT status, COUNT(*) FROM entries WHERE user_id = ? GROUP BY status');
$stmt->execute([$user['id']]);
$counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$counts['all'] = array_sum($counts);

// ---------- 2. The user's entries (only their own: WHERE e.user_id = ?) ----------
$sql = 'SELECT e.id, e.status, e.rating, e.hours, e.started_at, e.finished_at, e.review_removed,
               g.id AS game_id, g.title, g.cover_path
          FROM entries e
          JOIN games g ON g.id = e.game_id
         WHERE e.user_id = ?';
$params = [$user['id']];

if ($tab !== 'all') {
    $sql .= ' AND e.status = ?';
    $params[] = $tab;
}
$sql .= ' ORDER BY e.updated_at DESC, g.title ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll();

$pageTitle = 'Moja biblioteka';
require __DIR__ . '/includes/header.php';
?>
<h1>Moja biblioteka</h1>

<ul class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <li>
            <a href="<?= url($key === 'all' ? 'library.php' : 'library.php?status=' . $key) ?>"
               class="<?= $key === $tab ? 'active' : '' ?>">
                <?= e($label) ?> <span class="count"><?= $counts[$key] ?? 0 ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($counts['all'] === 0): ?>
    <!-- No games at all (on any tab) -->
    <div class="empty-state">
        <p>Vaša biblioteka je prazna.</p>
        <a href="<?= url('catalog.php') ?>">Pronađite igre u katalogu</a>
    </div>
<?php elseif (!$entries): ?>
    <!-- Library has games, just none with this status -->
    <div class="empty-state">
        <p>Nema igara sa statusom „<?= e($tabs[$tab]) ?>“.</p>
        <a href="<?= url('library.php') ?>">Prikaži sve igre</a>
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
                    <th></th>
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
                        <?php if ($en['review_removed']): ?>
                            <small>Recenzija uklonjena</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="status status-<?= e($en['status']) ?>"><?= e(status_label($en['status'])) ?></span></td>
                    <td><?= $en['rating'] !== null ? '★ ' . $en['rating'] : '—' ?></td>
                    <td><?= (float) $en['hours'] > 0 ? e($en['hours']) : '—' ?></td>
                    <td><?= format_date($en['started_at']) ?></td>
                    <td><?= format_date($en['finished_at']) ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="btn btn-secondary btn-small" href="<?= url('entry_form.php?id=' . $en['id']) ?>">Izmeni</a>
                            <form method="post" action="<?= url('entry_delete.php') ?>"
                                  data-confirm="Ukloniti „<?= e($en['title']) ?>“ iz biblioteke?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $en['id'] ?>">
                                <button type="submit" class="btn-danger btn-small">Obriši</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
