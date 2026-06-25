// --- Intersection Observer: anima elementos con clase "oculto" al hacer scroll ---
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('mostrar');
        }
    });
}, { threshold: 0.15 });

document.querySelectorAll('.oculto').forEach(el => observer.observe(el));

// --- Carrusel ---
(function () {
    const pista = document.querySelector('.carrusel-pista');
    if (!pista) return;

    const cards = pista.querySelectorAll('.card');
    if (cards.length === 0) return;

    let idx = 0;

    function mostrarCard(i) {
        cards.forEach(c => c.classList.remove('tarjeta-activa'));
        cards[i].classList.add('tarjeta-activa');
    }

    mostrarCard(idx);

    const btnAtras = document.getElementById('btn-atras');
    const btnAdelante = document.getElementById('btn-adelante');

    if (btnAtras) {
        btnAtras.addEventListener('click', () => {
            idx = (idx - 1 + cards.length) % cards.length;
            mostrarCard(idx);
        });
    }

    if (btnAdelante) {
        btnAdelante.addEventListener('click', () => {
            idx = (idx + 1) % cards.length;
            mostrarCard(idx);
        });
    }
})();

// --- Modales ---
document.querySelectorAll('.btn-mini').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = btn.getAttribute('data-target');
        if (!targetId) return;
        const modal = document.getElementById(targetId);
        if (modal) modal.classList.add('modal-activo');
    });
});

document.querySelectorAll('.btn-cerrar').forEach(btn => {
    btn.addEventListener('click', () => {
        const overlay = btn.closest('.modal-overlay');
        if (overlay) overlay.classList.remove('modal-activo');
    });
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('modal-activo');
    });
});
