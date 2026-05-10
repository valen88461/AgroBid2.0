
<?php
session_start();
include(__DIR__ . "/includes/conexion.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

$subasta_id = isset($_GET['id'])
    ? intval($_GET['id'])
    : (isset($_POST['subasta_id']) ? intval($_POST['subasta_id']) : 0);

$error = "";

// Verificar subasta
$sql_check = "SELECT s.*, u.nombre as vendedor_nombre
              FROM subastas s
              JOIN usuarios u ON s.usuario_id = u.id
              WHERE s.id = $subasta_id
              AND s.ganador_id = $usuario_id
              AND s.entregado = 1
              AND s.calificado = 0";

$result_check = mysqli_query($conexion, $sql_check);

if(!$result_check){
    die("Error SQL: " . mysqli_error($conexion));
}

if(mysqli_num_rows($result_check) == 0){
    header("Location: mis_compras.php?error=No puedes calificar este producto");
    exit();
}

$subasta = mysqli_fetch_assoc($result_check);

// Procesar formulario
if(isset($_POST['calificar'])){

    $puntuacion = intval($_POST['puntuacion']);
    $comentario = trim(mysqli_real_escape_string($conexion, $_POST['comentario']));
    $vendedor_id = $subasta['usuario_id'];

    if($puntuacion < 1 || $puntuacion > 5){
        $error = "❌ Debes seleccionar entre 1 y 5 estrellas";
    } else {

        $sql_insert = "INSERT INTO calificaciones
        (subasta_id, comprador_id, vendedor_id, puntuacion, comentario)
        VALUES
        ($subasta_id, $usuario_id, $vendedor_id, $puntuacion, '$comentario')";

        if(mysqli_query($conexion, $sql_insert)){

            // Marcar subasta como calificada
            mysqli_query($conexion,
                "UPDATE subastas SET calificado = 1 WHERE id = $subasta_id"
            );

            // Promedio reputación
            $sql_avg = "SELECT AVG(puntuacion) as promedio
                        FROM calificaciones
                        WHERE vendedor_id = $vendedor_id";

            $result_avg = mysqli_query($conexion, $sql_avg);
            $avg = mysqli_fetch_assoc($result_avg);

            $nueva_reputacion = round($avg['promedio'], 1);

            // Total calificaciones
            $sql_count = "SELECT COUNT(*) as total
                          FROM calificaciones
                          WHERE vendedor_id = $vendedor_id";

            $result_count = mysqli_query($conexion, $sql_count);
            $count = mysqli_fetch_assoc($result_count);

            mysqli_query($conexion,
                "UPDATE usuarios
                SET reputacion = $nueva_reputacion,
                total_calificaciones = {$count['total']}
                WHERE id = $vendedor_id"
            );

            header("Location: mis_compras.php?mensaje=✅ Gracias por calificar");
            exit();

        } else {
            $error = "❌ Error al guardar: " . mysqli_error($conexion);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Calificar Producto</title>

<link rel="stylesheet" href="css/estilos.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

.calificar-container{
    max-width:600px;
    margin:2rem auto;
}

.calificar-card{
    background:white;
    border-radius:20px;
    padding:2rem;
    box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

.estrellas{
    display:flex;
    justify-content:center;
    gap:10px;
    font-size:3rem;
    margin:1rem 0;
}

.estrella{
    color:#ddd;
    cursor:pointer;
    transition:0.2s;
}

.estrella.active{
    color:#ffd700;
}

.btn-enviar{
    width:100%;
    padding:1rem;
    border:none;
    border-radius:50px;
    background:#32cd32;
    color:white;
    font-weight:bold;
    cursor:pointer;
    margin-top:1rem;
}

.comentario-input{
    width:100%;
    padding:1rem;
    border-radius:10px;
    border:2px solid #ddd;
}

.alerta-error{
    background:#ffdede;
    color:#b30000;
    padding:1rem;
    border-radius:10px;
    margin-bottom:1rem;
}

</style>
</head>

<body>

<div class="calificar-container">

<div class="calificar-card">

<h2>⭐ Califica tu experiencia</h2>

<p>
<strong><?php echo htmlspecialchars($subasta['producto']); ?></strong>
</p>

<p>
Vendedor:
<?php echo htmlspecialchars($subasta['vendedor_nombre']); ?>
</p>

<?php if($error): ?>
<div class="alerta-error">
    <?php echo $error; ?>
</div>
<?php endif; ?>

<form method="POST"
action="calificar.php?id=<?php echo $subasta_id; ?>"
id="calificarForm">

<input type="hidden"
name="subasta_id"
value="<?php echo $subasta_id; ?>">

<input type="hidden"
name="puntuacion"
id="puntuacion">

<div class="estrellas">

<i class="fas fa-star estrella" data-valor="1"></i>
<i class="fas fa-star estrella" data-valor="2"></i>
<i class="fas fa-star estrella" data-valor="3"></i>
<i class="fas fa-star estrella" data-valor="4"></i>
<i class="fas fa-star estrella" data-valor="5"></i>

</div>

<div id="puntuacionTexto">
👆 Selecciona una puntuación
</div>

<br>

<textarea
name="comentario"
class="comentario-input"
rows="4"
placeholder="Escribe tu experiencia..."></textarea>

<button type="submit"
name="calificar"
class="btn-enviar">

⭐ Enviar calificación

</button>

</form>

</div>

</div>

<script>

const estrellas = document.querySelectorAll('.estrella');
const inputPuntuacion = document.getElementById('puntuacion');
const texto = document.getElementById('puntuacionTexto');

estrellas.forEach(estrella => {

    estrella.addEventListener('click', function(){

        let valor = this.dataset.valor;

        inputPuntuacion.value = valor;

        estrellas.forEach(e => {
            e.classList.remove('active');
        });

        for(let i = 0; i < valor; i++){
            estrellas[i].classList.add('active');
        }

        texto.innerHTML = "⭐ Calificación: " + valor + "/5";
    });

});

</script>

</body>
</html>
```
