<?php
include("includes/conexion.php");
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

if(isset($_POST['marcar_entregado'])){
    $subasta_id = $_POST['subasta_id'];
    
    // Verificar que el usuario es el vendedor
    $sql_check = "SELECT usuario_id FROM subastas WHERE id = $subasta_id";
    $res_check = mysqli_query($conexion, $sql_check);
    $subasta = mysqli_fetch_assoc($res_check);
    
    if($subasta['usuario_id'] == $_SESSION['user_id']){
        $sql = "UPDATE subastas SET entregado = 1 WHERE id = $subasta_id";
        
        if(mysqli_query($conexion, $sql)){
            echo "<script>
                alert(' Producto marcado como entregado. El comprador podrá calificar.');
                window.location.href = 'perfil.php';
            </script>";
        } else {
            echo "<script>
                alert(' Error al marcar como entregado');
                window.location.href = 'perfil.php';
            </script>";
        }
    } else {
        echo "<script>
            alert(' No tienes permiso para esto');
            window.location.href = 'perfil.php';
        </script>";
    }
    exit();
}
?>