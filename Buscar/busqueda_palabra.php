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

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$searchparam = isset($_GET['searchparam']) ? trim($_GET['searchparam']) : '';
$searchparam_sql = $conn->real_escape_string($searchparam);

$carrera_id = isset($_GET['carrera_id']) ? intval($_GET['carrera_id']) : 0;

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

function normalizarTexto($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto);
    return preg_replace('/[^a-z0-9\s]/i', '', $texto);
}

$stopwords = ['el', 'la', 'los', 'las', 'de', 'del', 'y', 'en', 'un', 'una', 'que', 'con', 'para', 'por', 'al', 'a'];

$normalizado = normalizarTexto($searchparam_sql);
$tokens = array_filter(explode(' ', $normalizado), function ($t) use ($stopwords) {
    return strlen($t) > 2 && !in_array($t, $stopwords);
});

$where_clauses = [];

foreach ($tokens as $word) {
    $escaped_word = $conn->real_escape_string($word);

    if (is_numeric($word)) {
        $where_clauses[] = "(
            LOWER(Titulo) LIKE '%$escaped_word%' OR 
            LOWER(Fecha_Publicacion) LIKE '%$escaped_word%')";
    } else {
        $where_clauses[] = "(
            LOWER(Titulo) LIKE '%$escaped_word%' OR 
            LOWER(Estado) LIKE '%$escaped_word%' OR 
            LOWER(Resumen) LIKE '%$escaped_word%' OR 
            LOWER(Fecha_Publicacion) LIKE '%$escaped_word%' OR 
            LOWER(Autor) LIKE '%$escaped_word%')";
    }
}

if ($carrera_id > 0) {
    $where_clauses[] = "Tesis.Carrera_ID = $carrera_id";
}

$sql = "SELECT Tesis.*, Carrera.Nombre AS Carrera_Nombre 
        FROM Tesis 
        LEFT JOIN Carrera ON Tesis.Carrera_ID = Carrera.ID";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY $order_by";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

