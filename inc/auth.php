<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/helpers.php';

// Démarrer la session si besoin
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function auth_current_user(): ?array {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (!isset($_SESSION['user'])) return null;
  global $pdo;
  $s = $pdo->prepare('SELECT name, email, username FROM users WHERE id = ? LIMIT 1');
  $s->execute([$_SESSION['user']['id']]);
  if ($row = $s->fetch()) {
    $_SESSION['user']['name'] = $row['name'];
    $_SESSION['user']['email'] = $row['email'];
    $_SESSION['user']['username'] = $row['username'];
  }
  return $_SESSION['user'];
}

function auth_login(string $email, string $password): bool {
  global $pdo;
  $stmt = $pdo->prepare('SELECT id, name, email, username, password_hash FROM users WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if (!$u) return false;
  if (!password_verify($password, $u['password_hash'])) return false;
  $_SESSION['user'] = [
    'id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'],
    'username' => $u['username'] ?? null
  ];
  return true;
}

function auth_register(string $name, string $email, string $password): array {
  global $pdo;
  // Vérif email unique
  $exists = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
  $exists->execute([$email]);
  if ($exists->fetchColumn()) {
    return [false, 'Cet email est déjà utilisé.'];
  }
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $ins = $pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES (?,?,?)');
  $ins->execute([$name,$email,$hash]);
  $uid = (int)$pdo->lastInsertId();
  $_SESSION['user'] = [ 'id'=>$uid, 'name'=>$name, 'email'=>$email ];
  // Créer le profil si absent
  $pdo->prepare('INSERT IGNORE INTO user_profile(user_id, selected_car_id) VALUES (?, NULL)')->execute([$uid]);
  return [true, null];
}

function auth_logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function auth_require_login(): void {
  if (!auth_current_user()) {
    header('Location: '.rtrim(BASE_URL,'/').'/login.php');
    exit;
  }
}

function auth_require_admin(): void {
  $u = auth_current_user();
  if (!$u) { header('Location: '.rtrim(BASE_URL,'/').'/login.php'); exit; }
  global $pdo;
  $s = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
  $s->execute([$u['id']]);
  $isAdmin = (int)$s->fetchColumn() === 1;
  if (!$isAdmin) { http_response_code(403); echo 'Accès réservé à l’admin.'; exit; }
}

?>