
function showPassword() {
    var Val = $("#signUpUser_password_first").attr("type");

    if (Val == "password") {
        $("#signUpUser_password_first").attr("type", "text2");
        $("#signUpUser_password_second").attr("type", "text2");
        $("i").removeClass("fa-eye").addClass("fa-eye-slash");
    }
    else if (Val == "text2") {
        $("#signUpUser_password_first").attr("type", "password");
        $("#signUpUser_password_second").attr("type", "password");
        $("i").removeClass("fa-eye-slash").addClass("fa-eye");
    }
}

function showcontent() {
    if ($('#userPreference_cxb_restricted_investor').is(":checked")) {
        $('#content1').removeClass('hide');
        $('#content2').addClass('hide');
        $('#content3').addClass('hide');
    }
    if ($('#userPreference_cxb_worth_investor').is(":checked")) {
        $('#content1').addClass('hide');
        $('#content2').removeClass('hide');
        $('#content3').addClass('hide');
    }
    if ($('#userPreference_cxb_sophisticated_investor').is(":checked")) {
        $('#content1').addClass('hide');
        $('#content2').addClass('hide');
        $('#content3').removeClass('hide');
    }
}

function showAddFunds() {

        $("#add-funds-form").removeClass("hide")

        $("#completion-message").addClass("hide");
}

function extractNumber(obj, decimalPlaces, allowNegative) {
    var temp = obj.value;

    // avoid changing things if already formatted correctly
    var reg0Str = '[0-9]*';
    if (decimalPlaces > 0) {
        reg0Str += '\\.?[0-9]{0,' + decimalPlaces + '}';
    } else if (decimalPlaces < 0) {
        reg0Str += '\\.?[0-9]*';
    }
    reg0Str = allowNegative ? '^-?' + reg0Str : '^' + reg0Str;
    reg0Str = reg0Str + '$';
    var reg0 = new RegExp(reg0Str);
    if (reg0.test(temp)) return true;

    // first replace all non numbers
    var reg1Str = '[^0-9' + (decimalPlaces != 0 ? '.' : '') + (allowNegative ? '-' : '') + ']';
    var reg1 = new RegExp(reg1Str, 'g');
    temp = temp.replace(reg1, '');

    if (allowNegative) {
        // replace extra negative
        var hasNegative = temp.length > 0 && temp.charAt(0) == '-';
        var reg2 = /-/g;
        temp = temp.replace(reg2, '');
        if (hasNegative) temp = '-' + temp;
    }

    if (decimalPlaces != 0) {
        var reg3 = /\./g;
        var reg3Array = reg3.exec(temp);
        if (reg3Array != null) {
            // keep only first occurrence of .
            //  and the number of places specified by decimalPlaces or the entire string if decimalPlaces < 0
            var reg3Right = temp.substring(reg3Array.index + reg3Array[0].length);
            reg3Right = reg3Right.replace(reg3, '');
            reg3Right = decimalPlaces > 0 ? reg3Right.substring(0, decimalPlaces) : reg3Right;
            temp = temp.substring(0, reg3Array.index) + '.' + reg3Right;
        }
    }

    obj.value = temp;
}

function blockNonNumbers(obj, e, allowDecimal, allowNegative) {
    var key;
    var isCtrl = false;
    var keychar;
    var reg;

    if (window.event) {
        key = e.keyCode;
        isCtrl = window.event.ctrlKey
    }
    else if (e.which) {
        key = e.which;
        isCtrl = e.ctrlKey;
    }

    if (isNaN(key)) return true;

    keychar = String.fromCharCode(key);

    // check for backspace or delete, or if Ctrl was pressed
    if (key == 8 || isCtrl) {
        return true;
    }

    reg = /\d/;
    var isFirstN = allowNegative ? keychar == '-' && obj.value.indexOf('-') == -1 : false;
    var isFirstD = allowDecimal ? keychar == '.' && obj.value.indexOf('.') == -1 : false;

    return isFirstN || isFirstD || reg.test(keychar);
}
function paymenttype() {

    if ($('#optcard').is(":checked")) {
        $('#paycard').removeClass('hide');
        $('#paycard_notice').removeClass('hide');
        $('#paybankwire').addClass('hide');
        $('#paywire_notice').addClass('hide');
        $('#amount').attr('readonly', false);
    }
    if ($('#optbank').is(":checked")) {
        $('#paybankwire').removeClass('hide');
        $('#paycard').addClass('hide');
        $('#paycard_notice').addClass('hide');
        if ($('#add-funds-bankwire').hasClass('hide')) {
            $('#paywire_notice').removeClass('hide');
        }
    }
}


