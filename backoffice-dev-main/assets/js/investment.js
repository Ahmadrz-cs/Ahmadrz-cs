/**
 * Investments specific JS
 * - Settlement data edit mode unlock
 */

const unlockFieldToggle = document.getElementById('toggle-settled-on');
const settledOnFields = document.querySelectorAll('#settleOnWidget select, #settleOnWidget option');
const investmentForm = document.querySelector('form[name="investment"]');

unlockFieldToggle.addEventListener('click', toggleSettled);
investmentForm.addEventListener('submit', unlockSettledOnForSubmit);

window.onload = function() {
    toggleSettledOnFields(true);
};

function toggleSettled() {
    let targetState = unlockFieldToggle.textContent;
    if (
        targetState == "Unlock" 
        && window.confirm('WARNING: Checking this box allows Settled On dates to be edited. Are you sure you would like to proceed?') == true
    ) {
        toggleSettledOnFields(false);
        unlockFieldToggle.textContent = "Lock";
    }
    else {
        toggleSettledOnFields(true);
        unlockFieldToggle.textContent = "Unlock";
    }
}

function toggleSettledOnFields(state) {
    settledOnFields.forEach(element => {
        element.disabled = state;
    });
}

function unlockSettledOnForSubmit(e) {
    e.preventDefault();
    toggleSettledOnFields(false);
    investmentForm.submit();
}
