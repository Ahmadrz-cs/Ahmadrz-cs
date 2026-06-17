//detect mobile platform
if (navigator.userAgent.match(/(iPod|iPhone|iPad)/)) {
    $("body").addClass("ios-device");
}
if (navigator.userAgent.match(/Android/i)) {
    $("body").addClass("android-device");
}

//detect desktop platform
if (navigator.appVersion.indexOf("Win") != -1) {
    $('body').addClass("win-os");
}
if (navigator.appVersion.indexOf("Mac") != -1) {
    $('body').addClass("mac-os");
}

//detect IE 10 and above 11
if (navigator.userAgent.indexOf('MSIE') !== -1 || navigator.appVersion.indexOf('Trident/') > 0) {
    $("html").addClass("ie10");
}
//for IE 8 & 9
//enable the below script for  placeholder to inputs in IE 9 and 8
/*
 if( $("html").hasClass("ie9") || $("html").hasClass("ie8")){
 $('.formidable .input input,.formidable .input textarea').each(function(){

 $(this).focusin(function(){
 $(this).parent().prev(".label").hide();
 });
 $(this).focusout(function(){
 if($(this).val()==""){
 $(this).parent().prev(".label").show();
 }
 });
 });
 }*/

//Specifically for IE8 (for replacing svg with png images)


setTimeout(function () {
    if ($("html").hasClass("ie10")) {
        var imgPath = window.location.protocol + "//" + window.location.host + "/bundles/app/images/";
        //var imgPath = "../bundles/app/images/";
        $("header .logo a img,.loading-screen img").attr("src", imgPath + "logo-ie.png");
        $(".h-1 img").attr("src", imgPath + "icon-4-ie.png");
        $(".h-2 img").attr("src", imgPath + "icon-5-ie.png");
        $(".h-3 img").attr("src", imgPath + "icon-6-ie.png");
        $(".h-4 img").attr("src", imgPath + "icon-7-ie.png");
        $(".h-16 img").attr("src", imgPath + "icon-16-ie.png");
        $(".h-13 img").attr("src", imgPath + "icon-13-ie.png");
        $(".h-14 img").attr("src", imgPath + "asset-ie.png");
        $(".dots img").attr("src", imgPath + "dots-ie.png");
        $(".h-15 img").attr("src", imgPath + "icon-15-ie.png");
        $(".h-16 img").attr("src", imgPath + "rental-ie.png");
        $(".p-h-1 img").attr("src", imgPath + "icon-9-ie.png");
        $(".p-h-2 img").attr("src", imgPath + "icon-10-ie.png");
        $(".p-h-3 img").attr("src", imgPath + "icon-11-ie.png");
        $(".p-h-4 img").attr("src", imgPath + "icon-12-ie.png");
        $(".p-h-4 img").attr("src", imgPath + "icon-12-ie.png");
        $(".story-1 img,.s-1 img").attr("src", imgPath + "s-icon-1-ie.png");
        $(".story-2 img,.s-2 img").attr("src", imgPath + "s-icon-2-ie.png");
        $(".story-3 img,.s-3 img").attr("src", imgPath + "s-icon-3-ie.png");
        $(".story-4 img,.s-4 img").attr("src", imgPath + "s-icon-4-ie.png");
        $(".story-5 img,.s-5 img").attr("src", imgPath + "s-icon-5-ie.png");
        $(".story-6 img,.s-6 img").attr("src", imgPath + "s-icon-6-ie.png");
        $(".story-7 img,.s-7 img").attr("src", imgPath + "s-icon-7-ie.png");
        $(".story-8 img,.s-8 img").attr("src", imgPath + "s-icon-8-ie.png");
        $(".story-9 img,.s-9 img").attr("src", imgPath + "s-icon-9-ie.png");
        $(".cert-icon img").attr("src", imgPath + "certificate-ie.png");
        $(".curve-1 img").attr("src", imgPath + "curve-1-ie.png");
        $(".curve-2 img").attr("src", imgPath + "curve-2-ie.png");
        $(".path img").attr("src", imgPath + "path-ie.png");
        $(".footer-bottom  .logo img").attr("src", imgPath + "logo-1-ie.png");
    }
}, 300);


var screenWidth, screenHeight;

