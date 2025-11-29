<?php
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();

setcookie('auth_token', '', [
  'expires' => time() - 3600,
  'path' => '/',
  'secure' => true,
  'httponly' => true,
  'samesite' => 'None'
]);

header('Location: https://biblioteca.cedhinuevaarequipa.edu.pe/');
exit;
?>