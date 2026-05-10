<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php?error=Debes iniciar sesión");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$mensaje = "";
$error = "";

// ============================================
// SUBIR CERTIFICADO A UN PRODUCTO EXISTENTE
// ============================================
if(isset($_POST['subir_certificado'])){
    $subasta_id = intval($_POST['subasta_id']);
    
    // Verificar que la subasta pertenece al usuario
    $check_sql = "SELECT id, producto FROM subastas WHERE id = $subasta_id AND usuario_id = $usuario_id";
    $check_result = mysqli_query($conexion, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0){
        $subasta = mysqli_fetch_assoc($check_result);
        
        if(isset($_FILES['certificado']) && $_FILES['certificado']['error'] === 0){
            $extension = strtolower(pathinfo($_FILES['certificado']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if(in_array($extension, $allowed)){
                $carpeta = __DIR__ . '/uploads/certificados/';
                if(!is_dir($carpeta)){
                    mkdir($carpeta, 0777, true);
                }
                
                $nombre_archivo = 'cert_' . $subasta_id . '_' . time() . '.' . $extension;
                
                if(move_uploaded_file($_FILES['certificado']['tmp_name'], $carpeta . $nombre_archivo)){
                    // Verificar si ya tiene certificado
                    $check_cert = mysqli_query($conexion, "SELECT id FROM certificaciones WHERE subasta_id = $subasta_id");
                    if(mysqli_num_rows($check_cert) > 0){
                        // Actualizar certificado existente
                        mysqli_query($conexion, "UPDATE certificaciones SET archivo = '$nombre_archivo' WHERE subasta_id = $subasta_id");
                        $mensaje = "✅ Certificado actualizado para: " . $subasta['producto'];
                    } else {
                        // Insertar nuevo certificado
                        mysqli_query($conexion, "INSERT INTO certificaciones (subasta_id, archivo) VALUES ($subasta_id, '$nombre_archivo')");
                        $mensaje = "✅ Certificado agregado a: " . $subasta['producto'];
                    }
                } else {
                    $error = "❌ Error al subir el archivo";
                }
            } else {
                $error = "❌ Formato no permitido. Usa: PDF, JPG, PNG";
            }
        } else {
            $error = "❌ Selecciona un archivo para subir";
        }
    } else {
        $error = "❌ Producto no encontrado o no te pertenece";
    }
}

// ============================================
// ELIMINAR CERTIFICADO
// ============================================
if(isset($_GET['eliminar'])){
    $subasta_id = intval($_GET['eliminar']);
    
    // Verificar propiedad
    $check_sql = "SELECT s.id, c.archivo FROM subastas s 
                  JOIN certificaciones c ON s.id = c.subasta_id 
                  WHERE s.id = $subasta_id AND s.usuario_id = $usuario_id";
    $check_result = mysqli_query($conexion, $check_sql);
    
    if(mysqli_num_rows($check_result) > 0){
        $cert = mysqli_fetch_assoc($check_result);
        
        // Eliminar archivo físico
        $ruta_archivo = __DIR__ . '/uploads/certificados/' . $cert['archivo'];
        if(file_exists($ruta_archivo)){
            unlink($ruta_archivo);
        }
        
        // Eliminar de la base de datos
        mysqli_query($conexion, "DELETE FROM certificaciones WHERE subasta_id = $subasta_id");
        $mensaje = "✅ Certificado eliminado correctamente";
    } else {
        $error = "❌ No puedes eliminar este certificado";
    }
}

// ============================================
// OBTENER PRODUCTOS DEL USUARIO
// ============================================
$sql_productos = "SELECT s.id, s.producto, s.precio_inicial, s.imagen, s.estado,
                         c.archivo as certificado
                  FROM subastas s
                  LEFT JOIN certificaciones c ON s.id = c.subasta_id
                  WHERE s.usuario_id = $usuario_id
                  ORDER BY s.id DESC";
$productos = mysqli_query($conexion, $sql_productos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Certificados | Tierra de Subastas</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .cert-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .producto-cert-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-left: 5px solid #32cd32;
        }
        
        .producto-info {
            flex: 2;
        }
        
        .producto-info h3 {
            color: #8b4513;
            margin-bottom: 0.3rem;
        }
        
        .producto-info small {
            color: #666;
        }
        
        .cert-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .badge-cert {
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .badge-no-cert {
            background: #fff3cd;
            color: #856404;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .btn-subir {
            background: #32cd32;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-ver {
            background: #1e90ff;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-eliminar-cert {
            background: #ff4500;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
        }
        
        @media (max-width: 768px) {
            .producto-cert-card {
                flex-direction: column;
                text-align: center;
            }
            .cert-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo">
        <div class="logo-texto">
            <h2>AgroBid</h2>
            <span>MIS CERTIFICADOS</span>
        </div>
    </div>
    <nav class="menu">
  
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

<div class="cert-container">
    <!-- Banner -->
    <div style="background: linear-gradient(135deg, #1e90ff10, #32cd3210); border-radius: 20px; padding: 1.5rem; text-align: center; margin-bottom: 2rem;">
        <div style="font-size: 2.5rem;">📄✅</div>
        <h2 style="color: #8b4513;">Mis Certificados</h2>
        <p>Administra los certificados de tus productos</p>
    </div>

    <!-- Mensajes -->
    <?php if($mensaje): ?>
        <div class="mensaje-exito" style="margin-bottom: 1rem; padding: 1rem; background: #d4edda; border-radius: 12px; color: #155724;">
            <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alerta-error" style="margin-bottom: 1rem; padding: 1rem; background: #f8d7da; border-radius: 12px; color: #721c24;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Lista de productos -->
    <h3 style="color: #8b4513; margin-bottom: 1rem;">📦 Mis Productos</h3>
    
    <?php if(mysqli_num_rows($productos) > 0): ?>
        <?php while($producto = mysqli_fetch_assoc($productos)): ?>
            <div class="producto-cert-card">
                <div class="producto-info">
                    <h3><?php echo htmlspecialchars($producto['producto']); ?></h3>
                    <small>💰 $<?php echo number_format($producto['precio_inicial']); ?></small>
                    <small> | 📍 Estado: <?php echo $producto['estado']; ?></small>
                    <div style="margin-top: 0.5rem;">
                        <?php if($producto['certificado']): ?>
                            <span class="badge-cert">
                                <i class="fas fa-check-circle"></i> Certificado adjunto
                            </span>
                        <?php else: ?>
                            <span class="badge-no-cert">
                                <i class="fas fa-clock"></i> Sin certificado
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cert-actions">
                    <?php if($producto['certificado']): ?>
                        <a href="uploads/certificados/<?php echo $producto['certificado']; ?>" target="_blank" class="btn-ver">
                            <i class="fas fa-eye"></i> Ver certificado
                        </a>
                        <a href="?eliminar=<?php echo $producto['id']; ?>" class="btn-eliminar-cert" onclick="return confirm('¿Eliminar este certificado?')">
                            <i class="fas fa-trash"></i> Eliminar
                        </a>
                    <?php endif; ?>
                    <button class="btn-subir" onclick="abrirModal(<?php echo $producto['id']; ?>, '<?php echo addslashes($producto['producto']); ?>')">
                        <i class="fas fa-upload"></i> <?php echo $producto['certificado'] ? 'Actualizar' : 'Subir'; ?> certificado
                    </button>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="sin-datos" style="text-align: center; padding: 3rem; background: white; border-radius: 20px;">
            <p>📭 Aún no has publicado ningún producto</p>
            <a href="publicar.php" class="btn-principal" style="display: inline-block; margin-top: 1rem;">+ Publicar producto</a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para subir certificado -->
<div id="modalCert" class="modal">
    <div class="modal-content">
        <h3 style="color: #8b4513; margin-bottom: 1rem;">📄 Subir certificado</h3>
        <form method="POST" enctype="multipart/form-data" id="formCertificado">
            <input type="hidden" name="subasta_id" id="modal_subasta_id">
            <p id="modal_producto_nombre" style="margin-bottom: 1rem; font-weight: bold;"></p>
            <div class="grupo-form">
                <label>Archivo del certificado</label>
                <input type="file" name="certificado" accept=".pdf,.jpg,.jpeg,.png" required style="width:100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 8px;">
                <small>Formatos permitidos: PDF, JPG, PNG (máx 5MB)</small>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button type="submit" name="subir_certificado" class="btn-principal" style="flex:1;">📤 Subir</button>
                <button type="button" class="btn-secundario" onclick="cerrarModal()" style="flex:1;">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<footer class="footer">
    <p>✅ Administra los certificados de tus productos | Tierra de Subastas 2026</p>
</footer>

<script>
    function abrirModal(subasta_id, producto_nombre) {
        document.getElementById('modal_subasta_id').value = subasta_id;
        document.getElementById('modal_producto_nombre').innerHTML = 'Producto: <strong>' + producto_nombre + '</strong>';
        document.getElementById('modalCert').style.display = 'flex';
    }
    
    function cerrarModal() {
        document.getElementById('modalCert').style.display = 'none';
    }
    
    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('modalCert');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>