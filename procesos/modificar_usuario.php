<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $original = trim($_POST['usuario_original'] ?? '');
    $nuevo = trim($_POST['nuevo_usuario'] ?? '');
    $password = $_POST['nueva_password'] ?? '';

    if (!empty($original) && !empty($nuevo) && !empty($password)) {
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];

        // 1. Validamos que el nuevo nombre no esté duplicado por otra persona
        if (strtolower($original) !== strtolower($nuevo)) {
            foreach ($usuarios as $u) {
                if (strtolower($u['usuario']) === strtolower($nuevo)) {
                    header('Location: ../dashboard.php?vista=vista-usuarios&error=duplicado');
                    exit;
                }
            }
        }

        // 2. Modificamos los valores del usuario
        foreach ($usuarios as &$u) {
            if ($u['usuario'] === $original) {
                $u['usuario'] = $nuevo;
                $u['password'] = $password;
                break;
            }
        }
        file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Si cambió el login de un Tutor, actualizamos el mapa relacional
        $archivo_tutores = __DIR__ . '/../data/tutores_alumnos.json';
        if (file_exists($archivo_tutores)) {
            $tutores = json_decode(file_get_contents($archivo_tutores), true) ?: [];
            if (isset($tutores[$original])) {
                $hijos_guardados = $tutores[$original];
                unset($tutores[$original]); // Borramos la llave vieja
                $tutores[$nuevo] = $hijos_guardados; // Creamos la llave nueva con sus hijos intactos
                file_put_contents($archivo_tutores, json_encode($tutores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
    header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_modificado');
    exit;
}