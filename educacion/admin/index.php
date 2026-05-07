<?php
require_once '../core/funciones.php';
$items = obtenerDatos();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Admin | Educar para Transformar</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #1b499b;
            border-bottom: 2px solid #8cc63f;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #1b499b;
            color: white;
        }

        .btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-nuevo {
            background: #8cc63f;
            display: inline-block;
            margin-bottom: 20px;
        }

        .btn-edit {
            background: #4cb2e4;
            margin-right: 5px;
        }

        .btn-del {
            background: #f15a24;
        }

        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: bold;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Gestión de Contenidos Educativos</h1>

        <?php if (isset($_GET['msj'])): ?>
            <div class="alert success">Acción realizada: <?php echo htmlspecialchars($_GET['msj']); ?></div>
        <?php endif; ?>

        <a href="crear.php" class="btn btn-nuevo">+ Agregar Nuevo Elemento</a>

        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($item['titulo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['descripcion']); ?></td>
                        <td>
                            <a href="editar.php?id=<?php echo $item['id']; ?>" class="btn btn-edit">Editar</a>
                            <a href="eliminar.php?id=<?php echo $item['id']; ?>" class="btn btn-del"
                                onclick="return confirm('¿Estás seguro?')">Borrar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>