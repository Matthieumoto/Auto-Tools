<?php
require_once __DIR__.'/../../inc/auth.php';
auth_require_admin();
require_once __DIR__.'/../../inc/db.php';
require_once __DIR__.'/../../inc/helpers.php';
require_once __DIR__.'/../../config.php'; // BASE_URL pour les liens

if (!function_exists('slugify')) {
  function slugify(string $s): string {
    $s = trim($s);
    if (function_exists('iconv')) {
      $tmp = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
      if ($tmp !== false) $s = $tmp;
    }
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/','-',$s);
    $s = trim($s, '-');
    return $s ?: 'n-a';
  }
}

function detect_delimiter(string $line): string {
  $candidates = [',',';','	','|'];
  $best = ','; $bestCount = -1;
  foreach ($candidates as $d) {
    $cnt = substr_count($line, $d);
    if ($cnt > $bestCount) { $best = $d; $bestCount = $cnt; }
  }
  return $best;
}

function normalize_fuel(?string $s): ?string {
  if ($s===null) return null;
  $s = strtolower(trim($s));
  if ($s==='essence' || $s==='petrol' || $s==='gasoline') return 'petrol';
  if ($s==='diesel' || $s==='gasoil') return 'diesel';
  if (str_contains($s,'hybr')) return 'hybrid';
  if (in_array($s, ['elec','electric','ev','électrique'])) return 'electric';
  return 'other';
}

$columns = [
  'make','model','year','variant','power_kw','weight_kg','zero_to_100_s','zero_to_200_s','fuel','co2_gpkm','wltp_l100','price_eur','tax_eur','image_url'
];

