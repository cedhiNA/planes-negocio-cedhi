<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../Accesos/auth_central.php";

$estaAutenticado = validarAutenticacionCentral();
if (!$estaAutenticado) {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/");
    exit();
}
$usuarioData = $estaAutenticado ? obtenerUsuarioCentral() : null;
$rol = $estaAutenticado ? strtolower($usuarioData['rol']) : null;

include_once('../Conection/conexion.php');

$order_by = "Fecha_publicacion DESC";  

if (isset($_GET['orden'])) {
    switch ($_GET['orden']) {
        case 'fecha_asc': $order_by = "Fecha_publicacion ASC"; break;
        case 'fecha_desc': $order_by = "Fecha_publicacion DESC"; break;
        case 'titulo_asc': $order_by = "Titulo ASC"; break;
        case 'titulo_desc': $order_by = "Titulo DESC"; break;
        case 'visualizaciones_asc': $order_by = "Visualizaciones ASC"; break;
        case 'visualizaciones_desc': $order_by = "Visualizaciones DESC"; break;
    }
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$carrera_filtro = isset($_GET['carrera']) ? intval($_GET['carrera']) : 0;

$sql = "SELECT t.*, c.Nombre AS NombreCarrera
        FROM Tesis t
        JOIN Carrera c ON t.Carrera_ID = c.ID";

if ($carrera_filtro > 0) {
    $sql .= " WHERE t.Carrera_ID = $carrera_filtro";
}

$sql .= " ORDER BY $order_by";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

$sql_carreras = "SELECT ID, Nombre FROM Carrera ORDER BY Nombre";
$result_carreras = $conn->query($sql_carreras);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Repositorio de Planes de Negocios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="../Imagenes/logo_cedhi_claro.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../estilos.css">

    <style>
    .main-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
        padding: 30px;
        margin-bottom: 40px;
    }

    .page-header {
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .page-title {
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 10px;
    }

    .filter-section {
        background-color: var(--light-color);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .filter-label {
        font-weight: 600;
        margin-right: 10px;
        color: var(--secondary-color);
    }

    .form-select {
        border-radius: 6px;
        border: 1px solid #ddd;
        padding: 8px 15px;
        transition: all 0.3s ease;
    }

    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }

    .btn-refresh {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-refresh:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    .thesis-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        overflow: hidden;
    }

    .thesis-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .card-body {
        padding: 25px;
    }

    .card-title {
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 15px;
        font-size: 1.3rem;
    }

    .card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .meta-item {
        display: flex;
        align-items: center;
        font-size: 0.9rem;
        color: #6c757d;
    }

    .meta-item i {
        margin-right: 5px;
        color: var(--primary-color);
    }

    .card-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-details {
        background-color: white;
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .btn-details:hover {
        background-color: var(--primary-color);
        color: white;
    }

    .btn-pdf {
        background-color: var(--secondary-color);
        border-color: var(--secondary-color);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-pdf:hover {
        background-color: #5e1491;
        border-color: #5e1491;
    }

    .collapse-content {
        padding: 20px;
        background-color: var(--light-color);
        border-top: 1px solid #eee;
    }

    .section-title {
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 10px;
    }

    .keywords {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .keyword {
        background-color: var(--primary-color);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #e0e0e0;
    }

    .career-badge {
        padding: 6px 12px;
        font-size: 0.85rem;
        font-weight: bold;
        border-radius: 20px;
        color: white;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    .badge-0 {
        background-color: #e74c3c;
    }

    .badge-1 {
        background-color: #2980b9;
    }

    .badge-2 {
        background-color: #27ae60;
    }

    .badge-3 {
        background-color: #8e44ad;
    }

    .badge-4 {
        background-color: #d35400;
    }

    .badge-5 {
        background-color: #16a085;
    }

    .badge-6 {
        background-color: #2c3e50;
    }

    .badge-7 {
        background-color: #f39c12;
    }

    .badge-8 {
        background-color: #c0392b;
    }

    .badge-9 {
        background-color: #34495e;
    }

    @media (max-width: 768px) {
        .card-actions {
            flex-direction: column;
        }

        .filter-section {
            padding: 15px;
        }

        .card-bottom {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
    }
    </style>
</head>

<body>
    <?php include_once '../navbar.php'; ?>

    <div class="container main-container">
        <div class="page-header">
            <h1 class="page-title">Lista de Planes de Negocios</h1>
        </div>

        <div class="filter-section">
            <form class="row g-3 align-items-center" method="GET">
                <div class="col-md-4">
                    <label for="orden" class="filter-label col-form-label">Ordenar por:</label>
                    <select name="orden" id="orden" class="form-select" onchange="this.form.submit()">
                        <option value="fecha_desc" <?= ($_GET['orden'] ?? '') === 'fecha_desc' ? 'selected' : '' ?>>
                            Fecha (más recientes)</option>
                        <option value="fecha_asc" <?= ($_GET['orden'] ?? '') === 'fecha_asc' ? 'selected' : '' ?>>Fecha
                            (más antiguas)</option>
                        <option value="titulo_asc" <?= ($_GET['orden'] ?? '') === 'titulo_asc' ? 'selected' : '' ?>>
                            Título (A-Z)</option>
                        <option value="titulo_desc" <?= ($_GET['orden'] ?? '') === 'titulo_desc' ? 'selected' : '' ?>>
                            Título (Z-A)</option>
                        <option value="visualizaciones_asc"
                            <?= ($_GET['orden'] ?? '') === 'visualizaciones_asc' ? 'selected' : '' ?>>Visualizaciones
                            (menor primero)</option>
                        <option value="visualizaciones_desc"
                            <?= ($_GET['orden'] ?? '') === 'visualizaciones_desc' ? 'selected' : '' ?>>Visualizaciones
                            (mayor primero)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="carrera" class="filter-label col-form-label">Filtrar por carrera:</label>
                    <select name="carrera" id="carrera" class="form-select" onchange="this.form.submit()">
                        <option value="0">Todas</option>
                        <?php while ($row_carrera = $result_carreras->fetch_assoc()): ?>
                        <option value="<?= $row_carrera['ID'] ?>"
                            <?= ($carrera_filtro == $row_carrera['ID']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row_carrera['Nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="thesis-list">
            <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                    $carrera = $row['NombreCarrera'];
                    $color_index = $row['Carrera_ID'] % 10;

                    $tesis_id = $row['ID'];
                    $sql_palabras = "SELECT Palabra FROM PalabraClave
                                     JOIN TesisPalabraClave ON PalabraClave.ID = TesisPalabraClave.PalabraClave_ID
                                     WHERE TesisPalabraClave.Tesis_ID = ?";
                    $stmt_palabras = $conn->prepare($sql_palabras);
                    $stmt_palabras->bind_param("i", $tesis_id);
                    $stmt_palabras->execute();
                    $result_palabras = $stmt_palabras->get_result();

                    $palabras_clave = [];
                    while ($row_palabra = $result_palabras->fetch_assoc()) {
                        $palabras_clave[] = $row_palabra['Palabra'];
                    }
                    ?>
            <div class="card thesis-card">
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($row['Titulo']) ?></h3>

                    <div class="card-meta">
                        <span class="meta-item">
                            <i class="far fa-calendar-alt"></i>
                            <?= htmlspecialchars($row['Fecha_publicacion']) ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-chart-line"></i>
                            <?= htmlspecialchars($row['Visualizaciones']) ?> visualizaciones
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-info-circle"></i>
                            <?= htmlspecialchars($row['Estado']) ?>
                        </span>
                    </div>

                    <div class="card-bottom">
                        <div class="card-actions">
                            <button class="btn btn-details" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detalles<?= $tesis_id ?>" aria-expanded="false">
                                <i class="fas fa-chevron-down"></i> Detalles
                            </button>
                            <a href="../Conection/ver_pdf.php?id=<?= $tesis_id ?>" target="_blank" class="btn btn-pdf">
                                <i class="fas fa-file-pdf"></i> Ver PDF
                            </a>
                        </div>
                        <div class="career-badge badge-<?= $color_index ?>">
                            <?= htmlspecialchars($carrera) ?>
                        </div>
                    </div>

                    <div class="collapse" id="detalles<?= $tesis_id ?>">
                        <div class="collapse-content mt-3">
                            <h5 class="section-title">Resumen</h5>
                            <p><?= nl2br(htmlspecialchars($row['Resumen'])) ?></p>

                            <h5 class="section-title">Palabras Clave</h5>
                            <div class="keywords">
                                <?php foreach ($palabras_clave as $palabra): ?>
                                <span class="keyword"><?= htmlspecialchars($palabra) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                $stmt_palabras->close();
                endwhile; ?>
            <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-book-open me-2"></i>
                No hay planes de negocio registrados actualmente.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Repositorio de Planes de Negocios - Todos los derechos reservados</p>
        </div>
    </footer>

    <?php $conn->close(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>