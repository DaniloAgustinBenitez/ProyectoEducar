<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva  = $_POST['password_nueva']  ?? '';
    $username_sesion = $_SESSION['usuario'];

    if (!empty($password_actual) && !empty($password_nueva)) {
        try {
            $stmt = $pdo->prepare("SELECT id, password FROM usuarios WHERE username = :user LIMIT 1");
            $stmt->execute([':user' => $username_sesion]);
            $usuario_bd = $stmt->fetch();

            if ($usuario_bd && password_verify($password_actual, $usuario_bd['password'])) {
                $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
                $stmt_upd = $pdo->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
                $stmt_upd->execute([':hash' => $nuevo_hash, ':id' => $usuario_bd['id']]);
                header('Location: ../dashboard.php?msj=clave_actualizada');
                exit;
            } else {
                header('Location: ../dashboard.php?error=clave_incorrecta');
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error cambiando clave: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php');
exit;
?>