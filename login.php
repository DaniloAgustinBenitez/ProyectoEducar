<?php
// Arrancamos el motor de sesiones de PHP
session_start();

// Si el usuario ya está logueado, lo mandamos directo al panel
if (isset($_SESSION['usuario'])) {
    header('Location: dashboard.php');
    exit;
}

$error = ""; // Variable para guardar el mensaje de error en pantalla

// Verificamos si alguien apretó el botón de "INGRESAR"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Llamamos a nuestro puente de base de datos
    require_once 'procesos/conexion.php'; 

    $usuario_ingresado = trim($_POST['usuario'] ?? '');
    $password_ingresada = trim($_POST['password'] ?? '');

    if (!empty($usuario_ingresado) && !empty($password_ingresada)) {
        try {
            // 2. Preparamos la consulta SQL de forma segura
            $stmt = $pdo->prepare("SELECT id, username, password, rol FROM usuarios WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $usuario_ingresado, PDO::PARAM_STR);
            $stmt->execute();
            
            $usuario_bd = $stmt->fetch();

            // 3. Verificamos si el usuario existe y desencriptamos su contraseña
            if ($usuario_bd && password_verify($password_ingresada, $usuario_bd['password'])) {
                
                // ¡Éxito! Iniciamos la sesión oficial
                $_SESSION['usuario_id'] = $usuario_bd['id'];
                $_SESSION['usuario'] = $usuario_bd['username'];
                $_SESSION['rol'] = $usuario_bd['rol'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos. Intentá de nuevo.";
            }

        } catch (PDOException $e) {
            $error = "Error de conexión con la base de datos.";
            error_log("Error en el login: " . $e->getMessage());
        }
    } else {
        $error = "Por favor completá todos los datos.";
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
            $alternativas = glob(__DIR__ . '/img/*.{png,jpg,jpeg,jfif,gif}', GLOB_BRACE);
            if (!empty($alternativas)) {
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

        <div style="text-align: center; margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
            <a href="ingresar.html" style="color: #888; text-decoration: none; font-size: 0.85rem; transition: color 0.3s;" onmouseover="this.style.color='var(--azul-primario)'" onmouseout="this.style.color='#888'">← Volver</a>
            <a href="#" id="link-olvido" style="color: var(--azul-primario); text-decoration: none; font-size: 0.85rem;">¿Olvidaste tu contraseña?</a>
        </div>

        <div id="caja-recuperacion" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #ccc;">
            <p style="font-size: 0.9rem; color: #555; text-align: center; margin-bottom: 15px;">Ingresá tu nombre de usuario y solicitaremos el blanqueo a Preceptoría/Administración.</p>
            <form action="procesos/procesar_recuperacion.php" method="POST">
                <input type="text" name="usuario_recuperar" placeholder="Tu usuario (ej: admin)" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
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