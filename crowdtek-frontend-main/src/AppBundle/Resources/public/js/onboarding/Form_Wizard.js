 $(window, document, undefined).ready(function () {

    $('input').blur(function () {
        var $this = $(this);
        if ($this.val())
            $this.addClass('used');
        else
            $this.removeClass('used');
    });

    var $ripples = $('.ripples');

    $ripples.on('click.Ripples', function (e) {

        var $this = $(this);
        var $offset = $this.parent().offset();
        var $circle = $this.find('.ripplesCircle');

        var x = e.pageX - $offset.left;
        var y = e.pageY - $offset.top;

        $circle.css({
            top: y + 'px',
            left: x + 'px'
        });

        $this.addClass('is-active');

    });

    $ripples.on('animationend webkitAnimationEnd mozAnimationEnd oanimationend MSAnimationEnd', function (e) {
        $(this).removeClass('is-active');
    });

});



function addListener () {
    $('input').blur(function () {
        var $this = $(this);
        if ($this.val())
            $this.addClass('used');
        else
            $this.removeClass('used');
    });
}

function readURL(input, imgid_val) {
    var imgid = imgid_val;
    if (imgid_val == 1) {
        document.getElementById("hdndoc1").value = "1";
    }
    if (imgid_val == 2) {
        document.getElementById("hdndoc2").value = "2";
    }
    if (imgid_val == 3) {
        document.getElementById("hdndoc3").value = "3";
    }
    if (input.files && input.files[0]) {

        var reader = new FileReader();

        reader.onload = function (e) {
            $('#uploadwrap'+imgid).hide();
            $('#pre' + imgid).attr('src', e.target.result);
            $('#imgcontent'+imgid).show();
            $('#imgtitle' + imgid).html(input.files[0].name);
            $('#file_' + imgid).removeClass('fileupload_start');
            $('#file_' + imgid).addClass('fileupload_Valid');
        };

        reader.readAsDataURL(input.files[0]);

    } else {
        removeUpload();
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
    var input = $("#userInformation_document_proof_of_"+file_info_type);
    input.val(null);
    $('#imgcontent'+imgid).hide();
    $('#uploadwrap' + imgid).show(); 
    $('#file_' + imgid).removeClass('fileupload_Valid');
    $('#file_' + imgid).addClass('fileupload_start');
}
/*
$('#uploadwrap'+imgid).bind('dragover', function () {
    $('#uploadwrap'+imgid).addClass('image-dropping');
});
$('#uploadwrap'+imgid).bind('dragleave', function () {
    $('#uploadwrap'+imgid).removeClass('image-dropping');
});
*/
function View_Question(qno) {
    var qn = parseInt(qno);
    var x = 0;
    if (qn == 1) {
        $('#bigintest').hide();
        $('#question' + qn).show(); 
    }
    if (document.getElementById("opt2").checked == true) {
        document.getElementById("totalq1").innerHTML = "6";
        document.getElementById("totalq2").innerHTML = "6";
        document.getElementById("totalq3").innerHTML = "6";
        document.getElementById("totalq4").innerHTML = "6";
        document.getElementById("totalq5").innerHTML = "6";
        document.getElementById("totalq6").innerHTML = "6";
        if (qn == 7) {
            $('#selfassessment').hide();
            $('#selfassessment').addClass('question_hide');
        }
        for (x = 1; x < 8; x++) {
            if (qn == x) {
                $('#question' + x).removeClass('question_hide');
                $('#question' + x).show();
            }
            else {
                $('#question' + x).addClass('question_hide');
                $('#question' + x).hide();
            }
        }
       
    }
    else {
        document.getElementById("totalq1").innerHTML = "5";
        document.getElementById("totalq2").innerHTML = "5";
        document.getElementById("totalq3").innerHTML = "5";
        document.getElementById("totalq4").innerHTML = "5";
        document.getElementById("totalq5").innerHTML = "5"; 
        if (qn == 6) {
            $('#selfassessment').hide();
            $('#selfassessment').addClass('question_hide');
            $('#question5').addClass('question_hide');
            $('#question5').hide();
            $('#question6').addClass('question_hide');
            $('#question6').hide();
            $('#question7').removeClass('question_hide');
            $('#question7').show();
        }
        else {
            for (x = 1; x < 8; x++) {
                if (qn == x) {
                    $('#question' + x).removeClass('question_hide');
                    $('#question' + x).show();
                }
                else {
                    $('#question' + x).addClass('question_hide');
                    $('#question' + x).hide();
                }
            }
        }
    }
}
 


function add_newfield() {
      
    if ($('#operating-address-same').is(":checked")) {
        
        $("#company-operating-address").addClass('hide');
        $("#company-operating-postcode").addClass('hide');
        } else {
        $("#company-operating-address").removeClass('hide');
        $("#company-operating-postcode").removeClass('hide');
        }
    } 
function add_business() {

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
function onlyMobile(elementid) {

    if (/\(?([0-9]{3})\)?([ .-]?)([0-9]{3})\2([0-9]{6})/.test(document.getElementById(elementid).value)) {
        document.getElementById(elementid).style = "border-bottom: 1px solid #e5e5e5; background: url(../images/tick.jpg) !important;background-size: 20px !important;background-repeat: no-repeat !important;background-position: right !important;";
        document.getElementById(elementid).addClass('valid_email');
        document.getElementById(elementid).removeClass('invalid_email');
        return true;
    }
    else {
        document.getElementById(elementid).style = "border-bottom: 1px solid #ff8282; background: url(../images/wrong.jpg) !important;background-size: 20px !important;background-repeat: no-repeat !important;background-position: right !important;";
        document.getElementById(elementid).removeClass('hasInput used');
        document.getElementById(elementid).removeClass('valid_email');
        document.getElementById(elementid).addClass('invalid_email');

        return false;
    }
}
function check_document() {
    var chkdoc3=1;
      if ($('#userInformation_info_corporate_investor').is(":checked")) {
      //   alert(document.getElementById("hdndoc3").value);
          if (document.getElementById("hdndoc1").value == "0" || document.getElementById("hdndoc1").value == "") {
              
              chkdoc3=0;
              document.getElementById("file_1").addClass('fileupload_Invalid');
              document.getElementById("file_1").removeClass('fileupload_Valid'); 
          } 
          if (document.getElementById("hdndoc2").value == "0" || document.getElementById("hdndoc2").value == "") { 
              chkdoc3=0;
              document.getElementById("file_2").addClass('fileupload_Invalid');
              document.getElementById("file_2").removeClass('fileupload_Valid'); 
          } 
         
          if (document.getElementById("hdndoc3").value == "0"  || document.getElementById("hdndoc3").value == "") { 
              chkdoc3=0;
              //document.getElementById("file_3").addClass('fileupload_Invalid');
            //  document.getElementById("file_3").removeClass('fileupload_Valid');
            //  alert("end"); 
          } 
          
         // alert(chkdoc3);
          if(chkdoc3==0)
          {
            //  alert("5");
              return false;
          }
          
      }
      else {
          
          var chksptdoc =1;
          if (document.getElementById("hdndoc1").value == "") {
             
              chksptdoc = 0;
          }
          
          if (document.getElementById("hdndoc2").value == "") {
              
              chksptdoc = 0;
          }
          
          if (chksptdoc == 0) { 
              return false;
          }
          
      }
       
  }
  
function chkddlvali(elementid1) {
  
    var x = document.getElementById(elementid1).value;
  
    if (document.getElementById(elementid1).value == "Select an option") {
        $("#" + elementid1).addClass('invalid_email');
        $("#" + elementid1).removeClass('valid_email');
         
    }
    else {
        $("#" + elementid1).addClass('valid_email');
        $("#" + elementid1).removeClass('invalid_email'); 
    }
}
function boxvali(elementid1) {

    var x = document.getElementById(elementid1).value;

    if (document.getElementById(elementid1).value == "") {
        $("#" + elementid1).addClass('box_invalid');
        $("#" + elementid1).removeClass('box_invalid');

    }
    else {
        $("#" + elementid1).addClass('box_invalid');
        $("#" + elementid1).removeClass('box_invalid');
    }
}
function chkdobdd(elementid1) {
 
    if (document.getElementById("userInformation_birthDate_day").value == "") {
        $("#userInformation_birthDate_day").addClass('invalid_email');
        $("#userInformation_birthDate_day").removeClass('valid_email');

    }
    else {
        $("#userInformation_birthDate_day").addClass('valid_email');
        $("#userInformation_birthDate_day").removeClass('invalid_email');
    }
}
function chkdobmm(elementid1) {

    if (document.getElementById("userInformation_birthDate_month").value == "") {
        $("#userInformation_birthDate_month").addClass('invalid_email');
        $("#userInformation_birthDate_month").removeClass('valid_email');

    }
    else {
        $("#userInformation_birthDate_month").addClass('valid_email');
        $("#userInformation_birthDate_month").removeClass('invalid_email');
    }
}
function chkdobyy(elementid1) {

    if (document.getElementById("userInformation_birthDate_year").value == "") {
        $("#userInformation_birthDate_year").addClass('invalid_email');
        $("#userInformation_birthDate_year").removeClass('valid_email');

    }
    else {
        $("#userInformation_birthDate_year").addClass('valid_email');
        $("#userInformation_birthDate_year").removeClass('invalid_email');
    }
}
