<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../Conection/conexion.php');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];

    $sql = "UPDATE Tesis SET Visualizaciones = Visualizaciones + 1 WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en prepare: " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Error en execute: " . $stmt->error);
    }

    $sql = "SELECT Archivo_pdf FROM Tesis WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error en prepare (select): " . $conn->error);
    }
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        die("Error en execute (select): " . $stmt->error);
    }
    $stmt->bind_result($ruta);
    $stmt->fetch();
    $stmt->close();

    $base_dir = __DIR__ . "/../Archivos/";
    $rutaCompleta = $base_dir . $ruta;
    
    if ($ruta && file_exists($rutaCompleta)) {
        $rutaUrl = '../Archivos/' . $ruta;  
        header("Location: $rutaUrl");
        exit();
    } else {
        echo "PDF no encontrado o ruta inválida.";
    }


} else {
    echo "ID de tesis no especificado o inválido.";
}
?>