<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si no viene accion, asumimos que es "agendar" por defecto
    $accion = $_POST['accion'] ?? 'agendar'; 
    $id_buscado = $_POST['id_entrevista'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if (!empty($id_buscado)) {
        $archivo_entrevistas = __DIR__ . '/../data/entrevistas.json';
        $entrevistas = file_exists($archivo_entrevistas) ? json_decode(file_get_contents($archivo_entrevistas), true) : [];

        if ($accion === 'borrar') {
            // Filtramos quitando la entrevista que queremos borrar
            $entrevistas = array_filter($entrevistas, function($ent) use ($id_buscado) {
                return $ent['id'] !== $id_buscado;
            });
            $entrevistas = array_values($entrevistas); // Reordenar
            file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            header('Location: ../dashboard.php?vista=vista-entrevistas&msj=entrevista_eliminada');
            exit;
        } 
        elseif (!empty($fecha) && !empty($hora)) {
            // Sirve tanto para agendar por primera vez como para editar
            foreach ($entrevistas as &$ent) {
                if ($ent['id'] === $id_buscado) {
                    $ent['estado'] = 'agendada';
                    $ent['fecha_agendada'] = $fecha;
                    $ent['hora_agendada'] = $hora;
                    break;
                }
            }
            file_put_contents($archivo_entrevistas, json_encode($entrevistas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $msj_retorno = ($accion === 'editar') ? 'entrevista_modificada' : 'entrevista_agendada';
            header("Location: ../dashboard.php?vista=vista-entrevistas&msj=$msj_retorno");
            exit;
        }
    }
}

header('Location: ../dashboard.php?vista=vista-entrevistas');
exit;
?>