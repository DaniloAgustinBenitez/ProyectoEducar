<?php
require_once 'credenciales.php';

function enviar_correo_real(string $destinatario, string $asunto, string $cuerpo): bool {
    $payload = json_encode([
        'sender'      => ['name' => BREVO_FROM_NAME, 'email' => BREVO_FROM_EMAIL],
        'to'          => [['email' => $destinatario]],
        'subject'     => $asunto,
        'textContent' => $cuerpo
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . BREVO_API_KEY,
            'content-type: application/json'
        ]
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("Error cURL al enviar correo a $destinatario: $curl_error");
        return false;
    }

    if ($http_code < 200 || $http_code >= 300) {
        error_log("Brevo API rechazó el correo a $destinatario. HTTP $http_code. Respuesta: $response");
        return false;
    }

    return true;
}
?>