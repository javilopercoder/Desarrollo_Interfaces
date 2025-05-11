<?php
/**
 * Panel de administración - Edición de usuario
 */

// Verificar rutas duplicadas
require_once '../includes/verificar_rutas.php';

// Incluir archivo de conexión
require_once '../includes/conexion.php';

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    $_SESSION['mensaje'] = 'Acceso denegado. Debes ser administrador para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ../index.php');
    exit;
}

// Verificar si se proporcionó un ID de usuario
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de usuario no válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: usuarios.php');
    exit;
}

$id_usuario = (int)$_GET['id'];

// Obtener información del usuario
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    // Verificar si el usuario existe
    if (!$usuario) {
        $_SESSION['mensaje'] = 'El usuario solicitado no existe.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: usuarios.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el usuario: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: usuarios.php');
    exit;
}

// Inicializar variables
$errores = [];
$nombre = $usuario['nombre'];
$correo = $usuario['correo'];
$rol = $usuario['rol'];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $correo = sanitizar($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    $rol = sanitizar($_POST['rol'] ?? 'usuario');
    $cambiar_contrasena = isset($_POST['cambiar_contrasena']) && $_POST['cambiar_contrasena'] === '1';
    
    // Validaciones
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio.';
    }
    
    if (empty($correo)) {
        $errores['correo'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El formato del correo electrónico no es válido.';
    } else {
        // Verificar si el correo ya está registrado (excluyendo el usuario actual)
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ? AND id_usuario != ?');
            $stmt->execute([$correo, $id_usuario]);
            if ($stmt->fetchColumn() > 0) {
                $errores['correo'] = 'Este correo electrónico ya está registrado por otro usuario.';
            }
        } catch (PDOException $e) {
            $errores['db'] = 'Error al verificar el correo: ' . $e->getMessage();
        }
    }
    
    // Validar contraseña si se va a cambiar
    if ($cambiar_contrasena) {
        if (empty($contrasena)) {
            $errores['contrasena'] = 'La contraseña es obligatoria.';
        } elseif (strlen($contrasena) < 6) {
            $errores['contrasena'] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        
        if ($contrasena !== $confirmar_contrasena) {
            $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
        }
    }
    
    if (!in_array($rol, ['usuario', 'soporte', 'administrador'])) {
        $errores['rol'] = 'Rol no válido.';
    }
    
    // Validar que no se esté quitando el último administrador
    if ($usuario['rol'] === 'administrador' && $rol !== 'administrador') {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'");
            $total_admin = $stmt->fetchColumn();
            
            if ($total_admin <= 1) {
                $errores['rol'] = 'No se puede cambiar el rol del último administrador del sistema.';
            }
        } catch (PDOException $e) {
            $errores['db'] = 'Error al verificar administradores: ' . $e->getMessage();
        }
    }
    
    // Si no hay errores, actualizar el usuario
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Preparar la consulta SQL dependiendo de si se cambia la contraseña o no
            if ($cambiar_contrasena) {
                $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
                $stmt = $db->prepare('UPDATE usuarios SET nombre = ?, correo = ?, contrasena = ?, rol = ? WHERE id_usuario = ?');
                $stmt->execute([$nombre, $correo, $contrasena_hash, $rol, $id_usuario]);
            } else {
                $stmt = $db->prepare('UPDATE usuarios SET nombre = ?, correo = ?, rol = ? WHERE id_usuario = ?');
                $stmt->execute([$nombre, $correo, $rol, $id_usuario]);
            }
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Usuario actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la lista de usuarios
            header('Location: usuarios.php');
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al actualizar el usuario: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Editar Usuario';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Editar Usuario: <?php echo htmlspecialchars($usuario['nombre']); ?></h1>
    <div class="admin-actions">
        <a href="usuarios.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a usuarios
        </a>
    </div>
</div>