$status = null; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $tmp = $_FILES['csv']['tmp_name'];
  $fh = fopen($tmp, 'r');
  if (!$fh) { $errors[] = 'Impossible de lire le fichier'; }
  else {
    $first = fgets($fh);
    if ($first===false) { $errors[] = 'Fichier vide'; }
    else {
      $delim = detect_delimiter($first);
      $header = str_getcsv($first, $delim);
      $map = [];
      foreach ($header as $i=>$h) {
        $hh = strtolower(trim($h));
        if (in_array($hh, $columns, true)) $map[$i] = $hh;
      }
      if (!isset($map) || count($map)===0) {
        $errors[] = 'En-têtes CSV invalides. Colonnes attendues: '.implode(', ',$columns);
      } else {
        $pdo->beginTransaction();
        $sql = "INSERT INTO cars (
                  make,model,year,variant,slug,
                  power_kw,weight_kg,zero_to_100_s,zero_to_200_s,fuel,co2_gpkm,wltp_l100,price_eur,tax_eur,image_url
                ) VALUES (
                  :make,:model,:year,:variant,:slug,
                  :power_kw,:weight_kg,:z100,:z200,:fuel,:co2,:wltp,:price,:tax,:img
                )
                ON DUPLICATE KEY UPDATE
                  weight_kg=VALUES(weight_kg),
                  zero_to_100_s=VALUES(zero_to_100_s),
                  zero_to_200_s=VALUES(zero_to_200_s),
                  co2_gpkm=VALUES(co2_gpkm),
                  wltp_l100=VALUES(wltp_l100),
                  price_eur=VALUES(price_eur),
                  tax_eur=VALUES(tax_eur),
                  image_url=VALUES(image_url)";
        $stmt = $pdo->prepare($sql);
        $count = 0; $lineNo = 1;
        fseek($fh, 0);

        while(($row = fgetcsv($fh, 0, $delim)) !== false){
          $lineNo++;
          if (count($row)===1 && trim($row[0])==='') continue;

          $data = array_fill_keys($columns, null);
          foreach($row as $i=>$value){
            if (!isset($map[$i])) continue;
            $key = $map[$i];
            $data[$key] = ($value === '' ? null : trim($value));
          }

          $data['year']          = $data['year']!==null ? (int)$data['year'] : null;
          $data['power_kw']      = $data['power_kw']!==null ? (int)$data['power_kw'] : null;
          $data['weight_kg']     = $data['weight_kg']!==null ? (int)$data['weight_kg'] : null;
          $data['zero_to_100_s'] = $data['zero_to_100_s']!==null ? (float)str_replace(',', '.', $data['zero_to_100_s']) : null;
          $data['zero_to_200_s'] = $data['zero_to_200_s']!==null ? (float)str_replace(',', '.', $data['zero_to_200_s']) : null;
          $data['co2_gpkm']      = $data['co2_gpkm']!==null ? (int)$data['co2_gpkm'] : null;
          $data['wltp_l100']     = $data['wltp_l100']!==null ? (float)str_replace(',', '.', $data['wltp_l100']) : null;
          $data['price_eur']     = $data['price_eur']!==null ? (int)$data['price_eur'] : null;
          $data['tax_eur']       = $data['tax_eur']!==null ? (int)$data['tax_eur'] : null;
          $data['fuel']          = normalize_fuel($data['fuel']);

          if ($data['variant']  === null) $data['variant']  = '';
          if ($data['fuel']     === null) $data['fuel']     = 'other';
          if ($data['power_kw'] === null) $data['power_kw'] = 0;

          $slug = slugify(
            ($data['make']??'').'-'.($data['model']??'').'-'.($data['year']??'').
            '-'.(($data['variant']!=='')?$data['variant']:'base').
            '-'.($data['fuel']??'other').'-'.($data['power_kw']??0)
          );

          try {
            $stmt->execute([
              ':make'=>$data['make'],
              ':model'=>$data['model'],
              ':year'=>$data['year'],
              ':variant'=>$data['variant'],
              ':slug'=>$slug,
              ':power_kw'=>$data['power_kw'],
              ':weight_kg'=>$data['weight_kg'],
              ':z100'=>$data['zero_to_100_s'],
              ':z200'=>$data['zero_to_200_s'],
              ':fuel'=>$data['fuel'],
              ':co2'=>$data['co2_gpkm'],
              ':wltp'=>$data['wltp_l100'],
              ':price'=>$data['price_eur'],
              ':tax'=>$data['tax_eur'],
              ':img'=>$data['image_url'],
            ]);
            $count++;
          } catch (Throwable $e) {
            $errors[] = 'Ligne '.$lineNo.' : '.$e->getMessage();
          }
        }
        fclose($fh);

        if (empty($errors)) { $pdo->commit(); $status = $count.' lignes importées/actualisées.'; }
        else { $pdo->rollBack(); }
      }
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Importer des véhicules (CSV)</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css" />
</head>
<body>
  <main class="container grid" style="margin-top:24px;">
    <section class="card">
      <h1 style="margin:0;">Importer des véhicules (CSV)</h1>
      <p class="label">
        Colonnes acceptées (ordre libre) :
        <code>make, model, year, variant, power_kw, weight_kg, zero_to_100_s, zero_to_200_s, fuel, co2_gpkm, wltp_l100, price_eur, tax_eur, image_url</code>
      </p>
      <?php if ($status): ?><p class="value" style="color:#8df08d;">✅ <?= e($status) ?></p><?php endif; ?>
      <?php if ($errors): ?>
        <div class="card" style="background:#2c1420; border-color:#7a2b41;">
          <div class="value">⚠️ Erreurs (import annulé)</div>
          <ul>
            <?php foreach($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" class="grid" style="grid-template-columns:1fr auto; gap:10px; margin-top:12px;">
        <input class="input" type="file" name="csv" accept=".csv,text/csv" required />
        <button class="btn" type="submit">Importer</button>
      </form>

      <details style="margin-top:12px;">
        <summary class="label">Exemple de CSV (séparateur virgule)</summary>
<pre style="white-space:pre-wrap;">
make,model,year,variant,power_kw,weight_kg,zero_to_100_s,zero_to_200_s,fuel,co2_gpkm,wltp_l100,price_eur,tax_eur,image_url
Peugeot,208,2022,1.2 PureTech 100,74,1150,10.2,,petrol,118,5.2,22000,0,https://images.unsplash.com/photo-1542362567-b07e54358753?q=80&w=1200&auto=format&fit=crop
Audi,RS5,2019,3.0 TFSI Quattro,331,1710,3.9,13.3,petrol,199,8.7,98000,5000,https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?q=80&w=1200&auto=format&fit=crop
</pre>
      </details>

      <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn" href="<?= e(BASE_URL) ?>/admin/">← Retour admin</a>
        <a class="btn" href="<?= e(BASE_URL) ?>/index.php">← Retour au site</a>
      </div>
    </section>
  </main>
</body>
</html>
