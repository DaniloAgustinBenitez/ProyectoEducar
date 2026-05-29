<?php
require_once __DIR__ . '/core/funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datosActuales = obtenerDatos();

    $nuevo = [
        "id" => time(),
        "titulo" => htmlspecialchars($_POST['titulo'], ENT_QUOTES, 'UTF-8'),
        "descripcion" => htmlspecialchars($_POST['descripcion'], ENT_QUOTES, 'UTF-8'),
        "imagen" => htmlspecialchars($_POST['imagen'], ENT_QUOTES, 'UTF-8')
    ];

    $datosActuales[] = $nuevo;
    if (guardarDatos($datosActuales)) {
        header('Location: index.php?msj=creado');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Elemento</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            padding: 50px;
        }

        .form-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 400px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #1b499b;
        }

        input,
        textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }

        .btn-submit {
            background: #8cc63f;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="form-card">
        <h2>Crear Registro</h2>
        <form method="POST">
            <label>Título</label>
            <input type="text" name="titulo" required>
            <label>Descripción</label>
            <textarea name="descripcion" rows="4" required></textarea>
            <label>Nombre Imagen (ej: foto.jpg)</label>
            <input type="text" name="imagen">
            <button type="submit" class="btn-submit">Guardar en el Sistema</button>
            <p align="center"><a href="index.php">Cancelar</a></p>
        </form>
    </div>
</body>

</html>