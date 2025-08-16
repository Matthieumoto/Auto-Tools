<?php
require_once __DIR__.'/../../inc/auth.php';
auth_require_admin();
require_once __DIR__.'/../../inc/db.php';
require_once __DIR__.'/../../inc/helpers.php';

// Remplit les slugs manquants à partir des champs d'identité
// Pattern: make-model-year-variant|base-fuel-power_kw

$pdo->beginTransaction();
$select = $pdo->query("SELECT id, make, model, year, IF(variant='' OR variant IS NULL,'base',variant) AS variant,
                              IF(fuel IS NULL,'other',fuel) AS fuel,
                              IFNULL(power_kw,0) AS power_kw, slug
                       FROM cars");

$updated = 0; $skipped = 0; $errors = [];
$upd = $pdo->prepare("UPDATE cars SET slug = :slug WHERE id = :id");

while ($row = $select->fetch()) {
  if (!empty($row['slug'])) { $skipped++; continue; }
  $slug = slugify($row['make'].'-'.$row['model'].'-'.$row['year'].'-'.$row['variant'].'-'.$row['fuel'].'-'.$row['power_kw']);
  try{
    $upd->execute([':slug'=>$slug, ':id'=>$row['id']]);
    $updated++;
  } catch (Throwable $e) {
    $errors[] = 'ID '.$row['id'].' : '.$e->getMessage();
  }
}

if (empty($errors)) { $pdo->commit(); } else { $pdo->rollBack(); }
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Backfill slugs</title>
  <link rel="stylesheet" href="../assets/style.css" />
</head>
<body>
  <main class="container grid" style="margin-top:24px;">
    <section class="card">
      <h1 style="margin:0;">Backfill des slugs</h1>
      <?php if (empty($errors)): ?>
        <p class="value" style="color:#8df08d;">✅ <?= (int)$updated ?> lignes mises à jour (<?= (int)$skipped ?> déjà OK).</p>
        <p class="label">Tu peux maintenant exécuter le <code>step 3</code> de <code>sql/add_slug.sql</code> pour mettre <b>slug NOT NULL</b>.</p>
      <?php else: ?>
        <div class="card" style="background:#2c1420; border-color:#7a2b41;">
          <div class="value">⚠️ Erreurs, transaction annulée</div>
          <ul>
            <?php foreach($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
      <div style="margin-top:12px;">
        <a class="btn" href="../index.php">← Retour admin</a>
      </div>
    </section>
  </main>
</body>
</html>
