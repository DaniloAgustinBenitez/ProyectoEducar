<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion   = $_POST['accion'] ?? '';
    $nivel    = $_POST['nivel'] ?? '';
    $profesor = $_POST['profesor'] ?? '';
    $curso    = $_POST['curso'] ?? '';
    $division = $_POST['division'] ?? '';
    $materia  = $_POST['materia'] ?? '';

    if (!empty($accion) && !empty($profesor) && !empty($curso) && !empty($division) && !empty($materia)) {
        try {
            if ($accion === 'asignar') {
                $stmt = $pdo->prepare("INSERT INTO catedras (docente_nombre, nivel, curso, division, materia) VALUES (:prof, :nivel, :curso, :div, :mat)");
                $stmt->execute([
                    ':prof'  => $profesor,
                    ':nivel' => $nivel,
                    ':curso' => $curso,
                    ':div'   => $division,
                    ':mat'   => $materia
                ]);
            } elseif ($accion === 'quitar') {
                $stmt = $pdo->prepare("DELETE FROM catedras WHERE docente_nombre = :prof AND curso = :curso AND division = :div AND materia = :mat");
                $stmt->execute([
                    ':prof'  => $profesor,
                    ':curso' => $curso,
                    ':div'   => $division,
                    ':mat'   => $materia
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error en procesar_asignacion: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-cursos');
exit;