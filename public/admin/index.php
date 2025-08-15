<?php
require_once __DIR__.'/../../inc/helpers.php'; // pour e()
require_once __DIR__.'/../../config.php';      // pour BASE_URL
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AutoTools • Admin</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css" />
</head>
<body>
  <main class="container grid" style="margin-top:24px;">
    <section class="card">
      <h1 style="margin:0;">Administration</h1>
      <p class="label">Outils de gestion (dev local)</p>
      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= e(BASE_URL) ?>/admin/import_csv.php">Importer des véhicules (CSV)</a>
        <a class="btn" href="<?= e(BASE_URL) ?>/index.php">← Retour au site</a>
      </div>
    </section>
  </main>
</body>
</html>