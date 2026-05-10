-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-05-2026 a las 14:41:21
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tierra_subastas`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_finalizar_subastas_vencidas` ()   BEGIN
    UPDATE subastas 
    SET estado = 'finalizada'
    WHERE estado = 'activa' 
    AND fecha_fin IS NOT NULL 
    AND fecha_fin < NOW();
    
    -- Marcar la puja ganadora
    UPDATE pujas p
    INNER JOIN (
        SELECT subasta_id, MAX(monto) as max_monto
        FROM pujas
        GROUP BY subasta_id
    ) pm ON p.subasta_id = pm.subasta_id AND p.monto = pm.max_monto
    SET p.es_ganadora = TRUE;
    
    -- Actualizar ganador en subastas
    UPDATE subastas s
    INNER JOIN pujas p ON s.id = p.subasta_id AND p.es_ganadora = TRUE
    SET s.ganador_id = p.usuario_id, s.monto_final = p.monto;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_registrar_puja` (IN `p_subasta_id` INT, IN `p_usuario_id` INT, IN `p_monto` DECIMAL(12,2))   BEGIN
    DECLARE v_precio_actual DECIMAL(12,2);
    DECLARE v_incremento_minimo DECIMAL(10,2);
    
    -- Obtener precio actual y incremento mínimo
    SELECT precio_actual, incremento_minimo INTO v_precio_actual, v_incremento_minimo
    FROM subastas WHERE id = p_subasta_id;
    
    -- Validar que la puja sea mayor al precio actual + incremento
    IF p_monto >= (v_precio_actual + v_incremento_minimo) THEN
        -- Registrar la puja
        INSERT INTO pujas (subasta_id, usuario_id, monto) 
        VALUES (p_subasta_id, p_usuario_id, p_monto);
        
        -- Actualizar precio actual
        UPDATE subastas SET precio_actual = p_monto WHERE id = p_subasta_id;
        
        SELECT 'Puja registrada exitosamente' AS mensaje, TRUE AS exito;
    ELSE
        SELECT 'El monto debe superar el precio actual más el incremento mínimo' AS mensaje, FALSE AS exito;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `transaccion_id` int(10) UNSIGNED NOT NULL,
  `calificador_id` int(10) UNSIGNED NOT NULL,
  `calificado_id` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL CHECK (`puntuacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha_calificacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Disparadores `calificaciones`
--
DELIMITER $$
CREATE TRIGGER `tr_actualizar_reputacion` AFTER INSERT ON `calificaciones` FOR EACH ROW BEGIN
    DECLARE promedio DECIMAL(2,1);
    
    SELECT AVG(puntuacion) INTO promedio 
    FROM calificaciones 
    WHERE calificado_id = NEW.calificado_id;
    
    UPDATE usuarios SET reputacion = promedio 
    WHERE id = NEW.calificado_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificaciones`
--

CREATE TABLE `certificaciones` (
  `id` int(11) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `archivo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `certificaciones_producto`
--

