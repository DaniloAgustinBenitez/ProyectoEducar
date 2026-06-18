<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_blanquear = trim($_POST['usuario_blanquear'] ?? '');

    if (!empty($usuario_blanquear)) {
        try {
            // Generamos el hash oficial para la clave "123"
            $hash_nuevo = password_hash('123', PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE usuarios SET password = :pass WHERE username = :user");
            $stmt->execute([':pass' => $hash_nuevo, ':user' => $usuario_blanquear]);

            // Limpiamos la solicitud de recuperación de la BD para que desaparezca del panel
            $stmt_del = $pdo->prepare("DELETE FROM recuperaciones WHERE usuario = :user");
            $stmt_del->execute([':user' => $usuario_blanquear]);

            header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_modificado');
            exit;
        } catch (PDOException $e) {
            error_log("Error al blanquear clave: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios');
exit;