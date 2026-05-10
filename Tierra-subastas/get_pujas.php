<?php
include(__DIR__ . "/includes/conexion.php");
header('Content-Type: application/json');

$subasta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT p.*, u.nombre as usuario 
        FROM pujas p
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.subasta_id = $subasta_id
        ORDER BY p.monto DESC";
$result = mysqli_query($conexion, $sql);

$pujas = [];
while($row = mysqli_fetch_assoc($result)){
    $pujas[] = [
        'id' => $row['id'],
        'usuario' => $row['usuario'],
        'usuario_id' => $row['usuario_id'],
        'monto' => $row['monto'],
        'fecha_puja' => date('d/m/Y H:i', strtotime($row['fecha_puja']))
    ];
}

echo json_encode($pujas);
?>