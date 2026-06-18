<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    $accion = $_POST['accion'] ?? '';

    // --- LÓGICA PARA CREAR RESERVA ---
    if ($accion === 'reservar') {
        $espacio = trim($_POST['espacio'] ?? '');
        $fecha   = trim($_POST['fecha_reserva'] ?? '');
        $modulo  = trim($_POST['modulo_reserva'] ?? '');

        if (!empty($espacio) && !empty($fecha) && !empty($modulo)) {
            try {
                // 1. EL SISTEMA ANTI-CHOQUES: UNIQUE KEY en la tabla lo garantiza, pero revisamos igual para dar error amigable
                $stmt_check = $pdo->prepare("SELECT id FROM reservas WHERE espacio = :esp AND fecha = :fecha AND modulo = :mod LIMIT 1");
                $stmt_check->execute([':esp' => $espacio, ':fecha' => $fecha, ':mod' => $modulo]);
                if ($stmt_check->fetch()) {
                    header('Location: ../dashboard.php?vista=vista-reservas&error=ocupado');
                    exit;
                }

                // 2. Buscamos el nombre real del profe
                $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
                $stmt_u->execute([':user' => $_SESSION['usuario']]);
                $u = $stmt_u->fetch();
                $nombre_profe = $u ? $u['nombre'] : $_SESSION['usuario'];

                // 3. Guardamos la reserva
                $stmt_ins = $pdo->prepare("INSERT INTO reservas (espacio, fecha, modulo, profesor) VALUES (:esp, :fecha, :mod, :prof)");
                $stmt_ins->execute([':esp' => $espacio, ':fecha' => $fecha, ':mod' => $modulo, ':prof' => $nombre_profe]);

                header('Location: ../dashboard.php?vista=vista-reservas&msj=reservado');
                exit;
            } catch (PDOException $e) {
                error_log("Error guardando reserva: " . $e->getMessage());
            }
        }
    }

    // --- LÓGICA PARA CANCELAR UNA RESERVA ---
    if ($accion === 'eliminar') {
        $id_borrar = $_POST['id_reserva'] ?? '';
        try {
            $stmt_del = $pdo->prepare("DELETE FROM reservas WHERE id = :id");
            $stmt_del->execute([':id' => $id_borrar]);
        } catch (PDOException $e) {
            error_log("Error eliminando reserva: " . $e->getMessage());
        }
        header('Location: ../dashboard.php?vista=vista-reservas&msj=eliminado');
        exit;
    }
}

header('Location: ../dashboard.php?vista=vista-reservas');
exit;
?>