<?php
require_once __DIR__.'/../../inc/auth.php';
require_once __DIR__.'/../../inc/helpers.php';
auth_require_admin();

$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $id) {
  $st = $action==='resolve' ? 'resolved' : ($action==='dismiss' ? 'dismissed' : null);
  if ($st) {
    $pdo->prepare("UPDATE car_mismatch_reports SET status=?, reviewed_at=NOW(), reviewed_by=? WHERE id=?")
        ->execute([$st, auth_current_user()['id'], $id]);
  }
  header('Location: mismatch_reports.php'); exit;
}

$rows = $pdo->query("
  SELECT r.*, c.make, c.model, c.year, c.variant, u.email
  FROM car_mismatch_reports r
  JOIN cars c ON c.id=r.car_id
  LEFT JOIN users u ON u.id=r.user_id
  ORDER BY r.status='open' DESC, r.created_at DESC
")->fetchAll();
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Signalements — Admin</title>
<link rel="stylesheet" href="../assets/style.css?v=8"/>
</head><body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Signalements “mauvaise voiture”</h1>
    <table class="table mt-2">
      <thead><tr><th>État</th><th>Voiture</th><th>Message</th><th>Par</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td class="label"><?= e($r['status']) ?></td>
          <td><?= e($r['make'].' '.$r['model'].' '.$r['year'].' '.($r['variant']??'')) ?></td>
          <td class="label"><?= nl2br(e($r['message'] ?? '')) ?></td>
          <td><?= e($r['email'] ?? 'Anonyme') ?></td>
          <td>
            <?php if ($r['status']==='open'): ?>
              <a class="btn" href="?action=resolve&id=<?= (int)$r['id'] ?>">Marquer “corrigé”</a>
              <a class="btn" style="background:#7a2b41;" href="?action=dismiss&id=<?= (int)$r['id'] ?>">Ignorer</a>
            <?php else: ?>
              <span class="label">Revu le <?= e($r['reviewed_at'] ?? '') ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="mt-2"><a class="btn" href="index.php">← Admin</a></div>
  </section>
</main>
</body></html>
