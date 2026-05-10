<?php
include("includes/conexion.php");
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

if(isset($_POST['confirmar_entrega'])){
    $subasta_id = $_POST['subasta_id'];
    
    // Actualizar la subasta como entregada
    $sql = "UPDATE subastas SET entregado = 1 WHERE id = $subasta_id";
    
    if(mysqli_query($conexion, $sql)){
        echo "<script>
            alert(' Producto marcado como recibido. Ahora puedes calificar al vendedor.');
            window.location.href = 'calificar.php';
        </script>";
    } else {
        echo "<script>
            alert('❌ Error: " . mysqli_error($conexion) . "');
            window.location.href = 'perfil.php';
        </script>";
    }
    exit();
} else {
    header("Location: perfil.php");
    exit();
}
?>