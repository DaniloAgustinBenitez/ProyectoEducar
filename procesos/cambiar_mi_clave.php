<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $nombre_sesion = $_SESSION['usuario']; // Recuperamos la identidad desde la sesión

    if (!empty($password_actual) && !empty($password_nueva)) {
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
        
        $encontrado = false;
        $clave_correcta = false;

        // Buscamos al usuario comparando con el nombre en la sesión
        foreach ($usuarios as &$u) {
            if ($u['nombre'] === $nombre_sesion) {
                $encontrado = true;
                if (password_verify($password_actual, $u['password'])) {
                    $u['password'] = password_hash($password_nueva, PASSWORD_DEFAULT);
                    $clave_correcta = true;
                }
                break;
            }
        }

        // Resguardo alternativo si la sesión tuviera guardado el ID de usuario en vez del nombre completo
        if (!$encontrado) {
            foreach ($usuarios as &$u) {
                if ($u['usuario'] === $nombre_sesion) {
                    $encontrado = true;
                    if (password_verify($password_actual, $u['password'])) {
                        $u['password'] = password_hash($password_nueva, PASSWORD_DEFAULT);
                        $clave_correcta = true;
                    }
                    break;
                }
            }
        }

        // Guardamos o redireccionamos según corresponda
        if ($clave_correcta) {
            file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ../dashboard.php?msj=clave_actualizada');
            exit;
        } else {
            header('Location: ../dashboard.php?error=clave_incorrecta');
            exit;
        }
    }
}

header('Location: ../dashboard.php');
exit;
?>