function screenResize() {
    screenWidth = $(window).width();
    screenHeight = $(window).height();

    $(".v-wrap video").height(screenHeight);
    $(".v-wrap video").width(screenWidth);


    //video resize
    videocontainerHeight = 750;
    //video screen sizing
    var hdmoviewidth = screenWidth;
    var heightswitch = (videocontainerHeight * 1.77);
    var hdmovieml = (heightswitch - screenWidth) / -2;

    if (screenWidth < heightswitch) {
        $("iframe[src*='//player.vimeo.com']").height(videocontainerHeight);
        $("iframe[src*='//player.vimeo.com']").width(heightswitch);
        $("iframe[src*='//player.vimeo.com']").css({'margin-top': "-5px"});
        $("iframe[src*='//player.vimeo.com']").css({'margin-left': hdmovieml});
    } else {
        var hdmovieheight = (screenWidth) / 1.77;
        $("iframe[src*='//player.vimeo.com']").height(hdmovieheight);
        $("iframe[src*='//player.vimeo.com']").width(hdmoviewidth);
        $("iframe[src*='//player.vimeo.com']").css({'margin-left': "0px", "margin-top": "-8px"});
    }
    if ($("iframe[src*='//player.vimeo.com']").height() > videocontainerHeight) {
        var vertVideoCenter = ($("iframe[src*='//player.vimeo.com']").height() - videocontainerHeight) / 2;
        //$("iframe").css({'margin-top':'0px'});
        $("iframe[src*='//player.vimeo.com']").css({'margin-top': '-' + vertVideoCenter + 'px'});
    }
}

//mobile menu
$(document).on("click", ".mobile-menu", function () {
    if ($(this).hasClass("active")) {
        $(this).removeClass("active");
    } else {
        $(this).addClass("active");
    }
});

/*
 $(document).on({
 mouseenter: function () {
 var yIndex = $(this).index();
 $(".p-desc").slideUp("slow");
 setTimeout(function(){
 $(".y-desc").slideDown("slow");
 $(".y-desc .y-content").eq(yIndex).slideDown("slow").siblings().slideUp("slow");
 },250);
 },
 mouseleave: function () {
 setTimeout(function(){
 $(".y-desc .y-content").slideUp("slow");
 $(".y-desc").slideUp("slow");
 },250);
 }
 }, '.y-process .col-sm-3');

 $(document).on({
 mouseenter: function () {
 var pIndex = $(this).index();
 $(".y-desc").slideUp("slow");
 setTimeout(function(){
 $(".p-desc").slideDown("slow");
 $(".p-desc .p-content").eq(pIndex).slideDown("slow").siblings().slideUp("slow");
 },250);
 },
 mouseleave: function () {
 setTimeout(function(){
 $(".p-desc .p-content").slideUp("slow");
 $(".p-desc").slideUp("slow");
 },250);
 }
 }, '.p-process .col-sm-2');
 */

/*$(document).on("click",".p-process .col-sm-2",function(){
 if ($(this).hasClass("active")) {
 $(".y-process .col-sm-3").removeClass("active");
 $(this).removeClass("active");
 $(".y-desc").slideUp("slow");
 $(".p-desc").slideUp("slow");
 $(".p-desc .p-content").slideUp("slow");
 $(this).find(".circle-b").fadeOut("slow");
 } else {
 var pIndex = $(this).index();
 $(".y-process .col-sm-3").removeClass("active");
 $(this).addClass("active").siblings().removeClass("active");
 $(".y-desc").slideUp("slow");
 $(this).find(".circle-b").fadeIn("slow");
 setTimeout(function(){
 $(".p-desc").slideDown("slow");
 $(".p-desc .p-content").eq(pIndex).slideDown("slow").siblings().slideUp("slow");
 var target = $(".full-info-bar");
 $('html,body').animate({
 scrollTop: target.offset().top - 250
 }, 1000);
 return false;
 },250);
 }
 });*/
