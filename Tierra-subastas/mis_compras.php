<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$mensaje = "";
$error = "";

// Marcar como entregado
if(isset($_GET['entregar'])){
    $subasta_id = intval($_GET['entregar']);
    
    $update = "UPDATE subastas SET entregado = 1 WHERE id = $subasta_id AND ganador_id = $usuario_id";
    if(mysqli_query($conexion, $update)){
        header("Location: mis_compras.php?mensaje=✅ Producto marcado como entregado. ¡Ahora puedes calificarlo!");
        exit();
    } else {
        $error = "❌ Error al marcar como entregado";
    }
}

// Obtener mensaje de URL
if(isset($_GET['mensaje'])){
    $mensaje = $_GET['mensaje'];
}

// Obtener mis compras
$sql = "SELECT s.*, u.nombre as vendedor_nombre 
        FROM subastas s
        JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.ganador_id = $usuario_id
        ORDER BY s.id DESC";
$compras = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras | Tierra de Subastas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        .mis-compras {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .compra-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #32cd32;
        }
        .compra-info {
            flex: 2;
        }
        .compra-info h3 {
            color: #8b4513;
            margin-bottom: 0.3rem;
        }
        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .badge-entregado {
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .badge-calificado {
            background: #cce5ff;
            color: #004085;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .btn-entregar {
            background: #1e90ff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-block;
        }
        .btn-calificar {
            background: #32cd32;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-block;
        }
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        .precio {
            font-weight: bold;
            color: #ff8c00;
        }
        .estado-label {
            margin-top: 0.5rem;
        }
        @media (max-width: 768px) {
            .compra-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo">🌾 AgroBid</div>
    <nav class="menu">
        <a href="index.php">Inicio</a>
        <a href="subastas.php">Subastas</a>
        <a href="publicar.php">Publicar</a>
        <a href="mis_subastas.php">Mis Subastas</a>
        <a href="mis_compras.php">Mis Compras</a>
        <a href="perfil.php">Mi Perfil</a>
        <a href="logout.php">Salir</a>
    </nav>
</header>

<div class="mis-compras">
    <h2>📦 Mis Compras</h2>
    
    <?php if($mensaje): ?>
        <div class="mensaje-exito"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    
    <?php if(mysqli_num_rows($compras) > 0): ?>
        <?php while($compra = mysqli_fetch_assoc($compras)): ?>
            <div class="compra-card">
                <div class="compra-info">
                    <h3>📦 <?php echo htmlspecialchars($compra['producto']); ?></h3>
                    <p>👤 Vendedor: <strong><?php echo htmlspecialchars($compra['vendedor_nombre']); ?></strong></p>
                    <p class="precio">💰 Pagado: <strong>$<?php echo number_format($compra['monto_final'] ?? $compra['precio_actual'] ?? $compra['precio_inicial']); ?></strong></p>
                    
                    <div class="estado-label">
                        <?php if($compra['entregado'] == 0): ?>
                            <span class="badge-pendiente">⏳ Pendiente de entrega</span>
                            <p style="font-size: 0.7rem; color: #666; margin-top: 0.3rem;">Espera a que el vendedor te entregue el producto</p>
                        <?php elseif($compra['entregado'] == 1 && $compra['calificado'] == 0): ?>
                            <span class="badge-entregado">✅ Producto entregado</span>
                            <p style="font-size: 0.7rem; color: #32cd32; margin-top: 0.3rem;">¡Ya puedes calificar este producto!</p>
                        <?php elseif($compra['calificado'] == 1): ?>
                            <span class="badge-calificado">⭐ Producto calificado</span>
                            <p style="font-size: 0.7rem; color: #666; margin-top: 0.3rem;">Gracias por tu opinión</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="compra-actions">
                    <?php if($compra['entregado'] == 0): ?>
                        <a href="?entregar=<?php echo $compra['id']; ?>" class="btn-entregar" onclick="return confirm('¿Confirmas que ya recibiste este producto en buen estado?')">
                            ✅ Marcar como entregado
                        </a>
                    <?php elseif($compra['entregado'] == 1 && $compra['calificado'] == 0): ?>
                        <a href="calificar.php?id=<?php echo $compra['id']; ?>" class="btn-calificar" style="background: #ffd700; color: #8b4513; font-weight: bold;">
                            ⭐⭐⭐⭐⭐ Calificar producto
                        </a>
                    <?php elseif($compra['calificado'] == 1): ?>
                        <span class="badge-calificado">⭐ Ya calificado</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="sin-datos" style="text-align: center; padding: 3rem; background: white; border-radius: 16px;">
            <p>📭 Aún no has ganado ninguna subasta</p>
            <p style="font-size: 0.8rem;">Cuando un vendedor te seleccione como ganador, aparecerá aquí</p>
            <a href="subastas.php" class="btn-principal" style="display: inline-block; margin-top: 1rem;">Ver subastas activas</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>