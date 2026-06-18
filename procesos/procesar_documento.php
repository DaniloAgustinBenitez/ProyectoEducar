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
require_once 'conexion.php';

if ($rol_actual === 'tutor') {
    $nombre_alumno = trim($_POST['alumno_destino'] ?? '');
} else {
    $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
    $stmt_u->execute([':user' => $usuario_actual]);
    $u = $stmt_u->fetch();
    $nombre_alumno = $u ? $u['nombre'] : $usuario_actual;
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
                try {
                    $hoy = date('Y-m-d');
                    if ($tipo_doc === 'Certificado Medico / Justificacion de Falta' || $tipo_doc === 'Permiso de Retiro') {
                        // Verificamos que no haya ya uno para hoy
                        $stmt_dup = $pdo->prepare("SELECT COUNT(*) FROM documentos WHERE alumno_nombre = :alumno AND tipo_doc = :tipo AND fecha = :hoy");
                        $stmt_dup->execute([':alumno' => $nombre_alumno, ':tipo' => $tipo_doc, ':hoy' => $hoy]);
                        if ($stmt_dup->fetchColumn() > 0) {
                            $estado_subida = 'ya_existe_hoy';
                        } else {
                            $stmt_ins = $pdo->prepare("INSERT INTO documentos (alumno_nombre, tipo_doc, fecha, archivo, estado) VALUES (:alumno, :tipo, :fecha, :archivo, 'pendiente')");
                            $stmt_ins->execute([':alumno' => $nombre_alumno, ':tipo' => $tipo_doc, ':fecha' => $hoy, ':archivo' => $ruta_web]);
                            $estado_subida = 'exito';
                        }
                    } else {
                        // Tipos simples: reemplazamos si ya existe uno para ese alumno y tipo
                        $stmt_del = $pdo->prepare("DELETE FROM documentos WHERE alumno_nombre = :alumno AND tipo_doc = :tipo");
                        $stmt_del->execute([':alumno' => $nombre_alumno, ':tipo' => $tipo_doc]);
                        $stmt_ins = $pdo->prepare("INSERT INTO documentos (alumno_nombre, tipo_doc, fecha, archivo) VALUES (:alumno, :tipo, :fecha, :archivo)");
                        $stmt_ins->execute([':alumno' => $nombre_alumno, ':tipo' => $tipo_doc, ':fecha' => $hoy, ':archivo' => $ruta_web]);
                        $estado_subida = 'exito';
                    }
                } catch (PDOException $e) {
                    error_log("Error guardando documento: " . $e->getMessage());
                    $estado_subida = 'error_mover';
                }
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