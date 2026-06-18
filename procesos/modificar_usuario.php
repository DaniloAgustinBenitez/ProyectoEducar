<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usr_original = trim($_POST['usuario_original'] ?? '');
    $nuevo_login  = trim($_POST['nuevo_usuario'] ?? '');
    $nueva_pass   = trim($_POST['nueva_password'] ?? '');

    if (!empty($usr_original) && !empty($nuevo_login)) {
        try {
            if ($nueva_pass !== '••••••••' && $nueva_pass !== '********' && !empty($nueva_pass)) {
                // Si el administrador escribió una nueva clave, la encriptamos
                $hash = password_hash($nueva_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username = :nuevo, password = :pass WHERE username = :orig");
                $stmt->execute([':nuevo' => $nuevo_login, ':pass' => $hash, ':orig' => $usr_original]);
            } else {
                // Si no tocó la contraseña, solo actualizamos el login de usuario
                $stmt = $pdo->prepare("UPDATE usuarios SET username = :nuevo WHERE username = :orig");
                $stmt->execute([':nuevo' => $nuevo_login, ':orig' => $usr_original]);
            }

            header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_modificado');
            exit;
        } catch (PDOException $e) {
            error_log("Error modificando usuario SQL: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios');
exit;