<?php
include(__DIR__ . "/includes/conexion.php");
session_start();

$sql = "SELECT s.*, u.nombre as vendedor
        FROM subastas s
        LEFT JOIN usuarios u ON s.usuario_id = u.id
        WHERE s.estado = 'activa' OR s.estado IS NULL
        ORDER BY s.id DESC";

$resultado = mysqli_query($conexion, $sql);

$iconos = [
    'Ganado' => '🐄',
    'Cosechas' => '🌽',
    'Maquinaria' => '🚜',
    'Herramientas' => '🔧',
    'Insumos' => '💧',
    'Otros' => '📦'
];

$imagen_default = "img/vaca.png";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subastas | Tierra de Subastas 2026</title>
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
        <?php if(isset($_SESSION['usuario'])): ?>
            <a href="perfil.php">👤 <?php echo $_SESSION['usuario']; ?></a>
            <a href="logout.php" onclick="return confirm('¿Cerrar sesión?')">🚪 Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php"> Ingresar</a>
        <?php endif; ?>
    </nav>
</header>

<section class="productos">
    <div class="titulo-seccion">
        <h2> Subastas Activas</h2>
        <p>Encuentra los mejores productos del campo colombiano</p>
    </div>

    <div class="grid">
        <?php if(mysqli_num_rows($resultado) > 0): ?>
            <?php while($fila = mysqli_fetch_assoc($resultado)): ?>
                <?php
                // Mostrar imagen del producto
                $imagen = $imagen_default;
                if(!empty($fila['imagen'])){
                    $ruta_imagen = "img/" . $fila['imagen'];
                    if(file_exists($ruta_imagen)){
                        $imagen = $ruta_imagen;
                    }
                }
                ?>
                <div class="card">
                    <!-- Imagen del producto -->
                    <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($fila['producto']); ?>">
                    
                    <!-- Nombre del producto -->
                    <h3><?php echo htmlspecialchars($fila['producto']); ?></h3>
                    
                    <!-- Categoría -->
                    <div class="badge-categoria">
                        <?php 
                        $categoria = $fila['categoria'] ?? 'Otros';
                        $icono = $iconos[$categoria] ?? '';
                        echo $icono . ' ' . htmlspecialchars($categoria);
                        ?>
                    </div>
                    
                    <!-- Precio -->
                    <div class="precio">
                        $<?php echo number_format($fila['precio_actual'] ?? $fila['precio_inicial']); ?>
                    </div>
                    
                    <!-- Código de la subasta -->
                    <div class="codigo-subasta">
                         Código: #SUB-<?php echo str_pad($fila['id'], 6, '0', STR_PAD_LEFT); ?>
                    </div>
                    
                    <!-- Info extra -->
                    <div class="info-extra">
                        <span> <?php echo htmlspecialchars($fila['entrega'] ?? 'A convenir'); ?></span>
                        <span> <?php echo htmlspecialchars($fila['duracion'] ?? '3 días'); ?></span>
                    </div>
                    
                    <!-- Ubicación -->
                    <?php if(!empty($fila['ubicacion'])): ?>
                        <div class="ubicacion"> <?php echo htmlspecialchars($fila['ubicacion']); ?></div>
                    <?php endif; ?>
                    
                    <!-- Vendedor -->
                    <div class="vendedor">
                        <?php if(isset($fila['vendedor']) && !empty($fila['vendedor'])): ?>
                            👤 Vendedor: <?php echo htmlspecialchars($fila['vendedor']); ?>
                        <?php else: ?>
                            👤 Vendedor: Usuario #<?php echo $fila['usuario_id']; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- ============================================= -->
                    <!-- MOSTRAR CERTIFICADO SI EXISTE -->
                    <!-- ============================================= -->
                    <?php
                    $cert_sql = "SELECT archivo FROM certificaciones WHERE subasta_id = " . $fila['id'];
                    $cert_result = mysqli_query($conexion, $cert_sql);
                    if(mysqli_num_rows($cert_result) > 0):
                        $cert = mysqli_fetch_assoc($cert_result);
                    ?>
                        <div style="margin: 0.5rem 1rem;">
                            <a href="uploads/certificados/<?php echo $cert['archivo']; ?>" target="_blank" style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.3rem 0.8rem; background: #f0f8ff; border-radius: 20px; text-decoration: none; color: #1e90ff; font-size: 0.7rem; border: 1px solid #1e90ff;">
                                📄 Ver certificado
                            </a>
                        </div>
                    <?php endif; ?>
                    <!-- ============================================= -->
                    
                    <!-- Botones -->
                    <div class="botones-card">
                        <a href="participar.php?id=<?php echo $fila['id']; ?>" class="btn-ofertar">💰 Participar</a>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $fila['usuario_id']): ?>
                            <a href="eliminar.php?id=<?php echo $fila['id']; ?>" class="btn-eliminar" onclick="return confirm('¿Eliminar esta subasta?')">🗑️ Eliminar</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="sin-subastas">
                <p> No hay subastas disponibles en este momento</p>
                <?php if(isset($_SESSION['usuario'])): ?>
                    <a href="publicar.php" class="btn-publicar-rapido">+ Publicar subasta</a>
                <?php else: ?>
                    <a href="login.php" class="btn-publicar-rapido">🔐 Iniciar sesión</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Conectamos el campo colombiano</p>
    <div class="footer-usuario">
        <?php if(isset($_SESSION['usuario'])): ?>
            👤 Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?>
        <?php else: ?>
            <a href="login.php"> Inicia sesión</a> para participar en subastas
        <?php endif; ?>
    </div>
</footer>

</body>
</html>