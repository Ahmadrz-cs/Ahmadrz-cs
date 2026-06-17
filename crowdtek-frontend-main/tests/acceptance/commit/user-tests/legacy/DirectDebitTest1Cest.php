<?php

// DirectDebitTest1Cest.php

class DirectDebitTest1Cest
{
    public function _before(AcceptanceTester $I)
    {
        $I->loginWithCredentials($I->reg_user_name, $I->admin_user_password);
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group directDebit
     */
    // public function checkNavigationToPage(AcceptanceTester $I)
    // {
    //    // portfolio
    //    $I->amOnPage('/my-portfolio');
    //    $I->waitForText('My Portfolio');
    //    $I->clickWithLeftButton('a[href="/my-profile/direct-debit-setup"]');
    //    $I->seeInCurrentUrl('/my-profile/direct-debit-setup');
    //    $I->waitForText('Setup a Direct Debit Payment');
    //    $I->seeElement('form[name="setup_direct_debit_type"]');
    //    // profile
    //    $I->amOnPage('/my-profile/dashboard');
    //    $I->waitForText('My Profile');
    //    $I->clickWithLeftButton('a[href="/my-profile/direct-debit-setup"]');
    //    $I->seeInCurrentUrl('/my-profile/direct-debit-setup');
    //    $I->waitForText('Setup a Direct Debit Payment');
    //    $I->seeElement('form[name="setup_direct_debit_type"]');
    //    // transaction history
    //    $I->amOnPage('/my-profile/transactions');
    //    $I->waitForText('Transaction History');
    //    $I->clickWithLeftButton('a[href="/my-profile/direct-debit-setup"]');
    //    $I->seeInCurrentUrl('/my-profile/direct-debit-setup');
    //    $I->waitForText('Setup a Direct Debit Payment');
    //    $I->seeElement('form[name="setup_direct_debit_type"]');
    //    // insufficent funds (if implemented)
    // }

    /**
     * @group directDebit
     */
    public function checkAddressAutoFill(AcceptanceTester $I)
    {
        $I->amOnPage('/my-profile/direct-debit');
        $I->clickWithLeftButton('//*[@id="profile"]/div/div/div/div/div/form/input');
        // $I->see('Setup a Direct Debit Payment');

        // pull user address from boxes
        $I->scrollTo('section#profile form');
        $building = $I->grabValueFrom('input#setup_direct_debit_type_address_1');
        $streetAddress = $I->grabValueFrom('input#setup_direct_debit_type_address_2');
        $city = $I->grabValueFrom('input#setup_direct_debit_type_town_city');
        $postcode = $I->grabValueFrom('input#setup_direct_debit_type_post_code');

        // pull user details from cms and assert equals to above variables
        $token = $I->getUserToken($I->reg_user_name, $I->admin_user_password);
        $user = $I->getUserInfoByAPI($token);
        $I->assertEquals($building, $user['address']['building']);
        $I->assertEquals($streetAddress, $user['address']['street_address']);
        $I->assertEquals($city, $user['address']['city']);
        $I->assertEquals($postcode, $user['address']['postal_code']);
        $I->seeInField('input#setup_direct_debit_type_post_code', 'CH6 2NL');
    }

    /**
     * @group directDebit
     */
    public function checkFormValidation(AcceptanceTester $I)
    {
        $I->amOnPage('/my-profile/direct-debit');
        $I->clickWithLeftButton('//*[@id="profile"]/div/div/div/div/div/form/input');

        // check all empty
        $I->scrollTo('ul.list-group');
        $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        $I->seeInCurrentUrl('/my-profile/direct-debit/setup');

        // check tick box (without)
        $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        $I->selectFromDropdown('#setup_direct_debit_type_bankAccountId', 3);
        $I->fillField('input#setup_direct_debit_type_amount', '50');
        $I->scrollTo('ul.list-group');
        $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        $I->seeInCurrentUrl('/my-profile/direct-debit/setup');

        // check invalid account details (account number - letter in number)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '3829000a');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (account number - invalid account)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290001');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (account number - number too long)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '382900088');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (account number - number too short)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '3829000');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (sort code - letter in number))
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '20041a');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (sort code - invalid account)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200411');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (sort code - number too long)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '2004155');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // check invalid account details (sort code - number too short)
        //      $I->amOnPage('/my-profile/direct-debit-setup');
        //      $I->scrollTo('input#setup_direct_debit_type_address_3');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        //      $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '20041');
        //      $I->fillField('input#setup_direct_debit_type_amount', '50');
        //      $I->scrollTo('ul.list-group');
        //      $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        //      $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (IBAN - Invalid IBAN)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR763000400003123456789014Q');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRPP');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (IBAN - IBAN too long)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR76300040000312345678901433');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRPP');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (IBAN - IBAN too short)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR763000400003123456789014');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRPP');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (BIC/SWIFT - Invalid BIC/SWIFT)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR7630004000031234567890143');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRP5');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (BIC/SWIFT - BIC/SWIFT too long)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR7630004000031234567890143');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRPPP');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // // check invalid account details (BIC/SWIFT - BIC/SWIFT too short)
        // $I->amOnPage('/my-profile/direct-debit-setup');
        // $I->scrollTo('input#setup_direct_debit_type_address_3');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        // $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        // $I->fillField('input#setup_direct_debit_type_accountIban', 'FR7630004000031234567890143');
        // $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRP');
        // $I->fillField('input#setup_direct_debit_type_amount', '50');
        // $I->scrollTo('ul.list-group');
        // $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // $I->dontSeeElement('body.ddm-confirmation');

        // check invalid ammount (too small)
        $I->amOnPage('/my-profile/direct-debit/setup');
        $I->scrollTo('input#setup_direct_debit_type_address_3');
        $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //      $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        $I->selectFromDropdown('#setup_direct_debit_type_bankAccountId', 3);
        $I->fillField('input#setup_direct_debit_type_amount', '49.99');
        $I->scrollTo('ul.list-group');
        $I->seeElement('div#belowMinPrompt');
        $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        $I->dontSeeElement('body.ddm-confirmation');
        $I->scrollTo('div.color-box.break.mb-3');
        $I->seeElement('input.input_error_highlight');
    }

