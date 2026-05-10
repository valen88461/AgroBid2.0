<?php
session_start();
require_once __DIR__ . "/includes/conexion.php";

$error = '';
$mensaje = '';
$token_valido = false;
$usuario_id = null;

// Verificar token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Buscar token válido
    $sql = "SELECT usuario_id, expiracion FROM recuperacion_password 
            WHERE token = ? AND usado = FALSE AND expiracion > NOW()";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($resultado)) {
        $token_valido = true;
        $usuario_id = $row['usuario_id'];
    } else {
        $error = "❌ Enlace inválido o expirado. Solicita un nuevo enlace de recuperación.";
    }
    mysqli_stmt_close($stmt);
}

// Procesar nueva contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resetear'])) {
    $token = $_POST['token'];
    $nueva_clave = $_POST['nueva_clave'];
    $confirmar_clave = $_POST['confirmar_clave'];
    
    // Validaciones de contraseña
    $errores = [];
    
    if (strlen($nueva_clave) < 8) {
        $errores[] = "🔒 mínimo 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $nueva_clave)) {
        $errores[] = "🔠 una mayúscula";
    }
    if (!preg_match('/[a-z]/', $nueva_clave)) {
        $errores[] = "🔡 una minúscula";
    }
    if (!preg_match('/[0-9]/', $nueva_clave)) {
        $errores[] = "🔢 un número";
    }
    if (preg_match('/\s/', $nueva_clave)) {
        $errores[] = "🚫 sin espacios";
    }
    
    if (!empty($errores)) {
        $error = "🔒 Contraseña insegura: " . implode(", ", $errores);
    } elseif ($nueva_clave !== $confirmar_clave) {
        $error = "❌ Las contraseñas no coinciden";
    } else {
        // Verificar token nuevamente (seguridad)
        $sql = "SELECT usuario_id FROM recuperacion_password 
                WHERE token = ? AND usado = FALSE AND expiracion > NOW()";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($resultado)) {
            $usuario_id = $row['usuario_id'];
            
            // Actualizar contraseña
            $nueva_clave_hash = password_hash($nueva_clave, PASSWORD_DEFAULT);
            $sql_update = "UPDATE usuarios SET clave = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conexion, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "si", $nueva_clave_hash, $usuario_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                // Marcar token como usado
                $sql_marcar = "UPDATE recuperacion_password SET usado = TRUE WHERE token = ?";
                $stmt_marcar = mysqli_prepare($conexion, $sql_marcar);
                mysqli_stmt_bind_param($stmt_marcar, "s", $token);
                mysqli_stmt_execute($stmt_marcar);
                mysqli_stmt_close($stmt_marcar);
                
                $mensaje = "✅ ¡Contraseña actualizada exitosamente!";
                echo "<script>
                    alert('✅ ¡Contraseña actualizada! Ahora puedes iniciar sesión.');
                    window.location='login.php';
                </script>";
                exit();
            } else {
                $error = "❌ Error al actualizar la contraseña";
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error = "❌ Enlace inválido o expirado";
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
    <title>Restablecer Contraseña | Tierra de Subastas 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Mismo estilo que recuperar.php */
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
        
        .reset-container {
            max-width: 500px;
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
        
        .btn-resetear {
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
        
        .btn-resetear:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(50, 205, 50, 0.3);
        }
        
        .requirement {
            font-size: 0.7rem;
            color: #6b4c1a;
            margin: 0.5rem 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        
        .requirement span {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .requirement .valid {
            color: #32cd32;
        }
        
        .requirement .invalid {
            color: #ff4500;
        }
        
        .mensaje-error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            color: #721c24;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.7rem;
        }
        
        .strength-bar {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.3rem;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            display: block;
            margin-top: 0.3rem;
            font-size: 0.7rem;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="header">
            <div class="icon">🔐</div>
            <h1>Crear nueva contraseña</h1>
            <p>Ingresa una contraseña segura</p>
        </div>
        
        <?php if ($error): ?>
            <div class="mensaje-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($token_valido): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="nueva_clave" id="password" placeholder="Nueva contraseña" required>
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-text" id="strengthText"></span>
                </div>
                
                <div class="requirement">
                    <span id="reqLength"><i class="fas fa-circle"></i> Mínimo 8 caracteres</span>
                    <span id="reqUpper"><i class="fas fa-circle"></i> Una mayúscula</span>
                    <span id="reqLower"><i class="fas fa-circle"></i> Una minúscula</span>
                    <span id="reqNumber"><i class="fas fa-circle"></i> Un número</span>
                    <span id="reqNoSpace"><i class="fas fa-circle"></i> Sin espacios</span>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirmar_clave" id="confirm_password" placeholder="Confirmar contraseña" required>
                </div>
                <small id="matchMessage" style="font-size: 0.7rem;"></small>
                
                <button type="submit" name="resetear" class="btn-resetear">
                    <i class="fas fa-save"></i> Restablecer contraseña
                </button>
            </form>
        <?php else: ?>
            <div class="mensaje-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error ?: "Enlace inválido"); ?>
            </div>
            <a href="recuperar.php" class="btn-resetear" style="text-align: center; text-decoration: none; display: inline-block;">
                <i class="fas fa-redo"></i> Solicitar nuevo enlace
            </a>
        <?php endif; ?>
    </div>
    
    <script>
        // Mismo JavaScript de validación de contraseña que en registro.php
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        const reqLength = document.getElementById('reqLength');
        const reqUpper = document.getElementById('reqUpper');
        const reqLower = document.getElementById('reqLower');
        const reqNumber = document.getElementById('reqNumber');
        const reqNoSpace = document.getElementById('reqNoSpace');
        const matchMessage = document.getElementById('matchMessage');
        
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        
        function checkPasswordStrength() {
            const val = password.value;
            let strength = 0;
            let validations = {
                length: val.length >= 8,
                upper: /[A-Z]/.test(val),
                lower: /[a-z]/.test(val),
                number: /[0-9]/.test(val),
                noSpace: !/\s/.test(val)
            };
            
            updateRequirement(reqLength, validations.length, "Mínimo 8 caracteres");
            updateRequirement(reqUpper, validations.upper, "Una mayúscula");
            updateRequirement(reqLower, validations.lower, "Una minúscula");
            updateRequirement(reqNumber, validations.number, "Un número");
            updateRequirement(reqNoSpace, validations.noSpace, "Sin espacios");
            
            strength = Object.values(validations).filter(v => v === true).length;
            
            let percentage = (strength / 5) * 100;
            strengthFill.style.width = percentage + '%';
            
            if(strength === 0) {
                strengthFill.style.background = '#e0e0e0';
                strengthText.textContent = '';
            } else if(strength <= 2) {
                strengthFill.style.background = '#ff4500';
                strengthText.textContent = '🔴 Contraseña débil';
            } else if(strength <= 4) {
                strengthFill.style.background = '#ff8c00';
                strengthText.textContent = '🟠 Contraseña media';
            } else {
                strengthFill.style.background = '#32cd32';
                strengthText.textContent = '🟢 Contraseña fuerte';
            }
            
            checkPasswordMatch();
            return Object.values(validations).every(v => v === true);
        }
        
        function updateRequirement(element, isValid, text) {
            if(isValid) {
                element.innerHTML = '<i class="fas fa-check-circle"></i> ' + text;
                element.className = 'valid';
            } else {
                element.innerHTML = '<i class="fas fa-circle"></i> ' + text;
                element.className = 'invalid';
            }
        }
        
        function checkPasswordMatch() {
            if(confirmPassword.value.length > 0) {
                if(password.value === confirmPassword.value) {
                    matchMessage.innerHTML = '✅ Las contraseñas coinciden';
                    matchMessage.style.color = '#32cd32';
                    return true;
                } else {
                    matchMessage.innerHTML = '❌ Las contraseñas no coinciden';
                    matchMessage.style.color = '#ff4500';
                    return false;
                }
            } else {
                matchMessage.innerHTML = '';
                return false;
            }
        }
        
        if(password) {
            password.addEventListener('input', checkPasswordStrength);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }
    </script>
</body>
</html>