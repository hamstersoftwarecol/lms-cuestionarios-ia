import DataTable from 'datatables.net-dt';
import 'datatables.net-buttons-dt';
import 'datatables.net-responsive-dt';
import JSZip from 'jszip';

DataTable.Buttons.jszip(JSZip);

// pdfmake (~2 MB con las fuentes) se descarga en segundo plano para no retrasar la tabla.
const pdfReady = Promise.all([import('pdfmake/build/pdfmake'), import('pdfmake/build/vfs_fonts')]).then(([pdfMake, fonts]) => {
    const lib = pdfMake.default ?? pdfMake;
    lib.addVirtualFileSystem(fonts.default ?? fonts);
    DataTable.Buttons.pdfMake(lib);
});

const language = {
    decimal: ',',
    thousands: '.',
    emptyTable: 'No hay datos disponibles',
    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
    infoEmpty: 'Mostrando 0 registros',
    infoFiltered: '(filtrado de _MAX_ registros)',
    lengthMenu: 'Mostrar _MENU_',
    loadingRecords: 'Cargando…',
    processing: 'Procesando…',
    search: 'Buscar:',
    zeroRecords: 'No se encontraron resultados',
    paginate: { first: '«', last: '»', next: '›', previous: '‹' },
    buttons: { copy: 'Copiar', csv: 'CSV', excel: 'Excel', pdf: 'PDF', print: 'Imprimir' },
};

/**
 * Inicializa todas las tablas con el atributo data-datatable.
 * Las columnas con la clase "no-export" se excluyen de CSV, Excel, PDF e impresión.
 */
export function initDataTables() {
    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        const title = table.dataset.exportTitle || document.title;
        const exportOptions = { columns: ':not(.no-export)' };

        new DataTable(table, {
            language,
            responsive: true,
            pageLength: parseInt(table.dataset.pageLength || '10', 10),
            order: table.dataset.order ? JSON.parse(table.dataset.order) : [],
            columnDefs: [{ targets: 'no-sort', orderable: false }],
            layout: {
                topStart: {
                    buttons: [
                        { extend: 'csvHtml5', text: 'CSV', title, exportOptions, bom: true },
                        { extend: 'excelHtml5', text: 'Excel', title, exportOptions },
                        {
                            extend: 'pdfHtml5',
                            text: 'PDF',
                            title,
                            exportOptions,
                            orientation: 'landscape',
                            pageSize: 'A4',
                            // Disponible aunque pdfmake siga cargando: la acción espera a que termine.
                            available: () => window.FileReader !== undefined,
                            action(event, dt, button, config, cb) {
                                pdfReady.then(() => DataTable.ext.buttons.pdfHtml5.action.call(this, event, dt, button, config, cb));
                            },
                        },
                        { extend: 'print', text: 'Imprimir', title, exportOptions },
                    ],
                },
                topEnd: 'search',
                bottomStart: ['pageLength', 'info'],
                bottomEnd: 'paging',
            },
        });
    });
}
