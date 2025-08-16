<?php
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/auth.php';
require_once __DIR__.'/../inc/mail.php';

$err = null;

/**
 * Envoie un lien de vérification à l'utilisateur $userId.
 * Retourne true si l'email a été loggé/envoyé.
 */
function send_verification_link(PDO $pdo, int $userId): bool {
  if ($userId <= 0) return false;

  $tok = bin2hex(random_bytes(32));
  $s = $pdo->prepare("UPDATE users SET verify_token=? WHERE id=?");
  $s->execute([$tok, $userId]);

  $u = $pdo->prepare("SELECT email, name FROM users WHERE id=? LIMIT 1");
  $u->execute([$userId]);
  $row = $u->fetch();
  if (!$row) return false;

  $url  = rtrim(BASE_URL, '/').'/verify_email.php?token='.$tok;
  $name = htmlspecialchars($row['name'] ?? '');
  $html = "<p>Salut {$name},</p>
           <p>Confirme ton adresse email en cliquant ici :</p>
           <p><a href=\"{$url}\">Vérifier mon email</a></p>
           <p>Si tu n’es pas à l’origine de cette inscription, ignore ce message.</p>";

  return mail_send((string)$row['email'], "Vérifie ton email • AutoTools", $html);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name      = trim($_POST['name'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $password  = $_POST['password']  ?? '';
  $password2 = $_POST['password2'] ?? '';

  if ($name === '' || $email === '' || $password === '' || $password2 === '') {
    $err = 'Tous les champs sont requis.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = "Email invalide";
  } elseif (strlen($password) < 6) {
    $err = 'Mot de passe trop court (6+).';
  } elseif ($password !== $password2) {
    $err = 'Les mots de passe ne correspondent pas.';
  } else {
    // Tente l’inscription
    [$ok, $msg] = auth_register($name, $email, $password);

    if ($ok) {
      // Récupère l'ID du nouvel utilisateur.
      // Si auth_register a fait l'INSERT via le même $pdo, lastInsertId() > 0.
      $uid = (int)$pdo->lastInsertId();

      // Fallback: si 0, récupère par l'email (cas de drivers/configs spécifiques)
      if ($uid <= 0) {
        $q = $pdo->prepare("SELECT id FROM users WHERE email = ? ORDER BY id DESC LIMIT 1");
        $q->execute([$email]);
        $uid = (int)$q->fetchColumn();
      }

      if ($uid > 0) {
        // Envoie le lien de vérification (log en local si MAIL_MODE=log)
        send_verification_link($pdo, $uid);
      }

      // Redirige vers le profil
      header('Location: '.rtrim(BASE_URL,'/').'/profile.php');
      exit;
    } else {
      $err = $msg; // message renvoyé par auth_register (ex: email déjà pris)
    }
  }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Inscription — AutoTools</title>
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css?v=8"/>
</head>
<body>
<main class="container grid" style="margin-top:24px;">
  <section class="card">
    <h1 style="margin:0;">Créer un compte</h1>
    <?php if ($err): ?><p class="value" style="color:#ff9aa2;">⚠️ <?= e($err) ?></p><?php endif; ?>
    <form method="post" class="grid" style="gap:10px;">
      <input class="input" name="name" placeholder="Ton nom" value="<?= e($_POST['name'] ?? '') ?>" required/>
      <input class="input" type="email" name="email" placeholder="ton@email" value="<?= e($_POST['email'] ?? '') ?>" required/>
      <input class="input" type="password" name="password" placeholder="Mot de passe (6+)" required/>
      <input class="input" type="password" name="password2" placeholder="Confirme le mot de passe" required/>
      <button class="btn" type="submit">S'inscrire</button>
    </form>
    <p class="label" style="margin-top:8px;">Déjà inscrit ? <a href="<?= e(BASE_URL) ?>/login.php">Connexion</a></p>
  </section>
</main>
</body>
</html>
