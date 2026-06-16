<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $curso_clave = $_POST['curso_clave'] ?? '';
    $alumno_nombre = trim($_POST['alumno_nombre'] ?? '');

    if (!empty($curso_clave) && !empty($alumno_nombre)) {
        $archivo_alumnos_cursos = __DIR__ . '/../data/alumnos_cursos.json';
        $alumnos_por_curso = file_exists($archivo_alumnos_cursos) ? json_decode(file_get_contents($archivo_alumnos_cursos), true) : [];

        // Normalizamos la estructura por si hay valores corruptos
        foreach ($alumnos_por_curso as $key => $lista) {
            if (!is_array($lista)) {
                $alumnos_por_curso[$key] = [];
            }
        }

        if ($accion === 'asignar') {
            // 1. Limpieza preventiva: Quitamos al alumno de cualquier otro curso/año previo
            foreach ($alumnos_por_curso as $key => $lista) {
                $alumnos_por_curso[$key] = array_values(array_filter($lista, function($item) use ($alumno_nombre) {
                    return strtolower(trim($item)) !== strtolower($alumno_nombre);
                }));
            }

            // 2. Lo agregamos al listado del nuevo curso seleccionado
            if (!isset($alumnos_por_curso[$curso_clave])) {
                $alumnos_por_curso[$curso_clave] = [];
            }
            
            // Evitamos duplicarlo por las dudas
            if (!in_array($alumno_nombre, $alumnos_por_curso[$curso_clave])) {
                $alumnos_por_curso[$curso_clave][] = $alumno_nombre;
            }

            file_put_contents($archivo_alumnos_cursos, json_encode($alumnos_por_curso, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ../dashboard.php?vista=vista-asignar-alumnos&msj=alumno_asignado');
            exit;
        } 
        elseif ($accion === 'quitar') {
            if (isset($alumnos_por_curso[$curso_clave])) {
                $alumnos_por_curso[$curso_clave] = array_values(array_filter($alumnos_por_curso[$curso_clave], function($item) use ($alumno_nombre) {
                    return strtolower(trim($item)) !== strtolower($alumno_nombre);
                }));
                file_put_contents($archivo_alumnos_cursos, json_encode($alumnos_por_curso, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            header('Location: ../dashboard.php?vista=vista-asignar-alumnos&msj=alumno_removido');
            exit;
        }
    }
}

header('Location: ../dashboard.php?vista=vista-asignar-alumnos');
exit;
?>