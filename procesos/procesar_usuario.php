<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password_plana = trim($_POST['password'] ?? '');

    if (!empty($nombre) && !empty($rol) && !empty($usuario) && !empty($password_plana)) {
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];

        // Validar que el login no exista
        foreach ($usuarios as $u) {
            if (strtolower($u['usuario']) === strtolower($usuario)) {
                header('Location: ../dashboard.php?vista=vista-usuarios&error=duplicado');
                exit;
            }
        }

        $nuevo_usuario = [
            'nombre' => $nombre,
            'rol' => $rol,
            'usuario' => $usuario,
            'password' => password_hash($password_plana, PASSWORD_DEFAULT)
        ];

        // --- MAGIA: SI ES ALUMNO, GUARDAMOS SU FICHA COMPLETA ---
        if ($rol === 'alumno') {
            $nuevo_usuario['dni'] = trim($_POST['dni'] ?? '');
            $nuevo_usuario['fecha_nacimiento'] = trim($_POST['fecha_nacimiento'] ?? '');
            $nuevo_usuario['tutor_nombre'] = trim($_POST['tutor_nombre'] ?? '');
            $nuevo_usuario['tutor_telefono'] = trim($_POST['tutor_telefono'] ?? '');
            $nuevo_usuario['tutor_email'] = trim($_POST['tutor_email'] ?? '');
            
            // Asignación de curso
            $curso = trim($_POST['curso_asig_al'] ?? '');
            $div = trim($_POST['division_asig_al'] ?? '');
            if (!empty($curso) && !empty($div)) {
                $archivo_ac = __DIR__ . '/../data/alumnos_cursos.json';
                $alumnos_cursos = file_exists($archivo_ac) ? json_decode(file_get_contents($archivo_ac), true) : [];
                $clave_c = $curso . "_" . $div;
                if (!isset($alumnos_cursos[$clave_c])) $alumnos_cursos[$clave_c] = [];
                if (!in_array($nombre, $alumnos_cursos[$clave_c])) {
                    $alumnos_cursos[$clave_c][] = $nombre;
                }
                file_put_contents($archivo_ac, json_encode($alumnos_cursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } 
        // Asignaciones para otros roles
        elseif ($rol === 'profesor' || $rol === 'preceptor') {
            $nivel = trim($_POST['nivel_asig'] ?? '');
            $curso = trim($_POST['curso_asig'] ?? '');
            $div = trim($_POST['division_asig'] ?? '');
            $materia = trim($_POST['materia_asig'] ?? '');
            if ($rol === 'preceptor') {
                $materia = 'Preceptor/a';
                $nivel = 'secundaria';
            }

            if (!empty($nivel) && !empty($curso) && !empty($div) && !empty($materia)) {
                $archivo_asig = __DIR__ . '/../data/asignaciones.json';
                $asignaciones = file_exists($archivo_asig) ? json_decode(file_get_contents($archivo_asig), true) : [];
                if (!isset($asignaciones[$nombre])) $asignaciones[$nombre] = [];
                
                $asignaciones[$nombre][] = [
                    'nivel' => $nivel,
                    'curso' => $curso,
                    'division' => $div,
                    'turno' => 'Mañana',
                    'materia' => $materia
                ];
                file_put_contents($archivo_asig, json_encode($asignaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
        elseif ($rol === 'tutor') {
            $hijos = $_POST['hijos_asignados'] ?? [];
            if (!empty($hijos)) {
                $archivo_tut = __DIR__ . '/../data/tutores_alumnos.json';
                $tutores = file_exists($archivo_tut) ? json_decode(file_get_contents($archivo_tut), true) : [];
                $tutores[$nombre] = $hijos;
                file_put_contents($archivo_tut, json_encode($tutores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        $usuarios[] = $nuevo_usuario;
        file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_creado');
        exit;
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios');
exit;
?>