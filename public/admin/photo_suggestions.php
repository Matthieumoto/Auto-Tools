<?php
require_once __DIR__.'/../../inc/auth.php';
require_once __DIR__.'/../../inc/helpers.php';
require_once __DIR__.'/../../inc/mail.php';
auth_require_admin();

$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $id) {
  if ($action==='approve') {
    $s = $pdo->prepare("SELECT car_id, image_url FROM car_photo_suggestions WHERE id=? AND status='open' LIMIT 1");
    $s->execute([$id]);
    if ($row = $s->fetch()) {
      $pdo->beginTransaction();
      try{
        $pdo->prepare("UPDATE cars SET image_url=? WHERE id=?")->execute([$row['image_url'], $row['car_id']]);
        $pdo->prepare("UPDATE car_photo_suggestions SET status='approved', reviewed_at=NOW(), reviewed_by=? WHERE id=?")
            ->execute([auth_current_user()['id'], $id]);
        $pdo->commit();
      } catch(Throwable $e){ $pdo->rollBack(); }
    }
  } elseif ($action==='reject') {
    $pdo->prepare("UPDATE car_photo_suggestions SET status='rejected', reviewed_at=NOW(), reviewed_by=? WHERE id=?")
        ->execute([auth_current_user()['id'], $id]);
  }

  // notifier le proposeur
  $info = $pdo->prepare("SELECT cps.user_id, u.email, c.make, c.model, c.year
                         FROM car_photo_suggestions cps
                         JOIN cars c ON c.id=cps.car_id
                         LEFT JOIN users u ON u.id=cps.user_id
                         WHERE cps.id=?");
  $info->execute([$id]);
  if ($r = $info->fetch()) {
    if (!empty($r['email'])) {
      if ($action==='approve') {
        $subject = "Ta photo a été acceptée ✅";
        $html = "<p>Merci ! Ta photo pour <b>".htmlspecialchars($r['make'].' '.$r['model'].' '.$r['year'])."</b> a été mise en ligne.</p>";
      } else {
        $subject = "Ta photo a été refusée ❌";
        $html = "<p>Désolé, ta photo pour <b>".htmlspecialchars($r['make'].' '.$r['model'].' '.$r['year'])."</b> a été refusée (droits, qualité, non correspondance).</p>";
      }
      mail_send($r['email'], $subject, $html);
    }
  }
  header('Location: photo_suggestions.php'); exit;
}

$rows = $pdo->query("
  SELECT cps.*, c.make, c.model, c.year, c.variant, u.email
  FROM car_photo_suggestions cps
  JOIN cars c ON c.id=cps.car_id
  LEFT JOIN users u ON u.id=cps.user_id
  ORDER BY cps.status='open' DESC, cps.created_at DESC
")->fetchAll();
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Modération photos — Admin</title>
<link rel="stylesheet" href="../assets/style.css?v=8"/>
</head><body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Propositions de photos</h1>
    <table class="table mt-2">
      <thead><tr><th>État</th><th>Voiture</th><th>Image</th><th>Proposé par</th><th>Commentaire</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td class="label"><?= e($r['status']) ?></td>
          <td><?= e($r['make'].' '.$r['model'].' '.$r['year'].' '.($r['variant']??'')) ?></td>
          <td><img src="<?= e($r['image_url']) ?>" alt="" style="width:180px;height:120px;object-fit:cover;border-radius:10px;border:1px solid var(--border);" /></td>
          <td><?= e($r['email'] ?? 'Anonyme') ?></td>
          <td class="label"><?= nl2br(e($r['comment'] ?? '')) ?></td>
          <td>
            <?php if ($r['status']==='open'): ?>
              <a class="btn" href="?action=approve&id=<?= (int)$r['id'] ?>">Approuver</a>
              <a class="btn" style="background:#7a2b41;" href="?action=reject&id=<?= (int)$r['id'] ?>">Refuser</a>
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
