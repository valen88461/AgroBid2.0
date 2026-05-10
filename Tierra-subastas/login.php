<?php
session_start(); // ⚠️ IMPORTANTE: Primera línea después de <?php
include(__DIR__ . "/includes/conexion.php");

$mensaje = "";

if(isset($_POST['ingresar'])){

    $correo = trim($_POST['correo']);
    $clave  = trim($_POST['clave']);
    
    // Usar consulta preparada
    $stmt = mysqli_prepare($conexion, "SELECT id, nombre, correo, clave FROM usuarios WHERE correo = ?");
    mysqli_stmt_bind_param($stmt, "s", $correo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if($fila = mysqli_fetch_assoc($resultado)){
        
        // Verificar contraseña con hash
        if(password_verify($clave, $fila['clave'])){
            $_SESSION['usuario'] = $fila['nombre'];
            $_SESSION['correo'] = $fila['correo'];
            $_SESSION['user_id'] = $fila['id'];
            
            // Redirigir después de iniciar sesión
            header("Location: index.php");
            exit();
        } else {
            $mensaje = "❌ Contraseña incorrecta";
        }
    } else {
        $mensaje = "❌ Correo no registrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Tierra de Subastas</title>
<link rel="stylesheet" href="css/estilos.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
  <div class="logo">
    <img src="img/logo.png" alt="Logo" class="logo-img">
    AgroBíd
</div>
    </div>
    
  <nav class="menu">
        <a href="index.php"> Inicio</a>
        <a href="subastas.php"> Subastas</a>
        <a href="publicar.php"> Publicar</a>
       <a href="certificacion.php"> Certificaciones</a>
         <a href="mis_compras.php">Mis Compras</a>
          <a href="mis_subastas.php">Mis Subastas</a> 
</nav>
        
        <?php if(isset($_SESSION['usuario'])): ?>
            <a href="perfil.php">👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?></a>
            <a href="logout.php" onclick="return confirm('¿Cerrar sesión?')">🚪 Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php" style="background: var(--esperanza); color: white; padding: 0.5rem 1.2rem; border-radius: 50px;">🔐 Ingresar</a>
        <?php endif; ?>
    </nav>
</header>

<section class="productos">
    <div class="titulo-seccion">
        <h2> Iniciar Sesión</h2>
        <p>Ingresa a tu cuenta y participa en las subastas</p>
    </div>

    <form method="POST" class="formulario">
        <div class="grupo-form">
            <label> Correo electrónico</label>
            <input type="email" name="correo" placeholder="tu@email.com" required value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
        </div>
        
        <div class="grupo-form">
            <label>🔒 Contraseña</label>
            <input type="password" name="clave" placeholder="••••••••" required>
        </div>
        
        <button type="submit" name="ingresar" class="btn-principal" style="width: 100%;">🚀 Ingresar</button>
        
        <p class="texto-link" style="text-align: center; margin-top: 1.5rem;">
            ¿No tienes cuenta? <a href="registro.php" style="color: var(--esperanza); font-weight: bold;">✨ Crear usuario</a>
        </p>
        
        <?php if(!empty($mensaje)): ?>
            <div class="error-msg" style="margin-top: 1rem;">⚠️ <?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
    </form>
</section>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Conectamos el campo colombiano</p>
</footer>

</body>
</html>