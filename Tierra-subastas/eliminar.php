<?php
include(__DIR__ . "/includes/conexion.php");
session_start();

// Verificar si el usuario está logueado
if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    
    // Verificar que la subasta pertenece al usuario
    $usuario_id = $_SESSION['user_id'];
    
    $stmt = mysqli_prepare($conexion, "DELETE FROM subastas WHERE id = ? AND usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $usuario_id);
    
    if(mysqli_stmt_execute($stmt)){
        header("Location: subastas.php");
        exit();
    } else {
        echo "Error al eliminar";
    }
} else {
    header("Location: subastas.php");
}
?>