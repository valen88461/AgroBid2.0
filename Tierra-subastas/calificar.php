<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$subasta_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['subasta_id']) ? intval($_POST['subasta_id']) : 0);
$error = "";
$exito = false;

// Verificar que la subasta existe y puede ser calificada
$sql_check = "SELECT s.*, u.nombre as vendedor_nombre 
              FROM subastas s
              JOIN usuarios u ON s.usuario_id = u.id
              WHERE s.id = $subasta_id AND s.ganador_id = $usuario_id AND s.entregado = 1 AND s.calificado = 0";
$result_check = mysqli_query($conexion, $sql_check);

if(mysqli_num_rows($result_check) == 0 && $subasta_id > 0){
    header("Location: mis_compras.php?error=No puedes calificar este producto");
    exit();
}

if($subasta_id > 0){
    $subasta = mysqli_fetch_assoc($result_check);
}

// Procesar calificación
if(isset($_POST['calificar'])){
    $puntuacion = intval($_POST['puntuacion']);
    $comentario = trim(mysqli_real_escape_string($conexion, $_POST['comentario']));
    $vendedor_id = $subasta['usuario_id'];
    
    if($puntuacion >= 1 && $puntuacion <= 5){
        // Guardar calificación
        $sql_insert = "INSERT INTO calificaciones (subasta_id, comprador_id, vendedor_id, puntuacion, comentario) 
                       VALUES ($subasta_id, $usuario_id, $vendedor_id, $puntuacion, '$comentario')";
        
        if(mysqli_query($conexion, $sql_insert)){
            mysqli_query($conexion, "UPDATE subastas SET calificado = 1 WHERE id = $subasta_id");
            
            // Actualizar reputación del vendedor
            $sql_avg = "SELECT AVG(puntuacion) as promedio FROM calificaciones WHERE vendedor_id = $vendedor_id";
            $result_avg = mysqli_query($conexion, $sql_avg);
            $avg = mysqli_fetch_assoc($result_avg);
            $nueva_reputacion = round($avg['promedio'], 1);
            
            $sql_count = "SELECT COUNT(*) as total FROM calificaciones WHERE vendedor_id = $vendedor_id";
            $result_count = mysqli_query($conexion, $sql_count);
            $count = mysqli_fetch_assoc($result_count);
            
            mysqli_query($conexion, "UPDATE usuarios SET reputacion = $nueva_reputacion, total_calificaciones = {$count['total']} WHERE id = $vendedor_id");
            
            header("Location: mis_compras.php?mensaje=✅ ¡Gracias por calificar!");
            exit();
        } else {
            $error = "❌ Error al guardar la calificación: " . mysqli_error($conexion);
        }
    } else {
        $error = "❌ Selecciona una puntuación de 1 a 5 estrellas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Producto | Tierra de Subastas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .calificar-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .calificar-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .producto-info {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .producto-info h3 {
            color: #8b4513;
            margin-bottom: 0.5rem;
        }
        .estrellas {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            font-size: 3.5rem;
            margin: 1.5rem 0;
            cursor: pointer;
        }
        .estrella {
            color: #ddd;
            transition: all 0.2s;
            cursor: pointer;
        }
        .estrella.active,
        .estrella:hover {
            color: #ffd700 !important;
            text-shadow: 0 0 10px #ff8c00;
            transform: scale(1.1);
        }
        .puntuacion-texto {
            margin: 0.5rem 0 1rem;
            font-size: 1rem;
            color: #ff8c00;
            font-weight: bold;
        }
        .comentario-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 12px;
            font-family: inherit;
            resize: vertical;
            margin: 1rem 0;
        }
        .comentario-input:focus {
            outline: none;
            border-color: #32cd32;
        }
        .btn-enviar {
            background: linear-gradient(135deg, #32cd32, #28a828);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(50,205,50,0.3);
        }
        .btn-volver {
            display: inline-block;
            margin-top: 1rem;
            color: #1e90ff;
            text-decoration: none;
        }
        .alerta-error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
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

<div class="calificar-container">
    <?php if($subasta_id > 0 && isset($subasta)): ?>
    <div class="calificar-card">
        <div class="producto-info">
            <h3>⭐ Califica tu experiencia</h3>
            <p><strong><?php echo htmlspecialchars($subasta['producto']); ?></strong></p>
            <p>Vendedor: <?php echo htmlspecialchars($subasta['vendedor_nombre']); ?></p>
            <p>💰 Pagaste: $<?php echo number_format($subasta['monto_final']); ?></p>
        </div>
        
        <?php if($error): ?>
            <div class="alerta-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="calificarForm">
            <input type="hidden" name="subasta_id" value="<?php echo $subasta_id; ?>">
            <input type="hidden" name="puntuacion" id="puntuacion" value="">
            
            <div class="estrellas" id="estrellas">
                <i class="fas fa-star estrella" data-valor="1"></i>
                <i class="fas fa-star estrella" data-valor="2"></i>
                <i class="fas fa-star estrella" data-valor="3"></i>
                <i class="fas fa-star estrella" data-valor="4"></i>
                <i class="fas fa-star estrella" data-valor="5"></i>
            </div>
            
            <div class="puntuacion-texto" id="puntuacionTexto">
                👆 Haz clic en las estrellas para calificar
            </div>
            
            <textarea name="comentario" class="comentario-input" rows="4" placeholder="¿Cómo fue tu experiencia? ¿El producto llegó en buen estado?"></textarea>
            
            <button type="submit" name="calificar" class="btn-enviar" id="btnEnviar">
                ⭐ Enviar calificación
            </button>
        </form>
        
        <a href="mis_compras.php" class="btn-volver">← Volver a Mis Compras</a>
    </div>
    <?php endif; ?>
</div>

<script>
    // Textos para cada puntuación
    const textosCalificacion = {
        1: '⭐ Muy malo - Producto en mal estado',
        2: '⭐⭐ Malo - Producto dañado',
        3: '⭐⭐⭐ Regular - Producto aceptable',
        4: '⭐⭐⭐⭐ Bueno - Producto en buen estado',
        5: '⭐⭐⭐⭐⭐ Excelente - Producto perfecto'
    };
    
    // Obtener elementos
    const estrellas = document.querySelectorAll('.estrella');
    const inputPuntuacion = document.getElementById('puntuacion');
    const textoDiv = document.getElementById('puntuacionTexto');
    const form = document.getElementById('calificarForm');
    const btnEnviar = document.getElementById('btnEnviar');
    
    let puntuacionSeleccionada = 0;
    
    // Función para actualizar las estrellas visualmente
    function actualizarEstrellas(valor) {
        estrellas.forEach((estrella, index) => {
            if (index < valor) {
                estrella.classList.add('active');
                estrella.style.color = '#ffd700';
            } else {
                estrella.classList.remove('active');
                estrella.style.color = '#ddd';
            }
        });
    }
    
    // Evento click en cada estrella
    estrellas.forEach(estrella => {
        estrella.addEventListener('click', function() {
            const valor = parseInt(this.dataset.valor);
            puntuacionSeleccionada = valor;
            inputPuntuacion.value = valor;
            
            // Actualizar visual
            actualizarEstrellas(valor);
            
            // Mostrar texto
            textoDiv.innerHTML = '👌 ' + textosCalificacion[valor];
            textoDiv.style.color = '#ff8c00';
            textoDiv.style.fontWeight = 'bold';
        });
        
        // Efecto hover
        estrella.addEventListener('mouseenter', function() {
            const valor = parseInt(this.dataset.valor);
            estrellas.forEach((e, index) => {
                if (index < valor) {
                    e.style.color = '#ffd700';
                } else {
                    e.style.color = '#ddd';
                }
            });
        });
        
        estrella.addEventListener('mouseleave', function() {
            actualizarEstrellas(puntuacionSeleccionada);
        });
    });
    
    // Validar antes de enviar
    if(form) {
        form.addEventListener('submit', function(e) {
            const puntuacion = inputPuntuacion.value;
            if(!puntuacion || puntuacion < 1 || puntuacion > 5) {
                e.preventDefault();
                textoDiv.innerHTML = '❌ Por favor, haz clic en las estrellas para seleccionar una calificación (1 a 5)';
                textoDiv.style.color = '#ff4500';
                return false;
            }
            return true;
        });
    }
</script>

</body>
</html>