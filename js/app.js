document.addEventListener('DOMContentLoaded', function () {
    // Variables para el menú
    const menuTrigger = document.getElementById('menuTrigger');
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');

    // Control del menú hamburguesa
    menuTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        menuTrigger.classList.toggle('active');
        sidebar.classList.toggle('active');
        container.classList.toggle('shifted');
    });

    // Cerrar el menú cuando se hace clic fuera
    document.addEventListener('click', function (e) {
        if (!sidebar.contains(e.target) && !menuTrigger.contains(e.target)) {
            menuTrigger.classList.remove('active');
            sidebar.classList.remove('active');
            container.classList.remove('shifted');
        }
    });

    // Prevenir que los clics dentro del sidebar cierren el menú
    sidebar.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    const statValues = document.querySelectorAll('.stat-value');
    statValues.forEach(value => {
        const finalValue = value.textContent;
        if (finalValue.includes('$')) {
            const num = parseFloat(finalValue.replace('$', '').replace('M', ''));
            animateValue(value, 0, num, 2000, true);
        } else {
            animateValue(value, 0, parseInt(finalValue), 2000, false);
        }
    });

    // Interactividad de las tarjetas de módulos
    const moduleCards = document.querySelectorAll('.module-card');
    moduleCards.forEach(card => {
        card.addEventListener('click', function () {
            const moduleTitle = this.querySelector('.module-title').textContent;
            console.log('Navegando a:', moduleTitle);
        });

        card.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.02)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'scale(1)';
        });
    });

    // Control de scroll
    let lastScroll = 0;
    window.addEventListener('scroll', function () {
        const navbar = document.querySelector('.navbar');
        const currentScroll = window.pageYOffset;

        if (currentScroll > lastScroll) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }
        if (currentScroll === 0) {
            navbar.style.transform = 'translateY(0)';
        }

        lastScroll = currentScroll;
    });
});

// Función para animar valores numéricos
function animateValue(obj, start, end, duration, isCurrency) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const currentValue = Math.floor(progress * (end - start) + start);

        if (isCurrency) {
            obj.textContent = `$${currentValue.toFixed(1)}M`;
        } else {
            obj.textContent = currentValue.toLocaleString();
        }

        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

function formatNumber(number) {
    return new Intl.NumberFormat('es-CO').format(number);
}

window.onerror = function (msg, url, lineNo, columnNo, error) {
    console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo);
    return false;
};

window.addEventListener('offline', function (e) {
    console.log('Perdida de conexión');
});

window.addEventListener('online', function (e) {
    console.log('Conexión restaurada');
});

function formatTextToHtml(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n\s*\n/g, '</p><p>');
    text = '<p>' + text + '</p>';
    return text;
}

$(document).ready(function () {

    $('#entidades').select2({
        placeholder: 'Busca el nombre de una entidad...',
        allowClear: true,
        minimumInputLength: 3,
        language: {
            inputTooShort: function () {
                return "Por favor ingresa 3 o más caracteres";
            },
            searching: function () {
                return "Buscando...";
            },
            noResults: function () {
                return "No se encontraron resultados";
            }
        },
        ajax: {
            url: 'inc/services/entidades.php',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: Array.isArray(data) ? data : []
                };
            },
            cache: true
        }
    });

    $('#searchBtn').click(function () {
        const selectedValue = $('#entidades').val();
        if (selectedValue) {
            window.location.href = 'visor.php?nit-entidad=' + encodeURIComponent(selectedValue);
        }
    });

    /*$('#entidades').on('select2:select', function(e) {
        const selectedValue = $(this).val();
        if(selectedValue) {
            window.location.href = 'visor.php?entidad=' + encodeURIComponent(selectedValue);
        }
    });*/

    // Formato texto IA

    let rawText = $('#text-ia').text();
    let formattedHtml = formatTextToHtml(rawText);
    $('#text-ia').html(formattedHtml);

    // Tabla licitaciones

    let dataTable;
    
    const formatCurrency = (value) => {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(value);
    };

    const loadData = async () => {
        try {
            $('#loadingSpinner').show();
            
            const response = await fetch('inc/services/oportunidades.php');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Error al cargar los datos');
            }

            if (dataTable) {
                dataTable.destroy();
            }

            // Inicializar DataTable
            dataTable = $('#licitacionesTable').DataTable({
                data: result.data,
                columns: [
                    { data: 'entidad' },
                    { data: 'ciudad_entidad' },
                    { 
                        data: 'descripci_n_del_procedimiento',
                        render: (data) => `<div class="descripcion-cell" title="${data}">${data}</div>`
                    },
                    { 
                        data: 'precio_base',
                        render: (data) => formatCurrency(data)
                    },
                    { 
                        data: 'duracion',
                        render: (data) => `${data} días`
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                responsive: true,
                order: [[3, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                initComplete: function() {
                    $('[title]').tooltip();
                }
            });

        } catch (error) {
            console.error('Error:', error);
            alert('Error al cargar los datos: ' + error.message);
        } finally {
            $('#loadingSpinner').hide();
        }
    };

    loadData();

    setInterval(loadData, 5 * 60 * 1000);
});

