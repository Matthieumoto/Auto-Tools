<?php
require_once __DIR__.'/../config.php';

/**
 * Envoie un email. En local, on logge dans storage/mail.log
 * MAIL_MODE: 'log' (dev) ou 'php' (mail()).
 */
function mail_send(string $to, string $subject, string $html, ?string $text = null): bool {
  if (defined('MAIL_MODE') && MAIL_MODE === 'log') {
    $dir = __DIR__.'/../storage';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $line = "TO: $to\nSUBJECT: $subject\nHTML:\n$html\n\nTEXT:\n".($text??'')."\n----\n";
    file_put_contents($dir.'/mail.log', $line, FILE_APPEND);
    return true;
  }

  // MODE 'php' : nécessite config SMTP de PHP sur XAMPP
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: ".(defined('MAIL_FROM') ? MAIL_FROM : 'AutoTools <noreply@autotools.local>')."\r\n";
  $ok = @mail($to, '=?UTF-8?B?'.base64_encode($subject).'?=', $html, $headers);
  return (bool)$ok;
}
