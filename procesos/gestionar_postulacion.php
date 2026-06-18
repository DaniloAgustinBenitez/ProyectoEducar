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
        require_once 'conexion.php';
        try {
            if ($accion === 'borrar') {
                // Eliminamos físicamente el archivo PDF del servidor para no ocupar espacio basura
                $stmt_cv = $pdo->prepare("SELECT ruta_cv FROM rrhh_postulaciones WHERE id = :id LIMIT 1");
                $stmt_cv->execute([':id' => $id_buscado]);
                $row = $stmt_cv->fetch();
                if ($row && !empty($row['ruta_cv']) && file_exists(__DIR__ . '/../' . $row['ruta_cv'])) {
                    unlink(__DIR__ . '/../' . $row['ruta_cv']);
                }

                $stmt_del = $pdo->prepare("DELETE FROM rrhh_postulaciones WHERE id = :id");
                $stmt_del->execute([':id' => $id_buscado]);

                header('Location: ../dashboard.php?vista=vista-rrhh&msj=postulacion_eliminada');
                exit;
            }
            elseif (!empty($fecha) && !empty($hora)) {
                // Rescatamos email, nombre y área para el correo
                $stmt_sel = $pdo->prepare("SELECT email, nombre_completo, area_interes FROM rrhh_postulaciones WHERE id = :id LIMIT 1");
                $stmt_sel->execute([':id' => $id_buscado]);
                $p = $stmt_sel->fetch();

                $stmt_upd = $pdo->prepare("UPDATE rrhh_postulaciones SET estado = 'agendada', fecha_entrevista = :fecha, hora_entrevista = :hora WHERE id = :id");
                $stmt_upd->execute([':fecha' => $fecha, ':hora' => $hora, ':id' => $id_buscado]);

                // --- ENVÍO DE CORREO REAL DE CITACIÓN LABORAL ---
                if ($p && !empty($p['email'])) {
                    $fecha_formateada = date('d/m/Y', strtotime($fecha));
                    $etiqueta_asunto = ($accion === 'editar') ? 'Reprogramación de Entrevista Laboral' : 'Convocatoria a Entrevista Laboral';
                    $palabra_cuerpo  = ($accion === 'editar') ? 'reprogramada' : 'agendada';

                    $asunto_mail = "$etiqueta_asunto - Educar para Transformar";
                    $cuerpo_mail = "Hola {$p['nombre_completo']},\n\nNos es grato saludarte desde el área de Selección de Personal institucional.\n\nQueremos informarte que tu entrevista técnica/laboral para la postulación en el área de {$p['area_interes']} ha sido $palabra_cuerpo.\n\nTe esperamos de forma presencial el día $fecha_formateada a las $hora hs en las oficinas de nuestra sede escolar.\n\nPor favor, confirmar recepción y asistencia respondiendo a este correo.\n\nSaludos cordiales,\nEquipo de Recursos Humanos.";

                    enviar_correo_real($p['email'], $asunto_mail, $cuerpo_mail);
                }

                $msj_retorno = ($accion === 'editar') ? 'postulacion_modificada' : 'postulacion_agendada';
                header("Location: ../dashboard.php?vista=vista-rrhh&msj=$msj_retorno");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Error gestionando postulación: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-rrhh');
exit;
?>