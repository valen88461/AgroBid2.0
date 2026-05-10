<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

// Verificar si es administrador (puedes ajustar según tu lógica)
if(!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin'){
    header("Location: login.php?error=Acceso denegado");
    exit();
}

$mensaje = "";
$error = "";

// Aprobar certificación
if(isset($_GET['aprobar'])){
    $id = intval($_GET['aprobar']);
    $tipo = $_GET['tipo']; // 'perfil' o 'producto'
    
    if($tipo == 'perfil'){
        $sql = "UPDATE solicitudes_certificacion SET estado = 'aprobada', revisado_por = 1, fecha_revision = NOW() WHERE id = $id";
    } else {
        $sql = "UPDATE certificaciones_producto SET estado = 'verificada', verificado_por = 1, fecha_verificacion = NOW() WHERE id = $id";
    }
    
    if(mysqli_query($conexion, $sql)){
        $mensaje = "✅ Certificación aprobada correctamente";
    } else {
        $error = "Error al aprobar";
    }
}

// Rechazar certificación
if(isset($_GET['rechazar'])){
    $id = intval($_GET['rechazar']);
    $tipo = $_GET['tipo'];
    
    if($tipo == 'perfil'){
        $sql = "UPDATE solicitudes_certificacion SET estado = 'rechazada', revisado_por = 1, fecha_revision = NOW() WHERE id = $id";
    } else {
        $sql = "UPDATE certificaciones_producto SET estado = 'rechazada', verificado_por = 1, fecha_verificacion = NOW() WHERE id = $id";
    }
    
    if(mysqli_query($conexion, $sql)){
        $mensaje = "❌ Certificación rechazada";
    } else {
        $error = "Error al rechazar";
    }
}

// Obtener solicitudes pendientes de perfil
$sql_solicitudes = "SELECT sc.*, u.nombre as usuario_nombre, u.correo, tc.nombre as tipo_nombre, tc.icono
                    FROM solicitudes_certificacion sc
                    JOIN usuarios u ON sc.usuario_id = u.id
                    JOIN tipos_certificacion tc ON sc.tipo_certificacion_id = tc.id
                    WHERE sc.estado = 'pendiente'
                    ORDER BY sc.created_at DESC";
$solicitudes = mysqli_query($conexion, $sql_solicitudes);

// Obtener certificaciones de productos pendientes
$sql_productos = "SELECT cp.*, u.nombre as usuario_nombre, s.producto, tc.nombre as tipo_nombre, tc.icono
                  FROM certificaciones_producto cp
                  JOIN usuarios u ON cp.usuario_id = u.id
                  JOIN subastas s ON cp.subasta_id = s.id
                  JOIN tipos_certificacion tc ON cp.tipo_certificacion_id = tc.id
                  WHERE cp.estado = 'pendiente'
                  ORDER BY cp.created_at DESC";
$productos_pendientes = mysqli_query($conexion, $sql_productos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Certificaciones | Tierra de Subastas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .admin-panel {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .cert-item {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .btn-aprobar {
            background: #32cd32;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-rechazar {
            background: #ff4500;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<header class="header">
    <div class="logo"><h2>🌾 Admin Certificaciones</h2></div>
    <nav class="menu">
        <a href="index.php">Inicio</a>
        <a href="admin_certificaciones.php">Certificaciones</a>
        <a href="logout.php">Salir</a>
    </nav>
</header>

<div class="admin-panel">
    <h2>✅ Solicitudes de Certificación de Perfil</h2>
    <?php if(mysqli_num_rows($solicitudes) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($solicitudes)): ?>
            <div class="cert-item">
                <div>
                    <strong><?php echo $row['icono']; ?> <?php echo $row['tipo_nombre']; ?></strong>
                    <div><?php echo $row['usuario_nombre']; ?> (<?php echo $row['correo']; ?>)</div>
                    <small>Documento: <a href="uploads/certificaciones/<?php echo $row['documento_url']; ?>" target="_blank">Ver archivo</a></small>
                    <?php if($row['comentarios']): ?>
                        <div><small>📝 <?php echo $row['comentarios']; ?></small></div>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="?aprobar=<?php echo $row['id']; ?>&tipo=perfil" class="btn-aprobar">✅ Aprobar</a>
                    <a href="?rechazar=<?php echo $row['id']; ?>&tipo=perfil" class="btn-rechazar" onclick="return confirm('¿Rechazar esta certificación?')">❌ Rechazar</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No hay solicitudes pendientes</p>
    <?php endif; ?>

    <h2 style="margin-top: 2rem;">📦 Certificaciones de Productos Pendientes</h2>
    <?php if(mysqli_num_rows($productos_pendientes) > 0): ?>
        <?php while($row = mysqli_fetch_assoc($productos_pendientes)): ?>
            <div class="cert-item">
                <div>
                    <strong><?php echo $row['icono']; ?> <?php echo $row['tipo_nombre']; ?></strong>
                    <div>Producto: <?php echo $row['producto']; ?></div>
                    <div>Vendedor: <?php echo $row['usuario_nombre']; ?></div>
                    <div>Nº Certificado: <?php echo $row['numero_certificado']; ?></div>
                    <small>Válido: <?php echo $row['fecha_emision']; ?> hasta <?php echo $row['fecha_vencimiento']; ?></small>
                </div>
                <div>
                    <a href="?aprobar=<?php echo $row['id']; ?>&tipo=producto" class="btn-aprobar">✅ Verificar</a>
                    <a href="?rechazar=<?php echo $row['id']; ?>&tipo=producto" class="btn-rechazar" onclick="return confirm('¿Rechazar esta certificación?')">❌ Rechazar</a>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No hay certificaciones de productos pendientes</p>
    <?php endif; ?>
</div>
</body>
</html>