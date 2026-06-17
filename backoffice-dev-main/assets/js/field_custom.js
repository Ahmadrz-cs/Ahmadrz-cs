/**
 * Custom/Additional Fields
 */

const list = $("#custom-fields-list");
const trigger = $("#add-custom-field");
let count = list.data('field-count');
let proto = list.data('prototype');

addRemoveCustomFieldButtonListener();

trigger.click(function () {
    let widgetContainer = $("<div>", { "id": "custom-field-" + count, "class": "custom-field-container" });

    let removeButtonWidget = $("<i>", { "class": "fa fa-times remove-custom-field pull-right " });

    removeButtonWidget = $('<div>').append(removeButtonWidget);

    let customFieldWidget = proto.replace(/__name__/g, count);

    widgetContainer.append(removeButtonWidget);
    widgetContainer.append(customFieldWidget);

    list.append(widgetContainer);

    addRemoveCustomFieldButtonListener();

    count++;

    return false;
});

function addRemoveCustomFieldButtonListener() {
    const removeButton = $(".remove-custom-field");

    removeButton.off('click');

    removeButton.click(function (event) {
        event.preventDefault();

        if (confirm('Are you sure?')) {
            const fieldContainer = $(this).closest('.custom-field-container');

            fieldContainer.fadeOut('slow', function () {
                $(this).remove();
            });
        }
    })
}