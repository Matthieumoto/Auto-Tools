<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'autotools');
define('DB_USER', 'root');
define('DB_PASS', '');

// IMPORTANT : dossier sans espace => "auto-tools"
define('BASE_URL', 'http://localhost/auto-tools/public');
// ---- Emails
define('MAIL_FROM', 'AutoTools <noreply@autotools.local>');
define('MAIL_MODE', 'log'); // en local; passera à 'php' ou SMTP en prod