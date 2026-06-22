function enviarFormulario(event) {
    event.preventDefault();
    document.getElementById('modal-exito').classList.add('activo');
}

function cerrarModal() {
    document.getElementById('modal-exito').classList.remove('activo');
    document.getElementById('form-inscripcion').reset();
}

document.getElementById('modal-exito').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});