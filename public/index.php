<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/auth.php';
$me = auth_current_user();
$isAdmin = false;
if ($me) {
  $stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
  $stmt->execute([$me['id']]);
  $isAdmin = ((int)$stmt->fetchColumn() === 1);
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q !== '') {
  $sql = "SELECT * FROM cars WHERE make LIKE ? OR model LIKE ? OR variant LIKE ? ORDER BY year DESC LIMIT 50";
  $stmt = $pdo->prepare($sql);
  $like = "%$q%"; $stmt->execute([$like,$like,$like]);
  $cars = $stmt->fetchAll();
} else {
  $cars = $pdo->query("SELECT * FROM cars ORDER BY created_at DESC LIMIT 10")->fetchAll();
}
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>AutoTools – Catalogue</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8"/>
</head>
<body>

<header class="header">
  <div class="container bar">
    <a class="brand" href="<?= e(BASE_URL) ?>/index.php">
      <div class="badge">AT</div>
      <div>
        <div style="font-weight:800">AutoTools</div>
        <div class="label">Catalogue & fiches</div>
      </div>
    </a>
    <nav class="actions">
      <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
      <?php if ($me): ?>
        <a class="btn" href="<?= e(BASE_URL) ?>/profile.php">Profil</a>
        <?php if ($isAdmin): ?>
          <a class="btn" href="<?= e(BASE_URL) ?>/admin/index.php">Admin</a>
        <?php endif; ?>
        <a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a>
      <?php else: ?>
        <a class="btn" href="<?= e(BASE_URL) ?>/login.php">Connexion</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <form method="get" class="grid" style="grid-template-columns:1fr auto; gap:10px;">
      <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Recherche (ex: Marques, Modèles ...)" />
      <button class="btn" type="submit">Rechercher</button>
    </form>
  </section>

  <section class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Modèle</th><th>Puissance</th><th>0–100</th><th>Prix</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cars as $c): ?>
        <tr>
          <td><?= e($c['make'].' '.$c['model'].' '.$c['year'].' '.($c['variant']??'')) ?></td>
          <td><?= e(($c['power_kw']??'—')).' kW' ?> (<?= e(hp_from_kw($c['power_kw']) ?? '—') ?> hp)</td>
          <td><?= e(is_null($c['zero_to_100_s']) ? '—' : num((float)$c['zero_to_100_s'],2).' s') ?></td>
          <td><?= e(euro($c['price_eur'])) ?></td>
          <td><a class="btn" href="<?= e(BASE_URL) ?>/car.php?id=<?= (int)$c['id'] ?>">Voir fiche</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <div class="mt-2">
    <div class="card" style="background:#11162e; border-color:#32365d;">
      <div class="label">
        🚗 Tu ne trouves pas ta voiture ?
        <a href="<?= e(BASE_URL) ?>/request_car.php<?= $q ? '?make='.urlencode($q) : '' ?>">Fais une demande d’ajout</a>.
      </div>
    </div>
  </div>
</main>
</body>
</html>
