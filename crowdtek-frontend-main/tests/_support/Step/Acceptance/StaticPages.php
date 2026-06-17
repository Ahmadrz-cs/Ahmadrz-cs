<?php

namespace Step\Acceptance;

class StaticPages extends \AcceptanceTester
{
    public $info_bar_css = '.full-info-bar';
    public $description_css = '.description-block';
    public $easysteps_css = '.b-overlay';
    public $investment_profile_css = 'div.form-block:nth-child(1)';
    public $top_yielder_css = '.full-block';
    public $benefits_css = '.benefits-section';
    public $Partners_css = '.client-slider';
    public $meet_the_team_css = 'div.overlay:nth-child(1)';
    public $offer_css = '.about-offer';
    public $social_box_css = '.social-box';
    public $ss_image1_css = '.slides-container > li:nth-child(1) > *:nth-child(1)';
    public $ss_image2_css = '.slides-container > li:nth-child(2) > *:nth-child(1)';
    public $ss_welcome_pack_css = '.get-started-btn > a:nth-child(1)';
    public $ss_css = '.slides-container';
    public $menu_css = '.submenu-block';

    public $news_letter_css = '.newsletter-block';
    public $article_2_css = 'a.grid-item:nth-child(3)';
    public $article_11_css = 'a.grid-item:nth-child(12)';
    public $article_20_css = 'a.grid-item:nth-child(21)';
    public $info_block_css = 'div.full-block:nth-child(10)';
    public $careers_css = '.col-sm-5 > h3:nth-child(1)';
    public $product_development_css = 'div.accordion-content:nth-child(1) > div:nth-child(1) > h3:nth-child(1)';
    public $accounts_assistant_css = 'div.accordion-content:nth-child(2) > div:nth-child(1) > h3:nth-child(1)';
    public $marketing_manager_css = 'div.accordion-content:nth-child(3) > div:nth-child(1) > h3:nth-child(1)';
    public $contact_css = 'a.def-btn:nth-child(4)';

    public $shariah_block = '.b-overlay';
    public $faq_block = '.col-sm-7 > p:nth-child(1)';
    public $faq_9_css = 'div.accordion-content:nth-child(9) > div:nth-child(1) > h3:nth-child(1)';
    public $faq_7_css = 'div.accordion-content:nth-child(7) > div:nth-child(1) > h3:nth-child(1)';
    public $faq_5_css = 'div.accordion-content:nth-child(5) > div:nth-child(1) > h3:nth-child(1)';
    public $content_slider_css = 'div.content-slider';
    public $next_button_css = 'button.slick-next';
    public $previous_button_css = 'button.slick-prev';
    public $content_slider_heading_1_css = 'div.content-box:nth-child(2) > div:nth-child(1) > div:nth-child(1) > h3:nth-child(1)';
    public $content_slider_heading_2_css = 'div.content-box:nth-child(3) > div:nth-child(1) > div:nth-child(1) > h3:nth-child(1)';
    public $content_slider_heading_3_css = 'div.content-box:nth-child(4) > div:nth-child(1) > div:nth-child(1) > h3:nth-child(1)';
    public $featured_properties_heading_css = '.f-wrap h2';

    public $yielders_process_css = '.process-block > div:nth-child(1) > h2:nth-child(1)';
    public $hassle_investments_css = '.feature-content > h2:nth-child(1)';
    public $crowdfunding_css = '.how-work-box > div:nth-child(1) > h2:nth-child(2)';
    public $crowdfunding_icon1_css = 'div.col-sm-3:nth-child(2) > span:nth-child(1) > img:nth-child(1)';
    public $crowdfunding_icon2_css = 'div.col-sm-3:nth-child(3) > span:nth-child(1) > img:nth-child(1)';
    public $crowdfunding_icon3_css = 'div.col-sm-3:nth-child(4) > span:nth-child(1) > img:nth-child(1)';
    public $crowdfunding_icon4_css = 'div.col-sm-3:nth-child(5) > span:nth-child(1) > img:nth-child(1)';
    public $crowdfunding_text1_css = 'div.how-content:nth-child(1) > p:nth-child(1)';
    public $crowdfunding_text2_css = 'div.how-content:nth-child(2) > p:nth-child(1)';
    public $crowdfunding_text3_css = 'div.how-content:nth-child(3) > p:nth-child(1)';

