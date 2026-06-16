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

        $entrevistas[] = [
            'id' => $nuevo_id,
            'fecha_solicitud' => date('d-m-Y H:i'),
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
    }
}

header('Location: ../inscripcion.html?msj=exito');
exit;
?>