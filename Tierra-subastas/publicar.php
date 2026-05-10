<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php?error=Debes iniciar sesión para publicar");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$error = "";

// Procesar formulario
if(isset($_POST['publicar'])){
    $producto = trim($_POST['producto']);
    $descripcion = trim($_POST['descripcion']);
    $categoria = $_POST['categoria'];
    $precio_inicial = floatval($_POST['precio_inicial']);
    $ubicacion = trim($_POST['ubicacion']);
    $entrega = trim($_POST['entrega']);
    $duracion = $_POST['duracion'];
    
    // Validaciones
    if(empty($producto) || empty($descripcion) || $precio_inicial <= 0){
        $error = "❌ Todos los campos obligatorios deben estar llenos";
    } else {
        // Calcular fecha_fin
        $fecha_fin = date('Y-m-d H:i:s', strtotime("+$duracion days"));
        
        // Escapar datos
        $producto = mysqli_real_escape_string($conexion, $producto);
        $descripcion = mysqli_real_escape_string($conexion, $descripcion);
        $ubicacion = mysqli_real_escape_string($conexion, $ubicacion);
        $entrega = mysqli_real_escape_string($conexion, $entrega);
        
        // =============================================
        // 1. SUBIR IMAGEN DEL PRODUCTO
        // =============================================
        $nombre_imagen = '';
        if(isset($_FILES['imagen_producto']) && $_FILES['imagen_producto']['error'] === 0){
            $extension = strtolower(pathinfo($_FILES['imagen_producto']['name'], PATHINFO_EXTENSION));
            $allowed_imagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if(in_array($extension, $allowed_imagen)){
                $carpeta_imagenes = __DIR__ . '/img/';
                $nombre_imagen = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
                
                if(move_uploaded_file($_FILES['imagen_producto']['tmp_name'], $carpeta_imagenes . $nombre_imagen)){
                    // Imagen subida correctamente
                } else {
                    $error = "❌ Error al subir la imagen del producto";
                }
            } else {
                $error = "❌ Formato de imagen no permitido. Usa: JPG, PNG, GIF, WEBP";
            }
        }
        
        // Si no hay error, insertar la subasta
        if(empty($error)){
            $sql = "INSERT INTO subastas (usuario_id, producto, descripcion, categoria, precio_inicial, ubicacion, entrega, fecha_fin, imagen, estado) 
                    VALUES ($usuario_id, '$producto', '$descripcion', '$categoria', $precio_inicial, '$ubicacion', '$entrega', '$fecha_fin', '$nombre_imagen', 'activa')";
            
            if(mysqli_query($conexion, $sql)){
                $subasta_id = mysqli_insert_id($conexion);
                
                // =============================================
                // 2. SUBIR CERTIFICADO (opcional)
                // =============================================
                if(isset($_FILES['certificado']) && $_FILES['certificado']['error'] === 0){
                    $extension_cert = strtolower(pathinfo($_FILES['certificado']['name'], PATHINFO_EXTENSION));
                    $allowed_cert = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if(in_array($extension_cert, $allowed_cert)){
                        $carpeta_certificados = __DIR__ . '/uploads/certificados/';
                        if(!is_dir($carpeta_certificados)){
                            mkdir($carpeta_certificados, 0777, true);
                        }
                        
                        $nombre_certificado = 'cert_' . $subasta_id . '_' . time() . '.' . $extension_cert;
                        
                        if(move_uploaded_file($_FILES['certificado']['tmp_name'], $carpeta_certificados . $nombre_certificado)){
                            mysqli_query($conexion, "INSERT INTO certificaciones (subasta_id, archivo) VALUES ($subasta_id, '$nombre_certificado')");
                        }
                    }
                }
                // =============================================
                
                echo "<script>alert('✅ Subasta publicada con éxito'); window.location='subastas.php';</script>";
                exit();
            } else {
                $error = "❌ Error al publicar: " . mysqli_error($conexion);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Subasta | Tierra de Subastas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        .area-imagen {
            background: #fef8f0;
            border: 2px dashed #8b4513;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .area-imagen:hover {
            border-color: #32cd32;
            background: #f0f8ff;
        }
        .preview-img {
            max-width: 100%;
            max-height: 150px;
            margin-top: 0.5rem;
            border-radius: 8px;
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
        <a href="mis_compras.php">Mis Compras</a>
        <?php if(isset($_SESSION['usuario'])): ?>
            <a href="perfil.php">👤 <?php echo $_SESSION['usuario']; ?></a>
            <a href="logout.php" onclick="return confirm('¿Cerrar sesión?')">🚪 Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php">Ingresar</a>
        <?php endif; ?>
    </nav>
</header>

<div class="form-publicar">
    <h2>🚜 Publicar nueva subasta</h2>
    
    <?php if($error): ?>
        <div class="alerta-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="grupo-form">
            <label>📦 Producto</label>
            <input type="text" name="producto" required placeholder="Ej: Ternero Holstein">
        </div>
        
        <div class="grupo-form">
            <label>📝 Descripción</label>
            <textarea name="descripcion" rows="4" required placeholder="Describe tu producto..."></textarea>
        </div>
        
        <div class="grupo-form">
            <label>🏷️ Categoría</label>
            <select name="categoria" required>
                <option value="Ganado">🐄 Ganado</option>
                <option value="Cosechas">🌽 Cosechas</option>
                <option value="Maquinaria">🚜 Maquinaria</option>
                <option value="Herramientas">🔧 Herramientas</option>
                <option value="Insumos">💧 Insumos</option>
                <option value="Otros">📦 Otros</option>
            </select>
        </div>
        
        <div class="grupo-form">
            <label>💰 Precio inicial</label>
            <input type="number" name="precio_inicial" required placeholder="0" step="1000">
        </div>
        
        <div class="grupo-form">
            <label>📍 Ubicación</label>
            <input type="text" name="ubicacion" placeholder="Ej: Bogotá, Cundinamarca">
        </div>
        
        <div class="grupo-form">
            <label>🚚 Entrega</label>
            <input type="text" name="entrega" placeholder="Ej: A convenir con el comprador">
        </div>
        
        <div class="grupo-form">
            <label>⏰ Duración</label>
            <select name="duracion">
                <option value="3">3 días</option>
                <option value="5">5 días</option>
                <option value="7">7 días</option>
                <option value="10">10 días</option>
                <option value="14">14 días</option>
            </select>
        </div>
        
        <!-- ============================================= -->
        <!-- CAMPO 1: IMAGEN DEL PRODUCTO (OBLIGATORIO) -->
        <!-- ============================================= -->
        <div class="grupo-form">
            <label>🖼️ Imagen del producto</label>
            <div class="area-imagen" id="areaImagen">
                <div style="font-size: 2rem;">🖼️</div>
                <div>Haz clic para subir la imagen del producto</div>
                <small>JPG, PNG, GIF, WEBP (máx 5MB)</small>
                <input type="file" name="imagen_producto" id="imagenInput" accept=".jpg,.jpeg,.png,.gif,.webp" style="display:none;" required>
            </div>
            <div id="previewImagen" style="display: none; margin-top: 0.5rem;">
                <img id="vistaPreviaImagen" class="preview-img">
                <button type="button" onclick="eliminarImagen()" style="margin-top: 0.5rem; background: #ff4500; color: white; border: none; padding: 0.2rem 0.5rem; border-radius: 5px; cursor: pointer;">🗑️ Eliminar imagen</button>
            </div>
        </div>
        
        <!-- ============================================= -->
        <!-- CAMPO 2: CERTIFICADO (OPCIONAL) -->
        <!-- ============================================= -->
        <div class="grupo-form">
            <label>📄 Certificado del producto (opcional)</label>
            <div class="area-imagen" id="areaCertificado">
                <div style="font-size: 2rem;">📄</div>
                <div>Haz clic para subir el certificado</div>
                <small>PDF, JPG, PNG (máx 5MB)</small>
                <input type="file" name="certificado" id="certificadoInput" accept=".pdf,.jpg,.jpeg,.png" style="display:none;">
            </div>
            <div id="previewCertificado" style="display: none; margin-top: 0.5rem;">
                <div style="background: #f0f8ff; padding: 0.5rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <span id="nombreArchivo"></span>
                    <button type="button" onclick="eliminarCertificado()" style="background: #ff4500; color: white; border: none; padding: 0.2rem 0.5rem; border-radius: 5px; cursor: pointer;">🗑️</button>
                </div>
            </div>
        </div>
        <!-- ============================================= -->
        
        <button type="submit" name="publicar" class="btn-principal" style="width:100%;">📢 Publicar subasta</button>
    </form>
</div>

<footer class="footer">
    <p>🚀 Tierra de Subastas 2026 · Conectamos el campo colombiano</p>
</footer>

<script>
    // =============================================
    // SCRIPT PARA IMAGEN DEL PRODUCTO
    // =============================================
    const areaImagen = document.getElementById('areaImagen');
    const inputImagen = document.getElementById('imagenInput');
    const previewImagenDiv = document.getElementById('previewImagen');
    const vistaPreviaImagen = document.getElementById('vistaPreviaImagen');
    
    if(areaImagen){
        areaImagen.addEventListener('click', function() {
            inputImagen.click();
        });
    }
    
    if(inputImagen){
        inputImagen.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    vistaPreviaImagen.src = event.target.result;
                    previewImagenDiv.style.display = 'block';
                    areaImagen.style.opacity = '0.5';
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    function eliminarImagen() {
        inputImagen.value = '';
        previewImagenDiv.style.display = 'none';
        areaImagen.style.opacity = '1';
        vistaPreviaImagen.src = '';
    }
    
    // =============================================
    // SCRIPT PARA CERTIFICADO
    // =============================================
    const areaCertificado = document.getElementById('areaCertificado');
    const inputCertificado = document.getElementById('certificadoInput');
    const previewCertificadoDiv = document.getElementById('previewCertificado');
    const nombreArchivoSpan = document.getElementById('nombreArchivo');
    
    if(areaCertificado){
        areaCertificado.addEventListener('click', function() {
            inputCertificado.click();
        });
    }
    
    if(inputCertificado){
        inputCertificado.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                nombreArchivoSpan.textContent = file.name;
                previewCertificadoDiv.style.display = 'block';
                areaCertificado.style.opacity = '0.5';
            }
        });
    }
    
    function eliminarCertificado() {
        inputCertificado.value = '';
        previewCertificadoDiv.style.display = 'none';
        areaCertificado.style.opacity = '1';
    }
</script>

</body>
</html>