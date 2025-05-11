<?php
/**
 * Página para crear nuevo artículo de conocimiento
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario tiene permisos (administrador o soporte)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] !== 'administrador' && $_SESSION['rol'] !== 'soporte')) {
    $_SESSION['mensaje'] = 'No tienes permisos para acceder a esta página.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: conocimientos.php');
    exit;
}

// Inicializar variables
$errores = [];
$categoria = '';
$titulo = '';
$resumen = '';
$contenido = '';
$etiquetas = '';
$image_uploaded = false;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar datos
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $nueva_categoria = sanitizar($_POST['nueva_categoria'] ?? '');
    $titulo = sanitizar($_POST['titulo'] ?? '');
    $resumen = sanitizar($_POST['resumen'] ?? '');
    $contenido = sanitizar($_POST['contenido'] ?? '');
    $etiquetas = sanitizar($_POST['etiquetas'] ?? '');
    
    // Usar nueva categoría si se proporcionó
    if (!empty($nueva_categoria)) {
        $categoria = $nueva_categoria;
    }
    
    // Validaciones
    if (empty($categoria)) {
        $errores['categoria'] = 'La categoría es obligatoria.';
    }
    
    if (empty($titulo)) {
        $errores['titulo'] = 'El título es obligatorio.';
    }
    
    if (empty($resumen)) {
        $errores['resumen'] = 'El resumen es obligatorio.';
    }
    
    if (empty($contenido)) {
        $errores['contenido'] = 'El contenido es obligatorio.';
    }
    
    // Manejar imagen si se subió
    $imagen_nombre = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/conocimientos/';
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_tmp = $_FILES['imagen']['tmp_name'];
        $file_name = $_FILES['imagen']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validar extensión
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_ext)) {
            // Generar nombre único
            $imagen_nombre = uniqid('articulo_') . '.' . $file_ext;
            $upload_path = $upload_dir . $imagen_nombre;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_uploaded = true;
            } else {
                $errores['imagen'] = 'Error al subir la imagen.';
            }
        } else {
            $errores['imagen'] = 'Formato de imagen no válido. Se permiten: jpg, jpeg, png, gif.';
        }
    }
    
    // Si no hay errores, guardar el artículo
    if (empty($errores)) {
        try {
            $db = getDB();
            
            $stmt = $db->prepare('
                INSERT INTO conocimientos 
                (categoria, titulo, resumen, contenido, imagen, etiquetas, id_autor) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $categoria, 
                $titulo, 
                $resumen, 
                $contenido, 
                $imagen_nombre, 
                $etiquetas, 
                $_SESSION['usuario_id']
            ]);
            
            // Obtener el ID del artículo recién creado
            $id_articulo = $db->lastInsertId();
            
            // Mensaje de éxito
            $_SESSION['mensaje'] = 'Artículo creado correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            
            // Redirigir a la vista del artículo
            header('Location: ver_articulo.php?id=' . $id_articulo);
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al guardar el artículo: ' . $e->getMessage();
        }
    }
}

// Obtener categorías existentes
try {
    $db = getDB();
    $stmt = $db->query('SELECT DISTINCT categoria FROM conocimientos ORDER BY categoria');
    $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $errores['db'] = 'Error al cargar las categorías: ' . $e->getMessage();
    $categorias = [];
}

// Establecer título de página
$page_title = 'Nuevo artículo de conocimiento';

// Incluir header
include 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 class="form-title">Crear nuevo artículo de conocimiento</h2>
        
        <?php if (isset($errores['db'])): ?>
            <div class="alert alert-error"><?php echo $errores['db']; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="nuevo_articulo.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="categoria">Categoría *</label>
                <select 
                    id="categoria" 
                    name="categoria" 
                    class="<?php echo isset($errores['categoria']) ? 'error' : ''; ?>"
                    required
                >
                    <option value="">Seleccione...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat === $categoria ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errores['categoria'])): ?>
                    <div class="error-message"><?php echo $errores['categoria']; ?></div>
                <?php endif; ?>
                <div class="mt-2">
                    <label for="nueva_categoria">O crea una nueva categoría:</label>
                    <input type="text" id="nueva_categoria" name="nueva_categoria">
                </div>
            </div>
            
            <div class="form-group">
                <label for="titulo">Título *</label>
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
            
            <div class="form-group">
                <label for="resumen">Resumen *</label>
                <p class="help-text">Escribe un breve resumen del artículo (200-300 caracteres).</p>
                <textarea 
                    id="resumen" 
                    name="resumen" 
                    class="<?php echo isset($errores['resumen']) ? 'error' : ''; ?>"
                    rows="3"
                    required
                ><?php echo htmlspecialchars($resumen); ?></textarea>
                <?php if (isset($errores['resumen'])): ?>
                    <div class="error-message"><?php echo $errores['resumen']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="contenido">Contenido completo *</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="formatText('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" onclick="formatText('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" onclick="formatText('underline')"><i class="fas fa-underline"></i></button>
                    <button type="button" onclick="formatText('list')"><i class="fas fa-list-ul"></i></button>
                    <button type="button" onclick="formatText('ol')"><i class="fas fa-list-ol"></i></button>
                    <button type="button" onclick="formatText('link')"><i class="fas fa-link"></i></button>
                    <button type="button" onclick="formatText('code')"><i class="fas fa-code"></i></button>
                </div>
                <textarea 
                    id="contenido" 
                    name="contenido" 
                    class="<?php echo isset($errores['contenido']) ? 'error' : ''; ?>"
                    rows="10"
                    required
                ><?php echo htmlspecialchars($contenido); ?></textarea>
                <?php if (isset($errores['contenido'])): ?>
                    <div class="error-message"><?php echo $errores['contenido']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="etiquetas">Etiquetas</label>
                <p class="help-text">Separa las etiquetas por comas (ej: php, mysql, seguridad)</p>
                <input 
                    type="text" 
                    id="etiquetas" 
                    name="etiquetas" 
                    value="<?php echo htmlspecialchars($etiquetas); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="imagen">Imagen (opcional)</label>
                <input 
                    type="file" 
                    id="imagen" 
                    name="imagen" 
                    accept="image/jpeg,image/png,image/gif"
                >
                <?php if (isset($errores['imagen'])): ?>
                    <div class="error-message"><?php echo $errores['imagen']; ?></div>
                <?php endif; ?>
                <p class="help-text">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.</p>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Guardar artículo</button>
                <a href="conocimientos.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</section>

<script>
// Función para manejar la selección de categoría nueva o existente
document.addEventListener('DOMContentLoaded', function() {
    const categoriaSelect = document.getElementById('categoria');
    const nuevaCategoriaInput = document.getElementById('nueva_categoria');
    
    nuevaCategoriaInput.addEventListener('input', function() {
        if (this.value.trim() !== '') {
            categoriaSelect.value = '';
            categoriaSelect.disabled = true;
        } else {
            categoriaSelect.disabled = false;
        }
    });
    
    categoriaSelect.addEventListener('change', function() {
        if (this.value !== '') {
            nuevaCategoriaInput.value = '';
            nuevaCategoriaInput.disabled = true;
        } else {
            nuevaCategoriaInput.disabled = false;
        }
    });
    
    // Funciones simples para el editor de texto
    window.formatText = function(format) {
        const contenido = document.getElementById('contenido');
        let start = contenido.selectionStart;
        let end = contenido.selectionEnd;
        let selectedText = contenido.value.substring(start, end);
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
            case 'link':
                const url = prompt('Introduce la URL:', 'https://');
                if (url) {
                    replacement = `[${selectedText}](${url})`;
                } else {
                    return;
                }
                break;
            case 'code':
                replacement = `\`\`\`\n${selectedText}\n\`\`\``;
                break;
        }
        
        contenido.value = contenido.value.substring(0, start) + replacement + contenido.value.substring(end);
        contenido.focus();
        contenido.setSelectionRange(start, start + replacement.length);
    };
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>
