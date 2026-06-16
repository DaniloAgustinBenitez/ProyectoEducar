<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_b = trim($_POST['usuario_blanquear'] ?? '');

    if (!empty($usuario_b)) {
        // 1. Buscamos al usuario y le seteamos la clave por defecto
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
        
        foreach ($usuarios as &$u) {
            if ($u['usuario'] === $usuario_b) {
                $u['password'] = password_hash('123', PASSWORD_DEFAULT); // CLAVE GENÉRICA
                break;
            }
        }
        file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Lo borramos de la lista de solicitudes de emergencia
        $archivo_rec = __DIR__ . '/../data/recuperaciones.json';
        $recuperaciones = file_exists($archivo_rec) ? json_decode(file_get_contents($archivo_rec), true) : [];
        
        $recuperaciones = array_filter($recuperaciones, function($r) use ($usuario_b) {
            return $r['usuario'] !== $usuario_b;
        });
        
        file_put_contents($archivo_rec, json_encode(array_values($recuperaciones), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// Lo mandamos de vuelta mostrando el cartelito azul de modificación exitosa
header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_modificado');
exit;