<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
$me = auth_current_user();

$carId = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
if ($carId <= 0) { http_response_code(400); echo 'car_id manquant'; exit; }

$stmt = $pdo->prepare("SELECT id, make, model, year, variant FROM cars WHERE id=?");
$stmt->execute([$carId]);
$car = $stmt->fetch();
if (!$car) { http_response_code(404); echo 'Véhicule introuvable'; exit; }

$ok=null; $err=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $message = trim($_POST['message'] ?? '');
  if ($message==='') $err="Explique ce qui cloche (modèle exact, année, etc.).";
  else {
    $pdo->prepare("INSERT INTO car_mismatch_reports(car_id,user_id,message) VALUES (?,?,?)")
        ->execute([$car['id'], $me['id'] ?? null, $message]);
    $ok="Merci ! On va vérifier et corriger.";
  }
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Signaler une erreur — AutoTools</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8"/>
</head><body>
<header class="header">
  <div class="container bar">
    <a class="brand" href="<?= e(BASE_URL) ?>/index.php"><div class="badge">AT</div><div><div style="font-weight:800">AutoTools</div><div class="label">Signalement</div></div></a>
    <nav class="actions">
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
      <?php if ($me): ?><a class="btn" href="<?= e(BASE_URL) ?>/profile.php">Profil</a><a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a>
      <?php else: ?><a class="btn" href="<?= e(BASE_URL) ?>/login.php">Connexion</a><?php endif; ?>
    </nav>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <h1 style="margin:0;">Ce n’est pas la bonne voiture ?</h1>
    <p class="label">Fiche : <b><?= e($car['make'].' '.$car['model'].' '.$car['year'].' '.($car['variant']??'')) ?></b></p>

    <?php if ($ok): ?><p class="value" style="color:#8df08d;">✅ <?= e($ok) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>

    <form method="post" class="grid" style="gap:10px;">
      <textarea class="input" name="message" rows="4" placeholder="Ex: 'C’est la 208 2021 PureTech 130, pas la 100' ou 'Photo = GTI'"></textarea>
      <button class="btn" type="submit">Envoyer le signalement</button>
    </form>
  </section>
</main>
</body></html>
