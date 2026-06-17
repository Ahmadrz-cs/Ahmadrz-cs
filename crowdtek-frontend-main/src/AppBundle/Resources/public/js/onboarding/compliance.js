window.onload = new function chkbusiness() {
    if ($('#userInformation_info_corporate_investor').is(":checked")) {
        document.getElementById('complient3').innerHTML = '3';
        document.getElementById('complient4').innerHTML = '4';
        document.getElementById('complient5').innerHTML = '5';
        $("#businessaddress").removeClass('hide');
        $("#business-doc").removeClass('hide');
    } else {
        $("#business-doc").addClass('hide');
        $("#businessaddress").addClass('hide');
        document.getElementById('complient4').innerHTML = '3';
        document.getElementById('complient5').innerHTML = '4';
    }
}

function chkcountry() {

    if (document.getElementById("userInformation_address_country").value == "") {
        $("#userInformation_address_country").addClass('invalid_email');
        $("#userInformation_address_country").removeClass('valid_email');

    }
    else {
        $("#userInformation_address_country").addClass('valid_email');
        $("#userInformation_address_country").removeClass('invalid_email');
    }
}
function View_Tab(qno) {

    var stp = 1;

    if (qno == 2) {

        if (document.getElementById("userInformation_honorific_prefix").value == "Select an option") {
            $('#userInformation_honorific_prefix').addClass('invalid_email');
            stp = 0;
        }
        if (document.getElementById("userInformation_gender").value == "Select an option") {
            $('#userInformation_gender').addClass('invalid_email');
            stp = 0;
        }
        if (document.getElementById("userInformation_firstname").value == "") {
            $('#userInformation_firstname').addClass('invalid_business');
            stp = 0;
        }
        if (document.getElementById("userInformation_lastname").value == "") {
            $('#userInformation_lastname').addClass('invalid_business');
            stp = 0;
        }

        var chk_dob = "block";// $("#dob_desktop").css('display');

        if (chk_dob == "block") {
            if (document.getElementById("userInformation_birthDate_day").value == "") {
                $('#userInformation_birthDate_day').addClass('invalid_email');
                stp = 0;
            }
            if (document.getElementById("userInformation_birthDate_month").value == "") {
                $('#userInformation_birthDate_month').addClass('invalid_email');
                stp = 0;
            }
            if (document.getElementById("userInformation_birthDate_year").value == "") {
                $('#userInformation_birthDate_year').addClass('invalid_email');
                stp = 0;
            }
        }
        else {
            if (document.getElementById("txtbirthdate").value == "") {
                $('#txtbirthdate').addClass('invalid_email');
                stp = 0;
            }
        }
        if (document.getElementById("userInformation_nationality").value == "") {
            $('#userInformation_nationality').addClass('invalid_email');
            stp = 0;
        }
        if (stp == 0) {
            return false;
        }

    }
    if (qno == 3) {
        if (document.getElementById("userInformation_address_country").value == "") {
            $('#userInformation_address_country').addClass('invalid_email');
            stp = 0;
        }
        if (document.getElementById("userInformation_address_address1").value == "") {
            $('#userInformation_address_address1').addClass('invalid_business');
            stp = 0;
        }
        if (document.getElementById("userInformation_address_city").value == "") {
            $('#userInformation_address_city').addClass('invalid_business');
            stp = 0;
        }
        if (document.getElementById("userInformation_address_postal_code").value == "") {
            $('#userInformation_address_postal_code').addClass('invalid_business');
            stp = 0;
        }
        if (stp == 0) {
            return false;
        }

    }
    if (qno == 4) {
        if ($('#userInformation_info_corporate_investor').is(":checked")) {
            if (document.getElementById("userInformation_info_company_name").value == "") {
                $('#userInformation_info_company_name').addClass('invalid_business');
                stp = 0;

            }
            if (document.getElementById("userInformation_info_company_registered_number").value == "") {
                $('#userInformation_info_company_registered_number').addClass('invalid_business');
                stp = 0;

            }
            if (document.getElementById("userInformation_info_company_nature_of_business").value == "") {
                $('#userInformation_info_company_nature_of_business').addClass('invalid_business');
                stp = 0;

            }
            if (document.getElementById("userInformation_info_company_telephone").value == "") {
                $('#userInformation_info_company_telephone').addClass('invalid_business');
                stp = 0;

            }

            if (document.getElementById("userInformation_info_company_registration_country").value == "") {
                $('#userInformation_info_company_registration_country').addClass('invalid_email');
                stp = 0;
            }

            if (document.getElementById("userInformation_info_company_registered_address_1").value == "") {
                $('#userInformation_info_company_registered_address_1').addClass('invalid_business');
                stp = 0;

            }
            if (document.getElementById("userInformation_info_company_postcode").value == "") {
                $('#userInformation_info_company_postcode').addClass('invalid_business');
                stp = 0;

            }
            if (document.getElementById("operating-address-same").checked == false) {
                if (document.getElementById("userInformation_info_operating_address").value == "") {
                    $('#userInformation_info_operating_address').addClass('invalid_business');
                    stp = 0;

                }
                if (document.getElementById("userInformation_info_operating_postcode").value == "") {
                    $('#userInformation_info_operating_postcode').addClass('invalid_business');
                    stp = 0;

                }
            }
        }


        if (stp == 0) {
            return false;
        }

    }
    if (qno == 5) {
        if (document.getElementById("userInformation_phone1").value == "") {
            $('#userInformation_phone1').addClass('invalid_business');
            stp = 0;
        }
        // if (document.getElementById("userInformation_phone2").value == "") {
        //     $('#userInformation_phone2').addClass('invalid_business');
        //     stp = 0;
        // }


        if (stp == 0) {
            return false;
        }

    }
    var qn = parseInt(qno);
    var x = 0;
    if ($('#userInformation_info_corporate_investor').is(":checked")) {
        for (x = 1; x < 8; x++) {
            if (qn == x) {
                $('#complient' + x).addClass('complient_h');
                $('#step_' + x).addClass('hide');
                $('#complient' + x).removeClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_next');
                $('#accordion' + x).removeClass('hide');
            }
            if (x < qn) {
                $('#step_' + x).removeClass('hide');
                $('#complient' + x).addClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_h');
                $('#complient' + x).removeClass('complient_next');
                $('#accordion' + x).addClass('hide');
            }
            if (x > qn) {
                $('#step_' + x).addClass('hide');
                $('#complient' + x).removeClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_h');
                $('#complient' + x).addClass('complient_next');
                $('#accordion' + x).addClass('hide');
            }
        }
    }
    else {

        if (qn == 3) {
            qn = qn + 1;
        }

        for (x = 1; x < 8; x++) {

            if (qn == x) {
                $('#step_' + x).addClass('hide');
                $('#complient' + x).addClass('complient_h');
                $('#complient' + x).removeClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_next');
                $('#accordion' + x).removeClass('hide');
            }
            if (x < qn) {
                $('#step_' + x).removeClass('hide');
                $('#complient' + x).addClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_h');
                $('#complient' + x).removeClass('complient_next');
                $('#accordion' + x).addClass('hide');
            }
            if (x > qn) {
                $('#step_' + x).addClass('hide');
                $('#complient' + x).removeClass('complient_h_complete');
                $('#complient' + x).removeClass('complient_h');
                $('#complient' + x).addClass('complient_next');
                $('#accordion' + x).addClass('hide');
            }
        }
    }


}
$(document).ready(function () {

    if (document.getElementById("userInformation_firstname").value != "") {
        //  document.getElementById("lbluserInformation_firstname").style = "top: -15px;";
        $('#lbluserInformation_firstname').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_firstname').removeClass('top-mar15');
        document.getElementById("lbluserInformation_firstname").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_lastname").value != "") {
        $('#lbluserInformation_lastname').addClass('top-mar15');
        document.getElementById("lbluserInformation_lastname").style = "top: -15px;";
    }
    else {
        $('#lbluserInformation_lastname').removeClass('top-mar15');
        document.getElementById("lbluserInformation_lastname").style = "top: -15px;";
    }

    if (document.getElementById("userInformation_address_address1").value != "") {
        $('#lbluserInformation_address_address1').addClass('top-mar15');
        document.getElementById("lbluserInformation_address_address1").style = "top: -15px;";
    }
    else {
        $('#lbluserInformation_address_address1').removeClass('top-mar15');
        document.getElementById("lbluserInformation_address_address1").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_address2").value != "") {
        document.getElementById("lbluserInformation_address_address2").style = "top: -15px;";
        $('#lbluserInformation_address_address2').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_address_address2').removeClass('top-mar15');
        document.getElementById("lbluserInformation_address_address2").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_address3").value != "") {
        document.getElementById("lbluserInformation_address_address3").style = "top: -15px;";
        $('#lbluserInformation_address_address3').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_address_address3').removeClass('top-mar15');
        document.getElementById("lbluserInformation_address_address3").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_city").value != "") {
        document.getElementById("lbluserInformation_address_city").style = "top: -15px;";
        $('#lbluserInformation_address_city').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_address_city').removeClass('top-mar15');
        document.getElementById("lbluserInformation_address_city").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_postal_code").value != "") {
        document.getElementById("lbluserInformation_address_postal_code").style = "top: -15px;";
        $('#lbluserInformation_address_postal_code').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_address_postal_code').removeClass('top-mar15');
        document.getElementById("lbluserInformation_address_postal_code").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_name").value != "") {
        document.getElementById("lbluserInformation_info_company_name").style = "top: -15px;";
        $('#lbluserInformation_info_company_name').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_name').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_name").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_number").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_number").style = "top: -15px;";
        $('#lbluserInformation_info_company_registered_number').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_registered_number').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_registered_number").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_nature_of_business").value != "") {
        document.getElementById("lbluserInformation_info_company_nature_of_business").style = "top: -15px;";
        $('#lbluserInformation_info_company_nature_of_business').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_nature_of_business').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_nature_of_business").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_website").value != "") {
        document.getElementById("lbluserInformation_info_company_website").style = "top: -15px;";
        $('#lbluserInformation_info_company_website').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_website').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_website").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_1").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_1").style = "top: -15px;";
        $('#lbluserInformation_info_company_registered_address_1').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_registered_address_1').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_registered_address_1").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_2").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_2").style = "top: -15px;";
        $('#userInformation_info_company_registered_address_2').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_registered_address_2').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_registered_address_2").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_3").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_3").style = "top: -15px;";
        $('#lbluserInformation_info_company_registered_address_3').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_registered_address_3').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_registered_address_3").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_postcode").value != "") {
        document.getElementById("lbluserInformation_info_company_postcode").style = "top: -15px;";
        $('#lbluserInformation_info_company_postcode').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_postcode').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_postcode").style = "top: -15px;";
    }

});
function chkemptyval() {
    if (document.getElementById("userInformation_firstname").value != "") {
        //  document.getElementById("lbluserInformation_firstname").style = "top: -15px;";
        $('#lbluserInformation_firstname').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_firstname').removeClass('top-mar15');
        document.getElementById("lbluserInformation_firstname").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_lastname").value != "") {
        $('#lbluserInformation_lastname').addClass('top-mar15');
        document.getElementById("lbluserInformation_lastname").style = "top: -15px;";
    }
    else {
        $('#lbluserInformation_lastname').removeClass('top-mar15');
        document.getElementById("lbluserInformation_lastname").style = "top: -15px;";
    }

    if (document.getElementById("userInformation_address_address1").value != "") {
        $('#lbluserInformation_address_address1').addClass('top-mar15');
        document.getElementById("lbluserInformation_address_address1").style = "top: -15px;";
    }
    else {
        $('#userInformation_address_address1').removeClass('top-mar15');
        document.getElementById("userInformation_address_address1").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_address2").value != "") {
        document.getElementById("lbluserInformation_address_address2").style = "top: -15px;";
        $('#lbluserInformation_address_address2').addClass('top-mar15');
    }
    else {
        $('#userInformation_address_address2').removeClass('top-mar15');
        document.getElementById("userInformation_address_address2").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_address3").value != "") {
        document.getElementById("lbluserInformation_address_address3").style = "top: -15px;";
        $('#lbluserInformation_address_address3').addClass('top-mar15');
    }
    else {
        $('#userInformation_address_address3').removeClass('top-mar15');
        document.getElementById("userInformation_address_address3").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_city").value != "") {
        document.getElementById("lbluserInformation_address_city").style = "top: -15px;";
        $('#lbluserInformation_address_city').addClass('top-mar15');
    }
    else {
        $('#userInformation_address_city').removeClass('top-mar15');
        document.getElementById("userInformation_address_city").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_address_postal_code").value != "") {
        document.getElementById("lbluserInformation_address_postal_code").style = "top: -15px;";
        $('#lbluserInformation_address_postal_code').addClass('top-mar15');
    }
    else {
        $('#userInformation_address_postal_code').removeClass('top-mar15');
        document.getElementById("userInformation_address_postal_code").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_name").value != "") {
        document.getElementById("lbluserInformation_info_company_name").style = "top: -15px;";
        $('#userInformation_info_company_name').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_name').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_name").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_number").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_number").style = "top: -15px;";
        $('#userInformation_info_company_registered_number').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_registered_number').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_registered_number").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_nature_of_business").value != "") {
        document.getElementById("lbluserInformation_info_company_nature_of_business").style = "top: -15px;";
        $('#userInformation_info_company_nature_of_business').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_nature_of_business').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_nature_of_business").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_website").value != "") {
        document.getElementById("lbluserInformation_info_company_website").style = "top: -15px;";
        $('#lbluserInformation_info_company_website').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_website').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_website").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_1").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_1").style = "top: -15px;";
        $('#lbluserInformation_info_company_registered_address_1').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_registered_address_1').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_registered_address_1").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_2").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_2").style = "top: -15px;";
        $('#userInformation_info_company_registered_address_2').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_registered_address_2').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_registered_address_2").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_registered_address_3").value != "") {
        document.getElementById("lbluserInformation_info_company_registered_address_3").style = "top: -15px;";
        $('#lbluserInformation_info_company_registered_address_3').addClass('top-mar15');
    }
    else {
        $('#userInformation_info_company_registered_address_3').removeClass('top-mar15');
        document.getElementById("userInformation_info_company_registered_address_3").style = "top: -15px;";
    }
    if (document.getElementById("userInformation_info_company_postcode").value != "") {
        document.getElementById("lbluserInformation_info_company_postcode").style = "top: -15px;";
        $('#lbluserInformation_info_company_postcode').addClass('top-mar15');
    }
    else {
        $('#lbluserInformation_info_company_postcode').removeClass('top-mar15');
        document.getElementById("lbluserInformation_info_company_postcode").style = "top: -15px;";
    }
}

