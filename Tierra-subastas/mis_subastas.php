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

// Obtener subastas del usuario (vendedor)
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM pujas WHERE subasta_id = s.id) as total_pujas,
        (SELECT MAX(monto) FROM pujas WHERE subasta_id = s.id) as puja_maxima
        FROM subastas s
        WHERE s.usuario_id = $usuario_id
        ORDER BY s.id DESC";
$subastas = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Subastas | Tierra de Subastas</title>
    <link rel="stylesheet" href="css/estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        .mis-subastas {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .subasta-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #32cd32;
        }
        .subasta-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .subasta-titulo {
            color: #8b4513;
            font-size: 1.2rem;
        }
        .badge-activa {
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .badge-finalizada {
            background: #6c757d;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .badge-ganador {
            background: #ffd700;
            color: #8b4513;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .info-pujas {
            background: #f0f8ff;
            padding: 0.5rem;
            border-radius: 8px;
            margin: 0.5rem 0;
        }
        .btn-ver-pujas {
            background: #1e90ff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 0.5rem;
        }
        .btn-seleccionar {
            background: #32cd32;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
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
            max-width: 500px;
            width: 90%;
        }
        .puja-item {
            padding: 0.8rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .puja-item:hover {
            background: #f5f5f5;
        }
        .monto-destacado {
            color: #ff8c00;
            font-weight: bold;
            font-size: 1.2rem;
        }
        @media (max-width: 768px) {
            .puja-item {
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

<div class="mis-subastas">
    <h2>📦 Mis Subastas Publicadas</h2>
    
    <?php if(mysqli_num_rows($subastas) > 0): ?>
        <?php while($subasta = mysqli_fetch_assoc($subastas)): ?>
            <div class="subasta-card">
                <div class="subasta-header">
                    <div>
                        <h3 class="subasta-titulo"><?php echo htmlspecialchars($subasta['producto']); ?></h3>
                        <?php
                        // Usar la fecha correcta (fecha_inicio o created_at)
                        $fecha_publicacion = $subasta['fecha_inicio'] ?? $subasta['created_at'] ?? date('Y-m-d H:i:s');
                        ?>
                        <small>Publicada: <?php echo date('d/m/Y H:i', strtotime($fecha_publicacion)); ?></small>
                    </div>
                    <div>
                        <?php if($subasta['estado'] == 'activa'): ?>
                            <span class="badge-activa">🟢 Activa</span>
                        <?php elseif($subasta['ganador_id'] > 0): ?>
                            <span class="badge-ganador">🏆 Subasta finalizada - Tiene ganador</span>
                        <?php else: ?>
                            <span class="badge-finalizada">⚫ Finalizada sin ganador</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-pujas">
                    <p>💰 Precio inicial: $<?php echo number_format($subasta['precio_inicial']); ?></p>
                    <p>📊 Total de pujas: <?php echo $subasta['total_pujas']; ?></p>
                    <p>🏆 Puja más alta: $<?php echo number_format($subasta['puja_maxima'] ?? $subasta['precio_inicial']); ?></p>
                </div>
                
                <?php if($subasta['estado'] == 'activa' && $subasta['total_pujas'] > 0): ?>
                    <button class="btn-seleccionar" onclick="verPujas(<?php echo $subasta['id']; ?>, '<?php echo addslashes($subasta['producto']); ?>')">
                        👥 Ver pujas y seleccionar ganador
                    </button>
                <?php elseif($subasta['ganador_id'] > 0): ?>
                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: #d4edda; border-radius: 8px;">
                        ✅ Ganador seleccionado - Subasta finalizada
                    </div>
                <?php elseif($subasta['estado'] != 'activa' && $subasta['total_pujas'] == 0): ?>
                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: #fff3cd; border-radius: 8px;">
                        ⚠️ Esta subasta finalizó sin recibir pujas
                    </div>
                <?php endif; ?>
                
                <a href="ver_subasta.php?id=<?php echo $subasta['id']; ?>" class="btn-ver-pujas">Ver detalles</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="sin-datos" style="text-align: center; padding: 3rem;">
            <p>📭 Aún no has publicado ninguna subasta</p>
            <a href="publicar.php" class="btn-principal">+ Publicar subasta</a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para ver pujas -->
<div id="modalPujas" class="modal">
    <div class="modal-content">
        <h3 id="modalTitulo" style="color: #8b4513;">Pujas</h3>
        <div id="listaPujas" style="max-height: 400px; overflow-y: auto;">
            Cargando...
        </div>
        <button onclick="cerrarModal()" class="btn-secundario" style="margin-top: 1rem; width: 100%;">Cerrar</button>
    </div>
</div>

<script>
    function verPujas(subastaId, productoNombre) {
        document.getElementById('modalTitulo').innerHTML = '📊 Pujas para: ' + productoNombre;
        document.getElementById('listaPujas').innerHTML = '<div style="text-align: center; padding: 2rem;">Cargando...</div>';
        document.getElementById('modalPujas').style.display = 'flex';
        
        fetch('get_pujas.php?id=' + subastaId)
            .then(response => response.json())
            .then(data => {
                if(data.length > 0) {
                    let html = '';
                    data.forEach(puja => {
                        html += `
                            <div class="puja-item">
                                <div>
                                    <strong>${puja.usuario}</strong>
                                    <div><small>${puja.fecha_puja}</small></div>
                                </div>
                                <div class="monto-destacado">
                                    $${formatNumber(puja.monto)}
                                </div>
                                <div>
                                    <button onclick="seleccionarGanador(${subastaId}, ${puja.usuario_id}, ${puja.monto}, '${puja.usuario}')" class="btn-seleccionar" style="background: #32cd32;">
                                        ✅ Seleccionar como ganador
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    document.getElementById('listaPujas').innerHTML = html;
                } else {
                    document.getElementById('listaPujas').innerHTML = '<div style="text-align: center; padding: 2rem;">No hay pujas disponibles</div>';
                }
            })
            .catch(error => {
                document.getElementById('listaPujas').innerHTML = '<div style="text-align: center; padding: 2rem; color: red;">Error al cargar las pujas</div>';
            });
    }
    
    function seleccionarGanador(subastaId, usuarioId, monto, usuarioNombre) {
        if(confirm(`¿Estás seguro de seleccionar a ${usuarioNombre} como ganador?\nMonto: $${formatNumber(monto)}`)) {
            fetch('seleccionar_ganador.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `subasta_id=${subastaId}&ganador_id=${usuarioId}&monto=${monto}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                alert('❌ Error al seleccionar ganador');
            });
        }
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat('es-CO').format(num);
    }
    
    function cerrarModal() {
        document.getElementById('modalPujas').style.display = 'none';
    }
    
    window.onclick = function(event) {
        const modal = document.getElementById('modalPujas');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

</body>
</html>