<div class="form-container">
    <?php if (isset($errores['db'])): ?>
        <div class="alert alert-error"><?php echo $errores['db']; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="editar_usuario.php?id=<?php echo $id_usuario; ?>" class="admin-form">
        <div class="form-group">
            <label for="nombre">Nombre completo *</label>
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
        
        <div class="form-group password-toggle">
            <label>
                <input type="checkbox" name="cambiar_contrasena" value="1" id="cambiar_contrasena">
                Cambiar contraseña
            </label>
        </div>
        
        <div id="password-fields" style="display: none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="contrasena">Nueva contraseña</label>
                    <div class="password-toggle-container">
                        <input 
                            type="password" 
                            id="contrasena" 
                            name="contrasena" 
                            class="<?php echo isset($errores['contrasena']) ? 'error' : ''; ?>"
                        >
                        <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errores['contrasena'])): ?>
                        <div class="error-message"><?php echo $errores['contrasena']; ?></div>
                    <?php endif; ?>
                    <small>Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_contrasena">Confirmar nueva contraseña</label>
                    <div class="password-toggle-container">
                        <input 
                            type="password" 
                            id="confirmar_contrasena" 
                            name="confirmar_contrasena" 
                            class="<?php echo isset($errores['confirmar_contrasena']) ? 'error' : ''; ?>"
                        >
                        <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (isset($errores['confirmar_contrasena'])): ?>
                        <div class="error-message"><?php echo $errores['confirmar_contrasena']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="rol">Rol *</label>
            <select 
                id="rol" 
                name="rol" 
                class="<?php echo isset($errores['rol']) ? 'error' : ''; ?>"
                required
            >
                <option value="usuario" <?php echo $rol === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                <option value="soporte" <?php echo $rol === 'soporte' ? 'selected' : ''; ?>>Soporte</option>
                <option value="administrador" <?php echo $rol === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
            </select>
            <?php if (isset($errores['rol'])): ?>
                <div class="error-message"><?php echo $errores['rol']; ?></div>
            <?php endif; ?>
            
            <?php if ($usuario['rol'] === 'administrador' && $id_usuario === $_SESSION['usuario_id']): ?>
                <small class="warning">Ten cuidado al cambiar tu propio rol. Si te quitas los permisos de administrador, 
                no podrás volver a acceder a esta sección.</small>
            <?php endif; ?>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Guardar cambios</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.admin-form {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

.password-toggle label {
    display: inline-flex;
    align-items: center;
    font-weight: normal;
    cursor: pointer;
}

.password-toggle input[type="checkbox"] {
    margin-right: 8px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 1rem;
}

.form-group input.error,
.form-group select.error {
    border-color: #e53935;
}

.form-group small {
    color: #666;
    font-size: 0.85rem;
    display: block;
    margin-top: 5px;
}

.form-group small.warning {
    color: #ff9800;
}

.error-message {
    color: #e53935;
    font-size: 0.85rem;
    margin-top: 5px;
}

.form-actions {
    display: flex;
    justify-content: flex-start;
    gap: 10px;
    margin-top: 30px;
}

@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cambiarContrasenaCheckbox = document.getElementById('cambiar_contrasena');
    const passwordFields = document.getElementById('password-fields');
    
    cambiarContrasenaCheckbox.addEventListener('change', function() {
        passwordFields.style.display = this.checked ? 'block' : 'none';
        
        // Resetear campos de contraseña cuando se desactiva la opción
        if (!this.checked) {
            document.getElementById('contrasena').value = '';
            document.getElementById('confirmar_contrasena').value = '';
        }
    });
    
    // Si hay errores en los campos de contraseña, mostrar la sección
    <?php if (isset($errores['contrasena']) || isset($errores['confirmar_contrasena'])): ?>
    cambiarContrasenaCheckbox.checked = true;
    passwordFields.style.display = 'block';
    <?php endif; ?>
});
</script>

<!-- Script para mostrar/ocultar contraseña -->
<script src="../assets/js/password-toggle.js"></script>

<?php
// Incluir footer
include '../includes/footer.php';
?>
