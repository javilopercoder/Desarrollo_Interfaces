<?php
/**
 * Panel de administración - Crear nuevo usuario
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

// Inicializar variables
$errores = [];
$nombre = '';
$correo = '';
$rol = 'usuario';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $nombre = sanitizar($_POST['nombre'] ?? '');
    $correo = sanitizar($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    $rol = sanitizar($_POST['rol'] ?? 'usuario');
    
    // Validaciones
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio.';
    }
    
    if (empty($correo)) {
        $errores['correo'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El formato del correo electrónico no es válido.';
    } else {
        // Verificar si el correo ya está registrado
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = ?');
            $stmt->execute([$correo]);
            if ($stmt->fetchColumn() > 0) {
                $errores['correo'] = 'Este correo electrónico ya está registrado.';
            }
        } catch (PDOException $e) {
            $errores['db'] = 'Error al verificar el correo: ' . $e->getMessage();
        }
    }
    
    if (empty($contrasena)) {
        $errores['contrasena'] = 'La contraseña es obligatoria.';
    } elseif (strlen($contrasena) < 6) {
        $errores['contrasena'] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    
    if ($contrasena !== $confirmar_contrasena) {
        $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
    }
    
    if (!in_array($rol, ['usuario', 'soporte', 'administrador'])) {
        $errores['rol'] = 'Rol no válido.';
    }
    
    // Si no hay errores, crear el usuario
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Hash de la contraseña
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            
            // Insertar el nuevo usuario
            $stmt = $db->prepare('INSERT INTO usuarios (nombre, correo, contrasena, rol, fecha_registro) VALUES (?, ?, ?, ?, datetime("now"))');
            $stmt->execute([$nombre, $correo, $contrasena_hash, $rol]);
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Usuario creado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la lista de usuarios
            header('Location: usuarios.php');
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al crear el usuario: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Crear Nuevo Usuario';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Crear Nuevo Usuario</h1>
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
    
    <form method="POST" action="nuevo_usuario.php" class="admin-form">
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
        
        <div class="form-row">
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
                <small>Mínimo 6 caracteres</small>
            </div>
            
            <div class="form-group">
                <label for="confirmar_contrasena">Confirmar contraseña *</label>
                <div class="password-toggle-container">
                    <input 
                        type="password" 
                        id="confirmar_contrasena" 
                        name="confirmar_contrasena" 
                        class="<?php echo isset($errores['confirmar_contrasena']) ? 'error' : ''; ?>"
                        required
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
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Crear usuario</button>
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

<?php
// Incluir footer
include '../includes/footer.php';
?>
