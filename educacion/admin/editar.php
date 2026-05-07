<?php
require_once '../core/funciones.php';

$id = $_GET['id'] ?? null;
$item = buscarPorId($id);

if (!$item) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = obtenerDatos();
    foreach ($datos as &$r) {
        if ($r['id'] == $id) {
            $r['titulo'] = htmlspecialchars($_POST['titulo'], ENT_QUOTES, 'UTF-8');
            $r['descripcion'] = htmlspecialchars($_POST['descripcion'], ENT_QUOTES, 'UTF-8');
            $r['imagen'] = htmlspecialchars($_POST['imagen'], ENT_QUOTES, 'UTF-8');
        }
    }
    guardarDatos($datos);
    header('Location: index.php?msj=actualizado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Elemento</title>
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
        }

        .btn-update {
            background: #4cb2e4;
            color: white;
            border: none;
            width: 100%;
            padding: 12px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="form-card">
        <h2>Editar Registro</h2>
        <form method="POST">
            <input type="text" name="titulo" value="<?php echo $item['titulo']; ?>" required>
            <textarea name="descripcion" rows="4" required><?php echo $item['descripcion']; ?></textarea>
            <input type="text" name="imagen" value="<?php echo $item['imagen']; ?>">
            <button type="submit" class="btn-update">Actualizar Datos</button>
            <p align="center"><a href="index.php">Volver</a></p>
        </form>
    </div>
</body>

</html>