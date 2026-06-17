
$(document).ready(function () {
   
    $("i").click(function () {

        var Val = $("#sign_in_type_password").attr("type");

        if (Val == "password") {
            $("#sign_in_type_password").attr("type", "text"); 
            $("i").removeClass("fa-eye").addClass("fa-eye-slash");
        }
        else if (Val == "text") {
            $("#sign_in_type_password").attr("type", "password"); 
            $("i").removeClass("fa-eye-slash").addClass("fa-eye");
        }
    });
});

function checkemail() {
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(document.getElementById("sign_in_type_email").value)) {
        document.getElementById("sign_in_type_email").style = "border-bottom: 1px solid #e5e5e5;";
        $('#sign_in_type_email').addClass('valid_email');
        $('#sign_in_type_email').removeClass('invalid_email');
        return true;
    }
    else {
        document.getElementById("sign_in_type_email").style = "border-bottom: 1px solid #ff8282;";
        $('#sign_in_type_email').addClass('invalid_email');
        $('#sign_in_type_email').removeClass('valid_email');
        return false;
    }

}

function checksignin() {
    if (checkemail() && document.getElementById("sign_in_type_password").value) {
        $('#btn_continue').addClass('button_active');
    }
    else {
        $('#btn_continue').removeClass('button_active');
    }

}