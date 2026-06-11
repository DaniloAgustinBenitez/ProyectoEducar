<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'profesor') {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $archivo_reservas = __DIR__ . '/../data/reservas.json';
    
    // Si no existe la carpeta, la creamos
    if (!file_exists(__DIR__ . '/../data')) mkdir(__DIR__ . '/../data', 0777, true);
    
    $reservas = file_exists($archivo_reservas) ? json_decode(file_get_contents($archivo_reservas), true) : [];

    // --- LÓGICA PARA CREAR RESERVA ---
    if ($accion === 'reservar') {
        $espacio = trim($_POST['espacio'] ?? '');
        $fecha = trim($_POST['fecha_reserva'] ?? ''); // Ej: 2026-05-15
        $modulo = trim($_POST['modulo_reserva'] ?? '');

        if (!empty($espacio) && !empty($fecha) && !empty($modulo)) {
            
            // 1. EL SISTEMA ANTI-CHOQUES: Revisamos si ya está ocupado
            $choque = false;
            foreach ($reservas as $r) {
                if ($r['espacio'] === $espacio && $r['fecha'] === $fecha && $r['modulo'] === $modulo) {
                    $choque = true;
                    break;
                }
            }

            // Si hay choque, lo rebotamos con un error
            if ($choque) {
                header('Location: ../dashboard.php?vista=vista-reservas&error=ocupado');
                exit;
            }

            // 2. Buscamos el nombre real del profe
            $archivo_usuarios = __DIR__ . '/../data/usuarios.json';
            $usuarios = file_exists($archivo_usuarios) ? json_decode(file_get_contents($archivo_usuarios), true) : [];
            $nombre_profe = $_SESSION['usuario'];
            foreach ($usuarios as $u) {
                if ($u['usuario'] === $_SESSION['usuario']) { 
                    $nombre_profe = $u['nombre']; 
                    break; 
                }
            }

            // 3. Guardamos la reserva con un ID único
            $reservas[] = [
                'id' => uniqid('res_'),
                'espacio' => $espacio,
                'fecha' => $fecha,
                'modulo' => $modulo,
                'profesor' => $nombre_profe
            ];
            
            file_put_contents($archivo_reservas, json_encode($reservas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            header('Location: ../dashboard.php?vista=vista-reservas&msj=reservado');
            exit;
        }
    }

    // --- LÓGICA PARA CANCELAR UNA RESERVA ---
    if ($accion === 'eliminar') {
        $id_borrar = $_POST['id_reserva'] ?? '';
        
        $reservas = array_filter($reservas, function($r) use ($id_borrar) {
            return ($r['id'] ?? '') !== $id_borrar;
        });
        
        file_put_contents($archivo_reservas, json_encode(array_values($reservas), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header('Location: ../dashboard.php?vista=vista-reservas&msj=eliminado');
        exit;
    }
}

header('Location: ../dashboard.php?vista=vista-reservas');
exit;
?>