    public $crowdfunding_text4_css = 'div.how-content:nth-child(4) > p:nth-child(1)';
    public $become_yielder_css = '.become-yielder-block';
    public $asset_css = 'body > div.wrapper > div.description-block';
    public $asset_yielder_css = 'div.col-sm-4:nth-child(3) > p:nth-child(2)';
    public $crowdfunding_block_css = '.how-work-box > div:nth-child(1)';
    public $crowdfunding_prework_css = 'div.h-16:nth-child(1) > span:nth-child(1) > img:nth-child(1)';
    public $conventional_block_css = '.yielders-process > div:nth-child(1)';
    public $introduce_block_css = '.footer';
    public $fees_css = '.fees-block > div:nth-child(1)';
    public $process_css = '.process-box';
    public $process_invest_css = 'div.process-item:nth-child(5) > div:nth-child(2) > p:nth-child(2)';
    public $story_css = '.story-block';

    public $story_image_css = '.story-5 > div:nth-child(2) > span:nth-child(1) > img:nth-child(1)';
    public $story_image_4_css = '.story-4 > div:nth-child(2) > span:nth-child(1) > img:nth-child(1)';
    public $portfolio_block_css = '.top-yielder';
    public $market_block_css = 'div.full-block:nth-child(8)';
    public $properties_portfolio_block_css = '.portfolio';
    public $properties_yielder_block_css = 'div.full-block:nth-child(9)';
    public $property_footer_css = '.footer';
    public $property_sourcing_css = '.b-overlay';
    public $faq_description_css = '.col-sm-7 > p:nth-child(1)';
    public $process_crowdfunding_css = '.how-work-box';
    public $process_fees_css = '.fees-block > div:nth-child(1)';
    public $term3_css = 'div.accordion-content:nth-child(3) > div:nth-child(1) > h3:nth-child(1)';
    public $term10_css = 'div.accordion-content:nth-child(10) > div:nth-child(1) > h3:nth-child(1)';

    public $term18_css = 'div.accordion-content:nth-child(18) > div:nth-child(1) > h3:nth-child(1)';
    public $header_logo_css = '.col-xs-2 > a:nth-child(1) > img:nth-child(1)';
    public $header_welcome_pack_css = '.d-btn';
    public $header_sign_up_css = 'li.signup-login-btn:nth-child(3) > a:nth-child(1)';
    public $header_login_css = 'li.signup-login-btn:nth-child(4) > a:nth-child(1)';
    public $header_facebook_css = 'li.mob-hide:nth-child(5) > ul:nth-child(1) > li:nth-child(1) > a:nth-child(1)';
    public $header_linkedin_css = 'li.mob-hide:nth-child(5) > ul:nth-child(1) > li:nth-child(2) > a:nth-child(1)';
    public $header_twitter_css = 'li.mob-hide:nth-child(5) > ul:nth-child(1) > li:nth-child(3) > a:nth-child(1)';
    public $header_instagram_css = 'li.mob-hide:nth-child(5) > ul:nth-child(1) > li:nth-child(4) > a:nth-child(1)';
    public $footer_css = '.footer-content';

    public $footer_all_properties_css = 'div.col:nth-child(1) > a:nth-child(2)';
    public $footer_relisted_properties_css = 'div.col:nth-child(1) > a:nth-child(3)';
    public $footer_how_it_works_css = 'div.col:nth-child(2) > a:nth-child(2)';
    public $footer_our_process_css = 'div.col:nth-child(2) > a:nth-child(3)';
    public $footer_property_sourcing_css = 'div.col:nth-child(2) > a:nth-child(4)';
    public $footer_our_fees_css = 'div.col:nth-child(2) > a:nth-child(5)';
    public $footer_faq_css = 'div.col:nth-child(2) > a:nth-child(6)';
    public $footer_our_story_css = 'div.col:nth-child(3) > a:nth-child(2)';
    public $footer_shariah_css = 'div.col:nth-child(3) > a:nth-child(3)';
    public $footer_risks_css = 'div.col:nth-child(3) > a:nth-child(4)';

