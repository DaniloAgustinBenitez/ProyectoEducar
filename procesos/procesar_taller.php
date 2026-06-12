<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = $_SESSION['rol'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    $archivo_talleres_din = __DIR__ . '/../data/talleres_dinamicos.json';
    if (!file_exists(__DIR__ . '/../data')) mkdir(__DIR__ . '/../data', 0777, true);
    $talleres = file_exists($archivo_talleres_din) ? json_decode(file_get_contents($archivo_talleres_din), true) : [];

    // --- ACCIONES EXCLUSIVAS DEL ADMINISTRADOR ---
    if ($rol_actual === 'admin') {
        if ($accion === 'crear') {
            $nuevo_taller = [
                'id' => uniqid('tal_'),
                'titulo' => trim($_POST['titulo'] ?? ''),
                'nivel' => trim($_POST['nivel'] ?? ''),
                'fecha' => trim($_POST['fecha'] ?? ''),
                'hora' => trim($_POST['hora'] ?? ''),
                'lugar' => trim($_POST['lugar'] ?? ''),
                'inscriptos' => []
            ];
            
            if (!empty($nuevo_taller['titulo']) && !empty($nuevo_taller['fecha'])) {
                $talleres[] = $nuevo_taller;
                file_put_contents($archivo_talleres_din, json_encode($talleres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        if ($accion === 'borrar') {
            $id_borrar = $_POST['id_taller'] ?? '';
            $talleres = array_filter($talleres, function($t) use ($id_borrar) {
                return $t['id'] !== $id_borrar;
            });
            file_put_contents($archivo_talleres_din, json_encode(array_values($talleres), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        header('Location: ../dashboard.php?vista=vista-grid-talleres&vista=vista-admin-talleres');
        exit;
    }

    // --- ACCIONES EXCLUSIVAS DEL ALUMNO ---
    if ($rol_actual === 'alumno') {
        $id_taller = trim($_POST['id_taller'] ?? '');
        
        // Conseguimos el nombre completo real de la cuenta
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
        $nombre_alumno = $_SESSION['usuario'];
        foreach ($usuarios as $u) {
            if ($u['usuario'] === $_SESSION['usuario']) { 
                $nombre_alumno = $u['nombre']; 
                break; 
            }
        }

        if ($accion === 'inscribir') {
            foreach ($talleres as &$t) {
                if ($t['id'] === $id_taller) {
                    if (!isset($t['inscriptos'])) $t['inscriptos'] = [];
                    if (!in_array($nombre_alumno, $t['inscriptos'])) {
                        $t['inscriptos'][] = $nombre_alumno;
                    }
                    break;
                }
            }
        }

        if ($accion === 'baja') {
            foreach ($talleres as &$t) {
                if ($t['id'] === $id_taller && isset($t['inscriptos'])) {
                    $t['inscriptos'] = array_filter($t['inscriptos'], function($a) use ($nombre_alumno) {
                        return $a !== $nombre_alumno;
                    });
                    $t['inscriptos'] = array_values($t['inscriptos']); // Resetea índices numéricos
                    break;
                }
            }
        }

        file_put_contents($archivo_talleres_din, json_encode($talleres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: ../dashboard.php?vista=vista-talleres');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
?>