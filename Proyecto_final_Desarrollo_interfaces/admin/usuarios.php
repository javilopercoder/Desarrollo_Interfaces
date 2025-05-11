<?php
/**
 * Panel de administración - Gestión de usuarios
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

// Procesar cambios de rol si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_rol'])) {
    $id_usuario = (int)$_POST['id_usuario'];
    $nuevo_rol = sanitizar($_POST['nuevo_rol']);
    
    // Validar el nuevo rol
    $roles_validos = ['usuario', 'soporte', 'administrador'];
    if (!in_array($nuevo_rol, $roles_validos)) {
        $_SESSION['mensaje'] = 'Rol no válido.';
        $_SESSION['mensaje_tipo'] = 'error';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE usuarios SET rol = ? WHERE id_usuario = ?');
            $stmt->execute([$nuevo_rol, $id_usuario]);
            
            $_SESSION['mensaje'] = 'Rol actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
        } catch (PDOException $e) {
            $_SESSION['mensaje'] = 'Error al actualizar el rol: ' . $e->getMessage();
            $_SESSION['mensaje_tipo'] = 'error';
        }
    }
}

// Obtener la lista de usuarios
try {
    $db = getDB();
    $stmt = $db->query('SELECT id_usuario, nombre, correo, rol, fecha_registro FROM usuarios ORDER BY nombre');
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener la lista de usuarios: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    $usuarios = [];
}

// Establecer título de página
$page_title = 'Gestión de Usuarios';

// Incluir header
include '../includes/header.php';
?>

<div class="admin-header">
    <h1>Gestión de Usuarios</h1>
    <div class="admin-actions">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al panel
        </a>
        <a href="nuevo_usuario.php" class="btn">
            <i class="fas fa-user-plus"></i> Crear nuevo usuario
        </a>
    </div>
</div>

<div class="dashboard-section">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol actual</th>
                    <th>Fecha de registro</th>
                    <th>Cambiar rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?php echo $usuario['id_usuario']; ?></td>
                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($usuario['correo']); ?></td>
                    <td>
                        <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                            <?php echo ucfirst($usuario['rol']); ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                    <td>
                        <form method="POST" action="usuarios.php" class="role-form">
                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id_usuario']; ?>">
                            <select name="nuevo_rol" class="role-select">
                                <option value="usuario" <?php echo $usuario['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                                <option value="soporte" <?php echo $usuario['rol'] === 'soporte' ? 'selected' : ''; ?>>Soporte</option>
                                <option value="administrador" <?php echo $usuario['rol'] === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                            <button type="submit" name="cambiar_rol" class="btn btn-sm">Cambiar</button>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="editar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn-icon" title="Editar usuario">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <?php if ($usuario['id_usuario'] != $_SESSION['usuario_id']): // No permitir eliminar el propio usuario ?>
                        <a href="eliminar_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn-icon btn-danger" 
                           title="Eliminar usuario" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.');">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <span class="btn-icon btn-disabled" title="No puedes eliminar tu propio usuario">
                            <i class="fas fa-trash"></i>
                        </span>
                        <?php endif; ?>
                        
                        <a href="ver_tickets_usuario.php?id=<?php echo $usuario['id_usuario']; ?>" class="btn-icon" title="Ver tickets del usuario">
                            <i class="fas fa-ticket-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background-color: #f5f5f5;
    font-weight: bold;
    color: #333;
}

.data-table tbody tr:hover {
    background-color: #f9f9f9;
}

.role-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
}

.role-administrador {
    background-color: rgba(255, 99, 132, 0.2);
    color: #e53935;
}

.role-soporte {
    background-color: rgba(54, 162, 235, 0.2);
    color: #1e88e5;
}

.role-usuario {
    background-color: rgba(75, 192, 192, 0.2);
    color: #26a69a;
}

.role-form {
    display: flex;
    align-items: center;
    gap: 5px;
}

.role-select {
    padding: 5px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 0.85rem;
}

.actions {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
}

.btn-icon {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 32px;
    height: 32px;
    border-radius: 4px;
    background-color: #f0f0f0;
    color: #333;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: #e0e0e0;
}

.btn-danger {
    background-color: #ffebee;
    color: #e53935;
}

.btn-danger:hover {
    background-color: #ffcdd2;
}

.btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

<?php
// Incluir footer
include '../includes/footer.php';
?>
