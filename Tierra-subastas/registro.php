<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

$errores = [];

if(isset($_POST['registrar'])){
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $clave = $_POST['clave'];
    $confirmar_clave = $_POST['confirmar_clave'];
    
    // Validaciones básicas
    if(empty($nombre)) $errores[] = "❌ Nombre obligatorio";
    if(empty($correo)) $errores[] = "❌ Correo obligatorio";
    if(!filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = "❌ Correo inválido";


    <?php
// En tu registro.php, dentro de las validaciones, agregar:

// Validar pregunta secreta
if(empty($_POST['pregunta_secreta'])) {
    $errores[] = "❌ Debes seleccionar una pregunta secreta";
}

if(empty($_POST['respuesta_secreta'])) {
    $errores[] = "❌ Debes ingresar una respuesta secreta";
} elseif(strlen($_POST['respuesta_secreta']) < 3) {
    $errores[] = "🔐 La respuesta debe tener al menos 3 caracteres";
} else {
    // Guardar respuesta encriptada (por seguridad)
    $respuesta_hash = password_hash(strtolower(trim($_POST['respuesta_secreta'])), PASSWORD_DEFAULT);
}

// Modificar el INSERT para incluir pregunta y respuesta
$sql = "INSERT INTO usuarios (nombre, correo, telefono, clave, reputacion, total_calificaciones, pregunta_secreta, respuesta_secreta) 
        VALUES ('$nombre', '$correo', $telefono_db, '$clave_hash', 5.0, 0, '{$_POST['pregunta_secreta']}', '$respuesta_hash')";
?>
    
    // ✅ VALIDACIONES DE CONTRASEÑA SEGURA
    if(strlen($clave) < 8) {
        $errores[] = "🔒 La contraseña debe tener al menos 8 caracteres";
    }
    if(!preg_match('/[A-Z]/', $clave)) {
        $errores[] = "🔒 La contraseña debe tener al menos una MAYÚSCULA";
    }
    if(!preg_match('/[a-z]/', $clave)) {
        $errores[] = "🔒 La contraseña debe tener al menos una minúscula";
    }
    if(!preg_match('/[0-9]/', $clave)) {
        $errores[] = "🔒 La contraseña debe tener al menos un número";
    }
    if(preg_match('/\s/', $clave)) {
        $errores[] = "🔒 La contraseña no puede tener espacios en blanco";
    }
    if($clave !== $confirmar_clave) {
        $errores[] = "❌ Las contraseñas no coinciden";
    }
    
    // Validar teléfono (opcional pero recomendado)
    if(!empty($telefono) && !preg_match('/^\d{10}$/', $telefono)) {
        $errores[] = "📱 El teléfono debe tener 10 dígitos (solo números)";
    }
    
    // Verificar si el correo ya existe
    if(empty($errores)){
        $check_sql = "SELECT id FROM usuarios WHERE correo = '$correo'";
        $check_result = mysqli_query($conexion, $check_sql);
        if(mysqli_num_rows($check_result) > 0){
            $errores[] = "❌ Este correo ya está registrado";
        }
    }
    
    
    
    // Si no hay errores, registrar
    if(empty($errores)){
        $clave_hash = password_hash($clave, PASSWORD_DEFAULT);
        $telefono_db = !empty($telefono) ? "'$telefono'" : "NULL";
        
        $sql = "INSERT INTO usuarios (nombre, correo, telefono, clave, reputacion, total_calificaciones) 
                VALUES ('$nombre', '$correo', $telefono_db, '$clave_hash', 5.0, 0)";
        
        if(mysqli_query($conexion, $sql)){
            echo "<script>
                alert('✅ ¡Registro exitoso! Bienvenido a Tierra de Subastas 2026');
                window.location='login.php';
            </script>";
            exit();
        } else {
            $errores[] = "Error en el registro: " . mysqli_error($conexion);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Tierra de Subastas 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
        }
        
        .registro-container {
            width: 100%;
            max-width: 550px;
            margin: 2rem auto;
        }
        
        .registro-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .registro-header .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .registro-header h1 {
            font-size: 1.8rem;
            color: #8b4513;
            margin-bottom: 0.5rem;
        }
        
        .registro-header p {
            color: #6b4c1a;
        }
        
        .formulario {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b4c1a;
            font-size: 1rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 2px solid rgba(139, 69, 19, 0.15);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #32cd32;
            box-shadow: 0 0 0 3px rgba(50, 205, 50, 0.1);
        }
        
        .grupo-form {
            margin-bottom: 1.2rem;
        }
        
        .grupo-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #8b4513;
            font-size: 0.9rem;
        }
        
        .error-messages {
            background: #fff5f5;
            border-left: 4px solid #ff4500;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .error-messages p {
            color: #e63e00;
            font-size: 0.85rem;
            margin: 0.3rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-registro {
            width: 100%;
            background: linear-gradient(135deg, #32cd32, #28a828);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        .btn-registro:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(50, 205, 50, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(139, 69, 19, 0.1);
        }
        
        .login-link a {
            color: #ff8c00;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .terms {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            font-size: 0.85rem;
        }
        
        .terms input {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #32cd32;
        }
        
        .terms a {
            color: #ff8c00;
            text-decoration: none;
        }
        
        /* Indicador de fortaleza de contraseña */
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
        
        .requirement {
            font-size: 0.7rem;
            color: #6b4c1a;
            margin-top: 0.3rem;
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
        
        .row {
            display: flex;
            gap: 1rem;
        }
        
        .row .grupo-form {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .formulario {
                padding: 1.5rem;
            }
            .row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="registro-container">
        <div class="registro-header">
            <div class="logo-icon">🌾</div>
            <h1>Crear Cuenta</h1>
            <p>Únete a Tierra de Subastas 2026</p>
        </div>

        <div class="formulario">
            <?php if(!empty($errores)): ?>
                <div class="error-messages">
                    <?php foreach($errores as $err): ?>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($err); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registroForm">
                <div class="row">
                    <div class="grupo-form">
                        <label>👤 Nombre completo</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="nombre" placeholder="Juan Pérez" required 
                                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                        </div>
                    </div>

                    <div class="grupo-form">
                        <label>📱 Teléfono</label>
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="telefono" placeholder="3001234567" 
                                   value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="grupo-form">
                    <label>📧 Correo electrónico</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="correo" placeholder="tu@email.com" required 
                               value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>">
                    </div>
                </div>

                <div class="grupo-form">
                    <label>🔒 Contraseña</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="clave" id="password" placeholder="Crea una contraseña segura" required>
                    </div>
                    
                    <!-- Indicador de fortaleza -->
                    <div class="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                    
                    <!-- Requisitos de contraseña -->
                    <div class="requirement" id="passwordRequirements">
                        <span id="reqLength"><i class="fas fa-circle"></i> Mínimo 8 caracteres</span>
                        <span id="reqUpper"><i class="fas fa-circle"></i> Una mayúscula</span>
                        <span id="reqLower"><i class="fas fa-circle"></i> Una minúscula</span>
                        <span id="reqNumber"><i class="fas fa-circle"></i> Un número</span>
                        <span id="reqNoSpace"><i class="fas fa-circle"></i> Sin espacios</span>
                    </div>
                </div>

                <div class="grupo-form">
                    <label>✅ Confirmar contraseña</label>
                    <div class="input-group">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirmar_clave" id="confirm_password" placeholder="Repite tu contraseña" required>
                    </div>
                    <small id="matchMessage" style="font-size: 0.7rem;"></small>
                </div>

                <div class="terms">
                    <input type="checkbox" id="terms" required>
                    <label for="terms">
                        Acepto los <a href="#">términos y condiciones</a> 
                    </label>
                </div>

                <button type="submit" name="registrar" class="btn-registro" id="registerBtn">
                    <i class="fas fa-user-plus"></i> REGISTRARSE
                </button>

                <div class="login-link">
                    ¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validación de contraseña en tiempo real
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const registerBtn = document.getElementById('registerBtn');
        
        // Elementos de requisitos
        const reqLength = document.getElementById('reqLength');
        const reqUpper = document.getElementById('reqUpper');
        const reqLower = document.getElementById('reqLower');
        const reqNumber = document.getElementById('reqNumber');
        const reqNoSpace = document.getElementById('reqNoSpace');
        const matchMessage = document.getElementById('matchMessage');
        
        // Barra de fortaleza
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
            
            // Actualizar indicadores visuales
            updateRequirement(reqLength, validations.length, "Mínimo 8 caracteres");
            updateRequirement(reqUpper, validations.upper, "Una mayúscula");
            updateRequirement(reqLower, validations.lower, "Una minúscula");
            updateRequirement(reqNumber, validations.number, "Un número");
            updateRequirement(reqNoSpace, validations.noSpace, "Sin espacios");
            
            // Calcular fortaleza
            strength = Object.values(validations).filter(v => v === true).length;
            
            // Actualizar barra y texto
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
            
            // Verificar coincidencia
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
                    confirmPassword.style.borderColor = '#32cd32';
                    return true;
                } else {
                    matchMessage.innerHTML = '❌ Las contraseñas no coinciden';
                    matchMessage.style.color = '#ff4500';
                    confirmPassword.style.borderColor = '#ff4500';
                    return false;
                }
            } else {
                matchMessage.innerHTML = '';
                confirmPassword.style.borderColor = '';
                return false;
            }
        }
        
        // Event listeners
        password.addEventListener('input', function() {
            checkPasswordStrength();
        });
        
        confirmPassword.addEventListener('input', function() {
            checkPasswordMatch();
            checkPasswordStrength();
        });
        
        // Validación antes de enviar
        document.getElementById('registroForm').addEventListener('submit', function(e) {
            const isStrong = checkPasswordStrength();
            const doMatch = password.value === confirmPassword.value;
            const isTermsChecked = document.getElementById('terms').checked;
            
            if(!isStrong) {
                e.preventDefault();
                alert('🔒 Por favor, crea una contraseña más segura.\n\nRequisitos:\n• Mínimo 8 caracteres\n• Al menos una MAYÚSCULA\n• Al menos una minúscula\n• Al menos un número\n• Sin espacios');
                password.focus();
                return false;
            }
            
            if(!doMatch) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden');
                confirmPassword.focus();
                return false;
            }
            
            if(!isTermsChecked) {
                e.preventDefault();
                alert('📋 Debes aceptar los términos y condiciones para registrarte');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>