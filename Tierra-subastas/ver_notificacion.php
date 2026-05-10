<?php
include("includes/conexion.php");
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Marcar todas las notificaciones como leídas
mysqli_query($conexion, "UPDATE notificaciones SET leido = 1 WHERE usuario_id = $user_id");

// Obtener todas las notificaciones
$sql = "SELECT * FROM notificaciones WHERE usuario_id = $user_id ORDER BY fecha DESC";
$resultado = mysqli_query($conexion, $sql);
$total_notif = mysqli_num_rows($resultado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Notificaciones | Tierra de Subastas 2026</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        .notif-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }
        
        .notif-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .notif-header h2 {
            font-size: 2rem;
            background: linear-gradient(135deg, #ffffff, #a3ffa3);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .notif-header p {
            color: #8bc34a;
        }
        
        .notif-card {
            background: rgba(10, 25, 10, 0.6);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 3px solid #2ecc71;
            transition: all 0.3s;
        }
        
        .notif-card:hover {
            transform: translateX(5px);
            background: rgba(10, 25, 10, 0.8);
        }
        
        .notif-mensaje {
            font-size: 1rem;
            color: #e0f2e0;
        }
        
        .notif-fecha {
            font-size: 0.7rem;
            color: #8bc34a;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notif-tipo {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.6rem;
            margin-top: 5px;
        }
        
        .tipo-puja {
            background: rgba(46, 204, 113, 0.3);
            color: #a3ffa3;
        }
        
        .tipo-sistema {
            background: rgba(46, 204, 113, 0.2);
            color: #8bc34a;
        }
        
        .sin-notif {
            text-align: center;
            padding: 3rem;
            background: rgba(10, 25, 10, 0.6);
            border-radius: 24px;
        }
        
        .sin-notif p {
            margin-bottom: 1rem;
            color: #6c8b6c;
        }
        
        .btn-volver {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.8rem 1.5rem;
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.5);
            border-radius: 50px;
            color: #2ecc71;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-volver:hover {
            background: rgba(46, 204, 113, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-limpiar {
            display: inline-block;
            margin-left: 1rem;
            padding: 0.8rem 1.5rem;
            background: rgba(255, 50, 50, 0.2);
            border: 1px solid rgba(255, 50, 50, 0.5);
            border-radius: 50px;
            color: #ff8888;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-limpiar:hover {
            background: rgba(255, 50, 50, 0.3);
        }
    </style>
</head>
<body>

<header class="header">
   <nav class="menu">
    <div class="logo"> AgroBid</div>
    <a href="index.php">Inicio</a>
    <a href="subastas.php">Subastas</a>
    <a href="publicar.php">Publicar</a>
    <a href="mis_subastas.php">Mis Subastas</a>  <!-- NUEVO -->
    <a href="mis_compras.php">Mis Compras</a>
    <a href="perfil.php">Mi Perfil</a>
    <a href="logout.php">Salir</a>
</nav>
    </nav>
</header>

<div class="notif-container">
    <div class="notif-header">
        <h2>📬 Mis Notificaciones</h2>
        <p>Todas las alertas y novedades de tus subastas</p>
    </div>
    
    <?php if($total_notif > 0): ?>
        <?php while($notif = mysqli_fetch_assoc($resultado)): ?>
            <div class="notif-card">
                <div class="notif-mensaje">
                    <?php echo htmlspecialchars($notif['mensaje']); ?>
                </div>
                <div class="notif-tipo <?php echo ($notif['tipo'] == 'puja') ? 'tipo-puja' : 'tipo-sistema'; ?>">
                    <?php echo ($notif['tipo'] == 'puja') ? '💰 Puja' : '📢 Sistema'; ?>
                </div>
                <div class="notif-fecha">
                    📅 <?php echo date('d/m/Y H:i:s', strtotime($notif['fecha'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
        
        <div style="text-align: center;">
            <a href="subastas.php" class="btn-volver">← Volver a subastas</a>
            <a href="limpiar_notificaciones.php" class="btn-limpiar" onclick="return confirm('¿Eliminar todas las notificaciones?')">🗑️ Limpiar todo</a>
        </div>
        
    <?php else: ?>
        <div class="sin-notif">
            <p>📭 No tienes notificaciones</p>
            <p>Cuando alguien puje en tus subastas, aparecerán aquí.</p>
            <a href="subastas.php" class="btn-volver">← Ver subastas activas</a>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Mantente informado</p>
</footer>

</body>
</html>