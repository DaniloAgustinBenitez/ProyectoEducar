<?php
// Arrancamos el motor de sesiones de PHP
session_start();

// Si el usuario ya está logueado, lo mandamos directo al panel
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = ""; // Variable para guardar el mensaje de error

// Verificamos si alguien apretó el botón de "INGRESAR"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_ingresado = trim($_POST['usuario'] ?? '');
    $password_ingresada = trim($_POST['password'] ?? '');

    $archivo_usuarios = __DIR__ . '/data/usuarios.json';
    $usuarios_permitidos = [];

    // Levantamos los usuarios desde nuestro archivo JSON
    if (file_exists($archivo_usuarios)) {
        $usuarios_permitidos = json_decode(file_get_contents($archivo_usuarios), true) ?: [];
    }

    $autenticado = false;

    // Recorremos la lista del JSON buscando coincidencia
    foreach ($usuarios_permitidos as $u) {
        // Usamos password_verify para comparar el texto que escribió el usuario con el hash guardado
        if (strtolower($usuario_ingresado) === strtolower($u['usuario']) && password_verify($password_ingresada, $u['password'])) {
            // Guardamos el nombre oficial y el rol en la sesión
            $_SESSION['usuario'] = $u['nombre'];
            $_SESSION['rol'] = $u['rol']; // Guardamos el rol para futuras validaciones de seguridad
            $autenticado = true;
            break;
        }
    }

    if ($autenticado) {
        // Credenciales correctas, directo al panel
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Usuario o contraseña incorrectos. Intentá de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema de Gestión Educativa</title>
    <style>
        :root {
            --azul-primario: #1b499b;
            --verde: #8cc63f;
            --naranja: #f15a24;
            --fondo-claro: #f8f9fa;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--fondo-claro);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            background: white;
            padding: 50px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
            border-top: 6px solid var(--azul-primario);
        }
        /* Apuntamos a la nueva estructura de carpetas organizadas */
        img { width: 80px; margin-bottom: 20px; }
        h2 { color: var(--azul-primario); margin-bottom: 30px; }
        
        input {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: var(--azul-primario);
        }
        .btn-entrar {
            background-color: var(--verde);
            color: white;
            border: none;
            padding: 15px;
            width: 100%;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-entrar:hover { transform: scale(1.03); background-color: #7ab033; }
        
        .error-alerta {
            background-color: #fce8e6;
            color: var(--naranja);
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <?php
        $logo_path = 'img/logo.png';
        if (!file_exists($logo_path)) {
            // Buscar una imagen alternativa en la carpeta img
            $alternativas = glob(__DIR__ . '/img/*.{png,jpg,jpeg,jfif,gif}', GLOB_BRACE);
            if (!empty($alternativas)) {
                // Tomamos la primera alternativa y convertimos a ruta relativa
                $logo_path = 'img/' . basename($alternativas[0]);
            } else {
                $logo_path = '';
            }
        }
        ?>
        <?php if ($logo_path): ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo">
        <?php endif; ?>
        <h2>Acceso al Portal</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-alerta"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="usuario" placeholder="Usuario o DNI" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn-entrar">INGRESAR</button>
        </form>

        <div style="text-align: center; margin-top: 15px;">
            <a href="#" id="link-olvido" style="color: var(--azul-primario); text-decoration: none; font-size: 0.9rem;">¿Olvidaste tu contraseña?</a>
        </div>

        <div id="caja-recuperacion" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #ccc;">
            <p style="font-size: 0.9rem; color: #555; text-align: center; margin-bottom: 15px;">Ingresá tu nombre de usuario y solicitaremos el blanqueo a Preceptoría/Administración.</p>
            <form action="procesos/procesar_recuperacion.php" method="POST">
                <input type="text" name="usuario_recuperar" placeholder="Tu usuario (ej: gmongelo)" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                <button type="submit" style="width: 100%; padding: 10px; background-color: #f15a24; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Solicitar Blanqueo</button>
            </form>
        </div>

        <?php if (isset($_GET['msj']) && $_GET['msj'] === 'recuperacion_enviada'): ?>
            <div style="background: #e6f6ec; color: #155724; padding: 10px; border-radius: 5px; text-align: center; font-size: 0.9rem; margin-top: 15px; font-weight: bold;">
                ✅ Solicitud enviada a la administración.
            </div>
        <?php endif; ?>

        <script>
            document.getElementById('link-olvido').addEventListener('click', function(e) {
                e.preventDefault();
                const caja = document.getElementById('caja-recuperacion');
                caja.style.display = caja.style.display === 'none' ? 'block' : 'none';
            });
        </script>

    </div>
</body>
</html>