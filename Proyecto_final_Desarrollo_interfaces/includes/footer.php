    </main>
    
    <footer>
        <div class="container">
            <div class="footer-links">
                <a href="/index.php">Inicio</a>
                <a href="/conocimientos.php">Base de conocimientos</a>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <a href="/tickets.php">Mis tickets</a>
                <?php else: ?>
                    <a href="/login.php">Iniciar sesión</a>
                <?php endif; ?>
                <a href="/contacto.php">Contacto</a>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> Sistema de Ticketing. Todos los derechos reservados.
            </div>
        </div>
    </footer>
    
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/cookies.js"></script>
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
