<?php
require_once __DIR__.'/../inc/auth.php';

$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($email === '' || $password === '') {
    $err = 'Email et mot de passe requis.';
  } elseif (!auth_login($email, $password)) {
    $err = 'Identifiants invalides.';
  } else {
    header('Location: '.rtrim(BASE_URL,'/').'/profile.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Connexion — AutoTools</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css"/>
</head>
<body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Connexion</h1>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>
    <form method="post" class="grid" style="gap:10px;">
      <input class="input" type="email" name="email" placeholder="Email" value="<?= e($_POST['email'] ?? '') ?>" required/>
      <input class="input" type="password" name="password" placeholder="Mot de passe" required/>
      <button class="btn" type="submit">Se connecter</button>
    </form>
    <p class="label" style="margin-top:8px;">Nouveau ? <a href="<?= e(BASE_URL) ?>/register.php">Créer un compte</a></p>
  </section>
</main>
</body>
</html>
