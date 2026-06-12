<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = $_SESSION['rol'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    $archivo_cap = __DIR__ . '/../data/capacitaciones.json';
    if (!file_exists(__DIR__ . '/../data')) mkdir(__DIR__ . '/../data', 0777, true);
    $capacitaciones = file_exists($archivo_cap) ? json_decode(file_get_contents($archivo_cap), true) : [];

    // --- ACCIONES DEL ADMINISTRADOR ---
    if ($rol_actual === 'admin') {
        if ($accion === 'crear') {
            $nueva_cap = [
                'id' => uniqid('cap_'),
                'titulo' => trim($_POST['titulo'] ?? ''),
                'fecha' => trim($_POST['fecha'] ?? ''),
                'hora' => trim($_POST['hora'] ?? ''),
                'lugar' => trim($_POST['lugar'] ?? ''),
                'inscriptos' => []
            ];
            
            if (!empty($nueva_cap['titulo']) && !empty($nueva_cap['fecha'])) {
                $capacitaciones[] = $nueva_cap;
                file_put_contents($archivo_cap, json_encode($capacitaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        if ($accion === 'borrar') {
            $id_borrar = $_POST['id_cap'] ?? '';
            $capacitaciones = array_filter($capacitaciones, function($c) use ($id_borrar) {
                return $c['id'] !== $id_borrar;
            });
            file_put_contents($archivo_cap, json_encode(array_values($capacitaciones), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        
        header('Location: ../dashboard.php?vista=vista-admin-capacitaciones');
        exit;
    }

    // --- ACCIONES DE LOS DOCENTES (Profesores, Maestros, Preceptores) ---
    if ($rol_actual === 'profesor' || $rol_actual === 'preceptor') {
        $id_cap = trim($_POST['id_cap'] ?? '');
        
        // Buscamos el nombre completo del docente para anotarlo
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
        $nombre_docente = $_SESSION['usuario'];
        foreach ($usuarios as $u) {
            if ($u['usuario'] === $_SESSION['usuario']) { 
                $nombre_docente = $u['nombre']; 
                break; 
            }
        }

        if ($accion === 'inscribir') {
            foreach ($capacitaciones as &$cap) {
                if ($cap['id'] === $id_cap) {
                    if (!isset($cap['inscriptos'])) $cap['inscriptos'] = [];
                    if (!in_array($nombre_docente, $cap['inscriptos'])) {
                        $cap['inscriptos'][] = $nombre_docente;
                    }
                    break;
                }
            }
        }

        if ($accion === 'baja') {
            foreach ($capacitaciones as &$cap) {
                if ($cap['id'] === $id_cap && isset($cap['inscriptos'])) {
                    $cap['inscriptos'] = array_filter($cap['inscriptos'], function($d) use ($nombre_docente) {
                        return $d !== $nombre_docente;
                    });
                    $cap['inscriptos'] = array_values($cap['inscriptos']); // Reorganizamos el array
                    break;
                }
            }
        }

        file_put_contents($archivo_cap, json_encode($capacitaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: ../dashboard.php?vista=vista-recursos-salud');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
?>