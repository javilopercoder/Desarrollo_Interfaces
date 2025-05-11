<?php
/**
 * Página de registro de nuevos usuarios
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Inicializar variables
$errores = [];
$nombre = '';
$correo = '';
$contrasena = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $correo = sanitizar($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    
    // Validar nombre de usuario
    if (!validarNombreUsuario($nombre)) {
        $errores['nombre'] = 'El nombre debe tener entre 10 y 30 caracteres, sin caracteres especiales y no puede comenzar con un número.';
    }
    
    // Validar correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'Por favor, introduce un correo electrónico válido.';
    }
    
    // Validar contraseña
    if (!validarContrasena($contrasena)) {
        $errores['contrasena'] = 'La contraseña debe tener entre 5 y 20 caracteres, al menos un número y una letra mayúscula.';
    }
    
    // Verificar que el correo no existe
    if (empty($errores['correo'])) {
        $db = getDB();
        $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE correo = ?');
        $stmt->execute([$correo]);
        
        if ($stmt->fetch()) {
            $errores['correo'] = 'Este correo electrónico ya está registrado.';
        }
    }
    
    // Si no hay errores, registrar al usuario
    if (empty($errores)) {
        try {
            $db = getDB();
            $stmt = $db->prepare('INSERT INTO usuarios (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)');
            
            // Hash de la contraseña
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            
            // Por defecto, los nuevos usuarios tienen rol 'usuario'
            $stmt->execute([$nombre, $correo, $contrasena_hash, 'usuario']);
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Cuenta creada correctamente. Ahora puedes iniciar sesión.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la página de login
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al registrar el usuario: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Registro';

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Crear una cuenta</h2>
        
        <?php if (isset($errores['db'])): ?>
            <div class="alert alert-error"><?php echo $errores['db']; ?></div>
        <?php endif; ?>
        
        <form id="register-form" method="POST" action="registro.php">
            <div class="form-group">
                <label for="nombre">Nombre de usuario *</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    value="<?php echo htmlspecialchars($nombre); ?>"
                    class="<?php echo isset($errores['nombre']) ? 'error' : ''; ?>"
                    required
                >
                <?php if (isset($errores['nombre'])): ?>
                    <div class="error-message"><?php echo $errores['nombre']; ?></div>
                <?php endif; ?>
                <small>Entre 10 y 30 caracteres, sin caracteres especiales, no puede comenzar con un número.</small>
            </div>
            
            <div class="form-group">
                <label for="correo">Correo electrónico *</label>
                <input 
                    type="email" 
                    id="correo" 
                    name="correo" 
                    value="<?php echo htmlspecialchars($correo); ?>"
                    class="<?php echo isset($errores['correo']) ? 'error' : ''; ?>"
                    required
                >
                <?php if (isset($errores['correo'])): ?>
                    <div class="error-message"><?php echo $errores['correo']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña *</label>
                <div class="password-toggle-container">
                    <input 
                        type="password" 
                        id="contrasena" 
                        name="contrasena"
                        class="<?php echo isset($errores['contrasena']) ? 'error' : ''; ?>"
                        required
                    >
                    <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errores['contrasena'])): ?>
                    <div class="error-message"><?php echo $errores['contrasena']; ?></div>
                <?php endif; ?>
                <small>Entre 5 y 20 caracteres, al menos un número y una letra mayúscula.</small>
            </div>
            
            <button type="submit" class="btn btn-block">Registrarme</button>
        </form>
        
        <p class="text-center mt-3">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>
        </p>
    </div>
</section>

<!-- Script para mostrar/ocultar contraseña -->
<script src="assets/js/password-toggle.js"></script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
