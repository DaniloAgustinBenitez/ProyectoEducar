<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$rol_actual = $_SESSION['rol'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    $accion = $_POST['accion'] ?? '';

    // --- ACCIONES EXCLUSIVAS DEL ADMINISTRADOR ---
    if ($rol_actual === 'admin') {
        try {
            if ($accion === 'crear') {
                $titulo = trim($_POST['titulo'] ?? '');
                $nivel  = trim($_POST['nivel']  ?? '');
                $fecha  = trim($_POST['fecha']  ?? '');
                $hora   = trim($_POST['hora']   ?? '') ?: null;
                $lugar  = trim($_POST['lugar']  ?? '');

                if (!empty($titulo) && !empty($fecha)) {
                    $stmt = $pdo->prepare("INSERT INTO talleres (titulo, nivel, fecha, hora, lugar) VALUES (:titulo, :nivel, :fecha, :hora, :lugar)");
                    $stmt->execute([':titulo' => $titulo, ':nivel' => $nivel, ':fecha' => $fecha, ':hora' => $hora, ':lugar' => $lugar]);
                }
            }

            if ($accion === 'borrar') {
                $id_borrar = $_POST['id_taller'] ?? '';
                $stmt = $pdo->prepare("DELETE FROM talleres WHERE id = :id");
                $stmt->execute([':id' => $id_borrar]);
            }
        } catch (PDOException $e) {
            error_log("Error en taller (admin): " . $e->getMessage());
        }

        header('Location: ../dashboard.php?vista=vista-grid-talleres&vista=vista-admin-talleres');
        exit;
    }

    // --- ACCIONES EXCLUSIVAS DEL ALUMNO ---
    if ($rol_actual === 'alumno') {
        $id_taller = trim($_POST['id_taller'] ?? '');

        try {
            // Conseguimos el nombre completo real de la cuenta
            $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
            $stmt_u->execute([':user' => $_SESSION['usuario']]);
            $u = $stmt_u->fetch();
            $nombre_alumno = $u ? $u['nombre'] : $_SESSION['usuario'];

            if ($accion === 'inscribir') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO taller_inscriptos (taller_id, alumno_nombre) VALUES (:id, :alumno)");
                $stmt->execute([':id' => $id_taller, ':alumno' => $nombre_alumno]);
            }

            if ($accion === 'baja') {
                $stmt = $pdo->prepare("DELETE FROM taller_inscriptos WHERE taller_id = :id AND alumno_nombre = :alumno");
                $stmt->execute([':id' => $id_taller, ':alumno' => $nombre_alumno]);
            }
        } catch (PDOException $e) {
            error_log("Error en taller (alumno): " . $e->getMessage());
        }

        header('Location: ../dashboard.php?vista=vista-talleres');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
?>