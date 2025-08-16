<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
$me = auth_current_user();

$ok=null; $err=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $make = trim($_POST['make'] ?? '');
  $model= trim($_POST['model'] ?? '');
  $year = $_POST['year'] !== '' ? (int)$_POST['year'] : null;
  $details = trim($_POST['details'] ?? '');

  if ($make==='' && $model==='') {
    $err = "Indique au moins la marque ou le modèle.";
  } else {
    // (option) si connecté mais non vérifié → bloque
    if ($me) {
      $v = $pdo->prepare("SELECT email_verified_at FROM users WHERE id=?");
      $v->execute([$me['id']]);
      if (!$v->fetchColumn()) $err = "Vérifie ton email pour envoyer une demande.";
    }
    if (!$err) {
      $stmt = $pdo->prepare('INSERT INTO car_requests(user_id, make, model, year, details) VALUES (?,?,?,?,?)');
      $stmt->execute([$me['id'] ?? null, $make ?: null, $model ?: null, $year, $details ?: null]);
      $ok = "Merci ! Ta demande a été envoyée à l’admin.";
    }
  }
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Demander l’ajout d’un véhicule — AutoTools</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8"/>
</head><body>
<header class="header">
  <div class="container bar">
    <a class="brand" href="<?= e(BASE_URL) ?>/index.php"><div class="badge">AT</div><div><div style="font-weight:800">AutoTools</div><div class="label">Demande d’ajout</div></div></a>
    <nav class="actions">
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
      <?php if ($me): ?><a class="btn" href="<?= e(BASE_URL) ?>/profile.php">Profil</a><a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a><?php else: ?>
      <a class="btn" href="<?= e(BASE_URL) ?>/login.php">Connexion</a><?php endif; ?>
    </nav>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <h1 style="margin:0;">Je ne trouve pas ma voiture</h1>
    <?php if($ok): ?><p class="value" style="color:#8df08d;">✅ <?= e($ok) ?></p><?php endif; ?>
    <?php if($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>

    <form method="post" class="grid" style="gap:10px;">
      <div class="grid grid-3">
        <input class="input" name="make"  placeholder="Marque (ex: Peugeot)" value="<?= e($_GET['make'] ?? '') ?>"/>
        <input class="input" name="model" placeholder="Modèle (ex: 208)" />
        <input class="input" type="number" name="year" min="1950" max="<?= date('Y') ?>" placeholder="Année"/>
      </div>
      <textarea class="input" name="details" rows="4" placeholder="Motorisation, finition, lien photo, etc. (optionnel)"></textarea>
      <button class="btn" type="submit">Envoyer la demande</button>
    </form>
  </section>
</main>
</body></html>
