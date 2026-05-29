# 🎓 Portal Educativo - Sistema de Gestión

Este proyecto es una plataforma escolar para la gestión de inscripciones, calificaciones, asistencias, comedores, talleres y legajos institucionales, diseñada para alumnos, docentes, preceptores y administradores.

El código se encuentra organizado en carpetas estructuradas para mantener la raíz limpia y facilitar el mantenimiento del sistema.

---

## 📁 Estructura del Proyecto

La nueva organización de carpetas del portal es la siguiente:

```text
portal-educativo/
│
├── 📂 admin/                # Panel CRUD de gestión de contenidos educativos
│   ├── 📂 core/             # Datos y funciones internas del CRUD (datos.json, funciones.php)
│   ├── crear.php            # Formulario de alta de registros
│   ├── editar.php           # Formulario de modificación de registros
│   ├── eliminar.php         # Lógica de borrado de registros
│   └── index.php            # Vista principal del gestor administrativo
│
├── 📂 data/                 # Bases de datos del sistema en formato JSON
│   ├── alumnos_cursos.json  # Alumnos registrados en cada curso y división
│   ├── asignaciones.json    # Cátedras y cursos asignados al personal docente/preceptores
│   ├── asistencias.json     # Historial de inasistencias por fecha y curso
│   ├── calificaciones.json  # Notas académicas por alumno y materia
│   ├── capacitaciones.json  # Registro de inscripciones de docentes en capacitaciones
│   ├── documentos.json      # Legajo digital de documentación cargada por alumnos
│   ├── materias.json        # Catálogo de materias del ciclo lectivo
│   ├── menu.json            # Menú publicado del comedor
│   ├── reservas.json        # Reservas de laboratorios y espacios deportivos
│   ├── talleres.json        # Inscripción de alumnos a talleres extraescolares
│   └── usuarios.json        # Credenciales e información de perfiles de usuario
│
├── 📂 img/                  # Recursos gráficos estáticos y logos del portal
│
├── 📂 procesos/             # Controladores y scripts que procesan los formularios (backend)
│   ├── eliminar_nota.php    # Elimina calificaciones de alumnos
│   ├── guardar_nota.php     # Guarda calificaciones de alumnos
│   ├── logout.php           # Destruye la sesión de usuario activa
│   ├── procesar_asignacion.php # Asigna o quita cátedras a docentes
│   ├── procesar_asistencia.php # Registra la asistencia diaria tomada por preceptores
│   ├── procesar_capacitacion.php # Inscribe o da de baja capacitaciones a profesores
│   ├── procesar_documento.php # Sube y gestiona archivos de documentación médica/DNI
│   ├── procesar_menu.php      # Actualiza el menú del comedor diario
│   ├── procesar_reserva.php   # Registra o elimina reservas de espacios escolares
│   ├── procesar_taller.php    # Inscribe o da de baja alumnos en actividades
│   └── procesar_usuario.php   # Registra nuevos usuarios y asigna cátedras/cursos
│
├── 📂 uploads/              # Archivos y certificados subidos físicamente por los alumnos
│
├── dashboard.php            # Panel principal interactivo del usuario (según su rol)
├── index.html               # Página de bienvenida / Landing Page institucional
├── inscripcion.html         # Formulario interactivo de solicitud de inscripción
└── login.php                # Formulario de inicio de sesión y autenticación
```

---

## 🔑 Roles y Credenciales del Sistema

Los usuarios del portal se autentican en [login.php](login.php) y son redirigidos a [dashboard.php](dashboard.php). Existen cuatro roles en la plataforma:

1. **Alumno / Tutor (`alumno`)**: Permite la carga de documentación escolar, consulta de calificaciones, control de inasistencias y la inscripción a talleres y deportes extraescolares.
2. **Personal Docente (`profesor`)**: Habilita la carga y eliminación de calificaciones de los alumnos a su cargo, reserva de espacios (laboratorio, canchas, pileta) e inscripción en capacitaciones.
3. **Preceptor / Auxiliar (`preceptor`)**: Permite el registro de la asistencia diaria por curso y división, y la revisión de legajos y certificados médicos entregados.
4. **Administrador (`admin`)**: Tiene acceso completo a la creación de nuevos usuarios y perfiles en el sistema, la asignación de materias y cátedras a profesores/preceptores, y la gestión del menú del comedor escolar.

---

## 🛠️ Tecnologías Utilizadas

- **Core**: HTML5 y Javascript para interactividad en el cliente.
- **Estilos**: CSS Vanilla con layouts modernos y responsivos (Grid y Flexbox).
- **Backend**: PHP 7+ para procesamiento de sesiones, subidas y lógica.
- **Almacenamiento**: Archivos planos codificados en JSON (`data/*.json`).
