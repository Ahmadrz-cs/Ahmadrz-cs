

























$(document).ready(function () {
    var navListItems = $('div.setup-panel-3 div a'),
        allWells = $('.setup-content-3'),
        allNextBtn = $('.nextBtn-3'),
        allPrevBtn = $('.prevBtn-3');

    allWells.hide();

    navListItems.click(function (e) {
        e.preventDefault();
        var $target = $($(this).attr('href')),
            $item = $(this);

        if (!$item.hasClass('disabled')) {
            navListItems.removeClass('btn-info').addClass('btn-pink');
            $item.addClass('btn-info');
            allWells.hide();
            $target.show();
            $target.find('input:eq(0)').focus();
        }
    });

    allPrevBtn.click(function(){
        var curStep = $(this).closest(".setup-content-3"),
            curStepBtn = curStep.attr("id"),
            prevStepSteps = $('div.setup-panel-3 div a[href="#' + curStepBtn + '"]').parent().prev().children("a");

            prevStepSteps.removeAttr('disabled').trigger('click');
    });

    allNextBtn.click(function(){
        var curStep = $(this).closest(".setup-content-3"),
            curStepBtn = curStep.attr("id"),
            nextStepSteps = $('div.setup-panel-3 div a[href="#' + curStepBtn + '"]').parent().next().children("a"),
            curInputs = curStep.find("input[type='text'],input[type='url']"),
            isValid = true;

        $(".form-group").removeClass("has-error");
            for(var i=0; i< curInputs.length; i++){
                if (!curInputs[i].validity.valid){
                    isValid = false;
                    $(curInputs[i]).closest(".form-group").addClass("has-error");
                }
            }

            if (isValid)
                nextStepSteps.removeAttr('disabled').trigger('click');
    });

    $('div.setup-panel-3 div a.btn-info').trigger('click');
});













var $body = $('body');
var $progressBar = $('progress');
var $animContainer = $('.animation-container');
var value = 0;
var transitionEnd = 'webkitTransitionEnd transitionend';

/**
 * Resets the form back to the default state.
 * ==========================================
 */
function formReset() {
	value = 0;
	$progressBar.val(value);
	$('form input').not('button').val('').removeClass('hasInput');
	$('.js-form-step').removeClass('left leaving');
	$('.js-form-step').not('.js-form-step[data-step="1"]').addClass('hidden waiting');
	$('.js-form-step[data-step="1"]').removeClass('hidden');
	$('.form-progress-indicator').not('.one').removeClass('active');
	
	$animContainer.css({
		'paddingBottom': $('.js-form-step[data-step="1"]').height() + 'px'
	});
	
	console.warn('Form reset.');
	return false;
}

/**
 * Sets up the click handlers on the form. Next/reset.
 * ===================================================
 */
function setupClickHandlers() {

	// Show next form on continue click
	$('button[type="submit"]').on('click', function(event) {
			event.preventDefault();
			var $currentForm = $(this).parents('.js-form-step');
			showNextForm($currentForm);
    });
    $('input[type="submit"]').on('click', function (event) {
        if ($('#optcard').is(":checked")) {
            if (document.getElementById("txtamount").value == "") {

                $("#txtamount").addClass('box_invalid');
            }
            else {
                $("#txtamount").removeClass('box_invalid');
                $("#txtamount").addClass('box_valid');
            }
            if (document.getElementById("txtcardholder").value == "") {
                $("#txtcardholder").addClass('box_invalid');
            }
            else {
                $("#txtcardholder").removeClass('box_invalid');
                $("#txtcardholder").addClass('box_valid');
            }
            if (document.getElementById("txtcardno").value == "") {
                $("#txtcardno").addClass('box_invalid');
            }
            else {
                $("#txtcardno").removeClass('box_invalid');
                $("#txtcardno").addClass('box_valid');
            }
            if (document.getElementById("txtcvv").value == "") {
                $("#txtcvv").addClass('box_invalid');
            }
            else {
                $("#txtcvv").removeClass('box_invalid');
                $("#txtcvv").addClass('box_valid');
            }
            if (document.getElementById("txtamount").value != "" && document.getElementById("txtcardholder").value != "" && document.getElementById("txtcardno").value != "" && document.getElementById("txtcvv").value != "") {
                event.preventDefault();
                var $currentForm = $(this).parents('.js-form-step');
                showNextForm($currentForm);
            }
        }
        else {
            if (document.getElementById("txtamount").value == "") {

                $("#txtamount").addClass('box_invalid');
            }
            if (document.getElementById("txtamount").value != "") {
                event.preventDefault();
                var $currentForm = $(this).parents('.js-form-step');
                showNextForm($currentForm);
            }
        }
    });
    $('a[type="submit"]').on('click', function (event) {
        if ($('#chkinvesting').is(":checked")) {
            if (document.getElementById("hdndoc1").value != "" && document.getElementById("hdndoc2").value != "" && document.getElementById("hdndoc3").value != "") {
                event.preventDefault();
                var $currentForm = $(this).parents('.js-form-step');
                showNextForm($currentForm);
            }
        }
        else {
            if (document.getElementById("hdndoc1").value != "" && document.getElementById("hdndoc2").value != "") {
                event.preventDefault();
                var $currentForm = $(this).parents('.js-form-step');
                showNextForm($currentForm);
            }

        }
    });
	// Reset form on reset button click
	$('.js-reset').on('click', function() {
		formReset();
	});
	
	return false;
}

