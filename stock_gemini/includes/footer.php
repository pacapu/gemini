<?php
// includes/footer.php
// Esta etiqueta </main> cierra la etiqueta <main> que se abre en el header.php
// Es crucial para que el layout de Bootstrap con 'flex-direction: column' funcione correctamente.
?>
    </main> 

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span>&copy; <?php echo date('Y'); ?> StockApp - Gemini</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="<?php echo $base_path; ?>js/script.js"></script>
</body>
</html>