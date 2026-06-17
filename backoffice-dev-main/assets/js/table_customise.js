/**
 * JS for user control over table columns
 */

const fieldPickerForm = document.getElementById('field-customise');

fieldPickerForm.addEventListener('change', updateTableColumn);

function updateTableColumn(event) {
    // event.target contains the thing that originally sourced the event
    let targetColumn = event.target.dataset.tableColumn;
    // console.log('Toggling: col-' + targetColumn);
    toggleColumn(targetColumn);
}

function toggleColumn(columnName) {
    const columnId = document.getElementById('col-' + columnName);
    let currentVisibility = columnId.style.visibility;
    if (!currentVisibility || 'visible' === currentVisibility) {
        columnId.style.visibility = 'collapse';
    } else {
        columnId.style.visibility = 'visible';
    }
}