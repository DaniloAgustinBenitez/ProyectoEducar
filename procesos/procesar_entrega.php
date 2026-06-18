<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'alumno') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_actividad = trim($_POST['id_actividad'] ?? '');
    $texto_respuesta = trim($_POST['texto_respuesta'] ?? '');

    require_once 'conexion.php';

    $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
    $stmt_u->execute([':user' => $_SESSION['usuario']]);
    $u = $stmt_u->fetch();
    $nombre_alumno = $u ? $u['nombre'] : $_SESSION['usuario'];

    $ruta_web_entrega = '';

    // --- ESCUDO DE SEGURIDAD (PARCHE) ---
    if (isset($_FILES['archivo_entrega']) && $_FILES['archivo_entrega']['error'] === UPLOAD_ERR_OK) {
        $archivo_tmp     = $_FILES['archivo_entrega']['tmp_name'];
        $nombre_original = $_FILES['archivo_entrega']['name'];

        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mime_real = finfo_file($finfo, $archivo_tmp);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

        $mimes_permitidos      = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
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
        try {
            $sql = "INSERT INTO actividad_entregas (actividad_id, alumno_nombre, fecha, texto, archivo, devolucion, nota)
                    VALUES (:act_id, :alumno, :fecha, :texto, :archivo, '', '')
                    ON DUPLICATE KEY UPDATE
                        fecha    = VALUES(fecha),
                        texto    = VALUES(texto),
                        archivo  = VALUES(archivo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':act_id'  => $id_actividad,
                ':alumno'  => $nombre_alumno,
                ':fecha'   => date('Y-m-d H:i:s'),
                ':texto'   => $texto_respuesta,
                ':archivo' => $ruta_web_entrega
            ]);
        } catch (PDOException $e) {
            error_log("Error guardando entrega: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>