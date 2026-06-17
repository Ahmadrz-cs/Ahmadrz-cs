
    $(".head h3").click(function () {
        if (!$(this).parents(".accordion-content").hasClass("active")) {
            $(this).parents(".accordion-content").addClass("active").siblings().removeClass("active");
            $(".accordion-content .accordion-details").slideUp("slow");
            $(".accordion-content.active .accordion-details").slideDown("slow");
            var target = $(this);
            // $('html,body').animate({
            //     scrollTop: target.offset().top - 200
            // }, 1000);
            return false;
        } else {
            $(this).parents(".accordion-content").find(".accordion-details").slideUp("slow");
            $(this).parents(".accordion-content").removeClass("active");
        }
    });

    //detect safari
    var is_safari = navigator.userAgent.indexOf("Safari") > -1;
    if (navigator.userAgent.indexOf('Safari') != -1 && navigator.userAgent.indexOf('Chrome') == -1) {
        $('body').addClass("safari");
    }


    $(".accordion-content .pd-terms").click(function () {
        $(".accordion-content").removeClass("active");
        $(".accordion-content .accordion-details").slideUp("slow");
        $(".accordion-content.p-terms .accordion-details").slideDown("slow");
        $(".accordion-content.p-terms").addClass("active");
    });


    $(".accordion-content .pd-website").click(function () {
        $(".accordion-content").removeClass("active");
        $(".accordion-content .accordion-details").slideUp("slow");
        $(".accordion-content.p-website .accordion-details").slideDown("slow");
        $(".accordion-content.p-website").addClass("active");
    });


    $(".accordion-content .pd-cookie").click(function () {
        $(".accordion-content").removeClass("active");
        $(".accordion-content .accordion-details").slideUp("slow");
        $(".accordion-content.p-cookie .accordion-details").slideDown("slow");
        $(".accordion-content.p-cookie").addClass("active");
    });

    $(".accordion-content .pd-privacy").click(function () {
        $(".accordion-content").removeClass("active");
        $(".accordion-content .accordion-details").slideUp("slow");
        $(".accordion-content.p-privacy .accordion-details").slideDown("slow");
        $(".accordion-content.p-privacy").addClass("active");
    });