/*

 $(document).on("click",".y-process .col-sm-3",function(){
 if ($(this).hasClass("active")) {
 $(".p-process .col-sm-2").removeClass("active");
 $(this).removeClass("active");
 $(".p-desc").slideUp("slow");
 $(".y-desc").slideUp("slow");
 $(".y-desc .y-content").slideUp("slow");
 $(this).find(".circle-b").fadeOut("slow");
 } else {
 var yIndex = $(this).index();
 $(".p-process .col-sm-2").removeClass("active");
 $(this).addClass("active").siblings().removeClass("active");
 $(".p-desc").slideUp("slow");
 $(this).find(".circle-b").fadeIn("slow");
 setTimeout(function(){
 $(".y-desc").slideDown("slow");
 $(".y-desc .y-content").eq(yIndex).slideDown("slow").siblings().slideUp("slow");
 var target = $(".full-info-bar");
 $('html,body').animate({
 scrollTop: target.offset().top - 250
 }, 1000);
 return false;
 },250);
 }
 });
 */




$(document).on("click", ".top-portfolio .p-image,.norm-portfolio .p-image", function () {
	$(".line-graph-box").show();
    var target = $(".line-graph-box");
    $('html,body').animate({
        scrollTop: target.offset().top
    }, 1000);
    var sector = $(this).attr("data-value");
    $(".yield-info-box").each(function () {
        var vSector = $(this).attr("data-sector");
        if (vSector.indexOf(sector) != -1) {
            $(this).slideDown("slow");
        } else {
            $(this).slideUp("slow");
        }
    });


    if ($(this).hasClass("active")) {
        //$(this).addClass("active");
        //$(this).parent(".property-item").removeClass("active");
        $(this).parents(".property-item-box").addClass("active").siblings().removeClass("active");
    } else {
        $(this).addClass("active");
        $(this).parents(".property-item-box").addClass("active").siblings().removeClass("active");
    }

    $('#line-example').html('');

    var isVIP = $(this).attr("data-vip");
    var dataGraph = $(this).attr("data-graph");    
    var dataChart = [];
    var arr = $.parseJSON(dataGraph);
    if (arr.length > 0) {
        $.each(arr, function (i, v) {
            var item = {y: v['y'], a: v['a'], b: v['b']};
            dataChart.push(item);
        });


        if (isVIP) {
            Morris.Line({
                element: 'line-example',
                data: dataChart,
                xkey: 'y',
                lineColors: ['#C5C5C5', '#C5C5C5'],
                pointFillColors: ['#b0a374', '#000'],
                pointStrokeColors: ['#fff', '#fff'],
                gridTextColor: ['#fff'],
                eventLineColors: ['#fff'],
                ykeys: ['a', 'b'],
                labels: ['Series A', 'Series B']
            });
        } else {
            Morris.Line({
                element: 'line-example',
                data: dataChart,
                xkey: 'y',
                lineColors: ['#fff', '#fff'],
                pointFillColors: ['#fff', '#fff'],
                pointStrokeColors: ['#fff', '#fff'],
                gridTextColor: ['#fff'],
                eventLineColors: ['#fff'],
                ykeys: ['a', 'b'],
                labels: ['Series A', 'Series B']
            });
        }
    }    
});


$(document).on("click", ".add-more a", function () {
    var clone = $('#g-input').clone().find("input").val("").end();
    clone.attr('id', '').show().append($('#textI2').val());
    $('.group-inputs').append(clone)
});

$(document).on("click", ".delete", function () {
    $(this).parent().remove();
});
$(document).ready(function () {
    $('.property-item-box').hover(function () {
            $(this).find('.progress-box span').addClass('flip animated');
        },
        function () {
            $(this).find('.progress-box span').removeClass('flip animated');
        });
});


