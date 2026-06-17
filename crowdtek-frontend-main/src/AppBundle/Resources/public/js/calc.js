let stepSlider = document.getElementById('calc-slider');
noUiSlider.create(stepSlider, {
    start: [100],
    format: {
        to: function (value) {
            return Math.round(value);
        },
        from: function (value) {
            return Math.round(value);
        }
    },
    tooltips: [true],
    range: {
        'min': [100, 400],
        '1%': [500, 500],
        'max': [100000]
    }
});

let stepSliderValueElement = document.getElementById('calc-slider-value');
stepSlider.noUiSlider.on('update', function (values, handle) {
    stepSliderValueElement.value = values[handle];
});

// calc remaining calculator values
let remainingPercent = calcRemaining();
$('#percentage-remaining-return').attr('data-percentage', remainingPercent);
$('#percentage-remaining-return').text($.formatNumber(remainingPercent, { format: "#0.##" }) + "%");
calcYields(); // calc with default investment amount


$('#investment-btn').on('click', function (e) {
    calcYields(); // refresh calculations
});

function calcYields() {
    let investment_amount = parseInt($('#calc-slider-value').val()); // get the slider amount

    let rent = parseFloat($('#percentage-rent').attr('data-percentage'));
    let remainingReturn = parseFloat($('#percentage-remaining-return').attr('data-percentage'));
    let totalReturn = parseFloat($('#percentage-total-return').attr('data-percentage'));

    rent = investment_amount * (rent / 100);
    remainingReturn = (investment_amount * (remainingReturn / 100)) + investment_amount;
    totalReturn = (investment_amount * (totalReturn / 100)) + investment_amount;

    if (rent < 0) {
        rent = 0;
    }
    if (remainingReturn < 0) {
        remainingReturn = 0;
    }
    if (totalReturn < 0) {
        totalReturn = 0;
    }

    // show returns
    $('#rent-yield').text("£" + $.formatNumber(rent, { format: "#,##0.00" }));
    $('#rent-yield-monthly').text("£" + $.formatNumber(rent / 12, { format: "#,##0.00" }));
    $('#remaining-return').text("£" + $.formatNumber(remainingReturn, { format: "#,##0.00" }));
    $('#total-return').text("£" + $.formatNumber(totalReturn, { format: "#,##0.00" }));
}

function calcRemaining() {
    let rent = parseFloat($('#percentage-rent').attr('data-percentage'));
    let totalReturn = parseFloat($('#percentage-total-return').attr('data-percentage'));
    let invTerm = parseFloat($('#percentage-remaining-return').attr('data-term-years'));
    let termRemaining = parseFloat($('#percentage-remaining-return').attr('data-term-remaining'));
    return ((rent / 12) * termRemaining) + (totalReturn - (rent * invTerm));
}

function updateCalcData() {
    // update the calculator numbers with new values of active property - for marketing pages

    // get the active property as a jquery object
    let propertyData = $(".feature-properties .f-wrap.slick-current.slick-active");

    // update data attributes (which are used for the maths)
    $('#percentage-rent').attr('data-percentage', parseFloat(propertyData.data("rent")));
    $('#percentage-remaining-return').attr('data-term-years', parseFloat(propertyData.data("term-y")));
    $('#percentage-remaining-return').attr('data-term-remaining', parseFloat(propertyData.data("term-m")));
    $('#percentage-total-return').attr('data-percentage', parseFloat(propertyData.data("return")));

    //run calc remaining function
    let remaining = calcRemaining();
    $('#percentage-remaining-return').attr('data-percentage', remaining);

    // update text 
    $('#percentage-rent').text($.formatNumber(parseFloat(propertyData.data("rent")), { format: "#0.##" }) + "%");
    $('#percentage-remaining-return').text($.formatNumber(remaining, { format: "#0.##" }) + "%");
    $('#percentage-total-return').text($.formatNumber(parseFloat(propertyData.data("return")), { format: "#0.##" }) + "%");

    // calculate returns with input
    calcYields();

}