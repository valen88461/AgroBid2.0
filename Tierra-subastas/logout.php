<?php
session_start(); // ⚠️ PRIMERA LÍNEA

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit();
?>