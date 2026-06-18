<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'preceptor'])) {
    header('Location: ../dashboard.php');
    exit;
}

$curso = trim($_GET['curso'] ?? '');
$mes   = trim($_GET['mes']   ?? date('Y-m'));

if (empty($curso) || !preg_match('/^\d{4}-\d{2}$/', $mes)) {
    header('Location: ../dashboard.php?vista=vista-asistencia-preceptor');
    exit;
}

[$anio, $num_mes] = explode('-', $mes);
$inicio = "$anio-$num_mes-01";
$fin    = date('Y-m-t', strtotime($inicio));

// Alumnos del curso
$stmt_al = $pdo->prepare("SELECT DISTINCT alumno_nombre FROM matricula WHERE curso_clave = :curso ORDER BY alumno_nombre");
$stmt_al->execute([':curso' => $curso]);
$alumnos = $stmt_al->fetchAll(PDO::FETCH_COLUMN);

// Días lectivos con asistencia registrada para ese curso en el mes
$stmt_dias = $pdo->prepare("SELECT DISTINCT fecha FROM asistencias WHERE curso_clave = :curso AND fecha BETWEEN :inicio AND :fin ORDER BY fecha");
$stmt_dias->execute([':curso' => $curso, ':inicio' => $inicio, ':fin' => $fin]);
$dias = $stmt_dias->fetchAll(PDO::FETCH_COLUMN);

// Ausentes por día
$stmt_aus = $pdo->prepare("SELECT alumno_nombre, fecha FROM asistencias WHERE curso_clave = :curso AND fecha BETWEEN :inicio AND :fin AND estado = 'ausente'");
$stmt_aus->execute([':curso' => $curso, ':inicio' => $inicio, ':fin' => $fin]);
$ausencias = [];
foreach ($stmt_aus->fetchAll() as $row) {
    $ausencias[$row['fecha']][$row['alumno_nombre']] = true;
}

$nombre_archivo = "asistencia_{$curso}_{$mes}.csv";
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"$nombre_archivo\"");
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

// Encabezado: Alumno | día1 | día2 | ... | Total faltas
$cabecera = ['Alumno'];
foreach ($dias as $d) $cabecera[] = date('d/m', strtotime($d));
$cabecera[] = 'Total faltas';
fputcsv($out, $cabecera, ';');

foreach ($alumnos as $alumno) {
    $fila = [$alumno];
    $total = 0;
    foreach ($dias as $d) {
        if (isset($ausencias[$d][$alumno])) {
            $fila[] = 'A'; // Ausente
            $total++;
        } else {
            $fila[] = 'P'; // Presente
        }
    }
    $fila[] = $total;
    fputcsv($out, $fila, ';');
}

fclose($out);
exit;
