<?php
include_once "../Accesos/auth_central.php";

if (!validarAutenticacionCentral()) {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/");
    exit();
}

$usuarioData = obtenerUsuarioCentral();
$rol = $usuarioData['rol'];

if ($rol !== 'admin' && $rol !== 'owner') {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/?error=permisos");
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['rol'] = $rol;
$_SESSION['usuario_id'] = $usuarioData['id'];

include_once "../Conection/conexion.php";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexi贸n fallida: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM Tesis WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: gestionar_tesis.php");
        exit();
    } else {
        echo "Error al eliminar la tesis: " . $conn->error;
    }
} else {
    die("ID de tesis no especificado.");
}

$conn->close();
?>