<?php
// includes/conexion.php - Versión que crea la BD automáticamente

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "tierra_subastas";
$port = 3306;

// Conectar sin seleccionar base de datos
$conexion = mysqli_connect($host, $user, $password, "", $port);

if(!$conexion){
    die("Error de conexión: " . mysqli_connect_error());
}

// Crear la base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if(mysqli_query($conexion, $sql)){
    // Seleccionar la base de datos
    mysqli_select_db($conexion, $database);
    
    // Crear tablas necesarias
    $tabla_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(100) UNIQUE NOT NULL,
        telefono VARCHAR(20),
        clave VARCHAR(255) NOT NULL,
        fecha_registro DATETIME
    )";
    mysqli_query($conexion, $tabla_usuarios);
    
    $tabla_subastas = "CREATE TABLE IF NOT EXISTS subastas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        producto VARCHAR(100) NOT NULL,
        categoria VARCHAR(50),
        precio DECIMAL(12,2),
        duracion VARCHAR(20),
        entrega VARCHAR(50),
        descripcion TEXT,
        imagen VARCHAR(255),
        usuario_id INT,
        fecha_publicacion DATETIME,
        estado VARCHAR(20) DEFAULT 'activa'
    )";
    mysqli_query($conexion, $tabla_subastas);
    
    $tabla_pujas = "CREATE TABLE IF NOT EXISTS pujas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subasta_id INT,
        usuario_id INT,
        monto DECIMAL(12,2),
        fecha_puja DATETIME
    )";
    mysqli_query($conexion, $tabla_pujas);
    
} else {
    die("Error al crear la base de datos: " . mysqli_error($conexion));
}

mysqli_set_charset($conexion, "utf8");
?>