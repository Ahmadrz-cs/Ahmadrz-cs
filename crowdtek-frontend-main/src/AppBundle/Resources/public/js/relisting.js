const sellForm = document.getElementById('shareholding-info');
const sharesToSell = document.getElementById('relisting_numberOfShares');
const valueToSell = document.getElementById('sale-value');

const relistingAmountElement = document.querySelector('#sale-value');
const relistingFeeElement = document.querySelector('#charge_listing');
const relistingDataElement = document.querySelector('#relisting-data');
const relistingFeeBands = JSON.parse(relistingDataElement.dataset.feeBands);
const monthlyRelistings = parseFloat(relistingDataElement.dataset.monthlyRelisting);
const feesPaid = getFeeCap(relistingFeeBands, monthlyRelistings);

document.addEventListener('DOMContentLoaded', (event) => {
    updateSalesData();
});
sharesToSell.addEventListener('input', updateSalesData);
sharesToSell.addEventListener('change', updateShareAmount);
valueToSell.addEventListener('change', updateShareAmount);

function updateShareAmount(e) {
    // console.log("calculating");
    if ("monetary" === e.target.dataset.inputType) {
        sharesToSell.value = Math.floor(parseInt(e.target.value, 10) / parseFloat(sellForm.dataset.sharePrice));
    }
    if (parseInt(sharesToSell.value, 10) < parseInt(sellForm.dataset.minShares, 10)) {
        sharesToSell.value = sellForm.dataset.minShares;
    }
    updateSalesData();
}

function updateSalesData() {
    if (parseInt(sharesToSell.value, 10) > parseInt(sellForm.dataset.shareholding, 10)) {
        sharesToSell.value = sellForm.dataset.shareholding;
    }
    valueToSell.value = (parseInt(sharesToSell.value, 10) * parseFloat(sellForm.dataset.sharePrice)).toFixed(2);
    updateRelistingFee(parseFloat(valueToSell.value) || 1);
}

function updateRelistingFee(amount) {
    let feeDue = 0;
    if (sellForm.dataset.feeExempt !== "1") {
        feeDue = getRelistingFeeDue(relistingFeeBands, monthlyRelistings, amount);
        let feeCap = getFeeCap(relistingFeeBands, monthlyRelistings + amount);
        console.log(feeDue, feeCap);
        console.log(relistingFeeBands);
        const feeTableRows = document.querySelectorAll('.fee-band-info');
        feeTableRows.forEach(function (row) {
            row.classList.remove('table-primary');
            if (row.dataset.feeBand == feeCap) {
                row.classList.add('table-primary');
            }
        });
    }
    relistingFeeElement.textContent = parseFloat(feeDue).toFixed(2);
}

function getRelistingFeeDue(feeBands, existing, current) {
    let feePaid = getFeeCap(feeBands, parseFloat(existing));
    let newCap = getFeeCap(feeBands, parseFloat(existing) + parseFloat(current));
    return newCap - feePaid;
}

function getFeeCap(feeBands, amount) {
    let fee = 0;
    for (let band in feeBands) {
        if (amount > 0 && amount > parseInt(band, 10)) {
            fee = parseInt(feeBands[band], 10);
        }
    }
    return fee;
}