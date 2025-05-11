<?php
/**
 * Script para eliminar un ticket
 */

// Incluir archivo de conexión
require_once 'includes/conexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['mensaje'] = 'Debes iniciar sesión para eliminar tickets.';
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
    
    // Verificar si el usuario tiene permiso para eliminar este ticket
    // (debe ser el propietario o un administrador)
    if ($ticket['id_usuario'] != $_SESSION['usuario_id'] && $_SESSION['rol'] !== 'administrador') {
        $_SESSION['mensaje'] = 'No tienes permiso para eliminar este ticket.';
        $_SESSION['mensaje_tipo'] = 'error';
        header('Location: tickets.php');
        exit;
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Eliminar acciones asociadas al ticket
    $stmt = $db->prepare('DELETE FROM acciones WHERE id_ticket = ?');
    $stmt->execute([$id_ticket]);
    
    // Eliminar archivos adjuntos de la base de datos
    $stmt = $db->prepare('DELETE FROM archivos WHERE id_ticket = ?');
    $stmt->execute([$id_ticket]);
    
    // Eliminar el ticket
    $stmt = $db->prepare('DELETE FROM tickets WHERE id_ticket = ?');
    $stmt->execute([$id_ticket]);
    
    // Confirmar transacción
    $db->commit();
    
    // Eliminar archivos físicos del disco (si existen)
    $dir_adjuntos = 'uploads/tickets/' . $id_ticket;
    if (file_exists($dir_adjuntos)) {
        // Función recursiva para eliminar directorio y su contenido
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
    
    $_SESSION['mensaje'] = 'Ticket eliminado correctamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['mensaje'] = 'Error al eliminar el ticket: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'error';
}

// Redirigir a la lista de tickets
header('Location: tickets.php');
exit;
