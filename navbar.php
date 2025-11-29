<?php

$current_file = $_SERVER['PHP_SELF'];
$current_dir = dirname($current_file);

if (strpos($current_dir, 'Buscar') !== false) {
    $base_path = '../';
} else if (strpos($current_dir, 'Admin') !== false) {
    $base_path = '../';
} else if (strpos($current_dir, 'Accesos') !== false) {
    $base_path = '../';
} else {
    $base_path = '';
}

function get_path($file) {
    global $base_path;
    return $base_path . $file;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= get_path('index.php') ?>">
            <i class="fas fa-book-open"></i> Planes de Negocios
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= get_path('index.php') ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= get_path('Buscar/lista_tesis.php') ?>">Planes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= get_path('Buscar/ranking.php') ?>">Ranking</a>
                </li>

                <?php if ($estaAutenticado && ($rol == 'admin' || $rol == 'owner')): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= get_path('Admin/gestionar_tesis.php') ?>">Gestionar Planes</a>
                </li>
                <?php endif; ?>

                <?php if ($estaAutenticado): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= get_path('Accesos/logout_sync.php') ?>">
                        <i class="fas fa-home"></i> Sistema Central
                    </a>
                </li>
                <li class="nav-item">
                    <span class="nav-link text-warning">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($usuarioData['nombre'] ?? 'Usuario') ?>
                    </span>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div style="display:none">
    <p>Current file: <?= $current_file ?></p>
    <p>Current dir: <?= $current_dir ?></p>
    <p>Base path: <?= $base_path ?></p>
</div>