const inputAsShares = document.getElementById('investment_retail_numberOfShares');
const inputAsAmount = document.getElementById('investment-total-value');

const formWrapper = document.getElementById('total-shares-input');
const opportunityInfo = document.getElementById('opportunity-info');

const shareAvailable = parseInt(opportunityInfo.dataset.shares);
const sharePrice = parseFloat(opportunityInfo.dataset.sharePrice);
const minCommit = Math.round(parseFloat(opportunityInfo.dataset.minCommit) / sharePrice);
const maxCommit = Math.round(parseFloat(opportunityInfo.dataset.maxCommit) / sharePrice);
const investedThisMonth = parseFloat(opportunityInfo.dataset.investedThisMonth);

const summaryTotal = document.getElementById('summary-total');
const summaryInvestment = document.getElementById('summary-investment');
const summaryStampDuty = document.getElementById('summary-stamp-duty');

formWrapper.addEventListener('input', processTotal);
formWrapper.addEventListener('change', processTotal);

document.addEventListener('DOMContentLoaded', (event) => {
    syncInputs(calcSharesFromAmount(inputAsAmount.value));
    updateSummary();
});


function processTotal(e) {
    let numberOfShares = inputAsShares.value;
    if ("monetary" === e.target.dataset.inputType) {
        numberOfShares = calcSharesFromAmount(e.target.value);
    }
    if (
        "change" == e.type
        || numberOfShares >= getMaxTotalShares()
        || ("shares" === e.target.dataset.inputType && numberOfShares >= minCommit)
    ) {
        syncInputs(numberOfShares);
        updateSummary();
    }
}

function syncInputs(shares) {
    let validShares = getValidNumberOfShares(shares)
    inputAsShares.value = validShares;
    if (validShares < 1) {
        amount = "0";
    } else {
        amount = parseFloat(inputAsShares.value * sharePrice).toFixed(2); 
    }
    inputAsAmount.value = amount;
}

function getValidNumberOfShares(shares) {
    let validShares = 0;
    validShares = Math.min(shares, getMaxTotalShares());
    validShares = Math.max(validShares, minCommit);
    return validShares;
}

function getMaxTotalShares() {
    if (!maxCommit) {
        return shareAvailable;
    }
    else {
        return Math.min(shareAvailable, maxCommit);
    }
}

function updateSummary() {
    // summaryTotal.textContent = inputAsAmount.value;
    summaryInvestment.textContent = inputAsAmount.value;
    let stampDuty = (calcStampDutyDue(investedThisMonth, parseFloat(inputAsAmount.value)));
    summaryStampDuty.textContent = stampDuty.toFixed(2);
    summaryTotal.textContent = (parseFloat(inputAsAmount.value) + stampDuty).toFixed(2);
}

function calcSharesFromAmount(amount) {
    return Math.floor(amount / sharePrice);
}

function calcStampDuty(amount) {
    if (amount >= 1000) {
        stamp_duty = parseFloat(((0.5 * amount) / 100));
        stamp_duty = roundToMultiple(stamp_duty, 5); //round up stamp duty to nearest multiple of 5
        return stamp_duty;
    }
    return 0;
}

function calcStampDutyDue(existingAmount, newAmount) {
    let existingStampDuty = calcStampDuty(existingAmount);
    let newTotalStampDuty = calcStampDuty(existingAmount + newAmount);
    return Math.round(newTotalStampDuty - existingStampDuty);
}

function roundToMultiple(val, multiple) {
    // Rounds some value to the nearest multiple given
    if (isNaN(val)) {
        return 0;
    }
    if (val < multiple) { 
        return multiple;
    }
    return Math.ceil(val / multiple) * multiple;
};