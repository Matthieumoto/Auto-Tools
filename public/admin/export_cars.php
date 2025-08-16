<?php

require_once __DIR__.'/../../inc/auth.php';
auth_require_admin();
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cars_export.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['make','model','year','variant','power_kw','weight_kg','zero_to_100_s','zero_to_200_s','fuel','co2_gpkm','wltp_l100','price_eur','tax_eur','image_url']);

global $pdo;
$q = $pdo->query("SELECT make,model,year,variant,power_kw,weight_kg,zero_to_100_s,zero_to_200_s,fuel,co2_gpkm,wltp_l100,price_eur,tax_eur,image_url FROM cars ORDER BY make, model, year");
while($row = $q->fetch(\PDO::FETCH_NUM)){ fputcsv($out, $row); }
fclose($out);
