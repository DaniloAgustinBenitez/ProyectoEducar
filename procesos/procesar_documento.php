<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$usuario_actual = $_SESSION['usuario'];

// Buscamos el nombre COMPLETO del alumno
$archivo_usuarios = __DIR__ . '/../data/usuarios.json';
$todos_usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
$nombre_alumno = $usuario_actual;
foreach ($todos_usuarios as $u) {
    if ($u['usuario'] === $usuario_actual) {
        $nombre_alumno = $u['nombre'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_doc'])) {
    $tipo_doc = $_POST['tipo_doc'] ?? 'Documento';
    
    // Creamos la carpeta "uploads" automáticamente si no existe
    $directorio_subidas = __DIR__ . '/../uploads/';
    if (!file_exists($directorio_subidas)) {
        mkdir($directorio_subidas, 0777, true);
    }

    // Le ponemos la hora al nombre del archivo para que no se sobreescriban
    $nombre_archivo = time() . "_" . basename($_FILES['archivo_doc']['name']);
    $ruta_destino_fisica = $directorio_subidas . $nombre_archivo;
    $ruta_destino_db = 'uploads/' . $nombre_archivo;

    if (move_uploaded_file($_FILES['archivo_doc']['tmp_name'], $ruta_destino_fisica)) {
        
        $archivo_docs = __DIR__ . '/../data/documentos.json';
        $docs = file_exists($archivo_docs) ? json_decode(file_get_contents($archivo_docs), true) : [];

        if (!isset($docs[$nombre_alumno])) {
            $docs[$nombre_alumno] = [];
        }

        // MAGIA: Si es un certificado de falta O un permiso de retiro, lo sumamos al historial.
        if ($tipo_doc === 'Certificado Médico / Justificación de Falta' || $tipo_doc === 'Permiso de Retiro') {
            if (!isset($docs[$nombre_alumno][$tipo_doc]) || !is_array($docs[$nombre_alumno][$tipo_doc])) {
                $docs[$nombre_alumno][$tipo_doc] = [];
            }
            $docs[$nombre_alumno][$tipo_doc][] = [
                'fecha' => date('d-m-Y H:i'), // Guardamos con hora
                'archivo' => $ruta_destino_db
            ];
        } else {
            // Si es DNI o Apto Físico, es único y se sobreescribe
            $docs[$nombre_alumno][$tipo_doc] = [
                'fecha' => date('d-m-Y'),
                'archivo' => $ruta_destino_db
            ];
        }

        file_put_contents($archivo_docs, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

header('Location: ../dashboard.php?vista=vista-documentacion');
exit;
?>