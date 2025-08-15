<?php
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function euro(?int $n): string { return is_null($n) ? '—' : number_format($n, 0, ',', ' ') . ' €'; }
function num(?float $n, int $dec = 2): string { return is_null($n) ? '—' : number_format($n, $dec, ',', ' '); }
function hp_from_kw(?int $kw): ?int { return is_null($kw) ? null : (int) round($kw * 1.34102); }
function kw_per_tonne(?int $kw, ?int $kg): ?float {
  if (is_null($kw) || is_null($kg) || $kg <= 0) return null;
  return ($kw) / ($kg / 1000.0);
}
if (!function_exists('slugify')) {
  function slugify(string $s): string {
    $s = trim($s);
    // translit (si dispo)
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
?>