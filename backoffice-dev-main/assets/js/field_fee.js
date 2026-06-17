/**
 * fee/Additional Fields
 */

const list = $("#fee-fields-list");
const trigger = $("#add-fee-field");
let count = list.data('field-count');
let proto = list.data('prototype');

addRemovefeeFieldButtonListener();

trigger.click(function () {
    let widgetContainer = $("<div>", { "id": "fee-field-" + count, "class": "fee-field-container" });

    let removeButtonWidget = $("<i>", { "class": "fa fa-times remove-fee-field pull-right " });

    removeButtonWidget = $('<div>').append(removeButtonWidget);

    let feeFieldWidget = proto.replace(/__name__/g, count);

    widgetContainer.append(removeButtonWidget);
    widgetContainer.append(feeFieldWidget);

    list.append(widgetContainer);

    addRemovefeeFieldButtonListener();

    count++;

    return false;
});

function addRemovefeeFieldButtonListener() {
    const removeButton = $(".remove-fee-field");

    removeButton.off('click');

    removeButton.click(function (event) {
        event.preventDefault();

        if (confirm('Are you sure?')) {
            const fieldContainer = $(this).closest('.fee-field-container');

            fieldContainer.fadeOut('slow', function () {
                $(this).remove();
            });
        }
    })
}