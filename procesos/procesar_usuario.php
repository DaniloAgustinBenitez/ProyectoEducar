<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. GUARDAR USUARIO
    $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
    if (!file_exists(__DIR__ . '/../data')) {
        mkdir(__DIR__ . '/../data', 0777, true);
    }
    $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];

    foreach ($usuarios as $u) {
        if (strtolower($u['usuario']) === strtolower($usuario)) {
            header('Location: ../dashboard.php?vista=vista-usuarios&error=duplicado');
            exit;
        }
    }

    $usuarios[] = [
        'nombre' => $nombre,
        'usuario' => $usuario,
        'password' => $password,
        'rol' => $rol
    ];
    file_put_contents($archivo_usuarios, json_encode($usuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // 2. SI ES PROFESOR O PRECEPTOR -> Asignar cátedra
    if ($rol === 'profesor' || $rol === 'preceptor') {
        $archivo_asig = __DIR__ . '/../data/asignaciones.json';
        $asignaciones = file_exists($archivo_asig) ? json_decode(file_get_contents($archivo_asig), true) : [];

        if (!isset($asignaciones[$nombre])) $asignaciones[$nombre] = [];

        $nivel_elegido = $_POST['nivel_asig'] ?? 'secundaria';
        
        if ($rol === 'preceptor') {
            $materia_a_guardar = 'Preceptor/a';
            $nivel_elegido = 'secundaria';
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

    // 3. SI ES ALUMNO -> Asignarle curso
    if ($rol === 'alumno') {
        $curso_al = $_POST['curso_asig_al'] ?? '';
        $div_al = $_POST['division_asig_al'] ?? '';
        
        if (!empty($curso_al) && !empty($div_al)) {
            $archivo_alumnos = __DIR__ . '/../data/alumnos_cursos.json';
            $alumnos_cursos = file_exists($archivo_alumnos) ? json_decode(file_get_contents($archivo_alumnos), true) : [];
            $clave = $curso_al . "_" . $div_al;
            
            if (!isset($alumnos_cursos[$clave])) {
                $alumnos_cursos[$clave] = [];
            }
            $alumnos_cursos[$clave][] = $nombre;
            file_put_contents($archivo_alumnos, json_encode($alumnos_cursos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    // 4. SI ES TUTOR -> Vincular con hijos (LA MAGIA ESTÁ ACÁ)
    if ($rol === 'tutor') {
        $hijos = $_POST['hijos_asignados'] ?? []; // Recibe el array de las casillas tildadas
        
        $archivo_tutores = __DIR__ . '/../data/tutores_alumnos.json';
        $tutores = file_exists($archivo_tutores) ? json_decode(file_get_contents($archivo_tutores), true) : [];
        
        if (!isset($tutores[$usuario])) {
            $tutores[$usuario] = [];
        }
        
        // Si el administrador tildó hijos, los guardamos en el perfil del tutor
        if (!empty($hijos) && is_array($hijos)) {
            foreach ($hijos as $hijo) {
                $hijo_limpio = trim($hijo);
                if (!empty($hijo_limpio) && !in_array($hijo_limpio, $tutores[$usuario])) {
                    $tutores[$usuario][] = $hijo_limpio; 
                }
            }
        }
        // Grabamos el archivo asegurando que se actualice o se cree desde cero
        file_put_contents($archivo_tutores, json_encode($tutores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    header('Location: ../dashboard.php?vista=vista-usuarios&msj=usuario_creado');
    exit;
}

header('Location: ../dashboard.php');
exit;
?>