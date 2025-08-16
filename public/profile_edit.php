<?php
require_once __DIR__.'/../inc/auth.php';
auth_require_login();
$me = auth_current_user();

$COOLDOWN_DAYS = 15;
$err = null; $ok = null;

/* Charger les valeurs actuelles */
$stmt = $pdo->prepare("SELECT u.username, u.username_changed_at,
                              up.gender, up.birth_year, up.city, up.country, up.lat, up.lng, up.is_public
                       FROM users u
                       LEFT JOIN user_profile up ON up.user_id = u.id
                       WHERE u.id = ?");
$stmt->execute([$me['id']]);
$cur = $stmt->fetch() ?: [
  'username'=>null,'username_changed_at'=>null,
  'gender'=>null,'birth_year'=>null,'city'=>null,'country'=>null,'lat'=>null,'lng'=>null,'is_public'=>0
];

/* Soumission */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $username   = trim($_POST['username'] ?? '');
  $gender     = $_POST['gender'] ?? null;                   // male/female/other/na
  $birth_year = $_POST['birth_year'] !== '' ? (int)$_POST['birth_year'] : null;
  $city       = trim($_POST['city'] ?? '');
  $country    = trim($_POST['country'] ?? '');
  $lat        = $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
  $lng        = $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;
  $is_public  = isset($_POST['is_public']) ? 1 : 0;

  // --- Validations ---
  // Pseudo (facultatif, mais si fourni => format + dispo + cooldown)
  if ($username !== '') {
    if (!preg_match('/^[a-z0-9._-]{3,20}$/i', $username)) {
      $err = "Le pseudo doit faire 3–20 caractères (lettres, chiffres, . _ -).";
    }
  }

  // Cooldown changement pseudo
  if (!$err && $username !== ($cur['username'] ?? '')) {
    if ($cur['username_changed_at']) {
      $next = strtotime($cur['username_changed_at'].' +'.$COOLDOWN_DAYS.' days');
      $now  = time();
      if ($now < $next) {
        $daysLeft = ceil(($next - $now)/86400);
        $err = "Tu pourras rechanger de pseudo dans $daysLeft jour(s).";
      }
    }
    // Unicité
    if (!$err && $username !== '') {
      $c = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND id <> ?");
      $c->execute([$username, $me['id']]);
      if ($c->fetchColumn()) $err = "Ce pseudo est déjà pris.";
    }
  }

  // Gender check
  if (!$err && $gender !== null && !in_array($gender, ['male','female','other','na'], true)) {
    $err = "Genre invalide.";
  }

  // Birth year
  if (!$err && $birth_year !== null && ($birth_year < 1900 || $birth_year > (int)date('Y'))) {
    $err = "Année de naissance invalide.";
  }

  if (!$err) {
    $pdo->beginTransaction();
    try {
      // MAJ users (username + changed_at si changé)
      if ($username !== ($cur['username'] ?? '')) {
        $u = $pdo->prepare("UPDATE users SET username = ?, username_changed_at = NOW() WHERE id = ?");
        $u->execute([$username ?: null, $me['id']]);
        // aussi mettre à jour la session (affichage header)
        $_SESSION['user']['name'] = $_SESSION['user']['name']; // no-op, mais on pourrait afficher username si tu veux
      }

      // UPSERT user_profile
      $up = $pdo->prepare("
        INSERT INTO user_profile (user_id, selected_car_id, gender, birth_year, city, country, lat, lng, is_public)
        VALUES (:uid, NULL, :gender, :birth_year, :city, :country, :lat, :lng, :is_public)
        ON DUPLICATE KEY UPDATE
          gender=VALUES(gender), birth_year=VALUES(birth_year),
          city=VALUES(city), country=VALUES(country),
          lat=VALUES(lat), lng=VALUES(lng), is_public=VALUES(is_public)
      ");
      $up->execute([
        ':uid'=>$me['id'],
        ':gender'=>$gender,
        ':birth_year'=>$birth_year,
        ':city'=>$city ?: null,
        ':country'=>$country ?: null,
        ':lat'=>$lat,
        ':lng'=>$lng,
        ':is_public'=>$is_public
      ]);

      $pdo->commit();
      $ok = "Profil mis à jour.";
      // recharger valeurs
      $stmt->execute([$me['id']]); $cur = $stmt->fetch();
    } catch (Throwable $e) {
      $pdo->rollBack();
      $err = "Erreur: ".$e->getMessage();
    }
  }
}

// helper âge
$age = null;
if ($cur['birth_year']) $age = (int)date('Y') - (int)$cur['birth_year'];

// cooldown restant
$cooldownLeft = null;
if ($cur['username_changed_at']) {
  $next = strtotime($cur['username_changed_at'].' +'.$COOLDOWN_DAYS.' days');
  if (time() < $next) $cooldownLeft = ceil(($next - time())/86400);
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Éditer le profil — AutoTools</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css"/>
</head>
<body>
<header class="header">
  <div class="container" style="display:flex; align-items:center; height:64px;">
    <div class="brand">
      <div class="badge">AT</div>
      <div>
        <div style="font-weight:800">AutoTools</div>
        <div class="label">Éditer le profil</div>
      </div>
    </div>
    <a class="btn" href="<?= e(BASE_URL) ?>/index.php">Accueil</a>
    <a class="btn" href="<?= e(BASE_URL) ?>/profile.php">Profil</a>
    <a class="btn" href="<?= e(BASE_URL) ?>/logout.php">Déconnexion</a>
  </div>
</header>

<main class="container grid" style="margin-top:16px;">
  <section class="card">
    <h1 style="margin:0;">Modifier ton profil</h1>
    <?php if ($ok): ?><p class="value" style="color:#8df08d;">✅ <?= e($ok) ?></p><?php endif; ?>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>

    <form method="post" class="grid" style="gap:12px;">
      <div class="grid grid-3">
        <div>
          <div class="label">Pseudo (visible publiquement)</div>
          <input class="input" name="username" placeholder="ex: matthieu_k" value="<?= e($cur['username'] ?? '') ?>"/>
          <div class="label">
            Format: 3–20 (lettres, chiffres, . _ -)
            <?php if ($cooldownLeft): ?> • Tu pourras rechanger dans <?= (int)$cooldownLeft ?> j.<?php endif; ?>
          </div>
        </div>

        <div>
          <div class="label">Genre</div>
          <select class="input" name="gender">
            <option value="">—</option>
            <option value="male"   <?= ($cur['gender']==='male'?'selected':'') ?>>Homme</option>
            <option value="female" <?= ($cur['gender']==='female'?'selected':'') ?>>Femme</option>
            <option value="other"  <?= ($cur['gender']==='other'?'selected':'') ?>>Autre</option>
            <option value="na"     <?= ($cur['gender']==='na'?'selected':'') ?>>Préférer ne pas dire</option>
          </select>
        </div>

        <div>
          <div class="label">Année de naissance</div>
          <input class="input" type="number" name="birth_year" min="1900" max="<?= date('Y') ?>" value="<?= e($cur['birth_year'] ?? '') ?>"/>
          <div class="label"><?= $age? 'Âge approx. : '.$age.' ans' : '' ?></div>
        </div>
      </div>

      <div class="grid grid-3">
        <div>
          <div class="label">Ville</div>
          <input class="input" name="city" value="<?= e($cur['city'] ?? '') ?>"/>
        </div>
        <div>
          <div class="label">Pays</div>
          <input class="input" name="country" value="<?= e($cur['country'] ?? '') ?>"/>
        </div>
        <div>
          <div class="label">Coordonnées (lat, lng) — optionnel</div>
          <div class="grid grid-2" style="grid-template-columns:1fr 1fr;">
            <input class="input" name="lat" value="<?= e($cur['lat'] ?? '') ?>" placeholder="48.8566"/>
            <input class="input" name="lng" value="<?= e($cur['lng'] ?? '') ?>" placeholder="2.3522"/>
          </div>
          <div class="label">Utile plus tard pour “le meilleur près de chez toi”.</div>
        </div>
      </div>

      <label class="label" style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="is_public" <?= $cur['is_public']? 'checked':'' ?> />
        Rendre mon profil visible aux autres utilisateurs (pseudo, ville/pays et véhicule principal).
      </label>

      <div class="card" style="background:#11162e; border-color:#32365d;">
        <div class="label">
          🔒 <b>Vie privée :</b> ces infos servent à afficher ton profil et des classements locaux.
          Tu peux laisser vide ce que tu ne veux pas partager. En rendant ton profil public,
          tu consens à l’affichage de ces infos aux autres utilisateurs d’AutoTools.
        </div>
      </div>

      <div>
        <button class="btn" type="submit">Enregistrer</button>
        <a class="btn" href="<?= e(BASE_URL) ?>/profile.php" style="background:#40456b;">Annuler</a>
      </div>
    </form>
  </section>
</main>
</body>
</html>
