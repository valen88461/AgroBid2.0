<?php
include(__DIR__ . "/includes/conexion.php");
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php?error=Debes iniciar sesión para ver tu perfil");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$nombre = $_SESSION['usuario'];
$correo = $_SESSION['correo'];

// Procesar actualización de perfil
$mensaje = "";
$error = "";

if(isset($_POST['actualizar_perfil'])){
    $nuevo_nombre = trim($_POST['nombre']);
    $nuevo_telefono = trim($_POST['telefono']);
    
    if(!empty($nuevo_nombre)){
        $sql_update = "UPDATE usuarios SET nombre = '$nuevo_nombre', telefono = '$nuevo_telefono' WHERE id = $usuario_id";
        if(mysqli_query($conexion, $sql_update)){
            $_SESSION['usuario'] = $nuevo_nombre;
            $mensaje = " Perfil actualizado correctamente";
        } else {
            $error = "Error al actualizar";
        }
    }
}

// Procesar subida de foto de perfil
if(isset($_POST['subir_foto'])){
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0){
        $extension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if(in_array($extension, $allowed)){
            $nombre_avatar = 'avatar_' . $usuario_id . '_' . time() . '.' . $extension;
            $ruta = __DIR__ . '/uploads/' . $nombre_avatar;
            
            if(!is_dir(__DIR__ . '/uploads')){
                mkdir(__DIR__ . '/uploads', 0777, true);
            }
            
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], $ruta)){
                $sql_avatar = "UPDATE usuarios SET avatar = '$nombre_avatar' WHERE id = $usuario_id";
                if(mysqli_query($conexion, $sql_avatar)){
                    $mensaje = " Foto de perfil actualizada correctamente";
                } else {
                    $error = "Error al guardar en la base de datos";
                }
            } else {
                $error = "Error al subir la imagen";
            }
        } else {
            $error = "Formato no permitido. Usa: JPG, PNG, GIF, WEBP";
        }
    } else {
        $error = "Selecciona una imagen";
    }
}

// Obtener datos actualizados del usuario
$sql_usuario = "SELECT * FROM usuarios WHERE id = $usuario_id";
$result_usuario = mysqli_query($conexion, $sql_usuario);
$usuario = mysqli_fetch_assoc($result_usuario);

// Obtener subastas publicadas
$sql_mis_subastas = "SELECT * FROM subastas WHERE usuario_id = $usuario_id ORDER BY id DESC";
$mis_subastas = mysqli_query($conexion, $sql_mis_subastas);
$total_mis_subastas = mysqli_num_rows($mis_subastas);

// Obtener pujas realizadas
$sql_mis_pujas = "SELECT s.*, p.monto, p.fecha_puja 
                  FROM pujas p 
                  JOIN subastas s ON p.subasta_id = s.id 
                  WHERE p.usuario_id = $usuario_id 
                  ORDER BY p.fecha_puja DESC";
$mis_pujas = mysqli_query($conexion, $sql_mis_pujas);
$total_mis_pujas = mysqli_num_rows($mis_pujas);

// Obtener subastas ganadas
$sql_ganadas = "SELECT * FROM subastas WHERE ganador_id = $usuario_id ORDER BY id DESC";
$ganadas = mysqli_query($conexion, $sql_ganadas);
$total_ganadas = mysqli_num_rows($ganadas);

// Obtener avatar
$avatar = !empty($usuario['avatar']) ? $usuario['avatar'] : 'default-avatar.png';
$ruta_avatar = "uploads/" . $avatar;
if(!file_exists($ruta_avatar)){
    $ruta_avatar = "img/default-avatar.png";
}

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
    <title>Mi Perfil | Tierra de Subastas 2026</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="header">
    <div class="logo">🌾 Tierra de Subastas 2026</div>
    <nav class="menu">
        <a href="index.php">🌾 Inicio</a>
        <a href="subastas.php"> Subastas</a>
        <a href="publicar.php"> Publicar</a>
        <a href="perfil.php"> Mi Perfil</a>
       <a href="mis_compras.php">Mis Compras</a>
        <a href="logout.php" onclick="return confirm('¿Cerrar sesión?')">🚪 Cerrar sesión</a>
    </nav>
</header>

