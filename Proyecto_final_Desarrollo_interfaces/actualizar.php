<?php
/**
 * Página para mostrar mensaje de actualización de base de datos
 */

// Establecer título de página
$page_title = 'Actualización de base de datos necesaria';

// Incluir header
include 'includes/header.php';
?>

<div class="container">
    <div class="update-container">
        <div class="update-message">
            <h2><i class="fas fa-database"></i> Actualización de base de datos necesaria</h2>
            
            <div class="alert alert-warning">
                <p>Se detectó que la estructura de la base de datos necesita ser actualizada para usar todas las nuevas funciones.</p>
                
                <?php if (isset($_SESSION['db_problemas']) && !empty($_SESSION['db_problemas'])): ?>
                    <div class="problems-detected">
                        <h4>Problemas detectados:</h4>
                        <ul>
                            <?php foreach ($_SESSION['db_problemas'] as $problema): ?>
                                <li><?php echo $problema; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php unset($_SESSION['db_problemas']); ?>
                <?php endif; ?>
            </div>
            
            <p>Es necesario realizar una actualización para añadir las siguientes funcionalidades:</p>
            
            <ul class="update-features">
                <li><i class="fas fa-star"></i> Sistema de valoración de artículos</li>
                <li><i class="fas fa-tags"></i> Soporte para etiquetas en artículos</li>
                <li><i class="fas fa-link"></i> Sistema de artículos relacionados</li>
                <li><i class="fas fa-eye"></i> Contador de visitas para artículos</li>
                <li><i class="fas fa-file-alt"></i> Campos de resumen e imagen para artículos</li>
            </ul>
            
            <div class="update-actions">
                <a href="db/actualizar_db.php" class="btn">
                    <i class="fas fa-wrench"></i> Actualizar ahora
                </a>
                <p class="help-text">¿Problemas con la actualización? 
                    <a href="db/reiniciar_db.php" class="advanced-link">
                        <i class="fas fa-undo"></i> Reiniciar estructura
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.update-container {
    max-width: 800px;
    margin: 50px auto;
    background: #fff;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
}

.update-message h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    margin-bottom: 20px;
}

.update-message h2 i {
    color: var(--primary-color);
}

.update-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
}

.update-features li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}

.update-features li i {
    color: var(--primary-color);
}

.update-actions {
    margin-top: 30px;
    text-align: center;
}

.update-actions .btn {
    padding: 12px 25px;
    font-size: 1.1rem;
}

.help-text {
    margin-top: 15px;
    font-size: 0.9rem;
    color: #666;
}

.advanced-link {
    color: #ff3333;
    text-decoration: none;
    font-size: 0.9rem;
}

.advanced-link:hover {
    text-decoration: underline;
}

.problems-detected {
    margin-top: 15px;
    background-color: #fff9e6;
    padding: 10px 15px;
    border-radius: 4px;
    border-left: 3px solid #ffcc00;
}

.problems-detected h4 {
    color: #b37400;
    margin-top: 0;
    margin-bottom: 10px;
}

.problems-detected ul {
    margin: 0;
    padding-left: 20px;
}

.problems-detected li {
    margin-bottom: 5px;
    color: #664200;
}
</style>

<?php 
// Incluir footer
include 'includes/footer.php';
?>
