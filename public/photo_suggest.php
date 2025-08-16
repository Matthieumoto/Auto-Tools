<?php
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/helpers.php';
$me = auth_current_user();

$carId = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;
if ($carId <= 0) { http_response_code(400); echo 'car_id manquant'; exit; }

$stmt = $pdo->prepare("SELECT id, make, model, year, variant, image_url FROM cars WHERE id=?");
$stmt->execute([$carId]);
$car = $stmt->fetch();
if (!$car) { http_response_code(404); echo 'Véhicule introuvable'; exit; }

$ok=null; $err=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $url = trim($_POST['image_url'] ?? '');
  $comment = trim($_POST['comment'] ?? '');
  if ($url === '' || !preg_match('~^https?://~i',$url)) {
    $err = "Entre une URL valide (http/https).";
  } else {
    if ($me) {
      $v = $pdo->prepare("SELECT email_verified_at FROM users WHERE id=?");
      $v->execute([$me['id']]);
      if (!$v->fetchColumn()) $err = "Vérifie ton email pour proposer une photo.";
    }
    if (!$err) {
      $ins = $pdo->prepare("INSERT INTO car_photo_suggestions(car_id,user_id,image_url,comment) VALUES (?,?,?,?)");
      $ins->execute([$car['id'], $me['id'] ?? null, $url, ($comment ?: null)]);
      $ok = "Merci ! Ta proposition sera vérifiée par l’admin.";
    }
  }
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Proposer une photo — AutoTools</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8"/>
</head><body>
<header class="header">
  <div class="container bar">
    <a class="brand" href="<?= e(BASE_URL) ?>/index.php"><div class="badge">AT</div><div><div style="font-weight:800">AutoTools</div><div class="label">Proposer une photo</div></div></a>
    <nav class="actions">
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
      <?php if ($me): ?><a class="btn" href="<?= e(BASE_URL) ?>/profile.php">Profil</a><a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a><?php else: ?>
      <a class="btn" href="<?= e(BASE_URL) ?>/login.php">Connexion</a><?php endif; ?>
    </nav>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <h1 style="margin:0;">Proposer une photo</h1>
    <p class="label">Véhicule : <b><?= e($car['make'].' '.$car['model'].' '.$car['year'].' '.($car['variant']??'')) ?></b></p>
    <div class="hero mt-1">
      <img src="<?= e($car['image_url'] ?: 'https://placehold.co/240x160') ?>" alt="photo actuelle"/>
      <div class="label">Photo actuelle</div>
    </div>

    <?php if ($ok): ?><p class="value" style="color:#8df08d;">✅ <?= e($ok) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>

    <form method="post" class="grid" style="gap:10px; margin-top:12px;">
      <input class="input" name="image_url" placeholder="Lien direct vers l’image (https://... .jpg/.png…)" required />
      <textarea class="input" name="comment" rows="3" placeholder="Commentaire (source, prise de vue, etc. — optionnel)"></textarea>
      <button class="btn" type="submit">Envoyer</button>
    </form>
  </section>
</main>
</body></html>
