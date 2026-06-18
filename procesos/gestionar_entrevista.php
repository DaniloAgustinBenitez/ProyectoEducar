<?php
session_start();

// Verificamos que solo el administrador pueda hacer esto
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

// 1. Llamamos a nuestros puentes
require_once 'conexion.php'; 
require_once 'mailer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'agendar'; 
    $id_buscado = $_POST['id_entrevista'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!empty($id_buscado)) {
        try {
            if ($accion === 'borrar') {
                // --- MOTOR SQL: DELETE (Eliminar fila) ---
                $stmt = $pdo->prepare("DELETE FROM admisiones WHERE id = :id");
                $stmt->bindParam(':id', $id_buscado, PDO::PARAM_INT);
                $stmt->execute();
                
                header('Location: ../dashboard.php?vista=vista-entrevistas&msj=entrevista_eliminada');
                exit;
            } 
            elseif (!empty($fecha) && !empty($hora)) {
                
                // 1. Rescatamos el email y nombre del tutor desde la Base de Datos para el correo
                $stmt_select = $pdo->prepare("SELECT email, nombre_tutor FROM admisiones WHERE id = :id LIMIT 1");
                $stmt_select->bindParam(':id', $id_buscado, PDO::PARAM_INT);
                $stmt_select->execute();
                $tutor_bd = $stmt_select->fetch();

                // 2. --- MOTOR SQL: UPDATE (Actualizar fila) ---
                $stmt_update = $pdo->prepare("UPDATE admisiones SET estado = 'agendada', fecha_entrevista = :fecha, hora_entrevista = :hora WHERE id = :id");
                $stmt_update->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmt_update->bindParam(':hora', $hora, PDO::PARAM_STR);
                $stmt_update->bindParam(':id', $id_buscado, PDO::PARAM_INT);
                $stmt_update->execute();
                
                // --- ENVÍO DE CORREO AUTOMÁTICO (BREVO) ---
                if ($tutor_bd && !empty($tutor_bd['email'])) {
                    $email_tutor = $tutor_bd['email'];
                    $nombre_tutor = $tutor_bd['nombre_tutor']; 
                    
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
        } catch (PDOException $e) {
            error_log("Error gestionando la entrevista SQL: " . $e->getMessage());
        }
    }
}

// Si algo falla o entran directo a este archivo, los devolvemos al panel
header('Location: ../dashboard.php?vista=vista-entrevistas');
exit;
?>