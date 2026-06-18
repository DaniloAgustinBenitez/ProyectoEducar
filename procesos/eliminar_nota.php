<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_nota = $_POST['id_nota'] ?? '';

    if (!empty($id_nota)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE id = :id");
            $stmt->execute([':id' => $id_nota]);
        } catch (PDOException $e) {
            error_log("Error eliminando nota en SQL: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-gestion-aula');
exit;
?>