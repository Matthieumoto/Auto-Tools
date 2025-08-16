<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/mail.php';
require_once __DIR__.'/../inc/helpers.php';
auth_require_login();
$me = auth_current_user();

$ok=false;
$u = $pdo->prepare("SELECT id, name, email, email_verified_at FROM users WHERE id=?");
$u->execute([$me['id']]);
$row = $u->fetch();

if ($row && !$row['email_verified_at']) {
  $tok = bin2hex(random_bytes(32));
  $pdo->prepare("UPDATE users SET verify_token=? WHERE id=?")->execute([$tok,$me['id']]);
  $url = rtrim(BASE_URL,'/').'/verify_email.php?token='.$tok;
  $html = "<p>Salut ".htmlspecialchars($row['name']).",</p><p>Pour vérifier ton email, clique ici :</p><p><a href=\"$url\">Vérifier mon email</a></p>";
  mail_send($row['email'], "Vérifie ton email • AutoTools", $html);
  $ok=true;
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Renvoi vérification</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8">
</head><body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Renvoi du lien</h1>
    <?php if($ok): ?><p class="value" style="color:#8df08d;">✅ L’email vient d’être renvoyé.</p>
    <?php else: ?><p class="value" style="color:#ff9aa2;">ℹ️ Ton email est déjà vérifié, ou introuvable.</p><?php endif; ?>
    <a class="btn" href="<?= e(BASE_URL) ?>/profile.php">← Profil</a>
  </section>
</main>
</body></html>
