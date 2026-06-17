<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'agendar'; 
    $id_buscado = $_POST['id_entrevista'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!empty($id_buscado)) {
        $archivo_entrevistas = __DIR__ . '/../data/entrevistas.json';
        $entrevistas = file_exists($archivo_entrevistas) ? json_decode(file_get_contents($archivo_entrevistas), true) : [];

        if ($accion === 'borrar') {
            $entrevistas = array_filter($entrevistas, function($ent) use ($id_buscado) {
                return $ent['id'] !== $id_buscado;
            });
            $entrevistas = array_values($entrevistas); 
            file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            header('Location: ../dashboard.php?vista=vista-entrevistas&msj=entrevista_eliminada');
            exit;
        } 
        elseif (!empty($fecha) && !empty($hora)) {
            
            // Variables para rescatar los datos del tutor y mandarle el mail
            $email_tutor = '';
            $nombre_tutor = '';
            
            foreach ($entrevistas as &$ent) {
                if ($ent['id'] === $id_buscado) {
                    $ent['estado'] = 'agendada';
                    $ent['fecha_agendada'] = $fecha;
                    $ent['hora_agendada'] = $hora;
                    
                    // Rescatamos los datos
                    $email_tutor = $ent['email'];
                    $nombre_tutor = $ent['tutor'];
                    break;
                }
            }
            file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // --- ENVÍO DE CORREO POR MAILTRAP (CONFIRMACIÓN) ---
            if (!empty($email_tutor)) {
                require_once 'mailer.php';
                $fecha_formateada = date('d/m/Y', strtotime($fecha));
                $etiqueta_asunto = ($accion === 'editar') ? 'Reprogramación de Entrevista' : 'Turno Confirmado';
                $palabra_cuerpo = ($accion === 'editar') ? 'reprogramada' : 'agendada';

                $asunto_mail = "$etiqueta_asunto - Educar para Transformar";
                $cuerpo_mail = "Hola $nombre_tutor,\n\nTe informamos que tu entrevista de admisión ha sido $palabra_cuerpo exitosamente.\n\nTe esperamos el día $fecha_formateada a las $hora hs en nuestras instalaciones.\n\nSaludos cordiales,\nEquipo de Admisiones.";
                
                enviar_correo_real($email_tutor, $asunto_mail, $cuerpo_mail);
            }
            // ---------------------------------------------------

            $msj_retorno = ($accion === 'editar') ? 'entrevista_modificada' : 'entrevista_agendada';
            header("Location: ../dashboard.php?vista=vista-entrevistas&msj=$msj_retorno");
            exit;
        }
    }
}
header('Location: ../dashboard.php?vista=vista-entrevistas');
exit;
?>