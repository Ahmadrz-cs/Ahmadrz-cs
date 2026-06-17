const totalSharesInput = document.getElementById('prefunding_numberOfShares');
const totalValueInput = document.getElementById('prefunding-total-value');
const keepSharesInput = document.getElementById('prefunding_sharesToKeep');
const keepValueInput = document.getElementById('prefunding-keep-value');

const totalInputGroup = document.getElementById('total-shares-input');
const keepInputGroup = document.getElementById('keep-shares-input');

const opportunityInfo = document.getElementById('opportunity-info');
const shareAvailable = parseInt(opportunityInfo.dataset.shares);
const sharePrice = parseFloat(opportunityInfo.dataset.sharePrice);
const minCommit = Math.round(parseFloat(opportunityInfo.dataset.minCommit) / sharePrice);
const maxCommit = Math.round(parseFloat(opportunityInfo.dataset.maxCommit) / sharePrice);
const minRetention = parseInt(opportunityInfo.dataset.minRetention);
const maxRetention = parseInt(opportunityInfo.dataset.maxRetention);

const summaryTotal = document.getElementById('summary-total');
const summaryPrefunding = document.getElementById('summary-prefunding');
const summaryKeep = document.getElementById('summary-keep');

totalInputGroup.addEventListener('input', processTotal);
totalInputGroup.addEventListener('change', processTotal);
keepInputGroup.addEventListener('input', processRetention);
keepInputGroup.addEventListener('change', processRetention);

document.addEventListener('DOMContentLoaded', (event) => {
    updateTotal(calcSharesFromAmount(totalValueInput.value));
    updateSummary();
});

function processTotal(e) {
    let numberOfShares = totalSharesInput.value;
    if ("monetary" === e.target.dataset.inputType) {
        numberOfShares = calcSharesFromAmount(e.target.value);
    }
    if (
        "change" == e.type
        || numberOfShares >= getMaxTotalShares()
        || ("shares" === e.target.dataset.inputType && numberOfShares >= minCommit)
    ) {
        updateTotal(numberOfShares);
        if ("change" == e.type && keepSharesInput.value > getMaxRetentionShares()) {
            updateRetention(getMaxRetentionShares());
        }
        updateSummary();
    }
}

function processRetention(e) {
    let numberOfShares = keepSharesInput.value;
    if ("monetary" === e.target.dataset.inputType) {
        numberOfShares = (calcSharesFromAmount(e.target.value));
    }
    if (
        "change" == e.type
        || numberOfShares >= getMaxRetentionShares()
        || ("shares" === e.target.dataset.inputType && numberOfShares >= getMinRetentionShares())
    ) {
        updateRetention(numberOfShares);
        updateSummary();
    }
}

function updateTotal(shares) {
    totalSharesInput.value = getValidTotal(shares);
    totalValueInput.value = parseFloat(totalSharesInput.value * sharePrice).toFixed(2);
}

function updateRetention(shares) {
    keepSharesInput.value = getValidRetention(shares);
    keepValueInput.value = parseFloat(keepSharesInput.value * sharePrice).toFixed(2);
}

function getValidTotal(shares) {
    let validShares = 0;
    validShares = Math.min(shares, getMaxTotalShares());
    validShares = Math.max(validShares, minCommit);
    return validShares;
}

function getValidRetention(shares) {
    let validRetention = shares;
    if (shares > 0) {
        validRetention = Math.max(validRetention, getMinRetentionShares(shares));
    }
    validRetention = Math.min(validRetention, getMaxRetentionShares(shares));
    return validRetention;
}

function getMaxTotalShares() {
    if (!maxCommit) {
        return shareAvailable;
    }
    else {
        return Math.min(shareAvailable, maxCommit);
    }
}

function getMinRetentionShares() {
    return Math.ceil((parseInt(totalSharesInput.value) * minRetention) / 100);
}

function getMaxRetentionShares() {
    return Math.floor((parseInt(totalSharesInput.value) * maxRetention) / 100);
}

function updateSummary() {
    summaryTotal.children[1].textContent = totalSharesInput.value;
    summaryTotal.children[2].textContent = totalValueInput.value;
    summaryPrefunding.children[1].textContent = getPrefundingAmount();
    summaryPrefunding.children[2].textContent = (getPrefundingAmount() * sharePrice).toFixed(2);
    summaryKeep.children[1].textContent = keepSharesInput.value;
    summaryKeep.children[2].textContent = keepValueInput.value;
}

function getPrefundingAmount() {
    return totalSharesInput.value - keepSharesInput.value;
}

function calcSharesFromAmount(amount) {
    return Math.floor(amount / sharePrice);
}