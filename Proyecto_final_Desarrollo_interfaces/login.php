<?php
/**
 * Página de inicio de sesión
 */

// Incluir archivos necesarios
require_once 'includes/conexion.php';
require_once 'includes/utilidades.php';
require_once 'includes/sesion.php';

// Iniciar sesión
iniciarSesion();

// Si el usuario ya está autenticado, redirigir al inicio
if (estaAutenticado()) {
    header('Location: index.php');
    exit;
}

// Inicializar variables
$errores = [];
$correo = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $correo = sanitizar($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $recordar = isset($_POST['recordar']);
    
    // Validaciones básicas
    if (empty($correo)) {
        $errores['correo'] = 'Por favor, introduce tu correo electrónico.';
    } elseif (!validarEmail($correo)) {
        $errores['correo'] = 'Por favor, introduce un correo electrónico válido.';
    }
    
    if (empty($contrasena)) {
        $errores['contrasena'] = 'Por favor, introduce tu contraseña.';
    }
    
    // Verificar credenciales
    if (empty($errores)) {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT id_usuario, nombre, correo, rol, contraseña FROM usuarios WHERE correo = ?');
            $stmt->execute([$correo]);
            $usuario = $stmt->fetch();
            
            // Para depuración, guardar información en un archivo de log
            $log_file = __DIR__ . '/logs/login_debug.txt';
            $log_message = date('Y-m-d H:i:s') . " - Email: $correo - ";
            $log_message .= "Usuario encontrado: " . ($usuario ? "SÍ" : "NO");
            
            if ($usuario) {
                $log_message .= " - Password verify: " . (password_verify($contrasena, $usuario['contraseña']) ? "SÍ" : "NO");
                $log_message .= " - Hash almacenado: " . substr($usuario['contraseña'], 0, 20) . "...";
            }
            
            file_put_contents($log_file, $log_message . "\n", FILE_APPEND);
            
            if ($usuario && password_verify($contrasena, $usuario['contraseña'])) {
                // Autenticar usuario
                autenticarUsuario($usuario);
                
                // Establecer cookie si se marcó "recordarme"
                establecerCookieUsuario($usuario, $recordar);
                
                // Registrar acceso en archivo de log
                registrarAcceso($usuario['nombre']);
                
                // Mensaje de éxito
                $_SESSION['mensaje'] = 'Has iniciado sesión correctamente.';
                $_SESSION['mensaje_tipo'] = 'success';
                
                // Redirigir según el rol
                if ($usuario['rol'] === 'administrador') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $errores['login'] = 'Correo electrónico o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $errores['db'] = 'Error al iniciar sesión: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Iniciar sesión';

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Iniciar sesión</h2>
        
        <?php if (isset($errores['db'])): ?>
            <div class="alert alert-error"><?php echo $errores['db']; ?></div>
        <?php endif; ?>
        
        <?php if (isset($errores['login'])): ?>
            <div class="alert alert-error"><?php echo $errores['login']; ?></div>
        <?php endif; ?>
        
        <form id="login-form" method="POST" action="login.php">
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
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="recordar"> Recordarme
                </label>
            </div>
            
            <button type="submit" class="btn btn-block">Iniciar sesión</button>
        </form>
        
        <p class="text-center mt-3">
            ¿No tienes una cuenta? <a href="registro.php">Regístrate</a>
        </p>
    </div>
</section>

<!-- Script para mostrar/ocultar contraseña -->
<script src="assets/js/password-toggle.js"></script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
