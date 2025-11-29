<?php
include('../Conection/conexion.php'); 
header('Content-Type: application/json');

if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$search = $_GET['q'];

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$search = $conn->real_escape_string($search);
$sql = "SELECT palabra FROM PalabrasBuscadas 
        WHERE palabra LIKE '$search%' 
        ORDER BY contador DESC 
        LIMIT 10";

$result = $conn->query($sql);

$words = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $words[] = $row['palabra'];
    }
}

echo json_encode($words);

$conn->close();
?>