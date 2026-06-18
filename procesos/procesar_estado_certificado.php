<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'preceptor'])) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($doc_id > 0 && in_array($accion, ['aprobar', 'rechazar'])) {
        try {
            $nuevo_estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
            $stmt = $pdo->prepare("UPDATE documentos SET estado = :estado WHERE id = :id");
            $stmt->execute([':estado' => $nuevo_estado, ':id' => $doc_id]);

            header('Location: ../dashboard.php?vista=vista-documentos-preceptor&msj=usuario_modificado');
            exit;
        } catch (PDOException $e) {
            error_log("Error actualizando estado de certificado: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-documentos-preceptor');
exit;
