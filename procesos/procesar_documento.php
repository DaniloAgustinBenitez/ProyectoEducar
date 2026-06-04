<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_actual = $_SESSION['usuario'];
$rol_actual = $_SESSION['rol'] ?? 'alumno';

// 1. DETERMINAMOS EL DUEÑO DEL DOCUMENTO
$nombre_alumno = '';

if ($rol_actual === 'tutor') {
    // Si es tutor, leemos el input oculto que mandamos desde el dashboard
    $nombre_alumno = trim($_POST['alumno_destino'] ?? '');
} else {
    // Si es alumno, buscamos su propio nombre en la base de datos
    $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
    $todos_usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
    $nombre_alumno = $usuario_actual; // Fallback de emergencia
    foreach ($todos_usuarios as $u) {
        if ($u['usuario'] === $usuario_actual) {
            $nombre_alumno = $u['nombre'];
            break;
        }
    }
}

// 2. PROCESAMOS EL ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc']) && !empty($nombre_alumno)) {
    $tipo_doc = $_POST['tipo_doc'] ?? 'Documento';
    
    // Rutas protegidas (subimos un nivel porque estamos en la carpeta procesos/)
    $directorio_subidas_fisico = __DIR__ . '/../uploads/';
    if (!file_exists($directorio_subidas_fisico)) {
        mkdir($directorio_subidas_fisico, 0777, true);
    }

    $nombre_archivo = time() . "_" . basename($_FILES['archivo_doc']['name']);
    $ruta_destino = $directorio_subidas_fisico . $nombre_archivo;
    
    // Esta es la ruta que se guarda en el JSON para que el navegador la encuentre (desde la raíz)
    $ruta_web = 'uploads/' . $nombre_archivo; 

    if (move_uploaded_file($_FILES['archivo_doc']['tmp_name'], $ruta_destino)) {
        
        $archivo_docs = __DIR__ . '/../data/documentos.json';
        $docs = file_exists($archivo_docs) ? json_decode(file_get_contents($archivo_docs), true) : [];

        if (!isset($docs[$nombre_alumno])) {
            $docs[$nombre_alumno] = [];
        }

        // MAGIA: Si es infinito, lo apilamos. Si es único, lo reemplazamos.
        if ($tipo_doc === 'Certificado Médico / Justificación de Falta' || $tipo_doc === 'Permiso de Retiro') {
            if (!isset($docs[$nombre_alumno][$tipo_doc]) || !is_array($docs[$nombre_alumno][$tipo_doc])) {
                $docs[$nombre_alumno][$tipo_doc] = [];
            }
            $docs[$nombre_alumno][$tipo_doc][] = [
                'fecha' => date('d-m-Y H:i'),
                'archivo' => $ruta_web
            ];
        } else {
            $docs[$nombre_alumno][$tipo_doc] = [
                'fecha' => date('d-m-Y'),
                'archivo' => $ruta_web
            ];
        }

        file_put_contents($archivo_docs, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

// 3. REDIRIGIMOS AL DASHBOARD (Manteniendo al hijo seleccionado en la URL)
$url_retorno = '../dashboard.php?vista=vista-documentacion';
if ($rol_actual === 'tutor' && !empty($nombre_alumno)) {
    $url_retorno .= '&hijo=' . urlencode($nombre_alumno);
}

header('Location: ' . $url_retorno);
exit;
?>