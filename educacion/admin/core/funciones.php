<?php
$archivo_json = __DIR__ . '/datos.json';

// Lee el archivo y devuelve un array
function obtenerDatos()
{
    global $archivo_json;
    if (!file_exists($archivo_json))
        return [];
    $contenido = file_get_contents($archivo_json);
    return json_decode($contenido, true) ?: [];
}

// Guarda el array de nuevo en el JSON
function guardarDatos($lista)
{
    global $archivo_json;
    // array_values resetea los índices por si borramos algo
    $json_string = json_encode(array_values($lista), JSON_PRETTY_PRINT);
    return file_put_contents($archivo_json, $json_string);
}

// Busca un elemento específico por su ID
function buscarPorId($id)
{
    $datos = obtenerDatos();
    foreach ($datos as $item) {
        if ($item['id'] == $id)
            return $item;
    }
    return null;
}
?>