<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario_nuevo = trim($_POST['usuario'] ?? '');
    $password_nueva = trim($_POST['password'] ?? '');
    $rol = $_POST['rol'] ?? '';

    if (!empty($nombre) && !empty($usuario_nuevo) && !empty($password_nueva) && !empty($rol)) {
        // 1. Guardar credenciales en usuarios.json
        $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
        $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
        
        // Evitar usuarios duplicados
        foreach ($usuarios as $u) {
            if (strtolower($u['usuario']) === strtolower($usuario_nuevo)) {
                header('Location: ../dashboard.php?vista=vista-usuarios&error=duplicado');
                exit;
            }
        }
        
        $usuarios[] = ["usuario" => $usuario_nuevo, "password" => $password_nueva, "rol" => $rol, "nombre" => $nombre];
        file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 2. Si es profesor o preceptor, guardar su cátedra en asignaciones.json
        if ($rol === 'profesor' || $rol === 'preceptor') {
            $archivo_asig = __DIR__ . '/../data/asignaciones.json';
            $asignaciones = file_exists($archivo_asig) ? json_decode(file_get_contents($archivo_asig), true) : [];

            if (!isset($asignaciones[$nombre])) $asignaciones[$nombre] = [];

            $nivel_elegido = $_POST['nivel_asig'] ?? 'secundaria';
            
            // MAGIA: Determinamos la materia según el rol
            if ($rol === 'preceptor') {
                $materia_a_guardar = 'Preceptor/a';
                $nivel_elegido = 'secundaria'; // El preceptor es exclusivo de secundaria
            } else {
                $materia_a_guardar = ($nivel_elegido === 'primaria') ? 'Maestro/a de Grado' : ($_POST['materia_asig'] ?? '');
            }

            $asignaciones[$nombre][] = [
                "nivel" => $nivel_elegido,
                "curso" => $_POST['curso_asig'] ?? '',
                "division" => $_POST['division_asig'] ?? '',
                "materia" => $materia_a_guardar,
                "turno" => "Mañana" 
            ];
            file_put_contents($archivo_asig, json_encode($asignaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // 3. ¡NUEVO! Si es alumno, guardarlo en la lista de su curso en alumnos_cursos.json
        elseif ($rol === 'alumno') {
            $curso_al = $_POST['curso_asig_al'] ?? '';
            $division_al = $_POST['division_asig_al'] ?? '';
            
            if (!empty($curso_al) && !empty($division_al)) {
                $archivo_alumnos = __DIR__ . '/../data/alumnos_cursos.json';
                $alumnos_cursos = file_exists($archivo_alumnos) ? json_decode(file_get_contents($archivo_alumnos), true) : [];
                
                // Armamos la llave exacta (Ej: "5_anio_B")
                $clave_curso_alumno = $curso_al . "_" . $division_al; 
                
                if (!isset($alumnos_cursos[$clave_curso_alumno])) {
                    $alumnos_cursos[$clave_curso_alumno] = [];
                }
                
                // Lo agregamos a la lista si no está repetido
                if (!in_array($nombre, $alumnos_cursos[$clave_curso_alumno])) {
                    $alumnos_cursos[$clave_curso_alumno][] = $nombre;
                }
                
                file_put_contents($archivo_alumnos, json_encode($alumnos_cursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_creado');
        exit;
    }
}
header('Location: ../dashboard.php?vista=vista-usuarios&error=datos_incompletos');
?>