<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

$archivo_actividades = __DIR__ . '/../data/actividades.json';
if (!file_exists(__DIR__ . '/../data')) mkdir(__DIR__ . '/../data', 0777, true);
$actividades = file_exists($archivo_actividades) ? json_decode(file_get_contents($archivo_actividades), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // CREAR ACTIVIDAD
    if ($accion === 'crear') {
        $curso_destino = explode('|', $_POST['curso_destino'] ?? ''); // Viene como "5_anio|A|Matematica"
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_limite = trim($_POST['fecha_limite'] ?? '');
        
        $ruta_web_adjunto = '';
        if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
            $dir_subidas = __DIR__ . '/../uploads/';
            if (!file_exists($dir_subidas)) mkdir($dir_subidas, 0777, true);
            
            $nombre_archivo = time() . "_profe_" . basename($_FILES['archivo_adjunto']['name']);
            if (move_uploaded_file($_FILES['archivo_adjunto']['tmp_name'], $dir_subidas . $nombre_archivo)) {
                $ruta_web_adjunto = 'uploads/' . $nombre_archivo;
            }
        }

        if (count($curso_destino) === 3 && !empty($titulo) && !empty($fecha_limite)) {
            // Conseguimos el nombre completo del profe
            $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
            $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
            $nombre_profe = $_SESSION['usuario'];
            foreach ($usuarios as $u) {
                if ($u['usuario'] === $_SESSION['usuario']) { $nombre_profe = $u['nombre']; break; }
            }

            $nueva_actividad = [
                'id' => uniqid('act_'),
                'profesor' => $nombre_profe,
                'curso' => $curso_destino[0],
                'division' => $curso_destino[1],
                'materia' => $curso_destino[2],
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'fecha_limite' => $fecha_limite,
                'archivo_adjunto' => $ruta_web_adjunto,
                'entregas' => []
            ];

            $actividades[] = $nueva_actividad;
            file_put_contents($archivo_actividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    // BORRAR ACTIVIDAD
    if ($accion === 'borrar') {
        $id_borrar = $_POST['id_actividad'] ?? '';
        $actividades = array_filter($actividades, function($a) use ($id_borrar) {
            return $a['id'] !== $id_borrar;
        });
        file_put_contents($archivo_actividades, json_encode(array_values($actividades), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>