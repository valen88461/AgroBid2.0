<?php
session_start();
include(__DIR__ . "/includes/conexion.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgroBíd 2026 | Futuro Agrícola</title>

    <link rel="stylesheet" href="css/estilos.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

<!-- HEADER -->
<header class="header">

    <div class="logo">
        <img src="img/logo.png" alt="Logo" class="logo-img">
        AgroBíd
    </div>

    <nav class="menu">

        <a href="index.php">🏠 Inicio</a>

        <a href="subastas.php">📡 Subastas</a>

        <a href="publicar.php">➕ Publicar</a>

        <a href="certificacion.php">📜 Certificaciones</a>

        <a href="mis_compras.php">🛒 Mis Compras</a>

        <a href="mis_subastas.php">📦 Mis Subastas</a>

        <?php if(isset($_SESSION['usuario'])): ?>

            <!-- PERFIL -->
            <a href="perfil.php">
                👤 <?php echo htmlspecialchars($_SESSION['usuario']); ?>
            </a>

            <!-- LOGOUT -->
            <a href="logout.php"
               onclick="return confirm('¿Cerrar sesión?')">
               🚪 Cerrar sesión
            </a>

            <!-- NOTIFICACIONES -->
            <?php

            if(isset($_SESSION['user_id'])){

                $user_id = $_SESSION['user_id'];

                $sql_notif = "
                    SELECT *
                    FROM notificaciones
                    WHERE usuario_id = $user_id
                    AND leido = 0
                    ORDER BY id DESC
                    LIMIT 5
                ";

                $res_notif = mysqli_query($conexion, $sql_notif);

                $total_notif = mysqli_num_rows($res_notif);

            } else {

                $total_notif = 0;
            }

            ?>

            <div class="notificaciones">

                <button class="btn-notificaciones"
                        onclick="toggleNotificaciones()">

                    🔔

                    <?php if($total_notif > 0): ?>

                        <span class="badge-notif">
                            <?php echo $total_notif; ?>
                        </span>

                    <?php endif; ?>

                </button>

                <div class="panel-notificaciones"
                     id="panelNotificaciones"
                     style="display:none;">

                    <?php if($total_notif > 0 && isset($res_notif)): ?>

                        <?php while($notif = mysqli_fetch_assoc($res_notif)): ?>

                            <div class="notificacion-item">

                                <p>
                                    <?php echo htmlspecialchars($notif['mensaje']); ?>
                                </p>

                                <small>
                                    <?php echo date('d/m/Y H:i', strtotime($notif['fecha'] ?? 'now')); ?>
                                </small>

                            </div>

                        <?php endwhile; ?>

                        <a href="ver_notificaciones.php"
                           class="ver-todas">
                           Ver todas
                        </a>

                    <?php else: ?>

                        <div class="sin-notif">
                            No hay notificaciones nuevas
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>

            <!-- LOGIN -->
            <a href="/Tierra-subastas/login.php"
               style="
               background: var(--esperanza);
               color: white;
               padding: 0.5rem 1.2rem;
               border-radius: 50px;
               text-decoration: none;
               display: inline-block;
               ">
               🔐 Ingresar
            </a>

        <?php endif; ?>

    </nav>

</header>

<!-- HERO -->
<section class="hero">

    <div class="hero-texto">

        <span class="badge">
            ⚡ Plataforma Rural 4.0 | 2026
        </span>

        <h1>
            Subasta productos del campo en tiempo real
        </h1>

        <p>
            🌱 Compra y vende ganado, cosechas,
            maquinaria e insumos agrícolas con
            ofertas en tiempo real.
        </p>

        <div class="botones">

            <a href="publicar.php"
               class="btn-principal">

               + Publicar ahora

            </a>

            <a href="subastas.php"
               class="btn-secundario">

               📡 Ver subastas activas

            </a>

        </div>

    </div>

    <!-- CARD -->
    <div class="hero-card">

        <h3>🔥 Subasta Destacada del Día</h3>

        <img src="img/tractor.png"
             alt="Tractor">

        <p>
            <strong>
                Tractor Agrícola John Deere 9RX
            </strong>
        </p>

        <p style="
           color: var(--calabaza);
           font-size: 1.5rem;
           font-weight: bold;
        ">

           $18.500.000 COP

        </p>

        <small>
            ⏱️ Cierra en 2 días · 14 pujas
        </small>

    </div>

</section>

<!-- ESTADÍSTICAS -->
<section class="stats">

    <div class="stat">
        <h2>+500</h2>
        <p>Subastas activas</p>
        <small>en 12 departamentos</small>
    </div>

    <div class="stat">
        <h2>+1.200</h2>
        <p>Usuarios verificados</p>
        <small>+350% este año</small>
    </div>

    <div class="stat">
        <h2>98.7%</h2>
        <p>Entregas exitosas</p>
        <small>certificadas</small>
    </div>

</section>

<!-- PRODUCTOS -->
<section class="productos">

    <div class="titulo-seccion">

        <h2>🌿 Productos más pujados</h2>

        <p>
            Los más populares de nuestra comunidad
        </p>

    </div>

    <div class="grid">

        <div class="card"
             onclick="location.href='subastas.php'">

            <img src="img/vaca.png" alt="Ganado">

            <h3> Ganado Brahman</h3>

            <p>
                Oferta actual:
                <strong style="color:var(--calabaza);">
                    $2.550.000
                </strong>
            </p>

            <small>
                12 pujas · 3h restantes
            </small>

        </div>

        <div class="card"
             onclick="location.href='subastas.php'">

            <img src="img/cafe.png" alt="Cafe">

            <h3>☕ Café Premium Excelso</h3>

            <p>
                Oferta actual:
                <strong style="color:var(--calabaza);">
                    $450.000
                </strong>
            </p>

            <small>
                28 pujas · 1 día restante
            </small>

        </div>

        <div class="card"
             onclick="location.href='subastas.php'">

            <img src="img/tractor.png" alt="Tractor">

            <h3>🚜 Maquinaria New Holland</h3>

            <p>
                Oferta actual:
                <strong style="color:var(--calabaza);">
                    $18.000.000
                </strong>
            </p>

            <small>
                7 pujas · 5 días restantes
            </small>

        </div>

    </div>

</section>

<!-- FOOTER -->
<footer class="footer">

    <p>
        🚀 AgroBíd 2026 · Tecnología para el campo colombiano
    </p>

    <div class="footer-usuario">

        <?php if(isset($_SESSION['usuario'])): ?>

            ✅ Bienvenido,
            <?php echo htmlspecialchars($_SESSION['usuario']); ?>

        <?php else: ?>

            <a href="login.php">
                🔐 Inicia sesión
            </a>

            para publicar y participar

        <?php endif; ?>

    </div>

</footer>

<!-- JS -->
<script>

function toggleNotificaciones() {

    var panel = document.getElementById('panelNotificaciones');

    if(panel.style.display === 'none' || panel.style.display === '') {

        panel.style.display = 'block';

    } else {

        panel.style.display = 'none';
    }
}

document.addEventListener('click', function(event) {

    var panel = document.getElementById('panelNotificaciones');

    var btn = document.querySelector('.btn-notificaciones');

    if(
        panel &&
        btn &&
        !btn.contains(event.target) &&
        !panel.contains(event.target)
    ) {

        panel.style.display = 'none';
    }
});

</script>

</body>
</html>