    public $footer_careers_css = 'div.col:nth-child(3) > a:nth-child(5)';
    public $footer_contact_css = 'div.col:nth-child(3) > a:nth-child(6)';
    public $footer_logo_css = 'a.logo > img:nth-child(1)';
    public $footer_facebook_css = 'div.col:nth-child(6) > ul:nth-child(1) > li:nth-child(1) > a:nth-child(1)';
    public $footer_linkedin_css = 'div.col:nth-child(6) > ul:nth-child(1) > li:nth-child(2) > a:nth-child(1)';
    public $footer_twitter_css = 'div.col:nth-child(6) > ul:nth-child(1) > li:nth-child(3) > a:nth-child(1)';
    public $footer_instagram_css = 'div.col:nth-child(6) > ul:nth-child(1) > li:nth-child(4) > a:nth-child(1)';
    public $footer_terms_css = '.terms > li:nth-child(1) > a:nth-child(1)';
    public $footer_privacy_css = '.terms > li:nth-child(2) > a:nth-child(1)';
    public $nav_properties_css = 'li.drop:nth-child(1) > a:nth-child(1)';

    public $nav_how_it_works_css = 'li.drop:nth-child(2) > a:nth-child(1)';
    public $nav_about_us_css = 'li.drop:nth-child(3) > a:nth-child(1)';
    public $nav_blog_css = 'div.submenu-block > div:nth-child(1) > ul:nth-child(2) > li:nth-child(4) > a:nth-child(1)';
    public $cleaf_css = '.c-btn';
    public $property_type_css = '#filter_property_type_chosen > a:nth-child(1)';
    public $risk_factor_css = '#filter_risk_factor_chosen > a:nth-child(1)';
    public $term_css = '#filter_term_chosen > a:nth-child(1)';
    public $yielder_properties_css = '.property-listing';
    public $relisted_properties_introduce_css = '.introduce';
    public $relisted_properties_yielder_block_css = 'div.full-block:nth-child(5)';
    public $porfolio_yielder_block_css = '/html/body/div[3]/div[4]/div[7]';
    public $porfolio_yielder_block_heading = 'Become a';

    public $edit_button_css = 'a.def-btn:nth-child(1)';
    public $edit_contact_text = 'Edit Contact';
    public $overview_block = '.page-head';
    public $profile_pic_css = '#file_avatar';
    public $sophisticated_investor_css = '#certificationHeadindTwo > h4:nth-child(1)';
    public $compliance_uri = '/my-profile/registration-compliance';
    public $company_form_checkbox_css = '.label-pl0 > label:nth-child(1)';
    public $kyc_choose_file_buttom_css = 'a.def-btn:nth-child(4)';

    public $add_Organization_form_css = '.add-property-block';
    public $add_Organization_Country_drop_css = '#organization_type_address_country_chosen > a:nth-child(1) > span:nth-child(1)';
    public $add_Organization_upload_field_css = '#dynamic_image';

    public $property_sourcing_team_text = 'Who you know and not what you know…';
    public $property_sourcing_team_heading = 'div.col-sm-3:nth-child(2) > div:nth-child(1) > h4:nth-child(2)';
    public $property_sourcing_buy_text = 'Buy 2 Let but with better returns…';
    public $property_sourcing_buy_heading = 'div.col-sm-3:nth-child(3) > div:nth-child(1) > h4:nth-child(2)';
    public $property_sourcing_maintenance_text = 'Your yield can be diluted through poor management!';
    public $property_sourcing_maintenance_heading = 'div.col-sm-3:nth-child(4) > div:nth-child(1) > h4:nth-child(2)';
    public $property_sourcing_cash_text = 'There is a reason why Cash is the undisputed King in property';
    public $property_sourcing_cash_heading = 'div.col-sm-3:nth-child(5) > div:nth-child(1) > h4:nth-child(2)';
    public $reg_compliance_css = '.submenu-block > div:nth-child(1) > ul:nth-child(2) > li:nth-child(3) > a:nth-child(1)';
    public $reg_compliance_fund_css = '.fund-account';

