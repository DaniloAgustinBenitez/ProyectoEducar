<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $rol      = trim($_POST['rol'] ?? '');
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($nombre) && !empty($rol) && !empty($usuario) && !empty($password)) {
        try {
            // Validamos si el nombre de usuario ya existe
            $stmt_check = $pdo->prepare("SELECT id FROM usuarios WHERE username = :user LIMIT 1");
            $stmt_check->execute([':user' => $usuario]);
            if ($stmt_check->fetch()) {
                header('Location: ../dashboard.php?vista=vista-usuarios&error=duplicado');
                exit;
            }

            // Encriptamos la contraseña de manera segura
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Capturamos datos específicos según el rol
            $dni        = ($rol === 'alumno') ? trim($_POST['dni'] ?? '') : null;
            $f_nac      = ($rol === 'alumno' && !empty($_POST['fecha_nacimiento'])) ? $_POST['fecha_nacimiento'] : null;
            $t_nombre   = ($rol === 'alumno') ? trim($_POST['tutor_nombre'] ?? '') : null;
            $t_telefono = ($rol === 'alumno') ? trim($_POST['tutor_telefono'] ?? '') : null;
            $t_email    = ($rol === 'alumno') ? trim($_POST['tutor_email'] ?? '') : null;

            // 1. Insertamos el perfil principal en MySQL
            $sql = "INSERT INTO usuarios (username, nombre, password, rol, dni, fecha_nacimiento, tutor_nombre, tutor_telefono, tutor_email) 
                    VALUES (:user, :nombre, :pass, :rol, :dni, :f_nac, :t_nombre, :t_telefono, :t_email)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user'       => $usuario,
                ':nombre'     => $nombre,
                ':pass'       => $password_hash,
                ':rol'        => $rol,
                ':dni'        => $dni,
                ':f_nac'      => $f_nac,
                ':t_nombre'   => $t_nombre,
                ':t_telefono' => $t_telefono,
                ':t_email'    => $t_email
            ]);

            // 2. --- CONEXIÓN ACADÉMICA DIRECTA A MYSQL ---
            if ($rol === 'alumno') {
                $curso = $_POST['curso_asig_al'] ?? '';
                $div   = $_POST['division_asig_al'] ?? '';
                if (!empty($curso) && !empty($div)) {
                    $clave_curso = $curso . '_' . $div;
                    // Lo matriculamos directo en la nueva tabla SQL
                    $stmt_mat = $pdo->prepare("INSERT INTO matricula (alumno_nombre, curso_clave) VALUES (:alumno, :curso)");
                    $stmt_mat->execute([':alumno' => $nombre, ':curso' => $clave_curso]);
                }
            } elseif ($rol === 'profesor' || $rol === 'preceptor') {
                $nivel = $_POST['nivel_asig'] ?? 'secundaria';
                $curso = $_POST['curso_asig'] ?? '';
                $div   = $_POST['division_asig'] ?? '';
                $mat   = ($rol === 'preceptor') ? 'Preceptor/a' : ($_POST['materia_asig'] ?? 'Maestro/a de Grado');

                if (!empty($curso) && !empty($div)) {
                    // Le asignamos la cátedra directo en la nueva tabla SQL
                    $stmt_cat = $pdo->prepare("INSERT INTO catedras (docente_nombre, nivel, curso, division, materia) VALUES (:prof, :nivel, :curso, :div, :mat)");
                    $stmt_cat->execute([
                        ':prof'  => $nombre,
                        ':nivel' => $nivel,
                        ':curso' => $curso,
                        ':div'   => $div,
                        ':mat'   => $mat
                    ]);
                }
            } elseif ($rol === 'tutor') {
                $hijos = $_POST['hijos_asignados'] ?? [];
                // Reemplazamos todas las asignaciones previas del tutor
                $stmt_del = $pdo->prepare("DELETE FROM tutores_alumnos WHERE tutor_username = :user");
                $stmt_del->execute([':user' => $usuario]);
                if (!empty($hijos)) {
                    $stmt_ins = $pdo->prepare("INSERT IGNORE INTO tutores_alumnos (tutor_username, alumno_nombre) VALUES (:user, :alumno)");
                    foreach ($hijos as $hijo) {
                        $stmt_ins->execute([':user' => $usuario, ':alumno' => $hijo]);
                    }
                }
            }

            header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_creado');
            exit;

        } catch (PDOException $e) {
            error_log("Error creando usuario: " . $e->getMessage());
        }
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios');
exit;
?>