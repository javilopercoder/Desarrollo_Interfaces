<?php
/**
 * Página para editar un ticket existente
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para editar tickets.';
    $_SESSION['mensaje_tipo'] = 'warning';
    header('Location: login.php');
    exit;
}

// Verificar si se proporcionó un ID de ticket
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de ticket no válido.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
    exit;
}

$id_ticket = (int)$_GET['id'];

try {
    $db = getDB();
    
    // Obtener información del ticket
    $stmt = $db->prepare('SELECT * FROM tickets WHERE id_ticket = ?');
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch();
    
    // Verificar si el ticket existe
    if (!$ticket) {
        $_SESSION['mensaje'] = 'El ticket solicitado no existe.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Verificar si el usuario tiene permiso para editar este ticket
    // (debe ser el propietario, o un administrador)
    if ($ticket['id_usuario'] != $_SESSION['usuario_id'] && $_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje'] = 'No tienes permiso para editar este ticket.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Verificar si el ticket está cerrado (no se puede editar)
    if ($ticket['estado'] === 'cerrado' && $_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje'] = 'No se puede editar un ticket cerrado.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: ver_ticket.php?id=' . $id_ticket);
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar el ticket: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: tickets.php');
    exit;
}

// Inicializar variables
$errores = [];
$titulo = $ticket['titulo'];
$descripcion = $ticket['descripcion'];
$categoria = $ticket['categoria'];
$prioridad = $ticket['prioridad'];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $descripcion = sanitizar($_POST['descripcion'] ?? '');
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $prioridad = sanitizar($_POST['prioridad'] ?? '');
    
    // Validaciones
    if (empty($titulo)) {
        $errores['titulo'] = 'El título es obligatorio.';
    }
    
    if (empty($descripcion)) {
        $errores['descripcion'] = 'La descripción es obligatoria.';
    }
    
    if (empty($categoria)) {
        $errores['categoria'] = 'Debes seleccionar una categoría.';
    }
    
    if (empty($prioridad)) {
        $errores['prioridad'] = 'Debes seleccionar una prioridad.';
    }
    
    // Si no hay errores, actualizar el ticket
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Iniciar transacción
            $db->beginTransaction();
            
            // Actualizar el ticket
            $stmt = $db->prepare('
                UPDATE tickets 
                SET titulo = ?, descripcion = ?, categoria = ?, prioridad = ?
                WHERE id_ticket = ?
            ');
            $stmt->execute([$titulo, $descripcion, $categoria, $prioridad, $id_ticket]);
            
            // Registrar la acción en el historial
            $stmt = $db->prepare('INSERT INTO acciones (id_ticket, id_usuario, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([$id_ticket, $_SESSION['usuario_id'], 'Ticket actualizado']);
            
            // Procesar archivo adjunto si existe
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                $archivo_nombre = $_FILES['adjunto']['name'];
                $archivo_tmp = $_FILES['adjunto']['tmp_name'];
                $archivo_tipo = $_FILES['adjunto']['type'];
                
                // Crear directorio si no existe
                $dir_adjuntos = 'uploads/tickets/' . $id_ticket;
                if (!file_exists($dir_adjuntos)) {
                    mkdir($dir_adjuntos, 0777, true);
                }
                
                // Generar nombre único para el archivo
                $archivo_extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);
                $archivo_nuevo_nombre = uniqid() . '.' . $archivo_extension;
                $archivo_ruta = $dir_adjuntos . '/' . $archivo_nuevo_nombre;
                
                // Mover el archivo
                if (move_uploaded_file($archivo_tmp, $archivo_ruta)) {
                    // Guardar información del archivo en la base de datos
                    $stmt = $db->prepare('INSERT INTO archivos (id_ticket, nombre_archivo, ruta_archivo, tipo_archivo) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$id_ticket, $archivo_nombre, $archivo_ruta, $archivo_tipo]);
                } else {
                    throw new Exception('Error al subir el archivo adjunto.');
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Ticket actualizado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la página de ver ticket
            header('Location: ver_ticket.php?id=' . $id_ticket);
            exit;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollBack();
            $errores['db'] = 'Error al actualizar el ticket: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Editar Ticket #' . $id_ticket;

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Editar Ticket #<?php echo $id_ticket; ?></h2>
        
        <?php if (isset($errores['db'])): ?>
            <div class="alert alert-error"><?php echo $errores['db']; ?></div>
        <?php endif; ?>
        
        <form id="ticket-form" method="POST" action="editar_ticket.php?id=<?php echo $id_ticket; ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titulo">Asunto *</label>
                <input 
                    type="text" 
                    id="titulo" 
                    name="titulo" 
                    value="<?php echo htmlspecialchars($titulo); ?>"
                    class="<?php echo isset($errores['titulo']) ? 'error' : ''; ?>"
                    required
                >
                <?php if (isset($errores['titulo'])): ?>
                    <div class="error-message"><?php echo $errores['titulo']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="categoria">Tipo *</label>
                    <select 
                        id="categoria" 
                        name="categoria" 
                        class="<?php echo isset($errores['categoria']) ? 'error' : ''; ?>"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="software" <?php echo $categoria === 'software' ? 'selected' : ''; ?>>Software</option>
                        <option value="hardware" <?php echo $categoria === 'hardware' ? 'selected' : ''; ?>>Hardware</option>
                        <option value="conexion" <?php echo $categoria === 'conexion' ? 'selected' : ''; ?>>Conexión</option>
                        <option value="otro" <?php echo $categoria === 'otro' ? 'selected' : ''; ?>>Otro</option>
                    </select>
                    <?php if (isset($errores['categoria'])): ?>
                        <div class="error-message"><?php echo $errores['categoria']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="prioridad">Prioridad *</label>
                    <select 
                        id="prioridad" 
                        name="prioridad" 
                        class="<?php echo isset($errores['prioridad']) ? 'error' : ''; ?>"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="baja" <?php echo $prioridad === 'baja' ? 'selected' : ''; ?>>Baja</option>
                        <option value="media" <?php echo $prioridad === 'media' ? 'selected' : ''; ?>>Media</option>
                        <option value="alta" <?php echo $prioridad === 'alta' ? 'selected' : ''; ?>>Alta</option>
                    </select>
                    <?php if (isset($errores['prioridad'])): ?>
                        <div class="error-message"><?php echo $errores['prioridad']; ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción *</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="formatText('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" onclick="formatText('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" onclick="formatText('underline')"><i class="fas fa-underline"></i></button>
                    <button type="button" onclick="formatText('list')"><i class="fas fa-list-ul"></i></button>
                    <button type="button" onclick="formatText('ol')"><i class="fas fa-list-ol"></i></button>
                </div>
                <textarea 
                    id="descripcion" 
                    name="descripcion" 
                    class="<?php echo isset($errores['descripcion']) ? 'error' : ''; ?>"
                    required
                ><?php echo htmlspecialchars($descripcion); ?></textarea>
                <?php if (isset($errores['descripcion'])): ?>
                    <div class="error-message"><?php echo $errores['descripcion']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="adjunto">Archivo adjunto (opcional)</label>
                <input type="file" id="adjunto" name="adjunto">
                <small>Archivos permitidos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máx. 5 MB)</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="ver_ticket.php?id=<?php echo $id_ticket; ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>

<script>
// Funciones simples para el editor de texto
document.addEventListener('DOMContentLoaded', function() {
    window.formatText = function(format) {
        const descripcion = document.getElementById('descripcion');
        let start = descripcion.selectionStart;
        let end = descripcion.selectionEnd;
        let selectedText = descripcion.value.substring(start, end);
        let replacement = '';
        
        switch(format) {
            case 'bold':
                replacement = `**${selectedText}**`;
                break;
            case 'italic':
                replacement = `*${selectedText}*`;
                break;
            case 'underline':
                replacement = `_${selectedText}_`;
                break;
            case 'list':
                replacement = selectedText.split('\n').map(line => `- ${line}`).join('\n');
                break;
            case 'ol':
                replacement = selectedText.split('\n').map((line, i) => `${i+1}. ${line}`).join('\n');
                break;
        }
        
        descripcion.value = descripcion.value.substring(0, start) + replacement + descripcion.value.substring(end);
        descripcion.focus();
        descripcion.setSelectionRange(start, start + replacement.length);
    };
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