<div class="perfil-container">
    <!-- Header del perfil con avatar -->
    <div class="perfil-header">
        <div class="avatar-container">
            <img src="<?php echo $ruta_avatar; ?>" alt="Avatar" class="avatar-img" id="avatarPreview">
            <form method="POST" enctype="multipart/form-data" id="formFoto" style="margin-top: 10px;">
                <label for="avatarInput" class="btn-cambiar-foto">📸 Cambiar foto</label>
                <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display:none;" onchange="document.getElementById('formFoto').submit();">
                <input type="hidden" name="subir_foto" value="1">
            </form>
        </div>
        
        <div class="info-usuario">
            <h2><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
            <p> <?php echo htmlspecialchars($correo); ?></p>
            <p> <?php echo htmlspecialchars($usuario['telefono'] ?? 'No especificado'); ?></p>
            
            <div class="reputacion">
                ⭐ Reputación: 
                <?php 
                $reputacion = $usuario['reputacion'] ?? 5.0;
                $estrellas_llenas = floor($reputacion);
                echo str_repeat('★', $estrellas_llenas) . str_repeat('☆', 5 - $estrellas_llenas);
                ?> (<?php echo number_format($reputacion, 1); ?> / 5.0)
            </div>
            <p>📊 <?php echo $usuario['total_calificaciones'] ?? 0; ?> calificaciones recibidas</p>
            
            <div class="stats-perfil">
                <div class="stat-perfil">
                    <div class="numero"><?php echo $total_mis_subastas; ?></div>
                    <div class="label">Subastas publicadas</div>
                </div>
                <div class="stat-perfil">
                    <div class="numero"><?php echo $total_mis_pujas; ?></div>
                    <div class="label">Pujas realizadas</div>
                </div>
                <div class="stat-perfil">
                    <div class="numero"><?php echo $total_ganadas; ?></div>
                    <div class="label">Subastas ganadas</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Editar perfil -->
    <div class="seccion-perfil">
        <h3>✏️ Editar Perfil</h3>
        
        <?php if($mensaje): ?>
            <div class="mensaje-exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-grupo">
                <label>👤 Nombre completo</label>
                <input type="text" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
            </div>
            
            <div class="form-grupo">
                <label> Teléfono</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
            </div>
            
            <div class="form-grupo">
                <label> Correo electrónico</label>
                <input type="email" value="<?php echo htmlspecialchars($correo); ?>" disabled style="opacity:0.7;">
                <small>El correo no se puede cambiar</small>
            </div>
            
            <button type="submit" name="actualizar_perfil" class="btn-guardar">💾 Guardar cambios</button>
        </form>
    </div>
    
    <!-- Mis Subastas Publicadas -->
    <div class="seccion-perfil">
        <h3>🚜 Mis Subastas Publicadas</h3>
        <div class="lista-subastas">
            <?php if($total_mis_subastas > 0): ?>
                <?php while($subasta = mysqli_fetch_assoc($mis_subastas)): ?>
                    <div class="subasta-item">
                        <h4><?php echo htmlspecialchars($subasta['producto']); ?></h4>
                        <div class="badge-categoria">
                            <?php 
                            $icono = $iconos[$subasta['categoria']] ?? '🏷️';
                            echo $icono . ' ' . htmlspecialchars($subasta['categoria']);
                            ?>
                        </div>
                        <div class="precio">$<?php echo number_format($subasta['precio_actual'] ?? $subasta['precio_inicial']); ?></div>
                        <div>
                            <span class="badge-estado <?php echo ($subasta['estado'] == 'activa' ? 'estado-activa' : 'estado-finalizada'); ?>">
                                <?php echo ($subasta['estado'] == 'activa' ? '🟢 Activa' : '⚫ Finalizada'); ?>
                            </span>
                        </div>
                        <a href="subastas.php" class="btn-ver">Ver detalles →</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="sin-datos">
                    <p>📭 Aún no has publicado ninguna subasta</p>
                    <a href="publicar.php">+ Publicar ahora</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mis Pujas Realizadas -->
    <div class="seccion-perfil">
        <h3>💰 Mis Pujas Realizadas</h3>
        <div class="lista-subastas">
            <?php if($total_mis_pujas > 0): ?>
                <?php while($puja = mysqli_fetch_assoc($mis_pujas)): ?>
                    <div class="subasta-item">
                        <h4><?php echo htmlspecialchars($puja['producto']); ?></h4>
                        <div class="badge-categoria">
                            <?php 
                            $icono = $iconos[$puja['categoria']] ?? '🏷️';
                            echo $icono . ' ' . htmlspecialchars($puja['categoria']);
                            ?>
                        </div>
                        <div class="precio">Mi puja: $<?php echo number_format($puja['monto']); ?></div>
                        <div>Precio actual: $<?php echo number_format($puja['precio_actual'] ?? $puja['precio_inicial']); ?></div>
                        <div class="info-extra">📅 <?php echo date('d/m/Y', strtotime($puja['fecha_puja'])); ?></div>
                        <a href="subastas.php" class="btn-ver">Ver subasta →</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="sin-datos">
                    <p>📭 Aún no has realizado ninguna puja</p>
                    <a href="subastas.php">Ver subastas activas</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Subastas Ganadas -->
    <div class="seccion-perfil">
        <h3>🏆 Subastas Ganadas</h3>
        <div class="lista-subastas">
            <?php if($total_ganadas > 0): ?>
                <?php while($ganada = mysqli_fetch_assoc($ganadas)): ?>
                    <div class="subasta-item">
                        <h4><?php echo htmlspecialchars($ganada['producto']); ?></h4>
                        <div class="badge-categoria">
                            <?php 
                            $icono = $iconos[$ganada['categoria']] ?? '🏷️';
                            echo $icono . ' ' . htmlspecialchars($ganada['categoria']);
                            ?>
                        </div>
                        <div class="precio">Ganada por: $<?php echo number_format($ganada['monto_final']); ?></div>
                        
                        <?php if($ganada['entregado'] == 0): ?>
                            <div><span class="badge-estado" style="background: #ffaa00; color:#000;">⏳ Pendiente de entrega</span></div>
                        <?php elseif($ganada['entregado'] == 1 && $ganada['calificado'] == 0): ?>
                            <div><span class="badge-estado" style="background: #2ecc71;">✅ Entregado - Pendiente calificar</span></div>
                            <div style="margin-top: 10px;">
                                <a href="calificar.php" class="btn-ver" style="background:#2ecc71; padding:5px 10px; border-radius:5px; color:white; display:inline-block;">⭐ Calificar vendedor</a>
                            </div>
                        <?php elseif($ganada['entregado'] == 1 && $ganada['calificado'] == 1): ?>
                            <div><span class="badge-estado" style="background: #27ae60;">✅ Completado</span></div>
                        <?php endif; ?>
                        
                        <a href="subastas.php" class="btn-ver">Ver detalles →</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="sin-datos">
                    <p>🏆 Aún no has ganado ninguna subasta</p>
                    <a href="subastas.php">¡Participa y gana!</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Tu perfil, tu reputación</p>
</footer>

<script>
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        const preview = document.getElementById('avatarPreview');
        const file = e.target.files[0];
        if(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>