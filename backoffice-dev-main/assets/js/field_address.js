/**
 * Address Fields
 */

const list = $("#address-list");
const trigger = $("#add-address-field");
let count = list.data('field-count');
let proto = list.data('prototype');

addRemoveAddressButtonListener();

trigger.click(function () {
    let widgetContainer = $("<div>", { "id": "address-field-" + count, "class": "address-field-container" });

    let removeButtonWidget = $("<i>", { "class": "fa fa-times remove-address-field pull-right " });

    removeButtonWidget = $('<div>').append(removeButtonWidget);

    let customFieldWidget = proto.replace(/__name__/g, count);

    widgetContainer.append(removeButtonWidget);
    widgetContainer.append(customFieldWidget);

    list.append(widgetContainer);

    addRemoveAddressButtonListener();

    count++;

    return false;
});

function addRemoveAddressButtonListener() {
    const removeButton = $(".remove-address-field");

    removeButton.off('click');

    removeButton.click(function (event) {
        event.preventDefault();

        if (confirm('Are you sure?')) {
            const fieldContainer = $(this).closest('.address-field-container');

            fieldContainer.fadeOut('slow', function () {
                $(this).remove();
            });
        }
    })
}