<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_actual = $_SESSION['usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $curso_nombre = $_POST['curso_nombre'] ?? '';

    $archivo_cap = __DIR__ . '/../data/capacitaciones.json';
    $capacitaciones = file_exists($archivo_cap) ? json_decode(file_get_contents($archivo_cap), true) : [];

    if ($accion === 'inscribir' && !empty($curso_nombre)) {
        // Evitamos que se anote dos veces
        $ya_inscripto = false;
        foreach ($capacitaciones as $cap) {
            if ($cap['profesor'] === $usuario_actual && $cap['curso'] === $curso_nombre) {
                $ya_inscripto = true;
                break;
            }
        }
        
        if (!$ya_inscripto) {
            $capacitaciones[] = [
                'profesor' => $usuario_actual,
                'curso' => $curso_nombre,
                'fecha_inscripcion' => date('d-m-Y H:i')
            ];
        }
    } 
    elseif ($accion === 'baja' && !empty($curso_nombre)) {
        foreach ($capacitaciones as $key => $cap) {
            if ($cap['profesor'] === $usuario_actual && $cap['curso'] === $curso_nombre) {
                unset($capacitaciones[$key]);
            }
        }
        // Reordenamos el JSON para que no queden huecos
        $capacitaciones = array_values($capacitaciones);
    }

    // Guardamos los cambios
    file_put_contents($archivo_cap, json_encode($capacitaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Lo devolvemos directo a la pestaña de Salud y Recursos
    header('Location: ../dashboard.php?vista=vista-recursos-salud');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>