function showinvestorcontent() {

    $("#userPreference_investor_type_0").change(function() {
        console.log("Hello world1!");
        $('#content1').removeClass('hide');
        $('#content2').addClass('hide');
        $('#content3').addClass('hide');
    });

    $("#userPreference_investor_type_1").change(function() {
        console.log("Hello world2!");
        $('#content1').addClass('hide');
        $('#content2').removeClass('hide');
        $('#content3').addClass('hide');
    });

    $("#userPreference_investor_type_2").change(function() {
        console.log("Hello world3!");
        $('#content1').addClass('hide');
        $('#content2').addClass('hide');
        $('#content3').removeClass('hide');
    });
}

function activateButtonOnClick(locator) {

    if ($(locator).is(":checked")) {

        $('#btn_continue').addClass('button_active');
    } else {
        $('#btn_continue').removeClass('button_active');
    }
}



// for directors

// this variable is the list in the dom, it's initiliazed when the document is ready
var $collectionHolderDirectors;
var $collectionHolderBeneficialOwner;
// the link which we click on to add new items
var $addNewItem = $('<div class="btn_1" style="float:left; padding-top:0; margin:0;"><a href="#" style="text-align:left;">ADD DIRECTORS</a></div>');
var $addBeneficialOwner = $('<div class="btn_1" style="float:left; padding-top:0; margin:0;"><a href="#" style="text-align:left;">ADD BENEFICIAL OWNERS</a></div>');
// when the page is loaded and ready
$(document).ready(function () {
    // get the Directors, initilize the var by getting the list;
    $collectionHolderDirectors = $('#company_director_names');
    // append the add new item link to the collectionHolder
    $collectionHolderDirectors.append($addNewItem);
    // add an index property to the collectionHolder which helps track the count of forms we have in the list
    $collectionHolderDirectors.data('index', $collectionHolderDirectors.find('.directors-names-panel').length);

    // finds all the panels in the list and foreach one of them we add a remove button to it
    // add remove button to existing items
    $collectionHolderDirectors.find('.directors-names-panel').each(function () {
        // $(this) means the current panel that we are at
        // which means we pass the panel to the addRemoveButton function
        // inside the function we create a footer and remove link and append them to the panel
        // more informations in the function inside
        addRemoveButtonDirectors($(this));
    });
    // handle the click event for addNewItem
    $addNewItem.click(function (e) {
        // preventDefault() is your  homework if you don't know what it is
        // also look up preventPropagation both are usefull
        e.preventDefault();
        // create a new form and append it to the collectionHolder
        // and by form we mean a new panel which contains the form
        addNewForm();
    });

    $collectionHolderBeneficialOwner = $('#company_beneficial_owners_names');
    $collectionHolderBeneficialOwner.append($addBeneficialOwner);
    $collectionHolderBeneficialOwner.data('index', $collectionHolderDirectors.find('.beneficial-owners-names-panel').length);

    $collectionHolderBeneficialOwner.find('.beneficial-owners-names-panel').each(function () {
        addRemoveButtonBeneficialOwner($(this));
    });

    $addBeneficialOwner.click(function (e) {

        e.preventDefault();
        addNewBeneficialOwner();
    })
});

