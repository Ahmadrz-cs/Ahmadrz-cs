$(document).ready(function() {

    $(".js-range-slider").ionRangeSlider({
        type: "single",
        skin: "round",
        min: 0,
        max: 100000,
        from_min: 100,
        step: 500,
        prettify: function(n) {
            return (n.toFixed(0)).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
        },
        from: $('.sliderData').val(),
        // decorate_both:false,
        prefix: "£",
        // format: {
        //     to: function (value) {
        //         return Math.round(value);
        //     },
        //     from: function (value) {
        //         return Math.round(value);
        //     }
        // },
        tooltips: [true],
        // range: {
        //     'min': [100, 400],
        //     '1%': [500, 500],
        //     'max': [100000]
        // },
        onChange: function(data) {
            $('.sliderData').val(data.from);
        }
    });

    // create card
    let slide={};
     slide.count = 1;
     slide.maxCount=parseInt($('#offerings_count').val());
    let sliderArrayLoad = [0];
    let ws = $(".variable").slick({
        dots: false,
        infinite: false,
        variableWidth: true,
        slidesToShow: 2,
        rtl: true,
        responsive: [{
                breakpoint: 992,
                settings: {
                    arrows: true,
                    speed: 300,
                    slidesToShow: 1,
                    variableWidth: true,
                    centerMode: true,
                    centerPadding:'20px'
                }
            },

        ]
    });
    
    // console.log("id req = "+slide.count);
    //     console.log(sliderArrayLoad);
    ws.slick("slickSetOption", "accessibility", false);
    ws.slick("slickSetOption", "draggable", false);
    ws.slick("slickSetOption", "swipe", false);
    ws.slick("slickSetOption", "touchMove", false);
    let cardFlag = 'def';
    ws.on('beforeChange', function(event, slick, currentSlide, nextSlide) {
       
       // console.log($('.variable').find('.slick-current'));
        // $('.variable').find('.slick-current').append('<div class="property-item-preloader"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');//show loader
        var decl = currentSlide - nextSlide;
        if (decl == 1 || decl == (slick.slideCount - 1) * (-1)) {
         

            ws.addClass('is-prev');
            cardFlag = "prev";
            slide.count=slide.count-1;

            
        } else {
            if(slide.count>=slide.maxCount){
                event.preventDefault();
                return false;
            }
            ws.addClass('is-next');
            cardFlag = "next";
            if (slide.count >= 0 && slide.count < slide.maxCount) {
                

                if (sliderArrayLoad.includes(slide.count) === false) { //dont call endpoint if already called
                  
                    $('.variable').find('.slick-current').next().append('<div class="property-item-preloader"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
                    
                    $('.slick-arrow').prop('disabled', true);
                    getData(slide.count,event);
                    
                    
                   
                }

            }
            
        }
    });
    ws.on('afterChange', function(event, slick, currentSlide, nextSlide) {
       if(cardFlag=="next"){
        if ((sliderArrayLoad.includes(slide.count) === false) && (slide.count >= 0 && slide.count < slide.maxCount)){
            // createCard();
            sliderArrayLoad.push(slide.count);
           
            
        }
        if(slide.count < slide.maxCount)
        slide.count=slide.count+1;
        
       }
       
        ws.removeClass('is-prev').removeClass('is-next');
        calculateAll();
      
        // $('.detailedUrl').html($('.variable .slick-current .downloadLink').val());

        
        //console.log("id req = "+slide.count);
        //console.log(sliderArrayLoad);
        
    });
    function getData(num,event) {
       
        //     let url=window.location.href+"featured-offering/"+num;
        let featOfferingUrl = "/featured-offering/";
        let xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {

                let card = JSON.parse(xhr.responseText);
                // console.log(card);
                
                if (card[0] == false) {
                    // console.log('There was error getting the property card data');
                    // $('#modal-alert-body').html('All featured properties are loaded.');
                    // $('#modal-alert-error').modal('show');
                    $('.slick-arrow').prop('disabled', false);
                   // event.prevendDefault();
                   ws.slick("slickPrev");
                   slide.maxCount=slide.count;
                   $('.property-item-preloader').remove();
                    return false;
                }
                window.cardData=card[0];
                createCard();
            }
        };
        xhr.open('GET', featOfferingUrl + num, true);
        xhr.send();

    }

    function getInfo(infoArray, infoKey, defaultValue = '') {
        for (const entry of infoArray) {
            if (infoKey == entry['type']) {
                return entry['value'];
            }
        }
        return defaultValue;
    }
 

    function singPlur(text, data) {
        if (parseInt(data) > 1) {
            return data + text + 's';
        } else if (parseInt(data) == 1) {
            return data + text;
        } else {
            return '0' + text;
        }
    }

    function digitsFormat(num) {
        return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
    }

    function createCard() {
        let data=window.cardData;
        let term = (data.term != 'null') ? data.term + ' Years' : 'null';
        let displayName = (data.display_name != 'null') ? data.display_name : data.name;
        let streetAdd = (data.address.street_address != 'null') ? data.address.street_address + ', ' : '';
        let cityAdd = (data.address.city != 'null') ? data.address.city + ', ' : '';
        let countryAdd = (data.address.country != 'null') ? data.address.country : '';

        let rentGuaranteed = 'Rent Guaranteed' == getInfo(data.info, 'proposedTenancy');
        let investTermRem = (data.custom.investment_term != 'null') ? data.custom.investment_term + ' Months' : 'null';
        let projectRent = (data.estimated_net_roi != null) ? data.estimated_net_roi + '%' : '0%';
        let projectReturn = (data.estimated_gross_roi != null) ? data.estimated_gross_roi + '%' : '0%';
        let status = (data.raised_percent < 100) ? 'Open' : 'Sold out';
        let prop_img = 'bundles/app/img/property-thumb-md.jpg';
        let detailedUrl = '';
        let percent = ((data.amount_raised/data.funding_goal)*100).toFixed(2);
        if(data.life_cycle_stage == 0){
            status="Draft";
            statusClass="draft";
       }else if(percent>=100){
            status="Sold Out";
            statusClass="sold";
       }else{
            status="Open";
            statusClass="open";
       }
        $.each(data.documents, function(i, v) {
            if (v.tag == 'logo') {
                prop_img = v.document_url;
                return false;
            }
            // if (v.tag == 'calculations') {
            //     detailedUrl = `<a target="_blank" href="`+v.document_url+`" class="btn-underline text-sm font-medium">Download detailed breakdown</a>`;
            // }
        });
        if (loggedInStatus==1) {
            url="/secondary-asset/" + data.offering_id + "/overview";
        } else {
            url="/asset-public/" + data.offering_id + "/overview";
        }
       slideElements = `<div style="width: 279px;" dir="ltr">
                    <div class="card-imgholder position-relative overflow-hidden">
                        <img src="` + prop_img + `" class="card-img-top" alt="...">
                        <a href="#" class="btn property-item-status `+ statusClass +` btn-sm layer-z-1 text-sm">` + status + `</a>
                        <div class="card-quickinfo position-absolute p-1 text-right">
                            <p class="mb-0 text-light">Total Funding Goal</p>
                            <p class="mb-0 text-light">£` + digitsFormat(parseFloat(data.funding_goal).toFixed(0)) + `</p>
                        </div>
                    </div>
                    <div class="card-body">`
        
        if (rentGuaranteed) {
            slideElements += `<div class="mb-1">
                <span class="badge bg-secondary rounded-pill px-1">Rent Guaranteed</span>
            </div>`
        }
                    
        slideElements += `<h6 class="card-title mb-0">` + displayName + `</h6>
                        <p class="address" title="` + streetAdd + cityAdd + countryAdd + `"><small>` + streetAdd + cityAdd + countryAdd + `</small></p>

                        <!-- price -->
                        <div class="price rounded-pill position-relative">
                            <div class="price-bar rounded-pill" style="width: ` + data.raised_percent + `%;"></div>
                            <div class="price-text d-flex justify-content-between align-items-center position-relative font-medium p-1 layer-z-1">
                                <span>£` + digitsFormat(parseFloat(data.amount_raised).toFixed(0)) + `</span>
                                <span>` + digitsFormat(parseFloat(data.raised_percent).toFixed(0)) + `%</span>
                                <span>£` + digitsFormat(parseFloat(data.funding_goal).toFixed(0)) + `</span>
                            </div>
                        </div>

                        <!-- property details -->
                        <ul class="property-dtls-list p-0 mt-2 mb-1">
                                <li class="d-flex justify-content-between border-bottom text-sm">
                                <span class="pr-1" data-cardterm="` + data.term + `">Investment Period</span>
                                <span>` + singPlur(' Year', data.term) + ` </span>
                            </li>
                                <li class="d-flex justify-content-between border-bottom text-sm">
                                <span class="pr-1" data-cardinvestementterm="` + data.investment_term + `">Investment Term Remaining</span>
                                <span>` + singPlur(' Month', data.investment_term) + ` </span>
                            </li>
                            <li class="d-flex justify-content-between text-sm">
                                <span class="pr-1" data-cardcprojectedrent="` + data.estimated_net_roi + `">Projected Net Annual Rent</span>
                                <span>` + projectRent + `</span>
                            </li>  
                        </ul>
                    </div>
                    <a href="`+url+`" class="card-footer btn btn-primary btn-block py-2">See Property Details</a>
                    <input type="hidden" class="downloadLink">
                </div>`;

                // Total return removed
                // <li class="d-flex justify-content-between border-bottom text-sm">
                //     <span class="pr-1" data-cardProjectedReturn="` + data.estimated_gross_roi + `">Projected Return (Full Term)</span>
                //     <span>` + projectReturn + `</span>
                // </li>

        // $('.property-home .property-item.slick-slide.slick-current').css('background','none !important');
        // $('.property-home .property-item.slick-slide.slick-current').css('opacity','1');
        
        $('.variable .slick-current').html(slideElements);
        // $('.variable .slick-current .downloadLink').val(detailedUrl);
        // $('.detailedUrl').html(detailedUrl);
        $('.property-item-preloader').remove();//remove loader
     
        $('.slick-arrow').prop('disabled', false);
        $('.property-home .property-item.slick-slide.slick-current .card-imgholder, .property-home .property-item.slick-slide.slick-current .card-body, .property-home .property-item.slick-slide.slick-current .card-footer').css('filter','blur(0px)');
        updateCalculator(data);
     

    }

    function SetViewData(dataName, mainData, visData) {
        if (mainData == null) {
            mainData = 0;
            visData = visData.replace("null", "0.0");
        }
        if (isNaN(mainData)) {
            visData = visData.replace("NaN", "0.0");
        }
        $('[data-' + dataName + ']').attr('data-' + dataName, mainData); //set data
        $('[data-' + dataName + ']').html(visData); //display data
    }

    function getAttrData(attrName) {
        return parseFloat($('.variable .slick-current [data-' + attrName + ']').attr('data-' + attrName));
    }

    function updateCalculator(data) {

        let sliderValAmt = parseFloat($('.sliderData').val());
        //projected rent
        SetViewData('calcProjectedRent', data.estimated_net_roi, data.estimated_net_roi + '%');
        // rental return per year
        let newcalcrentalreturnpy = parseFloat((data.estimated_net_roi / 100) * sliderValAmt);
        SetViewData('calcrentalreturnpy', newcalcrentalreturnpy, '£' + digitsFormat(newcalcrentalreturnpy.toFixed(2)));

        //rental return per month
        SetViewData('calcrentalreturnpm', newcalcrentalreturnpy / 12, '£' + (newcalcrentalreturnpy / 12).toFixed(2));

        //net projected Capital Return
        let var1 = (parseFloat(((data.estimated_net_roi / 100) / 12) * data.investment_term)) || 0;
        let var2 = (parseFloat((data.estimated_gross_roi) / 100) - parseFloat(((data.estimated_net_roi / 100) * data.term))) || 0;
        SetViewData('calcnetprojectedcapitalreturn', (var1 + var2) * 100, ((var1 + var2) * 100).toFixed(2) + '%');

        //net projected Total Return
        SetViewData('calcprojectedtotalreturn', data.estimated_gross_roi, data.estimated_gross_roi + '%');

        //remaining term income 
        let remtermincome = sliderValAmt + (sliderValAmt * (var1 + var2));
        SetViewData('calcprojectedcapitalreturn', remtermincome, '£' + digitsFormat(remtermincome.toFixed(2)));

        //projected capital & rental at end of investement
        let finalcalcpcrei = sliderValAmt + (sliderValAmt * parseFloat(data.estimated_gross_roi / 100));
        SetViewData('calcpcrei', finalcalcpcrei, '£' + digitsFormat(finalcalcpcrei.toFixed(2)));


    }

    function calculateAll() {
        let sliderVal = parseFloat($('.sliderData').val()); //amount
        //projected rent

        SetViewData('calcProjectedRent', parseFloat(getAttrData('cardcprojectedrent')), getAttrData('cardcprojectedrent') + '%');
        // rental return per year
        let newcalcrentalreturnpy = parseFloat(getAttrData('cardcprojectedrent') / 100) * sliderVal;
        SetViewData('calcrentalreturnpy', newcalcrentalreturnpy, '£' + digitsFormat(newcalcrentalreturnpy.toFixed(2)));
        // rental return per month
        let calcRentalReturnPM = parseFloat(parseFloat(newcalcrentalreturnpy / 12));
        SetViewData('calcrentalreturnpm', calcRentalReturnPM, '£' + digitsFormat(calcRentalReturnPM.toFixed(2)));

        //net projected Capital Return  
        let var1 = (parseFloat(((getAttrData('cardcprojectedrent') / 100) / 12) * getAttrData('cardinvestementterm'))) || 0;
        let var2 = (parseFloat((getAttrData('cardProjectedReturn') / 100) - (parseFloat((getAttrData('cardcprojectedrent') / 100) * getAttrData('cardterm'))))) || 0;
        SetViewData('calcnetprojectedcapitalreturn', (var1 + var2) * 100, ((var1 + var2) * 100).toFixed(2) + '%');


        //calcProjectedReturn cardProjectedReturn
        SetViewData('calcprojectedtotalreturn', getAttrData('cardProjectedReturn'), getAttrData('cardProjectedReturn') + '%');

        //remaining term income 
        let remtermincome = sliderVal + (sliderVal * (var1 + var2));
        SetViewData('calcprojectedcapitalreturn', remtermincome, '£' + digitsFormat(remtermincome.toFixed(2)));

        //projected capital & rental at end of investement
        let finalcalcpcrei = sliderVal + ((sliderVal * parseFloat(getAttrData('cardprojectedreturn') / 100)) || 0);
        SetViewData('calcpcrei', finalcalcpcrei, '£' + digitsFormat(parseFloat(finalcalcpcrei).toFixed(2)));

    }
    $('.finalCalc').click(function() {
        calculateAll();
    });
    calculateAll();
    $('.property-home .property-item.slick-slide.slick-current .card-imgholder, .property-home .property-item.slick-slide.slick-current .card-body, .property-home .property-item.slick-slide.slick-current .card-footer').css('filter','blur(0px)'); 

});