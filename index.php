<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once "Accesos/auth_central.php";

$estaAutenticado = validarAutenticacionCentral();
if (!$estaAutenticado) {
    header("Location: https://biblioteca.cedhinuevaarequipa.edu.pe/");
    exit();
}
$usuarioData = $estaAutenticado ? obtenerUsuarioCentral() : null;
$rol = $estaAutenticado ? strtolower($usuarioData['rol']) : null;

include_once('Conection/conexion.php');

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$searchparam = isset($_GET['searchparam']) ? $_GET['searchparam'] : '';

$sql = "SELECT * FROM Tesis";
if ($searchparam != '') {
    $search = $conn->real_escape_string($searchparam);
    $sql .= " WHERE MATCH(titulo, autor, resumen) AGAINST('{$search}' IN NATURAL LANGUAGE MODE)";
}
$result = $conn->query($sql);

$sql_busquedas = "SELECT palabra, contador FROM PalabrasBuscadas ORDER BY contador DESC LIMIT 10";
$result_busquedas = $conn->query($sql_busquedas);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Repositorio de Planes de Negocios</title>
    <link rel="icon" type="image/png" href="Imagenes/logo_cedhi_claro.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="estilos.css">
</head>

<body>
    <?php include_once 'navbar.php'; ?>

    <section class="hero-section">
        <div class="hero-overlay"></div>

        <div class="carousel">
            <img src="Imagenes/DJI_0036.jpg" class="active" alt="imagen 1" />
            <img src="Imagenes/NVC_0303.jpg" alt="imagen 2" />
            <img src="Imagenes/NVC_0510.jpg" alt="imagen 3" />
        </div>

        <div class="hero-content">
            <img src="Imagenes/logotipob.png" alt="Logo" class="hero-logo" />
            <h1 class="hero-title">Explora nuestro Repositorio de Planes de Negocios</h1>
            <p class="hero-subtitle">Encuentra investigaciones académicas y planes de negocio completos</p>

            <div class="search-container" style="position: relative;">
                <form class="search-form" id="search-form" action="Buscar/busqueda_palabra.php" method="GET"
                    autocomplete="off">
                    <input type="text" class="search-input" id="searchparam" name="searchparam"
                        placeholder="Buscar por título, autor o palabras clave..."
                        value="<?= htmlspecialchars($searchparam) ?>" />
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <div id="suggestions" class="suggestions-list" style="display:none"></div>
                </form>
            </div>

            <?php if ($result_busquedas && $result_busquedas->num_rows > 0): ?>
            <div class="popular-searches" aria-live="polite">
                <h3><i class="fas fa-fire"></i> Tendencias de búsqueda</h3>
                <div class="search-tags">
                    <?php while ($row = $result_busquedas->fetch_assoc()): ?>
                    <div class="search-tag" role="button" tabindex="0"
                        onclick="fillAndSearch('<?= htmlspecialchars($row['palabra'], ENT_QUOTES) ?>')">
                        <?= htmlspecialchars(ucwords($row['palabra'])) ?>
                        <span class="count"><?= $row['contador'] ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Repositorio de Planes de Negocios - Todos los derechos reservados</p>
        </div>
    </footer>

    <?php $conn->close(); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    (function() {
        let currentIndex = 0;
        const images = document.querySelectorAll('.carousel img');
        if (!images.length) return;
        const totalImages = images.length;

        function showNextImage() {
            images[currentIndex].classList.remove('active');
            currentIndex = (currentIndex + 1) % totalImages;
            images[currentIndex].classList.add('active');
        }
        setInterval(showNextImage, 5000);
    })();

    (function() {
        const heroTitle = document.querySelector('.hero-title');
        if (!heroTitle) return;
        const originalText = heroTitle.textContent;
        heroTitle.textContent = '';
        let i = 0;
        const typingEffect = setInterval(() => {
            if (i < originalText.length) {
                heroTitle.textContent += originalText.charAt(i);
                i++;
            } else {
                clearInterval(typingEffect);
            }
        }, 40);
    })();

    function fillAndSearch(value) {
        const input = document.getElementById('searchparam');
        input.value = value;
        document.getElementById('search-form').submit();
    }
    </script>
</body>

</html>