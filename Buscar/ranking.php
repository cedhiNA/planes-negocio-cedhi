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

$sql = "SELECT * FROM Tesis ORDER BY Visualizaciones DESC LIMIT 10";
$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta SQL: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Ranking de Planes de Negocios - Top 10 Visualizaciones</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="../Imagenes/logo_cedhi_claro.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../estilos.css">

    <style>
    .main-container {
        max-width: 1000px;
        margin: 30px auto;
        padding: 30px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .page-title {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
    }

    .page-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: var(--accent-color);
        border-radius: 2px;
    }

    .ranking-item {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding: 20px;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border-left: 5px solid transparent;
    }

    .ranking-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }

    .ranking-number {
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 700;
        margin-right: 25px;
        color: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .ranking-number.gold {
        background: linear-gradient(135deg, #FFD700, #D4AF37);
    }

    .ranking-number.silver {
        background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
    }

    .ranking-number.bronze {
        background: linear-gradient(135deg, #CD7F32, #B87333);
    }

    .ranking-number.other {
        background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    }

    .ranking-content {
        flex-grow: 1;
    }

    .ranking-title {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 5px;
        font-size: 1.2rem;
    }

    .ranking-meta {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 10px;
        font-size: 0.9rem;
        color: #666;
    }

    .ranking-meta span {
        margin-right: 15px;
        display: flex;
        align-items: center;
    }

    .ranking-meta i {
        margin-right: 5px;
        color: var(--secondary-color);
    }

    .ranking-views {
        font-weight: 600;
        color: var(--primary-color);
    }

    .btn-ranking {
        padding: 8px 20px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
        margin-right: 10px;
        margin-bottom: 10px;
    }

    .btn-details {
        background: white;
        border: 1px solid var(--secondary-color);
        color: var(--secondary-color);
    }

    .btn-details:hover {
        background: var(--secondary-color);
        color: white;
    }

    .btn-pdf {
        background: var(--primary-color);
        color: white;
        border: none;
    }

    .btn-pdf:hover {
        background: #5e1491;
        color: white;
    }

    .ranking-details {
        margin-top: 15px;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 8px;
        border-left: 3px solid var(--accent-color);
    }

    .ranking-details p {
        margin-bottom: 10px;
    }

    .keywords {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .keyword {
        background: rgba(106, 27, 154, 0.1);
        color: var(--primary-color);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    @media (max-width: 768px) {

        .ranking-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .ranking-number {
            margin-right: 0;
            margin-bottom: 15px;
        }

        .ranking-meta span {
            margin-right: 10px;
            margin-bottom: 5px;
        }
    }

    .no-results {
        text-align: center;
        padding: 40px;
        color: #666;
        font-size: 1.1rem;
    }

    .no-results i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 20px;
        display: block;
    }

    .ranking-meta .icon-spacing {
        margin-right: 6px;
    }

    .btn-icon-spacing {
        margin-right: 5px;
    }

    .ranking-meta i,
    .ranking-actions i {
        margin-right: 6px;
    }
    </style>
</head>

<body>
    <?php include_once '../navbar.php'; ?>

    <div class="main-container">
        <h1 class="page-title">Top 10 Planes Más Visualizados</h1>

        <?php if ($result && $result->num_rows > 0): ?>
        <?php 
            $posicion = 1; 
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
                
                $clase_numero = 'other';
                if ($posicion === 1) {
                    $clase_numero = 'gold';
                } else if ($posicion === 2) {
                    $clase_numero = 'silver';
                } else if ($posicion === 3) {
                    $clase_numero = 'bronze';
                }
            ?>
        <div class="ranking-item">
            <div class="ranking-number <?= $clase_numero ?>"><?= $posicion ?></div>
            <div class="ranking-content">
                <h3 class="ranking-title"><?= htmlspecialchars($row['Titulo']) ?></h3>
                <div class="ranking-meta">
                    <span><i class="fas fa-calendar-alt icon-spacing"></i>
                        <?= htmlspecialchars($row['Fecha_publicacion']) ?></span>
                    <span><i class="fas fa-check-circle icon-spacing"></i>
                        <?= htmlspecialchars($row['Estado']) ?></span>
                    <span class="ranking-views"><i class="fas fa-eye icon-spacing"></i>
                        <?= htmlspecialchars($row['Visualizaciones']) ?> visualizaciones</span>
                </div>

                <div class="ranking-actions">
                    <button class="btn btn-ranking btn-details" type="button" data-bs-toggle="collapse"
                        data-bs-target="#detalles<?= $row['ID'] ?>">
                        <i class="fas fa-info-circle btn-icon-spacing"></i> Detalles
                    </button>
                    <a href="../Conection/ver_pdf.php?id=<?= $row['ID'] ?>" target="_blank"
                        class="btn btn-ranking btn-pdf">
                        <i class="fas fa-file-pdf btn-icon-spacing"></i> Ver PDF
                    </a>
                </div>

                <div class="collapse ranking-details" id="detalles<?= $row['ID'] ?>">
                    <p><strong>Resumen:</strong> <?= nl2br(htmlspecialchars($row['Resumen'])) ?></p>
                    <?php if (!empty($palabras_clave)): ?>
                    <div>
                        <strong>Palabras clave:</strong>
                        <div class="keywords">
                            <?php foreach ($palabras_clave as $palabra): ?>
                            <span class="keyword"><?= htmlspecialchars($palabra) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php 
                $posicion++;
                $stmt_palabras->close();
            endwhile; ?>
        <?php else: ?>
        <div class="no-results">
            <i class="fas fa-book-open"></i>
            <p>No hay planes de negocio registrados todavía.</p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>