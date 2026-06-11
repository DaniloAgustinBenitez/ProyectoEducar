<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_a_borrar = trim($_POST['usuario_id'] ?? '');

    if (!empty($usuario_a_borrar)) {
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];

        // Filtramos eliminando al usuario correspondiente
        $usuarios_filtrados = array_filter($usuarios, function($u) use ($usuario_a_borrar) {
            return $u['usuario'] !== $usuario_a_borrar;
        });

        file_put_contents($archivo_usuarios, json_encode(array_values($usuarios_filtrados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Limpieza marginal en tutores_alumnos por si era un tutor
        $archivo_tutores = __DIR__ . '/../data/tutores_alumnos.json';
        if (file_exists($archivo_tutores)) {
            $tutores = json_decode(file_get_contents($archivo_tutores), true) ?: [];
            if (isset($tutores[$usuario_a_borrar])) {
                unset($tutores[$usuario_a_borrar]);
                file_put_contents($archivo_tutores, json_encode($tutores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
    header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_eliminado');
    exit;
}