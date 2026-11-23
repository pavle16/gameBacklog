<?php
require_once __DIR__ . '/includes/init.php';
require_login();

$user = current_user();

// true only for a real calendar date in Y-m-d format (rejects "2026-02-30", "abc"...)
function is_valid_date(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// ---------- 1. Which mode? Load the entry (edit) or the game (add) ----------
$entryId = query_int('id');        // 0 = not given / invalid
$gameId  = query_int('game_id');

$entry = null;   // stays null in add mode

if ($entryId) {
    // EDIT mode. "AND e.user_id = ?" means someone else's entry looks exactly like a missing one.
    $stmt = $pdo->prepare('SELECT e.*, g.title, g.cover_path
                             FROM entries e
                             JOIN games g ON g.id = e.game_id
                            WHERE e.id = ? AND e.user_id = ?');
    $stmt->execute([$entryId, $user['id']]);
    $entry = $stmt->fetch();
    if (!$entry) {
        not_found('Unos ne postoji.');
    }
    $game = ['id' => $entry['game_id'], 'title' => $entry['title'], 'cover_path' => $entry['cover_path']];
} elseif ($gameId) {
    // ADD mode
    $stmt = $pdo->prepare('SELECT id, title, cover_path FROM games WHERE id = ?');
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    if (!$game) {
        not_found('Tražena igra ne postoji.');
    }

    // Already in the library? -> edit that entry instead (also stops a double submit from inserting twice)
    $stmt = $pdo->prepare('SELECT id FROM entries WHERE user_id = ? AND game_id = ?');
    $stmt->execute([$user['id'], $game['id']]);
    $existingId = $stmt->fetchColumn();
    if ($existingId) {
        redirect('entry_form.php?id=' . $existingId);
    }
} else {
    not_found('Nije izabrana igra.');
}

$isEdit = $entry !== null;
$fields = ['status', 'rating', 'review', 'notes', 'started_at', 'finished_at', 'hours'];

// ---------- 2. Current form values ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    foreach ($fields as $f) {
        $values[$f] = trim($_POST[$f] ?? '');   // fields disabled by JS are not sent -> ''
    }
} elseif ($isEdit) {
    $values = [];
    foreach ($fields as $f) {
        $values[$f] = (string) ($entry[$f] ?? '');   // NULL from DB -> ''
    }
} else {
    $values = ['status' => 'backlog', 'rating' => '', 'review' => '', 'notes' => '',
               'started_at' => '', 'finished_at' => '', 'hours' => '0'];
}

