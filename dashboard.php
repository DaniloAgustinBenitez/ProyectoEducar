<?php
session_start();
// Configuramos el reloj del sistema para nuestra región
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario_actual = $_SESSION['usuario'];

// Leemos el rol que nos pasó el login (si por algún motivo falla, lo mandamos a alumno por seguridad)
$rol_actual = $_SESSION['rol'] ?? 'alumno'; 

// Ahora validamos por el ROL, sin importar cómo se llame la persona
$es_profesor = ($rol_actual === 'profesor');
$es_admin = ($rol_actual === 'admin');
$es_alumno = ($rol_actual === 'alumno');
$es_preceptor = ($rol_actual === 'preceptor'); 
$es_tutor = ($rol_actual === 'tutor'); // ¡NUEVO ROL!

// LÓGICA DE RECUPERACIÓN DE CLAVES
$archivo_recuperaciones = __DIR__ . '/data/recuperaciones.json';
$todas_las_recuperaciones = file_exists($archivo_recuperaciones) ? json_decode(file_get_contents($archivo_recuperaciones), true) : [];

// 1. LÓGICA PARA LEER TODOS LOS USUARIOS PRIMERO
$archivo_usuarios_lista = __DIR__ . '/data/usuarios.json';
$todos_los_usuarios = [];
$nombre_completo_actual = trim($usuario_actual); // Por defecto
$mi_usuario_login = trim($usuario_actual); // Por defecto

if (file_exists($archivo_usuarios_lista)) {
    $todos_los_usuarios = json_decode(file_get_contents($archivo_usuarios_lista), true) ?: [];
    foreach ($todos_los_usuarios as $usr) {
        // MAGIA: Cruzamos datos. Si la sesión es "Gustavo Mongelo" (nombre) o "gmongelo" (login), lo unimos.
        if (strtolower(trim($usr['usuario'])) === strtolower(trim($usuario_actual)) || 
            strtolower(trim($usr['nombre'])) === strtolower(trim($usuario_actual))) {
            
            $nombre_completo_actual = trim($usr['nombre']); // Ej: Gustavo Mongelo
            $mi_usuario_login = trim($usr['usuario']); // Ej: gmongelo
            break;
        }
    }
}

// 2. MAGIA DE TUTORES: El hilo invisible
$archivo_tutores = __DIR__ . '/data/tutores_alumnos.json';
$mis_tutelados = [];
$alumno_seleccionado = ''; 

if (file_exists($archivo_tutores)) {
    $mapa_tutores = json_decode(file_get_contents($archivo_tutores), true) ?: [];
    
    if ($es_tutor) {
        // BÚSQUEDA SÚPER INTELIGENTE DEFINITIVA
        foreach ($mapa_tutores as $tutor_key => $lista_hijos) {
            $llave_limpia = strtolower(trim($tutor_key));
            
            // Ahora comparamos contra las dos identidades (Nombre y Login)
            if ($llave_limpia === strtolower($mi_usuario_login) || 
                $llave_limpia === strtolower($nombre_completo_actual) ||
                $llave_limpia === strtolower(trim($usuario_actual))) {
                
                $mis_tutelados = $lista_hijos;
                break;
            }
        }
        
        // --- MAGIA: Leemos si el tutor cambió de hijo ---
        if (!empty($mis_tutelados)) {
            if (isset($_GET['hijo']) && in_array($_GET['hijo'], $mis_tutelados)) {
                $alumno_seleccionado = $_GET['hijo'];
            } else {
                $alumno_seleccionado = $mis_tutelados[0] ?? ''; // Primer hijo por defecto
            }
        }
    }
}

// Generamos la etiqueta visual y "engañamos" al sistema
$etiqueta_perfil = $nombre_completo_actual; // Guardamos su nombre real para el diseño

if ($es_tutor && !empty($alumno_seleccionado)) {
    $etiqueta_perfil = "Tutor de " . $alumno_seleccionado;
    // HACK: Reemplazamos su nombre por el del hijo para que TODAS las consultas busquen al alumno
    $nombre_completo_actual = $alumno_seleccionado; 
}

// 2.5 CREAMOS LAS INICIALES CON EL NOMBRE
$palabras = explode(" ", $etiqueta_perfil);
$iniciales = "";
foreach ($palabras as $p) {
    if (!empty($p) && ctype_alpha(mb_substr($p, 0, 1))) {
        $iniciales .= mb_substr($p, 0, 1);
    }
}
$iniciales = strtoupper(mb_substr($iniciales, 0, 2));

// 3. LÓGICA PARA ASIGNACIONES DE PROFESORES Y PRECEPTORES
$archivo_asignaciones = __DIR__ . '/data/asignaciones.json';
$mis_cursos = [];
$asignaciones_totales = []; 

if (file_exists($archivo_asignaciones)) {
    $asignaciones_totales = json_decode(file_get_contents($archivo_asignaciones), true) ?: [];
    
    // Extraemos sus cursos asignados (Funciona igual para Profes y Preceptores)
    if (($es_profesor || $es_preceptor) && isset($asignaciones_totales[$nombre_completo_actual])) {
        $mis_cursos = $asignaciones_totales[$nombre_completo_actual];
    }
}

// Averiguamos si es un Maestro de Primaria
$es_maestro_primaria = false;
if ($es_profesor) {
    foreach ($mis_cursos as $c) {
        if (($c['nivel'] ?? '') === 'primaria') {
            $es_maestro_primaria = true;
            break;
        }
    }
}

// LÓGICA DE ASISTENCIAS 
$archivo_asistencias = __DIR__ . '/data/asistencias.json';
$todas_asistencias = file_exists($archivo_asistencias) ? json_decode(file_get_contents($archivo_asistencias), true) : [];

// 4. LÓGICA PARA CARGAR ALUMNOS POR CURSO  
$archivo_alumnos_cursos = __DIR__ . '/data/alumnos_cursos.json';
$alumnos_por_curso = [];
if (file_exists($archivo_alumnos_cursos)) {
    $alumnos_por_curso = json_decode(file_get_contents($archivo_alumnos_cursos), true) ?: [];
}
// 5. CALIFICACIONES Y DESCUBRIMIENTO DE MATERIAS
$archivo_notas = __DIR__ . '/data/calificaciones.json';
$mis_materias = [];
$todas_las_notas = []; 

if (file_exists($archivo_notas)) {
    $contenido = file_get_contents($archivo_notas);
    $todas_las_notas = json_decode($contenido, true) ?: [];
}

if ($es_alumno || $es_tutor) {
    // A. ¿En qué cursos está el alumno?
    $mis_cursos_alumno = [];
    foreach ($alumnos_por_curso as $clave_curso => $lista_alumnos) {
        foreach ($lista_alumnos as $al) {
            if (strtolower(trim($al)) === strtolower(trim($nombre_completo_actual))) {
                $mis_cursos_alumno[] = $clave_curso;
            }
        }
    }

    // B. Recopilar materias base de esos cursos
    // B. Recopilar materias base de esos cursos
    $materias_descubiertas = [];
    if (file_exists(__DIR__ . '/data/asignaciones.json')) {
        $asig_totales = json_decode(file_get_contents(__DIR__ . '/data/asignaciones.json'), true) ?: [];
        foreach ($asig_totales as $prof => $cursos_prof) {
            foreach ($cursos_prof as $c) {
                $clave_c = $c['curso'] . "_" . $c['division'];
                if (in_array($clave_c, $mis_cursos_alumno)) {
                    $mat = trim($c['materia']);
                    
                    // MAGIA: Evitamos agregar la etiqueta administrativa como si fuera una materia
                    if ($mat !== 'Maestro/a de Grado' && !in_array($mat, $materias_descubiertas)) {
                        $materias_descubiertas[] = $mat;
                    }
                }
            }
        }
    }

    // C. Rellenar las grillas completas según el nivel
    foreach ($mis_cursos_alumno as $mc) {
        // Si es de Primaria, sumamos su paquete de materias
        if (strpos($mc, 'grado') !== false) {
            $materias_descubiertas = array_merge($materias_descubiertas, ['Lengua', 'Matemática', 'Ciencias Naturales', 'Ciencias Sociales', 'Educación Física', 'Música', 'Plástica', 'Inglés']);
        }
        
        // ¡NUEVO! Si es de Secundaria (contiene '_anio'), inyectamos todo su plan de estudios
        if (strpos($mc, 'anio') !== false) {
            $materias_descubiertas = array_merge($materias_descubiertas, ['Literatura', 'Matemática', 'Física', 'Historia', 'Biología', 'Geografía', 'Inglés', 'Educación Física', 'Educación Tecnológica', 'Construcción Ciudadana', 'Contabilidad']);
        }
    }
    $materias_descubiertas = array_unique($materias_descubiertas);

    // D. Inicializar en vacío para que muestre "0 instancias evaluadas" por defecto
    foreach ($materias_descubiertas as $m) {
        $mis_materias[$m] = [];
    }

    // E. Acoplamos las notas reales que ya existan en calificaciones.json
    foreach ($todas_las_notas as $k_alumno => $materias) {
        if (strtolower(trim($k_alumno)) === strtolower(trim($nombre_completo_actual))) {
            foreach ($materias as $nom_mat => $examenes) {
                $mis_materias[trim($nom_mat)] = $examenes;
            }
            break;
        }
    }
}

// LÓGICA PARA ENTREVISTAS DE ADMISIÓN (CON AUTOLIMPIEZA)
$archivo_entrevistas = __DIR__ . '/data/entrevistas.json';
$todas_las_entrevistas = [];
$hubo_limpieza = false;

