<?php
/**
 * Página para crear un nuevo ticket
 */

// Incluir archivos necesarios
require_once 'includes/conexion.php';
require_once 'includes/utilidades.php';
require_once 'includes/sesion.php';
require_once 'includes/archivos.php';

// Requerir que el usuario esté autenticado
requireLogin();

// Inicializar variables
$errores = [];
$titulo = '';
$descripcion = '';
$categoria = '';
$prioridad = '';

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
    
    // Si no hay errores, crear el ticket
    if (empty($errores)) {
        try {
            $db = getDB();
            
            // Iniciar transacción
            $db->beginTransaction();
            
            // Insertar el ticket
            $stmt = $db->prepare('INSERT INTO tickets (titulo, descripcion, categoria, prioridad, estado, id_usuario) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$titulo, $descripcion, $categoria, $prioridad, 'abierto', $_SESSION['usuario_id']]);
            
            // Obtener el ID del ticket recién creado
            $id_ticket = $db->lastInsertId();
            
            // Registrar la acción en el historial
            $stmt = $db->prepare('INSERT INTO acciones (id_ticket, id_usuario, descripcion) VALUES (?, ?, ?)');
            $stmt->execute([$id_ticket, $_SESSION['usuario_id'], 'Ticket creado']);
            
            // Procesar archivo adjunto si existe
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $resultado_subida = subirArchivo($_FILES['adjunto'], $id_ticket);
                
                if (!$resultado_subida['success']) {
                    throw new Exception('Error al subir el archivo: ' . $resultado_subida['error']);
                }
            }
            
            // Procesar varios archivos adjuntos si existen
            if (isset($_FILES['adjuntos'])) {
                $archivos = $_FILES['adjuntos'];
                $total_archivos = count($archivos['name']);
                
                for ($i = 0; $i < $total_archivos; $i++) {
                    if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                        $archivo = [
                            'name' => $archivos['name'][$i],
                            'type' => $archivos['type'][$i],
                            'tmp_name' => $archivos['tmp_name'][$i],
                            'error' => $archivos['error'][$i],
                            'size' => $archivos['size'][$i]
                        ];
                        
                        $resultado_subida = subirArchivo($archivo, $id_ticket);
                        
                        if (!$resultado_subida['success']) {
                            // Registrar error pero continuar con los demás archivos
                            error_log('Error al subir archivo: ' . $resultado_subida['error']);
                        }
                    }
                }
            }
            
            // Confirmar transacción
            $db->commit();
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Ticket creado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la página de ver ticket
            header('Location: ver_ticket.php?id=' . $id_ticket);
            exit;
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollBack();
            $errores['db'] = 'Error al crear el ticket: ' . $e->getMessage();
        }
    }
}

// Establecer título de página
$page_title = 'Nuevo Ticket';

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Crear nuevo ticket de soporte</h2>
        
        <?php if (isset($errores['db'])): ?>
            <div class="alert alert-error"><?php echo $errores['db']; ?></div>
        <?php endif; ?>
        
        <form id="ticket-form" method="POST" action="nuevo_ticket.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="titulo">Asunto *</label>
                <input 
                    type="text" 
                    id="titulo" 
                    name="titulo" 
                    value="<?php echo htmlspecialchars($titulo); ?>"
                    class="<?php echo isset($errores['titulo']) ? 'error' : ''; ?>"
                    required
                    placeholder="Breve descripción del problema"
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
                    placeholder="Escriba aquí"
                ><?php echo htmlspecialchars($descripcion); ?></textarea>
                <?php if (isset($errores['descripcion'])): ?>
                    <div class="error-message"><?php echo $errores['descripcion']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="adjunto">Archivo adjunto principal (opcional)</label>
                <input type="file" id="adjunto" name="adjunto">
                <small>Archivos permitidos: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (máx. 5 MB)</small>
            </div>
            
            <div class="form-group">
                <label for="adjuntos">Archivos adicionales (opcional)</label>
                <input type="file" id="adjuntos" name="adjuntos[]" multiple>
                <small>Puede seleccionar varios archivos. Tamaño máximo por archivo: 5 MB.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Enviar</button>
                <a href="tickets.php" class="btn btn-secondary">Cancelar</a>
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
