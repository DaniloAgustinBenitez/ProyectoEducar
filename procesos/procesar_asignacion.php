<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $profesor = trim($_POST['profesor'] ?? '');
    
    // AHORA RECIBIMOS EL NIVEL DIRECTAMENTE DEL FORMULARIO
    $nivel = $_POST['nivel'] ?? 'secundaria'; 
    $curso = $_POST['curso'] ?? '';
    $division = $_POST['division'] ?? '';
    $materia = $_POST['materia'] ?? '';

    $archivo_asig = __DIR__ . '/../data/asignaciones.json';
    $asignaciones = file_exists($archivo_asig) ? json_decode(file_get_contents($archivo_asig), true) : [];

    if ($accion === 'asignar' && !empty($profesor) && !empty($curso) && !empty($division) && !empty($materia)) {
        if (!isset($asignaciones[$profesor])) {
            $asignaciones[$profesor] = [];
        }

        // Evitamos registros duplicados
        $ya_existe = false;
        foreach ($asignaciones[$profesor] as $asig) {
            if (($asig['nivel'] ?? '') === $nivel && $asig['curso'] === $curso && $asig['division'] === $division && $asig['materia'] === $materia) {
                $ya_existe = true;
                break;
            }
        }

        if (!$ya_existe) {
            $asignaciones[$profesor][] = [
                "nivel" => $nivel,
                "curso" => $curso,
                "division" => $division,
                "turno" => "Mañana",
                "materia" => $materia
            ];
            file_put_contents($archivo_asig, json_encode($asignaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    } 
    
    elseif ($accion === 'quitar' && !empty($profesor) && !empty($curso) && !empty($division) && !empty($materia)) {
        if (isset($asignaciones[$profesor])) {
            foreach ($asignaciones[$profesor] as $indice => $asig) {
                if (($asig['nivel'] ?? '') === $nivel && $asig['curso'] === $curso && $asig['division'] === $division && $asig['materia'] === $materia) {
                    unset($asignaciones[$profesor][$indice]);
                    $asignaciones[$profesor] = array_values($asignaciones[$profesor]); // Reindexamos para limpiar vacíos
                    break;
                }
            }

            if (empty($asignaciones[$profesor])) {
                unset($asignaciones[$profesor]);
            }

            file_put_contents($archivo_asig, json_encode($asignaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    // Volvemos con el ancla de la vista para no perdernos
    header('Location: ../dashboard.php?vista=vista-cursos');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>