<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "../Accesos/auth_central.php";

if (!validarAutenticacionCentral()) {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/");
    exit();
}

$usuarioData = obtenerUsuarioCentral();
$rol = strtolower($usuarioData['rol']);
$estaAutenticado = true;

if ($rol !== 'admin' && $rol !== 'owner') {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/?error=permisos");
    exit();
}

include_once "../Conection/conexion.php";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$filtro_busqueda = isset($_GET['busqueda']) ? $conn->real_escape_string($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? $conn->real_escape_string($_GET['estado']) : '';
$filtro_carrera = isset($_GET['carrera']) ? $conn->real_escape_string($_GET['carrera']) : '';
$orden = isset($_GET['orden']) ? $conn->real_escape_string($_GET['orden']) : 'fecha_desc';

$sql = "SELECT t.*, c.Nombre AS NombreCarrera
        FROM Tesis t
        JOIN Carrera c ON t.Carrera_ID = c.ID
        WHERE 1=1";

$params = [];
$types = '';

if (!empty($filtro_busqueda)) {
    $sql .= " AND (t.Titulo LIKE ? OR t.Resumen LIKE ?)";
    $params[] = "%$filtro_busqueda%";
    $params[] = "%$filtro_busqueda%";
    $types .= 'ss';
}

if (!empty($filtro_estado)) {
    $sql .= " AND t.Estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if (!empty($filtro_carrera)) {
    $sql .= " AND c.ID = ?";
    $params[] = $filtro_carrera;
    $types .= 'i';
}

switch ($orden) {
    case 'titulo_asc':
        $sql .= " ORDER BY t.Titulo ASC";
        break;
    case 'titulo_desc':
        $sql .= " ORDER BY t.Titulo DESC";
        break;
    case 'fecha_asc':
        $sql .= " ORDER BY t.Fecha_publicacion ASC";
        break;
    case 'fecha_desc':
        $sql .= " ORDER BY t.Fecha_publicacion DESC";
        break;
    case 'vistas_desc':
        $sql .= " ORDER BY t.Visualizaciones DESC";
        break;
    default:
        $sql .= " ORDER BY t.Fecha_publicacion DESC";
        break;
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}

$sql_carreras = "SELECT ID, Nombre FROM Carrera ORDER BY Nombre";
$result_carreras = $conn->query($sql_carreras);

$total_resultados = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestionar Planes de Negocios - Panel de Administración</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="../Imagenes/logo_cedhi_claro.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../estilos.css">
    <link rel="stylesheet" href="gestionar_tesis.css">
</head>

<body>
    <?php include_once '../navbar.php'; ?>
    <div class="container main-container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">
                <i class="fas fa-book-open"></i> Gestión de Planes de Negocios
            </h2>
            <span class="badge bg-primary fw-normal" style="background-color: #6a1b9a !important;">
                <?= $total_resultados ?> plan(es) encontrado(s)
            </span>
        </div>

        <!-- Panel de Filtros -->
        <div class="filters-card">
            <form method="GET" action="" id="filtersForm">
                <div class="row">
                    <div class="col-md-4 filter-group">
                        <div class="filter-label">
                            <i class="fas fa-search"></i> Buscar
                        </div>
                        <input type="text" name="busqueda" class="form-control form-control-sm"
                            placeholder="Buscar por título o resumen..."
                            value="<?= htmlspecialchars($filtro_busqueda) ?>">
                    </div>

                    <div class="col-md-3 filter-group">
                        <div class="filter-label">
                            <i class="fas fa-filter"></i> Estado
                        </div>
                        <select name="estado" class="form-select form-select-sm">
                            <option value="">Todos los estados</option>
                            <option value="Aprobada" <?= $filtro_estado == 'Aprobada' ? 'selected' : '' ?>>Aprobada
                            </option>
                            <option value="Finalizada" <?= $filtro_estado == 'Finalizada' ? 'selected' : '' ?>>
                                Finalizada</option>
                            <option value="En proceso" <?= $filtro_estado == 'En proceso' ? 'selected' : '' ?>>En
                                proceso</option>
                        </select>
                    </div>

                    <div class="col-md-3 filter-group">
                        <div class="filter-label">
                            <i class="fas fa-graduation-cap"></i> Carrera
                        </div>
                        <select name="carrera" class="form-select form-select-sm">
                            <option value="">Todas las carreras</option>
                            <?php while ($carrera = $result_carreras->fetch_assoc()): ?>
                            <option value="<?= $carrera['ID'] ?>"
                                <?= $filtro_carrera == $carrera['ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($carrera['Nombre']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-2 filter-group">
                        <div class="filter-label">
                            <i class="fas fa-sort"></i> Ordenar
                        </div>
                        <select name="orden" class="form-select form-select-sm">
                            <option value="fecha_desc" <?= $orden == 'fecha_desc' ? 'selected' : '' ?>>Más recientes
                            </option>
                            <option value="fecha_asc" <?= $orden == 'fecha_asc' ? 'selected' : '' ?>>Más antiguos
                            </option>
                            <option value="titulo_asc" <?= $orden == 'titulo_asc' ? 'selected' : '' ?>>Título A-Z
                            </option>
                            <option value="titulo_desc" <?= $orden == 'titulo_desc' ? 'selected' : '' ?>>Título Z-A
                            </option>
                        </select>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="filter-buttons d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-filter">
                                    <i class="fas fa-filter"></i> Aplicar
                                </button>
                                <a href="?" class="btn btn-clear">
                                    <i class="fas fa-times"></i> Limpiar
                                </a>
                            </div>
                            <a href="ingresar_tesis.php" class="btn btn-success btn-tiny">
                                <i class="fas fa-plus"></i> Nuevo Plan
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || !empty($filtro_carrera)): ?>
                <div class="results-info mt-3">
                    <strong>Filtros activos:</strong>
                    <div class="active-filters">
                        <?php if (!empty($filtro_busqueda)): ?>
                        <span class="filter-badge">
                            Búsqueda: "<?= htmlspecialchars($filtro_busqueda) ?>"
                            <a href="?<?= http_build_query(array_merge($_GET, ['busqueda' => ''])) ?>"
                                class="text-white ms-1">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($filtro_estado)): ?>
                        <span class="filter-badge">
                            Estado: <?= htmlspecialchars($filtro_estado) ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['estado' => ''])) ?>"
                                class="text-white ms-1">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($filtro_carrera)): ?>
                        <span class="filter-badge">
                            <?php 
                                $result_carreras->data_seek(0);
                                $carrera_nombre = '';
                                while ($carrera = $result_carreras->fetch_assoc()) {
                                    if ($carrera['ID'] == $filtro_carrera) {
                                        $carrera_nombre = $carrera['Nombre'];
                                        break;
                                    }
                                }
                                ?>
                            Carrera: <?= htmlspecialchars($carrera_nombre) ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['carrera' => ''])) ?>"
                                class="text-white ms-1">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- VERSIÓN ESCRITORIO - Tabla -->
        <div class="table-container desktop-table">
            <div class="table-wrapper">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="col-titulo text-left">Título</th>
                            <th class="col-estado text-center">Estado</th>
                            <th class="col-resumen text-left">Resumen</th>
                            <th class="col-fecha text-center">Fecha</th>
                            <th class="col-pdf text-center">PDF</th>
                            <th class="col-carrera text-center">Carrera</th>
                            <th class="col-palabras text-center">Palabras Clave</th>
                            <th class="col-acciones text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $total_resultados > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
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
                                $stmt_palabras->close();
                                ?>

                        <tr>
                            <td class="col-titulo text-left">
                                <div class="titulo-compacto" title="<?= htmlspecialchars($row['Titulo']) ?>">
                                    <?= htmlspecialchars($row['Titulo']) ?>
                                </div>
                            </td>
                            <td class="col-estado text-center">
                                <div class="centered-content">
                                    <span class="badge-status text-white
                                                <?= $row['Estado'] == 'Aprobada' ? 'bg-success' : 
                                                ($row['Estado'] == 'En proceso' ? 'bg-warning' : 'bg-secondary') ?>">
                                        <?= htmlspecialchars($row['Estado']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="col-resumen text-left">
                                <span class="resumen-preview" data-bs-toggle="modal"
                                    data-bs-target="#modalResumen<?= $row['ID'] ?>"
                                    title="Haz clic para ver el resumen completo">
                                    <?= htmlspecialchars(substr($row['Resumen'], 0, 60)) ?>...
                                </span>
                            </td>
                            <td class="col-fecha text-center">
                                <div class="centered-content">
                                    <?= htmlspecialchars($row['Fecha_publicacion']) ?>
                                </div>
                            </td>
                            <td class="col-pdf text-center">
                                <div class="centered-content">
                                    <?php if (!empty($row['Archivo_pdf'])): ?>
                                    <a href="../Archivos/<?= htmlspecialchars($row['Archivo_pdf']) ?>" target="_blank"
                                        class="btn btn-view btn-admin">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted small">No disponible</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-carrera text-center">
                                <div class="centered-content">
                                    <span class="carrera-compacta"
                                        title="<?= htmlspecialchars($row['NombreCarrera']) ?>">
                                        <?= htmlspecialchars($row['NombreCarrera']) ?>
                                    </span>
                                </div>
                            </td>
                            <td class="col-palabras text-center">
                                <div class="palabras-clave-container">
                                    <?php if (!empty($palabras_clave)): ?>
                                    <?php foreach (array_slice($palabras_clave, 0, 2) as $palabra): ?>
                                    <span class="keyword-badge"><?= htmlspecialchars($palabra) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($palabras_clave) > 2): ?>
                                    <span class="keyword-badge">+<?= count($palabras_clave) - 2 ?> más</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted small">Sin palabras clave</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="col-acciones text-center">
                                <div class="action-buttons">
                                    <button class="btn btn-details btn-admin" data-bs-toggle="modal"
                                        data-bs-target="#modalResumen<?= $row['ID'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="modificar_tesis.php?id=<?= $row['ID'] ?>" class="btn btn-edit btn-admin">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="eliminar_tesis.php?id=<?= $row['ID'] ?>" class="btn btn-delete btn-admin"
                                        onclick="return confirm('¿Está seguro de eliminar este plan de negocio? Esta acción no se puede deshacer.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No se encontraron planes de negocio</h6>
                                <p class="text-muted small mb-3">
                                    <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || !empty($filtro_carrera)): ?>
                                    Intenta ajustar los filtros de búsqueda
                                    <?php else: ?>
                                    No hay planes de negocio registrados
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || !empty($filtro_carrera)): ?>
                                <a href="?" class="btn btn-primary btn-sm">
                                    <i class="fas fa-times"></i> Limpiar Filtros
                                </a>
                                <?php else: ?>
                                <a href="ingresar_tesis.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Agregar Primer Plan
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- VERSIÓN MÓVIL - Cards -->
        <div class="mobile-cards">
            <?php if ($result && $total_resultados > 0): ?>
            <?php 
                // Reiniciar el puntero del resultado
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()): 
                ?>
            <?php
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
                    $stmt_palabras->close();
                    ?>

            <!-- CARD MÓVIL -->
            <div class="tesis-card">
                <div class="card-header">
                    <div class="card-title"><?= htmlspecialchars($row['Titulo']) ?></div>
                </div>

                <div class="card-details">
                    <div class="detail-item">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">
                            <span class="badge-status text-white
                                        <?= $row['Estado'] == 'Aprobada' ? 'bg-success' : 
                                        ($row['Estado'] == 'En proceso' ? 'bg-warning' : 'bg-secondary') ?>">
                                <?= htmlspecialchars($row['Estado']) ?>
                            </span>
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Fecha</span>
                        <span class="detail-value"><?= htmlspecialchars($row['Fecha_publicacion']) ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Carrera</span>
                        <span class="detail-value"><?= htmlspecialchars($row['NombreCarrera']) ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">PDF</span>
                        <span class="detail-value">
                            <?php if (!empty($row['Archivo_pdf'])): ?>
                            <a href="../Archivos/<?= htmlspecialchars($row['Archivo_pdf']) ?>" target="_blank"
                                class="text-primary">
                                <i class="fas fa-file-pdf"></i> Disponible
                            </a>
                            <?php else: ?>
                            <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="btn-mobile btn-view-mobile" data-bs-toggle="modal"
                        data-bs-target="#modalResumen<?= $row['ID'] ?>">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <a href="modificar_tesis.php?id=<?= $row['ID'] ?>" class="btn-mobile btn-edit-mobile">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="eliminar_tesis.php?id=<?= $row['ID'] ?>" class="btn-mobile btn-delete-mobile"
                        onclick="return confirm('¿Está seguro de eliminar este plan de negocio?');">
                        <i class="fas fa-trash"></i> Eliminar
                    </a>
                </div>
            </div>
            <?php endwhile; ?>

            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-search fa-2x text-muted mb-2"></i>
                <h6 class="text-muted">No se encontraron planes de negocio</h6>
                <p class="text-muted small">
                    <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || !empty($filtro_carrera)): ?>
                    Intenta ajustar los filtros de búsqueda
                    <?php else: ?>
                    No hay planes de negocio registrados
                    <?php endif; ?>
                </p>
                <?php if (!empty($filtro_busqueda) || !empty($filtro_estado) || !empty($filtro_carrera)): ?>
                <a href="?" class="btn btn-primary btn-sm">
                    <i class="fas fa-times"></i> Limpiar Filtros
                </a>
                <?php else: ?>
                <a href="ingresar_tesis.php" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Agregar Primer Plan
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Repositorio de Planes de Negocios - Panel de Administración</p>
        </div>
    </footer>
    <?php
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()): 
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
        $stmt_palabras->close();
    ?>
    <!-- MODAL ÚNICO para registro <?= $row['ID'] ?> -->
    <div class="modal fade" id="modalResumen<?= $row['ID'] ?>" tabindex="-1"
        aria-labelledby="modalTitle<?= $row['ID'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle<?= $row['ID'] ?>">
                        <i class="fas fa-file-alt me-2"></i>
                        Detalles del Plan de Negocio
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">

                    <div class="detail-item">
                        <div class="detail-label">Título</div>
                        <div class="detail-value"><?= htmlspecialchars($row['Titulo']) ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Autor</div>
                        <div class="detail-value"><?= htmlspecialchars($row['Autor'] ?? 'No especificado') ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Estado</div>
                        <div class="detail-value">
                            <span class="badge-status text-white
                                <?= $row['Estado'] == 'Aprobada' ? 'bg-success' : 
                                ($row['Estado'] == 'En proceso' ? 'bg-warning' : 'bg-secondary') ?>">
                                <?= htmlspecialchars($row['Estado']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Resumen Completo</div>
                        <div class="resumen-completo">
                            <?= nl2br(htmlspecialchars($row['Resumen'])) ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Palabras Clave</div>
                        <div class="detail-value">
                            <?php if (!empty($palabras_clave)): ?>
                            <div class="palabras-clave-container" style="justify-content: flex-start; max-width: 100%;">
                                <?php foreach ($palabras_clave as $palabra): ?>
                                <span class="keyword-badge"><?= htmlspecialchars($palabra) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">No hay palabras clave registradas</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Carrera</div>
                        <div class="detail-value"><?= htmlspecialchars($row['NombreCarrera']) ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Fecha de Publicación</div>
                        <div class="detail-value"><?= htmlspecialchars($row['Fecha_publicacion']) ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Visualizaciones</div>
                        <div class="detail-value"><?= $row['Visualizaciones'] ?? 0 ?></div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Archivo PDF</div>
                        <div class="detail-value">
                            <?php if (!empty($row['Archivo_pdf'])): ?>
                            <a href="../Archivos/<?= htmlspecialchars($row['Archivo_pdf']) ?>" target="_blank"
                                class="btn btn-view">
                                <i class="fas fa-file-pdf me-1"></i> Ver PDF
                            </a>
                            <?php else: ?>
                            <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="modificar_tesis.php?id=<?= $row['ID'] ?>" class="btn btn-edit">
                        <i class="fas fa-edit me-1"></i> Editar
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        aria-label="Cerrar ventana modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    <?php 
    $stmt->close();
    $conn->close(); 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelector('select[name="orden"]').addEventListener('change', function() {
        document.getElementById('filtersForm').submit();
    });
    </script>
</body>

</html>