if (!empty($searchparam) && $result->num_rows > 0) {
    $palabra = strtolower($searchparam_sql);
    $checkSql = "SELECT contador FROM PalabrasBuscadas WHERE palabra = '$palabra'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        $conn->query("UPDATE PalabrasBuscadas SET contador = contador + 1 WHERE palabra = '$palabra'");
    } else {
        $conn->query("INSERT INTO PalabrasBuscadas (palabra, contador) VALUES ('$palabra', 1)");
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultados de Búsqueda - Repositorio de Planes de Negocios</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    .search-term {
        color: var(--accent-color);
        font-weight: 600;
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
        font-size: 0.9rem;
        color: #6c757d;
    }

    .meta-item {
        display: flex;
        align-items: center;
    }

    .meta-item i {
        margin-right: 5px;
        color: var(--primary-color);
    }

    .card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #e0e0e0;
    }

    .card-actions {
        display: flex;
        gap: 10px;
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

    .collapse-content {
        padding: 20px;
        background-color: var(--light-color);
        border-radius: 8px;
        border-left: 3px solid var(--accent-color);
        margin-top: 15px;
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

    .suggestions-section {
        background-color: var(--light-color);
        border-radius: 8px;
        padding: 20px;
        margin-top: 30px;
    }

    .suggestion-badge {
        background-color: var(--primary-color);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-block;
        margin: 5px;
    }

    .suggestion-badge:hover {
        background-color: var(--accent-color);
        color: var(--dark-color);
        transform: translateY(-2px);
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .card-bottom {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .card-actions {
            flex-direction: column;
            width: 100%;
        }

        .card-actions .btn {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <!-- Incluimos el navbar -->
    <?php include_once '../navbar.php'; ?>

    <div class="container main-container">
        <div class="page-header">
            <h1 class="page-title">Resultados de Búsqueda</h1>
            <p class="lead">Para: <span class="search-term">"<?= htmlspecialchars($searchparam) ?>"</span></p>

            <?php if ($carrera_id > 0): 
                $nombre_carrera = 'Carrera seleccionada';
                $query_nombre = $conn->query("SELECT Nombre FROM Carrera WHERE ID = $carrera_id");
                if ($query_nombre && $query_nombre->num_rows > 0) {
                    $nombre_carrera = $query_nombre->fetch_assoc()['Nombre'];
                }
            ?>
            <p><strong>Filtrado por carrera:</strong> <?= htmlspecialchars($nombre_carrera) ?></p>
            <?php endif; ?>
        </div>

        <div class="filter-section">
            <form class="row g-3 align-items-center" method="GET">
                <input type="hidden" name="searchparam" value="<?= htmlspecialchars($searchparam) ?>">

                <div class="col-md-4">
                    <label for="carrera_id" class="filter-label col-form-label">Filtrar por carrera:</label>
                    <select name="carrera_id" id="carrera_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">Todas las carreras</option>
                        <?php
                        $query_carreras = $conn->query("SELECT ID, Nombre FROM Carrera ORDER BY Nombre ASC");
                        while ($row_carrera = $query_carreras->fetch_assoc()) {
                            $selected = ($carrera_id == $row_carrera['ID']) ? 'selected' : '';
                            echo "<option value='{$row_carrera['ID']}' $selected>" . htmlspecialchars($row_carrera['Nombre']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="orden" class="filter-label col-form-label">Ordenar por:</label>
                    <select name="orden" id="orden" class="form-select" onchange="this.form.submit()">
                        <option value="fecha_desc" <?= (($_GET['orden'] ?? '') === 'fecha_desc') ? 'selected' : '' ?>>
                            Más recientes</option>
                        <option value="fecha_asc" <?= (($_GET['orden'] ?? '') === 'fecha_asc') ? 'selected' : '' ?>>Más
                            antiguas</option>
                        <option value="titulo_asc" <?= (($_GET['orden'] ?? '') === 'titulo_asc') ? 'selected' : '' ?>>
                            Título A-Z</option>
                        <option value="titulo_desc" <?= (($_GET['orden'] ?? '') === 'titulo_desc') ? 'selected' : '' ?>>
                            Título Z-A</option>
                        <option value="visualizaciones_asc"
                            <?= (($_GET['orden'] ?? '') === 'visualizaciones_asc') ? 'selected' : '' ?>>Menos
                            visualizaciones</option>
                        <option value="visualizaciones_desc"
                            <?= (($_GET['orden'] ?? '') === 'visualizaciones_desc') ? 'selected' : '' ?>>Más
                            visualizaciones</option>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
        <div class="thesis-list">
            <?php while($row = $result->fetch_assoc()): ?>
            <?php
                    $tesis_id = $row['ID'];
                    $carrera_nombre = $row['Carrera_Nombre'] ?? 'Sin carrera';
                    $color_index = $row['Carrera_ID'] % 10;

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
                            <i class="fas fa-calendar-alt"></i>
                            <?= htmlspecialchars($row['Fecha_publicacion']) ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($row['Estado']) ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-eye"></i>
                            <?= htmlspecialchars($row['Visualizaciones']) ?> visualizaciones
                        </span>
                    </div>

                    <div class="card-bottom">
                        <div class="card-actions">
                            <button class="btn btn-details" type="button" data-bs-toggle="collapse"
                                data-bs-target="#detalles<?= $tesis_id ?>" aria-expanded="false">
                                <i class="fas fa-info-circle"></i> Detalles
                            </button>
                            <a href="../Conection/ver_pdf.php?id=<?= $tesis_id ?>" target="_blank" class="btn btn-pdf">
                                <i class="fas fa-file-pdf"></i> Ver PDF
                            </a>
                        </div>
                        <div class="career-badge badge-<?= $color_index ?>">
                            <?= htmlspecialchars($carrera_nombre) ?>
                        </div>
                    </div>

                    <div class="collapse" id="detalles<?= $tesis_id ?>">
                        <div class="collapse-content">
                            <h5 class="section-title">Resumen</h5>
                            <p><?= nl2br(htmlspecialchars($row['Resumen'])) ?></p>

                            <?php if (!empty($palabras_clave)): ?>
                            <h5 class="section-title">Palabras Clave</h5>
                            <div class="keywords">
                                <?php foreach ($palabras_clave as $palabra): ?>
                                <span class="keyword"><?= htmlspecialchars($palabra) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                $stmt_palabras->close();
                endwhile; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-search fa-2x mb-3"></i>
            <h4>No se encontraron resultados</h4>
            <p>No hay planes de negocio que coincidan con "<strong><?= htmlspecialchars($searchparam) ?></strong>".</p>
            <a href="../index.php" class="btn btn-primary mt-2">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($searchparam)): ?>
        <div class="suggestions-section">
            <h5><i class="fas fa-lightbulb"></i> Otras personas también buscaron:</h5>
            <div class="mt-3">
                <?php
                $base = substr(strtolower($searchparam), 0, 3);
                $query_sugerencias = "SELECT palabra FROM PalabrasBuscadas 
                                      WHERE palabra LIKE '%$base%' AND palabra != '$searchparam_sql' 
                                      ORDER BY contador DESC LIMIT 10";
                $resultado_sugerencias = $conn->query($query_sugerencias);
                if ($resultado_sugerencias && $resultado_sugerencias->num_rows > 0):
                    while ($row_sugerencia = $resultado_sugerencias->fetch_assoc()):
                        $sugerida = htmlspecialchars($row_sugerencia['palabra']);
                        echo "<a href='?searchparam=" . urlencode($sugerida) . "' class='suggestion-badge'>$sugerida</a>";
                    endwhile;
                else:
                    echo "<p class='text-muted'>No se encontraron sugerencias.</p>";
                endif;
                ?>
            </div>
        </div>
        <?php endif; ?>
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