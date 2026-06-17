<?php
session_start();
require_once 'mailer.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'agendar'; 
    $id_buscado = $_POST['id_postulacion'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!empty($id_buscado)) {
        $archivo_postulaciones = __DIR__ . '/../data/postulaciones.json';
        $postulaciones = file_exists($archivo_postulaciones) ? json_decode(file_get_contents($archivo_postulaciones), true) : [];

        if ($accion === 'borrar') {
            // Eliminamos físicamente el archivo PDF del servidor para no ocupar espacio basura
            foreach ($postulaciones as $p) {
                if ($p['id'] === $id_buscado && !empty($p['cv']) && file_exists(__DIR__ . '/../' . $p['cv'])) {
                    unlink(__DIR__ . '/../' . $p['cv']);
                }
            }
            
            $postulaciones = array_filter($postulaciones, function($p) use ($id_buscado) {
                return $p['id'] !== $id_buscado;
            });
            $postulaciones = array_values($postulaciones); 
            file_put_contents($archivo_postulaciones, json_encode($postulaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            header('Location: ../dashboard.php?vista=vista-rrhh&msj=postulacion_eliminada');
            exit;
        } 
        elseif (!empty($fecha) && !empty($hora)) {
            $email_postulante = '';
            $nombre_postulante = '';
            $area_postulante = '';

            foreach ($postulaciones as &$p) {
                if ($p['id'] === $id_buscado) {
                    $p['estado'] = 'agendada';
                    $p['fecha_entrevista'] = $fecha;
                    $p['hora_entrevista'] = $hora;
                    
                    $email_postulante = $p['email'];
                    $nombre_postulante = $p['nombre'];
                    $area_postulante = $p['area'];
                    break;
                }
            }
            file_put_contents($archivo_postulaciones, json_encode($postulaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // --- ENVÍO DE CORREO REAL DE CITACIÓN LABORAL ---
            if (!empty($email_postulante)) {
                $fecha_formateada = date('d/m/Y', strtotime($fecha));
                $etiqueta_asunto = ($accion === 'editar') ? 'Reprogramación de Entrevista Laboral' : 'Convocatoria a Entrevista Laboral';
                $palabra_cuerpo = ($accion === 'editar') ? 'reprogramada' : 'agendada';

                $asunto_mail = "$etiqueta_asunto - Educar para Transformar";
                $cuerpo_mail = "Hola $nombre_postulante,\n\nNos es grato saludarte desde el área de Selección de Personal institucional.\n\nQueremos informarte que tu entrevista técnica/laboral para la postulación en el área de $area_postulante ha sido $palabra_cuerpo.\n\nTe esperamos de forma presencial el día $fecha_formateada a las $hora hs en las oficinas de nuestra sede escolar.\n\nPor favor, confirmar recepción y asistencia respondiendo a este correo.\n\nSaludos cordiales,\nEquipo de Recursos Humanos.";
                
                enviar_correo_real($email_postulante, $asunto_mail, $cuerpo_mail);
            }

            $msj_retorno = ($accion === 'editar') ? 'postulacion_modificada' : 'postulacion_agendada';
            header("Location: ../dashboard.php?vista=vista-rrhh&msj=$msj_retorno");
            exit;
        }
    }
}
header('Location: ../dashboard.php?vista=vista-rrhh');
exit;
?>