/**
 * Shows the next form.
 * @param - Node - The current form.
 * ======================================
 */
function onlyAlphabets(e, t) {
    try {
        if (window.event) {
            var charCode = window.event.keyCode;
        }
        else if (e) {
            var charCode = e.which;
        }
        else { return true; }
        if ((charCode > 64 && charCode < 91) || (charCode > 96 && charCode < 123) || (charCode == 32))
            return true;
        else
            return false;
    }
    catch (err) {
        alert(err.Description);
    }
}
 
function showNextForm($currentForm) {
	var currentFormStep = parseInt($currentForm.attr('data-step')) || false;
	 var $nextForm = $('.js-form-step[data-step="' + (currentFormStep + 1) + '"]'); 
     
	 if(currentFormStep ==1)
     {
         $('#step4').removeClass("mobile_step");
         $('#step5').removeClass("mobile_step");
         document.getElementById('mob_text').innerHTML = 'SELF ASSESSMENT'; 
$('#step'+currentFormStep).addClass("previoustab");
	 }
	 if(currentFormStep ==4)
     {
         $('#step4').addClass("mobile_step");
         $('#step5').addClass("mobile_step");
         document.getElementById('mob_text').innerHTML = 'COMPLIANCE'; 
$('#step2').addClass("previoustab");
	 }
	 if(currentFormStep ==5)
     {
         document.getElementById('mob_text').innerHTML = 'COMPLETE'; 
		 $('#step6').addClass("previoustab");
$('#step'+currentFormStep).addClass("previoustab");
	 }
	 if(currentFormStep ==6)
	 {	
$('#step'+currentFormStep).addClass("previoustab");
	 } 
	console.log('Current step is ' + currentFormStep);
	console.log('The next form is # ' + $nextForm.attr('data-step'));

	$body.addClass('freeze');

	// Ensure top of form is in view
	$('html, body').animate({
		// scrollTop : $progressBar.offset().top
	}, 'fast');

	// Hide current form fields
	$currentForm.addClass('leaving');
	setTimeout(function() {
		$currentForm.addClass('hidden');
	}, 0);
	
	// Animate container to height of form
	$animContainer.css({
		'paddingBottom' : $nextForm.height() + 'px'
	});  

	// Show next form fields
	$nextForm.removeClass('hidden')
					 .addClass('coming')
					 .one(transitionEnd, function() {
						 $nextForm.removeClass('coming waiting');
					 });

	// Increment value (based on 4 steps 0 - 100)
	value += 33;

	// Reset if we've reached the end
	if (value >= 500) {
		formReset();
	} else {
		$('.form-progress')
			.find('.form-progress-indicator.active')
			.next('.form-progress-indicator')
			.addClass('active');

		// Set progress bar to the next value
		$progressBar.val(value);
	}

	// Update hidden progress descriptor (for a11y)
	$('.js-form-progress-completion').html($progressBar.val() + '% complete');

	$body.removeClass('freeze');

	return false;
}

/**
 * Sets up and handles the float labels on the inputs.
 =====================================================
 */
function setupFloatLabels() {
	// Check the inputs to see if we should keep the label floating or not
	$('form input').not('button').on('blur', function() {

		// Different validation for different inputs
		switch (this.tagName) {
			case 'SELECT':
				if (this.value > 0) {
					this.className = 'hasInput';
				} else {
					this.className = '';
				}
				break;

			 

			default:
				break;
		}
	});
	
	return false;
}
function init() {
	formReset();
	setupFloatLabels();
	setupClickHandlers();
}

init();


/**
 * Gets the party started.
 * =======================
 */
 
 
 
 
 
 
 /* MODAL */

var modal = document.querySelector("#modal");
var modalOverlay = document.querySelector("#modal-overlay");
var modalWrapper = document.querySelector("#modal-wrapper");
var closeButton = document.querySelector("#close-button");
var openButton = document.querySelector("#open-button");

closeButton.addEventListener("click", function() {
    $("#modal").hide();
    $("#modal").scrollTop();
    $("#modal-overlay").hide();
    $("#modal-wrapper").hide();
    $("body").removeClass("modal-open");
});

openButton.addEventListener("click", function() {
    $("#modal").show();
    $("#modal-overlay").show();
    $("#modal-wrapper").show();
    $("body").addClass("modal-open");
});

window.addEventListener("mouseup", function(event) {
    if (!$(event.target).closest(".modal,.open-button").length) {
        $("#modal").hide();
        $("#modal").scrollTop();
        $("#modal-overlay").hide();
        $("#modal-wrapper").hide();
        $("body").removeClass("modal-open");
    }
});


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
        $('#paybankwire').addClass('hide');
        
    }
    if ($('#optbank').is(":checked")) {
             
        $('#paybankwire').removeClass('hide');
        $('#paycard').addClass('hide');
    }
}









 