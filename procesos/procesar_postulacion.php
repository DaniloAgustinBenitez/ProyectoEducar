<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $area = trim($_POST['area'] ?? '');
    
    // --- PROCESAMIENTO SEGURO DEL ARCHIVO CV (PDF) ---
    $cv_ruta_relativa = '';
    
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['cv']['tmp_name'];
        $file_name = $_FILES['cv']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === 'pdf') {
            $dir_subida = __DIR__ . '/../uploads/cvs/';
            
            // Si la carpeta de almacenamiento no existe, la creamos mágicamente
            if (!is_dir($dir_subida)) {
                mkdir($dir_subida, 0777, true);
            }
            
            // Renombramos el archivo para que no se pisen si dos personas suben "cv.pdf"
            $nuevo_nombre = uniqid('cv_') . '.pdf';
            
            if (move_uploaded_file($file_tmp, $dir_subida . $nuevo_nombre)) {
                $cv_ruta_relativa = 'uploads/cvs/' . $nuevo_nombre;
            }
        } else {
            header('Location: ../empleo.html?error=archivo');
            exit;
        }
    }

    if (!empty($nombre) && !empty($email) && !empty($cv_ruta_relativa)) {
        $archivo_postulaciones = __DIR__ . '/../data/postulaciones.json';
        $postulaciones = file_exists($archivo_postulaciones) ? json_decode(file_get_contents($archivo_postulaciones), true) : [];

        $nuevo_id = uniqid('post_');
        $fecha_actual = date('d-m-Y H:i');

        $postulaciones[] = [
            'id' => $nuevo_id,
            'fecha_solicitud' => $fecha_actual,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'area' => $area,
            'cv' => $cv_ruta_relativa,
            'estado' => 'pendiente',
            'fecha_entrevista' => '',
            'hora_entrevista' => ''
        ];
        
        file_put_contents($archivo_postulaciones, json_encode($postulaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // --- DISPARADOR DE CORREO ELECTRÓNICO REAL (BREVO) ---
        $asunto_mail = 'Postulación Recibida - Recursos Humanos';
        $cuerpo_mail = "Hola $nombre,\n\nHemos recibido correctamente tus antecedentes y currículum adjunto para el área de interest: $area.\n\nTu postulación ya se encuentra bajo evaluación de nuestro equipo de selección para el ciclo lectivo 2027. En caso de que tu perfil se adapte a nuestras vacantes vigentes, te llegará un nuevo correo automático para agendar una entrevista presencial.\n\nAgradecemos sinceramente tu interés en formar parte de nuestra institución.\n\nSaludos cordiales,\nÁrea de Recursos Humanos\nEducar para Transformar.";
        
        enviar_correo_real($email, $asunto_mail, $cuerpo_mail);
        // -----------------------------------------------------

        header('Location: ../empleo.html?msj=exito');
        exit;
    }
}
header('Location: ../index.html');
exit;
?>