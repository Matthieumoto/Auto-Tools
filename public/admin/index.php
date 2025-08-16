<?php
require_once __DIR__.'/../../inc/auth.php';
require_once __DIR__.'/../../inc/helpers.php';
auth_require_admin();

// compteurs
$openReq   = (int)$pdo->query("SELECT COUNT(*) FROM car_requests WHERE status='open'")->fetchColumn();
$openPhoto = (int)$pdo->query("SELECT COUNT(*) FROM car_photo_suggestions WHERE status='open'")->fetchColumn();
$openRep   = (int)$pdo->query("SELECT COUNT(*) FROM car_mismatch_reports WHERE status='open'")->fetchColumn();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>AutoTools • Admin</title>
  <link rel="stylesheet" href="../assets/style.css?v=8"/>
</head>
<body>
  <main class="container grid" style="margin-top:24px;">
    <section class="card">
      <h1 style="margin:0;">Administration</h1>
      <p class="label">Outils de gestion</p>
      <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:12px;">
        <a class="btn" href="import_csv.php">Importer des véhicules (CSV)</a>
        <a class="btn" href="photo_suggestions.php">Modérer les photos (<?= $openPhoto ?>)</a>
        <a class="btn" href="mismatch_reports.php">Signalements (<?= $openRep ?>)</a>
        <a class="btn" href="requests.php">Demandes d’ajout (<?= $openReq ?>)</a>
        <a class="btn" href="../index.php" style="background:#40456b;">← Retour au site</a>
      </div>
    </section>
  </main>
</body>
</html>