function check_document() {

    var chkdoc3 = 1;
    if ($('#userInformation_info_corporate_investor').is(":checked")) {
        if (document.getElementById("hdndoc1").value == "0" || document.getElementById("hdndoc1").value == "") {
            chkdoc3 = 0;
            //$('#file_1').addClass('fileupload_Invalid');
            $('#file_1').removeClass('fileupload_Valid');
        }
        if (document.getElementById("hdndoc2").value == "0" || document.getElementById("hdndoc2").value == "") {
            chkdoc3 = 0;
            //  $('#file_2').addClass('fileupload_Invalid');
            $('#file_2').removeClass('fileupload_Valid');
        }

        if (document.getElementById("hdndoc3").value == "0" || document.getElementById("hdndoc3").value == "") {
            chkdoc3 = 0;
            //   $('#file_3').addClass('fileupload_Invalid');
            $('#file_3').removeClass('fileupload_Valid');
        }
        if (chkdoc3 == 0) {
            return false;
        }

    }
    else {
        var chksptdoc = 1;
        if (document.getElementById("hdndoc1").value == "0" || document.getElementById("hdndoc1").value == "") {
            chksptdoc = 0;
            //  $('#file_1').addClass('fileupload_Invalid');
            $('#file_1').removeClass('fileupload_Valid');
        }
        if (document.getElementById("hdndoc2").value == "0" || document.getElementById("hdndoc2").value == "") {
            chksptdoc = 0;
            //  $('#file_2').addClass('fileupload_Invalid');
            $('#file_2').removeClass('fileupload_Valid');
        }
        if (chksptdoc == 0) {
            return false;
        }
    }
    return true;
}

