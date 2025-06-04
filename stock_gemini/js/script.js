document.addEventListener('DOMContentLoaded', function() {
    // Para el menú desplegable (si usas hover puro CSS, esto podría no ser necesario)
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const dropbtn = dropdown.querySelector('.dropbtn');
        const dropdownContent = dropdown.querySelector('.dropdown-content');

        if (dropbtn && dropdownContent) {
            // Mostrar al pasar el mouse
            dropdown.addEventListener('mouseenter', () => {
                dropdownContent.style.display = 'block';
            });

            // Ocultar al quitar el mouse
            dropdown.addEventListener('mouseleave', () => {
                dropdownContent.style.display = 'none';
            });
        }
    });

    // Opcional: Cerrar dropdowns si se hace clic fuera
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.dropbtn')) {
            dropdowns.forEach(dropdown => {
                const dropdownContent = dropdown.querySelector('.dropdown-content');
                if (dropdownContent && dropdownContent.style.display === 'block') {
                    dropdownContent.style.display = 'none';
                }
            });
        }
    });
});