<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/Exception.php';
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';

function enviar_correo_real($destinatario, $asunto, $cuerpo) {
    $mail = new PHPMailer(true);

    try {
        // Llamamos al archivo secreto
        require_once 'credenciales.php';

        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com'; 
        $mail->SMTPAuth   = true;
        $mail->AuthType   = 'LOGIN'; 
        
        // Usamos las variables en lugar del texto
        $mail->Username   = BREVO_USER; 
        $mail->Password   = BREVO_PASS;
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587; 

        // Salvavidas XAMPP
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('javiergomez2001@gmail.com', 'Educar para Transformar');
        $mail->addAddress($destinatario);

        $mail->isHTML(false);
        $mail->Subject = utf8_decode($asunto);
        $mail->Body    = utf8_decode($cuerpo);

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error enviando correo a $destinatario. Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>