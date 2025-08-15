<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q !== '') {
  $sql = "SELECT * FROM cars WHERE make LIKE ? OR model LIKE ? OR variant LIKE ? ORDER BY year DESC LIMIT 50";
  $stmt = $pdo->prepare($sql);
  $like = "%$q%";
  $stmt->execute([$like, $like, $like]);
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
  <link rel="stylesheet" href="assets/style.css"/>
</head>
<body>
<header class="header">
  <div class="container" style="display:flex; align-items:center; justify-content:space-between; height:64px;">
    <div class="brand">
      <div class="badge">AT</div>
      <div>
        <div style="font-weight:800">AutoTools</div>
        <div class="label">Catalogue & fiches</div>
      </div>
    </div>
    <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
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
          <th>Modèle</th>
          <th>Puissance</th>
          <th>0–100</th>
          <th>Prix</th>
          <th></th>
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
</main>
</body>
</html>