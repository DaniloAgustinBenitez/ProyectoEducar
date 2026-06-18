<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

// 1. Llamamos a nuestros dos puentes maestros
require_once 'mailer.php';
require_once 'conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutor = trim($_POST['tutor'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $alumno = trim($_POST['alumno'] ?? '');
    $dni_alumno = trim($_POST['dni_alumno'] ?? '');
    $fecha_nacimiento_alumno = trim($_POST['fecha_nacimiento_alumno'] ?? '');
    $nivel = trim($_POST['nivel'] ?? '');
    $disponibilidad = trim($_POST['disponibilidad'] ?? '');

    if (!empty($tutor) && !empty($email) && !empty($telefono)) {
        
        try {
            // --- TRUCO DE MAPEO ---
            // Juntamos algunos datos para que entren perfecto en nuestra estructura SQL
            $tutor_y_alumno = $tutor . ' (Padre de ' . $alumno . ')';
            $nivel_y_disp = $nivel . ' | Disp: ' . $disponibilidad;

            // --- MOTOR DE GUARDADO: INSERT INTO MYSQL ---
            $sql = "INSERT INTO admisiones (dni_tutor, nombre_tutor, email, telefono, nivel_interes, fecha_nacimiento_alumno, estado)
                    VALUES (:dni, :nombre, :email, :telefono, :nivel, :fnac, 'pendiente')";
            
            $stmt = $pdo->prepare($sql);
            
            // Guardamos el DNI del alumno en la columna del tutor para no perderlo
            $stmt->bindParam(':dni', $dni_alumno, PDO::PARAM_STR); 
            $stmt->bindParam(':nombre', $tutor_y_alumno, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
            $stmt->bindParam(':nivel', $nivel_y_disp, PDO::PARAM_STR);
            $fnac = !empty($fecha_nacimiento_alumno) ? $fecha_nacimiento_alumno : null;
            $stmt->bindParam(':fnac', $fnac, PDO::PARAM_STR);

            $stmt->execute();
            // ---------------------------------------------

            // --- ENVÍO DE CORREO REAL (BREVO) ---
            $asunto_mail = 'Solicitud de Entrevista Recibida - Educar para Transformar';
            $cuerpo_mail = "Hola $tutor,\n\nHemos recibido correctamente la solicitud de admisión para el aspirante $alumno.\n\nEn breve, nuestro equipo de administración asignará un turno basado en tu preferencia horaria ($disponibilidad) y te llegará un nuevo correo con la fecha y hora exacta.\n\nSaludos cordiales,\nEquipo de Admisiones.";
            
            enviar_correo_real($email, $asunto_mail, $cuerpo_mail);
            // ------------------------------------

        } catch (PDOException $e) {
            // Falla silenciosa de BD para no mostrarle errores de código al padre
            error_log("Error insertando entrevista: " . $e->getMessage());
        }
    }
}

// Volvemos a la página pública con el cartel de éxito
header('Location: ../inscripcion.html?msj=exito');
exit;
?>