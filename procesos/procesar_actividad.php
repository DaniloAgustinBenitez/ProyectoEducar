<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'conexion.php';
    $accion = $_POST['accion'] ?? '';

    // CREAR ACTIVIDAD
    if ($accion === 'crear') {
        $curso_destino = explode('|', $_POST['curso_destino'] ?? ''); // Viene como "5_anio|A|Matematica"
        $titulo        = trim($_POST['titulo']       ?? '');
        $descripcion   = trim($_POST['descripcion']  ?? '');
        $fecha_limite  = trim($_POST['fecha_limite'] ?? '');

        $ruta_web_adjunto = '';
        if (isset($_FILES['archivo_adjunto']) && $_FILES['archivo_adjunto']['error'] === UPLOAD_ERR_OK) {
            $dir_subidas = __DIR__ . '/../uploads/';
            if (!file_exists($dir_subidas)) mkdir($dir_subidas, 0777, true);

            $nombre_archivo = time() . "_profe_" . basename($_FILES['archivo_adjunto']['name']);
            if (move_uploaded_file($_FILES['archivo_adjunto']['tmp_name'], $dir_subidas . $nombre_archivo)) {
                $ruta_web_adjunto = 'uploads/' . $nombre_archivo;
            }
        }

        if (count($curso_destino) === 3 && !empty($titulo) && !empty($fecha_limite)) {
            try {
                // Conseguimos el nombre completo del profe
                $stmt_u = $pdo->prepare("SELECT nombre FROM usuarios WHERE username = :user LIMIT 1");
                $stmt_u->execute([':user' => $_SESSION['usuario']]);
                $u = $stmt_u->fetch();
                $nombre_profe = $u ? $u['nombre'] : $_SESSION['usuario'];

                $stmt = $pdo->prepare("INSERT INTO actividades (profesor, curso, division, materia, titulo, descripcion, fecha_limite, archivo_adjunto)
                                       VALUES (:prof, :curso, :div, :mat, :titulo, :desc, :fecha, :archivo)");
                $stmt->execute([
                    ':prof'    => $nombre_profe,
                    ':curso'   => $curso_destino[0],
                    ':div'     => $curso_destino[1],
                    ':mat'     => $curso_destino[2],
                    ':titulo'  => $titulo,
                    ':desc'    => $descripcion,
                    ':fecha'   => $fecha_limite,
                    ':archivo' => $ruta_web_adjunto
                ]);
            } catch (PDOException $e) {
                error_log("Error creando actividad: " . $e->getMessage());
            }
        }
    }

    // BORRAR ACTIVIDAD
    if ($accion === 'borrar') {
        $id_borrar = $_POST['id_actividad'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM actividades WHERE id = :id");
            $stmt->execute([':id' => $id_borrar]);
        } catch (PDOException $e) {
            error_log("Error borrando actividad: " . $e->getMessage());
        }
    }
}

header('Location: ../dashboard.php?vista=vista-actividades');
exit;
?>