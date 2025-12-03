// Funcionalidades básicas para la página de inicio
document.addEventListener('DOMContentLoaded', function() {
    console.log('EcoGarden - Página cargada correctamente');
    
    // Actualizar contador del carrito
    function actualizarContadorCarrito() {
        const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
        const totalItems = carrito.reduce((sum, item) => sum + item.cantidad, 0);
        document.querySelectorAll('.cart-count').forEach(count => {
            count.textContent = totalItems;
        });
    }
    
    // Inicializar contador del carrito
    actualizarContadorCarrito();
    
    // Manejar botones "Agregar al Carrito" en productos de muestra
    document.querySelectorAll('.product-card .btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidad de carrito en desarrollo. Pronto podrás agregar productos.');
        });
    });
    
    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});