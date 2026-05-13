import './bootstrap';
import $ from 'jquery';
import DataTable from 'datatables.net-bs5';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';
import '@fortawesome/fontawesome-free/css/all.min.css';

window.$ = $;
window.jQuery = $;
window.DataTable = DataTable;
window.Chart = Chart;
window.Swal = Swal;

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.js-datatable').forEach((table) => {
        if (table.dataset.manualDatatable === 'true') {
            return;
        }
        // eslint-disable-next-line no-new
        new DataTable(table, {
            pageLength: 10,
            stateSave: true,
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                paginate: {
                    next: 'Siguiente',
                    previous: 'Anterior',
                },
            },
        });
    });
});
