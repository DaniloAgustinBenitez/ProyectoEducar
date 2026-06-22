// Scroll parallax effect: actualiza la variable CSS --scale basada en el scroll
function updateParallax() {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const scrollPercent = Math.min(1, scrollTop / docHeight);
    
    // Calcula la escala suavemente de 0.08 a 0 mientras scrolleas
    const scale = Math.max(0, 0.08 * Math.pow(1 - scrollPercent, 1.2));
    document.documentElement.style.setProperty('--scale', scale);
}

window.addEventListener('scroll', updateParallax, { passive: true });
updateParallax(); // Llama una vez al cargar la página para establecer el valor inicial de la escala.

const tarjetasOcultas = document.querySelectorAll('.oculto');

    const vigilante = new IntersectionObserver((entradas) => {
    entradas.forEach((entrada) => {
        if (entrada.isIntersecting) {
            entrada.target.classList.add('mostrar');
        }
    });
});

tarjetasOcultas.forEach((tarjeta) => {
    vigilante.observe(tarjeta);
});

/*  MOTOR DE VENTANAS MODALES*/

// 1. SELECCIONAR A LOS ACTORES
// Buscamos todos los botones azules (+) y todas las cruces de cerrar (X)
const botonesAbrir = document.querySelectorAll('.btn-mini');
const botonesCerrar = document.querySelectorAll('.btn-cerrar');
const todosLosModales = document.querySelectorAll('.modal-overlay');

// 2. LA LÓGICA DE ABRIR
// Recorremos cada botoncito azul para ponerle un escuchador de clics
botonesAbrir.forEach((boton) => {
    boton.addEventListener('click', () => {
        // Leemos el atributo data-target (ej: "modal-lab")
        const idDelModal = boton.getAttribute('data-target');
        
        // Buscamos en el documento el telón negro que tenga ese ID exacto
        const modalElegido = document.getElementById(idDelModal);
        
        // Le inyectamos la clase que lo hace visible
        modalElegido.classList.add('modal-activo');
    });
});

// 3. LA LÓGICA DE CERRAR (Con la X)
// Recorremos cada botón de la X para ponerle su "escuchador"
botonesCerrar.forEach((boton) => {
    boton.addEventListener('click', () => {
        // La herramienta .closest() busca hacia "arriba" en el HTML
        // Le decimos: "Buscame al padre de esta X que tenga la clase .modal-overlay"
        const modalPadre = boton.closest('.modal-overlay');
        
        // Le sacamos la clase para que se vuelva a ocultar
        modalPadre.classList.remove('modal-activo');
    });
});

// 4. EL TOQUE PROFESIONAL (Cerrar haciendo clic afuera)
// Recorremos todos los telones negros
todosLosModales.forEach((modal) => {
    modal.addEventListener('click', (evento) => {
        // Preguntamos: ¿El usuario le hizo clic exactamente al fondo negro (modal)?
        // (Esto evita que se cierre si hace clic adentro de la tarjeta blanca)
        if (evento.target === modal) {
            modal.classList.remove('modal-activo');
        }
    });
});

/*MOTOR DEL CARRUSEL (SLIDER MANUAL)*/

// 1. RECOLECTAR MATERIALES
// Le decimos a JavaScript: "Buscame todas las tarjetas que están en la pista y guardalas en una lista
const tarjetasCarrusel = document.querySelectorAll('.carrusel-pista .card');
// Buscamos los dos botones de las flechas
const btnAtras = document.getElementById('btn-atras');
const btnAdelante = document.getElementById('btn-adelante');

// 2. EL CONTADOR (La memoria del sistema)
// En programación, siempre empezamos a contar desde el cero. La primera tarjeta es la 0.
let tarjetaActual = 0;

// 3. LA FUNCIÓN MAESTRA (La que hace el trabajo sucio)
function mostrarTarjeta(indice) {
    // Paso A: Agarramos la lista de tarjetas y le sacamos la clase "tarjeta-activa" a TODAS.
    // Es como apagar todas las luces de un edificio.
    tarjetasCarrusel.forEach(function(tarjeta) {
        tarjeta.classList.remove('tarjeta-activa');
    });

    // Paso B: Vamos a la tarjeta específica que nos pide el contador (el índice)
    // y solo a esa le prendemos la luz agregándole la clase.
    tarjetasCarrusel[indice].classList.add('tarjeta-activa');
}

// 4. ENCENDIDO INICIAL
// Cuando el usuario entra a la página, le decimos que ejecute la función mostrando la tarjeta 0.
if (tarjetasCarrusel.length > 0) {
    mostrarTarjeta(tarjetaActual);
}

// 5. ACCIÓN: QUÉ PASA AL TOCAR LA FLECHA DERECHA (Adelante)
if (btnAdelante) btnAdelante.addEventListener('click', function() {
    // Le sumamos 1 a nuestro contador
    tarjetaActual = tarjetaActual + 1; 

    // Regla matemática: Si el contador se pasa de la cantidad de tarjetas que tenemos...
    // (Ejemplo: si estamos en la carta 4 y tocamos siguiente), lo reseteamos a 0 para que vuelva a empezar.
    if (tarjetaActual >= tarjetasCarrusel.length) {
        tarjetaActual = 0;
    }

    // Le pedimos a la función maestra que dibuje la pantalla con el nuevo número
    mostrarTarjeta(tarjetaActual); 
});

// 6. ACCIÓN: QUÉ PASA AL TOCAR LA FLECHA IZQUIERDA (Atrás)
if (btnAtras) btnAtras.addEventListener('click', function() {
    // Le restamos 1 a nuestro contador
    tarjetaActual = tarjetaActual - 1; 

    // Regla matemática: Si estábamos en la primera tarjeta (la 0) y el usuario retrocede (da -1)...
    // Lo mandamos al final de la lista. (Length - 1 significa "la última tarjeta").
    if (tarjetaActual < 0) {
        tarjetaActual = tarjetasCarrusel.length - 1;
    }

    // Le pedimos a la función maestra que dibuje la pantalla
    mostrarTarjeta(tarjetaActual); 
});