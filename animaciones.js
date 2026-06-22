document.addEventListener('DOMContentLoaded', function() {
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            var elementos = document.querySelectorAll('.oculto');
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('mostrar');
                    }
                });
            });
            elementos.forEach(function(el) {
                observer.observe(el);
            });
        });
    });
});
