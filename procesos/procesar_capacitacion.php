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

    // --- ACCIONES DEL ADMINISTRADOR ---
    if ($rol_actual === 'admin') {
        try {
            if ($accion === 'crear') {
                $titulo = trim($_POST['titulo'] ?? '');
                $fecha  = trim($_POST['fecha']  ?? '');
                $hora   = trim($_POST['hora']   ?? '') ?: null;
                $lugar  = trim($_POST['lugar']  ?? '');

                if (!empty($titulo) && !empty($fecha)) {
                    $stmt = $pdo->prepare("INSERT INTO capacitaciones (titulo, fecha, hora, lugar) VALUES (:titulo, :fecha, :hora, :lugar)");
                    $stmt->execute([':titulo' => $titulo, ':fecha' => $fecha, ':hora' => $hora, ':lugar' => $lugar]);
                }
            }

            if ($accion === 'borrar') {
                $id_borrar = $_POST['id_cap'] ?? '';
                $stmt = $pdo->prepare("DELETE FROM capacitaciones WHERE id = :id");
                $stmt->execute([':id' => $id_borrar]);
            }
        } catch (PDOException $e) {
            error_log("Error en capacitación (admin): " . $e->getMessage());
        }

        header('Location: ../dashboard.php?vista=vista-admin-capacitaciones');
        exit;
    }

    // --- ACCIONES DE LOS DOCENTES ---
    if ($rol_actual === 'profesor' || $rol_actual === 'preceptor') {
        $id_cap = trim($_POST['id_cap'] ?? '');

        try {
            // Buscamos el nombre completo del docente para anotarlo
            $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
            $stmt_u->execute([':user' => $_SESSION['usuario']]);
            $u = $stmt_u->fetch();
            $nombre_docente = $u ? $u['nombre'] : $_SESSION['usuario'];

            if ($accion === 'inscribir') {
                $stmt = $pdo->prepare("INSERT IGNORE INTO cap_inscriptos (cap_id, docente_nombre) VALUES (:id, :docente)");
                $stmt->execute([':id' => $id_cap, ':docente' => $nombre_docente]);
            }

            if ($accion === 'baja') {
                $stmt = $pdo->prepare("DELETE FROM cap_inscriptos WHERE cap_id = :id AND docente_nombre = :docente");
                $stmt->execute([':id' => $id_cap, ':docente' => $nombre_docente]);
            }
        } catch (PDOException $e) {
            error_log("Error en capacitación (docente): " . $e->getMessage());
        }

        header('Location: ../dashboard.php?vista=vista-recursos-salud');
        exit;
    }
}

header('Location: ../dashboard.php');
exit;
?>