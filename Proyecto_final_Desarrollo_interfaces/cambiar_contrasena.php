<?php
/**
 * Página para cambiar la contraseña del usuario
 */

// Verificar rutas duplicadas
require_once 'includes/verificar_rutas.php';

// Incluir archivos necesarios
require_once 'includes/conexion.php';
require_once 'includes/utilidades.php';
require_once 'includes/sesion.php';

// Verificar si el usuario está autenticado
if (!estaAutenticado()) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para cambiar tu contraseña.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: login.php');
    exit;
}

// Inicializar variables
$errores = [];

// Procesar formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    // Obtener datos del formulario
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    
    // Validaciones básicas
    if (empty($contrasena_actual)) {
        $errores['contrasena_actual'] = 'Debes introducir tu contraseña actual.';
    }
    
    if (empty($nueva_contrasena)) {
        $errores['nueva_contrasena'] = 'Debes introducir una nueva contraseña.';
    } else {
        // Validar fortaleza de la nueva contraseña
        $validacion = validarPassword($nueva_contrasena);
        if (!empty($validacion)) {
            $errores['nueva_contrasena'] = implode(' ', $validacion);
        }
    }
    
    if (empty($confirmar_contrasena)) {
        $errores['confirmar_contrasena'] = 'Debes confirmar la nueva contraseña.';
    } elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $errores['confirmar_contrasena'] = 'Las contraseñas no coinciden.';
    }
    
    // Verificar la contraseña actual
    if (empty($errores)) {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT contraseña FROM usuarios WHERE id_usuario = ?');
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            if (!$usuario || !password_verify($contrasena_actual, $usuario['contraseña'])) {
                $errores['contrasena_actual'] = 'La contraseña actual no es correcta.';
            }
        } catch (PDOException $e) {
            $errores['db'] = 'Error al verificar la contraseña: ' . $e->getMessage();
        }
    }
    
    // Actualizar la contraseña si no hay errores
    if (empty($errores)) {
        try {
            // Hash de la nueva contraseña
            $hash_nueva_contrasena = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            
            // Actualizar en la base de datos
            $stmt = $db->prepare('UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?');
            $stmt->execute([$hash_nueva_contrasena, $_SESSION['usuario_id']]);
            
            // Mensaje de éxito y redirigir
            $_SESSION['mensaje'] = 'Contraseña actualizada correctamente.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: perfil.php');
            exit;
        } catch (PDOException $e) {
            $errores['db'] = 'Error al actualizar la contraseña: ' . $e->getMessage();
        }
    }
    
    // Si hay errores, redirigir de vuelta al perfil con los mensajes
    if (!empty($errores)) {
        $_SESSION['mensaje'] = $errores['contrasena_actual'] ?? $errores['nueva_contrasena'] ?? $errores['confirmar_contrasena'] ?? $errores['db'] ?? 'Error al cambiar la contraseña.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: perfil.php');
        exit;
    }
} else {
    // Si se accede directamente sin enviar el formulario
    header('Location: perfil.php');
    exit;
}
?>
