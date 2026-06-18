<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

// 1. Llamamos a tus dos puentes: el de correos y el de la Base de Datos
require_once 'mailer.php';
require_once 'conexion.php'; 

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
            
            if (!is_dir($dir_subida)) {
                mkdir($dir_subida, 0777, true);
            }
            
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
        
        try {
            // --- NUEVO MOTOR DE GUARDADO: INSERT INTO MYSQL ---
            // Usamos sentencias preparadas para máxima seguridad contra inyecciones SQL
            $sql = "INSERT INTO rrhh_postulaciones (nombre_completo, email, telefono, area_interes, ruta_cv, estado) 
                    VALUES (:nombre, :email, :telefono, :area, :cv, 'pendiente')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
            $stmt->bindParam(':area', $area, PDO::PARAM_STR);
            $stmt->bindParam(':cv', $cv_ruta_relativa, PDO::PARAM_STR);
            
            $stmt->execute();
            // ---------------------------------------------------

            // --- DISPARADOR DE CORREO ELECTRÓNICO REAL (BREVO) ---
            $asunto_mail = 'Postulación Recibida - Recursos Humanos';
            $cuerpo_mail = "Hola $nombre,\n\nHemos recibido correctamente tus antecedentes y currículum adjunto para el área de interés: $area.\n\nTu postulación ya se encuentra bajo evaluación de nuestro equipo de selección para el ciclo lectivo. En caso de que tu perfil se adapte a nuestras vacantes vigentes, te llegará un nuevo correo automático para agendar una entrevista presencial.\n\nAgradecemos sinceramente tu interés en formar parte de nuestra institución.\n\nSaludos cordiales,\nÁrea de Recursos Humanos\nEducar para Transformar.";
            
            enviar_correo_real($email, $asunto_mail, $cuerpo_mail);
            // -----------------------------------------------------

            header('Location: ../empleo.html?msj=exito');
            exit;

        } catch (PDOException $e) {
            // Si la base de datos falla, evitamos que la página se rompa
            error_log("Error insertando postulación en la BD: " . $e->getMessage());
            header('Location: ../empleo.html?error=sistema');
            exit;
        }
    }
}
header('Location: ../index.html');
exit;
?>