$(document).ready(function () {
    /* loading screen */
    $('.logo-middle').fadeIn(500);
    $('.loader').delay(1500).fadeOut(500);

    //call resize function
    screenResize();
    $(window).resize(screenResize);

    /* scroll events*/
    var menuSection = $('.submenu-block').offset().top;
    $(window).scroll(function () {
        //console.log($(document).scrollTop());


        if ($(".h-banner").length) {
            /*$(".banner-block").css({
             "height": 700 - $(window).scrollTop() - 20
             });*/


            if ($(window).scrollTop() < screenHeight) {
                $(".banner-caption h1").css({
                    "top": 180 + $(window).scrollTop(),
                    "opacity": 1 - $(window).scrollTop() * 1 / 200
                });
            }

            if ($(window).scrollTop() > 300) {
                if ($(window).scrollTop() < screenHeight) {
                    $(".banner-bottom ul,.banner-bottom .go-down").css({
                        "opacity": 1 - $(window).scrollTop() / 300
                    });
                }
            } else {
                $(".banner-bottom ul,.banner-bottom .go-down").css({
                    "opacity": 1
                });
            }
        }

        $(".cleaf,.cleaf-wrap .c-btn").toggleClass('anim', $(window).scrollTop() > 300);


        if ($(window).width() > 992) {
            $(".submenu-block").toggleClass('fix-it', $(window).scrollTop() > menuSection);
            $("ul.list-inline.header-btn-block li.hide-btn").toggleClass('newhidden', $(window).scrollTop() > menuSection);
            $(".wrapper").toggleClass('fix', $(window).scrollTop() > menuSection);

            $("header").toggleClass('sticky', $(window).scrollTop() > 2);
        }
        $(".sticky-footer").toggleClass('sticky', $(window).scrollTop() > 100);
        // $(".content-slider").toggleClass('addspace', $(window).scrollTop() > menuSection);

    });
    if (!$("html").hasClass("ie10")) {
        if ($(window).width() > 1200) {
            setTimeout(function () {
                $('.f-wrap').parallax({
                    speed: 0.5
                });
            }, 1000);
            $('.process-box').parallax({
                speed: 0.8
            });
            $('.banner-block').parallax({
                speed: 0.2
            });

        }
    }



    if (window.location.href.indexOf("#sign-up") > -1) {
        $('#modal-signup').modal('show');
    }


    $(".progress .progress-bar").each(function () {
        $(this)
            .data("origWidth", $(this).width())
            .width(0)
            .animate({
                width: $(this).data("origWidth")
            }, 3000);
    });

    $('input, textarea').placeholder();


    $('#slides').superslides({
        animation: 'fade',
        play: 10000,
        inherit_height_from: $(".banner-block")
    });
    if ($(".counter").length) {
        $('.counter').counterUp({
            delay: 10,
            time: 1000
        });
    }

    $(".w-video").click(function () {
        $(".v-wrap,.close-btn").fadeIn("slow");
    });

    $('#investment-btn').on('click', function (e) {
       $(".cal-result").addClass("act");
    });


    $("#modal-signup .e-div").hover(function(){
        if($("#sign_up_type_confirm_terms").is(':disabled')){
            $("#activateConfirm").addClass("active");
        }else{
            $("#activateConfirm").removeClass("active");
            $('#modal-signup .checkbox label').tooltipster('destroy');
            $('#modal-signup .checkbox label').removeAttr( "title" )
        }
    }, function(){
        $("#activateConfirm").removeClass("active");
    });


    $(".close-btn").click(function () {
        $(".v-wrap,.close-btn").fadeOut("slow");
        $("#bgvid").get(0).pause();
    });

    $(".invest-btn").click(function () {
        var starget = $(".property-info");
        $('html,body').animate({
            scrollTop: starget.offset().top - 30
        }, 1000);
        $("#invest_click").addClass("active");
    });


    if ($(".h-banner").length) {
        $(document).on('init.slides', function () {
            var vid = document.getElementById("vid");
            //vid.play();
        });
    }

    $(".cleaf-wrap .c-btn").click(function () {
        $(".cleaf").fadeOut("slow");
        $(".cleaf-wrap .c-btn").fadeOut("slow");
    });


    $(".head h3").click(function () {
        if (!$(this).parents(".accordion-content").hasClass("active")) {
            $(this).parents(".accordion-content").addClass("active").siblings().removeClass("active");
            $(".accordion-content .accordion-details").slideUp("slow");
            $(".accordion-content.active .accordion-details").slideDown("slow");
            var target = $(this);
            $('html,body').animate({
                scrollTop: target.offset().top - 200
            }, 1000);
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


    if (screenWidth <= 1200) {
        $(".submenu-block li.drop").click(function () {
            $(this).removeClass("not-clicked");
            if (!$(this).hasClass("clicked")) {
                $(this).addClass("clicked").siblings().removeClass("clicked");
                return false;
            }
        });
        $(".nav .header-btn-block li").click(function () {
            if (!$(this).hasClass("selected")) {
                $(this).addClass("selected").siblings().removeClass("selected");
            }
            else {
                $(this).removeClass("selected");
            }
        });
    }


    $(".menu-btn").click(function () {
        $(".submenu-block ").fadeIn("slow");
    });

    $(".submenu-block .fa-close").click(function () {
        $(".submenu-block ").fadeOut("slow");
    });


    /*if (screenWidth > 767) {
     $(".chosen,select").chosen({disable_search_threshold: 50});
     }*/
    if (screenWidth > 1200) {
    $("#form_sign_up select").chosen({disable_search_threshold: 50});
    }

    if (screenWidth > 1024) {
        if ($(".feature-slider").length) {
            $(".feature-slider select").chosen({disable_search_threshold: 50});
        }
        if ($(".property-filter").length) {
            $(".property-filter select").chosen({disable_search_threshold: 50});
        }
    }


    //instafeed
    $(".instagram-block").append('<div id="instafeed" class="responsive"></div>');


    $(".go-down,.started").click(function () {
        var target = $(".submenu-block,.privacy-block");
        $('html,body').animate({
            scrollTop: target.offset().top - 390
        }, 1000);
        return false;
    });

    $(".e-tab").click(function () {
        var target = $(".ethical-tab-content");
        $('html,body').animate({
            scrollTop: target.offset().top - 390
        }, 1000);
    });


    $(".graph-board a").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).parent(".graph-board").find(".graph-wrap").slideUp("slow");
            $(this).html("Show Graph");
        } else {
            $(this).html("Hide Graph");
            $(this).addClass("active");
            $(this).parent(".graph-board").find(".graph-wrap").slideDown("slow");
        }
    });

    $(".property-item .see-more").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).parents(".info-detail").find(".det-slide").slideUp("slow");
            $(this).html("See More");
        } else {
            $(this).html("See Less");
            $(this).addClass("active");
            $(this).parents(".info-detail").find(".det-slide").slideDown("slow");
        }
        return false;
    });


    $(".top-portfolio .message-board .info-title h3,.norm-portfolio .message-board .info-title h3").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).parents(".message-board").find("ul").slideUp("slow");
        } else {
            $(this).addClass("active");
            $(this).parents(".message-board").find("ul").slideDown("slow");
        }
        return false;
    });


    $(".signup").click(function () {
        var target = $(".form-box");
        $('html,body').animate({
            scrollTop: target.offset().top - 80
        }, 1000);
        return false;
    });

    $('.fancybox').fancybox({
        padding: 0,
        type: 'image',
        helpers: {
            overlay: {
                locked: false,
                css: {
                    'background': 'rgba(0,0,0,0.7)'
                }
            }

        }
    });
    if ($(window).width() > 1200) {
        $('.fancybox-media').fancybox({
            width: screenWidth - 100,
            height: screenHeight - 100,
            padding: 0,
            margin: 0,
            wrapCSS: 'video-popup',
            helpers: {
                media: {},
                overlay: {
                    locked: false,
                    css: {
                        'background': 'rgba(0,0,0,0.7)'
                    }
                }
            }
        });
    } else {
        $('.fancybox-media').fancybox({
            width: screenWidth,
            height: screenHeight,
            padding: 0,
            margin: 0,
            wrapCSS: 'video-popup',
            helpers: {
                media: {},
                overlay: {
                    locked: false,
                    css: {
                        'background': 'rgba(0,0,0,0.7)'
                    }
                }
            }
        });
    }



    // // start of changes by Ali | 12/08/2017
    // $("#calc-slider1").noUiSlider({
    //     start: 1000,
    //     step: 1000,
    //     decimals: 0,
    //     range: {
    //         'min': 0,
    //         'max': 100000
    //     },
    //     format: wNumb({
    //         decimals: false
    //     })
    // });

    // $("#calc-slider1").Link('lower').to('-inline-<div class="tooltip"></div>', function (value) {

    //     // The tooltip HTML is 'this', so additional
    //     // markup can be inserted here.
    //     $(this).html('<span>' + value + '</span> <input type="hidden" id="svalue" name="investment-input"  value="' + value + '">');
    // });
    // $("#calc-slider1").Link('lower').to($('#input-calc1'));

    // $("#calc-slider2").noUiSlider({
    //     start: 1000,
    //     step: 1000,
    //     decimals: 0,
    //     range: {
    //         'min': 0,
    //         'max': 100000
    //     },
    //     format: wNumb({
    //         decimals: false
    //     })
    // });

    // $("#calc-slider2").Link('lower').to('-inline-<div class="tooltip"></div>', function (value) {

    //     // The tooltip HTML is 'this', so additional
    //     // markup can be inserted here.
    //     $(this).html('<span>' + value + '</span> <input type="hidden" id="svalue" name="investment-input"  value="' + value + '">');
    // });
    // $("#calc-slider2").Link('lower').to($('#input-calc2'));

    // $("#calc-slider3").noUiSlider({
    //     start: 1000,
    //     step: 1000,
    //     decimals: 0,
    //     range: {
    //         'min': 0,
    //         'max': 100000
    //     },
    //     format: wNumb({
    //         decimals: false
    //     })
    // });

    // $("#calc-slider3").Link('lower').to('-inline-<div class="tooltip"></div>', function (value) {

    //     // The tooltip HTML is 'this', so additional
    //     // markup can be inserted here.
    //     $(this).html('<span>' + value + '</span> <input type="hidden" id="svalue" name="investment-input"  value="' + value + '">');
    // });
    // $("#calc-slider3").Link('lower').to($('#input-calc3'));

    // end of changes by Ali


    /*$('.ethical-tab a').hover(function() {
     $(this).tab('show');
     });*/


    $(".e-tab").click(function () {
        if (!$(this).hasClass("active")) {
            $(".e-tab").removeClass("active");
            $(this).addClass("active");
        }
    });

    /*$('input').iCheck({
     checkboxClass: 'icheckbox_flat',
     radioClass: 'iradio_flat',
     increaseArea: '25%' // optional
     });*/

    $('.form-box.new input').iCheck({
     checkboxClass: 'icheckbox_flat',
     radioClass: 'iradio_flat',
     increaseArea: '25%' // optional
     });


    $(".pay-select").chosen().change(function(event) {
        if($(event.target).val() == 'CB_VISA_MASTERCARD') {
            $('.by-bank').slideUp("slow");
            $('.by-visa').slideDown("slow");
        }else if($(event.target).val() == 'BANK_WIRE') {
            $('.by-visa').slideUp("slow");
            $('.by-bank').slideDown("slow");
        }
    });




    $('#upload1').on('change', function () {
        var ch = this.files[0].name;
        $("#document").attr("placeholder", ch);
    })

    $('#upload2').on('change', function () {
        var ch = this.files[0].name;
        $("#plan").attr("placeholder", ch);
    })

    /*$('.s3_upload').on('change',function(){
     var ch = this.files[0].name;
     $(this).parents(".file-container").find(".form-control").attr("placeholder", ch);
     })*/

    $('.stooltip').tooltipster({
        position: 'top'
    });
    $('.ptooltip').tooltipster({
        position: 'bottom'
    });
    $('.rtooltip').tooltipster({
        position: 'right'
    });

    $('.e-tab a').hover(function () {
        $(this).tab('show');
        /*var target = $(".ethical-tab-content");
         $('html,body').animate({
         scrollTop: target.offset().top - 390
         }, 1000);*/
    });


    $('.cleaf').click(function () {
        $this = $(this);
        $('.cleaf-wrap .c-btn.anim').removeClass("anim");
        $this.removeClass("anim");
        $('.cleaf-wrap .c-btn').fadeOut("slow");
        setTimeout(function () {
            $this.fadeOut("slow");
        }, 600);
        $zopim.livechat.window.show();
        return false;
    });


    $(".meter > span").each(function () {
        $(this)
            .data("origWidth", $(this).width())
            .width(0)
            .animate({
                width: $(this).data("origWidth")
            }, 1200);
    });


    $(".portfolio.property-info .def-btn,.more-btn").click(function () {
        if ($(this).hasClass("active")) {
            $(this).removeClass("active");
            $(this).parents().find(".more").slideUp("slow");
            $(this).html("Read More");
        } else {
            $(this).html("Read Less");
            $(this).addClass("active");
            $(this).parents().find(".more").slideDown("slow");
        }
    });
    if ($(".blog-listing").length) {
        var $grid = $('.grid').imagesLoaded(function () {
            $grid.masonry({
                itemSelector: '.grid-item',
                gutter: 10,
                percentPosition: true,
                columnWidth: '.grid-sizer'
            });
        });
    }

    $('.slide').slick({
        dots: false,
        infinite: true,
        speed: 400,
        adaptiveHeight: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

   $('.videoslide').slick({
        dots: false,
        infinite: false,
        speed: 300,
        adaptiveHeight: true,
        slidesToShow: 3,
        slidesToScroll: 3,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
		    infinite: true,
                    slidesToScroll: 1,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

    $('.story-slide').slick({
        dots: false,
        infinite: true,
        vertical: true,
        speed: 400,
        adaptiveHeight: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: true,
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

    $('.property-slide').slick({
        dots: false,
        infinite: true,
        speed: 400,
        adaptiveHeight: true,
        slidesToShow: 2,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 1,
                    infinite: true,
                }
            },
            {
                breakpoint: 992,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 660,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

    $('.feature').slick({
        dots: false,
        infinite: true,
        speed: 400,
        /*fade: true,*/
        adaptiveHeight: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

// Start of changes made by Ali | 10/08/2017
    $('.socialFeature').slick({
        dots: false,
        infinite: true,
        speed: 400,
        fade: true,
        adaptiveHeight: true,
        variableWidth: false,
        slidesToShow: 1,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    infinite: true
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });
    // End of changes made by Ali

    $('.gallery').slick({
        dots: false,
        infinite: true,
        speed: 400,
        adaptiveHeight: true,
        slidesToShow: 4,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true,
                }
            },
            {
                breakpoint: 640,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

    $('.client').slick({
        dots: false,
        infinite: true,
        speed: 400,
        adaptiveHeight: true,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1024,
                settings: {
                    slidesToShow: 3,
                    slidesToScroll: 3,
                    infinite: true,
                }
            },
            {
                breakpoint: 640,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 480,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
        ]
    });

    //block position change on hover - home page
    $(".how-work-box .item-col").hover(function () {
        var frameLeft = $(this).position().left;
        var fcontent = frameLeft + 70
        var pointIndex = $(this).index();
        $(".how-work-box .frame-wrap").css("left", frameLeft + "px");
        $(this).find(".c-content").css("left", "-" + fcontent + "px");
        $(this).addClass("active").siblings().removeClass("active");
        $(".how-content").eq(pointIndex - 1).fadeIn("slow").siblings().fadeOut("slow");
    }, function () {
        /*var originalLeft = $(".how-work-box .item-col.active").position().left;
         $(".how-work-box .frame").css("left",originalLeft+"px");*/
    });
    $(".how-work-box .item-col").click(function () {
        $(this).addClass("active").siblings().removeClass("active");
    });

});

$(window).load(function () {


    //Addthis icon change
    setTimeout(function () {
        $("#at4-share").removeClass("addthis_32x32_style").addClass("addthis_20x20_style");
        $(".at4-share-outer").show();
        $("#vid").show();
        $(".h-banner #slides").show();
    }, 100);

    $(".zopim").hide();


    if ($("#instafeed").length) {
        var feed = new Instafeed({
            get: 'user',
            userId: '1225726900',
            clientId: '2a01f5e11cb24cebb85a760a92a52451',
            resolution: 'low_resolution',
            sortBy: 'most-recent',
            template: '<li><a href="{{link}}" style="background-image:url({{image}});" target="_blank"><img class="hidden" src="{{image}}" alt="pinza" /><span class="fa fa-instagram"></span><div class="overlay"><p>{{caption}}</p></div></a></li>',
            limit: 4,
            after: function (data) {
                $('.responsive').slick({
                    dots: false,
                    infinite: true,
                    autoplay: false,
                    speed: 300,
                    autoplaySpeed: 6000,
                    slidesToShow: 4,
                    centerMode: false,
                    slidesToScroll: 1,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: {
                                slidesToShow: 3,
                                slidesToScroll: 3,
                            }
                        },
                        {
                            breakpoint: 640,
                            settings: {
                                slidesToShow: 2,
                                slidesToScroll: 2
                            }
                        },
                        {
                            breakpoint: 480,
                            settings: {
                                slidesToShow: 1,
                                slidesToScroll: 1
                            }
                        }
                        // You can unslick at a given breakpoint now by adding:
                        // settings: "unslick"
                        // instead of a settings object
                    ]
                });
                /* Apply dynamic height to make instablock square */
                $("#instafeed .slick-slide").each(function () {
                    $(this).css("height", $(this).width());
                });
            }
        });
        feed.run();
    }
});
