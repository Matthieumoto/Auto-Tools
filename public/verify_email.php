<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';

$token = $_GET['token'] ?? '';
$ok=false;

if ($token && preg_match('/^[a-f0-9]{64}$/', $token)) {
  $s = $pdo->prepare("SELECT id FROM users WHERE verify_token=? LIMIT 1");
  $s->execute([$token]);
  if ($u = $s->fetch()) {
    $pdo->prepare("UPDATE users SET email_verified_at=NOW(), verify_token=NULL WHERE id=?")->execute([$u['id']]);
    $ok=true;
  }
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vérification email</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8">
</head><body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Vérification d’email</h1>
    <?php if($ok): ?>
      <p class="value" style="color:#8df08d;">✅ Ton email est confirmé.</p>
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">← Retour au site</a>
    <?php else: ?>
      <p class="value" style="color:#ff9aa2;">⚠️ Lien invalide ou déjà utilisé.</p>
    <?php endif; ?>
  </section>
</main>
</body></html>
