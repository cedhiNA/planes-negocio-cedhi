<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM Tesis WHERE ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tesis = $result->fetch_assoc();
    } else {
        die("Plan de negocio no encontrado.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $titulo = $_POST['titulo'];
        $estado = $_POST['estado'];
        $resumen = $_POST['resumen'];
        $fecha_publicacion = $_POST['fecha_publicacion'];
        $carrera_id = $_POST['carrera_id'];

        $sql_update = "UPDATE Tesis SET Titulo = ?, Estado = ?, Resumen = ?, Fecha_publicacion = ?, Carrera_ID = ? WHERE ID = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssssii", $titulo, $estado, $resumen, $fecha_publicacion, $carrera_id, $id);

        if ($stmt_update->execute()) {
            echo "<script>alert('Plan de negocio actualizado correctamente.'); window.location.href='gestionar_tesis.php';</script>";
            exit();
        } else {
            echo "<script>alert('Error al actualizar el plan de negocio: " . $conn->error . "');</script>";
        }
    }
} else {
    die("ID de plan de negocio no especificado.");
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Modificar Plan de Negocios - Panel de Administración</title>
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

    .admin-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .user-info {
        background: var(--light-color);
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border-left: 4px solid var(--accent-color);
    }

    .admin-badge {
        background-color: var(--primary-color);
        color: white;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .owner-badge {
        background-color: var(--accent-color);
        color: var(--dark-color);
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: bold;
    }

    .form-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(106, 27, 154, 0.25);
    }

    .current-file {
        background: var(--light-color);
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid var(--accent-color);
        margin-bottom: 25px;
    }

    .info-card {
        background: var(--light-color);
        border-radius: 8px;
        border-left: 4px solid var(--primary-color);
        margin-bottom: 25px;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(106, 27, 154, 0.3);
    }

    .btn-cancel {
        background: #6c757d;
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .btn-delete {
        background: #dc3545;
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-2px);
    }

    .required-field::after {
        content: " *";
        color: #dc3545;
    }

    .form-section {
        background: var(--light-color);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid var(--accent-color);
    }

    .form-section-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #eee;
    }

    @media (max-width: 768px) {
        .form-container {
            padding: 0 15px;
        }

        .btn-group {
            width: 100%;
        }

        .btn-submit,
        .btn-cancel,
        .btn-delete {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    </style>
</head>

<body>
    <?php include_once '../navbar.php'; ?>

    <div class="container main-container">
        <div class="col-md-12 text-end">
            <div class="btn-group">
                <a href="gestionar_tesis.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Volver a Gestión
                </a>
            </div>
        </div>
        <div class="form-container">
            <form action="modificar_tesis.php?id=<?= $tesis['ID'] ?>" method="POST">

                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-info-circle"></i> Información Básica
                    </h4>

                    <div class="mb-4">
                        <label for="titulo" class="form-label required-field">
                            <i class="fas fa-heading"></i> Título del Plan
                        </label>
                        <input type="text" class="form-control" id="titulo" name="titulo"
                            value="<?= htmlspecialchars($tesis['Titulo']) ?>" required
                            placeholder="Ingrese el título completo del plan de negocio">
                    </div>

                    <div class="mb-4">
                        <label for="resumen" class="form-label required-field">
                            <i class="fas fa-file-alt"></i> Resumen Ejecutivo
                        </label>
                        <textarea class="form-control" id="resumen" name="resumen" rows="5" required
                            placeholder="Describa brevemente el plan de negocio, objetivos principales, mercado objetivo, etc..."><?= htmlspecialchars($tesis['Resumen']) ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-cog"></i> Detalles del Plan
                    </h4>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="estado" class="form-label required-field">
                                <i class="fas fa-chart-line"></i> Estado del Plan
                            </label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="En proceso" <?= $tesis['Estado'] == 'En proceso' ? 'selected' : '' ?>>En
                                    proceso</option>
                                <option value="Finalizada" <?= $tesis['Estado'] == 'Finalizada' ? 'selected' : '' ?>>
                                    Finalizada</option>
                                <option value="Aprobada" <?= $tesis['Estado'] == 'Aprobada' ? 'selected' : '' ?>>
                                    Aprobada</option>
                                <option value="En revisión" <?= $tesis['Estado'] == 'En revisión' ? 'selected' : '' ?>>
                                    En revisión</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="fecha_publicacion" class="form-label required-field">
                                <i class="fas fa-calendar-alt"></i> Año de Publicación
                            </label>
                            <input type="number" class="form-control" id="fecha_publicacion" name="fecha_publicacion"
                                value="<?= $tesis['Fecha_publicacion'] ?>" min="2000" max="<?= date('Y') ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="carrera_id" class="form-label required-field">
                            <i class="fas fa-graduation-cap"></i> Carrera
                        </label>
                        <select class="form-select" id="carrera_id" name="carrera_id" required>
                            <?php
                            $sql_carreras = "SELECT * FROM Carrera ORDER BY Nombre";
                            $result_carreras = $conn->query($sql_carreras);
                            while ($row_carrera = $result_carreras->fetch_assoc()) {
                                $selected = ($row_carrera['ID'] == $tesis['Carrera_ID']) ? 'selected' : '';
                                echo "<option value='" . $row_carrera['ID'] . "' $selected>" . htmlspecialchars($row_carrera['Nombre']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="gestionar_tesis.php" class="btn btn-cancel me-md-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Repositorio de Planes de Negocios - Panel de Administración</p>
        </div>
    </footer>

    <?php $conn->close(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>