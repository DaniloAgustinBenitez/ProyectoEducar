<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

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
        $archivo_entrevistas = __DIR__ . '/../data/entrevistas.json';
        $entrevistas = file_exists($archivo_entrevistas) ? json_decode(file_get_contents($archivo_entrevistas), true) : [];

        $nuevo_id = uniqid('ent_');
        $fecha_actual = date('d-m-Y H:i');

        $entrevistas[] = [
            'id' => $nuevo_id,
            'fecha_solicitud' => $fecha_actual,
            'tutor' => $tutor,
            'email' => $email,
            'telefono' => $telefono,
            'alumno' => $alumno,
            'dni_alumno' => $dni_alumno,
            'fecha_nacimiento_alumno' => $fecha_nacimiento_alumno,
            'nivel' => $nivel,
            'disponibilidad' => $disponibilidad,
            'estado' => 'pendiente',
            'fecha_agendada' => '',
            'hora_agendada' => ''
        ];
        file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // --- ENVÍO DE CORREO POR MAILTRAP (BIENVENIDA) ---
        require_once 'mailer.php';
        $asunto_mail = 'Solicitud de Entrevista Recibida - Educar para Transformar';
        $cuerpo_mail = "Hola $tutor,\n\nHemos recibido correctamente la solicitud de admisión para el aspirante $alumno.\n\nEn breve, nuestro equipo de administración asignará un turno basado en tu preferencia horaria ($disponibilidad) y te llegará un nuevo correo con la fecha y hora exacta.\n\nSaludos cordiales,\nEquipo de Admisiones.";
        
        enviar_correo_real($email, $asunto_mail, $cuerpo_mail);
        // -------------------------------------------------
    }
}

header('Location: ../inscripcion.html?msj=exito');
exit;
?>