    public $add_Organization_desc_css = '#organization_type_brief_desc';
    public $offer_text_css = 'Looking for a career in Fintech?';
    public $article_css = 'a.grid-item:nth-child(2)';

    public $yielders_asset_icon_css = '#eone-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_investment_icon_css = '#etwo-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_return_icon_css = '#ethree-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_exit_icon_css = '#efour-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_offer_icon_css = '#efive-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_acquire_icon_css = '#esix-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_spv_icon_css = '#eseven-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_rental_icon_css = '#eeight-tab > span:nth-child(2) > img:nth-child(1)';
    public $yielders_dividend_icon_css = '#enine-tab > span:nth-child(2) > img:nth-child(1)';

    public $privacy_use_policy_text = 'WEBSITE ACCEPTABLE USE POLICY';
    public $privacy_terms_policy_text = 'TERMS OF WEBSITE USE POLICY';
    public $privacy_cookie_policy_text = 'COOKIE POLICY';
    public $privacy_policy_text = 'PRIVACY POLICY';

    public $process_browse_icon_css = 'div.col-sm-3:nth-child(1) > span:nth-child(1) > img:nth-child(1)';
    public $process_investmens_icon_css = 'div.col-sm-3:nth-child(2) > span:nth-child(1) > img:nth-child(1)';
    public $process_return_icon_css = 'div.col-sm-3:nth-child(3) > span:nth-child(1) > img:nth-child(1)';
    public $process_exit_icon_css = 'div.col-sm-3:nth-child(4) > span:nth-child(1) > img:nth-child(1)';

    public $risk_q1_css = 'div.accordion-content:nth-child(1) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q2_css = 'div.accordion-content:nth-child(2) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q3_css = 'div.accordion-content:nth-child(3) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q4_css = 'div.accordion-content:nth-child(4) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q5_css = 'div.accordion-content:nth-child(5) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q6_css = 'div.accordion-content:nth-child(6) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q7_css = 'div.accordion-content:nth-child(7) > div:nth-child(1) > h3:nth-child(1)';
    public $risk_q8_css = 'div.accordion-content:nth-child(8) > div:nth-child(1) > h3:nth-child(1)';

    public $shariah_interest_css = '#eone-tab';
    public $shariah_voting_css = '#etwo-tab';
    public $shariah_rights_css = '#ethree-tab';
    public $shariah_audit_css = '#efour-tab';
    public $shariah_social_css = '#efive-tab';
    public $shariah_transparency_css = '#esix-tab';
    public $shariah_yield_css = '#enine-tab';
    public $shariah_debt_css = '#eten-tab';

    public $yielder_application_css = '.apply-top-block';
    public $my_wallet_transaction_history = 'div.transaction-history:nth-child(1) > h2:nth-child(1)';
    public $add_property_css = '.banner-caption > h1:nth-child(1)';

    public $profile_firstname_css = '#given_name';
    public $profile_lastname_css = '#family_name';
    public $profile_phone_css = '#phone_1';
    public $profile_save_button_css = 'input.def-btn';

    public $kyc_submit_button_css = '#kyc-submit-btn';
    public $kyc_submit_text = 'Submit';
    public $kyc_addfunds_text = 'Add funds';

    public $offer_property_tab_css = '.add-property-tabs';





    public function footerScroll($text)
    {
        $I = $this;

        $I->scrollTo($I->footer_css);
        $I->waitForText($text);
        $I->scrollTo($I->footer_css);
    }

    public function footerScrollElement($element)
    {
        $I = $this;

        $I->scrollTo($I->footer_css);
        $I->waitForElementVisible($element);
        $I->scrollTo($I->footer_css);
    }

    //Social Box
    public function socialBoxHeadingTest()
    {
        $I = $this;

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForText('Follow our Socialfeeds');

        //Heading
        $I->see('Follow our Socialfeeds', 'ul.social:nth-child(2) > li:nth-child(1) > p:nth-child(1)');
    }


