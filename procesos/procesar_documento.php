<?php
session_start();

date_default_timezone_set('America/Argentina/Buenos_Aires');
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_actual = $_SESSION['usuario'];
$rol_actual = $_SESSION['rol'] ?? 'alumno';

$nombre_alumno = '';
if ($rol_actual === 'tutor') {
    $nombre_alumno = trim($_POST['alumno_destino'] ?? '');
} else {
    $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
    $todos_usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
    $nombre_alumno = $usuario_actual; 
    foreach ($todos_usuarios as $u) {
        if ($u['usuario'] === $usuario_actual) {
            $nombre_alumno = $u['nombre'];
            break;
        }
    }
}

$estado_subida = ''; // Variable que guarda el resultado para mostrar el cartel

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc']) && !empty($nombre_alumno)) {
    $tipo_doc = $_POST['tipo_doc'] ?? 'Documento';
    
    $archivo_tmp = $_FILES['archivo_doc']['tmp_name'];
    $nombre_original = $_FILES['archivo_doc']['name'];
    $error_carga = $_FILES['archivo_doc']['error'];

    // 1. Verificamos si XAMPP bloqueó el archivo por pesar más de 2 MB
    if ($error_carga === UPLOAD_ERR_INI_SIZE || $error_carga === UPLOAD_ERR_FORM_SIZE) {
        $estado_subida = 'error_peso';
    } 
    // 2. Si no hubo errores de peso, procedemos al análisis de seguridad
    elseif ($error_carga === UPLOAD_ERR_OK) {
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $archivo_tmp);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        // Agregamos 'application/x-pdf' por si tu navegador manda el PDF con un formato alternativo
        $mimes_permitidos = ['application/pdf', 'application/x-pdf', 'image/jpeg', 'image/png'];
        $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($mime_real, $mimes_permitidos) && in_array($extension, $extensiones_permitidas)) {
            
            $directorio_subidas_fisico = __DIR__ . '/../uploads/';
            if (!file_exists($directorio_subidas_fisico)) mkdir($directorio_subidas_fisico, 0777, true);

            $nombre_archivo = time() . "_" . basename($nombre_original);
            $ruta_destino = $directorio_subidas_fisico . $nombre_archivo;
            $ruta_web = 'uploads/' . $nombre_archivo; 

            if (move_uploaded_file($archivo_tmp, $ruta_destino)) {
                $archivo_docs = __DIR__ . '/../data/documentos.json';
                $docs = file_exists($archivo_docs) ? json_decode(file_get_contents($archivo_docs), true) : [];

                if (!isset($docs[$nombre_alumno])) $docs[$nombre_alumno] = [];

                if ($tipo_doc === 'Certificado Médico / Justificación de Falta' || $tipo_doc === 'Permiso de Retiro') {
                    if (!isset($docs[$nombre_alumno][$tipo_doc]) || !is_array($docs[$nombre_alumno][$tipo_doc])) {
                        $docs[$nombre_alumno][$tipo_doc] = [];
                    }
                    $docs[$nombre_alumno][$tipo_doc][] = [
                        'fecha' => date('d-m-Y H:i'),
                        'archivo' => $ruta_web,
                        'estado' => 'pendiente' // PARCHE LOGICO: Nace pendiente de revisión
                    ];
                } else {
                    $docs[$nombre_alumno][$tipo_doc] = [
                        'fecha' => date('d-m-Y'),
                        'archivo' => $ruta_web
                    ];
                }
                file_put_contents($archivo_docs, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                
                $estado_subida = 'exito'; // Todo salió perfecto
            } else {
                $estado_subida = 'error_mover';
            }
        } else {
            $estado_subida = 'error_seguridad'; // Es un archivo disfrazado
        }
    } else {
        $estado_subida = 'error_peso'; // Error general de servidor
    }
}

// Redirección inteligente que avisa qué cartel mostrar
$url_retorno = '../dashboard.php?vista=vista-documentacion';
if ($rol_actual === 'tutor' && !empty($nombre_alumno)) {
    $url_retorno .= '&hijo=' . urlencode($nombre_alumno);
}
if ($estado_subida !== '') {
    $url_retorno .= '&subida=' . $estado_subida;
}
header('Location: ' . $url_retorno);
exit;
?>