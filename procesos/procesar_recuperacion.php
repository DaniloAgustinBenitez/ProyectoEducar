<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario_recuperar'] ?? '');

    if (!empty($usuario)) {
        require_once __DIR__ . '/conexion.php';
        try {
            // 1. Verificamos si el usuario realmente existe en el sistema
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE LOWER(username) = LOWER(:user) LIMIT 1");
            $stmt_check->execute([':user' => $usuario]);

            if ($stmt_check->fetch()) {
                // 2. Evitamos que spammeen el botón (UNIQUE KEY en la tabla lo garantiza igual)
                $stmt_ya = $pdo->prepare("SELECT id FROM recuperaciones WHERE usuario = :user LIMIT 1");
                $stmt_ya->execute([':user' => $usuario]);

                // 3. Guardamos el pedido de auxilio
                if (!$stmt_ya->fetch()) {
                    $stmt_ins = $pdo->prepare("INSERT INTO recuperaciones (usuario, fecha) VALUES (:user, :fecha)");
                    $stmt_ins->execute([':user' => $usuario, ':fecha' => date('Y-m-d H:i:s')]);
                }
            }
        } catch (PDOException $e) {
            error_log("Error en recuperación de clave: " . $e->getMessage());
        }
    }
    // Por seguridad, siempre mostramos éxito, exista o no el usuario (para evitar escaneo de cuentas)
    header('Location: ../login.php?msj=recuperacion_enviada');
    exit;
}