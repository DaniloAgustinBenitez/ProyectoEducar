<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_buscado = $_POST['id_entrevista'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!empty($id_buscado) && !empty($fecha) && !empty($hora)) {
        $archivo_entrevistas = __DIR__ . '/../data/entrevistas.json';
        $entrevistas = file_exists($archivo_entrevistas) ? json_decode(file_get_contents($archivo_entrevistas), true) : [];

        // Buscamos la entrevista y le actualizamos el estado
        foreach ($entrevistas as &$ent) {
            if ($ent['id'] === $id_buscado) {
                $ent['estado'] = 'agendada';
                $ent['fecha_agendada'] = $fecha;
                $ent['hora_agendada'] = $hora;
                break;
            }
        }

        file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Lo mandamos al dashboard con la señal de éxito para que salte el cartel de Simulación de Correo
        header('Location: ../dashboard.php?vista=vista-entrevistas&msj=entrevista_agendada');
        exit;
    }
}

header('Location: ../dashboard.php?vista=vista-entrevistas');
exit;
?>