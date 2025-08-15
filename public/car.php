<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../config.php'; // BASE_URL + DB_NAME

// 1) On détecte si la colonne 'slug' existe dans la table 'cars'
$check = $pdo->prepare("
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'cars' AND COLUMN_NAME = 'slug'
");
$check->execute([DB_NAME]);
$hasSlug = $check->fetchColumn() > 0;

// 2) On accepte /car.php?slug=... (si colonne existe) OU /car.php?id=...
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
$id   = isset($_GET['id'])   ? (int)$_GET['id']     : 0;

// colonnes communes
$cols = "id, make, model, year, variant, power_kw, weight_kg, zero_to_100_s, zero_to_200_s, fuel, co2_gpkm, wltp_l100, price_eur, tax_eur, image_url";
if ($hasSlug) { $cols = "slug, " . $cols; }

if ($hasSlug && $slug !== null && $slug !== '') {
  $stmt = $pdo->prepare("SELECT $cols FROM cars WHERE slug = ? LIMIT 1");
  $stmt->execute([$slug]);
} else {
  $stmt = $pdo->prepare("SELECT $cols FROM cars WHERE id = ? LIMIT 1");
  $stmt->execute([$id]);
}

$car = $stmt->fetch();
if (!$car) { http_response_code(404); echo 'Fiche introuvable'; exit; }

// Utilitaires d’affichage
$hp  = isset($car['power_kw']) ? hp_from_kw((int)$car['power_kw']) : null;
$ptw = (isset($car['power_kw'],$car['weight_kg']) ? kw_per_tonne((int)$car['power_kw'], (int)$car['weight_kg']) : null);

// URL « jolie » si slug dispo ET colonne existante, sinon fallback ?id=
if ($hasSlug && !empty($car['slug'])) {
  $friendly = rtrim(BASE_URL, '/').'/cars/'.rawurlencode($car['slug']);
} else {
  $friendly = rtrim(BASE_URL, '/').'/car.php?id='.(int)$car['id'];
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= e((($car['make']??'').' '.($car['model']??'').' '.($car['year']??''))) ?> — Fiche</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css"/>
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
      <h1 style="margin:0; font-size:24px;">
        <?= e((($car['make']??'').' '.($car['model']??'').' '.($car['year']??'').' '.(($car['variant']??'') ?: ''))) ?>
      </h1>
      <div class="label">URL de la fiche : <a href="<?= e($friendly) ?>"><?= e($friendly) ?></a></div>
      <div class="label">
        Carburant: <?= e($car['fuel'] ?? '—') ?>
        • CO₂: <?= isset($car['co2_gpkm']) ? e((string)$car['co2_gpkm']).' g/km' : '—' ?>
        • WLTP: <?= isset($car['wltp_l100']) ? e(number_format((float)$car['wltp_l100'],1,',',' ')).' L/100' : '—' ?>
      </div>
    </div>
  </section>

  <section class="card grid grid-3">
    <div>
      <div class="label">Puissance</div>
      <div class="value">
        <?= isset($car['power_kw']) ? e((string)$car['power_kw']).' kW' : '—' ?>
        <?= $hp ? ' ('.e((string)$hp).' hp)' : '' ?>
      </div>
    </div>
    <div>
      <div class="label">Poids</div>
      <div class="value"><?= isset($car['weight_kg']) ? e((string)$car['weight_kg']).' kg' : '—' ?></div>
    </div>
    <div>
      <div class="label">Puissance/poids</div>
      <div class="value"><?= ($ptw!==null) ? e(number_format((float)$ptw,2,',',' ')).' kW/t' : '—' ?></div>
    </div>

    <div>
      <div class="label">0–100 km/h</div>
      <div class="value"><?= isset($car['zero_to_100_s']) ? e(number_format((float)$car['zero_to_100_s'],2,',',' ')).' s' : '—' ?></div>
    </div>
    <div>
      <div class="label">0–200 km/h</div>
      <div class="value"><?= isset($car['zero_to_200_s']) ? e(number_format((float)$car['zero_to_200_s'],1,',',' ')).' s' : '—' ?></div>
    </div>
    <div>
      <div class="label">Prix public</div>
      <div class="value"><?= e(euro($car['price_eur'] ?? null)) ?></div>
    </div>

    <div>
      <div class="label">Taxe/Malus</div>
      <div class="value"><?= e(euro($car['tax_eur'] ?? null)) ?></div>
    </div>
    <div>
      <div class="label">CO₂ (g/km)</div>
      <div class="value"><?= isset($car['co2_gpkm']) ? e((string)$car['co2_gpkm']) : '—' ?></div>
    </div>
    <div>
      <div class="label">Conso WLTP</div>
      <div class="value"><?= isset($car['wltp_l100']) ? e(number_format((float)$car['wltp_l100'],1,',',' ')).' L/100' : '—' ?></div>
    </div>
  </section>
</main>
</body>
</html>