    /**
     * @group directDebit
     */
    public function checkPaymentDates(AcceptanceTester $I)
    {
        // check that the payments are not all lined up for same date
        $I->amOnPage('/my-profile/direct-debit');
        $I->clickWithLeftButton('//*[@id="profile"]/div/div/div/div/div/form/input');
        $I->scrollTo('input#setup_direct_debit_type_address_3');
        $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        //  $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
        //       $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //       $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        $I->selectFromDropdown('#setup_direct_debit_type_bankAccountId', 3);
        $I->fillField('input#setup_direct_debit_type_amount', '50');
        $I->scrollTo('#profile h4:nth-child(15)'); // CHANGE TO 16 WHEN EU DD IS BACK
        $secondDate = $I->grabTextFrom('h6#third-payment-date');
        $thirdDate = $I->grabTextFrom('h6#second-payment-date');
        $I->assertNotEquals($secondDate, $thirdDate);
    }

    // /**
    //  * @group directDebit
    //  */
    //  public function checkEUAccount(AcceptanceTester $I)
    //  {
    //     // include 50p check
    //     // include mandate check
    //     $I->amOnPage('/my-profile/direct-debit-setup');
    //     $I->scrollTo('input#setup_direct_debit_type_address_3');
    //     $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
    //     $I->scrollTo('input#setup_direct_debit_type_addressCheck');
    //     $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_1');
    //     $I->fillField('input#setup_direct_debit_type_accountIban', 'FR7630004000031234567890143');
    //     $I->fillField('input#setup_direct_debit_type_sortBic', 'CRLYFRPP');
    //     $invAmount = 50.00;
    //     $I->fillField('input#setup_direct_debit_type_amount', $invAmount);
    //     // check for the 50p transaction fee
    //     $I->scrollTo('#profile h4:nth-child(16)');
    //     $payment = $I->grabTextFrom('span#first-payment-amount');
    //     $cleanpayment = str_replace(array('£'),'',$payment);
    //     $I->assertEquals(($invAmount+0.60), $cleanpayment);
    //     $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
    //     // check directed to mandate
    //     $I->SeeElement('body.ddm-confirmation');
    //  }

    /**
     * @group directDebit
     */
    public function checkCanCreateAndConfirmMandate(AcceptanceTester $I)
    {
        // include 60p check
        // include mandate check
        $I->amOnPage('/my-profile/direct-debit/setup');
        $I->scrollTo('input#setup_direct_debit_type_address_3');
        $I->clickWithLeftButton('input#setup_direct_debit_type_addressCheck');
        $I->scrollTo('input#setup_direct_debit_type_addressCheck');
        // $I->clickWithLeftButton('input#setup_direct_debit_type_bankAccountType_0');
        //      $I->fillField('input#setup_direct_debit_type_accountIban', '38290008');
        //      $I->fillField('input#setup_direct_debit_type_sortBic', '200415');
        $I->selectFromDropdown('#setup_direct_debit_type_bankAccountId', 3);
        $invAmount = 50.00;
        $I->fillField('input#setup_direct_debit_type_amount', $invAmount);
        // check for the 50p transaction fee
        $I->scrollTo('#profile h4:nth-child(15)'); // CHANGE TO 16 WHEN EU DD IS BACK
        $payment = $I->grabTextFrom('span#first-payment-amount');
        $cleanpayment = str_replace(['£'], '', $payment);
        $I->assertEquals(($invAmount + 0.60), $cleanpayment);
        $I->clickWithLeftButton('button#setup_direct_debit_type_submit');
        // check directed to mandate
        $I->SeeElement('body.ddm-confirmation');
        $I->clickWithLeftButton('label[for="Authorization"]');
        $I->clickWithLeftButton('button.btn-confirm');
        $I->seeInCurrentUrl('/my-profile/direct-debit');
        $I->seeElement('div#links-card');
    }

    /**
     * @group directDebit
     */
    public function checkDDNavPages(AcceptanceTester $I)
    {
        $I->amOnPage('/my-profile/direct-debit');
        $I->seeElement('div#links-card');
        $I->clickWithLeftButton('a#detailsLink');
        $I->amOnPage('/my-profile/direct-debit/details');
        $I->seeElement('div#links-card');
        $I->clickWithLeftButton('a#cancelLink');
        $I->amOnPage('/my-profile/direct-debit/cancel');
        $I->seeElement('div#links-card');
        $I->clickWithLeftButton('a#summaryLink');
    }

    /**
     * @group directDebitc
     */
    public function checkCanCancellDD(AcceptanceTester $I)
    {
        $I->amOnPage('/my-profile/direct-debit/cancel');
        $I->seeElement('div#links-card');
        $I->clickWithLeftButton('button[data-target="#confirmModal"]');
        $I->waitForElementClickable('button#form_cancel');
        $I->clickWithLeftButton('button#form_cancel');
        $I->waitForText('Your Direct Debit has been cancelled');
        $I->amOnPage('/logout');
    }
}
