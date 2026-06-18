<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usr_id = trim($_POST['usuario_id'] ?? '');

    if (!empty($usr_id)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE username = :user");
            $stmt->execute([':user' => $usr_id]);

            header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_eliminado');
            exit;
        } catch (PDOException $e) {
            error_log("Error eliminando usuario SQL: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios');
exit;