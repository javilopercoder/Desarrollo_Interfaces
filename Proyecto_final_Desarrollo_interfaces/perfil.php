<?php
/**
 * Página de perfil de usuario
 */

// Verificar rutas duplicadas
require_once 'includes/verificar_rutas.php';

// Incluir archivos necesarios
require_once 'includes/conexion.php';
require_once 'includes/sesion.php';

// Verificar si el usuario está autenticado
if (!estaAutenticado()) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para acceder a tu perfil.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['mensaje'] = 'No se pudo encontrar la información del usuario.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al recuperar el perfil: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: index.php');
    exit;
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    // Obtener datos del formulario
    $nombre = sanitizar($_POST['nombre'] ?? '');
    
    // Validar datos
    $errores = [];
    
    if (empty($nombre)) {
        $errores['nombre'] = 'El nombre es obligatorio.';
    }
    
    // Actualizar perfil si no hay errores
    if (empty($errores)) {
        try {
            $stmt = $db->prepare('UPDATE usuarios SET nombre = ? WHERE id_usuario = ?');
            $stmt->execute([$nombre, $_SESSION['usuario_id']]);
            
            $_SESSION['mensaje'] = 'Perfil actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Actualizar el nombre en la sesión
            $_SESSION['nombre'] = $nombre;
            
            // Redirigir para evitar reenvío del formulario
            header('Location: perfil.php');
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al actualizar el perfil: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Mi perfil';

// Incluir header
include 'includes/header.php';
?>

<section>
    <h2 class="section-title">Mi perfil</h2>
    
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
            <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-container">
        <div class="card">
            <div class="card-header">
                <h3>Información personal</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="perfil.php">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        <?php if (isset($errores['nombre'])): ?>
                            <span class="error"><?php echo $errores['nombre']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="correo">Correo electrónico:</label>
                        <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" disabled>
                        <small>El correo electrónico no se puede cambiar.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <input type="text" id="rol" name="rol" value="<?php echo htmlspecialchars($usuario['rol']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de registro:</label>
                        <p><?php echo formatearFecha($usuario['fecha_registro']); ?></p>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">Actualizar perfil</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Cambiar contraseña</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="cambiar_contrasena.php">
                    <div class="form-group">
                        <label for="contrasena_actual">Contraseña actual:</label>
                        <div class="password-toggle-container">
                            <input type="password" id="contrasena_actual" name="contrasena_actual" required>
                            <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nueva_contrasena">Nueva contraseña:</label>
                        <div class="password-toggle-container">
                            <input type="password" id="nueva_contrasena" name="nueva_contrasena" required>
                            <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_contrasena">Confirmar contraseña:</label>
                        <div class="password-toggle-container">
                            <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
                            <button type="button" class="password-toggle-btn" title="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" name="cambiar_contrasena" class="btn btn-primary">Cambiar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Mis tickets recientes</h3>
            </div>
            <div class="card-body">
                <?php
                try {
                    $stmt = $db->prepare('SELECT * FROM tickets WHERE id_usuario = ? ORDER BY fecha_creacion DESC LIMIT 5');
                    $stmt->execute([$_SESSION['usuario_id']]);
                    $tickets = $stmt->fetchAll();
                    
                    if (count($tickets) > 0) {
                        echo '<ul class="ticket-list">';
                        foreach ($tickets as $ticket) {
                            echo '<li class="ticket-item">';
                            echo '<div class="ticket-title"><a href="ver_ticket.php?id=' . $ticket['id_ticket'] . '">' . htmlspecialchars($ticket['titulo']) . '</a></div>';
                            echo '<div class="ticket-status ' . $ticket['estado'] . '">' . htmlspecialchars($ticket['estado']) . '</div>';
                            echo '<div class="ticket-date">' . formatearFecha($ticket['fecha_creacion']) . '</div>';
                            echo '</li>';
                        }
                        echo '</ul>';
                        echo '<div class="view-all"><a href="tickets.php" class="btn">Ver todos mis tickets</a></div>';
                    } else {
                        echo '<p>No tienes tickets recientes.</p>';
                        echo '<div class="view-all"><a href="nuevo_ticket.php" class="btn">Crear un ticket</a></div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="alert alert-error">Error al cargar tickets: ' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Script para mostrar/ocultar contraseña -->
<script src="assets/js/password-toggle.js"></script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
