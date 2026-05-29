<?php
// Forzamos a PHP a mostrar errores por si algo falla en el servidor
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Seguridad: Solo alumnos pueden inscribirse o darse de baja
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] === 'Profesor') {
    die("<h1>Acceso Denegado</h1><p>Solo los alumnos pueden gestionar sus talleres.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno = $_SESSION['usuario']; 
    $accion = $_POST['accion'] ?? '';
    $taller_id = $_POST['taller_id'] ?? '';

    if (empty($taller_id)) {
        die("<h1>Error</h1><p>No se especificó ningún taller.</p>");
    }

    $archivo_talleres = __DIR__ . '/../data/talleres.json';
    $datos = [];

    // 2. Leemos el archivo JSON y validamos su contenido
    if (file_exists($archivo_talleres)) {
        $contenido = file_get_contents($archivo_talleres);
        $datos = json_decode($contenido, true);
        
        // Si el JSON está corrupto o vacío, lo inicializamos como array
        if (!is_array($datos)) { $datos = []; }
    }

    // 3. Nos aseguramos de que el alumno tenga su lista
    if (!isset($datos[$alumno])) {
        $datos[$alumno] = [];
    }

    // 4. Procesamos la acción
    if ($accion === 'inscribir') {
        if (!in_array($taller_id, $datos[$alumno])) {
            $datos[$alumno][] = $taller_id;
        }
    } elseif ($accion === 'baja') {
        $datos[$alumno] = array_filter($datos[$alumno], function($id) use ($taller_id) {
            return $id !== $taller_id;
        });
        $datos[$alumno] = array_values($datos[$alumno]); // Reindexar
    }

    // 5. Intentamos guardar los cambios
    $resultado = file_put_contents($archivo_talleres, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($resultado === false) {
        die("<h1>Error de Escritura</h1><p>No se pudo escribir en 'talleres.json'. Revisá los permisos de la carpeta.</p>");
    }

    // 6. Si todo salió bien, volvemos al dashboard
    header('Location: ../dashboard.php?vista=vista-talleres');
    exit;

} else {
    die("<h1>Acceso Directo Prohibido</h1><p>Debes usar el formulario del panel.</p>");
}
?>