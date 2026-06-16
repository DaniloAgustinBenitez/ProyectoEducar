<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Solo personal autorizado puede aprobar
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'preceptor' && $_SESSION['rol'] !== 'maestro')) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alumno = trim($_POST['alumno'] ?? '');
    $tipo_doc = trim($_POST['tipo_doc'] ?? '');
    $indice = $_POST['indice'] ?? '';
    $accion = trim($_POST['accion'] ?? '');

    if ($alumno !== '' && $tipo_doc !== '' && $indice !== '' && $accion !== '') {
        $archivo_docs = __DIR__ . '/../data/documentos.json';
        $docs = file_exists($archivo_docs) ? json_decode(file_get_contents($archivo_docs), true) : [];

        if (isset($docs[$alumno][$tipo_doc][$indice])) {
            // Cambiamos el estado según el botón que apretó
            $docs[$alumno][$tipo_doc][$indice]['estado'] = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
            file_put_contents($archivo_docs, json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}

header('Location: ../dashboard.php?vista=vista-documentos-preceptor');
exit;
?>