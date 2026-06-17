// Input fields

const emailField = document.getElementById('signUpUser_email');
const nameField1 = document.getElementById('signUpUser_firstname');
const nameField2 = document.getElementById('signUpUser_lastname');
const pwField1 = document.getElementById('signUpUser_password_first');
const pwField2 = document.getElementById('signUpUser_password_second');
const pwToggle1 = document.getElementById('toggle_1');
const pwToggle2 = document.getElementById('toggle_2');
const continueBtn = document.getElementById('btn_continue');

// Listeners

pwToggle1.addEventListener('click', togglePw);
pwToggle2.addEventListener('click', togglePw);
continueBtn.addEventListener('click', validateForm);
nameField1.addEventListener('input', validateNameField);
nameField2.addEventListener('input', validateNameField);
pwField1.addEventListener('input', checkPwStrength);
pwField2.addEventListener('input', checkPwMatch);
emailField.addEventListener('change', function () {
    if (checkEmail(event.target.value)) {
        event.target.classList.remove('invalid_email');
    }
    else {
        event.target.classList.add('invalid_email');
    }
});

function validateNameField(event) {
    if (checkName(event.target.value)) {
        event.target.classList.remove('invalid_email');
    }
    else {
        event.target.classList.add('invalid_email');
    }
}

// Functions

function togglePw() {

    var Val = pwField1.getAttribute("type");

    if (Val == "password") {
        pwField1.setAttribute("type", "text");
        pwField2.setAttribute("type", "text");
        pwToggle1.classList.add("pw-show");
        pwToggle1.classList.remove("pw-hide");
        pwToggle2.classList.add("pw-show");
        pwToggle2.classList.remove("pw-hide");
    }
    else if (Val == "text") {
        pwField1.setAttribute("type", "password");
        pwField2.setAttribute("type", "password");
        pwToggle1.classList.add("pw-hide");
        pwToggle1.classList.remove("pw-show");
        pwToggle2.classList.add("pw-hide");
        pwToggle2.classList.remove("pw-show");
    }

}

function checkEmail(email) {
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email)) {
        return true;
    }
    else {
        return false;
    }
}

function checkName(name) {
    if (/^[a-zA-Z][a-zA-Z .,'-]*$/.test(name)) {
        return true;
    }
    else {
        return false;
    }
}

function checkPwStrength() {

    let pswd = pwField1.value;

    // validate letter
    if (pswd.match(/[a-z]/)) {
        $('#letter').removeClass('text-danger border-left border-danger');
    } else {
        $('#letter').addClass('text-danger border-left border-danger');
    }

    //validate capital letter
    if (pswd.match(/[A-Z]/)) {
        $('#capital').removeClass('text-danger border-left border-danger');
    } else {
        $('#capital').addClass('text-danger border-left border-danger');
    }

    //validate number
    if (pswd.match(/\d/)) {
        $('#number').removeClass('text-danger border-left border-danger');
    } else {
        $('#number').addClass('text-danger border-left border-danger');
    }

    // validate length
    if (pswd.length > 7) {
        $('#length').removeClass('text-danger border-left border-danger');
    }
    else {
        $('#length').addClass('text-danger border-left border-danger');
    }

    if (pswd.match(/[a-z]/) && pswd.match(/[A-Z]/) && pswd.match(/\d/) && pswd.length > 7 ) {
        return true;
    }
    else {
        return false;
    }

}

function checkPwMatch() {

    // validate match
    if (pwField1.value == pwField2.value) {
        $('#noMatch').addClass('hidden').removeClass('text-danger border-left border-danger');
        return true;
    }
    else {
        $('#noMatch').removeClass('hidden').addClass('text-danger border-left border-danger');
        return false;
    }

}

function validateForm(event) {
    event.preventDefault();
    if (checkName(nameField1.value) && checkName(nameField2.value) && checkEmail(emailField.value) && checkPwStrength() && checkPwMatch()) {
        document.getElementById('sign-up-form').submit();
    }

}