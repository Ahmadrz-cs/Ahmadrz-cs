// css stuff from modules
import 'bootstrap/dist/css/bootstrap.min.css';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'select2/dist/css/select2.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

// custom css
import '../css/app.css';

// js stuff from modules
import 'bootstrap';
import 'datatables.net';
import 'datatables.net-bs5';
import 'select2';

// Data table initialisation
if ($('.datatable').length) {
    $('table.datatable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": false,
        "info": true,
        "autoWidth": true
    });
}

// Enable select2 on any select with class searchable
$('select.searchable').select2();