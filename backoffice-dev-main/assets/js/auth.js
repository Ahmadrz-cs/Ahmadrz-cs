// css stuff from modules
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

// custom css
import '../css/auth.css';

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
import $ from 'jquery';

// js stuff from modules
import 'bootstrap';

const passwordField = document.getElementsByName('_password')[0];
const passwordToggle = document.getElementById('toggle-pw-visibility');
const toggleIcon = document.querySelector('#toggle-pw-visibility i');

passwordToggle.addEventListener('click', togglePwVisibility);

function togglePwVisibility() {
    if (passwordField.type == "password") {
        passwordField.type = "text";
        toggleIcon.classList.remove("fa-eye-slash");
        toggleIcon.classList.add("fa-eye");
    }
    else {
        if (passwordField.type == "text") {
            passwordField.type = "password";
            toggleIcon.classList.remove("fa-eye");
            toggleIcon.classList.add("fa-eye-slash");
        }
    }
}