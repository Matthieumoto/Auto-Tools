<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare('SELECT * FROM cars WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$car = $stmt->fetch();
if (!$car) { http_response_code(404); echo 'Fiche introuvable'; exit; }
$hp = hp_from_kw($car['power_kw']);
$ptw = kw_per_tonne($car['power_kw'], $car['weight_kg']);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= e($car['make'].' '.$car['model'].' '.$car['year']) ?> — Fiche</title>
  <link rel="stylesheet" href="assets/style.css"/>
</head>
<body>
<header class="header">
  <div class="container" style="display:flex; align-items:center; justify-content:space-between; height:64px;">
    <div class="brand">
      <div class="badge">AT</div>
      <div>
        <div style="font-weight:800">AutoTools</div>
        <div class="label">Fiche véhicule</div>
      </div>
    </div>
    <a class="btn" href="<?= e(BASE_URL) ?>/index.php">← Retour</a>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card hero">
    <img src="<?= e($car['image_url'] ?: 'https://placehold.co/240x160') ?>" alt="Photo"/>
    <div>
      <h1 style="margin:0; font-size:24px;"><?= e($car['make'].' '.$car['model'].' '.$car['year'].' '.($car['variant']??'')) ?></h1>
      <div class="label">Carburant: <?= e($car['fuel']) ?> • CO₂: <?= e($car['co2_gpkm'] ?? '—') ?> g/km • WLTP: <?= e(is_null($car['wltp_l100']) ? '—' : num((float)$car['wltp_l100'],1).' L/100') ?></div>
    </div>
  </section>

  <section class="card grid grid-3">
    <div>
      <div class="label">Puissance</div>
      <div class="value"><?= e(($car['power_kw']??'—')).' kW' ?><?= $hp? ' ('.$hp.' hp)': '' ?></div>
    </div>
    <div>
      <div class="label">Poids</div>
      <div class="value"><?= e($car['weight_kg'] ?? '—') ?> kg</div>
    </div>
    <div>
      <div class="label">Puissance/poids</div>
      <div class="value"><?= is_null($ptw)? '—' : num($ptw,2).' kW/t' ?></div>
    </div>

    <div>
      <div class="label">0–100 km/h</div>
      <div class="value"><?= e(is_null($car['zero_to_100_s']) ? '—' : num((float)$car['zero_to_100_s'],2).' s') ?></div>
    </div>
    <div>
      <div class="label">0–200 km/h</div>
      <div class="value"><?= e(is_null($car['zero_to_200_s']) ? '—' : num((float)$car['zero_to_200_s'],2).' s') ?></div>
    </div>
    <div>
      <div class="label">Prix public</div>
      <div class="value"><?= e(euro($car['price_eur'])) ?></div>
    </div>

    <div>
      <div class="label">Taxe/Malus</div>
      <div class="value"><?= e(euro($car['tax_eur'])) ?></div>
    </div>
    <div>
      <div class="label">CO₂ (g/km)</div>
      <div class="value"><?= e($car['co2_gpkm'] ?? '—') ?></div>
    </div>
    <div>
      <div class="label">Conso WLTP</div>
      <div class="value"><?= e(is_null($car['wltp_l100']) ? '—' : num((float)$car['wltp_l100'],1).' L/100') ?></div>
    </div>
  </section>

</main>
</body>
</html>