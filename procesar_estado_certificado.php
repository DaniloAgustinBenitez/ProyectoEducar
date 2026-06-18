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
        require_once 'procesos/conexion.php';
        try {
            // $indice ahora es el id SQL de la fila en la tabla documentos
            $nuevo_estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
            $stmt = $pdo->prepare("UPDATE documentos SET estado = :estado WHERE id = :id AND alumno_nombre = :alumno AND tipo_doc = :tipo");
            $stmt->execute([':estado' => $nuevo_estado, ':id' => $indice, ':alumno' => $alumno, ':tipo' => $tipo_doc]);
        } catch (PDOException $e) {
            error_log("Error actualizando estado de documento: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-documentos-preceptor');
exit;
?>