<?php
/**
 * Panel de administración - Eliminar usuario
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

// No permitir eliminar el propio usuario
if ($id_usuario === $_SESSION['usuario_id']) {
    $_SESSION['mensaje'] = 'No puedes eliminar tu propio usuario.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: usuarios.php');
    exit;
}

// Verificar si el usuario existe y obtener información
try {
    $db = getDB();
    
    // Obtener información del usuario
    $stmt = $db->prepare('SELECT nombre, rol FROM usuarios WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        $_SESSION['mensaje'] = 'El usuario solicitado no existe.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: usuarios.php');
        exit;
    }
    
    // Verificar si es el último administrador
    if ($usuario['rol'] === 'administrador') {
        $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador'");
        $total_admin = $stmt->fetchColumn();
        
        if ($total_admin <= 1) {
            $_SESSION['mensaje'] = 'No se puede eliminar el último administrador del sistema.';
            $_SESSION['mensaje_tipo'] = 'error';
            header('Location: usuarios.php');
            exit;
        }
    }
    
    // Procesamiento para eliminación
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === '1') {
        // Iniciar transacción
        $db->beginTransaction();
        
        // Opciones para manejar los tickets del usuario
        $opcion = $_POST['opcion'] ?? '';
        
        if ($opcion === 'transferir') {
            $nuevo_usuario_id = (int)$_POST['nuevo_usuario_id'] ?? 0;
            
            // Verificar si el usuario destino existe
            $stmt = $db->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario = ?');
            $stmt->execute([$nuevo_usuario_id]);
            if (!$stmt->fetch()) {
                throw new Exception('El usuario destino seleccionado no existe.');
            }
            
            // Transferir tickets al nuevo usuario
            $stmt = $db->prepare('UPDATE tickets SET id_usuario = ? WHERE id_usuario = ?');
            $stmt->execute([$nuevo_usuario_id, $id_usuario]);
            
            // Actualizar acciones para mantener la integridad referencial
            $stmt = $db->prepare('UPDATE acciones SET id_usuario = ? WHERE id_usuario = ?');
            $stmt->execute([$nuevo_usuario_id, $id_usuario]);
        } elseif ($opcion === 'eliminar') {
            // Eliminar primero las acciones relacionadas a los tickets del usuario
            $stmt = $db->prepare('
                DELETE FROM acciones 
                WHERE id_ticket IN (SELECT id_ticket FROM tickets WHERE id_usuario = ?)
            ');
            $stmt->execute([$id_usuario]);
            
            // Eliminar archivos asociados a los tickets del usuario
            $stmt = $db->prepare('
                DELETE FROM archivos 
                WHERE id_ticket IN (SELECT id_ticket FROM tickets WHERE id_usuario = ?)
            ');
            $stmt->execute([$id_usuario]);
            
            // Obtener IDs de tickets para eliminar archivos físicos
            $stmt = $db->prepare('SELECT id_ticket FROM tickets WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);
            $tickets_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Eliminar tickets del usuario
            $stmt = $db->prepare('DELETE FROM tickets WHERE id_usuario = ?');
            $stmt->execute([$id_usuario]);
            
            // Eliminar archivos físicos
            foreach ($tickets_ids as $ticket_id) {
                $dir_adjuntos = '../uploads/tickets/' . $ticket_id;
                if (file_exists($dir_adjuntos)) {
                    // Función recursiva para eliminar directorio
                    function eliminarDirectorio($dir) {
                        if (is_dir($dir)) {
                            $objetos = scandir($dir);
                            foreach ($objetos as $objeto) {
                                if ($objeto != '.' && $objeto != '..') {
                                    $ruta = $dir . '/' . $objeto;
                                    if (is_dir($ruta)) {
                                        eliminarDirectorio($ruta);
                                    } else {
                                        unlink($ruta);
                                    }
                                }
                            }
                            rmdir($dir);
                        }
                    }
                    
                    eliminarDirectorio($dir_adjuntos);
                }
            }
        } else {
            throw new Exception('Debe seleccionar una opción para los tickets del usuario.');
        }
        
        // Eliminar el usuario
        $stmt = $db->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
        $stmt->execute([$id_usuario]);
        
        // Confirmar transacción
        $db->commit();
        
        $_SESSION['mensaje'] = 'Usuario eliminado correctamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: usuarios.php');
        exit;
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Si llegamos aquí es porque se envió el formulario pero faltaron datos
        $_SESSION['mensaje'] = 'Debe confirmar la eliminación y seleccionar una opción para los tickets.';
        $_SESSION['mensaje_tipo'] = 'warning';
    }
    
    // Verificar si el usuario tiene tickets
    $stmt = $db->prepare('SELECT COUNT(*) FROM tickets WHERE id_usuario = ?');
    $stmt->execute([$id_usuario]);
    $total_tickets = $stmt->fetchColumn();
    
    // Obtener lista de otros usuarios para transferir tickets
    $stmt = $db->prepare('SELECT id_usuario, nombre FROM usuarios WHERE id_usuario != ? ORDER BY nombre');
    $stmt->execute([$id_usuario]);
    $otros_usuarios = $stmt->fetchAll();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: usuarios.php');
    exit;
}

// Establecer título de página
$page_title = 'Eliminar Usuario';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Eliminar Usuario: <?php echo htmlspecialchars($usuario['nombre']); ?></h1>
    <div class="admin-actions">
        <a href="usuarios.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a usuarios
        </a>
    </div>
</div>

<div class="form-container">
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>¡Atención!</strong> Esta acción no se puede deshacer. Se eliminará permanentemente la cuenta de usuario.
    </div>
    
    <div class="admin-form">
        <div class="user-info">
            <div class="user-detail">
                <strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre']); ?>
            </div>
            <div class="user-detail">
                <strong>Rol:</strong> <?php echo ucfirst($usuario['rol']); ?>
            </div>
            <?php if ($total_tickets > 0): ?>
                <div class="user-detail">
                    <strong>Tickets asociados:</strong> <?php echo $total_tickets; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="eliminar_usuario.php?id=<?php echo $id_usuario; ?>">
            <?php if ($total_tickets > 0): ?>
                <div class="form-section">
                    <h3>¿Qué deseas hacer con los tickets de este usuario?</h3>
                    
                    <div class="radio-option">
                        <input type="radio" id="transferir" name="opcion" value="transferir" required>
                        <label for="transferir">Transferir tickets a otro usuario</label>
                        
                        <div class="conditional-section" id="transferir-section" style="display: none;">
                            <label for="nuevo_usuario_id">Selecciona el usuario destino:</label>
                            <select id="nuevo_usuario_id" name="nuevo_usuario_id">
                                <?php foreach ($otros_usuarios as $otro_usuario): ?>
                                    <option value="<?php echo $otro_usuario['id_usuario']; ?>">
                                        <?php echo htmlspecialchars($otro_usuario['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="radio-option">
                        <input type="radio" id="eliminar" name="opcion" value="eliminar" required>
                        <label for="eliminar">Eliminar todos los tickets del usuario</label>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="confirm-section">
                <div class="checkbox-option">
                    <input type="checkbox" id="confirmar" name="confirmar" value="1" required>
                    <label for="confirmar">
                        Confirmo que quiero eliminar este usuario y entiendo que esta acción es irreversible.
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar usuario
                </button>
                <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<style>
.admin-form {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.user-info {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.user-detail {
    margin-bottom: 8px;
}

.user-detail:last-child {
    margin-bottom: 0;
}

.form-section {
    margin-bottom: 25px;
    border-bottom: 1px solid #eee;
    padding-bottom: 20px;
}

.form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.radio-option,
.checkbox-option {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
}

.radio-option input[type="radio"],
.checkbox-option input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 4px;
}

.conditional-section {
    margin-left: 25px;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
    margin-top: 10px;
}

.conditional-section label {
    display: block;
    margin-bottom: 8px;
}

.conditional-section select {
    width: 100%;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.confirm-section {
    background-color: #fff8e1;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border-left: 4px solid #ffb300;
}

.alert {
    margin-bottom: 20px;
}

.btn-danger {
    background-color: #f44336;
    color: #fff;
}

.btn-danger:hover {
    background-color: #e53935;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const transferirRadio = document.getElementById('transferir');
    const eliminarRadio = document.getElementById('eliminar');
    const transferirSection = document.getElementById('transferir-section');
    
    // Función para actualizar la sección condicional
    function updateConditionalSection() {
        if (transferirRadio.checked) {
            transferirSection.style.display = 'block';
        } else {
            transferirSection.style.display = 'none';
        }
    }
    
    // Inicializar
    updateConditionalSection();
    
    // Agregar listeners
    transferirRadio.addEventListener('change', updateConditionalSection);
    eliminarRadio.addEventListener('change', updateConditionalSection);
});
</script>

<?php
// Incluir footer
include '../includes/footer.php';
?>