// ---------- 3. Validate and save ----------
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $status   = $values['status'];
    $rating   = $values['rating'];
    $started  = $values['started_at'];
    $finished = $values['finished_at'];
    $hours    = str_replace(',', '.', $values['hours']);   // accept "12,5" as well as "12.5"
    if ($hours === '') {
        $hours = '0';
    }
    $today = date('Y-m-d');

    if (!in_array($status, ENTRY_STATUSES, true)) {
        $errors[] = 'Izaberite ispravan status.';
    }
    if ($rating !== '' && !preg_match('/^([1-9]|10)$/', $rating)) {
        $errors[] = 'Ocena mora biti ceo broj od 1 do 10.';
    }
    // up to 5 digits, optionally one decimal: 0, 12, 12.5, 99999
    if (!preg_match('/^\d{1,5}(\.\d)?$/', $hours) || (float) $hours > 99999) {
        $errors[] = 'Broj sati: od 0 do 99999, najviše jedna decimala.';
    }

    // Date format + no dates in the future ("Y-m-d" strings compare correctly as text)
    foreach (['started_at' => 'Datum početka', 'finished_at' => 'Datum završetka'] as $field => $label) {
        if ($values[$field] === '') continue;
        if (!is_valid_date($values[$field])) {
            $errors[] = "$label nije ispravan datum.";
        } elseif ($values[$field] > $today) {
            $errors[] = "$label ne može biti u budućnosti.";
        }
    }

    // Rules that depend on the status
    if ($status === 'backlog') {
        if ($rating !== '') {
            $errors[] = 'Igra na listi ne može imati ocenu.';
        }
        if ($started !== '' || $finished !== '') {
            $errors[] = 'Igra na listi ne može imati datume.';
        }
        if ((float) $hours != 0) {
            $errors[] = 'Igra na listi mora imati 0 sati.';
        }
    }
    if (($status === 'playing' || $status === 'dropped') && $finished !== '') {
        $errors[] = 'Datum završetka se unosi samo za završene igre.';
    }
    if ($status === 'completed' && $finished === '') {
        $errors[] = 'Za završenu igru unesite datum završetka.';
    }
    if ($started !== '' && $finished !== '' && $finished < $started) {
        $errors[] = 'Datum završetka ne može biti pre datuma početka.';
    }

    if (!$errors) {
        // Same order as the "?" in both queries below. Empty strings become NULL.
        $data = [
            $status,
            $rating === '' ? null : (int) $rating,
            empty_to_null($values['review']),
            empty_to_null($values['notes']),
            empty_to_null($started),
            empty_to_null($finished),
            $hours,
        ];

        if ($isEdit) {
            // review_removed is deliberately not in this list, so a moderator's decision stays
            $stmt = $pdo->prepare('UPDATE entries
                                      SET status = ?, rating = ?, review = ?, notes = ?,
                                          started_at = ?, finished_at = ?, hours = ?
                                    WHERE id = ? AND user_id = ?');
            $stmt->execute([...$data, $entry['id'], $user['id']]);
            flash('success', 'Izmene za „' . $game['title'] . '“ su sačuvane.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO entries
                                          (status, rating, review, notes, started_at, finished_at, hours, user_id, game_id)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([...$data, $user['id'], $game['id']]);
            flash('success', '„' . $game['title'] . '“ je dodata u biblioteku.');
        }
        redirect('library.php');
    }
}

$pageTitle   = ($isEdit ? 'Izmena: ' : 'Dodavanje: ') . $game['title'];
$pageScripts = ['public/js/entry_form.js'];
require __DIR__ . '/includes/header.php';
?>
<div class="entry-header">
    <img src="<?= e(cover_url($game['cover_path'])) ?>" alt="<?= e($game['title']) ?>">
    <div>
        <p class="meta"><?= $isEdit ? 'Izmena unosa' : 'Dodavanje u biblioteku' ?></p>
        <h1><a href="<?= url('game.php?id=' . $game['id']) ?>"><?= e($game['title']) ?></a></h1>
    </div>
</div>

<?php if ($isEdit && $entry['review_removed']): ?>
    <div class="flash flash-error">Moderator je uklonio vašu recenziju. Ona se ne prikazuje drugim korisnicima.</div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="flash flash-error">
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="form form-wide">
    <?= csrf_field() ?>

    <label>Status
        <select name="status" id="status">
            <?php foreach (ENTRY_STATUSES as $s): ?>
                <option value="<?= $s ?>" <?= $values['status'] === $s ? 'selected' : '' ?>><?= e(status_label($s)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label id="field-rating">Ocena
        <select name="rating">
            <option value="">Bez ocene</option>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= $values['rating'] === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </label>

    <label id="field-started">Početak igranja
        <input type="date" name="started_at" value="<?= e($values['started_at']) ?>" max="<?= date('Y-m-d') ?>">
    </label>

    <label id="field-finished">Završetak
        <input type="date" name="finished_at" id="finished_at" value="<?= e($values['finished_at']) ?>" max="<?= date('Y-m-d') ?>">
    </label>

    <label id="field-hours">Broj sati
        <input type="number" name="hours" value="<?= e($values['hours']) ?>" min="0" max="99999" step="0.1">
    </label>

    <label>Recenzija <small>(javna)</small>
        <textarea name="review"><?= e($values['review']) ?></textarea>
    </label>

    <label>Beleške <small>(vidite ih samo vi)</small>
        <textarea name="notes"><?= e($values['notes']) ?></textarea>
    </label>

    <div class="form-actions">
        <button type="submit"><?= $isEdit ? 'Sačuvaj izmene' : 'Dodaj u biblioteku' ?></button>
        <a href="<?= url('library.php') ?>">Odustani</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <form method="post" action="<?= url('entry_delete.php') ?>" class="danger-zone"
          data-confirm="Ukloniti „<?= e($game['title']) ?>“ iz biblioteke?">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $entry['id'] ?>">
        <button type="submit" class="btn-danger">Ukloni iz biblioteke</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
