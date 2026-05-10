<?php
session_start();
include(__DIR__ . "/includes/conexion.php");
header('Content-Type: application/json');

if(!isset($_SESSION['usuario'])){
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit();
}

$usuario_id = $_SESSION['user_id'];
$subasta_id = isset($_POST['subasta_id']) ? intval($_POST['subasta_id']) : 0;
$ganador_id = isset($_POST['ganador_id']) ? intval($_POST['ganador_id']) : 0;
$monto = isset($_POST['monto']) ? floatval($_POST['monto']) : 0;

// Verificar que la subasta pertenece al usuario
$sql_check = "SELECT * FROM subastas WHERE id = $subasta_id AND usuario_id = $usuario_id AND estado = 'activa'";
$result_check = mysqli_query($conexion, $sql_check);

if(mysqli_num_rows($result_check) == 0){
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para finalizar esta subasta']);
    exit();
}

// Actualizar la subasta
$sql_update = "UPDATE subastas SET 
                ganador_id = $ganador_id, 
                monto_final = $monto,
                estado = 'finalizada',
                entregado = 0,
                calificado = 0,
                fecha_finalizacion = NOW()
                WHERE id = $subasta_id";

if(mysqli_query($conexion, $sql_update)){
    $sql_ganador = "SELECT nombre FROM usuarios WHERE id = $ganador_id";
    $result_ganador = mysqli_query($conexion, $sql_ganador);
    $ganador = mysqli_fetch_assoc($result_ganador);
    
    echo json_encode(['success' => true, 'message' => 'Ganador seleccionado: ' . $ganador['nombre']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conexion)]);
}
?>