    //Block 1
    public function socialBoxImage_1Test()
    {
        /*$I = $this;

        $I->wantTo("Check if image 1 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(1) > div:nth-child(1)');

        //Block 1
        $I->seeElement('li.slick-slide:nth-child(1) > div:nth-child(1)');*/
    }

    public function socialBoxOverlay_1Test()
    {
        /*$I = $this;

        $I->wantTo("Check if overlay 1 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(1) > div:nth-child(1)');

        //Block 1
        $I->moveMouseOver('li.slick-slide:nth-child(1) > div:nth-child(1)');

        $I->waitForElementVisible('li.slick-slide:nth-child(1) > div:nth-child(1) > div:nth-child(2) > p:nth-child(1)');
        //Text
        $I->seeElement('li.slick-slide:nth-child(1) > div:nth-child(1) > div:nth-child(2) > p:nth-child(1)');*/
    }

    public function socialBoxButton_1Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the read more button in block 1 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(1) > div:nth-child(1)');

        //Block 1
        $I->moveMouseOver('li.slick-slide:nth-child(1) > div:nth-child(1)');
        //Read more button

        $I->waitForText('read more');

        $I->see('read more');*/
    }


    //Block 2
    public function socialBoxImage_2Test()
    {
        /*$I = $this;

        $I->wantTo("Check if image 2 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(2) > div:nth-child(1)');

        //Block 2
        $I->seeElement('li.slick-slide:nth-child(2) > div:nth-child(1)');*/
    }

    public function socialBoxOverlay_2Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the overlay 2 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(2) > div:nth-child(1)');

        //Block 2
        $I->moveMouseOver('li.slick-slide:nth-child(2) > div:nth-child(1)');
        //Text
        $I->seeElement('li.slick-slide:nth-child(2) > div:nth-child(1) > div:nth-child(2) > p:nth-child(1)');*/
    }

    public function socialBoxButton_2Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the read more button in block 2 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(2) > div:nth-child(1)');

        //Block 2
        $I->moveMouseOver('li.slick-slide:nth-child(2) > div:nth-child(1)');
        //Read more Button

        $I->waitForText('read more');
        $I->see('read more');*/
    }


    //Block 3
    public function socialBoxImage_3Test()
    {
        /*$I = $this;

        $I->wantTo("Check if image 3 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(3) > div:nth-child(1)');

        //Block 3
        $I->seeElement('li.slick-slide:nth-child(3) > div:nth-child(1)');*/
    }

    public function socialBoxOverlay_3Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the overlay 3 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(3) > div:nth-child(1)');

        //Block 3
        $I->moveMouseOver('li.slick-slide:nth-child(3) > div:nth-child(1)');
        //Text
        $I->seeElement('li.slick-slide:nth-child(3) > div:nth-child(1) > div:nth-child(2) > p:nth-child(1)');*/
    }

    public function socialBoxButton_3Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the read more button in block 3 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(3) > div:nth-child(1)');

        //Block 3
        $I->moveMouseOver('li.slick-slide:nth-child(3) > div:nth-child(1)');

        //Read More Button
        $I->waitForText('read more');
        $I->see('read more');*/
    }

    //Block 4
    public function socialBoxImage_4Test()
    {
        /*$I = $this;

        $I->wantTo("Check if image 4 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(4) > div:nth-child(1)');

        //Block 4
        $I->seeElement('li.slick-slide:nth-child(4) > div:nth-child(1)');*/
    }

    public function socialBoxOverlay_4Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the overlay 4 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(4) > div:nth-child(1)');

        //Block 4
        $I->moveMouseOver('li.slick-slide:nth-child(4) > div:nth-child(1)');
        //Text
        $I->seeElement('li.slick-slide:nth-child(4) > div:nth-child(1) > div:nth-child(2) > p:nth-child(1)');*/
    }

    public function socialBoxButton_4Test()
    {
        /*$I = $this;

        $I->wantTo("Check if the read more button in block 4 in 'Social Box' is visible");

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('li.slick-slide:nth-child(4) > div:nth-child(1)');

        //Block 4
        $I->moveMouseOver('li.slick-slide:nth-child(4) > div:nth-child(1)');

        //Read more Button
        $I->waitForText('read more');
        $I->see('read more');*/
    }

    public function socialBoxFacebookIconTest()
    {
        $I = $this;

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('ul.social:nth-child(2) > li:nth-child(2) > a:nth-child(1)');

        //Facebook link
        $I->seeElement('ul.social:nth-child(2) > li:nth-child(2) > a:nth-child(1)');
    }

    public function socialBoxLinkedinIconTest()
    {
        $I = $this;

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('ul.social:nth-child(2) > li:nth-child(3) > a:nth-child(1)');

        //Linkedin link
        $I->seeElement('ul.social:nth-child(2) > li:nth-child(3) > a:nth-child(1)');
    }

    public function socialBoxTwitterIconTest()
    {
        $I = $this;

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('ul.social:nth-child(2) > li:nth-child(4) > a:nth-child(1)');

        //Twitter link
        $I->seeElement('ul.social:nth-child(2) > li:nth-child(4) > a:nth-child(1)');
    }

    public function socialBoxInstagramIconTest()
    {
        $I = $this;

        //Submit button
        $I->scrollTo($I->social_box_css);
        $I->waitForElementVisible('ul.social:nth-child(2) > li:nth-child(5) > a:nth-child(1)');

        //Instagram link
        $I->seeElement('ul.social:nth-child(2) > li:nth-child(5) > a:nth-child(1)');
    }

    public function kycScroll()
    {
        $I = $this;

        //scrolls to the kyc form
        $I->waitForText('2. KYC');
        $I->scrollTo('.u-name > h1:nth-child(1)');
        $I->wait($this->page_wait_time);
        $I->scrollTo($I->reg_compliance_css);
        $I->click('2. KYC');
        $I->waitForText('Identity Check');
        $I->scrollTo('#title_kyc');
    }

    public function addNewOfferScroll()
    {
        $I = $this;

        //scrolls to the Add New Offer form
        $I->scrollTo('.banner-caption > h1:nth-child(1)');
        $I->click('Add New Offering');
        $I->waitForText('Choose your Organization');
        $I->scrollTo('#offering-tab');
    }

    public function shareholderScroll()
    {
        $I = $this;

        //scrolls to the Shareholder form
        $I->scrollTo('.banner-caption > h1:nth-child(1)');
        $I->click('Shareholder Repayments');
        $I->waitForText('Select Property');
        $I->scrollTo('#shareholder-tab');
    }

    public function investmentManagementScroll()
    {
        $I = $this;

        //scrolls to the Shareholder form
        $I->scrollTo('.banner-caption > h1:nth-child(1)');
        $I->click('Investment Management');
        $I->waitForText('Action');
        $I->scrollTo('#investment-tab');
    }

    public function termSubHeadingTest($termHeading, $term, $scroll)
    {
        $I = $this;

        $termCss = "div.accordion-content:nth-child($term) > div:nth-child(1) > 
        h3:nth-child(1)";

        $I->scrollTo($scroll);
        $I->waitForElementVisible($termCss);

        $I->waitForText($termHeading, 3, $termCss);
    }

    public function termTextTest($termHeading, $term, $scroll)
    {
        $I = $this;

        $termCss = "div.accordion-content:nth-child($term) > div:nth-child(1) > 
        h3:nth-child(1)";

        $termText = "div.accordion-content:nth-child($term) > div:nth-child(1) > div:nth-child(2) > 
        p:nth-child(1)";

        $I->click($termCss);    //open accordion
        $I->wait($I->animation_time);

        $I->waitForElementVisible($termText);

        $I->click($termCss);    //close accordion
        $I->wait($I->animation_time);
    }

    public function faqQuestionTest($question, $questionNo)
    {
        $I = $this;

        $questionCss = "div.accordion-content:nth-child($questionNo) > div:nth-child(1) > 
        h3:nth-child(1)";

        $questionText = "div.accordion-content:nth-child($questionNo) > div:nth-child(1) > div:nth-child(2) > 
        *:nth-child(1)";

        $I->see($question, $questionCss);

        $I->click($questionCss);
        $I->wait(0.75);

        $I->waitForElementVisible($questionText, 3);
    }
}