/**
 * creates a new form and appends it to the collectionHolder
 */
function addNewForm() {
    // getting the prototype
    // the prototype is the form itself, plain html
    var prototype = $collectionHolderDirectors.data('prototype');
    // get the index
    // this is the index we set when the document was ready, look above for more info
    var index = $collectionHolderDirectors.data('index');
    // create the form
    var newForm = prototype;
    // replace the __name__ string in the html using a regular expression with the index value
    newForm = newForm.replace(/__name__/g, index);
    // incrementing the index data and setting it again to the collectionHolder
    $collectionHolderDirectors.data('index', index+1);
    // create the panel
    // this is the panel that will be appending to the collectionHolder
    var $panel = $('<div class="directors-names-panel"></div>');
    // create the panel-body and append the form to it
    var $panelBody = $('<div class="panel-body"></div>').append(newForm);
    // append the body to the panel
    $panel.append($panelBody);
    // append the removebutton to the new panel
    addRemoveButtonDirectors($panel);
    // append the panel to the addNewItem
    // we are doing it this way to that the link is always at the bottom of the collectionHolder
    $addNewItem.before($panel);
}

/**
 * creates a new form and appends it to the collectionHolder
 */
function addNewBeneficialOwner() {
    // getting the prototype
    // the prototype is the form itself, plain html
    var prototype = $collectionHolderBeneficialOwner.data('prototype');
    // get the index
    // this is the index we set when the document was ready, look above for more info
    var index = $collectionHolderBeneficialOwner.data('index');
    // create the form
    var newForm = prototype;
    // replace the __name__ string in the html using a regular expression with the index value
    newForm = newForm.replace(/__name__/g, index);
    // incrementing the index data and setting it again to the collectionHolder
    $collectionHolderBeneficialOwner.data('index', index+1);
    // create the panel
    // this is the panel that will be appending to the collectionHolder
    var $panel = $('<div class="beneficial-owners-names-panel"></div>');
    // create the panel-body and append the form to it
    var $panelBody = $('<div class="panel-body"></div>').append(newForm);
    // append the body to the panel
    $panel.append($panelBody);
    // append the removebutton to the new panel
    addRemoveButtonBeneficialOwner($panel);
    // append the panel to the addNewItem
    // we are doing it this way to that the link is always at the bottom of the collectionHolder
    $addBeneficialOwner.before($panel);
}

/**
 * adds a remove button to the panel that is passed in the parameter
 * @param $panel
 */
function addRemoveButtonDirectors ($panel) {
    // create remove button
    var $removeButton = $('<a href="#" class="btn btn-outline-danger">Remove</a>');
    // appending the removebutton to the panel footer
    var $panelFooter = $('<div class="panel-footer"></div>').append($removeButton);
    // handle the click event of the remove button
    $removeButton.click(function (e) {
        e.preventDefault();
        // gets the parent of the button that we clicked on "the panel" and animates it
        // after the animation is done the element (the panel) is removed from the html
        $(e.target).parents('.directors-names-panel').slideUp(100, function () {
            $(this).remove();
        })
    });
    // append the footer to the panel
    $panel.append($panelFooter);
}

/**
 * adds a remove button to the panel that is passed in the parameter
 * @param $panel
 */
function addRemoveButtonBeneficialOwner ($panel) {
    // create remove button
    var $removeButton = $('<a href="#" class="btn btn-outline-danger">Remove</a>');
    // appending the removebutton to the panel footer
    var $panelFooter = $('<div class="panel-footer"></div>').append($removeButton);
    // handle the click event of the remove button
    $removeButton.click(function (e) {
        e.preventDefault();
        // gets the parent of the button that we clicked on "the panel" and animates it
        // after the animation is done the element (the panel) is removed from the html
        $(e.target).parents('.beneficial-owners-names-panel').slideUp(100, function () {
            $(this).remove();
        })
    });
    // append the footer to the panel
    $panel.append($panelFooter);
}