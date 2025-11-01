<?php
/**
 * Gestión de Proyectos - Editar
 */

// Redirigir a la página de crear/editar con el ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    header("Location: project-create.php?id=$id");
    exit();
} else {
    header('Location: projects.php?error=' . urlencode('ID de proyecto no especificado'));
    exit();
}
?>