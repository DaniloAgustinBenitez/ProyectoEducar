<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_actividad = trim($_POST['id_actividad'] ?? '');
    $texto_respuesta = trim($_POST['texto_respuesta'] ?? '');

    $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
    $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
    $nombre_alumno = $_SESSION['usuario'];
    foreach ($usuarios as $u) {
        if ($u['usuario'] === $_SESSION['usuario']) { $nombre_alumno = $u['nombre']; break; }
    }

    $ruta_web_entrega = '';
    
    // --- ESCUDO DE SEGURIDAD (PARCHE) ---
    if (isset($_FILES['archivo_entrega']) && $_FILES['archivo_entrega']['error'] === UPLOAD_ERR_OK) {
        $archivo_tmp = $_FILES['archivo_entrega']['tmp_name'];
        $nombre_original = $_FILES['archivo_entrega']['name'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $archivo_tmp);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        // Permitimos PDFs, Imágenes y documentos de Word
        $mimes_permitidos = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];

        if (in_array($mime_real, $mimes_permitidos) && in_array($extension, $extensiones_permitidas)) {
            $dir_subidas = __DIR__ . '/../uploads/';
            if (!file_exists($dir_subidas)) mkdir($dir_subidas, 0777, true);
            
            $nombre_archivo = time() . "_alum_" . basename($nombre_original);
            if (move_uploaded_file($archivo_tmp, $dir_subidas . $nombre_archivo)) {
                $ruta_web_entrega = 'uploads/' . $nombre_archivo;
            }
        }
    }

    if (!empty($id_actividad) && (!empty($texto_respuesta) || !empty($ruta_web_entrega))) {
        $archivo_actividades = __DIR__ . '/../data/actividades.json';
        if (file_exists($archivo_actividades)) {
            $actividades = json_decode(file_get_contents($archivo_actividades), true) ?: [];
            
            foreach ($actividades as &$act) {
                if ($act['id'] === $id_actividad) {
                    $act['entregas'][$nombre_alumno] = [
                        'fecha' => date('d/m/Y H:i'),
                        'texto' => $texto_respuesta,
                        'archivo' => $ruta_web_entrega,
                        'devolucion' => '',
                        'nota' => ''
                    ];
                    break;
                }
            }
            file_put_contents($archivo_actividades, json_encode($actividades, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>