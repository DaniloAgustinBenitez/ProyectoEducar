<?php
session_start();

// Control de seguridad: solo los profesores pueden eliminar notas
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno = $_POST['alumno'] ?? '';
    $materia = $_POST['materia'] ?? '';
    $indice = $_POST['indice'] ?? '';

    // Verificamos que no falte ningún dato y que el índice sea un número
    if ($alumno !== '' && $materia !== '' && is_numeric($indice)) {
        $archivo_notas = __DIR__ . '/../data/calificaciones.json';
        
        if (file_exists($archivo_notas)) {
            $notas_actuales = json_decode(file_get_contents($archivo_notas), true) ?: [];

            // Verificamos si existe la nota exacta en el registro
            if (isset($notas_actuales[$alumno][$materia][$indice])) {
                
                // Eliminamos ese examen específico del array
                unset($notas_actuales[$alumno][$materia][$indice]);
                
                // Re-ordenamos los índices del array para que no queden "huecos" numéricos
                $notas_actuales[$alumno][$materia] = array_values($notas_actuales[$alumno][$materia]);

                // Guardamos el JSON limpio
                file_put_contents($archivo_notas, json_encode($notas_actuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
}

// Devolvemos al profesor a la vista en la que estaba
header('Location: ../dashboard.php?vista=vista-gestion-aula');
exit;
?>