<?php
function guardarNotificacion($conexion, $usuario_id, $subasta_id, $mensaje, $tipo = 'puja'){
    $sql = "INSERT INTO notificaciones (usuario_id, subasta_id, mensaje, tipo) 
            VALUES ($usuario_id, $subasta_id, '$mensaje', '$tipo')";
    return mysqli_query($conexion, $sql);
}
?>