// Input field

const formField = document.getElementById('user_form_type_info_words_of_your_own');
const limitNoticeField = document.getElementById('limitNotice');

// Listener

document.addEventListener('DOMContentLoaded', updateLimitNotice);
formField.addEventListener('input', updateLimitNotice);

// Function

function updateLimitNotice() {

    let charCount = formField.value.length;

    console.log(charCount);

    limitNoticeField.textContent = charCount;
}