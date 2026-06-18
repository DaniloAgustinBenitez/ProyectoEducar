<?php
require_once __DIR__ . '/../../procesos/conexion.php';

function obtenerDatos(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT id, titulo, descripcion, imagen FROM admin_niveles ORDER BY id ASC");
    return $stmt->fetchAll();
}

function guardarDatos(array $lista): bool
{
    global $pdo;
    try {
        $pdo->exec("TRUNCATE TABLE admin_niveles");
        $stmt = $pdo->prepare("INSERT INTO admin_niveles (id, titulo, descripcion, imagen) VALUES (:id, :titulo, :desc, :img)");
        foreach (array_values($lista) as $item) {
            $stmt->execute([
                ':id'     => $item['id'],
                ':titulo' => $item['titulo'],
                ':desc'   => $item['descripcion'],
                ':img'    => $item['imagen'] ?? ''
            ]);
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error en guardarDatos admin: " . $e->getMessage());
        return false;
    }
}

function buscarPorId($id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, titulo, descripcion, imagen FROM admin_niveles WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
?>