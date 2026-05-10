<?php
include(__DIR__ . "/includes/conexion.php");
session_start();

// Verificar si el usuario está logueado
if(!isset($_SESSION['usuario'])){
    header("Location: login.php?error=Debes iniciar sesión para participar");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$error = "";
$exito = "";

// Obtener datos de la subasta
$subasta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($subasta_id == 0){
    header("Location: subastas.php");
    exit();
}

// Obtener información de la subasta
$sql_subasta = "SELECT s.*, u.nombre as vendedor 
                FROM subastas s 
                LEFT JOIN usuarios u ON s.usuario_id = u.id 
                WHERE s.id = $subasta_id";
$result_subasta = mysqli_query($conexion, $sql_subasta);
$subasta = mysqli_fetch_assoc($result_subasta);

if(!$subasta){
    header("Location: subastas.php");
    exit();
}

// Verificar que la subasta esté activa
if($subasta['estado'] != 'activa'){
    $error = "Esta subasta ya finalizó";
}

// Procesar la puja
if(isset($_POST['pujar'])){
    $monto_puja = floatval($_POST['monto']);
    $precio_actual = floatval($subasta['precio_actual']);
    $incremento_minimo = 50000;
    
    if($monto_puja >= ($precio_actual + $incremento_minimo)){
        
        // Guardar la puja
        $sql_puja = "INSERT INTO pujas (subasta_id, usuario_id, monto, fecha_puja) 
                     VALUES ($subasta_id, $usuario_id, $monto_puja, NOW())";
        
        if(mysqli_query($conexion, $sql_puja)){
            
            // Actualizar precio actual
            $sql_update = "UPDATE subastas SET precio_actual = $monto_puja WHERE id = $subasta_id";
            mysqli_query($conexion, $sql_update);
            
            // Notificación al vendedor
            $mensaje_vendedor = "💰 " . $_SESSION['usuario'] . " ha pujado $" . number_format($monto_puja) . " por tu producto: " . $subasta['producto'];
            $sql_notif = "INSERT INTO notificaciones (usuario_id, subasta_id, mensaje, tipo) 
                          VALUES ({$subasta['usuario_id']}, $subasta_id, '$mensaje_vendedor', 'puja')";
            mysqli_query($conexion, $sql_notif);
            
            // Notificación al vendedor
           $mensaje_vendedor = "💰 " . $_SESSION['usuario'] . " ha pujado $" . number_format($monto_puja) . " por tu producto: " . $subasta['producto'];
           $sql_notif = "INSERT INTO notificaciones (usuario_id, subasta_id, mensaje, tipo, fecha) 
              VALUES ({$subasta['usuario_id']}, $subasta_id, '$mensaje_vendedor', 'puja', NOW())";
           mysqli_query($conexion, $sql_notif);
            // Notificación a otros pujadores
            $sql_otros = "SELECT DISTINCT usuario_id FROM pujas WHERE subasta_id = $subasta_id AND usuario_id != $usuario_id";
            $res_otros = mysqli_query($conexion, $sql_otros);
            while($otro = mysqli_fetch_assoc($res_otros)){
                $mensaje_otro = "⚠️ Te superaron en la subasta: " . $subasta['producto'] . " - Nueva puja: $" . number_format($monto_puja);
                $sql_notif2 = "INSERT INTO notificaciones (usuario_id, subasta_id, mensaje, tipo) 
                               VALUES ({$otro['usuario_id']}, $subasta_id, '$mensaje_otro', 'puja')";
                mysqli_query($conexion, $sql_notif2);
            }
            
            $exito = " ¡Puja registrada exitosamente!";
            echo "<script>alert('$exito'); window.location.href='subastas.php';</script>";
            exit();
        } else {
            $error = "Error al registrar la puja: " . mysqli_error($conexion);
        }
    } else {
        $error = "El monto mínimo debe ser de $" . number_format($precio_actual + $incremento_minimo);
    }
}

// Obtener historial de pujas
$sql_historial = "SELECT p.*, u.nombre as pujador 
                  FROM pujas p 
                  JOIN usuarios u ON p.usuario_id = u.id 
                  WHERE p.subasta_id = $subasta_id 
                  ORDER BY p.monto DESC";
$historial = mysqli_query($conexion, $sql_historial);
$total_pujas = mysqli_num_rows($historial);

// Iconos por categoría
$iconos = [
    'Ganado' => '',
    'Cosechas' => '', 
    'Maquinaria' => '',
    'Herramientas' => '',
    'Insumos' => '',
    'Otros' => ''
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participar en Subasta | Tierra de Subastas 2026</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="logo"> Tierra de Subastas 2026</div>
 <nav class="menu">
        <a href="index.php"> Inicio</a>
        <a href="subastas.php"> Subastas</a>
        <a href="publicar.php"> Publicar</a>
       <a href="certificacion.php"> Certificaciones</a>
         <a href="mis_compras.php">Mis Compras</a>
          <a href="mis_subastas.php">Mis Subastas</a> 
</nav>

        <?php if(isset($_SESSION['usuario'])): ?>
            <a href="perfil.php">👤 <?php echo $_SESSION['usuario']; ?></a>
            <a href="logout.php" onclick="return confirm('¿Cerrar sesión?')">🚪 Salir</a>
        <?php else: ?>
            <a href="login.php">🔐 Ingresar</a>
        <?php endif; ?>
    </nav>
</header>

<div class="participar-container">
    <!-- Columna izquierda: Info de la subasta -->
    <div class="info-subasta">
        <?php 
        $imagen = "img/producto.png";
        if(!empty($subasta['imagen']) && file_exists("img/" . $subasta['imagen'])){
            $imagen = "img/" . $subasta['imagen'];
        }
        ?>
        <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($subasta['producto']); ?>">
        
        <h2><?php echo htmlspecialchars($subasta['producto']); ?></h2>
        <div class="badge-categoria">
            <?php 
            $icono = $iconos[$subasta['categoria']] ?? '🏷️';
            echo $icono . ' ' . htmlspecialchars($subasta['categoria']);
            ?>
        </div>
        
        <div class="precio-actual">
            $<?php echo number_format($subasta['precio_actual']); ?>
        </div>
        
        <p><strong>📝 Descripción:</strong></p>
        <p><?php echo nl2br(htmlspecialchars($subasta['descripcion'] ?? 'Sin descripción')); ?></p>
        
        <div style="margin-top: 1rem;">
            <p>👤 Vendedor: <?php echo htmlspecialchars($subasta['vendedor']); ?></p>
            <p>📍 Ubicación: <?php echo htmlspecialchars($subasta['ubicacion'] ?? 'No especificada'); ?></p>
            <p>⏱️ Duración: <?php echo htmlspecialchars($subasta['duracion'] ?? '3 días'); ?></p>
            <p>📅 Publicada: <?php echo date('d/m/Y', strtotime($subasta['fecha_creacion'])); ?></p>
        </div>
    </div>
    
    <!-- Columna derecha: Formulario de puja -->
    <div class="form-puja">
        <h3>💰 Hacer una puja</h3>
        
        <?php if($error): ?>
            <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($subasta['usuario_id'] == $usuario_id): ?>
            <div class="error-msg">
                ⚠️ No puedes pujar en tu propia subasta
            </div>
        <?php elseif($subasta['estado'] != 'activa'): ?>
            <div class="error-msg">
                ⚠️ Esta subasta ya finalizó
            </div>
        <?php else: ?>
            <form method="POST">
                <label>💰 Precio actual: <strong>$<?php echo number_format($subasta['precio_actual']); ?></strong></label>
                <label>📈 Incremento mínimo: <strong>$50,000 COP</strong></label>
                
                <input type="number" name="monto" class="monto-input" 
                       placeholder="Ingresa tu puja" 
                       min="<?php echo $subasta['precio_actual'] + 50000; ?>" 
                       step="50000" required>
                
                <button type="submit" name="pujar" class="btn-pujar">
                    🚀 Enviar Puja
                </button>
            </form>
        <?php endif; ?>
        
        <!-- Historial de pujas -->
        <div style="margin-top: 2rem;">
            <h4>📊 Historial de pujas (<?php echo $total_pujas; ?>)</h4>
            <div class="historial">
                <?php if($total_pujas > 0): ?>
                    <?php while($puja = mysqli_fetch_assoc($historial)): ?>
                        <div class="puja-item">
                            <strong>👤 <?php echo htmlspecialchars($puja['pujador']); ?></strong>
                            <span style="color:#2ecc71;">pujó $<?php echo number_format($puja['monto']); ?></span>
                            <small><?php echo date('d/m/Y H:i', strtotime($puja['fecha_puja'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="sin-pujas">No hay pujas aún. ¡Sé el primero!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Participa y gana</p>
</footer>

</body>
</html>