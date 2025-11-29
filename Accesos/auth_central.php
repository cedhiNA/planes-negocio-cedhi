<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setcookie('auth_token', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'None'
]);

function validarAutenticacionCentral() {
    $token = $_COOKIE['auth_token'] ?? ($_GET['token'] ?? null);

    if (!empty($token)) {
        try {
            $key = 'cedhi2024biblio';
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $userData = (array) $decoded;

            session_unset();
            $_SESSION['user_id']             = $userData['userId'] ?? null;
            $_SESSION['user_email_address']  = $userData['email'] ?? null;
            $_SESSION['user_first_name']     = $userData['nombre'] ?? null;
            $_SESSION['user_last_name']      = $userData['apellido'] ?? null;
            $_SESSION['role']                = strtolower($userData['rol'] ?? '');

            if (isset($_GET['token'])) {
                setcookie('auth_token', $token, [
                    'expires' => time() + 3600,
                    'path' => '/',
                    'secure'   => true,
                    'httponly' => true,
                    'samesite' => 'None'
                ]);

                $redirect = strtok($_SERVER["REQUEST_URI"], '?');
                header("Location: $redirect");
                exit;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    return false;
}


function obtenerUsuarioCentral() {
    if (!validarAutenticacionCentral()) {
        return null;
    }

    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'email'    => $_SESSION['user_email_address'] ?? null,
        'nombre'   => $_SESSION['user_first_name'] ?? null,
        'apellido' => $_SESSION['user_last_name'] ?? null,
        'rol'      => $_SESSION['role'] ?? null
    ];
}
?>