if (file_exists($archivo_entrevistas)) {
    $todas_las_entrevistas = json_decode(file_get_contents($archivo_entrevistas), true) ?: [];
    
    foreach ($todas_las_entrevistas as $key => $ent) {
        if ($ent['estado'] === 'agendada') {
            // Unimos fecha y hora y lo convertimos a "tiempo máquina"
            $fecha_hora_entrevista = strtotime($ent['fecha_agendada'] . ' ' . $ent['hora_agendada']);
            
            // Si el tiempo actual superó por 24hs (86400 segundos) a la cita, la borramos
            if (time() > ($fecha_hora_entrevista + 86400)) {
                unset($todas_las_entrevistas[$key]);
                $hubo_limpieza = true;
            }
        }
    }
    
    // Si borró alguna, guardamos el archivo actualizado sin molestar al usuario
    if ($hubo_limpieza) {
        $todas_las_entrevistas = array_values($todas_las_entrevistas); // Reordenar
        file_put_contents($archivo_entrevistas, json_encode($todas_las_entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
// LÓGICA PARA EL MENÚ DEL COMEDOR 
$archivo_menu = __DIR__ . '/data/menu.json';
$menu_hoy = [
    "plato_principal" => "No cargado",
    "opcion_vegetariana" => "No cargado",
    "postre" => "No cargado"
];

if (file_exists($archivo_menu)) {
    $menu_hoy = json_decode(file_get_contents($archivo_menu), true) ?: $menu_hoy;
}

// LÓGICA PARA TALLERES Y DEPORTES (NUEVA VERSIÓN DINÁMICA)
$archivo_talleres_din = __DIR__ . '/data/talleres_dinamicos.json';
$todos_los_talleres = [];
if (file_exists($archivo_talleres_din)) {
    $todos_los_talleres = json_decode(file_get_contents($archivo_talleres_din), true) ?: [];
}

$archivo_talleres = __DIR__ . '/data/talleres.json';
$mis_talleres = [];

// Leemos en qué talleres está anotado el usuario actual
if (file_exists($archivo_talleres)) {
    $datos_talleres = json_decode(file_get_contents($archivo_talleres), true);
    // Si los datos son válidos y el usuario tiene talleres
    if (is_array($datos_talleres) && isset($datos_talleres[$usuario_actual])) {
        $mis_talleres = $datos_talleres[$usuario_actual];
    }
}

// LÓGICA DE ASISTENCIAS 
$archivo_asistencias = __DIR__ . '/data/asistencias.json';
$todas_asistencias = file_exists($archivo_asistencias) ? json_decode(file_get_contents($archivo_asistencias), true) : [];

// LÓGICA DE ACTIVIDADES (NUEVO)
$archivo_actividades = __DIR__ . '/data/actividades.json';
$todas_las_actividades = file_exists($archivo_actividades) ? json_decode(file_get_contents($archivo_actividades), true) : [];


//LÓGICA PARA RESERVAS DE ESPACIOS 
$archivo_reservas = __DIR__ . '/data/reservas.json';
$todas_las_reservas = [];
if (file_exists($archivo_reservas)) {
    $todas_las_reservas = json_decode(file_get_contents($archivo_reservas), true) ?: [];
}

// LÓGICA PARA DOCUMENTACIÓN 
$archivo_docs = __DIR__ . '/data/documentos.json';
$mis_documentos = [];
$todos_los_documentos = []; // <--- ¡Lo hacemos global!

$tipos_permitidos = [
    'DNI del Alumno',
    'Apto Físico',
    'Partida de Nacimiento',
    'Permiso de Retiro',
    'Certificado Médico / Justificación de Falta'
];

if (file_exists($archivo_docs)) {
    $todos_los_documentos = json_decode(file_get_contents($archivo_docs), true) ?: [];
    if (isset($todos_los_documentos[$nombre_completo_actual])) {
        $mis_documentos = $todos_los_documentos[$nombre_completo_actual];
    }
}

// LÓGICA PARA CAPACITACIONES DOCENTES (NUEVA VERSIÓN DINÁMICA)
$archivo_cap = __DIR__ . '/data/capacitaciones.json';
$todas_las_capacitaciones = [];
if (file_exists($archivo_cap)) {
    $todas_las_capacitaciones = json_decode(file_get_contents($archivo_cap), true) ?: [];
}

// LÓGICA DEL SIMULADOR DE CORREOS
$archivo_correos = __DIR__ . '/data/correos.json';
$todos_los_correos = file_exists($archivo_correos) ? json_decode(file_get_contents($archivo_correos), true) : [];

// LÓGICA DE RECURSOS HUMANOS (CON AUTOLIMPIEZA Y BORRADO DE PDFs)
$archivo_postulaciones = __DIR__ . '/data/postulaciones.json';
$todas_las_postulaciones = [];
$hubo_limpieza_rrhh = false;

if (file_exists($archivo_postulaciones)) {
    $todas_las_postulaciones = json_decode(file_get_contents($archivo_postulaciones), true) ?: [];
    
    foreach ($todas_las_postulaciones as $key => $post) {
        if ($post['estado'] === 'agendada') {
            $fecha_hora_entrevista = strtotime($post['fecha_entrevista'] . ' ' . $post['hora_entrevista']);
            
            // Si pasaron 24hs (86400 segundos) de la cita, borramos el registro
            if (time() > ($fecha_hora_entrevista + 86400)) {
                
                // ¡Súper importante! Borramos el archivo PDF físico del servidor
                if (!empty($post['cv']) && file_exists(__DIR__ . '/' . $post['cv'])) {
                    unlink(__DIR__ . '/' . $post['cv']);
                }
                
                unset($todas_las_postulaciones[$key]);
                $hubo_limpieza_rrhh = true;
            }
        }
    }
    
    // Guardamos el JSON limpio sin molestar al usuario
    if ($hubo_limpieza_rrhh) {
        $todas_las_postulaciones = array_values($todas_las_postulaciones);
        file_put_contents($archivo_postulaciones, json_encode($todas_las_postulaciones, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Educativo | Panel Principal</title>
    <style>
        :root {
            --azul-primario: #1b499b;
            --celeste: #4cb2e4;
            --verde: #8cc63f;
            --naranja: #f15a24;
            --rosa: #ec008c;
            --violeta: #9e005d;
            --fondo-gris: #f4f7f6;
            --blanco: #ffffff;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--fondo-gris);
            display: flex;
        }

        /* --- BARRA LATERAL (SIDEBAR) --- */
        .sidebar {
            width: 280px;
            height: 100vh;
            background-color: var(--blanco);
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
        }

        .sidebar.collapsed { width: 70px; }

        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
        }

        .menu-toggle {
            cursor: pointer;
            font-size: 24px;
            color: var(--azul-primario);
        }

        /* --- LINKS DEL MENÚ --- */
        .menu-section { padding: 20px 10px 10px 20px; }
        .sidebar.collapsed .menu-section { padding-left: 15px; }

        .section-title {
            font-size: 0.75rem;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: block;
        }
        .sidebar.collapsed .section-title { display: none; }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #555;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: 0.2s;
            white-space: nowrap;
        }

        .nav-item:hover { background-color: var(--fondo-gris); color: var(--azul-primario); }
        .nav-item i { margin-right: 15px; font-style: normal; font-size: 1.2rem; }

        /*  ÁREA DE CONTENIDO  */
        .main-content {
            flex-grow: 1;
            padding: 30px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-welcome h1 { color: var(--azul-primario); margin: 0; font-size: 1.5rem; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            align-items: start;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            border-left: 5px solid var(--azul-primario);
        }
        /*  ESTILOS DE LA SECCIÓN DE INSCRIPCIONES  */

        /* El botón verde brillante arriba a la derecha */
        .btn-nuevo {
            background-color: var(--verde); /*  variable de la paleta */
            color: white; /* Letra blanca */
            border: none; /* Sin borde oscuro */
            padding: 10px 20px; /* Relleno para hacerlo gordito */
            border-radius: 8px; /* Bordes suaves */
            font-weight: bold; /* Negrita */
            cursor: pointer; /* Manito al pasar el mouse */
            transition: 0.3s; /* Animación suave al hacer hover */
        }

        /*   cuando pasamos el mouse por el botón  */
        .btn-nuevo:hover {
            background-color: #7ab033; 
            transform: scale(1.05); /* Crece un poco */
        }

        /* La caja blanca que abraza a toda la tabla */
        .tabla-contenedor {
            background: white; /* Fondo blanco */
            border-radius: 12px; /* Esquinas redondas */
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); /* Sombra súper suave y premium */
            overflow: hidden; /* Si la tabla es muy grande, recorta lo que sobra para no romper los bordes redondos */
            padding: 20px; /* Aire interno */
        }

        /* La tabla en sí misma */
        .tabla-datos {
            width: 100%; /* Que ocupe todo el ancho de la caja blanca */
            border-collapse: collapse; /* Borra los espacios dobles feos entre las celdas que hace HTML por defecto */
            text-align: left; /* Alinea todo el texto a la izquierda */
        }

        /* Estilo para los títulos de las columnas (th) y las celdas comunes (td) */
        .tabla-datos th, .tabla-datos td {
            padding: 15px; /* Mucho aire para que no se vea apretado */
            border-bottom: 1px solid #eee; /* Una línea gris muy finita separando cada renglón */
        }

        /* Estilo específico solo para los títulos de arriba */
        .tabla-datos th {
            color: #888; /* Gris claro */
            font-weight: 600; /* Semi-negrita */
            font-size: 0.9rem; /* Un poco más chico que el texto normal */
            text-transform: uppercase; /* Todo en mayúsculas     */
        }

        /*   cuando pasás el mouse por un renglón, se pinta de gris claro */
        .tabla-datos tbody tr:hover {
            background-color: #fcfcfc;
        }

        /*  PASTILLAS DE ESTADO   */
        /* Molde base para las pastillitas de colores */
        .badge {
            padding: 5px 12px; /* Relleno chiquito */
            border-radius: 50px; /* Bordes totalmente redondos como píldoras */
            font-size: 0.85rem; /* Letra chiquita */
            font-weight: bold; /* Negrita */
        }

        /* Color para la pastilla de "Pendiente" */
        .badge-pendiente {
            background-color: #fff3cd; /* Fondo amarillo clarito */
            color: #856404; /* Letra amarilla oscura/marrón */
        }

        /* Color para la pastilla de "Aprobado" */
        .badge-aprobado {
            background-color: #d4edda; /* Fondo verde clarito */
            color: #155724; /* Letra verde oscura */
        }

        /* ---BOTONES CHIQUITOS DE LA TABLA  */
        /* Celda que agrupa los botones para que no se peguen */
        .acciones-celda {
            display: flex; /* Los pone uno al lado del otro */
            gap: 10px; /* Les deja 10 píxeles de pasillo entre ellos */
        }

        /* Molde para los botones de Ver y Editar */
        .btn-accion {
            background: white; /* Fondo blanco */
            border: 1px solid #ddd; /* Borde gris clarito */
            padding: 5px 10px; /* Tamaño chico */
            border-radius: 5px; /* Bordes apenitas redondos */
            cursor: pointer; /* Manito de clic */
            transition: 0.2s; /* Transición rápida */
            color: #555; /* Texto gris oscuro */
        }

        /* Efecto al pasar el mouse por los botones chiquitos */
        .btn-accion:hover {
            border-color: var(--azul-primario); /* El borde se vuelve azul */
            color: var(--azul-primario); /* La letra se vuelve azul */
        }
        /*  ESTILOS DEL MODAL DEL DASHBOARD  */
        
        /* El telón negro de fondo */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            
            /* Arranca invisible */
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        /* La clase que le agregamos con JS para que aparezca */
        .modal-activo {
            opacity: 1;
            visibility: visible;
        }

        /* La caja blanca del formulario */
        .modal-box {
            background: white;
            width: 90%;
            max-width: 600px; /* Tamaño ideal para que el formulario no quede estirado */
            border-radius: 12px;
            overflow: hidden; /* Para que el encabezado no se salga de los bordes redondos */
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            transform: translateY(-20px); /* Arranca un poquito más arriba */
            transition: all 0.3s ease;
        }

        /* Cuando el modal se activa, la caja baja a su posición normal dando un efecto suave */
        .modal-activo .modal-box {
            transform: translateY(0);
        }

        /* Encabezado del modal (donde va el título y la X) */
        .modal-header {
            background-color: var(--fondo-gris);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--azul-primario);
            font-size: 1.3rem;
        }

        /* La crucecita para cerrar */
        .btn-cerrar-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #888;
            transition: 0.2s;
        }
        .btn-cerrar-modal:hover { color: var(--naranja); }

        /*  ESTILOS DEL FORMULARIO INTERNO  */
        .form-dashboard {
            padding: 20px;
        }

        /* Usamos CSS Grid para poner los inputs en 2 columnas */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Dos columnas iguales */
            gap: 15px; /* Espacio entre los campos */
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-size: 0.85rem;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .input-group input, .input-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit; /* Hereda la tipografía de la página */
        }

        /* La botonera de abajo */
        .modal-footer {
            margin-top: 25px;
            display: flex;
            justify-content: flex-end; /* Tira los botones a la derecha */
            gap: 10px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .btn-cancelar {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-guardar {
            padding: 10px 20px;
            background: var(--verde);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        /*  SISTEMA DE NAVEGACIÓN DE VISTAS  */
        
        /* Oculta todas las secciones por defecto */
        .vista-panel {
            display: none;
            animation: aparecerSuave 0.4s ease; /* Usamos la misma animación del carrusel */
        }

        /* Solo muestra la que tiene la clase activa */
        .vista-activa {
            display: block;
        }

        
    
        /* --- SISTEMA DE NAVEGACIÓN Y COLORES POR SECCIÓN --- */

        /* 1. Base para los ítems activos del menú */
        .nav-item.menu-activo {
            background-color: var(--fondo-gris);
            font-weight: bold;
        }

        /* Colores específicos para el borde y texto del menú lateral */
        .nav-item[data-vista="vista-documentacion"].menu-activo { color: var(--azul-primario); border-right: 4px solid var(--azul-primario); }
        .nav-item[data-vista="vista-calificaciones"].menu-activo { color: var(--rosa); border-right: 4px solid var(--rosa); }
        .nav-item[data-vista="vista-servicios"].menu-activo { color: var(--naranja); border-right: 4px solid var(--naranja); }
        .nav-item[data-vista="vista-talleres"].menu-activo { color: var(--verde); border-right: 4px solid var(--verde); }
        .nav-item[data-vista="vista-usuarios"].menu-activo { color: var(--violeta); border-right: 4px solid var(--violeta); }
        .nav-item[data-vista="vista-cursos"].menu-activo { color: var(--azul-primario); border-right: 4px solid var(--azul-primario); }
        /* 2. Base corregida para las tarjetas (stat-card) */
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border-left: 6px solid var(--azul-primario); /* Borde lateral por defecto */
            border-top: none; /* Nos aseguramos de que NO tenga borde arriba */
            transition: transform 0.3s ease;
        }

        /* 3. Unificamos los colores de las tarjetas según la vista donde estén */
        
        /* En Documentación: Todo Azul */
        #vista-documentacion .stat-card { border-left-color: var(--azul-primario); }

        /* En Calificaciones: Todo Rosa (incluyendo el acordeón) */
        #vista-calificaciones .stat-card { border-left-color: var(--rosa); }
        #vista-calificaciones .acordeon-materia { border-left: 6px solid var(--rosa); border-top: none; }

        /* En Servicios: Todo Naranja */
        #vista-servicios .stat-card { border-left-color: var(--naranja); border-top: none; }

        /* En Talleres: Todo Verde para que coincida con su sección */
        #vista-talleres .stat-card { border-left-color: var(--verde); border-top: none; }

        /* --- COLORES DINÁMICOS DE LAS TARJETAS --- */
        /* Cambia el color del borde izquierdo de las cards según la vista activa */
        #vista-documentacion .stat-card { border-left-color: var(--azul-primario); }
        #vista-calificaciones .stat-card { border-left-color: var(--rosa); }
        #vista-servicios .stat-card { border-left-color: var(--naranja); }
        #vista-talleres .stat-card { border-left-color: var(--verde); }
        .nav-item[data-vista="vista-actividades"].menu-activo { color: var(--rosa); border-right: 4px solid var(--rosa); }
        #vista-actividades .stat-card { border-left-color: var(--rosa); }
        #vista-actividades .btn-nuevo { background-color: var(--rosa); }
        .nav-item[data-vista="vista-rrhh"].menu-activo { color: var(--rosa); border-right: 4px solid var(--rosa); }
        #vista-rrhh .stat-card { border-left-color: var(--rosa); }
        /* --- ESTILOS DEL ACORDEÓN DE CALIFICACIONES --- */
        .acordeon-materia {
            background: white;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid var(--azul-primario); /* Línea de color a la izquierda */
            overflow: hidden;
        }

        /* La barra en la que el usuario hace clic */
        .acordeon-materia summary {
            padding: 20px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between; /* Separa el título del promedio */
            align-items: center;
            list-style: none; /* Oculta la flechita por defecto en algunos navegadores */
            transition: background 0.2s;
        }

        .acordeon-materia summary:hover {
            background-color: var(--fondo-gris);
        }

        /* El contenido oculto con los detalles de las notas */
        .acordeon-contenido {
            padding: 20px;
            border-top: 1px solid #eee;
            background-color: #fafafa;
        }

        .acordeon-contenido p {
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        /* Estilo para que los desplegables de adentro se vean escalonados */
        .acordeon-contenido details {
            margin-left: 20px;
            margin-top: 10px;
            border-left: 2px solid #ddd;
            padding-left: 10px;
        }
        /*  ESTILOS DEL PERFIL DE USUARIO (TOP BAR)  */

        /* El contenedor que agrupa el nombre y el círculo */
        .user-profile {
            display: flex; /* Alinea nombre y avatar en una fila */
            align-items: center; /* Los centra verticalmente entre sí */
            gap: 12px; /* Espacio de separación entre el nombre y el círculo */
            background-color: var(--blanco); /* Fondo blanco */
            padding: 6px 15px; /* Espacio interno para que no quede apretado */
            border-radius: 50px; /* Bordes totalmente redondeados tipo píldora */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Sombra muy suave para que resalte */
        }

        /* Estilo para el nombre */
        .user-name {
            font-weight: bold;
            color: var(--azul-primario); /* Usamos tu azul institucional */
            font-size: 0.9rem;
        }

        /* El círculo con las iniciales */
        .user-avatar {
            width: 35px;
            height: 35px;
            background-color: var(--azul-primario); /* Fondo azul */
            color: white; /* Letras blancas */
            border-radius: 50%; /* Lo hace un círculo perfecto */
            display: flex;
            justify-content: center; /* Centra la "JN" horizontalmente */
            align-items: center; /* Centra la "JN" verticalmente */
            font-weight: bold;
            font-size: 0.8rem;
            border: 2px solid var(--celeste); /* Un bordecito celeste para darle detalle */
        }
       /*  COLORES DINÁMICOS PARA LA SECCIÓN DOCENTES  */

        /* 1. GESTIÓN DE AULA (VIOLETA) */
        .nav-item[data-vista="vista-gestion-aula"].menu-activo { 
            color: var(--violeta); border-right: 4px solid var(--violeta); 
        }
        #vista-gestion-aula .stat-card { border-left-color: var(--violeta); }
        #vista-gestion-aula .acordeon-materia { border-left: 6px solid var(--violeta); }
        #vista-gestion-aula .btn-nuevo { background-color: var(--violeta); }

        /* 2. RESERVAS (CELESTE) */
        .nav-item[data-vista="vista-reservas"].menu-activo { 
            color: var(--celeste); border-right: 4px solid var(--celeste); 
        }
        #vista-reservas .stat-card { border-left-color: var(--celeste); }
        #vista-reservas .btn-nuevo { background-color: var(--celeste); }

        /* 3. RECURSOS Y SALUD (AZUL PRIMARIO) */
        .nav-item[data-vista="vista-recursos-salud"].menu-activo { 
            color: var(--azul-primario); border-right: 4px solid var(--azul-primario); 
        }
        #vista-recursos-salud .stat-card { border-left-color: var(--azul-primario); }
        #vista-recursos-salud .acordeon-materia { border-left: 6px solid var(--azul-primario); }
        #vista-recursos-salud .btn-nuevo { background-color: var(--azul-primario); }
        /* Estilo para los nuevos campos de fecha */
        .selector-fecha {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fafafa;
            font-family: inherit;
            cursor: pointer;
            box-sizing: border-box; /* Evita que el padding ensanche el input de más */
        }

        /* Cambia el color del borde al hacer foco */
        .selector-fecha:focus {
            outline: none;
            border-color: var(--celeste);
        }
        /* --- COLORES DINÁMICOS PARA LA SECCIÓN ADMINISTRATIVA --- */

        /* 1. COBRO DE CUOTAS (NARANJA) */
        .nav-item[data-vista="vista-cuotas"].menu-activo { color: var(--naranja); border-right: 4px solid var(--naranja); }
        #vista-cuotas .stat-card { border-left-color: var(--naranja); }
        #vista-cuotas .acordeon-materia { border-left: 5px solid var(--naranja); }

        /* 2. LIQUIDACIÓN DE SUELDOS (ROSA) */
        .nav-item[data-vista="vista-sueldos"].menu-activo { color: var(--rosa); border-right: 4px solid var(--rosa); }
        #vista-sueldos .stat-card { border-left-color: var(--rosa); }
        #vista-sueldos .acordeon-materia { border-left: 5px solid var(--rosa); }

        /* 3. COMEDOR (VERDE) */
        .nav-item[data-vista="vista-comedor"].menu-activo { color: var(--verde); border-right: 4px solid var(--verde); }
        #vista-comedor .stat-card { border-left-color: var(--verde); }
        #vista-comedor .btn-nuevo { background-color: var(--verde); }

        /* 4. TRANSPORTE (CELESTE) */
        .nav-item[data-vista="vista-transporte"].menu-activo { color: var(--celeste); border-right: 4px solid var(--celeste); }
        #vista-transporte .stat-card { border-left-color: var(--celeste); }

        /* 5. MANTENIMIENTO (AZUL PRIMARIO) */
        .nav-item[data-vista="vista-mantenimiento"].menu-activo { color: var(--azul-primario); border-right: 4px solid var(--azul-primario); }
        #vista-mantenimiento .stat-card { border-left-color: var(--azul-primario); }

        .nav-item[data-vista="vista-admin-talleres"].menu-activo { color: var(--verde); border-right: 4px solid var(--verde); }
        #vista-admin-talleres .stat-card { border-left-color: var(--verde); }

        .nav-item[data-vista="vista-asistencia-preceptor"].menu-activo { color: var(--celeste); border-right: 4px solid var(--celeste); }  
        .nav-item[data-vista="vista-documentos-preceptor"].menu-activo { color: var(--celeste); border-right: 4px solid var(--celeste); }
        .item-hijo:hover { background-color: #eef2f5; border-radius: 4px; }

        /* --- 📱 MODO RESPONSIVO (MÓVILES Y TABLETS) --- */
        @media (max-width: 768px) {
            /* 1. La barra lateral se convierte en un menú flotante oculto */
            .sidebar {
                position: fixed;
                left: -320px; /* Oculto a la izquierda */
                z-index: 9999;
                height: 100vh;
                box-shadow: 5px 0 25px rgba(0,0,0,0.5);
            }
            .sidebar.mobile-active {
                left: 0; /* Aparece suavemente al tocar el botón */
            }

            /* 2. Ajustamos los márgenes del contenido principal */
            .main-content {
                padding: 15px;
                width: 100%;
                box-sizing: border-box;
            }

            /* 3. El encabezado y los perfiles se apilan para no aplastarse */
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            /* 4. Las grillas pasan a ser de 1 sola columna vertical */
            .dashboard-grid {
                grid-template-columns: 1fr !important;
            }

            /* 5. Salvavidas para las Tablas: permite deslizar con el dedo en horizontal */
            .acordeon-contenido, .stat-card, .tabla-contenedor, [style*="overflow-x: auto"] {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            /* 6. Los Modales (ventanas flotantes) se adaptan a la pantalla chica */
            .modal-box {
                width: 95%;
                margin: 10px;
                max-height: 90vh;
                overflow-y: auto;
            }
            .form-grid {
                grid-template-columns: 1fr; /* Formularios en 1 sola columna */
            }
        }

        /* Ocultamos la barra hamburguesa extra si estamos en la computadora */
        @media (min-width: 769px) {
            .mobile-top-bar { display: none; }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="section-title" id="logo-text" style="color: var(--azul-primario); font-size: 1rem;">GESTIÓN EDUCATIVA</span>
            <div class="menu-toggle" onclick="toggleSidebar()">☰</div>
        </div>
        <?php if ($es_tutor): ?>
            <?php if (!empty($mis_tutelados)): ?>
            <div class="menu-section" style="background-color: #f8fbfb; margin: 10px 15px; border-radius: 8px; padding: 15px; border: 1px solid #e1e8ed;">
                <span class="section-title" style="color: var(--azul-primario); margin-bottom: 8px;">👨‍👩‍👧 Alumno Seleccionado</span>
                <form action="dashboard.php" method="GET" id="form-cambiar-hijo" style="margin: 0;">
                    <input type="hidden" name="vista" id="vista-actual-input" value="vista-documentacion">
                    
                    <select name="hijo" onchange="document.getElementById('form-cambiar-hijo').submit();" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.9rem; font-weight: bold; color: #444; cursor: pointer;">
                        <?php foreach ($mis_tutelados as $tutelado): ?>
                            <option value="<?php echo htmlspecialchars($tutelado); ?>" <?php echo $alumno_seleccionado === $tutelado ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tutelado); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <script>
                document.getElementById('form-cambiar-hijo')?.addEventListener('submit', function() {
                    const vistaActiva = document.querySelector('.vista-activa');
                    if (vistaActiva) {
                        document.getElementById('vista-actual-input').value = vistaActiva.id;
                    }
                });
            </script>
            <?php else: ?>
            <div class="menu-section" style="background-color: #fce8e6; margin: 10px 15px; border-radius: 8px; padding: 15px; border: 1px solid #f5c6cb;">
                <span style="color: var(--naranja); font-size: 0.85rem; font-weight: bold;">⚠️ Sin hijos asignados</span>
                <p style="font-size: 0.8rem; color: #666; margin-top: 5px; margin-bottom: 0;">Este perfil no tiene alumnos vinculados.</p>
                
                <div style="background: #222; color: #0f0; padding: 10px; margin-top: 15px; border-radius: 4px; font-family: monospace; font-size: 0.7rem; overflow-wrap: break-word;">
                    <strong>🕵️ DIAGNÓSTICO:</strong><br>
                    Sesión PHP: [<?php echo htmlspecialchars($usuario_actual); ?>]<br>
                    Nombre Perfil: [<?php echo htmlspecialchars($nombre_completo_actual); ?>]<br>
                    Lectura JSON: <?php echo file_exists($archivo_tutores) ? 'OK' : 'ERROR RUTAS'; ?><br>
                    Llaves JSON: [<?php 
                        $leido = json_decode(file_get_contents($archivo_tutores), true) ?: [];
                        echo htmlspecialchars(implode(', ', array_keys($leido))); 
                    ?>]
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($es_alumno || $es_tutor): ?>
        <div class="menu-section">
            <span class="section-title">Padres y Alumnos</span>
            <a href="#" class="nav-item menu-activo" data-vista="vista-documentacion"><i>📂</i> <span>Documentación</span></a>
            <a href="#" class="nav-item" data-vista="vista-calificaciones"><i>📊</i> <span>Calificaciones</span></a>
            <a href="#" class="nav-item" data-vista="vista-asistencia-alumno"><i>📅</i> <span>Asistencia</span></a>
            <a href="#" class="nav-item" data-vista="vista-servicios"><i>🚌</i> <span>Servicios</span></a>
            <a href="#" class="nav-item" data-vista="vista-talleres"><i>🏀</i> <span>Talleres y Deportes</span></a>
            <a href="#" class="nav-item" data-vista="vista-actividades"><i>📝</i> <span>Actividades y Tareas</span></a>
        </div>
        <?php endif; ?>

        <?php if ($es_profesor || $es_preceptor): ?>
        <div class="menu-section">
            <span class="section-title" style="border-top: 1px solid #eee; padding-top: 15px;">Plantel Docente</span>
            <?php if ($es_profesor): ?>
                <a href="#" class="nav-item menu-activo" data-vista="vista-gestion-aula"><i>🏫</i> <span>Gestión de Aula</span></a>
                <a href="#" class="nav-item" data-vista="vista-reservas"><i>🧪</i> <span>Reservas</span></a>
                <a href="#" class="nav-item" data-vista="vista-actividades"><i>📝</i> <span>Actividades y Tareas</span></a>
            <?php endif; ?>
            <a href="#" class="nav-item" data-vista="vista-recursos-salud"><i>🎓</i> <span>Capacitaciones y Perfil</span></a>
        </div>
        <?php endif; ?>
        <?php if ($es_preceptor || $es_maestro_primaria): ?>
        <div class="menu-section">
            <span class="section-title" style="border-top: 1px solid #eee; padding-top: 15px;">Control de Asistencia</span>
            <a href="#" class="nav-item <?php echo $es_preceptor ? 'menu-activo' : ''; ?>" data-vista="vista-asistencia-preceptor"><i>📝</i> <span>Tomar Asistencia</span></a>
            <?php if ($es_preceptor): ?>
            <a href="#" class="nav-item" data-vista="vista-documentos-preceptor"><i>📂</i> <span>Legajos Médicos</span></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <div class="menu-section">
            <span class="section-title" style="border-top: 1px solid #eee; padding-top: 15px;">Administrativo</span>
            <a href="#" class="nav-item menu-activo" data-vista="vista-usuarios"><i>👥</i> <span>Gestión de Usuarios</span></a>
            <a href="#" class="nav-item" data-vista="vista-cursos"><i>📚</i> <span>Asignación de Cátedras</span></a>
            <a href="#" class="nav-item" data-vista="vista-asignar-alumnos"><i>🎒</i> <span>Asignación de Alumnos</span></a>
            <a href="#" class="nav-item" data-vista="vista-admin-capacitaciones"><i>🎓</i> <span>Gestión Capacitaciones</span></a>
            <a href="#" class="nav-item" data-vista="vista-admin-talleres"><i>🏀</i> <span>Gestión Talleres</span></a> <a href="#" class="nav-item" data-vista="vista-comedor"><i>🥗</i> <span>Comedor</span></a>
            <a href="#" class="nav-item" data-vista="vista-transporte"><i>🚌</i> <span>Rutas de Transporte</span></a>
            <a href="#" class="nav-item" data-vista="vista-entrevistas"><i>🤝</i> <span>Entrevistas Admisión</span></a>
            <a href="#" class="nav-item" data-vista="vista-rrhh"><i>💼</i> <span>Recursos Humanos (RRHH)</span></a>
            <a href="#" class="nav-item" data-vista="vista-correos"><i>📧</i> <span>Registro de Correos</span></a>
        </div>
        <?php endif; ?>
        <div class="menu-section" style="margin-top: auto; padding-bottom: 20px;">
            <a href="#" class="nav-item" id="btn-abrir-cambiar-clave" style="color: var(--azul-primario); background-color: #f0f4f8; margin-bottom: 10px;">
                <i>🔑</i> <span style="font-weight: bold;">Cambiar Contraseña</span>
            </a>
            <a href="procesos/logout.php" class="nav-item" style="color: var(--naranja); background-color: #fff5f2;">
                <i>🚪</i> <span style="font-weight: bold;">Cerrar Sesión</span>
            </a>
        </div>
    </aside>

   <main class="main-content">
    <div class="mobile-top-bar" style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🎓</span>
                <span style="color: var(--azul-primario); font-weight: bold; font-size: 1.1rem; letter-spacing: 0.5px;">Portal Educativo</span>
            </div>
            <button onclick="toggleSidebar()" style="background: #f0f4f8; border: none; font-size: 1.5rem; color: var(--azul-primario); padding: 5px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s;">☰</button>
        </div>
        <?php if (isset($_GET['msj']) && $_GET['msj'] === 'clave_actualizada'): ?>
            <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">
                ✅ ¡Tu contraseña se ha actualizado correctamente!
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'clave_incorrecta'): ?>
            <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 20px;">
                ⚠️ La contraseña actual ingresada es incorrecta. No se realizaron cambios.
            </div>
        <?php endif; ?>
        
        <section id="vista-documentacion" class="vista-panel <?php echo ($es_alumno || $es_tutor) ? 'vista-activa' : ''; ?>">   
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Carga de Documentación</h1>
                    <p style="color: #666;">Subí certificados médicos, partidas de nacimiento y permisos.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                
                <?php if (isset($_GET['subida'])): ?>
                <div style="margin-bottom: 20px;">
                    <?php if ($_GET['subida'] === 'exito'): ?>
                        <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                            ✅ Documento subido y guardado correctamente.
                        </div>
                    <?php elseif ($_GET['subida'] === 'error_seguridad'): ?>
                        <div style="background: #fce8e6; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                            ⚠️ Archivo rechazado por seguridad. Solo se permiten formatos .PDF, .JPG o .PNG estándar.
                        </div>
                    <?php else: ?>
                        <div style="background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; font-weight: bold;">
                            ⚠️ Hubo un error al procesar el archivo. Es probable que sea demasiado pesado para el servidor.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                
                <?php if ($es_tutor): ?>
                <div class="stat-card">
                    <h3>Subir Nuevo Documento</h3>
                    
                    <form action="procesos/procesar_documento.php" method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                        
                        <input type="hidden" name="alumno_destino" value="<?php echo htmlspecialchars($alumno_seleccionado); ?>">

                        <label for="tipo-doc" style="display:block; margin-bottom: 5px; font-weight: bold;">Tipo de documento:</label>
                        <select name="tipo_doc" id="tipo-doc" required style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ddd;">
                            <option value="" disabled selected>Elegí qué vas a subir...</option>
                            <?php foreach ($tipos_permitidos as $tipo): ?>
                                <?php if ($tipo === 'Certificado Médico / Justificación de Falta' || $tipo === 'Permiso de Retiro' || !isset($mis_documentos[$tipo])): ?>
                                    <option value="<?php echo htmlspecialchars($tipo); ?>"><?php echo htmlspecialchars($tipo); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="file" name="archivo_doc" accept=".pdf, .jpg, .jpeg, .png" required style="margin-bottom: 15px; width: 100%;">
                        <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--azul-primario);">⬆️ Cargar Documento</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="stat-card" <?php echo (!$es_tutor) ? 'style="grid-column: 1 / -1;"' : ''; ?>>
                    <h3>Documentos Entregados</h3>
                    <ul style="list-style: none; padding: 0; margin-top: 15px;">
                        <?php foreach ($tipos_permitidos as $tipo): ?>
                            <?php if (isset($mis_documentos[$tipo])): ?>
                                <?php if ($tipo === 'Certificado Médico / Justificación de Falta' || $tipo === 'Permiso de Retiro'): ?>
                                    
                                    <?php 
                                    $lista_docs = isset($mis_documentos[$tipo]['fecha']) ? [$mis_documentos[$tipo]] : $mis_documentos[$tipo];
                                    ?>
                                    
                                    <?php foreach ($lista_docs as $idx => $doc_infinito): ?>
                                        <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                            ✅ <strong style="color: var(--verde);"><?php echo htmlspecialchars($tipo); ?> (#<?php echo $idx + 1; ?>):</strong> 
                                            <span style="color: #666; font-size: 0.9rem;">Entregado el <?php echo $doc_infinito['fecha']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    
                                <?php else: ?>
                                    <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        ✅ <strong style="color: var(--verde);"><?php echo htmlspecialchars($tipo); ?>:</strong> 
                                        <span style="color: #666; font-size: 0.9rem;">Entregado el <?php echo $mis_documentos[$tipo]['fecha']; ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php else: ?>
                                <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                    <span style="color: var(--naranja);">❌ <strong><?php echo htmlspecialchars($tipo); ?>:</strong> Pendiente de carga</span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
        <?php if ($es_admin): ?>
        <section id="vista-cursos" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Asignación de Cátedras</h1>
                    <p style="color: #666;">Administrá la grilla de cada curso y asigná o quitá profesores en tiempo real.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($nombre_completo_actual); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                <div class="stat-card" style="border-left-color: var(--azul-primario);">
                    <h3>Distribución del Plantel Docente</h3>
                    <p style="color: #888; margin-bottom: 20px;">Hacé clic en cada curso para asignar o remover al personal.</p>

                    <?php
                    // 1. Definiciones de Primaria
                    // 1. Definiciones de Primaria
                    $cursos_prim = ['1_grado' => '1° Grado', '2_grado' => '2° Grado', '3_grado' => '3° Grado', '4_grado' => '4° Grado', '5_grado' => '5° Grado', '6_grado' => '6° Grado', '7_grado' => '7° Grado'];
                    $materias_prim = ['Maestro/a de Grado']; 

                    // 2. Definiciones de Secundaria (¡Le metimos el Preceptor/a!)
                    $cursos_sec = ['1_anio' => '1° Año', '2_anio' => '2° Año', '3_anio' => '3° Año', '4_anio' => '4° Año', '5_anio' => '5° Año'];
                    $materias_sec = ['Preceptor/a', 'Literatura', 'Matemática', 'Física', 'Historia', 'Biología', 'Geografía', 'Inglés', 'Educación Física', 'Educación Tecnológica', 'Construcción Ciudadana', 'Contabilidad'];
                    
                    $divisiones = ['A', 'B', 'C'];

                    // Filtramos listas separadas
                    $profesores_disponibles = [];
                    $preceptores_disponibles = [];
                    foreach ($todos_los_usuarios as $u) {
                        if (($u['rol'] ?? '') === 'profesor') $profesores_disponibles[] = $u['nombre'];
                        if (($u['rol'] ?? '') === 'preceptor') $preceptores_disponibles[] = $u['nombre'];
                    }

                    function renderizarCursos($cursos, $divisiones, $materias, $nivel_nombre, $nivel_id, $asignaciones_totales, $profesores_disponibles, $preceptores_disponibles) {
                         echo "<h4 style='color: var(--azul-primario); margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Nivel {$nivel_nombre}</h4>";
                        
                        foreach ($cursos as $id_curso => $label_curso) {
                            foreach ($divisiones as $div) {
                                ?>
                                <details class="acordeon-materia" style="border-left-color: var(--azul-primario); margin-bottom: 10px;">
                                    <summary>
                                        <strong>🏫 <?php echo $label_curso . " \"" . $div . "\""; ?></strong>
                                        <span class="badge" style="background-color: var(--fondo-gris); color: var(--azul-primario); border: 1px solid var(--azul-primario);"><?php echo $nivel_nombre; ?></span>
                                    </summary>
                                    <div class="acordeon-contenido" style="background: white; padding: 10px 20px;">
                                        <table class="tabla-datos" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 40%;"><?php echo $nivel_id === 'primaria' ? 'Cargo' : 'Materia'; ?></th>
                                                    <th style="width: 60%;">Profesor Asignado / Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($materias as $materia): 
                                                    // Buscamos si ya tiene un profesor asignado
                                                    $profe_actual = null;
                                                    foreach ($asignaciones_totales as $nombre_profe => $lista_asig) {
                                                        if (is_array($lista_asig)) {
                                                            foreach ($lista_asig as $asig) {
                                                                if (($asig['nivel'] ?? '') === $nivel_id && 
                                                                    ($asig['curso'] ?? '') === $id_curso && 
                                                                    ($asig['division'] ?? '') === $div && 
                                                                    ($asig['materia'] ?? '') === $materia) {
                                                                    $profe_actual = $nombre_profe;
                                                                    break 2;
                                                                }
                                                            }
                                                        }
                                                    }
                                                ?>
                                                    <tr>
                                                        <td style="padding: 10px 5px;">📚 <strong><?php echo htmlspecialchars($materia); ?></strong></td>
                                                        <td style="padding: 10px 5px;">
                                                            <?php if ($profe_actual): ?>
                                                                <div style="display: flex; align-items: center; justify-content: space-between; background: #e6f6ec; padding: 6px 12px; border-radius: 6px; border: 1px solid #d4edda;">
                                                                    <span style="color: #155724; font-weight: 500;">👤 <?php echo htmlspecialchars($profe_actual); ?></span>
                                                                    <form action="procesos/procesar_asignacion.php" method="POST" style="margin: 0;">
                                                                        <input type="hidden" name="accion" value="quitar">
                                                                        <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel_id); ?>">
                                                                        <input type="hidden" name="profesor" value="<?php echo htmlspecialchars($profe_actual); ?>">
                                                                        <input type="hidden" name="curso" value="<?php echo htmlspecialchars($id_curso); ?>">
                                                                        <input type="hidden" name="division" value="<?php echo htmlspecialchars($div); ?>">
                                                                        <input type="hidden" name="materia" value="<?php echo htmlspecialchars($materia); ?>">
                                                                        <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja); padding: 3px 8px; font-size: 0.8rem; background: white; font-weight: bold;">❌ Quitar</button>
                                                                    </form>
                                                                </div>
                                                            <?php else: ?>
                                                                <form action="procesos/procesar_asignacion.php" method="POST" style="margin: 0; display: flex; gap: 10px; align-items: center;">
                                                                    <input type="hidden" name="accion" value="asignar">
                                                                    <input type="hidden" name="nivel" value="<?php echo htmlspecialchars($nivel_id); ?>">
                                                                    <input type="hidden" name="curso" value="<?php echo htmlspecialchars($id_curso); ?>">
                                                                    <input type="hidden" name="division" value="<?php echo htmlspecialchars($div); ?>">
                                                                    <input type="hidden" name="materia" value="<?php echo htmlspecialchars($materia); ?>">
                                                                    
                                                                    <select name="profesor" required style="padding: 6px; border: 1px solid #ddd; border-radius: 5px; flex: 1; font-family: inherit; font-size: 0.9rem;">
                                                                        <option value="" disabled selected>Seleccionar...</option>
                                                                        <?php 
                                                                        // MAGIA: Si es el renglón de preceptor, mostramos solo preceptores
                                                                        $lista_desplegable = ($materia === 'Preceptor/a') ? $preceptores_disponibles : $profesores_disponibles;
                                                                        foreach ($lista_desplegable as $nom_p): 
                                                                        ?>
                                                                            <option value="<?php echo htmlspecialchars($nom_p); ?>"><?php echo htmlspecialchars($nom_p); ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <button type="submit" class="btn-nuevo" style="padding: 6px 12px; font-size: 0.85rem; background-color: var(--verde);">➕ Asignar</button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                                <?php
                            }
                        }
                    }

                    // Renderizamos todo con dos líneas de código
                    renderizarCursos($cursos_prim, $divisiones, $materias_prim, 'Primario', 'primaria', $asignaciones_totales, $profesores_disponibles, $preceptores_disponibles);
                    renderizarCursos($cursos_sec, $divisiones, $materias_sec, 'Secundario', 'secundaria', $asignaciones_totales, $profesores_disponibles, $preceptores_disponibles);
                    ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <section id="vista-asignar-alumnos" class="vista-panel">
            <header class="top-bar" style="flex-direction: column; align-items: stretch; gap: 15px;">
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'alumno_asignado'): ?>
                    <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✅ Estudiante matriculado y asignado al curso correctamente.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'alumno_removido'): ?>
                    <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                        🗑️ Estudiante removido del curso con éxito.
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-welcome">
                        <h1>Asignación de Alumnos</h1>
                        <p style="color: #666;">Distribuí y mové a los estudiantes en los diferentes años, grados y divisiones del establecimiento.</p>
                    </div>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($nombre_completo_actual); ?></span>
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                <div class="stat-card" style="border-left-color: var(--azul-primario);">
                    <h3>Distribución de la Matrícula Escolar</h3>
                    <p style="color: #888; margin-bottom: 20px;">Abrí cada curso para gestionar sus alumnos inscriptos o agregar nuevos alumnos de la lista general del sistema.</p>

                    <?php
                    // Recopilamos todos los alumnos con rol de alumno creados en el sistema
                    $alumnos_totales_sistema = [];
                    foreach ($todos_los_usuarios as $u) {
                        if (($u['rol'] ?? '') === 'alumno') {
                            $alumnos_totales_sistema[] = trim($u['nombre']);
                        }
                    }
                    sort($alumnos_totales_sistema);

                    function renderizarAsignacionAlumnos($cursos, $divisiones, $nivel_nombre, $alumnos_por_curso, $alumnos_totales_sistema) {
                        echo "<h4 style='color: var(--azul-primario); margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;'>Alumnos - Nivel {$nivel_nombre}</h4>";
                        
                        foreach ($cursos as $id_curso => $label_curso) {
                            foreach ($divisiones as $div) {
                                $clave_c = $id_curso . "_" . $div;
                                $alumnos_este_curso = isset($alumnos_por_curso[$clave_c]) ? $alumnos_por_curso[$clave_c] : [];
                                ?>
                                <details class="acordeon-materia" style="border-left-color: var(--azul-primario); margin-bottom: 10px;">
                                    <summary>
                                        <strong>🎒 <?php echo $label_curso . " \"" . $div . "\""; ?></strong>
                                        <span class="badge" style="background-color: var(--fondo-gris); color: var(--azul-primario); border: 1px solid var(--azul-primario);"><?php echo count($alumnos_este_curso); ?> Alumnos</span>
                                    </summary>
                                    <div class="acordeon-contenido" style="background: white; padding: 10px 20px;">
                                        
                                        <form action="procesos/procesar_asignacion_alumno.php" method="POST" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #fafafa; padding: 10px; border-radius: 8px; border: 1px solid #eee;">
                                            <input type="hidden" name="accion" value="asignar">
                                            <input type="hidden" name="curso_clave" value="<?php echo $clave_c; ?>">
                                            
                                            <label style="font-weight: bold; font-size: 0.9rem; color: #555;">Inscribir Alumno:</label>
                                            <select name="alumno_nombre" required style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; flex: 1; font-family: inherit; font-size: 0.9rem;">
                                                <option value="" disabled selected>Seleccionar alumno de la institución...</option>
                                                <?php foreach ($alumnos_totales_sistema as $nom_al): ?>
                                                    <option value="<?php echo htmlspecialchars($nom_al); ?>"><?php echo htmlspecialchars($nom_al); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-nuevo" style="padding: 8px 15px; font-size: 0.85rem; background-color: var(--verde);">➕ Inscribir / Mover</button>
                                        </form>

                                        <table class="tabla-datos" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th style="width: 75%;">Nombre del Estudiante</th>
                                                    <th style="width: 25%; text-align: right;">Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($alumnos_este_curso)): ?>
                                                    <tr>
                                                        <td colspan="2" style="text-align: center; color: #999; padding: 15px;">No hay alumnos asignados a esta división.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($alumnos_este_curso as $alumno_item): ?>
                                                        <tr>
                                                            <td>👤 <strong><?php echo htmlspecialchars($alumno_item); ?></strong></td>
                                                            <td style="text-align: right;">
                                                                <form action="procesos/procesar_asignacion_alumno.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que querés remover a este alumno de este curso?');">
                                                                    <input type="hidden" name="accion" value="quitar">
                                                                    <input type="hidden" name="curso_clave" value="<?php echo $clave_c; ?>">
                                                                    <input type="hidden" name="alumno_nombre" value="<?php echo htmlspecialchars($alumno_item); ?>">
                                                                    <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja); padding: 4px 10px; font-size: 0.8rem; background: white; font-weight: bold;">❌ Quitar</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                                <?php
                            }
                        }
                    }

                    renderizarAsignacionAlumnos($cursos_prim, $divisiones, 'Primario', $alumnos_por_curso, $alumnos_totales_sistema);
                    renderizarAsignacionAlumnos($cursos_sec, $divisiones, 'Secundario', $alumnos_por_curso, $alumnos_totales_sistema);
                    ?>
                </div>
            </div>
        </section>

        <section id="vista-calificaciones" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Mis Calificaciones</h1>
                    <p style="color: #666;">Detalle de exámenes, promedios y asistencia por materia.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <?php if (empty($mis_materias)): ?>
                <div class="stat-card">
                    <p>Aún no tenés exámenes calificados en este ciclo lectivo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($mis_materias as $nombre_materia => $examenes): ?>
                    <details class="acordeon-materia" name="grupo-materias">
                        <summary>
                            <strong><?php echo htmlspecialchars($nombre_materia); ?></strong> 
                            <span class="badge badge-aprobado">
                                <?php echo count($examenes); ?> Instancias Evaluadas
                            </span>
                        </summary>
                        <div class="acordeon-contenido">
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($examenes as $ex): ?>
                                    <li style="padding: 12px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                        <span>📝 <?php echo htmlspecialchars($ex['examen']); ?></span>
                                        <strong style="color: var(--azul-primario); font-size: 1.1rem;">
                                            Nota: <?php echo htmlspecialchars($ex['nota']); ?>
                                        </strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <section id="vista-servicios" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Servicios al Alumno</h1>
                    <p style="color: #666;">Ubicación del transporte en tiempo real y menú escolar.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>🚌 Seguimiento del Micro (Ruta Norte)</h3>
                    <p style="color: var(--verde); font-weight: bold;">Estado: En recorrido</p>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d113589.65476043217!2d-59.04369795033486!3d-27.46056581413813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456b79d5bed36b%3A0xfa999f1ef3b40646!2sResistencia%2C%20Chaco!5e0!3m2!1ses-419!2sar!4v1715055301234!5m2!1ses-419!2sar" width="100%" height="250" style="border:0; border-radius: 8px; margin-top: 15px;" allowfullscreen="" loading="lazy"></iframe>
                    <p style="margin-top: 10px; font-size: 0.9rem;">Próxima parada estimada: <strong>14:15 hs</strong></p>
                </div>

                <div class="stat-card">
                    <h3>🥗 Menú del Comedor (Hoy)</h3>
                    <div style="background-color: var(--fondo-gris); padding: 15px; border-radius: 8px; margin-top: 15px;">
                        <h4 style="color: var(--azul-primario); margin-bottom: 5px;">Plato Principal:</h4>
                        <p><?php echo htmlspecialchars($menu_hoy['plato_principal']); ?></p>
                        
                        <h4 style="color: var(--azul-primario); margin-top: 15px; margin-bottom: 5px;">Opción Vegetariana:</h4>
                        <p><?php echo htmlspecialchars($menu_hoy['opcion_vegetariana']); ?></p>

                        <h4 style="color: var(--azul-primario); margin-top: 15px; margin-bottom: 5px;">Postre:</h4>
                        <p><?php echo htmlspecialchars($menu_hoy['postre']); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <section id="vista-talleres" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Talleres y Deportes</h1>
                    <p style="color: #666;">Inscripción a las actividades extracurriculares y clubes escolares.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php if (empty($todos_los_talleres)): ?>
                    <div class="stat-card" style="grid-column: 1 / -1;">
                        <p style="text-align: center; color: #888;">No hay talleres o deportes disponibles en este momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($todos_los_talleres) as $taller): 
                        $fecha_hora_str = $taller['fecha'] . ' ' . $taller['hora'];
                        $esta_finalizado = time() > strtotime($fecha_hora_str);
                        $inscriptos = $taller['inscriptos'] ?? [];
                        
                        // Si es tutor, $nombre_completo_actual contiene el nombre del hijo, haciendo que esto funcione de diez
                        $estoy_inscripto = in_array($nombre_completo_actual, $inscriptos);
                    ?>
                    <div class="stat-card" style="border-left-color: <?php echo $esta_finalizado ? '#ccc' : 'var(--verde)'; ?>; opacity: <?php echo $esta_finalizado ? '0.7' : '1'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($taller['titulo']); ?></h3>
                            <?php if ($esta_finalizado): ?>
                                <span class="badge" style="background: #eee; color: #888;">Finalizado</span>
                            <?php elseif ($estoy_inscripto): ?>
                                <span class="badge badge-aprobado">Inscripto/a</span>
                            <?php endif; ?>
                        </div>
                        <p style="color: #888; font-size: 0.85rem; font-weight: bold; margin-bottom: 10px;">🎯 Nivel: <?php echo htmlspecialchars($taller['nivel']); ?></p>
                        
                        <hr style="border: 0; border-top: 1px dashed #ddd; margin: 10px 0;">
                        <p style="color: #555; font-size: 0.9rem;">🕒 <strong>Horario de Encuentro:</strong> <?php echo date('d/m/Y', strtotime($taller['fecha'])); ?> - <?php echo htmlspecialchars($taller['hora']); ?> hs</p>
                        <p style="color: #555; font-size: 0.9rem; margin-top: 5px;">📍 <strong>Lugar / Sector:</strong> <?php echo htmlspecialchars($taller['lugar']); ?></p>
                        
                        <?php if (!$esta_finalizado && !$es_profesor): ?>
                            <form action="procesos/procesar_taller.php" method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="id_taller" value="<?php echo htmlspecialchars($taller['id']); ?>">
                                
                                <?php if ($es_tutor): ?>
                                    <?php if (!$estoy_inscripto): ?>
                                        <p style="text-align: center; color: #999; font-size: 0.85rem; margin-top: 10px; font-style: italic;">El alumno no participa de esta actividad.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($estoy_inscripto): ?>
                                        <input type="hidden" name="accion" value="baja">
                                        <button type="submit" class="btn-accion" style="width: 100%; color: var(--naranja); border-color: var(--naranja); background: #fff5f2;">❌ Cancelar Inscripción</button>
                                    <?php else: ?>
                                        <input type="hidden" name="accion" value="inscribir">
                                        <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--verde);">⚽ Inscribirme ahora</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="vista-actividades" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Actividades y Tareas</h1>
                    <p style="color: #666;">Espacio virtual de entrega de trabajos y devoluciones.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php if ($es_profesor): ?>
                    <div style="grid-column: 1 / -1; margin-bottom: 20px;">
                        <button class="btn-nuevo btn-abrir-crear-actividad" style="font-size: 1.1rem; padding: 12px 25px;">+ Crear Nueva Actividad</button>
                    </div>

                    <?php 
                    $actividades_del_profe = array_filter($todas_las_actividades, function($a) use ($nombre_completo_actual) {
                        return $a['profesor'] === $nombre_completo_actual;
                    });

                    if (empty($actividades_del_profe)): ?>
                        <div class="stat-card" style="grid-column: 1 / -1;">
                            <p style="text-align: center; color: #888;">No has creado ninguna actividad todavía.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_reverse($actividades_del_profe) as $act): 
                            $fecha_cierre = strtotime($act['fecha_limite']);
                            $esta_vencida = time() > $fecha_cierre;
                        ?>
                            <div class="stat-card" style="grid-column: 1 / -1; border-left: 5px solid <?php echo $esta_vencida ? 'var(--naranja)' : 'var(--rosa)'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($act['titulo']); ?></h3>
                                        <p style="color: #666; font-size: 0.9rem; font-weight: bold;">
                                            📚 <?php echo htmlspecialchars($act['materia']); ?> (<?php echo htmlspecialchars(str_replace('_', ' ', $act['curso'])) . ' "' . htmlspecialchars($act['division']) . '"'; ?>)
                                        </p>
                                        <p style="font-size: 0.85rem; color: <?php echo $esta_vencida ? 'var(--naranja)' : 'var(--verde)'; ?>;">
                                            ⏳ Cierre: <?php echo date('d/m/Y H:i', $fecha_cierre); ?>
                                        </p>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <form action="procesos/procesar_actividad.php" method="POST" onsubmit="return confirm('¿Borrar esta actividad y todas sus entregas?');">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_actividad" value="<?php echo htmlspecialchars($act['id']); ?>">
                                            <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja);">🗑️ Borrar</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <p style="margin-top: 15px; background: #f9f9f9; padding: 15px; border-radius: 6px; font-size: 0.95rem;">
                                    <?php echo nl2br(htmlspecialchars($act['descripcion'])); ?>
                                </p>
                                
                                <?php if (!empty($act['archivo_adjunto'])): ?>
                                    <a href="<?php echo htmlspecialchars($act['archivo_adjunto']); ?>" target="_blank" class="btn-accion" style="display: inline-block; margin-top: 10px; border-color: var(--celeste); color: var(--celeste);">📎 Ver Material Adjunto</a>
                                <?php endif; ?>

                                <details style="margin-top: 20px; border: 1px solid #eee; border-radius: 6px; padding: 10px;">
                                    <summary style="font-weight: bold; cursor: pointer; color: var(--azul-primario);">
                                        📥 Ver Entregas de Alumnos (<?php echo count($act['entregas'] ?? []); ?> recibidas)
                                    </summary>
                                    <div style="margin-top: 15px;">
                                        <?php if (empty($act['entregas'])): ?>
                                            <p style="color: #999; font-size: 0.85rem;">Aún no hay entregas.</p>
                                        <?php else: ?>
                                            <table class="tabla-datos" style="font-size: 0.9rem;">
                                                <thead><tr><th>Alumno</th><th>Fecha Entrega</th><th>Archivo/Texto</th><th>Devolución</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($act['entregas'] as $alu_nom => $entrega): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($alu_nom); ?></strong></td>
                                                            <td><?php echo $entrega['fecha']; ?></td>
                                                            <td>
                                                                <?php if (!empty($entrega['archivo'])): ?>
                                                                    <a href="<?php echo htmlspecialchars($entrega['archivo']); ?>" target="_blank">📎 Archivo</a><br>
                                                                <?php endif; ?>
                                                                <?php if (!empty($entrega['texto'])): ?>
                                                                    <small><i>"<?php echo htmlspecialchars($entrega['texto']); ?>"</i></small>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($entrega['nota'])): ?>
                                                                    <span class="badge badge-aprobado">Nota: <?php echo htmlspecialchars($entrega['nota']); ?></span>
                                                                <?php else: ?>
                                                                    <button class="btn-accion btn-corregir" data-id="<?php echo htmlspecialchars($act['id']); ?>" data-alumno="<?php echo htmlspecialchars($alu_nom); ?>" data-materia="<?php echo htmlspecialchars($act['materia']); ?>" style="color: var(--rosa); border-color: var(--rosa);">✏️ Evaluar</button>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php elseif ($es_alumno || $es_tutor): ?>
                    <?php 
                    $hay_tareas = false;
                    foreach (array_reverse($todas_las_actividades) as $act):
                        $clave_act = $act['curso'] . "_" . $act['division'];
                        // Verificamos si la tarea es para alguno de los cursos en los que está el alumno
                        if (in_array($clave_act, $mis_cursos_alumno ?? [])):
                            $hay_tareas = true;
                            $fecha_cierre = strtotime($act['fecha_limite']);
                            $esta_vencida = time() > $fecha_cierre;
                            
                            // Revisamos si este alumno ya entregó
                            $mi_entrega = $act['entregas'][$nombre_completo_actual] ?? null;
                            $estado_color = 'var(--rosa)';
                            $estado_texto = 'Pendiente';
                            
                            if ($mi_entrega) {
                                $estado_color = 'var(--verde)';
                                $estado_texto = 'Entregado';
                            } elseif ($esta_vencida) {
                                $estado_color = 'var(--naranja)';
                                $estado_texto = 'Vencido';
                            }
                    ?>
                        <div class="stat-card" style="border-left-color: <?php echo $estado_color; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($act['titulo']); ?></h3>
                                <span class="badge" style="background-color: <?php echo $estado_color; ?>; color: white;"><?php echo $estado_texto; ?></span>
                            </div>
                            <p style="color: var(--azul-primario); font-weight: bold; font-size: 0.9rem;">📚 <?php echo htmlspecialchars($act['materia']); ?></p>
                            <p style="font-size: 0.85rem; color: #666; margin-top: 5px;">⏳ Vence: <?php echo date('d/m/Y H:i', $fecha_cierre); ?></p>
                            
                            <hr style="border: 0; border-top: 1px dashed #ddd; margin: 15px 0;">
                            <p style="font-size: 0.95rem; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($act['descripcion'])); ?></p>
                            
                            <?php if (!empty($act['archivo_adjunto'])): ?>
                                <a href="<?php echo htmlspecialchars($act['archivo_adjunto']); ?>" target="_blank" class="btn-accion" style="display: inline-block; margin-top: 15px; border-color: var(--celeste); color: var(--celeste);">📎 Material Adjunto</a>
                            <?php endif; ?>

                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                                <?php if ($mi_entrega): ?>
                                    <div style="background: #f0fdf4; padding: 15px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                        <p style="color: #166534; font-weight: bold; margin-bottom: 5px;">✅ Tu entrega (<?php echo $mi_entrega['fecha']; ?>)</p>
                                        <?php if (!empty($mi_entrega['archivo'])): ?>
                                            <a href="<?php echo htmlspecialchars($mi_entrega['archivo']); ?>" target="_blank" style="font-size: 0.85rem;">Ver archivo subido</a><br>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($mi_entrega['devolucion'])): ?>
                                            <div style="margin-top: 10px; padding: 10px; background: white; border-left: 3px solid var(--azul-primario);">
                                                <p style="font-size: 0.85rem; color: #555;"><strong>Devolución del profe:</strong><br> <?php echo htmlspecialchars($mi_entrega['devolucion']); ?></p>
                                                <?php if (!empty($mi_entrega['nota'])): ?>
                                                    <p style="font-size: 0.9rem; margin-top: 5px; color: var(--rosa); font-weight: bold;">Calificación: <?php echo htmlspecialchars($mi_entrega['nota']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <p style="font-size: 0.85rem; color: #666; margin-top: 10px;"><i>Esperando corrección del profesor...</i></p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($es_alumno && !$esta_vencida): ?>
                                    <button class="btn-nuevo btn-abrir-entrega" data-id="<?php echo htmlspecialchars($act['id']); ?>" style="width: 100%;">Subir Entrega</button>
                                <?php elseif ($es_tutor): ?>
                                    <p style="color: #888; font-size: 0.85rem; text-align: center;">El alumno aún no ha entregado esta actividad.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    
                    if (!$hay_tareas): ?>
                        <div class="stat-card" style="grid-column: 1 / -1;">
                            <p style="text-align: center; color: #888;">No hay actividades pendientes para este curso.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <section id="vista-gestion-aula" class="vista-panel <?php echo $es_profesor ? 'vista-activa' : ''; ?>">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Gestión de Aula</h1>
                    <p style="color: #666;">Control de notas y asistencias por curso.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <?php if (empty($mis_cursos)): ?>
                <div class="stat-card">
                    <p style="text-align: center; color: #888; padding: 20px;">No tenés cursos asignados para este ciclo lectivo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($mis_cursos as $index => $curso): 
                    // Limpiamos los textos para que se vean lindos (ej: "5_anio" pasa a "5° Año")
                    $nombre_curso = str_replace('_anio', '° Año', $curso['curso']);
                    $nombre_curso = str_replace('_grado', '° Grado', $nombre_curso);
                ?>
                    <details class="acordeon-materia" <?php echo $index === 0 ? 'open' : ''; ?>>
                        <summary>
                            <strong>Curso: <?php echo $nombre_curso . " " . htmlspecialchars($curso['division']); ?></strong>
                            <span class="badge badge-aprobado" style="background-color: var(--fondo-gris); color: var(--violeta); border: 1px solid var(--violeta);">
                                <?php echo htmlspecialchars($curso['materia']); ?>
                            </span>
                        </summary>
                        <div class="acordeon-contenido">
                            <table class="tabla-datos">
                                <thead>
                                    <tr>
                                        <th>Alumnos Inscriptos</th>
                                    </tr>
                                </thead>
                        <tbody>
                            <?php 
                            $clave_curso = $curso['curso'] . "_" . $curso['division']; 
                            $alumnos_este_curso = isset($alumnos_por_curso[$clave_curso]) ? $alumnos_por_curso[$clave_curso] : [];
                            
                            if (empty($alumnos_este_curso)): 
                            ?>
                                <tr>
                                    <td style="padding: 20px; color: #888; text-align: center;">
                                        No hay alumnos inscriptos en este curso.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alumnos_este_curso as $nom_alumno): ?>
                                    <tr>
                                        <td style="padding: 15px;">
                                            <div style="font-size: 1.1rem; font-weight: 500; margin-bottom: 5px;">
                                                👤 <?php echo htmlspecialchars($nom_alumno); ?>
                                            </div>
                                            
                                            <?php 
                                            // Buscamos TODAS las notas de este alumno en el JSON
                                            $nom_alumno_limpio = trim($nom_alumno);
                                            $notas_del_alumno = [];
                                            
                                            foreach ($todas_las_notas as $k_alumno => $materias) {
                                                if (strtolower(trim($k_alumno)) === strtolower($nom_alumno_limpio)) {
                                                    $notas_del_alumno = $materias;
                                                    break;
                                                }
                                            }
                                            
                                            if (!empty($notas_del_alumno)): 
                                            ?>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 8px;">
                                                    <?php foreach ($notas_del_alumno as $nombre_mat => $lista_examenes): ?>
                                                        <?php foreach ($lista_examenes as $indice => $nota_info): ?>
                                                            <div style="background-color: #f0f4f8; border: 1px solid #d9e2ec; border-radius: 6px; padding: 5px 10px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">
                                                                <span style="color: var(--azul-primario);">
                                                                    <strong>[<?php echo htmlspecialchars($nombre_mat); ?>] <?php echo htmlspecialchars($nota_info['examen']); ?>:</strong> <?php echo htmlspecialchars($nota_info['nota']); ?>
                                                                </span>
                                                                
                                                                <form action="procesos/eliminar_nota.php" method="POST" style="margin: 0; display: flex; align-items: center;">
                                                                    <input type="hidden" name="alumno" value="<?php echo htmlspecialchars($nom_alumno_limpio); ?>">
                                                                    <input type="hidden" name="materia" value="<?php echo htmlspecialchars($nombre_mat); ?>">
                                                                    <input type="hidden" name="indice" value="<?php echo $indice; ?>">
                                                                    <button type="submit" style="background: none; border: none; color: var(--naranja); cursor: pointer; padding: 0 0 0 5px; font-weight: bold; font-size: 1rem;" title="Eliminar examen">×</button>
                                                                </form>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="font-size: 0.8rem; color: #aaa; margin-top: 5px;">Sin calificaciones cargadas.</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                            </table>
                            
                            <div class="modal-footer" style="justify-content: flex-start; gap: 15px;">
                                <button type="button" class="btn-nuevo btn-abrir-notas" 
                                    style="background-color: var(--azul-primario); padding: 10px 20px;"
                                    data-curso="<?php echo $clave_curso; ?>"
                                    data-materia="<?php echo htmlspecialchars($curso['materia']); ?>"
                                    data-nivel="<?php echo isset($curso['nivel']) ? $curso['nivel'] : 'secundaria'; ?>">
                                    📝 Cargar Examen
                                </button>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section id="vista-reservas" class="vista-panel">
            <header class="top-bar" style="flex-direction: column; align-items: stretch; gap: 15px;">
                
                <?php if (isset($_GET['error']) && $_GET['error'] === 'ocupado'): ?>
                    <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ⚠️ ¡Atención! El espacio ya se encuentra reservado por otro docente en ese mismo día y módulo. Elegí otro horario.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'reservado'): ?>
                    <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✅ ¡Tu reserva ha sido confirmada y agendada con éxito!
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'eliminado'): ?>
                    <div style="background: #e1f5fe; color: var(--azul-primario); padding: 12px; border-radius: 8px; font-weight: bold;">
                        🗑️ La reserva fue cancelada y el espacio vuelve a estar disponible.
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-welcome">
                        <h1>Reservas de Espacios</h1>
                        <p style="color: #666;">Gestioná el uso de laboratorios y áreas deportivas evitando cruces de horarios.</p>
                    </div>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                    </div>
                </div>
            </header>

            <?php 
            // Función auxiliar para no repetir código HTML en cada tarjeta
            function dibujarTarjetaReserva($titulo, $id_espacio, $es_profesor) {
                ?>
                <div class="stat-card">
                    <h3><?php echo $titulo; ?></h3>
                    <form action="procesos/procesar_reserva.php" method="POST">
                        <input type="hidden" name="accion" value="reservar">
                        <input type="hidden" name="espacio" value="<?php echo $id_espacio; ?>">
                        
                        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 120px;">
                                <label style="font-size: 0.8rem; color: #777; font-weight: bold;">Día:</label>
                                <input type="date" name="fecha_reserva" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div style="flex: 1; min-width: 150px;">
                                <label style="font-size: 0.8rem; color: #777; font-weight: bold;">Módulo Horario:</label>
                                <select name="modulo_reserva" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                    <option value="" disabled selected>Elegir...</option>
                                    <option value="1° Módulo (08:00 - 09:20)">1° Módulo (08:00 - 09:20)</option>
                                    <option value="2° Módulo (09:30 - 10:50)">2° Módulo (09:30 - 10:50)</option>
                                    <option value="3° Módulo (11:00 - 12:20)">3° Módulo (11:00 - 12:20)</option>
                                    <option value="4° Módulo (13:30 - 14:50)">4° Módulo (13:30 - 14:50)</option>
                                    <option value="5° Módulo (15:00 - 16:20)">5° Módulo (15:00 - 16:20)</option>
                                </select>
                            </div>
                        </div>
                        <?php if ($es_profesor): ?>
                            <button type="submit" class="btn-nuevo" style="width: 100%; margin-top: 15px; background-color: var(--azul-primario);">Confirmar Reserva</button>
                        <?php else: ?>
                            <p style="font-size: 0.8rem; color: #888; margin-top: 10px;">Solo personal docente puede reservar.</p>
                        <?php endif; ?>
                    </form>
                </div>
                <?php
            }
            ?>

            <div class="dashboard-grid">
                <?php 
                // Dibujamos las tarjetas usando la función auxiliar para mantener el código corto y limpio
                dibujarTarjetaReserva('🔬 Laboratorio de Química', 'laboratorio', $es_profesor);
                dibujarTarjetaReserva('⚽ Cancha de Fútbol 5', 'cancha', $es_profesor);
                dibujarTarjetaReserva('🏊‍♂️ Pileta de Natación', 'pileta', $es_profesor);
                dibujarTarjetaReserva('🏃 Pista de Atletismo', 'pista', $es_profesor);
                dibujarTarjetaReserva('🏋️ Gimnasio Cubierto', 'gimnasio', $es_profesor);
                dibujarTarjetaReserva('💻 Carro Móvil (Netbooks)', 'netbooks', $es_profesor); // Agregué un recurso móvil como ejemplo
                ?>

                <div class="stat-card" style="grid-column: 1 / -1;">
                    <h3>📅 Próximas Reservas Confirmadas</h3>
                    <?php if (empty($todas_las_reservas)): ?>
                        <p style="color: #999;">No hay reservas programadas en el sistema todavía.</p>
                    <?php else: ?>
                        <ul style="list-style: none; padding: 0;">
                            <?php 
                            $nombres_espacios = [
                                'laboratorio' => '🔬 Laboratorio',
                                'cancha' => '⚽ Cancha F5',
                                'pileta' => '🏊‍♂️ Pileta',
                                'pista' => '🏃 Pista',
                                'gimnasio' => '🏋️ Gimnasio',
                                'netbooks' => '💻 Netbooks'
                            ];
                            
                            foreach (array_reverse($todas_las_reservas) as $r): 
                                $nombre_mostrar = isset($nombres_espacios[$r['espacio']]) ? $nombres_espacios[$r['espacio']] : ucfirst($r['espacio']);
                                
                                // Damos vuelta la fecha para que se lea en formato local (DD-MM-YYYY)
                                $fecha_formateada = date('d-m-Y', strtotime($r['fecha']));
                            ?>
                                <li style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fafafa; border-radius: 6px; margin-bottom: 10px;">
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <strong style="color: var(--azul-primario); font-size: 1.1rem;"><?php echo $nombre_mostrar; ?></strong>
                                        <span>📅 <?php echo $fecha_formateada; ?> | ⏱️ <?php echo htmlspecialchars($r['modulo'] ?? ''); ?></span>
                                        <span style="color: #666; font-size: 0.85rem; font-weight: bold;">Reservado por: <?php echo htmlspecialchars($r['profesor']); ?></span>
                                    </div>
                                    
                                    <?php 
                                    // Solo el profe que la creó (o un admin futuro) puede borrarla
                                    if ($es_profesor && $r['profesor'] === $nombre_completo_actual): 
                                    ?>
                                    <form action="procesos/procesar_reserva.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro querés liberar este espacio?');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_reserva" value="<?php echo htmlspecialchars($r['id'] ?? ''); ?>">
                                        <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja); padding: 8px 15px; font-weight: bold;">❌ Cancelar Reserva</button>
                                    </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="vista-recursos-salud" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Desarrollo Profesional</h1>
                    <p style="color: #666;">Inscripción a capacitaciones institucionales y avisos.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php if (empty($todas_las_capacitaciones)): ?>
                    <div class="stat-card" style="grid-column: 1 / -1;">
                        <p style="text-align: center; color: #888;">No hay capacitaciones programadas por el momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($todas_las_capacitaciones) as $cap): 
                        $fecha_hora_str = $cap['fecha'] . ' ' . $cap['hora'];
                        $esta_finalizada = time() > strtotime($fecha_hora_str);
                        $inscriptos = $cap['inscriptos'] ?? [];
                        $estoy_inscripto = in_array($nombre_completo_actual, $inscriptos);
                    ?>
                    <div class="stat-card" style="border-left-color: <?php echo $esta_finalizada ? '#ccc' : 'var(--azul-primario)'; ?>; opacity: <?php echo $esta_finalizada ? '0.7' : '1'; ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($cap['titulo']); ?></h3>
                            <?php if ($esta_finalizada): ?>
                                <span class="badge" style="background: #eee; color: #888;">Finalizada</span>
                            <?php elseif ($estoy_inscripto): ?>
                                <span class="badge badge-aprobado">Inscripto/a</span>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="border: 0; border-top: 1px dashed #ddd; margin: 10px 0;">
                        <p style="color: #555; font-size: 0.9rem;">📅 <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($cap['fecha'])); ?> a las <?php echo htmlspecialchars($cap['hora']); ?> hs</p>
                        <p style="color: #555; font-size: 0.9rem; margin-top: 5px;">📍 <strong>Lugar:</strong> <?php echo htmlspecialchars($cap['lugar']); ?></p>
                        
                        <?php if (!$esta_finalizada): ?>
                            <form action="procesos/procesar_capacitacion.php" method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="id_cap" value="<?php echo htmlspecialchars($cap['id']); ?>">
                                <?php if ($estoy_inscripto): ?>
                                    <input type="hidden" name="accion" value="baja">
                                    <button type="submit" class="btn-accion" style="width: 100%; color: var(--naranja); border-color: var(--naranja); background: #fff5f2;">❌ Cancelar Inscripción</button>
                                <?php else: ?>
                                    <input type="hidden" name="accion" value="inscribir">
                                    <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--azul-primario);">✅ Inscribirme</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($es_maestro_primaria): ?>
                <div class="stat-card" style="grid-column: 1 / -1; margin-top: 20px; border-left-color: var(--violeta);">
                    <h3 style="color: var(--violeta);">🏥 Consulta de Legajos Médicos (Primaria)</h3>
                    <p style="margin-bottom: 15px; color: #666;">Acceso a los certificados de salud y justificaciones de tus alumnos.</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; flex-direction: column;">
                        <?php
                        $mis_alumnos_unicos = [];
                        foreach ($mis_cursos as $curso) {
                            $clave_c = $curso['curso'] . "_" . $curso['division'];
                            if (isset($alumnos_por_curso[$clave_c])) {
                                foreach ($alumnos_por_curso[$clave_c] as $alum) {
                                    $alum_limpio = trim($alum);
                                    if (!in_array($alum_limpio, $mis_alumnos_unicos)) $mis_alumnos_unicos[] = $alum_limpio;
                                }
                            }
                        }

                        $hay_legajos = false;
                        foreach ($mis_alumnos_unicos as $alum) {
                            $apto = $todos_los_documentos[$alum]['Apto Físico'] ?? null;
                            $certificados = $todos_los_documentos[$alum]['Certificado Médico / Justificación de Falta'] ?? null;

                            if ($apto || $certificados) {
                                $hay_legajos = true;
                                echo '<div style="margin-bottom: 10px; padding: 12px; background: #f9f9f9; border-radius: 8px; width: 100%; border-left: 3px solid var(--violeta);">';
                                echo '<strong>👤 ' . htmlspecialchars($alum) . '</strong><br>';
                                echo '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">';
                                
                                if ($apto) echo '<a href="' . htmlspecialchars($apto['archivo']) . '" target="_blank" class="btn-accion" style="text-decoration: none; border-color: var(--verde); color: var(--verde); background: #e6f6ec;">📄 Apto Físico</a>';
                                if ($certificados && is_array($certificados)) {
                                    foreach ($certificados as $cert) {
                                        $solo_fecha = explode(' ', $cert['fecha'])[0];
                                        echo '<a href="' . htmlspecialchars($cert['archivo']) . '" target="_blank" class="btn-accion" style="text-decoration: none; border-color: var(--naranja); color: var(--naranja); background: #fff5f2;">🩺 Justificativo (' . $solo_fecha . ')</a>';
                                    }
                                }
                                echo '</div></div>';
                            }
                        }
                        if (!$hay_legajos) echo '<p style="color: #999; font-size: 0.9rem;">Tus alumnos no han subido documentación médica aún.</p>';
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="vista-usuarios" class="vista-panel <?php echo $es_admin ? 'vista-activa' : ''; ?>">
            <header class="top-bar" style="flex-direction: column; align-items: stretch; gap: 15px;">
                <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicado'): ?>
                    <div style="background: #fce8e6; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ⚠️ El nombre de usuario de login ya se encuentra registrado. Elegí otro.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'usuario_creado'): ?>
                    <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✅ ¡Usuario registrado con éxito en el sistema! Ya puede iniciar sesión.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'usuario_modificado'): ?>
                    <div style="background: #e1f5fe; color: var(--azul-primario); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ⚙️ ¡Credenciales actualizadas con éxito!
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'usuario_eliminado'): ?>
                    <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                        🗑️ El usuario ha sido removido por completo del sistema.
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-welcome">
                        <h1>Gestión de Usuarios</h1>
                        <p style="color: #666;">Administración integral, separación por roles y modificación de credenciales de acceso.</p>
                    </div>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                    </div>
                </div>
            </header><?php if (!empty($todas_las_recuperaciones)): ?>
            <div class="stat-card" style="border-left-color: var(--naranja); background-color: #fff5f2; margin-bottom: 25px; grid-column: 1 / -1;">
                <h3 style="color: var(--naranja); margin-top: 0;">⚠️ Solicitudes de Blanqueo de Clave</h3>
                <p style="font-size: 0.9rem; color: #555;">Los siguientes usuarios olvidaron su contraseña. Al aprobar la solicitud, su nueva clave pasará a ser automáticamente <strong>123</strong>.</p>
                <div style="overflow-x: auto; margin-top: 15px;">
                    <table class="tabla-datos" style="background: white; border-radius: 8px; border: 1px solid #f5c6cb;">
                        <thead><tr><th>Usuario Afectado</th><th>Fecha de Solicitud</th><th style="text-align: right;">Acción Requerida</th></tr></thead>
                        <tbody>
                            <?php foreach (array_reverse($todas_las_recuperaciones) as $rec): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rec['usuario']); ?></strong></td>
                                    <td><span class="badge badge-pendiente"><?php echo $rec['fecha']; ?></span></td>
                                    <td style="text-align: right;">
                                        <form action="procesos/blanquear_clave.php" method="POST" onsubmit="return confirm('¿Confirmás el blanqueo de clave a 123 para este usuario?');" style="margin:0;">
                                            <input type="hidden" name="usuario_blanquear" value="<?php echo htmlspecialchars($rec['usuario']); ?>">
                                            <button type="submit" class="btn-nuevo" style="background-color: var(--naranja); padding: 8px 15px; font-size: 0.85rem;">🔄 Restablecer a 123</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // --- CEREBRO CLASIFICADOR DE USUARIOS ---
            $usuarios_alumnos = [];
            $usuarios_profesores = [];
            $usuarios_maestros = [];
            $usuarios_preceptores = [];
            $usuarios_tutores = [];
            $usuarios_admins = [];

            foreach ($todos_los_usuarios as $u) {
                $rol_u = $u['rol'] ?? '';
                if ($rol_u === 'alumno') {
                    $encontrado_curso = 'Sin Curso Asignado';
                    foreach ($alumnos_por_curso as $clave_c => $lista_al) {
                        if (in_array($u['nombre'], $lista_al)) {
                            $encontrado_curso = $clave_c;
                            break;
                        }
                    }
                    $usuarios_alumnos[$encontrado_curso][] = $u;
                } elseif ($rol_u === 'profesor') {
                    $es_de_primaria = false;
                    if (isset($asignaciones_totales[$u['nombre']])) {
                        foreach ($asignaciones_totales[$u['nombre']] as $asig) {
                            if (($asig['nivel'] ?? '') === 'primaria') {
                                $es_de_primaria = true;
                                break;
                            }
                        }
                    }
                    if ($es_de_primaria) {
                        $usuarios_maestros[] = $u;
                    } else {
                        $usuarios_profesores[] = $u;
                    }
                } elseif ($rol_u === 'preceptor') {
                    $usuarios_preceptores[] = $u;
                } elseif ($rol_u === 'tutor') {
                    $usuarios_tutores[] = $u;
                } elseif ($rol_u === 'admin') {
                    $usuarios_admins[] = $u;
                }
            }
            ksort($usuarios_alumnos); // Ordenamos los cursos de forma alfabética
            ?>

            <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                <div class="stat-card" style="border-left-color: var(--violeta);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                        <h3>Árbol de Cuentas Institucionales</h3>
                        <button type="button" class="btn-nuevo" id="btn-abrir-usuarios" style="background-color: var(--violeta); display: flex; align-items: center; gap: 8px;">
                            👥 Registrar Nuevo Usuario
                        </button>
                    </div>

                    <details class="acordeon-materia" style="border-left-color: var(--verde); margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">🎒 Alumnos (Agrupados por Curso y División)</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php if (empty($usuarios_alumnos)): ?>
                                <p style="color:#999; text-align:center;">No hay alumnos registrados.</p>
                            <?php else: ?>
                                <?php foreach ($usuarios_alumnos as $clave_curso => $lista_de_alumnos): 
                                    $label_curso = str_replace(['_anio', '_grado', '_'], ['° Año', '° Grado', ' '], $clave_curso);
                                ?>
                                    <details style="margin-bottom: 10px; border: 1px solid #eee; border-radius: 6px;">
                                        <summary style="padding: 10px; background: #fafafa; font-weight: 600; cursor: pointer; color: var(--azul-primario);">
                                            🏫 Cursos: <?php echo $label_curso; ?> (<?php echo count($lista_de_alumnos); ?> Alumnos)
                                        </summary>
                                        <div style="padding: 10px; overflow-x: auto;">
                                            <table class="tabla-datos">
                                                <thead>
                                                    <tr><th>Nombre Completo</th><th>Usuario (Login)</th><th>Contraseña</th><th style="text-align:right;">Acciones</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($lista_de_alumnos as $alu): ?>
                                                        <tr>
                                                            <td><strong><?php echo htmlspecialchars($alu['nombre']); ?></strong></td>
                                                            <td><code><?php echo htmlspecialchars($alu['usuario']); ?></code></td>
                                                            <td><code style="color: #999;">********</code></td>
                                                            <td style="text-align:right;">
                                                                <div class="acciones-celda" style="justify-content: flex-end;">
                                                                    <button type="button" class="btn-accion btn-ver-ficha" 
                                                                        data-nombre="<?php echo htmlspecialchars($alu['nombre']); ?>"
                                                                        data-dni="<?php echo htmlspecialchars($alu['dni'] ?? 'No cargado'); ?>"
                                                                        data-fnac="<?php echo htmlspecialchars($alu['fecha_nacimiento'] ?? 'No cargado'); ?>"
                                                                        data-tnombre="<?php echo htmlspecialchars($alu['tutor_nombre'] ?? 'No cargado'); ?>"
                                                                        data-ttel="<?php echo htmlspecialchars($alu['tutor_telefono'] ?? 'No cargado'); ?>"
                                                                        data-temail="<?php echo htmlspecialchars($alu['tutor_email'] ?? 'No cargado'); ?>"
                                                                    >👁️ Ficha</button>
                                                                    <button type="button" class="btn-accion btn-editar-usuario" data-nombre="<?php echo htmlspecialchars($alu['nombre']); ?>" data-usuario="<?php echo htmlspecialchars($alu['usuario']); ?>" data-password="••••••••">✏️ Modificar</button>
                                                                    <form action="procesos/eliminar_usuario.php" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar a este alumno? Esto limpiará sus accesos.');" style="margin:0;">
                                                                        <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($alu['usuario']); ?>">
                                                                        <button type="submit" class="btn-accion" style="color:var(--naranja); border-color:var(--naranja);">🗑️ Borrar</button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </details>

                    <?php
                    function dibujarTablaUsuarios($lista, $titulo_singular, $color_borde) {
                        if (empty($lista)) {
                            echo '<p style="color:#999; padding: 10px;">No hay personal registrado en esta categoría.</p>';
                        } else {
                            echo '<div style="overflow-x:auto;"><table class="tabla-datos"><thead><tr><th>Nombre Completo</th><th>Usuario (Login)</th><th>Contraseña</th><th style="text-align:right;">Acciones</th></tr></thead><tbody>';
                            foreach ($lista as $usr_item) {
                                echo '<tr>';
                                echo '<td><strong>'.htmlspecialchars($usr_item['nombre']).'</strong></td>';
                                echo '<td><code>'.htmlspecialchars($usr_item['usuario']).'</code></td>';
                                echo '<td><code>'.htmlspecialchars($usr_item['password']).'</code></td>';
                                echo '<td style="text-align:right;"><div class="acciones-celda" style="justify-content: flex-end;">';
                                echo '<button type="button" class="btn-accion btn-editar-usuario" data-nombre="'.htmlspecialchars($usr_item['nombre']).'" data-usuario="'.htmlspecialchars($usr_item['usuario']).'" data-password="'.htmlspecialchars($usr_item['password']).'">✏️ Modificar</button>';
                                echo '<form action="procesos/eliminar_usuario.php" method="POST" onsubmit="return confirm(\'¿Eliminar por completo a este usuario?\');" style="margin:0;">';
                                echo '<input type="hidden" name="usuario_id" value="'.htmlspecialchars($usr_item['usuario']).'">';
                                echo '<button type="submit" class="btn-accion" style="color:var(--naranja); border-color:var(--naranja);">🗑️ Borrar</button>';
                                echo '</form>';
                                echo '</div></td>';
                                echo '</tr>';
                            }
                            echo '</tbody></table></div>';
                        }
                    }
                    ?>

                    <details class="acordeon-materia" style="border-left-color: var(--rosa); margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">📚 Personal Docente (Nivel Secundario)</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php dibujarTablaUsuarios($usuarios_profesores, 'Profesor', 'var(--rosa)'); ?>
                        </div>
                    </details>

                    <details class="acordeon-materia" style="border-left-color: var(--violeta); margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">🍏 Maestros/as de Grado (Nivel Primario)</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php dibujarTablaUsuarios($usuarios_maestros, 'Maestro', 'var(--violeta)'); ?>
                        </div>
                    </details>

                    <details class="acordeon-materia" style="border-left-color: var(--celeste); margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">📋 Preceptores / Auxiliares</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php dibujarTablaUsuarios($usuarios_preceptores, 'Preceptor', 'var(--celeste)'); ?>
                        </div>
                    </details>

                    <details class="acordeon-materia" style="border-left-color: var(--naranja); margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">👨‍👩‍👧 Padres / Tutores Legales</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php dibujarTablaUsuarios($usuarios_tutores, 'Tutor', 'var(--naranja)'); ?>
                        </div>
                    </details>

                    <details class="acordeon-materia" style="border-left-color: #333; margin-bottom: 15px;">
                        <summary style="font-weight: bold; color: #333;">⚙️ Equipo de Administración</summary>
                        <div class="acordeon-contenido" style="background: white; padding: 15px;">
                            <?php dibujarTablaUsuarios($usuarios_admins, 'Admin', '#333'); ?>
                        </div>
                    </details>

                </div>
            </div>
        </section>

        <?php if ($es_admin): ?>
        <section id="vista-admin-capacitaciones" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Gestión de Capacitaciones</h1>
                    <p style="color: #666;">Creación de cursos y control de asistencia docente.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="border-left-color: var(--rosa);">
                    <h3 style="color: var(--rosa);">Crear Nueva Capacitación</h3>
                    <form action="procesos/procesar_capacitacion.php" method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="accion" value="crear">
                        
                        <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Título del Curso/Charla:</label>
                        <input type="text" name="titulo" required style="width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">
                        
                        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Fecha:</label>
                                <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd; font-family: inherit; box-sizing: border-box;">
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Hora:</label>
                                <input type="time" name="hora" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd; font-family: inherit; box-sizing: border-box;">
                            </div>
                        </div>

                        <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Lugar / Aula:</label>
                        <input type="text" name="lugar" required placeholder="Ej: Salón de Actos" style="width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">
                        
                        <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--rosa);">Publicar Capacitación</button>
                    </form>
                </div>

                <div class="stat-card" style="grid-column: 1 / -1;">
                    <h3>Panel de Control de Cursos</h3>
                    <?php if (empty($todas_las_capacitaciones)): ?>
                        <p style="color: #999;">No hay capacitaciones creadas.</p>
                    <?php else: ?>
                        <?php foreach (array_reverse($todas_las_capacitaciones) as $cap): 
                            $fecha_hora_str = $cap['fecha'] . ' ' . $cap['hora'];
                            $esta_finalizada = time() > strtotime($fecha_hora_str);
                            $inscriptos = $cap['inscriptos'] ?? [];
                        ?>
                            <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-bottom: 15px; background: <?php echo $esta_finalizada ? '#f9f9f9' : '#fff'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0; color: var(--azul-primario);"><?php echo htmlspecialchars($cap['titulo']); ?></h4>
                                        <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #666;">
                                            📅 <?php echo date('d/m/Y', strtotime($cap['fecha'])); ?> | 🕒 <?php echo htmlspecialchars($cap['hora']); ?> hs | 📍 <?php echo htmlspecialchars($cap['lugar']); ?>
                                        </p>
                                    </div>
                                    <form action="procesos/procesar_capacitacion.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que querés borrar este curso completo?');">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id_cap" value="<?php echo htmlspecialchars($cap['id']); ?>">
                                        <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja);">🗑️ Borrar</button>
                                    </form>
                                </div>
                                
                                <details style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                    <summary style="cursor: pointer; font-size: 0.9rem; font-weight: bold; color: var(--verde);">
                                        👥 Ver Inscriptos (<?php echo count($inscriptos); ?> docentes)
                                    </summary>
                                    <ul style="margin-top: 10px; padding-left: 20px; font-size: 0.9rem; color: #444;">
                                        <?php if (empty($inscriptos)): ?>
                                            <li>Nadie se ha inscripto aún.</li>
                                        <?php else: ?>
                                            <?php foreach ($inscriptos as $docente): ?>
                                                <li><?php echo htmlspecialchars($docente); ?></li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <section id="vista-admin-talleres" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Gestión de Talleres y Deportes</h1>
                    <p style="color: #666;">Apertura de convocatorias extracurriculares y grilla de alumnos inscriptos.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="border-left-color: var(--verde);">
                    <h3 style="color: var(--verde);">Abrir Nueva Convocatoria</h3>
                    <form action="procesos/procesar_taller.php" method="POST" style="margin-top: 15px;">
                        <input type="hidden" name="accion" value="crear">
                        
                        <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Nombre de la Actividad:</label>
                        <input type="text" name="titulo" placeholder="Ej: Club de Ajedrez o Básquet Femenino" required style="width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">
                        
                        <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Nivel / Categoría:</label>
                        <input type="text" name="nivel" placeholder="Ej: Principiantes o Sub-18 Competitivo" required style="width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 15px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">

                        <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                            <div style="flex: 1;">
                                <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Fecha de Inicio/Encuentro:</label>
                                <input type="date" name="fecha" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd; font-family: inherit; box-sizing: border-box;">
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Hora:</label>
                                <input type="time" name="hora" required style="width: 100%; padding: 10px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd; font-family: inherit; box-sizing: border-box;">
                            </div>
                        </div>

                        <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Lugar / Instalación:</label>
                        <input type="text" name="lugar" required placeholder="Ej: Gimnasio Cubierto o Biblioteca" style="width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">
                        
                        <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--verde);">Publicar Actividad</button>
                    </form>
                </div>

                <div class="stat-card" style="grid-column: 1 / -1;">
                    <h3>Grilla Operativa de Talleres</h3>
                    <?php if (empty($todos_los_talleres)): ?>
                        <p style="color: #999;">No hay actividades extracurriculares creadas todavía.</p>
                    <?php else: ?>
                        <?php foreach (array_reverse($todos_los_talleres) as $taller): 
                            $fecha_hora_str = $taller['fecha'] . ' ' . $taller['hora'];
                            $esta_finalizado = time() > strtotime($fecha_hora_str);
                            $inscriptos = $taller['inscriptos'] ?? [];
                        ?>
                            <div style="border: 1px solid #eee; padding: 15px; border-radius: 8px; margin-bottom: 15px; background: <?php echo $esta_finalizada ? '#f9f9f9' : '#fff'; ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0; color: var(--azul-primario);"><?php echo htmlspecialchars($taller['titulo']); ?> <small style="color:#666;">(<?php echo htmlspecialchars($taller['nivel']); ?>)</small></h4>
                                        <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #666;">
                                            📅 <?php echo date('d/m/Y', strtotime($taller['fecha'])); ?> | 🕒 <?php echo htmlspecialchars($taller['hora']); ?> hs | 📍 <?php echo htmlspecialchars($taller['lugar']); ?>
                                        </p>
                                    </div>
                                    <form action="procesos/procesar_taller.php" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que querés dar de baja este taller por completo?');">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id_taller" value="<?php echo htmlspecialchars($taller['id']); ?>">
                                        <button type="submit" class="btn-accion" style="color: var(--naranja); border-color: var(--naranja);">🗑️ Eliminar</button>
                                    </form>
                                </div>
                                
                                <details style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ddd;">
                                    <summary style="cursor: pointer; font-size: 0.9rem; font-weight: bold; color: var(--verde);">
                                        🎒 Alumnos Matriculados (<?php echo count($inscriptos); ?> inscriptos)
                                    </summary>
                                    <ul style="margin-top: 10px; padding-left: 20px; font-size: 0.9rem; color: #444;">
                                        <?php if (empty($inscriptos)): ?>
                                            <li>Ningún alumno se ha anotado aún.</li>
                                        <?php else: ?>
                                            <?php foreach ($inscriptos as $alumno): ?>
                                                <li>🎒 <?php echo htmlspecialchars($alumno); ?></li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </details>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section id="vista-comedor" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Gestión de Comedor</h1>
                    <p style="color: #666;">Carga de menú y administración de proveedores.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>Cargar Menú del Día</h3>
                    <form action="procesos/procesar_menu.php" method="POST" style="margin-top:15px;">
                        <input type="text" name="plato_principal" placeholder="Plato Principal" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:1px solid #ddd;">
                        <input type="text" name="opcion_vegetariana" placeholder="Opción Vegetariana" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:1px solid #ddd;">
                        <input type="text" name="postre" placeholder="Postre" required style="width:100%; padding:10px; margin-bottom:10px; border-radius:5px; border:1px solid #ddd;">
                        <button type="submit" class="btn-nuevo" style="width:100%;">Publicar Menú</button>
                    </form>
                </div>

                <div class="stat-card">
                    <h3>Proveedores</h3>
                    <ul style="list-style:none; padding:0; margin-top:10px;">
                        <li style="padding:10px; border-bottom:1px solid #eee;">
                            <strong>Frutería "El Sol":</strong> Frutas y Verduras <br>
                            <small>Contacto: 3624-XXXXXX</small>
                        </li>
                        <li style="padding:10px; border-bottom:1px solid #eee;">
                            <strong>Carnicería Central:</strong> Carnes rojas y pollo <br>
                            <small>Contacto: 3624-YYYYYY</small>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="vista-transporte" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Rutas de Transporte</h1>
                    <p style="color: #666;">Seguimiento en tiempo real de las unidades de traslado.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card">
                    <h3>🚌 Colectivo 01 - Ruta Norte</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d113589.65476043217!2d-59.04369795033486!3d-27.46056581413813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456b79d5bed36b%3A0xfa999f1ef3b40646!2sResistencia%2C%20Chaco!5e0!3m2!1ses-419!2sar!4v1715055301234!5m2!1ses-419!2sar" width="100%" height="150" style="border:0; border-radius:8px; margin-top:10px;"></iframe>
                </div>
                <div class="stat-card">
                    <h3>🚌 Colectivo 02 - Ruta Sur</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d113589.65476043217!2d-59.04369795033486!3d-27.46056581413813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456b79d5bed36b%3A0xfa999f1ef3b40646!2sResistencia%2C%20Chaco!5e0!3m2!1ses-419!2sar!4v1715055301234!5m2!1ses-419!2sar" width="100%" height="150" style="border:0; border-radius:8px; margin-top:10px;"></iframe>
                </div>
                <div class="stat-card">
                    <h3>🚌 Colectivo 03 - Ruta Este</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d113589.65476043217!2d-59.04369795033486!3d-27.46056581413813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456b79d5bed36b%3A0xfa999f1ef3b40646!2sResistencia%2C%20Chaco!5e0!3m2!1ses-419!2sar!4v1715055301234!5m2!1ses-419!2sar" width="100%" height="150" style="border:0; border-radius:8px; margin-top:10px;"></iframe>
                </div>
                <div class="stat-card">
                    <h3>🚌 Colectivo 04 - Ruta Oeste</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d113589.65476043217!2d-59.04369795033486!3d-27.46056581413813!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94456b79d5bed36b%3A0xfa999f1ef3b40646!2sResistencia%2C%20Chaco!5e0!3m2!1ses-419!2sar!4v1715055301234!5m2!1ses-419!2sar" width="100%" height="150" style="border:0; border-radius:8px; margin-top:10px;"></iframe>
                </div>
            </div>
        </section>


        <?php if ($es_alumno || $es_tutor): ?>
        <section id="vista-asistencia-alumno" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Registro de Inasistencias</h1>
                    <p style="color: #666;">Historial de faltas registradas en el sistema.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="grid-column: 1 / -1; border-left-color: var(--naranja);">
                    <h3>Registro de Faltas</h3>
                    <?php
                    $mis_faltas = [];
                    foreach ($mis_cursos_alumno as $c_alumno) {
                        if (isset($todas_asistencias[$c_alumno])) {
                            foreach ($todas_asistencias[$c_alumno] as $fecha => $ausentes) {
                                if (in_array(trim($nombre_completo_actual), $ausentes)) {
                                    $mis_faltas[] = ['fecha' => $fecha, 'curso' => $c_alumno];
                                }
                            }
                        }
                    }
                    ?>
                    
                   <?php
                    // Contamos las faltas justificadas (certificados médicos aprobados)
                    $cant_justificadas = 0;
                    if (isset($mis_documentos['Certificado Médico / Justificación de Falta'])) {
                        foreach ($mis_documentos['Certificado Médico / Justificación de Falta'] as $doc) {
                            if (($doc['estado'] ?? 'pendiente') === 'aprobado') {
                                $cant_justificadas++;
                            }
                        }
                    }
                    ?>
                    
                    <div style="display: flex; gap: 20px; justify-content: center; margin-top: 15px;">
                        <div style="text-align: center;">
                            <h2 style="color: var(--naranja); font-size: 3rem; margin: 0;"><?php echo count($mis_faltas); ?></h2>
                            <p style="color: #888; font-weight: bold; margin: 5px 0 20px 0;">Faltas Injustificadas</p>
                        </div>
                        <div style="border-left: 2px solid #eee; padding-left: 20px; text-align: center;">
                            <h2 style="color: var(--verde); font-size: 3rem; margin: 0;"><?php echo $cant_justificadas; ?></h2>
                            <p style="color: #888; font-weight: bold; margin: 5px 0 20px 0;">Faltas Justificadas</p>
                        </div>
                    </div>

                    <?php if (empty($mis_faltas)): ?>
                        <div style="background: #e6f6ec; padding: 20px; border-radius: 8px; text-align: center; color: var(--verde); font-weight: bold;">
                            ✅ ¡Excelente! Tenés asistencia perfecta (sin contar tus faltas justificadas por salud).
                        </div>
                    <?php else: ?>
                        <table class="tabla-datos">
                            <thead><tr><th>Fecha de Ausencia</th><th>Curso Inscripto</th></tr></thead>
                            <tbody>
                                <?php foreach (array_reverse($mis_faltas) as $falta): 
                                    $nombre_c = str_replace(['_anio', '_grado', '_'], ['° Año', '° Grado', ' '], $falta['curso']);
                                ?>
                                <tr>
                                    <td><strong style="color: var(--naranja);">📅 <?php echo $falta['fecha']; ?></strong></td>
                                    <td><?php echo $nombre_c; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($es_preceptor || $es_maestro_primaria): ?>
        <section id="vista-asistencia-preceptor" class="vista-panel <?php echo $es_preceptor ? 'vista-activa' : ''; ?>">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Toma de Asistencia (<?php echo date('d/m/Y'); ?>)</h1>
                    <p style="color: #666;">Seleccioná el curso y marcá únicamente a los alumnos ausentes.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php 
                $hay_cursos = false;
                
                // MAGIA ANTI-DUPLICADOS: Array para recordar qué cursos ya dibujamos
                $cursos_ya_mostrados = [];
                
                // El preceptor o maestro solo recorre SUS cursos asignados
                foreach ($mis_cursos as $mi_curso): 
                    $clave_curso = $mi_curso['curso'] . "_" . $mi_curso['division'];
                    
                    // Si ya mostramos la tarjeta de asistencia para este curso, saltamos al siguiente
                    if (in_array($clave_curso, $cursos_ya_mostrados)) {
                        continue;
                    }
                    $cursos_ya_mostrados[] = $clave_curso; // Lo anotamos como mostrado
                    
                    $lista_alumnos = isset($alumnos_por_curso[$clave_curso]) ? $alumnos_por_curso[$clave_curso] : [];
                    
                    if (empty($lista_alumnos)) continue;
                    
                    $hay_cursos = true;
                    $nombre_c = str_replace(['_anio', '_grado', '_'], ['° Año', '° Grado', ' '], $clave_curso);
                    $fecha_hoy = date('d-m-Y');
                    $ya_tomada = isset($todas_asistencias[$clave_curso][$fecha_hoy]);
                ?>
                    <details class="acordeon-materia" style="grid-column: 1 / -1; border-left-color: <?php echo $ya_tomada ? 'var(--verde)' : 'var(--celeste)'; ?>;">
                        <summary>
                            <strong>🏫 <?php echo $nombre_c; ?></strong>
                            <?php if ($ya_tomada): ?>
                                <span class="badge badge-aprobado">✅ Completada hoy</span>
                            <?php else: ?>
                                <span class="badge badge-pendiente" style="background: #e1f5fe; color: var(--azul-primario);">Pendiente</span>
                            <?php endif; ?>
                        </summary>
                        <div class="acordeon-contenido">
                            <?php if ($ya_tomada): ?>
                                <p style="color: var(--verde); font-weight: bold; text-align: center; padding: 20px;">
                                    ¡La asistencia de este curso ya fue registrada en el día de la fecha!
                                </p>
                            <?php else: ?>
                                <form action="procesos/procesar_asistencia.php" method="POST">
                                    <input type="hidden" name="curso" value="<?php echo $clave_curso; ?>">
                                    <input type="hidden" name="fecha" value="<?php echo $fecha_hoy; ?>">
                                    
                                    <table class="tabla-datos" style="margin-bottom: 20px;">
                                        <thead>
                                            <tr>
                                                <th style="width: 70%;">Alumno</th>
                                                <th style="text-align: center;">Estado / Asistencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lista_alumnos as $al): 
                                                $al_limpio = trim($al);
                                                $tiene_certificado_hoy = false;
                                                
                                                if (isset($todos_los_documentos[$al_limpio]['Certificado Médico / Justificación de Falta'])) {
                                                    foreach ($todos_los_documentos[$al_limpio]['Certificado Médico / Justificación de Falta'] as $cert) {
                                                        $fecha_cert = explode(' ', $cert['fecha'])[0]; 
                                                        $estado_cert = $cert['estado'] ?? 'pendiente';
                                                        
                                                        // MAGIA: Solo bloquea la falta si el estado es 'aprobado'
                                                        if ($fecha_cert === $fecha_hoy && $estado_cert === 'aprobado') {
                                                            $tiene_certificado_hoy = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <tr style="<?php echo $tiene_certificado_hoy ? 'background-color: #f0fdf4;' : ''; ?>">
                                                <td style="font-weight: 500; <?php echo $tiene_certificado_hoy ? 'color: #155724;' : ''; ?>">
                                                    👤 <?php echo htmlspecialchars($al); ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <?php if ($tiene_certificado_hoy): ?>
                                                        <span class="badge badge-aprobado" style="font-size: 0.8rem; border: 1px solid #c3e6cb;">
                                                            🩺 Falta Justificada
                                                        </span>
                                                    <?php else: ?>
                                                        <label style="cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                            <input type="checkbox" name="ausentes[]" value="<?php echo htmlspecialchars($al_limpio); ?>" style="transform: scale(1.5); cursor: pointer; margin: 0;">
                                                            <span style="font-size: 0.85rem; color: #666;">Ausente</span>
                                                        </label>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <div style="display: flex; justify-content: flex-end;">
                                        <button type="submit" class="btn-nuevo" style="background-color: var(--celeste);">Guardar Asistencia Diaria</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>

                <?php if (!$hay_cursos): ?>
                    <p style="text-align: center; color: #888; grid-column: 1 / -1;">No tenés cursos asignados a tu cargo o no hay alumnos matriculados en ellos.</p>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($es_preceptor): ?>
        <section id="vista-documentos-preceptor" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Revisión de Legajos Institucionales</h1>
                    <p style="color: #666;">Panel de certificados y permisos de retiro de los alumnos a tu cargo.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="grid-column: 1 / -1; border-left-color: var(--celeste);">
                    <input type="text" id="buscador-legajos" placeholder="🔍 Buscar por nombre del alumno..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; font-family: inherit;">
                    
                    <?php 
                    // 1. Recopilamos todos los alumnos únicos de los cursos asignados al preceptor
                    $mis_alumnos_unicos = [];
                    foreach ($mis_cursos as $curso) {
                        $clave_c = $curso['curso'] . "_" . $curso['division'];
                        if (isset($alumnos_por_curso[$clave_c])) {
                            foreach ($alumnos_por_curso[$clave_c] as $alum) {
                                $alum_limpio = trim($alum);
                                if (!in_array($alum_limpio, $mis_alumnos_unicos)) {
                                    $mis_alumnos_unicos[] = $alum_limpio;
                                }
                            }
                        }
                    }

                    // 2. Filtramos los documentos de la escuela para mostrar SOLO los de estos alumnos
                    $documentos_filtrados = [];
                    foreach ($mis_alumnos_unicos as $alum) {
                        if (isset($todos_los_documentos[$alum])) {
                            $documentos_filtrados[$alum] = $todos_los_documentos[$alum];
                        }
                    }
                    ?>

                    <?php if (empty($documentos_filtrados)): ?>
                        <p style="color: #888; text-align: center;">Ninguno de tus alumnos asignados ha subido documentación médica aún.</p>
                    <?php else: ?>
                        <div id="contenedor-legajos" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($documentos_filtrados as $alum_nombre => $docs): ?>
                                <div class="tarjeta-legajo" style="padding: 15px; border: 1px solid #eee; border-radius: 8px; background: #fdfdfd; border-left: 4px solid var(--celeste);">
                                    <h4 style="margin: 0 0 10px 0; color: var(--azul-primario);">👤 <?php echo htmlspecialchars($alum_nombre); ?></h4>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <?php 
                                        $hay_archivos = false;
                                        foreach ($docs as $tipo_doc => $datos) {
                                            if (is_array($datos) && !isset($datos['archivo'])) {
                                                // Listas infinitas (Permisos / Certificados Médicos)
                                                foreach ($datos as $idx => $infinito) {
                                                    if (file_exists($infinito['archivo'])) {
                                                        $hay_archivos = true;
                                                        $color = ($tipo_doc === 'Permiso de Retiro') ? 'var(--celeste)' : 'var(--naranja)';
                                                        $estado_actual = $infinito['estado'] ?? 'pendiente';
                                                        
                                                        echo '<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px; background: white; padding: 8px; border-radius: 6px; border: 1px solid #eee;">';
                                                        echo '<a href="'.htmlspecialchars($infinito['archivo']).'" target="_blank" class="btn-accion" style="text-decoration: none; border-color: '.$color.'; color: '.$color.'; font-size: 0.85rem;">📎 '.$tipo_doc.' ('.explode(' ', $infinito['fecha'])[0].')</a>';
                                                        
                                                        // Sistema interactivo de Aprobación
                                                        if ($estado_actual === 'pendiente') {
                                                            echo '<form action="procesos/procesar_estado_certificado.php" method="POST" style="margin: 0; display: flex; gap: 5px;">';
                                                            echo '<input type="hidden" name="alumno" value="'.htmlspecialchars($alum_nombre).'">';
                                                            echo '<input type="hidden" name="tipo_doc" value="'.htmlspecialchars($tipo_doc).'">';
                                                            echo '<input type="hidden" name="indice" value="'.$idx.'">';
                                                            echo '<button type="submit" name="accion" value="aprobar" class="btn-accion" style="border-color: var(--verde); color: var(--verde); padding: 3px 8px; font-size: 0.8rem;" title="Aprobar Certificado">✅</button>';
                                                            echo '<button type="submit" name="accion" value="rechazar" class="btn-accion" style="border-color: var(--naranja); color: var(--naranja); padding: 3px 8px; font-size: 0.8rem;" title="Rechazar Certificado">❌</button>';
                                                            echo '</form>';
                                                        } elseif ($estado_actual === 'aprobado') {
                                                            echo '<span class="badge badge-aprobado" style="font-size: 0.75rem;">Aprobado</span>';
                                                        } else {
                                                            echo '<span class="badge" style="background: #fce8e6; color: var(--naranja); font-size: 0.75rem;">Rechazado</span>';
                                                        }
                                                        echo '</div>';
                                                    }
                                                }
                                            } elseif (isset($datos['archivo'])) {
                                                // Documentos únicos (DNI, Partida, Apto)
                                                if (file_exists($datos['archivo'])) {
                                                    $hay_archivos = true;
                                                    echo '<a href="'.htmlspecialchars($datos['archivo']).'" target="_blank" class="btn-accion" style="text-decoration: none; border-color: var(--verde); color: var(--verde);">✅ '.$tipo_doc.'</a>';
                                                }
                                            }
                                        }
                                        if (!$hay_archivos) echo '<span style="color: #999; font-size: 0.85rem;">No hay archivos físicos disponibles en el servidor.</span>';
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                // Buscador en vivo para legajos del preceptor
                document.getElementById('buscador-legajos')?.addEventListener('input', function(e) {
                    const texto = e.target.value.toLowerCase();
                    document.querySelectorAll('.tarjeta-legajo').forEach(tarjeta => {
                        const nombre = tarjeta.querySelector('h4').textContent.toLowerCase();
                        tarjeta.style.display = nombre.includes(texto) ? 'block' : 'none';
                    });
                });
            </script>
        </section>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <section id="vista-entrevistas" class="vista-panel">
            <header class="top-bar" style="flex-direction: column; align-items: stretch; gap: 15px;">
                
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'entrevista_agendada'): ?>
                    <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✅ Entrevista agendada correctamente. <br>
                        <span style="font-size: 0.85rem; color: #155724; font-weight: normal;">📧 (Simulación de Sistema) Se ha enviado un correo automático al tutor con los detalles de la cita.</span>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'entrevista_modificada'): ?>
                    <div style="background: #e1f5fe; color: var(--azul-primario); padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 10px;">
                        ✏️ Fecha y hora de la entrevista actualizadas correctamente.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'entrevista_eliminada'): ?>
                    <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold; margin-bottom: 10px;">
                        🗑️ La solicitud de entrevista fue cancelada y eliminada.
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-welcome">
                        <h1>Entrevistas de Admisión</h1>
                        <p style="color: #666;">Gestión de solicitudes de ingreso y coordinación de reuniones con padres.</p>
                    </div>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php if (empty($todas_las_entrevistas)): ?>
                    <div class="stat-card" style="grid-column: 1 / -1;">
                        <p style="text-align: center; color: #888;">No hay solicitudes de entrevistas pendientes.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($todas_las_entrevistas) as $ent): 
                        $es_pendiente = ($ent['estado'] === 'pendiente');
                        $color_borde = $es_pendiente ? 'var(--naranja)' : 'var(--verde)';
                    ?>
                        <div class="stat-card" style="border-left-color: <?php echo $color_borde; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h3 style="margin: 0;">Familia <?php echo htmlspecialchars(explode(' ', $ent['tutor'])[1] ?? $ent['tutor']); ?></h3>
                                <?php if ($es_pendiente): ?>
                                    <span class="badge badge-pendiente">Pendiente</span>
                                <?php else: ?>
                                    <span class="badge badge-aprobado">Agendada</span>
                                <?php endif; ?>
                            </div>
                            
                            <p style="color: #666; font-size: 0.9rem; margin: 5px 0;"><strong>🧒 Aspirante:</strong> <?php echo htmlspecialchars($ent['alumno']); ?> (<?php echo htmlspecialchars($ent['nivel']); ?>)</p>
                            <p style="color: #666; font-size: 0.85rem; margin: 5px 0;"><strong>📄 DNI:</strong> <?php echo htmlspecialchars($ent['dni_alumno'] ?? 'No especificado'); ?> | <strong>🎂 Nacimiento:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($ent['fecha_nacimiento_alumno'] ?? ''))); ?></p>
                            <p style="color: #666; font-size: 0.9rem; margin: 5px 0;"><strong>📞 Contacto Tutor:</strong> <?php echo htmlspecialchars($ent['telefono']); ?> | ✉️ <?php echo htmlspecialchars($ent['email']); ?></p>
                            
                            <hr style="border: 0; border-top: 1px dashed #ddd; margin: 15px 0;">
                            
                            <?php if ($es_pendiente): ?>
                                <p style="font-size: 0.85rem; color: var(--azul-primario); font-weight: bold; margin-bottom: 10px;">🕒 Preferencia del Tutor: <?php echo htmlspecialchars($ent['disponibilidad']); ?></p>
                                
                                <form action="procesos/gestionar_entrevista.php" method="POST" onsubmit="return validarFechaHoraEntrevista(this);" style="background: #fafafa; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
                                    <input type="hidden" name="id_entrevista" value="<?php echo htmlspecialchars($ent['id']); ?>">
                                    <label style="font-size: 0.85rem; font-weight: bold; color: #555;">Fijar Fecha y Hora:</label>
                                    <div style="display: flex; gap: 10px; margin-top: 5px; margin-bottom: 15px;">
                                        <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                        <input type="time" name="hora" required style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                    </div>
                                    <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--verde); font-size: 0.9rem;">✅ Agendar y Notificar</button>
                                </form>
                            <?php else: ?>
                                <div style="background: #e6f6ec; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb; text-align: center;">
                                    <p style="margin: 0; color: #155724; font-weight: bold; font-size: 0.9rem;">Reunión Coordinada para el:</p>
                                    <h2 style="margin: 5px 0; color: var(--verde);"><?php echo date('d/m/Y', strtotime($ent['fecha_agendada'])); ?> a las <?php echo htmlspecialchars($ent['hora_agendada']); ?>hs</h2>
                                    
                                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                                        <button class="btn-accion btn-editar-entrevista" 
                                            data-id="<?php echo htmlspecialchars($ent['id']); ?>" 
                                            data-fecha="<?php echo htmlspecialchars($ent['fecha_agendada']); ?>" 
                                            data-hora="<?php echo htmlspecialchars($ent['hora_agendada']); ?>"
                                            style="border-color: var(--celeste); color: var(--celeste);">✏️ Reprogramar</button>
                                            
                                        <form action="procesos/gestionar_entrevista.php" method="POST" onsubmit="return confirm('¿Estás seguro de cancelar esta entrevista?');" style="margin: 0;">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_entrevista" value="<?php echo htmlspecialchars($ent['id']); ?>">
                                            <button type="submit" class="btn-accion" style="border-color: var(--naranja); color: var(--naranja);">❌ Cancelar Cita</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <p style="font-size: 0.75rem; color: #aaa; text-align: right; margin-top: 10px;">Recibido el: <?php echo $ent['fecha_solicitud']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($es_admin): ?>
        <section id="vista-rrhh" class="vista-panel">
            <header class="top-bar" style="flex-direction: column; align-items: stretch; gap: 15px;">
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'postulacion_agendada'): ?>
                    <div style="background: #e6f6ec; color: var(--verde); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✅ Convocatoria agendada y correo real enviado al postulante.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'postulacion_modificada'): ?>
                    <div style="background: #e1f5fe; color: var(--azul-primario); padding: 12px; border-radius: 8px; font-weight: bold;">
                        ✏️ Entrevista laboral reprogramada y notificada con éxito.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['msj']) && $_GET['msj'] === 'postulacion_eliminada'): ?>
                    <div style="background: #fff5f2; color: var(--naranja); padding: 12px; border-radius: 8px; font-weight: bold;">
                        🗑️ Currículum y postulación eliminados del servidor.
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="user-welcome">
                        <h1>Recursos Humanos (Bolsa de Trabajo)</h1>
                        <p style="color: #666;">Revisión de Currículums Vitae recibidos y citación a entrevistas de trabajo.</p>
                    </div>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                        <div class="user-avatar"><?php echo $iniciales; ?></div>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid">
                <?php if (empty($todas_las_postulaciones)): ?>
                    <div class="stat-card" style="grid-column: 1 / -1;">
                        <p style="text-align: center; color: #888;">No se han recibido currículums postulantes por el momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($todas_las_postulaciones) as $p): 
                        $es_pendiente = ($p['estado'] === 'pendiente');
                        $color_b = $es_pendiente ? 'var(--naranja)' : 'var(--verde)';
                    ?>
                        <div class="stat-card" style="border-left-color: <?php echo $color_b; ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h3 style="margin: 0; font-size: 1.15rem;">👤 <?php echo htmlspecialchars($p['nombre']); ?></h3>
                                <span class="badge <?php echo $es_pendiente ? 'badge-pendiente' : 'badge-aprobado'; ?>">
                                    <?php echo ucfirst($p['estado']); ?>
                                </span>
                            </div>
                            
                            <p style="color: #444; font-size: 0.9rem; margin: 5px 0;"><strong>🎯 Área de interés:</strong> <span style="color: var(--azul-primario); font-weight: bold;"><?php echo htmlspecialchars($p['area']); ?></span></p>
                            <p style="color: #666; font-size: 0.9rem; margin: 5px 0;"><strong>📞 Contacto:</strong> <?php echo htmlspecialchars($p['telefono']); ?> | ✉️ <?php echo htmlspecialchars($p['email']); ?></p>
                            
                            <div style="margin: 15px 0;">
                                <a href="<?php echo htmlspecialchars($p['cv']); ?>" target="_blank" class="btn-accion" style="display: block; text-align: center; background: #fafafa; border-color: var(--azul-primario); color: var(--azul-primario); font-weight: bold; text-decoration: none; padding: 8px;">
                                    📎 Abrir Currículum Vitae (PDF)
                                </a>
                            </div>
                            
                            <hr style="border: 0; border-top: 1px dashed #ddd; margin: 15px 0;">
                            
                            <?php if ($es_pendiente): ?>
                                <form action="procesos/gestionar_postulacion.php" method="POST" onsubmit="return validarFechaHoraEntrevista(this);" style="background: #fafafa; padding: 12px; border-radius: 8px; border: 1px solid #eee;">
                                    <input type="hidden" name="id_postulacion" value="<?php echo htmlspecialchars($p['id']); ?>">
                                    <label style="font-size: 0.8rem; font-weight: bold; color: #555;">Agendar Entrevista Laboral:</label>
                                    <div style="display: flex; gap: 8px; margin-top: 5px; margin-bottom: 10px;">
                                        <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" required style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.85rem;">
                                        <input type="time" name="hora" required style="flex: 1; padding: 6px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-size: 0.85rem;">
                                    </div>
                                    <button type="submit" class="btn-nuevo" style="width: 100%; background-color: var(--azul-primario); font-size: 0.85rem; padding: 8px;">Convocar a Reunión</button>
                                </form>
                            <?php else: ?>
                                <div style="background: #e6f6ec; padding: 12px; border-radius: 8px; border: 1px solid #c3e6cb; text-align: center;">
                                    <p style="margin: 0; color: #155724; font-weight: bold; font-size: 0.85rem;">Cita laboral pactada:</p>
                                    <h3 style="margin: 5px 0; color: var(--verde); font-size: 1.2rem;"><?php echo date('d/m/Y', strtotime($p['fecha_entrevista'])); ?> - <?php echo htmlspecialchars($p['hora_entrevista']); ?>hs</h3>
                                    
                                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                                        <button class="btn-accion btn-editar-postulacion" 
                                            data-id="<?php echo htmlspecialchars($p['id']); ?>" 
                                            data-fecha="<?php echo htmlspecialchars($p['fecha_entrevista']); ?>" 
                                            data-hora="<?php echo htmlspecialchars($p['hora_entrevista']); ?>"
                                            style="border-color: var(--celeste); color: var(--celeste); font-size: 0.8rem; padding: 4px 8px;">✏️ Cambiar</button>
                                            
                                        <form action="procesos/gestionar_postulacion.php" method="POST" onsubmit="return confirm('¿Seguro querés descartar esta postulación laboral?');" style="margin: 0;">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id_postulacion" value="<?php echo htmlspecialchars($p['id']); ?>">
                                            <button type="submit" class="btn-accion" style="border-color: var(--naranja); color: var(--naranja); font-size: 0.8rem; padding: 4px 8px;">❌ Descartar</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <p style="font-size: 0.7rem; color: #aaa; text-align: right; margin-top: 8px; margin-bottom: 0;">Postulado el: <?php echo $p['fecha_solicitud']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($es_admin): ?>
        <section id="vista-correos" class="vista-panel">
            <header class="top-bar">
                <div class="user-welcome">
                    <h1>Bandeja de Salida (Simulador)</h1>
                    <p style="color: #666;">Auditoría de correos automáticos disparados por el sistema.</p>
                </div>
                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($etiqueta_perfil); ?></span>
                    <div class="user-avatar"><?php echo $iniciales; ?></div>
                </div>
            </header>

            <div class="dashboard-grid" style="grid-template-columns: 1fr;">
                <div class="stat-card" style="border-left-color: var(--celeste);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">Correos Enviados (Outbox)</h3>
                        <span class="badge" style="background: #e1f5fe; color: var(--azul-primario);"><?php echo count($todos_los_correos); ?> Procesados</span>
                    </div>

                    <?php if (empty($todos_los_correos)): ?>
                        <p style="color: #999; text-align: center; padding: 20px;">No se ha registrado el envío de ningún correo todavía.</p>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="tabla-datos">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Fecha/Hora</th>
                                        <th style="width: 25%;">Destinatario</th>
                                        <th style="width: 60%;">Asunto y Contenido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($todos_los_correos) as $mail): ?>
                                        <tr>
                                            <td style="color: #888; font-size: 0.85rem;"><strong><?php echo $mail['fecha_envio']; ?></strong></td>
                                            <td><span style="background: #f4f7f6; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; border: 1px solid #ddd;">✉️ <?php echo htmlspecialchars($mail['destinatario']); ?></span></td>
                                            <td>
                                                <strong style="color: var(--azul-primario); display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($mail['asunto']); ?></strong>
                                                <p style="margin: 0; font-size: 0.85rem; color: #555; white-space: pre-wrap; background: #fafafa; padding: 10px; border-left: 3px solid #ddd; border-radius: 4px;"><?php echo htmlspecialchars($mail['cuerpo']); ?></p>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </main>
    

    <div class="modal-overlay" id="modal-crear-actividad">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Crear Nueva Actividad</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-crear-actividad').classList.remove('modal-activo');">×</button>
            </div>
            <form action="procesos/procesar_actividad.php" method="POST" enctype="multipart/form-data" class="form-dashboard">
                <input type="hidden" name="accion" value="crear">
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Seleccionar Curso y Materia destino</label>
                        <select name="curso_destino" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="" disabled selected>Elegí de tus materias asignadas...</option>
                            <?php foreach ($mis_cursos as $c): 
                                $etiqueta = str_replace(['_anio', '_grado'], ['° Año', '° Grado'], $c['curso']) . ' "' . $c['division'] . '" - ' . $c['materia'];
                                $valor = $c['curso'] . '|' . $c['division'] . '|' . $c['materia'];
                            ?>
                                <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($etiqueta); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Título de la Tarea</label>
                        <input type="text" name="titulo" required>
                    </div>
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Instrucciones</label>
                        <textarea name="descripcion" rows="3" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                    <div class="input-group">
                        <label>Fecha y Hora Límite</label>
                        <input type="text" name="fecha_limite" class="selector-fecha" required>
                    </div>
                    <div class="input-group">
                        <label>Archivo Adjunto (Opcional)</label>
                        <input type="file" name="archivo_adjunto" accept=".pdf, .jpg, .jpeg, .png, .doc, .docx, .xls, .xlsx" style="padding: 8px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-guardar" style="background-color: var(--rosa);">Publicar Tarea</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-entrega-alumno">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Entregar Actividad</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-entrega-alumno').classList.remove('modal-activo');">×</button>
            </div>
            <form action="procesos/procesar_entrega.php" method="POST" enctype="multipart/form-data" class="form-dashboard">
                <input type="hidden" name="id_actividad" id="input-entrega-id">
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Respuesta escrita (Opcional)</label>
                        <textarea name="texto_respuesta" rows="3" placeholder="Profe, le dejo mi trabajo acá..." style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Subir Archivo de Trabajo</label>
                        <input type="file" name="archivo_entrega" accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" style="padding: 8px;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-guardar" style="background-color: var(--verde);">Enviar Trabajo</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-corregir-profe">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Evaluar Entrega</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-corregir-profe').classList.remove('modal-activo');">×</button>
            </div>
            <form action="procesos/procesar_devolucion.php" method="POST" class="form-dashboard">
                <input type="hidden" name="id_actividad" id="input-correccion-id">
                <input type="hidden" name="alumno" id="input-correccion-alumno">
                <input type="hidden" name="materia" id="input-correccion-materia">
                
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Comentario / Devolución</label>
                        <textarea name="comentario" rows="3" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"></textarea>
                    </div>
                    <div class="input-group">
                        <label>Nota Numérica (Opcional)</label>
                        <input type="number" step="0.1" name="nota" placeholder="Ej: 8.5">
                    </div>
                    <div class="input-group" style="justify-content: center; padding-top: 15px;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--rosa);">
                            <input type="checkbox" name="guardar_boletin" value="si" style="width: 20px; height: 20px; margin: 0;">
                            <strong>Cargar nota al boletín oficial</strong>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-guardar" style="background-color: var(--rosa);">Enviar Devolución</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-overlay" id="modal-notas">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Cargar Nueva Calificación</h2>
                <button class="btn-cerrar-modal" id="btn-cerrar-notas">×</button>
            </div>
            <form action="procesos/guardar_nota.php" method="POST" class="form-dashboard">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Alumno a evaluar</label>
                        <select name="alumno" id="modal-alumno-select" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                            <option value="" disabled selected>Seleccioná un alumno...</option>
                        </select>
                    </div>
                    
                    <div class="input-group" id="contenedor-materia-modal">
                        <label>Materia</label>
                        <input type="text" name="materia" readonly style="background-color: #eee; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Nombre del Examen / Instancia</label>
                        <input type="text" name="nombre_examen" placeholder="Ej: Primer Trimestre, Trabajo Práctico N°1..." required>
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Calificación Numérica</label>
                        <input type="number" step="0.1" name="nota" placeholder="Ej: 8" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" id="btn-cancelar-notas">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--azul-primario);">Guardar Examen</button>
                </div>
            </form>
        </div>
    </div> <?php if ($es_admin): ?> 
        <div class="modal-overlay" id="modal-usuarios">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Registrar Nuevo Perfil Institucional</h2>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-usuario">×</button>
            </div>
            <form action="procesos/procesar_usuario.php" method="POST" class="form-dashboard">
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Nombre y Apellido Completo</label>
                        <input type="text" name="nombre" placeholder="Ej: Roberto Carlos Gómez" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Tipo de Perfil (Rol)</label>
                        <select name="rol" id="select-rol" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                            <option value="" disabled selected>Seleccioná una opción...</option>
                            <option value="alumno">🎒 Alumno</option>
                            <option value="tutor">👨‍👩‍👧 Padre / Tutor</option>
                            <option value="profesor">📚 Personal Docente</option>
                            <option value="preceptor">📋 Preceptor / Auxiliar</option>
                            <option value="admin">⚙️ Administrador</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Nombre de Usuario (Login)</label>
                        <input type="text" name="usuario" placeholder="Ej: r_gomez" required>
                    </div>

                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Contraseña de Acceso Inicial</label>
                        <input type="password" name="password" placeholder="Definir contraseña provisoria" required>
                    </div>

                    <div id="campos-alumno" style="grid-column: 1 / -1; display: none; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 15px;">
                            <strong style="color: var(--verde); font-size: 0.9rem;">Datos Personales y Curso (Solo Perfil Alumno)</strong>
                        </div>
                        
                        <div class="input-group">
                            <label>DNI del Alumno</label>
                            <input type="text" name="dni" placeholder="Ej: 45123456" pattern="[0-9]*">
                        </div>
                        <div class="input-group">
                            <label>Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                        </div>
                        
                        <div class="input-group">
                            <label>Año / Grado</label>
                            <select name="curso_asig_al" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="5_anio">5° Año (Secundaria)</option>
                                <option value="4_anio">4° Año (Secundaria)</option>
                                <option value="3_anio">3° Año (Secundaria)</option>
                                <option value="2_anio">2° Año (Secundaria)</option>
                                <option value="1_anio">1° Año (Secundaria)</option>
                                <option value="7_grado">7° Grado (Primaria)</option>
                                <option value="6_grado">6° Grado (Primaria)</option>
                                <option value="5_grado">5° Grado (Primaria)</option>
                                <option value="4_grado">4° Grado (Primaria)</option>
                                <option value="3_grado">3° Grado (Primaria)</option>
                                <option value="2_grado">2° Grado (Primaria)</option>
                                <option value="1_grado">1° Grado (Primaria)</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>División</label>
                            <select name="division_asig_al" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>

                        <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 15px;">
                            <strong style="color: var(--naranja); font-size: 0.9rem;">Información del Tutor / Contacto de Emergencia</strong>
                        </div>
                        <div class="input-group" style="grid-column: 1 / -1;">
                            <label>Nombre Completo del Tutor/a</label>
                            <input type="text" name="tutor_nombre" placeholder="Ej: María González">
                        </div>
                        <div class="input-group">
                            <label>Teléfono / WhatsApp</label>
                            <input type="tel" name="tutor_telefono" placeholder="+54 362...">
                        </div>
                        <div class="input-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="tutor_email" placeholder="correo@ejemplo.com">
                        </div>
                    </div>

                    <div id="campos-tutor" style="grid-column: 1 / -1; display: none; grid-template-columns: 1fr; gap: 15px;">
                        <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 15px;">
                            <strong style="color: var(--naranja); font-size: 0.9rem;">Vincular con Alumno/s (Solo para Tutores)</strong>
                        </div>
                        
                        <div class="input-group">
                            <label>Buscar y Seleccionar Hijo/s</label>
                            <input type="text" id="buscador-hijos" placeholder="🔍 Escribí el nombre para filtrar..." style="padding: 10px; border: 1px solid #ddd; border-radius: 6px 6px 0 0; margin-bottom: 0; border-bottom: none; outline: none; background: #fff;">
                            
                            <div id="lista-hijos-checkboxes" style="max-height: 180px; overflow-y: auto; border: 1px solid #ddd; border-radius: 0 0 6px 6px; padding: 10px; background: #fafafa;">
                                <?php 
                                $hay_alumnos = false;
                                foreach ($todos_los_usuarios as $u): 
                                ?>
                                    <?php if (($u['rol'] ?? '') === 'alumno'): 
                                        $hay_alumnos = true;
                                    ?>
                                        <label class="item-hijo" style="display: flex; align-items: center; gap: 10px; padding: 8px; cursor: pointer; border-bottom: 1px solid #eee; transition: 0.2s;">
                                            <input type="checkbox" name="hijos_asignados[]" value="<?php echo htmlspecialchars($u['nombre']); ?>" style="margin: 0; width: 18px; height: 18px; cursor: pointer;">
                                            <span class="nombre-hijo-filtro" style="font-size: 0.95rem; color: #444;">🎒 <?php echo htmlspecialchars($u['nombre']); ?></span>
                                        </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (!$hay_alumnos): ?>
                                    <p style="color: #999; font-size: 0.85rem; text-align: center; margin: 10px 0;">No hay alumnos registrados en el sistema todavía.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="campos-catedra" style="grid-column: 1 / -1; display: none; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="input-group" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px dashed #eee; padding-top: 15px;">
                            <strong style="color: var(--azul-primario); font-size: 0.9rem;">Asignación de Cátedra (Personal Docente)</strong>
                        </div>
                        <div class="input-group">
                            <label>Nivel Educativo</label>
                            <select name="nivel_asig" id="select-nivel-asig" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="secundaria">Secundaria</option>
                                <option value="primaria">Primaria</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Curso / Año</label>
                            <select name="curso_asig" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="5_anio">5° Año (Secundaria)</option>
                                <option value="4_anio">4° Año (Secundaria)</option>
                                <option value="3_anio">3° Año (Secundaria)</option>
                                <option value="2_anio">2° Año (Secundaria)</option>
                                <option value="1_anio">1° Año (Secundaria)</option>
                                <option value="7_grado">7° Grado (Primaria)</option>
                                <option value="6_grado">6° Grado (Primaria)</option>
                                <option value="5_grado">5° Grado (Primaria)</option>
                                <option value="4_grado">4° Grado (Primaria)</option>
                                <option value="3_grado">3° Grado (Primaria)</option>
                                <option value="2_grado">2° Grado (Primaria)</option>
                                <option value="1_grado">1° Grado (Primaria)</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>División</label>
                            <select name="division_asig" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                            </select>
                        </div>
                        <div class="input-group" id="grupo-materia-asig" style="grid-column: 1 / -1;">
                            <label>Materia a dictar</label>
                            <select name="materia_asig" style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="" disabled selected>Seleccioná una materia...</option>
                                <option value="Lengua">Lengua</option>
                                <option value="Matemática">Matemática</option>
                                <option value="Ciencias Naturales">Ciencias Naturales</option>
                                <option value="Ciencias Sociales">Ciencias Sociales</option>
                                <option value="Literatura">Literatura</option>
                                <option value="Física">Física</option>
                                <option value="Historia">Historia</option>
                                <option value="Biología">Biología</option>
                                <option value="Geografía">Geografía</option>
                                <option value="Inglés">Inglés</option>
                                <option value="Educación Física">Educación Física</option>
                                <option value="Educación Tecnológica">Educación Tecnológica</option>
                                <option value="Construcción Ciudadana">Construcción Ciudadana</option>
                                <option value="Contabilidad">Contabilidad</option>
                            </select>
                        </div>
                    </div>

                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" id="btn-cancelar-usuario">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--azul-primario);">Confirmar Registro</button>
                </div>
            </form>
        </div>
    </div> <div class="modal-overlay" id="modal-editar-usuario">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Modificar Credenciales de Acceso</h2>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-editar-usuario">×</button>
            </div>
            <form action="procesos/modificar_usuario.php" method="POST" class="form-dashboard">
                <input type="hidden" name="usuario_original" id="edit-usuario-original">
                
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Nombre Completo del Perfil</label>
                        <input type="text" id="edit-nombre-pantalla" readonly style="background-color: #eee; cursor: not-allowed; font-weight: bold; color: #555;">
                    </div>
                    
                    <div class="input-group">
                        <label>Nombre de Usuario (Login)</label>
                        <input type="text" name="nuevo_usuario" id="edit-usuario-login" required style="font-family: monospace;">
                    </div>

                    <div class="input-group">
                        <label>Contraseña de Acceso</label>
                        <input type="text" name="nueva_password" id="edit-usuario-password" required style="font-family: monospace;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" id="btn-cancelar-editar-usuario">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--violeta);">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?> <div class="modal-overlay" id="modal-cambiar-clave">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Actualizar Mi Contraseña</h2>
                <button type="button" class="btn-cerrar-modal" id="btn-cerrar-cambiar-clave">×</button>
            </div>
            <form action="procesos/cambiar_mi_clave.php" method="POST" class="form-dashboard" onsubmit="return validarContrasenasNueva()">
                <div class="form-grid">
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Contraseña Actual</label>
                        <input type="password" name="password_actual" required placeholder="Escribí tu clave actual">
                    </div>
                    
                    <div class="input-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="password_nueva" id="nueva-clave-input" required placeholder="Mínimo 4 caracteres">
                    </div>

                    <div class="input-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" id="confirmar-clave-input" required placeholder="Repetí la nueva clave">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" id="btn-cancelar-cambiar-clave">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--azul-primario);">Actualizar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal-overlay" id="modal-ficha-alumno">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Ficha Estudiantil</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-ficha-alumno').classList.remove('modal-activo');">×</button>
            </div>
            <div style="padding: 25px;">
                <h3 id="ficha-nombre" style="color: var(--azul-primario); margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;">Nombre</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div>
                        <p style="color: #777; font-size: 0.85rem; margin: 0 0 5px 0;">DNI del Alumno:</p>
                        <p style="margin: 0; font-weight: bold; font-size: 1.1rem;" id="ficha-dni"></p>
                    </div>
                    <div>
                        <p style="color: #777; font-size: 0.85rem; margin: 0 0 5px 0;">Fecha de Nacimiento:</p>
                        <p style="margin: 0; font-weight: bold; font-size: 1.1rem;" id="ficha-fnac"></p>
                    </div>
                </div>

                <div style="background: #fafafa; border: 1px solid #eee; border-radius: 8px; padding: 15px; margin-top: 25px;">
                    <h4 style="color: var(--naranja); margin: 0 0 15px 0;">📞 Contacto del Tutor / Emergencia</h4>
                    <p style="margin: 5px 0;"><strong>Nombre:</strong> <span id="ficha-tnombre"></span></p>
                    <p style="margin: 5px 0;"><strong>Teléfono:</strong> <span id="ficha-ttel"></span></p>
                    <p style="margin: 5px 0;"><strong>Email:</strong> <span id="ficha-temail"></span></p>
                </div>
            </div>
            <div class="modal-footer" style="background: #f4f7f6; padding: 15px 25px;">
                <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-ficha-alumno').classList.remove('modal-activo');">Cerrar Ficha</button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" id="modal-editar-entrevista">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Reprogramar Entrevista</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-editar-entrevista').classList.remove('modal-activo');">×</button>
            </div>
            <form action="procesos/gestionar_entrevista.php" method="POST" class="form-dashboard" onsubmit="return validarFechaHoraEntrevista(this);">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_entrevista" id="edit-entrevista-id">
                
                <div class="form-grid">
                    <div class="input-group">
                        <label>Nueva Fecha</label>
                        <input type="date" name="fecha" id="edit-entrevista-fecha" min="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                    </div>
                    <div class="input-group">
                        <label>Nueva Hora</label>
                        <input type="time" name="hora" id="edit-entrevista-hora" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-editar-entrevista').classList.remove('modal-activo');">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--celeste);">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="modal-editar-postulacion">
        <div class="modal-box">
            <div class="modal-header">
                <h2>Reprogramar Turno de Entrevista Laboral</h2>
                <button type="button" class="btn-cerrar-modal" onclick="document.getElementById('modal-editar-postulacion').classList.remove('modal-activo');">×</button>
            </div>
            <form action="procesos/gestionar_postulacion.php" method="POST" class="form-dashboard" onsubmit="return validarFechaHoraEntrevista(this);">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_postulacion" id="edit-postulacion-id">
                
                <div class="form-grid">
                    <div class="input-group">
                        <label>Nueva Fecha</label>
                        <input type="date" name="fecha" id="edit-postulacion-fecha" min="<?php echo date('Y-m-d'); ?>" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                    </div>
                    <div class="input-group">
                        <label>Nueva Hora</label>
                        <input type="time" name="hora" id="edit-postulacion-hora" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancelar" onclick="document.getElementById('modal-editar-postulacion').classList.remove('modal-activo');">Cancelar</button>
                    <button type="submit" class="btn-guardar" style="background-color: var(--celeste);">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
       //  MOTOR DEL MENÚ LATERAL (CON INTELIGENCIA MÓVIL) 
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const logoText = document.getElementById('logo-text');
            
            // ¿Estamos en un celular? (Pantalla menor a 768px)
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-active');
            } 
            // ¿Estamos en una computadora?
            else {
                sidebar.classList.toggle('collapsed');
                if(sidebar && sidebar.classList.contains('collapsed')) {
                    if(logoText) logoText.style.visibility = 'hidden';
                } else {
                    if(logoText) logoText.style.visibility = 'visible';
                }
            }
        }

        // MAGIA EXTRA: Si estamos en el celular y tocamos en la zona gris (fuera del menú), se cierra solo
        document.addEventListener('click', (e) => {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-active')) {
                // Si el clic NO fue adentro del menú lateral y TAMPOCO fue en el botón hamburguesa
                if (!sidebar.contains(e.target) && !e.target.closest('.mobile-top-bar') && !e.target.closest('.menu-toggle')) {
                    sidebar.classList.remove('mobile-active');
                }
            }
        });
        
        // Cierra el menú móvil automáticamente cuando tocás una sección
        document.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('mobile-active');
                }
            });
        });

        //  MOTOR DEL MODAL DE TALLERES (PROTEGIDO) 
        const btnAbrirTaller = document.getElementById('btn-abrir-talleres'); 
        const modalTalleres = document.getElementById('modal-talleres'); 
        const btnCerrarTaller = document.getElementById('btn-cerrar-taller'); 
        const btnCancelarTaller = document.getElementById('btn-cancelar-taller'); 

        // Solo se activa si el botón existe (para el alumno)
        if (btnAbrirTaller) {
            btnAbrirTaller.addEventListener('click', () => {
                modalTalleres.classList.add('modal-activo');
            });
        }

        function cerrarModalTaller() {
            if(modalTalleres) modalTalleres.classList.remove('modal-activo');
        }

        if(btnCerrarTaller) btnCerrarTaller.addEventListener('click', cerrarModalTaller);
        if(btnCancelarTaller) btnCancelarTaller.addEventListener('click', cerrarModalTaller);

        if(modalTalleres) {
            modalTalleres.addEventListener('click', (evento) => {
                if (evento.target === modalTalleres) {
                    cerrarModalTaller();
                }
            });
        }

        //  MOTOR DE NAVEGACIÓN INTERNA 
        const linksMenu = document.querySelectorAll('.nav-item[data-vista]');
        const vistas = document.querySelectorAll('.vista-panel');

        linksMenu.forEach(link => {
            link.addEventListener('click', (evento) => {
                evento.preventDefault(); 
                const idVistaDestino = link.getAttribute('data-vista');
                
                vistas.forEach(vista => vista.classList.remove('vista-activa'));
                linksMenu.forEach(l => l.classList.remove('menu-activo'));

                const vistaDestino = document.getElementById(idVistaDestino);
                if(vistaDestino) {
                    vistaDestino.classList.add('vista-activa');
                    link.classList.add('menu-activo');
                }
            });
        });

        // MOTOR DE RESERVAS 
        if(document.querySelector(".selector-fecha")) {
            flatpickr(".selector-fecha", {
                enableTime: true,
                dateFormat: "d-m-Y H:i",
                minDate: "today",
                time_24hr: true,
                locale: "es"
            });
        }

        //  MOTOR DE ACORDEONES 
        const acordeones = document.querySelectorAll('.acordeon-materia');
        acordeones.forEach((acordeon) => {
            acordeon.addEventListener('toggle', () => {
                if (acordeon.open) {
                    acordeones.forEach((otro) => {
                        if (otro !== acordeon && otro.parentNode === acordeon.parentNode) {
                            otro.removeAttribute('open');
                        }
                    });
                }
            });
        });

