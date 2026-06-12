<?php
session_start();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc']) && !empty($nombre_alumno)) {
    $tipo_doc = $_POST['tipo_doc'] ?? 'Documento';
    
    // --- ESCUDO DE SEGURIDAD (PARCHE) ---
    $archivo_tmp = $_FILES['archivo_doc']['tmp_name'];
    $nombre_original = $_FILES['archivo_doc']['name'];
    $error_carga = $_FILES['archivo_doc']['error'];

    // 1. Verificamos que no haya errores de carga por límite de peso
    if ($error_carga === UPLOAD_ERR_OK) {
        
        // 2. Leemos el ADN real del archivo (MIME Type)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $archivo_tmp);
        finfo_close($finfo);

        // 3. Extraemos la extensión del nombre
        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        // 4. Listas blancas (Lo único que aceptamos)
        $mimes_permitidos = ['application/pdf', 'image/jpeg', 'image/png'];
        $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];

        // Si es un archivo falso o no permitido, frenamos todo
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
    }
}

$url_retorno = '../dashboard.php?vista=vista-documentacion';
if ($rol_actual === 'tutor' && !empty($nombre_alumno)) {
    $url_retorno .= '&hijo=' . urlencode($nombre_alumno);
}
header('Location: ' . $url_retorno);
exit;
?>