CREATE TABLE `certificaciones_producto` (
  `id` int(11) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `tipo_certificacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero_certificado` varchar(100) DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `imagen_certificado` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','verificada','rechazada') DEFAULT 'pendiente',
  `verificado_por` int(11) DEFAULT NULL,
  `fecha_verificacion` datetime DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `certificaciones_producto`
--

INSERT INTO `certificaciones_producto` (`id`, `subasta_id`, `tipo_certificacion_id`, `usuario_id`, `numero_certificado`, `fecha_emision`, `fecha_vencimiento`, `imagen_certificado`, `estado`, `verificado_por`, `fecha_verificacion`, `observaciones`, `created_at`) VALUES
(1, 5, 1, 4, 'CERT-5-1', '2026-05-03', '2027-05-03', NULL, 'verificada', NULL, NULL, NULL, '2026-05-03 19:27:20'),
(2, 5, 2, 4, 'CERT-5-2', '2026-05-03', '2027-05-03', NULL, 'verificada', NULL, NULL, NULL, '2026-05-03 19:27:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `subasta_id` int(10) UNSIGNED NOT NULL,
  `fecha_agregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs`
--

CREATE TABLE `logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `fecha_log` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `subasta_id` int(11) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `tipo` varchar(20) DEFAULT 'puja',
  `leido` tinyint(1) DEFAULT 0,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `subasta_id`, `mensaje`, `tipo`, `leido`, `fecha`) VALUES
(10, 4, NULL, '💰 ¡Bienvenido a Tierra de Subastas 2026!', 'sistema', 1, '2026-05-02 16:03:49'),
(11, 4, NULL, '⭐ Completa tu perfil para mejor reputación', 'sistema', 1, '2026-05-02 16:03:49'),
(12, 4, NULL, '📢 ¡Publica tu primera subasta y gana dinero!', 'sistema', 1, '2026-05-02 16:03:49'),
(13, 4, 5, '???? valeria ha pujado $5,259,000 por tu producto: vaca lechera', 'puja', 1, '2026-05-03 13:47:19'),
(14, 4, 5, '???? valeria ha pujado $5,259,000 por tu producto: vaca lechera', 'puja', 1, '2026-05-03 13:47:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pujas`
--

CREATE TABLE `pujas` (
  `id` int(10) UNSIGNED NOT NULL,
  `subasta_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha_puja` datetime DEFAULT current_timestamp(),
  `es_ganadora` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pujas`
--

INSERT INTO `pujas` (`id`, `subasta_id`, `usuario_id`, `monto`, `fecha_puja`, `es_ganadora`, `ip_address`) VALUES
(1, 5, 5, 5259000.00, '2026-05-03 13:47:19', 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_certificacion`
--

CREATE TABLE `solicitudes_certificacion` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_certificacion_id` int(11) NOT NULL,
  `documento_url` varchar(255) DEFAULT NULL,
  `comentarios` text DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  `revisado_por` int(11) DEFAULT NULL,
  `fecha_revision` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subastas`
--

CREATE TABLE `subastas` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `producto` varchar(150) NOT NULL,
  `categoria` enum('Ganado','Cosechas','Maquinaria','Herramientas','Insumos','Otros') DEFAULT 'Otros',
  `descripcion` text DEFAULT NULL,
  `precio_inicial` decimal(12,2) NOT NULL,
  `precio_actual` decimal(12,2) DEFAULT NULL,
  `incremento_minimo` decimal(10,2) DEFAULT 50000.00,
  `duracion` varchar(20) DEFAULT '3 días',
  `fecha_inicio` datetime DEFAULT current_timestamp(),
  `fecha_fin` datetime DEFAULT NULL,
  `entrega` varchar(50) DEFAULT '3 días hábiles',
  `imagen` varchar(255) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `visitas` int(10) UNSIGNED DEFAULT 0,
  `estado` enum('activa','finalizada','cancelada','pausada') DEFAULT 'activa',
  `ganador_id` int(10) UNSIGNED DEFAULT NULL,
  `monto_final` decimal(12,2) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `entregado` tinyint(1) DEFAULT 0,
  `calificado` tinyint(1) DEFAULT 0,
  `finalizada_por` int(11) DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subastas`
--

INSERT INTO `subastas` (`id`, `usuario_id`, `producto`, `categoria`, `descripcion`, `precio_inicial`, `precio_actual`, `incremento_minimo`, `duracion`, `fecha_inicio`, `fecha_fin`, `entrega`, `imagen`, `ubicacion`, `visitas`, `estado`, `ganador_id`, `monto_final`, `fecha_creacion`, `entregado`, `calificado`, `finalizada_por`, `fecha_finalizacion`, `created_at`) VALUES
(5, 4, 'vaca lechera', 'Herramientas', 'Vaca lechera', 5209000.00, 5259000.00, 50000.00, '3 días', '2026-05-03 13:24:38', NULL, '3 días hábiles', '1777832678_69f792e6395a2.jpeg', 'fusagasuga/colombia', 0, 'finalizada', 5, 5259000.00, '2026-05-03 13:24:38', 1, 0, NULL, '2026-05-03 16:28:21', '2026-05-03 21:35:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_certificacion`
--

CREATE TABLE `tipos_certificacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT NULL,
  `entidad_emisora` varchar(200) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipos_certificacion`
--

INSERT INTO `tipos_certificacion` (`id`, `nombre`, `descripcion`, `icono`, `entidad_emisora`, `color`, `activo`) VALUES
(1, 'ICA - Inocuidad', 'Certificación de Inocuidad del ICA para productos agrícolas', '🧪', 'Instituto Colombiano Agropecuario (ICA)', '#1e90ff', 1),
(2, 'Orgánico Colombia', 'Certificación de producción orgánica - Sin agroquímicos', '🌱', 'Ministerio de Agricultura', '#32cd32', 1),
(3, 'Fair Trade', 'Certificación de Comercio Justo', '⚖️', 'Fairtrade International', '#ff8c00', 1),
(4, 'GlobalG.A.P.', 'Certificación de Buenas Prácticas Agrícolas', '🌍', 'GlobalG.A.P.', '#1e90ff', 1),
(5, 'Rainforest Alliance', 'Certificación de Sostenibilidad Ambiental', '🐸', 'Rainforest Alliance', '#32cd32', 1),
(6, 'BPA - Buenas Prácticas', 'Buenas Prácticas Agrícolas', '📋', 'SENA / ICA', '#8b4513', 1),
(7, 'Trazabilidad', 'Sistema de trazabilidad del producto', '🔍', 'Entidad certificadora', '#ff4500', 1),
(8, 'Denominación Origen', 'Denominación de Origen Protegida', '🏆', 'Superintendencia de Industria y Comercio', '#ffd700', 1),
(9, 'Bio - Agricultura', 'Certificación de Agricultura Biológica', '🐞', 'Entidad certificadora', '#32cd32', 1),
(10, 'Libre de Transgénicos', 'Certificación Non-GMO', '🚫', 'Certificación independiente', '#ff8c00', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transacciones`
--

CREATE TABLE `transacciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `subasta_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `vendedor_id` int(10) UNSIGNED NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `comision` decimal(10,2) DEFAULT 0.00,
  `estado` enum('pendiente','pagado','enviado','entregado','cancelado') DEFAULT 'pendiente',
  `metodo_pago` enum('tarjeta','transferencia','efectivo') DEFAULT 'tarjeta',
  `comprobante` varchar(255) DEFAULT NULL,
  `fecha_transaccion` datetime DEFAULT current_timestamp(),
  `fecha_entrega` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `clave` varchar(255) NOT NULL,
  `direccion` text DEFAULT NULL,
  `tipo_usuario` enum('comprador','vendedor','admin') DEFAULT 'comprador',
  `reputacion` decimal(2,1) DEFAULT 5.0,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `estado` enum('activo','bloqueado','pendiente') DEFAULT 'activo',
  `total_calificaciones` int(11) DEFAULT 0,
  `pregunta_secreta` varchar(255) DEFAULT NULL,
  `respuesta_secreta` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `telefono`, `clave`, `direccion`, `tipo_usuario`, `reputacion`, `avatar`, `fecha_registro`, `ultimo_acceso`, `estado`, `total_calificaciones`, `pregunta_secreta`, `respuesta_secreta`) VALUES
(1, 'Administrador', 'admin@tierrasubastas.com', '3001234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'admin', 5.0, 'default-avatar.png', '2026-05-02 10:29:16', NULL, 'activo', 0, NULL, NULL),
(2, 'Juan Campesino', 'juan@email.com', '3112223344', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'vendedor', 5.0, 'default-avatar.png', '2026-05-02 10:29:16', NULL, 'activo', 0, NULL, NULL),
(3, 'María Agricultora', 'maria@email.com', '3223334455', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'comprador', 5.0, 'default-avatar.png', '2026-05-02 10:29:16', NULL, 'activo', 0, NULL, NULL),
(4, 'cristian', 'cristian@gmail.com', '3056554265', '$2y$10$NWcrL0JvZuobv.Nn7hcqzeyhIzb1YVRJf6y0aCupGBTIXMkUEpoG2', NULL, 'comprador', 5.0, 'avatar_4_1777757490.png', '2026-05-02 10:43:06', NULL, 'activo', 0, NULL, NULL),
(5, 'valeria', 'valeria@gmail.com', '3056554265', '$2y$10$NWcrL0JvZuobv.Nn7hcqzeyhIzb1YVRJf6y0aCupGBTIXMkUEpoG2', NULL, 'comprador', 5.0, 'avatar_5_1778363197.png', '2026-05-03 13:45:57', NULL, 'activo', 0, NULL, NULL),
(6, 'gabriel', 'gabriel@gmail.com', '3056554265', '$2y$10$zDCo5/ZICOrw.I3gh289XefcZq0cUYWkMs58.6DRZab1G.lMJg5RO', NULL, 'comprador', 5.0, 'default-avatar.png', '2026-05-03 14:10:48', NULL, 'activo', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_subastas_activas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_subastas_activas` (
`id` int(10) unsigned
,`producto` varchar(150)
,`categoria` enum('Ganado','Cosechas','Maquinaria','Herramientas','Insumos','Otros')
,`precio_actual` decimal(12,2)
,`fecha_fin` datetime
,`incremento_minimo` decimal(10,2)
,`imagen` varchar(255)
,`vendedor` varchar(100)
,`reputacion` decimal(2,1)
,`total_pujas` bigint(21)
,`puja_maxima` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_top_vendedores`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_top_vendedores` (
`id` int(10) unsigned
,`nombre` varchar(100)
,`reputacion` decimal(2,1)
,`total_subastas` bigint(21)
,`ventas_realizadas` bigint(21)
,`total_ventas` decimal(34,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_subastas_activas`
--
DROP TABLE IF EXISTS `v_subastas_activas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_subastas_activas`  AS SELECT `s`.`id` AS `id`, `s`.`producto` AS `producto`, `s`.`categoria` AS `categoria`, `s`.`precio_actual` AS `precio_actual`, `s`.`fecha_fin` AS `fecha_fin`, `s`.`incremento_minimo` AS `incremento_minimo`, `s`.`imagen` AS `imagen`, `u`.`nombre` AS `vendedor`, `u`.`reputacion` AS `reputacion`, (select count(0) from `pujas` where `pujas`.`subasta_id` = `s`.`id`) AS `total_pujas`, (select max(`pujas`.`monto`) from `pujas` where `pujas`.`subasta_id` = `s`.`id`) AS `puja_maxima` FROM (`subastas` `s` join `usuarios` `u` on(`s`.`usuario_id` = `u`.`id`)) WHERE `s`.`estado` = 'activa' AND (`s`.`fecha_fin` is null OR `s`.`fecha_fin` > current_timestamp()) ORDER BY `s`.`fecha_inicio` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_top_vendedores`
--
DROP TABLE IF EXISTS `v_top_vendedores`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_top_vendedores`  AS SELECT `u`.`id` AS `id`, `u`.`nombre` AS `nombre`, `u`.`reputacion` AS `reputacion`, count(distinct `s`.`id`) AS `total_subastas`, count(distinct `t`.`id`) AS `ventas_realizadas`, coalesce(sum(`t`.`monto`),0) AS `total_ventas` FROM ((`usuarios` `u` left join `subastas` `s` on(`u`.`id` = `s`.`usuario_id`)) left join `transacciones` `t` on(`s`.`id` = `t`.`subasta_id` and `t`.`estado` = 'entregado')) WHERE `u`.`tipo_usuario` in ('vendedor','admin') GROUP BY `u`.`id` ORDER BY coalesce(sum(`t`.`monto`),0) DESC LIMIT 0, 10 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaccion_id` (`transaccion_id`),
  ADD KEY `calificador_id` (`calificador_id`),
  ADD KEY `idx_calificado` (`calificado_id`);

--
-- Indices de la tabla `certificaciones`
--
ALTER TABLE `certificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subasta` (`subasta_id`);

--
-- Indices de la tabla `certificaciones_producto`
--
ALTER TABLE `certificaciones_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subasta` (`subasta_id`),
  ADD KEY `idx_tipo` (`tipo_certificacion_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_verificado` (`verificado_por`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuario_subasta` (`usuario_id`,`subasta_id`),
  ADD KEY `subasta_id` (`subasta_id`);

--
-- Indices de la tabla `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_fecha` (`fecha_log`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pujas`
--
ALTER TABLE `pujas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_subasta` (`subasta_id`),
  ADD KEY `idx_fecha` (`fecha_puja`),
  ADD KEY `idx_monto` (`monto`);

--
-- Indices de la tabla `solicitudes_certificacion`
--
ALTER TABLE `solicitudes_certificacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_tipo` (`tipo_certificacion_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `subastas`
--
ALTER TABLE `subastas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_fin` (`fecha_fin`),
  ADD KEY `idx_categoria` (`categoria`),
  ADD KEY `idx_ganador` (`ganador_id`);
ALTER TABLE `subastas` ADD FULLTEXT KEY `idx_busqueda` (`producto`,`descripcion`);

--
-- Indices de la tabla `tipos_certificacion`
--
ALTER TABLE `tipos_certificacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subasta_id` (`subasta_id`),
  ADD KEY `comprador_id` (`comprador_id`),
  ADD KEY `vendedor_id` (`vendedor_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `idx_correo` (`correo`),
  ADD KEY `idx_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `certificaciones`
--
ALTER TABLE `certificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `certificaciones_producto`
--
ALTER TABLE `certificaciones_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `pujas`
--
ALTER TABLE `pujas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `solicitudes_certificacion`
--
ALTER TABLE `solicitudes_certificacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subastas`
--
ALTER TABLE `subastas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipos_certificacion`
--
ALTER TABLE `tipos_certificacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `transacciones`
--
ALTER TABLE `transacciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `calificaciones_ibfk_1` FOREIGN KEY (`transaccion_id`) REFERENCES `transacciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_ibfk_2` FOREIGN KEY (`calificador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `calificaciones_ibfk_3` FOREIGN KEY (`calificado_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pujas`
--
ALTER TABLE `pujas`
  ADD CONSTRAINT `pujas_ibfk_1` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pujas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `subastas`
--
ALTER TABLE `subastas`
  ADD CONSTRAINT `subastas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subastas_ibfk_2` FOREIGN KEY (`ganador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `transacciones`
--
ALTER TABLE `transacciones`
  ADD CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transacciones_ibfk_2` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `transacciones_ibfk_3` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