//  MOTOR DINÁMICO DEL MODAL DE NOTAS 
        const dbAlumnosCursos = <?php echo json_encode($alumnos_por_curso); ?>;
        // Pasamos la lista de usuarios para cruzar nombres completos con usuarios de login
        const dbTodosUsuarios = <?php echo json_encode($todos_los_usuarios); ?>;
        
        const botonesAbrirNotas = document.querySelectorAll('.btn-abrir-notas');
        const modalNotas = document.getElementById('modal-notas');
        const btnCerrarNotas = document.getElementById('btn-cerrar-notas');
        const btnCancelarNotas = document.getElementById('btn-cancelar-notas');

        if (botonesAbrirNotas.length > 0) {
            botonesAbrirNotas.forEach(btn => {
                btn.addEventListener('click', () => {
                    const cursoClick = btn.getAttribute('data-curso');
                    const materiaClick = btn.getAttribute('data-materia');
                    const nivelClick = btn.getAttribute('data-nivel');
                    
                    const selectAlumnos = document.getElementById('modal-alumno-select');
                    selectAlumnos.innerHTML = '<option value="" disabled selected>Seleccioná un alumno...</option>';
                    
                    if (dbAlumnosCursos[cursoClick] && dbAlumnosCursos[cursoClick].length > 0) {
                        dbAlumnosCursos[cursoClick].forEach(alumnoNombre => {
                            const opt = document.createElement('option');
                            // ENVIAMOS EL NOMBRE COMPLETO DIRECTAMENTE AL PHP
                            opt.value = alumnoNombre.trim(); 
                            opt.textContent = alumnoNombre;
                            selectAlumnos.appendChild(opt);
                        });
                    } else {
                        const opt = document.createElement('option');
                        opt.value = "";
                        opt.textContent = "No hay alumnos inscriptos";
                        opt.disabled = true;
                        selectAlumnos.appendChild(opt);
                    }

                    const contenedorMateria = document.getElementById('contenedor-materia-modal');
                    if (nivelClick === 'secundaria') {
                        contenedorMateria.innerHTML = `
                            <label>Materia</label>
                            <input type="text" name="materia" value="${materiaClick}" readonly style="background-color: #eee; cursor: not-allowed; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; font-weight: bold; color: #555;">
                        `;
                    } else {
                        contenedorMateria.innerHTML = `
                            <label>Materia a evaluar</label>
                            <select name="materia" required style="padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;">
                                <option value="Lengua" ${materiaClick === 'Lengua' ? 'selected' : ''}>Lengua</option>
                                <option value="Matemática" ${materiaClick === 'Matemática' ? 'selected' : ''}>Matemática</option>
                                <option value="Ciencias Naturales" ${materiaClick === 'Ciencias Naturales' ? 'selected' : ''}>Ciencias Naturales</option>
                                <option value="Ciencias Sociales" ${materiaClick === 'Ciencias Sociales' ? 'selected' : ''}>Ciencias Sociales</option>
                                <option value="Educación Física">Educación Física</option>
                                <option value="Música">Música</option>
                                <option value="Plástica">Plástica</option>
                                <option value="Inglés">Inglés</option>
                                <option value="Tecnología">Tecnología</option>
                            </select>
                        `;
                    }

                    modalNotas.classList.add('modal-activo');
                });
            });
        }

        function cerrarModalNotas() {
            if(modalNotas) modalNotas.classList.remove('modal-activo');
        }

        if(btnCerrarNotas) btnCerrarNotas.addEventListener('click', cerrarModalNotas);
        if(btnCancelarNotas) btnCancelarNotas.addEventListener('click', cerrarModalNotas);

        if(modalNotas) {
            modalNotas.addEventListener('click', (evento) => {
                if (evento.target === modalNotas) {
                    cerrarModalNotas();
                }
            });
        }

       //  MOTOR DEL MODAL DE ASISTENCIA 
        const btnAbrirAsistencia = document.querySelector('.btn-abrir-asistencia'); 
        const modalAsistencia = document.getElementById('modal-asistencia');
        const btnCerrarAsistencia = document.getElementById('btn-cerrar-asistencia');
        const btnCancelarAsistencia = document.getElementById('btn-cancelar-asistencia');

        if (btnAbrirAsistencia) {
            btnAbrirAsistencia.addEventListener('click', () => {
                modalAsistencia.classList.add('modal-activo');
            });
        }

        function cerrarModalAsistencia() {
            if(modalAsistencia) modalAsistencia.classList.remove('modal-activo');
        }

        if(btnCerrarAsistencia) btnCerrarAsistencia.addEventListener('click', cerrarModalAsistencia);
        if(btnCancelarAsistencia) btnCancelarAsistencia.addEventListener('click', cerrarModalAsistencia);
        //  MOTOR DE PERSISTENCIA DE VISTA 
        // Al cargar la página, verificamos si hay una "vista" en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const vistaSolicitada = urlParams.get('vista');

        if (vistaSolicitada) {
            // Buscamos el botón del menú que corresponde a esa vista
            const botonCorrespondiente = document.querySelector(`.nav-item[data-vista="${vistaSolicitada}"]`);
            
            if (botonCorrespondiente) {
                // Forzamos un clic automático para que el sistema de navegación haga su magia
                botonCorrespondiente.click();
            }
        }
        // --- MOTOR DE BÚSQUEDA Y FILTROS PARA CUOTAS ---
        const buscadorAlumnos = document.getElementById('buscador-alumnos');
        const filtroDeuda = document.getElementById('filtro-deuda');
        
        // Seleccionamos a todos los alumnos (están dentro del nivel más profundo de <details>)
        // Buscamos los details que están adentro de div.padding:10px
        const listaAlumnos = document.querySelectorAll('.acordeon-contenido > details > div > details');

        function filtrarCuotas() {
            if(!buscadorAlumnos || !filtroDeuda) return;

            const textoBusqueda = buscadorAlumnos.value.toLowerCase();
            const estadoFiltro = filtroDeuda.value;

            listaAlumnos.forEach(alumno => {
                // El nombre está en la etiqueta <summary>
                const nombreAlumno = alumno.querySelector('summary').textContent.toLowerCase();
                const tieneDeuda = alumno.classList.contains('estado-con-deuda');
                const estaAlDia = alumno.classList.contains('estado-al-dia');

                // 1. Verificamos si coincide con el texto
                const coincideTexto = nombreAlumno.includes(textoBusqueda);

                // 2. Verificamos si coincide con el filtro
                let coincideFiltro = true;
                if (estadoFiltro === 'con-deuda' && !tieneDeuda) coincideFiltro = false;
                if (estadoFiltro === 'al-dia' && !estaAlDia) coincideFiltro = false;

                // Si cumple ambas condiciones, lo mostramos. Si no, lo ocultamos.
                if (coincideTexto && coincideFiltro) {
                    alumno.style.display = 'block';
                    // Si estamos buscando, abrimos los acordeones padres para que se vea el resultado
                    if (textoBusqueda !== '') {
                        alumno.parentElement.parentElement.setAttribute('open', '');
                        alumno.parentElement.parentElement.parentElement.parentElement.setAttribute('open', '');
                    }
                } else {
                    alumno.style.display = 'none';
                }
            });
        }

        // Escuchamos cuando el usuario escribe o cambia el desplegable
        if(buscadorAlumnos) buscadorAlumnos.addEventListener('input', filtrarCuotas);
        if(filtroDeuda) filtroDeuda.addEventListener('change', filtrarCuotas);
        // --- MOTOR DE BÚSQUEDA Y FILTROS PARA SUELDOS ---
        const buscadorEmpleados = document.getElementById('buscador-empleados');
        const filtroSueldos = document.getElementById('filtro-sueldos');
        
        // Seleccionamos directamente a los detalles que tienen las clases de estado en la sección de sueldos
        const listaEmpleados = document.querySelectorAll('#vista-sueldos .estado-liquidado, #vista-sueldos .estado-pendiente');

        function filtrarSueldos() {
            if(!buscadorEmpleados || !filtroSueldos) return;

            const textoBusqueda = buscadorEmpleados.value.toLowerCase();
            const estadoFiltro = filtroSueldos.value;

            listaEmpleados.forEach(empleado => {
                // El nombre del empleado está en el <summary>
                const nombreEmpleado = empleado.querySelector('summary').textContent.toLowerCase();
                const estaPendiente = empleado.classList.contains('estado-pendiente');
                const estaLiquidado = empleado.classList.contains('estado-liquidado');

                // 1. Coincidencia de texto
                const coincideTexto = nombreEmpleado.includes(textoBusqueda);

                // 2. Coincidencia de filtro
                let coincideFiltro = true;
                if (estadoFiltro === 'pendiente' && !estaPendiente) coincideFiltro = false;
                if (estadoFiltro === 'liquidado' && !estaLiquidado) coincideFiltro = false;

                // Aplicar visualización
                if (coincideTexto && coincideFiltro) {
                    empleado.style.display = 'block';
                    
                    // Si estamos buscando texto, abrimos todos los acordeones padres automáticamente
                    if (textoBusqueda !== '') {
                        let padre = empleado.parentElement;
                        // Subimos por el árbol HTML hasta salir de la sección de sueldos
                        while(padre && padre.tagName !== 'SECTION') {
                            if(padre.tagName === 'DETAILS') {
                                padre.setAttribute('open', '');
                            }
                            padre = padre.parentElement;
                        }
                    }
                } else {
                    empleado.style.display = 'none';
                }
            });
        }

        // Escuchamos los cambios
        if(buscadorEmpleados) buscadorEmpleados.addEventListener('input', filtrarSueldos);
        if(filtroSueldos) filtroSueldos.addEventListener('change', filtrarSueldos);
        // --- MOTOR DEL MODAL DE USUARIOS (ADMINISTRACIÓN) ---
        const btnAbrirUsuario = document.getElementById('btn-abrir-usuarios');
        const modalUsuarios = document.getElementById('modal-usuarios');
        const btnCerrarUsuario = document.getElementById('btn-cerrar-usuario');
        const btnCancelarUsuario = document.getElementById('btn-cancelar-usuario');

        if (btnAbrirUsuario && modalUsuarios) {
            btnAbrirUsuario.addEventListener('click', () => {
                modalUsuarios.classList.add('modal-activo');
            });

            function cerrarModalUsuario() {
                modalUsuarios.classList.remove('modal-activo');
                if(camposCatedra) camposCatedra.style.display = 'none';
                if(camposAlumno) camposAlumno.style.display = 'none';
                if(camposTutor) camposTutor.style.display = 'none';
                if(selectRol) selectRol.value = ""; // Reiniciamos el selector
            }

            btnCerrarUsuario.addEventListener('click', cerrarModalUsuario);
            btnCancelarUsuario.addEventListener('click', cerrarModalUsuario);

            // Cerrar si se hace clic en la zona oscura exterior
            modalUsuarios.addEventListener('click', (evento) => {
                if (evento.target === modalUsuarios) {
                    cerrarModalUsuario();
                }
            });
        }
        
 
        // --- INTERRUPTOR DINÁMICO DE CAMPOS SEGÚN EL ROL ---
        const selectRol = document.getElementById('select-rol');
        const camposCatedra = document.getElementById('campos-catedra');
        const camposAlumno = document.getElementById('campos-alumno');
        const camposTutor = document.getElementById('campos-tutor'); // <--- NUEVO
        const grupoMateriaAsig = document.getElementById('grupo-materia-asig');
        const selectNivelAsig = document.getElementById('select-nivel-asig');
        const selectMateriaAsig = document.querySelector('select[name="materia_asig"]');

        if (selectRol) {
            selectRol.addEventListener('change', () => {
                // Primero apagamos todos los paneles especiales por defecto
                if(camposCatedra) camposCatedra.style.display = 'none';
                if(camposAlumno) camposAlumno.style.display = 'none';
                if(camposTutor) camposTutor.style.display = 'none';

                // Prendemos solo el que corresponde
                if (selectRol.value === 'profesor') {
                    if(camposCatedra) camposCatedra.style.display = 'grid';
                    if(grupoMateriaAsig) grupoMateriaAsig.style.display = (selectNivelAsig && selectNivelAsig.value === 'primaria') ? 'none' : 'flex';
                } else if (selectRol.value === 'preceptor') {
                    if(camposCatedra) camposCatedra.style.display = 'grid';
                    if(grupoMateriaAsig) grupoMateriaAsig.style.display = 'none'; 
                    if(selectNivelAsig) selectNivelAsig.value = 'secundaria'; 
                } else if (selectRol.value === 'alumno') {
                    if(camposAlumno) camposAlumno.style.display = 'grid';
                } else if (selectRol.value === 'tutor') {
                    if(camposTutor) camposTutor.style.display = 'grid'; // <--- Mostramos la asignación del hijo
                }
            });
        }
        
        // --- INTERRUPTOR DE MATERIAS PARA PRIMARIA Y PRECEPTORES ---
        if (selectNivelAsig && grupoMateriaAsig) {
            selectNivelAsig.addEventListener('change', () => {
                if (selectRol && selectRol.value === 'preceptor') {
                    grupoMateriaAsig.style.display = 'none';
                } else if (selectNivelAsig.value === 'primaria') {
                    grupoMateriaAsig.style.display = 'none'; // Oculta el menú de materias
                    if (selectMateriaAsig) selectMateriaAsig.value = ""; 
                } else {
                    grupoMateriaAsig.style.display = 'flex'; // Lo vuelve a mostrar
                }
            });
        }
        // --- MOTOR DE BÚSQUEDA PARA ASIGNAR HIJOS ---
        const buscadorHijos = document.getElementById('buscador-hijos');
        const listaItemsHijos = document.querySelectorAll('.item-hijo');

        if (buscadorHijos) {
            buscadorHijos.addEventListener('input', function(e) {
                const textoBuscado = e.target.value.toLowerCase();
                
                listaItemsHijos.forEach(label => {
                    const nombreAlumno = label.querySelector('.nombre-hijo-filtro').textContent.toLowerCase();
                    if (nombreAlumno.includes(textoBuscado)) {
                        label.style.display = 'flex';
                    } else {
                        label.style.display = 'none';
                    }
                });
            });
        }
        // --- MOTOR DINÁMICO DEL MODAL DE EDICIÓN DE USUARIOS ---
        const modalEditarUsuario = document.getElementById('modal-editar-usuario');
        const btnCerrarEditar = document.getElementById('btn-cerrar-editar-usuario');
        const btnCancelarEditar = document.getElementById('btn-cancelar-editar-usuario');

        // Activación del modal y auto-relleno de datos
        document.querySelectorAll('.btn-editar-usuario').forEach(btn => {
            btn.addEventListener('click', () => {
                const nombre = btn.getAttribute('data-nombre');
                const usuario = btn.getAttribute('data-usuario');
                const password = btn.getAttribute('data-password');

                document.getElementById('edit-usuario-original').value = usuario;
                document.getElementById('edit-nombre-pantalla').value = nombre;
                document.getElementById('edit-usuario-login').value = usuario;
                document.getElementById('edit-usuario-password').value = password;

                if (modalEditarUsuario) modalEditarUsuario.classList.add('modal-activo');
            });
        });

        function cerrarModalEditar() {
            if (modalEditarUsuario) modalEditarUsuario.classList.remove('modal-activo');
        }

        if (btnCerrarEditar) btnCerrarEditar.addEventListener('click', cerrarModalEditar);
        if (btnCancelarEditar) btnCancelarEditar.addEventListener('click', cerrarModalEditar);
        if (modalEditarUsuario) {
            modalEditarUsuario.addEventListener('click', (e) => {
                if (e.target === modalEditarUsuario) cerrarModalEditar();
            });
        }

        // --- MOTOR DE MODALES DE ACTIVIDADES ---
        const btnCrearAct = document.querySelector('.btn-abrir-crear-actividad');
        if (btnCrearAct) {
            btnCrearAct.addEventListener('click', () => document.getElementById('modal-crear-actividad').classList.add('modal-activo'));
        }

        document.querySelectorAll('.btn-abrir-entrega').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('input-entrega-id').value = btn.getAttribute('data-id');
                document.getElementById('modal-entrega-alumno').classList.add('modal-activo');
            });
        });

        document.querySelectorAll('.btn-corregir').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('input-correccion-id').value = btn.getAttribute('data-id');
                document.getElementById('input-correccion-alumno').value = btn.getAttribute('data-alumno');
                document.getElementById('input-correccion-materia').value = btn.getAttribute('data-materia');
                document.getElementById('modal-corregir-profe').classList.add('modal-activo');
            });
        });

        // --- MOTOR DEL MODAL DE CAMBIAR CONTRASEÑA PROPIA ---
        const btnAbrirCambiarClave = document.getElementById('btn-abrir-cambiar-clave');
        const modalCambiarClave = document.getElementById('modal-cambiar-clave');
        const btnCerrarCambiarClave = document.getElementById('btn-cerrar-cambiar-clave');
        const btnCancelarCambiarClave = document.getElementById('btn-cancelar-cambiar-clave');

        if (btnAbrirCambiarClave && modalCambiarClave) {
            btnAbrirCambiarClave.addEventListener('click', (e) => {
                e.preventDefault();
                modalCambiarClave.classList.add('modal-activo');
            });
        }

        function cerrarModalCambiarClave() {
            if (modalCambiarClave) modalCambiarClave.classList.remove('modal-activo');
        }

        if (btnCerrarCambiarClave) btnCerrarCambiarClave.addEventListener('click', cerrarModalCambiarClave);
        if (btnCancelarCambiarClave) btnCancelarCambiarClave.addEventListener('click', cerrarModalCambiarClave);
        if (modalCambiarClave) {
            modalCambiarClave.addEventListener('click', (e) => {
                if (e.target === modalCambiarClave) cerrarModalCambiarClave();
            });
        }

        function validarContrasenasNueva() {
            const nueva = document.getElementById('nueva-clave-input').value;
            const confirma = document.getElementById('confirmar-clave-input').value;
            if (nueva.length < 4) {
                alert('La nueva contraseña debe tener al menos 4 caracteres.');
                return false;
            }
            if (nueva !== confirma) {
                alert('La nueva contraseña y la confirmación no coinciden.');
                return false;
            }
            return true;
        }

        // --- MOTOR DEL MODAL DE FICHA DE ALUMNOS ---
        document.querySelectorAll('.btn-ver-ficha').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('ficha-nombre').innerText = "🎒 " + btn.getAttribute('data-nombre');
                document.getElementById('ficha-dni').innerText = btn.getAttribute('data-dni');
                
                // Le damos formato Argentino a la fecha (DD/MM/YYYY)
                let fnac = btn.getAttribute('data-fnac');
                if (fnac && fnac !== 'No cargado') {
                    let partes = fnac.split('-');
                    if (partes.length === 3) fnac = partes[2] + '/' + partes[1] + '/' + partes[0];
                }
                document.getElementById('ficha-fnac').innerText = fnac || 'No cargado';
                
                document.getElementById('ficha-tnombre').innerText = btn.getAttribute('data-tnombre') || 'No cargado';
                document.getElementById('ficha-ttel').innerText = btn.getAttribute('data-ttel') || 'No cargado';
                document.getElementById('ficha-temail').innerText = btn.getAttribute('data-temail') || 'No cargado';
                
                document.getElementById('modal-ficha-alumno').classList.add('modal-activo');
            });
        });

        // --- MOTOR PARA REPROGRAMAR ENTREVISTAS ---
        document.querySelectorAll('.btn-editar-entrevista').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit-entrevista-id').value = btn.getAttribute('data-id');
                document.getElementById('edit-entrevista-fecha').value = btn.getAttribute('data-fecha');
                document.getElementById('edit-entrevista-hora').value = btn.getAttribute('data-hora');
                document.getElementById('modal-editar-entrevista').classList.add('modal-activo');
            });
        });

        // --- MOTOR DE VALIDACIÓN DE FECHA/HORA DE ENTREVISTAS ---
        function validarFechaHoraEntrevista(formulario) {
            const inputFecha = formulario.querySelector('input[type="date"]');
            const inputHora = formulario.querySelector('input[type="time"]');
            
            if (inputFecha && inputHora) {
                const fechaElegida = inputFecha.value;
                const horaElegida = inputHora.value;
                
                const ahora = new Date();
                
                // Ajustamos la zona horaria para que coincida exactamente con la local
                const tzOffset = ahora.getTimezoneOffset() * 60000;
                const localISOTime = (new Date(ahora - tzOffset)).toISOString().slice(0, -1);
                const hoyStr = localISOTime.split("T")[0]; // Sacamos la fecha local
                
                // Si la entrevista es HOY, revisamos la hora
                if (fechaElegida === hoyStr) {
                    const horaActual = ahora.getHours().toString().padStart(2, '0') + ":" + ahora.getMinutes().toString().padStart(2, '0');
                    if (horaElegida < horaActual) {
                        alert("⚠️ Error: No podés agendar una entrevista en un horario que ya pasó.");
                        return false; // Frena el envío del formulario
                    }
                }
            }
            return true;
        }

        // --- MOTOR PARA REPROGRAMAR POSTULACIONES LABORALES ---
        document.querySelectorAll('.btn-editar-postulacion').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit-postulacion-id').value = btn.getAttribute('data-id');
                document.getElementById('edit-postulacion-fecha').value = btn.getAttribute('data-fecha');
                document.getElementById('edit-postulacion-hora').value = btn.getAttribute('data-hora');
                document.getElementById('modal-editar-postulacion').classList.add('modal-activo');
            });
        });
    </script>
</body>
</html>