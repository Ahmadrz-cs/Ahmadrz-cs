/**
 * Asset Member Fields
 */

const list = $("#members-list");
const trigger = $("#add-member-field");
let count = list.data('field-count');
let proto = list.data('prototype');

addRemoveMemberButtonListener();

trigger.click(function () {
    let widgetContainer = $("<div>", { "id": "member-field-" + count, "class": "member-field-container" });

    let removeButtonWidget = $("<i>", { "class": "fa fa-times remove-member-field pull-right " });

    removeButtonWidget = $('<div>').append(removeButtonWidget);

    let memberFieldWidget = proto.replace(/__name__/g, count);

    widgetContainer.append(removeButtonWidget);
    widgetContainer.append(memberFieldWidget);

    list.append(widgetContainer);

    addRemoveMemberButtonListener();

    count++;

    return false;
});

function addRemoveMemberButtonListener() {
    const removeButton = $(".remove-member-field");

    removeButton.off('click');

    removeButton.click(function (event) {
        event.preventDefault();

        if (confirm('Are you sure?')) {
            const fieldContainer = $(this).closest('.member-field-container');

            fieldContainer.fadeOut('slow', function () {
                $(this).remove();
            });
        }
    })
}