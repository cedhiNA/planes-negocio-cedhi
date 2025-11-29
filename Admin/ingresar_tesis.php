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

include_once('../Conection/conexion.php');

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$mensajeError = ""; 

$queryCarreras = "SELECT ID, Nombre FROM Carrera";
$resultCarreras = $conn->query($queryCarreras);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $autor = $_POST['autor'];
    $estado = $_POST['estado'];
    $resumen = $_POST['resumen'];
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $carrera_id = $_POST['carrera_id'];
    $palabras_clave = explode(",", $_POST['palabras_clave']); 
    $archivo_pdf = "";

    if (isset($_FILES["archivo_pdf"]) && $_FILES["archivo_pdf"]["error"] == 0) {
        $archivo_pdf = basename($_FILES["archivo_pdf"]["name"]);
        $ruta_archivo = "../Archivos/" . $archivo_pdf;
        
        $extension = strtolower(pathinfo($archivo_pdf, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            echo "<script>alert('Solo se permiten archivos PDF.'); window.history.back();</script>";
            exit;
        }
        
        move_uploaded_file($_FILES["archivo_pdf"]["tmp_name"], $ruta_archivo);
    } else {
        echo "<script>alert('Error al subir el archivo PDF.'); window.history.back();</script>";
        exit;
    }

    if ($carrera_id === "otra" && !empty($_POST['nueva_carrera'])) {
        $nueva_carrera = trim($_POST['nueva_carrera']);

        $sqlNuevaCarrera = "INSERT INTO Carrera (Nombre) VALUES (?)";
        $stmtNuevaCarrera = $conn->prepare($sqlNuevaCarrera);
        $stmtNuevaCarrera->bind_param("s", $nueva_carrera);

        if ($stmtNuevaCarrera->execute()) {
            $carrera_id = $conn->insert_id;
        } else {
            die("Error al insertar nueva carrera: " . $stmtNuevaCarrera->error);
        }

        $stmtNuevaCarrera->close();
    }

    $sql = "INSERT INTO Tesis (Titulo, Autor, Estado, Resumen, Fecha_publicacion, Archivo_pdf, Carrera_ID)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $titulo, $autor, $estado, $resumen, $fecha_publicacion, $archivo_pdf, $carrera_id);

    if ($stmt->execute()) {
        $tesis_id = $conn->insert_id;  

        foreach ($palabras_clave as $palabra) {
            $palabra = trim($palabra);
            if (!empty($palabra)) {
                $sql_check = "SELECT ID FROM PalabraClave WHERE Palabra = ?";
                $stmt_check = $conn->prepare($sql_check);
                $stmt_check->bind_param("s", $palabra);
                $stmt_check->execute();
                $res_check = $stmt_check->get_result();

                if ($res_check->num_rows > 0) {
                    $row = $res_check->fetch_assoc();
                    $palabra_id = $row['ID'];
                } else {
                    $sql_insert = "INSERT INTO PalabraClave (Palabra) VALUES (?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("s", $palabra);
                    $stmt_insert->execute();
                    $palabra_id = $conn->insert_id;
                }

                $sql_rel = "INSERT INTO TesisPalabraClave (Tesis_ID, PalabraClave_ID) VALUES (?, ?)";
                $stmt_rel = $conn->prepare($sql_rel);
                $stmt_rel->bind_param("ii", $tesis_id, $palabra_id);
                $stmt_rel->execute();
            }
        }

        echo "<script>alert('Plan de negocio registrado correctamente.'); window.location.href='gestionar_tesis.php';</script>";
    } else {
        echo "Error al registrar el plan de negocio: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar Plan de Negocios - Panel de Administración</title>
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

    .form-text {
        color: #6c757d;
        font-size: 0.85rem;
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
        .btn-cancel {
            width: 100%;
            margin-bottom: 10px;
        }
    }
    </style>
</head>

<script>
function toggleNuevaCarrera(selectElement) {
    const nuevaCarreraDiv = document.getElementById("nueva_carrera_div");
    if (selectElement.value === "otra") {
        nuevaCarreraDiv.style.display = "block";
        document.getElementById("nueva_carrera").required = true;
    } else {
        nuevaCarreraDiv.style.display = "none";
        document.getElementById("nueva_carrera").required = false;
    }
}
</script>

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
            <form method="POST" enctype="multipart/form-data">

                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-info-circle"></i> Información Básica
                    </h4>

                    <div class="mb-4">
                        <label for="titulo" class="form-label required-field">
                            <i class="fas fa-heading"></i> Título del Plan
                        </label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required
                            placeholder="Ingrese el título completo del plan de negocio">
                    </div>

                    <div class="mb-4">
                        <label for="autor" class="form-label required-field">
                            <i class="fas fa-user"></i> Autor(es)
                        </label>
                        <input type="text" class="form-control" id="autor" name="autor" required
                            placeholder="Nombre del autor o autores">
                    </div>

                    <div class="mb-4">
                        <label for="resumen" class="form-label required-field">
                            <i class="fas fa-file-alt"></i> Resumen Ejecutivo
                        </label>
                        <textarea class="form-control" id="resumen" name="resumen" rows="5" required
                            placeholder="Describa brevemente el plan de negocio, objetivos principales, mercado objetivo, etc..."></textarea>
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
                                <option value="">-- Seleccione el estado --</option>
                                <option value="En proceso">En proceso</option>
                                <option value="Finalizada">Finalizada</option>
                                <option value="Aprobada">Aprobada</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="fecha_publicacion" class="form-label required-field">
                                <i class="fas fa-calendar-alt"></i> Año de Publicación
                            </label>
                            <input type="number" class="form-control" id="fecha_publicacion" name="fecha_publicacion"
                                min="2000" max="<?= date('Y') ?>" value="<?= date('Y') ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="carrera_id" class="form-label required-field">
                            <i class="fas fa-graduation-cap"></i> Carrera
                        </label>
                        <select class="form-select" id="carrera_id" name="carrera_id" required
                            onchange="toggleNuevaCarrera(this)">
                            <option value="">-- Selecciona una carrera --</option>
                            <?php 
                            $resultCarreras->data_seek(0);
                            while ($row = $resultCarreras->fetch_assoc()): ?>
                            <option value="<?= $row['ID'] ?>"><?= htmlspecialchars($row['Nombre']) ?></option>
                            <?php endwhile; ?>
                            <option value="otra">Otra (Agregar nueva carrera)</option>
                        </select>
                    </div>

                    <div class="mb-4" id="nueva_carrera_div" style="display: none;">
                        <label for="nueva_carrera" class="form-label">
                            <i class="fas fa-plus"></i> Nombre de la nueva carrera
                        </label>
                        <input type="text" class="form-control" id="nueva_carrera" name="nueva_carrera"
                            placeholder="Ingrese el nombre de la nueva carrera">
                    </div>
                </div>

                <div class="form-section">
                    <h4 class="form-section-title">
                        <i class="fas fa-file-upload"></i> Archivos y Metadatos
                    </h4>

                    <div class="mb-4">
                        <label for="archivo_pdf" class="form-label required-field">
                            <i class="fas fa-file-pdf"></i> Archivo PDF
                        </label>
                        <input type="file" class="form-control" id="archivo_pdf" name="archivo_pdf" accept=".pdf"
                            required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Solo se permiten archivos PDF. Tamaño máximo: 8MB
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="palabras_clave" class="form-label required-field">
                            <i class="fas fa-tags"></i> Palabras Clave
                        </label>
                        <input type="text" class="form-control" id="palabras_clave" name="palabras_clave" required
                            placeholder="Ej: marketing, finanzas, emprendimiento, innovación, startup...">
                        <div class="form-text">
                            <i class="fas fa-lightbulb"></i> Separe las palabras clave con comas. Estas ayudarán a los
                            usuarios a encontrar el plan.
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="gestionar_tesis.php" class="btn btn-cancel me-md-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Registrar Plan de Negocio
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>