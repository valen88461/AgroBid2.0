<?php
session_start();
include("includes/conexion.php");

// Verificar login
if(!isset($_SESSION['usuario'])){
    die("❌ Debes <a href='login.php'>iniciar sesión</a> primero");
}

$mensaje = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $producto = $_POST['producto'];
    $categoria = $_POST['categoria'];
    $precio = $_POST['precio'];
    $usuario_id = $_SESSION['user_id'];
    
    // Insertar simple
    $sql = "INSERT INTO subastas (producto, categoria, precio_inicial, precio_actual, usuario_id, estado) 
            VALUES ('$producto', '$categoria', '$precio', '$precio', '$usuario_id', 'activa')";
    
    if(mysqli_query($conexion, $sql)){
        $mensaje = " Subasta creada con ID: " . mysqli_insert_id($conexion);
    } else {
        $mensaje = " Error: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Publicar Simple</title>
    <meta charset="UTF-8">
</head>
<body style="background:#0a0f0a; color:white; font-family:Arial; padding:20px;">
    <h1>Publicar Subasta (Versión Simple)</h1>
    
    <?php if($mensaje): ?>
        <div style="background:#1a3a1a; padding:10px; margin:10px 0; border-radius:5px;">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="background:#1a2a1a; padding:20px; border-radius:10px; max-width:400px;">
        <div style="margin-bottom:10px;">
            <label>Producto:</label><br>
            <input type="text" name="producto" required style="width:100%; padding:8px;">
        </div>
        
        <div style="margin-bottom:10px;">
            <label>Categoría:</label><br>
            <select name="categoria" required style="width:100%; padding:8px;">
                <option value="Ganado">Ganado</option>
                <option value="Cosechas">Cosechas</option>
                <option value="Maquinaria">Maquinaria</option>
            </select>
        </div>
        
        <div style="margin-bottom:10px;">
            <label>Precio:</label><br>
            <input type="number" name="precio" required style="width:100%; padding:8px;">
        </div>
        
        <button type="submit" style="background:#2ecc71; color:white; padding:10px 20px; border:none; border-radius:5px;">
            Publicar
        </button>
    </form>
    
    <p style="margin-top:20px;">
        <a href="subastas.php" style="color:#2ecc71;">Ver subastas</a> | 
        <a href="cerrar_sesion.php" style="color:#ff6666;">Cerrar sesión</a>
    </p>
</body>
</html>