<?php
require_once __DIR__.'/../../inc/auth.php';
require_once __DIR__.'/../../inc/helpers.php';
require_once __DIR__.'/../../inc/mail.php';
auth_require_admin();

$action = $_GET['action'] ?? null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action && $id) {
  $st = $action==='done' ? 'done' : ($action==='reject' ? 'rejected' : null);
  if ($st) {
    $pdo->prepare("UPDATE car_requests SET status=?, reviewed_at=NOW() WHERE id=?")
        ->execute([$st, $id]);

    // notifier l'utilisateur si connu
    $info = $pdo->prepare("SELECT cr.make, cr.model, cr.year, u.email
                           FROM car_requests cr LEFT JOIN users u ON u.id=cr.user_id
                           WHERE cr.id=?");
    $info->execute([$id]);
    if ($r = $info->fetch()) {
      if (!empty($r['email'])) {
        $txt = trim(($r['make']??'').' '.($r['model']??'').' '.($r['year']??''));
        if ($st==='done') {
          $subject = "Ta demande de véhicule est en ligne ✅";
          $html = "<p>Bonne nouvelle, on a ajouté <b>".htmlspecialchars($txt)."</b> dans AutoTools.</p>";
        } else {
          $subject = "Ta demande de véhicule a été refusée ❌";
          $html = "<p>Ta demande <b>".htmlspecialchars($txt)."</b> a été refusée (non conforme/duplicat). Tu peux soumettre à nouveau avec plus de détails.</p>";
        }
        mail_send($r['email'], $subject, $html);
      }
    }
  }
  header('Location: requests.php'); exit;
}

$rows = $pdo->query("SELECT cr.*, u.email
                     FROM car_requests cr
                     LEFT JOIN users u ON u.id=cr.user_id
                     ORDER BY cr.status='open' DESC, cr.created_at DESC")->fetchAll();
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Demandes de véhicules — Admin</title>
<link rel="stylesheet" href="../assets/style.css?v=8"/>
</head><body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Demandes d’ajout</h1>
    <table class="table mt-2">
      <thead><tr><th>État</th><th>Créée</th><th>Utilisateur</th><th>Marque</th><th>Modèle</th><th>Année</th><th>Détails</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td class="label"><?= e($r['status']) ?></td>
            <td class="label"><?= e($r['created_at']) ?></td>
            <td><?= e($r['email'] ?? '—') ?></td>
            <td><?= e($r['make'] ?? '—') ?></td>
            <td><?= e($r['model'] ?? '—') ?></td>
            <td><?= e($r['year'] ?? '—') ?></td>
            <td class="label"><?= nl2br(e($r['details'] ?? '')) ?></td>
            <td>
              <?php if ($r['status']==='open'): ?>
                <a class="btn" href="?action=done&id=<?= (int)$r['id'] ?>">Marquer “fait”</a>
                <a class="btn" href="?action=reject&id=<?= (int)$r['id'] ?>" style="background:#7a2b41;">Refuser</a>
              <?php else: ?>
                <span class="label">Revu</span>
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