function checkFileSize(file) {
    var fileSize = file.size;
    // 4*2**20 == 4,194,304Bytes == 4MiB
    if (fileSize < 4194300) {
        return true;
    }
    else {
        return false;
    }
}

var validFileTypes = [".jpg", ".jpeg", ".pdf", ".gif", ".png"];
function checkFileType(file) {
    var fileName = file.name;
    // console.log("Filename:" + fileName);

    for (var i = 0; i < validFileTypes.length; i++) {
        var currType = validFileTypes[i];
        // extract the last n chars from the filename to see if it matches the fileType
        // set to lowercase to ignore case sensitivity, i.e.g we want .JPEG == .jpeg to be true
        if (fileName.substr(fileName.length - currType.length, currType.length).toLowerCase() == currType.toLowerCase()) {
            return true;
        }
    }
    return false;
}

function readURL(input, imgid_val) {
    // check that there are files in the input 
    // complete validation checks
    var imgid = imgid_val;

    if (input.files) {
        var validFileSize = checkFileSize(input.files[0]);
        var validFileType = checkFileType(input.files[0]);
        // if valid then update the form
        if (validFileSize && validFileType) {
            if (imgid_val == 1) {
                document.getElementById("hdndoc1").value = "1";
            }
            if (imgid_val == 2) {
                document.getElementById("hdndoc2").value = "2";
            }
            if (imgid_val == 3) {
                document.getElementById("hdndoc3").value = "3";
            }
            $('#tryother' + imgid_val).hide();
            if (window.FileReader) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('#uploadwrap' + imgid).hide();
                    $('#pre' + imgid).attr('src', e.target.result);
                    $('#imgcontent' + imgid).show();
                    $('#imgtitle' + imgid).html(input.files[0].name);
                    $('#file_' + imgid).removeClass('fileupload_start');
                    $('#file_' + imgid).addClass('fileupload_Valid');
                };

                reader.readAsDataURL(input.files[0]);
            }
            else {
                $('#uploadwrap' + imgid).hide();
                //$('#pre' + imgid).attr('src', e.target.result);
                $('#imgcontent' + imgid).show();
                $('#imgtitle' + imgid).html(input.files[0].name);
                $('#file_' + imgid).removeClass('fileupload_start');
                $('#file_' + imgid).addClass('fileupload_Valid');
            }
        }
        else {
            removeUpload(imgid_val);
            $('#tryother' + imgid_val).show();
        }
        // if not set the error messages
        if (!validFileSize) {
            $('#sizeWarning' + imgid_val).show();
        }
        else {
            $('#sizeWarning' + imgid_val).hide();
        }
        if (!validFileType) {
            $('#typeWarning' + imgid_val).show();
        }
        else {
            $('#typeWarning' + imgid_val).hide();
        }

        if (check_document()) {
            $("#submit-compliance").prop("disabled",false);
        }
        else {
            $("#submit-compliance").prop("disabled",true);
        }

    }

}

function removeUpload(imgid_val) {

    var imgid = imgid_val;
    var file_info_type = "";
    if (imgid_val == 1) {
        document.getElementById("hdndoc1").value = "";
        file_info_type = "id";
    }
    if (imgid_val == 2) {
        document.getElementById("hdndoc2").value = "";
        file_info_type = "address";
    }
    if (imgid_val == 3) {
        document.getElementById("hdndoc3").value = "";
        file_info_type = "business";
    }
    var input = $("#userInformation_document_proof_of_" + file_info_type);
    input.val(null);
    $('#imgcontent' + imgid).hide();
    $('#uploadwrap' + imgid).show();
    $('#file_' + imgid).removeClass('fileupload_Valid');
    $('#file_' + imgid).addClass('fileupload_start');

    $("#submit-compliance").prop("disabled",true);
}


$('#postcode_lookup').change(function () {
    setTimeout(chkemptyval, 250);
});

$('#company_reg_postcode_lookup').change(function () {
    setTimeout(chkemptyval, 250);
});
