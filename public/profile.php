<?php
require_once __DIR__.'/../inc/auth.php';
auth_require_login();
$me = auth_current_user();

$verified = false;
if ($me) {
  $vr = $pdo->prepare("SELECT email_verified_at FROM users WHERE id=? LIMIT 1");
  $vr->execute([$me['id']]);
  $verified = (bool)$vr->fetchColumn();
}

// Sélection du véhicule
$info = null; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id'])) {
  $carId = (int)$_POST['car_id'];
  // Vérifie que la voiture existe
  $chk = $pdo->prepare('SELECT id FROM cars WHERE id = ?');
  $chk->execute([$carId]);
  if ($chk->fetch()) {
    // Upsert profil
    $pdo->prepare('INSERT INTO user_profile(user_id, selected_car_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE selected_car_id = VALUES(selected_car_id)')
        ->execute([$me['id'], $carId]);
    $info = 'Véhicule principal mis à jour.';
  } else {
    $err = "Véhicule introuvable.";
  }
}

// Recherche de voitures
$q = trim($_GET['q'] ?? '');
$cars = [];
if ($q !== '') {
  $like = "%$q%";
  $stmt = $pdo->prepare("SELECT id, make, model, year, variant FROM cars WHERE make LIKE ? OR model LIKE ? OR variant LIKE ? ORDER BY year DESC LIMIT 50");
  $stmt->execute([$like,$like,$like]);
  $cars = $stmt->fetchAll();
}

// Voiture sélectionnée
$sel = $pdo->prepare('SELECT c.id, c.make, c.model, c.year, c.variant, c.slug FROM user_profile up LEFT JOIN cars c ON c.id = up.selected_car_id WHERE up.user_id = ?');
$sel->execute([$me['id']]);
$mycar = $sel->fetch();

// URL fiche
$fichUrl = null;
if ($mycar) {
  if (!empty($mycar['slug'])) $fichUrl = rtrim(BASE_URL,'/').'/cars/'.$mycar['slug'];
  else $fichUrl = rtrim(BASE_URL,'/').'/car.php?id='.(int)$mycar['id'];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Profil — AutoTools</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css"/>
</head>
<body>
<header class="header">
  <div class="container" style="display:flex; align-items:center; justify-content:space-between; height:64px;">
    <div class="brand">
      <div class="badge">AT</div>
      <div>
        <div style="font-weight:800">AutoTools</div>
        <div class="label">Profil utilisateur</div>
      </div>
    </div>
    <div style="display:flex; gap:8px;">
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
      <a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Se déconnecter</a>
    </div>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <?php $displayName = !empty($me['username']) ? $me['username'] : $me['name']; ?>
    <h1 style="margin:0; display:flex; align-items:center; gap:8px;">
      Salut, <?= e(!empty($me['username']) ? $me['username'] : $me['name']) ?> 👋
      <?php if ($verified): ?>
        <span class="label">• Email vérifié</span>
      <?php else: ?>
        <span class="label">• Email non vérifié</span>
      <?php endif; ?>
    </h1>
    <p class="label">Email : <?= e($me['email']) ?></p>
    <?php if (!$verified): ?>
    <div class="card" style="background:#2b243f;border-color:#4e4591; margin-top:12px;">
      <div class="label" style="margin-bottom:8px;">
        ✉️ Ton email n’est pas vérifié. Clique sur le bouton pour recevoir un lien de confirmation.
      </div>
      <a class="btn" href="<?= e(BASE_URL) ?>/resend_verification.php">Vérifier mon email</a>
    </div>
    <?php endif; ?>
    <a class="btn" href="<?= e(BASE_URL) ?>/profile_edit.php">Éditer le profil</a>
    <?php if ($info): ?><p class="value" style="color:#8df08d;">✅ <?= e($info) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>
  </section>

  <section class="card">
    <h2 style="margin-top:0;">Ton véhicule principal</h2>
    <?php if ($mycar): ?>
      <p class="value">👉 <?= e($mycar['make'].' '.$mycar['model'].' '.$mycar['year'].' '.($mycar['variant']??'')) ?></p>
      <?php if ($fichUrl): ?><p class="label">Fiche : <a href="<?= e($fichUrl) ?>">ouvrir</a></p><?php endif; ?>
    <?php else: ?>
      <p class="label">Aucun véhicule défini pour l'instant.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2 style="margin-top:0;">Choisir / changer de véhicule</h2>
    <form method="get" class="grid" style="grid-template-columns:1fr auto; gap:10px;">
      <input class="input" name="q" placeholder="Recherche (ex: nom, marque, modèle...)" value="<?= e($q) ?>"/>
      <button class="btn" type="submit">Chercher</button>
    </form>
    <?php if ($q !== ''): ?>
      <table class="table" style="margin-top:12px;">
        <thead><tr><th>Modèle</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($cars as $c): ?>
            <tr>
              <td><?= e($c['make'].' '.$c['model'].' '.$c['year'].' '.($c['variant']??'')) ?></td>
              <td>
                <form method="post" style="margin:0;">
                  <input type="hidden" name="car_id" value="<?= (int)$c['id'] ?>"/>
                  <button class="btn" type="submit">Choisir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <div class="mt-2">
  <div class="card" style="background:#11162e; border-color:#32365d;">
    <div class="label">
      🚗 Tu ne trouves pas ta voiture ?
      <a href="<?= e(BASE_URL) ?>/request_car.php<?= $q ? '?make='.urlencode($q) : '' ?>">Fais une demande d’ajout</a>
      — on te prévient quand c’est en ligne.
    </div>
  </div>
</div>
  </section>
</main>
</body>
</html>
