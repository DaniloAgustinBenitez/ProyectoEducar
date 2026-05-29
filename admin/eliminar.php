<?php
require_once __DIR__ . '/core/funciones.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $datos = obtenerDatos();
    $datosNuevos = array_filter($datos, function ($item) use ($id) {
        return $item['id'] != $id;
    });
    guardarDatos($datosNuevos);
}

header('Location: index.php?msj=eliminado');
exit;