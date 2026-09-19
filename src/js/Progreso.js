/**
 * BiGlobal - Script de Interactividad y Progreso
 * Gestiona el progreso de los cursos en localStorage y las interacciones de la UI.
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. CARGAR PROGRESO GUARDADO AL INICIAR
    // ==========================================================================
    const cursosGuardados = JSON.parse(localStorage.getItem('biglobal_cursos_progreso')) || [];
    
    // Seleccionamos todas las tarjetas de curso para actualizar su estado visual
    const tarjetasCurso = document.querySelectorAll('.tarjeta-curso');
    
    tarjetasCurso.forEach(tarjeta => {
        const tituloElemento = tarjeta.querySelector('.titulo-curso');
        const botonComenzar = tarjeta.querySelector('.enlace-comenzar');
        
        if (tituloElemento && botonComenzar) {
            const nombreCurso = tituloElemento.textContent.trim();
            
            // Si el curso ya está en el localStorage, cambiamos el diseño a "Continuar"
            if (cursosGuardados.includes(nombreCurso)) {
                marcarCursoComoIniciado(tarjeta, botonComenzar);
            }
        }
    });

    // 2. EVENTOS PARA COMENZAR O REPRODUCIR CURSOS
    // ==========================================================================
    const botonesAccionCurso = document.querySelectorAll('.enlace-comenzar, .boton-reproducir');
    
    botonesAccionCurso.forEach(boton => {
        boton.addEventListener('click', (evento) => {
            evento.preventDefault();
            
            // Encontramos la tarjeta contenedora y el título del curso
            const tarjeta = boton.closest('.tarjeta-curso');
            const tituloCurso = tarjeta.querySelector('.titulo-curso').textContent.trim();
            const botonComenzar = tarjeta.querySelector('.enlace-comenzar');
            
            // Obtenemos la lista actual de cursos iniciados
            let progresoActual = JSON.parse(localStorage.getItem('biglobal_cursos_progreso')) || [];
            
            // Si no estaba iniciado, lo agregamos y guardamos en el navegador
            if (!progresoActual.includes(tituloCurso)) {
                progresoActual.push(tituloCurso);
                localStorage.setItem('biglobal_cursos_progreso', JSON.stringify(progresoActual));
                
                // Actualizamos la interfaz visualmente
                marcarCursoComoIniciado(tarjeta, botonComenzar);
                mostrarNotificacion(`¡Excelente! Has comenzado el curso de "${tituloCurso}".`);
            } else {
                mostrarNotificacion(`Continuando con tu lección de "${tituloCurso}"...`, 'info');
            }
        });
    });

    // 3. INTERACTIVIDAD DEL BOLETÍN (FOOTER)
    // ==========================================================================
    const botonBoletin = document.querySelector('.input-group .btn-principal');
    const campoBoletin = document.querySelector('.campo-boletin');
    
    if (botonBoletin && campoBoletin) {
        botonBoletin.addEventListener('click', () => {
            const correo = campoBoletin.value.trim();
            
            if (correo === '' || !correo.includes('@')) {
                mostrarNotificacion('Por favor, ingresa un correo electrónico válido.', 'error');
                campoBoletin.focus();
            } else {
                mostrarNotificacion('¡Gracias por suscribirte! Revisa tu bandeja de entrada pronto.', 'exito');
                campoBoletin.value = ''; // Limpiamos el campo
            }
        });
        
        // Permitir enviar con la tecla Enter
        campoBoletin.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                botonBoletin.click();
            }
        });
    }

    // 4. DESPLAZAMIENTO SUAVE PARA ENLACES DEL MENÚ (SMOOTH SCROLL)
    // ==========================================================================
    const enlacesMenu = document.querySelectorAll('.navbar-nav .nav-link, .contenedor-botones-hero .btn-secundario');
    
    enlacesMenu.forEach(enlace => {
        enlace.addEventListener('click', (e) => {
            const textoEnlace = enlace.textContent.toLowerCase();
            
            // Si hacen clic en "Cursos" o "Explorar cursos", bajamos suavemente al catálogo
            if (textoEnlace.includes('curso')) {
                e.preventDefault();
                const seccionCursos = document.querySelector('.seccion-cursos');
                if (seccionCursos) {
                    seccionCursos.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});

/**
 * Función auxiliar para cambiar el aspecto visual de un curso ya iniciado
 */
function marcarCursoComoIniciado(tarjeta, boton) {
    boton.innerHTML = 'Continuar <i class="fa-solid fa-circle-play ms-1"></i>';
    boton.style.color = '#2563EB'; // Cambiamos al azul primario de BiGlobal
    
    // Opcional: añadimos un borde o sombra indicando que está activo
    tarjeta.style.borderColor = '#93C5FD';
}

/**
 * Sistema flotante de notificaciones sin requerir librerías externas
 */
function mostrarNotificacion(mensaje, tipo = 'exito') {
    // Si ya existe una notificación en pantalla, la removemos
    const alertaExistente = document.querySelector('.notificacion-biglobal');
    if (alertaExistente) alertaExistente.remove();
    
    // Creamos el elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `notificacion-biglobal shadow-lg d-flex align-items-center gap-2`;
    
    // Estilos base de la notificación flotante
    Object.assign(notificacion.style, {
        position: 'fixed',
        bottom: '20px',
        right: '20px',
        padding: '12px 20px',
        borderRadius: '10px',
        color: '#FFFFFF',
        fontFamily: "'Manrope', sans-serif",
        fontWeight: '600',
        fontSize: '0.9rem',
        zIndex: '9999',
        transition: 'all 0.3s ease',
        transform: 'translateY(100px)',
        opacity: '0'
    });

    // Colores según el tipo de mensaje
    if (tipo === 'error') {
        notificacion.style.backgroundColor = '#EF4444'; // Rojo
        notificacion.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${mensaje}`;
    } else if (tipo === 'info') {
        notificacion.style.backgroundColor = '#2563EB'; // Azul BiGlobal
        notificacion.innerHTML = `<i class="fa-solid fa-circle-info"></i> ${mensaje}`;
    } else {
        notificacion.style.backgroundColor = '#10B981'; // Verde éxito
        notificacion.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${mensaje}`;
    }
    
    document.body.appendChild(notificacion);
    
    // Animación de entrada
    setTimeout(() => {
        notificacion.style.transform = 'translateY(0)';
        notificacion.style.opacity = '1';
    }, 10);
    
    // Animación de salida y destrucción después de 4 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateY(100px)';
        notificacion.style.opacity = '0';
        setTimeout(() => notificacion.remove(), 300);
    }, 4000);
}