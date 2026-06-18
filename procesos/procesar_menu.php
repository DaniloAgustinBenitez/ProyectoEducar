<?php
session_start();

// Verificamos que sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recogemos los datos del formulario de dashboard.php
    $plato_principal = trim($_POST['plato_principal'] ?? '');
    $opcion_vegetariana = trim($_POST['opcion_vegetariana'] ?? '');
    $postre = trim($_POST['postre'] ?? '');

    if (!empty($plato_principal) && !empty($opcion_vegetariana) && !empty($postre)) {
        require_once 'conexion.php';
        try {
            $stmt = $pdo->prepare("UPDATE menu_comedor SET plato_principal = :pp, opcion_vegetariana = :ov, postre = :postre WHERE id = 1");
            $stmt->execute([
                ':pp'     => $plato_principal,
                ':ov'     => $opcion_vegetariana,
                ':postre' => $postre
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando menú: " . $e->getMessage());
        }
    }

    // Lo devolvemos al dashboard en la pestaña del comedor
    header('Location: ../dashboard.php?vista=vista-comedor');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>