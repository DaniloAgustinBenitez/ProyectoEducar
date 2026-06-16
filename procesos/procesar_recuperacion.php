<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario_recuperar'] ?? '');

    if (!empty($usuario)) {
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];

        // 1. Verificamos si el usuario realmente existe en el sistema
        $existe = false;
        foreach ($usuarios as $u) {
            if (strtolower($u['usuario']) === strtolower($usuario)) {
                $existe = true;
                break;
            }
        }

        if ($existe) {
            $archivo_rec = __DIR__ . '/../data/recuperaciones.json';
            if (!file_exists(__DIR__ . '/../data')) mkdir(__DIR__ . '/../data', 0777, true);
            $recuperaciones = file_exists($archivo_rec) ? json_decode(file_get_contents($archivo_rec), true) : [];

            // 2. Evitamos que un alumno spammee el botón mil veces
            $ya_pedido = false;
            foreach ($recuperaciones as $r) {
                if ($r['usuario'] === $usuario) {
                    $ya_pedido = true;
                    break;
                }
            }

            // 3. Guardamos el pedido de auxilio
            if (!$ya_pedido) {
                $recuperaciones[] = [
                    'usuario' => $usuario,
                    'fecha' => date('d-m-Y H:i')
                ];
                file_put_contents($archivo_rec, json_encode($recuperaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
    // Por seguridad, siempre mostramos éxito, exista o no el usuario (para evitar escaneo de cuentas)
    header('Location: ../login.php?msj=recuperacion_enviada');
    exit;
}