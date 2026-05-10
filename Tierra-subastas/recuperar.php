<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recuperar'])) {
    $correo = trim($_POST['correo'] ?? '');
    
    if (empty($correo)) {
        $error = "❌ Ingresa tu correo electrónico";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "📧 Correo inválido";
    } else {
        // Verificar si el correo existe
        $sql = "SELECT id, nombre FROM usuarios WHERE correo = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "s", $correo);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($usuario = mysqli_fetch_assoc($resultado)) {
            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Guardar token en BD
            $sql_insert = "INSERT INTO recuperacion_password (usuario_id, token, expiracion) VALUES (?, ?, ?)";
            $stmt_insert = mysqli_prepare($conexion, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "iss", $usuario['id'], $token, $expiracion);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                // Enviar correo (configurar tu servidor de correo)
                $enlace = "http://" . $_SERVER['HTTP_HOST'] . "/resetear_password.php?token=" . $token;
                
                // Aquí puedes enviar correo con PHPMailer o mail()
                $asunto = "🔐 Recuperación de contraseña - Tierra de Subastas 2026";
                $mensaje_correo = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2 style='color: #8b4513;'> AgroBid 2026</h2>
                    <p>Hola <strong>{$usuario['nombre']}</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                    <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                    <p><a href='{$enlace}' style='background: #32cd32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Restablecer contraseña</a></p>
                    <p>Este enlace expirará en <strong>1 hora</strong>.</p>
                    <p>Si no solicitaste esto, ignora este mensaje.</p>
                    <hr>
                    <small>📧 Este es un correo automático, por favor no responder.</small>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: no-reply@tierradesubastas.com" . "\r\n";
                
                // Intentar enviar correo
                if (mail($correo, $asunto, $mensaje_correo, $headers)) {
                    $mensaje = "✅ ¡Correo enviado! Revisa tu bandeja de entrada (o spam) para restablecer tu contraseña.";
                } else {
                    $error = "⚠️ No se pudo enviar el correo. Contacta al administrador.";
                }
            } else {
                $error = "❌ Error al procesar la solicitud";
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            // Por seguridad, no revelamos si el correo existe o no
            $mensaje = "✅ Si el correo está registrado, recibirás instrucciones para recuperar tu contraseña.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | Tierra de Subastas 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .recuperar-container {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .header h1 {
            color: #8b4513;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #6b4c1a;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8b4513;
            font-size: 1rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 2px solid rgba(139, 69, 19, 0.15);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #32cd32;
            box-shadow: 0 0 0 3px rgba(50, 205, 50, 0.1);
        }
        
        .btn-enviar {
            width: 100%;
            background: linear-gradient(135deg, #32cd32, #28a828);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .btn-enviar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(50, 205, 50, 0.3);
        }
        
        .btn-volver {
            width: 100%;
            background: #f0f0f0;
            color: #8b4513;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-volver:hover {
            background: #e0e0e0;
        }
        
        .mensaje-exito {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #155724;
        }
        
        .mensaje-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #721c24;
        }
        
        .info-text {
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="recuperar-container">
        <div class="header">
            <div class="icon">🔐</div>
            <h1>¿Olvidaste tu contraseña?</h1>
            <p>Te enviaremos un enlace para restablecerla</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="mensaje-exito">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mensaje-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="correo" placeholder="tu@email.com" required>
            </div>
            
            <button type="submit" name="recuperar" class="btn-enviar">
                <i class="fas fa-paper-plane"></i> Enviar instrucciones
            </button>
            
            <a href="login.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al inicio de sesión
            </a>
        </form>
        
        <div class="info-text">
            <i class="fas fa-shield-alt"></i> Recibirás un enlace válido por 1 hora
        </div>
    </div>
</body>
</html>