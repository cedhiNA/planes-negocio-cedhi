<?php
$servername = "localhost";  
$username   = "";          
$password   = "";       
$dbname     = "cedhinue_planes_db";  

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}