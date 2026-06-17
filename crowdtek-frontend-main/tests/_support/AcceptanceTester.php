<?php

use Facebook\WebDriver\WebDriverKeys;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    // for multi-session testing
    use \Codeception\Lib\Actor\Shared\Friend;

    // https://docs.mangopay.com/guide/testing-payments
    public const CARD_CHALLENGE = "4970105181818183"; // Challenge flow for high amounts > £40
    public const CARD_FRICTIONLESS = "4970107111111119"; // Frictionless for amounts <= £40

    public const SCA_PIN = "502100";
    public const SCA_PHONE_CODE = "702100";
    public const SCA_TEST_PHONE_NUMBER = "611111111";
    public const MANGOPAY_TEST_BANK_ACCOUNT = "55779911";
    public const MANGOPAY_TEST_SORT_CODE = "200000";
    public const MANGOPAY_TEST_IBAN = "FR7630004000031234567890143";
    public const MANGOPAY_TEST_BIC = "BNPAFRPP";

    public $reg_user_name = 'ben.autotest@crowdtek.co.uk';
    public $approved_investor_1 = 'holly.autotest@helpmewithit.com';
    public $approved_investor_2 = 'jim.autotest@crowdtek.co.uk';
    public $approved_investor_low_balance = 'ed.autotest@red.com';
    public $unapproved_investor = 'email-not-verified@crowdtek.co.uk';
    public $admin_user_name = 'admin@crowdtek.co.uk';
    public $admin_user_password = 'Password123!';
    public $unregistered_user = 'email-not-verified@crowdtek.co.uk';

    public const string BEN_MP_EMAIL = "ben.auto@test.yielderverse.co.uk";
    public const string HOLLY_MP_EMAIL = "holly.auto@test.yielderverse.co.uk";
    public const string JIM_MP_EMAIL = "jim.auto@test.yielderverse.co.uk";
    public const string LOW_BALANCE_MP_EMAIL = "anne.auto@test.yielderverse.co.uk";

    public $limit = 10;
    public $offset = 0;
    public $search = null;
    public $orderby = 'create_date';
    public $sort = 'desc';
    public $page_wait_time = 5;
    public $animation_time = 1.25;

    public $cmsCheck = true; //default true

    public $indietest = false; //default false

    public $salesforce_params = [
        "id" => ($_ENV["SALESFORCE_CONSUMER_KEY"] ?? getenv("SALESFORCE_CONSUMER_KEY")) ?: "",
        "secret" => ($_ENV["SALESFORCE_CONSUMER_SECRET"] ?? getenv("SALESFORCE_CONSUMER_SECRET")) ?: "",
        "refresh_token" => ($_ENV["SALESFORCE_REFRESH_TOKEN"] ?? getenv("SALESFORCE_REFRESH_TOKEN")) ?: "",
        "user_object" => "Contact",
    ];

    public $oauth2Params = [
        "clientId" => ($_ENV["OAUTH2_CLIENT_ID"] ?? getenv("OAUTH2_CLIENT_ID")) ?: "",
        "clientSecret" => ($_ENV["OAUTH2_CLIENT_SECRET"] ?? getenv("OAUTH2_CLIENT_SECRET")) ?: "",
    ];

    public $new_user_yorran = [
        "firstname" => "Yorran",
        "lastname" => "Davies",
        "email" => "yorran@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $new_user_yalta = [
        "email" => "yalta_1signupd@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $verified_user_yorran = [
        "email" => "yorran_2sverified@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $user_henley = [
        "email" => "henley_3declaredqs@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $user_bryson = [
        "email" => "bryson_3declaredqf@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $assessed_user_patton = [
        "firstname" => "Harriet",
        "lastname" => "Patton",
        "email" => "patton_4user@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $assessed_user_sonnet = [
        "firstname" => "Jeremy",
        "lastname" => "Sonnet",
        "email" => "sonnet_4busi@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ];
    public $user_hamlin = [
        "email" => "hamlin_5complied@cb.co.uk",
        "password" => "Amsterdam1209",
    ];

    public function loginWithName($user, $usesnap = true)
    {
        $I = $this;
        if ($I->loadSessionSnapshot($user["email"]) && $usesnap) {
            try {
                // If snapshot not available, will be sent to login screen
                $I->amOnPage('/login');
                $I->seeElement('input', ['name' => '_username']);
            } catch (\Exception $e) {
                // If snapshot available, won't be on login screen
                // Should be redirected to the homepage
                $I->wait(1);
                return;
            }
        }
        $I->amOnPage('/login');
        try {
            $I->seeElement('input', ['name' => '_username']);
        } catch (\Exception $e) {
            $I->amOnPage('/logout');
            $I->amOnPage('/login');
        }
        $I->clearMailCatcher();
        $I->fillField(['name' => '_username'], $user["email"]);
        $I->fillField(['name' => '_password'], $user["password"]);
        $I->click('button[type=submit]');
        $I->loginMfaStep();
        $I->saveSessionSnapshot($user["email"]);
        // $I->checkWalletScaVerification($user["email"]);
    }
    public function loginWithCredentials($email, $password, $usesnap = true, bool $skipScaCheck = true)
    {
        $I = $this;
        if ($usesnap && $I->loadSessionSnapshot($email)) {
            try {
                // If snapshot not available, will be sent to login screen
                $I->amOnPage('/login');
                $I->seeElement('input', ['name' => '_username']);
            } catch (\Exception $e) {
                // If snapshot available, won't be on login screen
                // Should be redirected to the homepage
                $I->wait(1);
                return;
            }
        }
        $I->amOnPage('/login');
        try {
            $I->seeElement('input', ['name' => '_username']);
        } catch (\Exception $e) {
            $I->amOnPage('/logout');
            $I->amOnPage('/login');
        }
        $I->clearMailCatcher();
        $I->fillField(['name' => '_username'], $email);
        $I->fillField(['name' => '_password'], $password);
        $I->click('button[type=submit]');
        $I->loginMfaStep();
        $I->saveSessionSnapshot($email);

        // Only attempt the SCA bypass on select users
        if (!$skipScaCheck) {
            $scaEmail = match ($email) {
                $this->reg_user_name => $I::BEN_MP_EMAIL,
                $this->approved_investor_1 => $I::HOLLY_MP_EMAIL,
                $this->approved_investor_2 => $I::JIM_MP_EMAIL,
                $this->approved_investor_low_balance => $I::LOW_BALANCE_MP_EMAIL,
                default => $email,
            };
            $I->checkWalletScaVerification($scaEmail);
        }
    }

    public function loginMfaStep()
    {
        $I = $this;
        try {
            $I->waitForText('Two-Factor Authentication', 2.4);
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $I->getMailcatcherUrl() . '/messages/1.plain',
                CURLOPT_RETURNTRANSFER => true,
                // CURLOPT_USERPWD => 'admin:London123',
            ]);
            $response = curl_exec($curl);
            curl_close($curl);
            $mfaCode = explode(':', $response)[1];
            $I->fillField(['name' => '_auth_code'], $mfaCode);
            $I->click('input[type=submit]');
            $I->wait(1);
        } catch (\Exception $e) {
            // user does not have mfa email enabled
        }
    }

    public function getMailcatcherUrl()
    {
        $I = $this;
        if ($I->getScenario()->current('env') == 'local-chrome') {
            return 'http://localhost:1080';
        } elseif ($I->getScenario()->current('env') == 'docker-chrome') {
            return 'http://mailer:1080';
        } elseif ($I->getScenario()->current('env') == 'ci-independent-chrome') {
            return 'http://mailer:1080';
        } else {
            return 'https://admin:London123!@mailcatcher.yielderverse.co.uk';
        }
    }

    public function getBackendUrl()
    {
        $I = $this;
        if ($I->getScenario()->current('env') == 'local-chrome') {
            return 'http://local.qa.com';
        } elseif ($I->getScenario()->current('env') == 'docker-chrome') {
            return 'http://api.dev.local';
        } elseif ($I->getScenario()->current('env') == 'ci-independent-chrome') {
            return 'http://backoffice';
        } else {
            return 'https://test-back.yielderverse.co.uk';
        }
    }

    public function clearMailCatcher()
    {
        $I = $this;
        //Clear mail Catcher via API - Http DELETE on /messages
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getMailcatcherUrl() . '/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            // CURLOPT_USERPWD => 'admin:London123',
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
    }

    public function getAppBaseHost()
    {
        $config = \Codeception\Configuration::suiteSettings("acceptance", \Codeception\Configuration::config());
        $baseUrl = $config["env"][$this->getScenario()->current('env')]["modules"]["config"]["WebDriver"]["url"];
        // echo PHP_EOL . $baseUrl . PHP_EOL;
        return $baseUrl;
    }

    /**
     * resetBaseHost will set the base host to the url given in config of the acceptance suite
     */
    public function resetBaseHost()
    {
        $I = $this;
        $I->amOnUrl($this->getAppBaseHost());
    }

    public function createUserAtStage($stage, $inv_type = null, $seed = null): string
    {
        // if seed is left empty, use stage as the seed
        if (!$seed) {
            $seed = $stage;
        }

        $I = $this;

        // if user with seed already exists return
        if ($I->loadSessionSnapshot(sqs($seed) . $I->new_user_yorran["email"])) {
            return sqs($seed) . $I->new_user_yorran["email"];
        }

        $I->clearMailCatcher();
        $I->amOnPage('/onboarding/sign-up');


        $I->fillField(['id' => 'signUpUser_firstname'], $I->new_user_yorran["firstname"]);
        $I->fillField(['id' => 'signUpUser_lastname'], $I->new_user_yorran["lastname"]);
        $I->fillField(['id' => 'signUpUser_email'], sqs($seed) . $I->new_user_yorran["email"]);
        $I->fillField(['id' => 'signUpUser_password_first'], $I->new_user_yorran["password"]);
        $I->fillField(['id' => 'signUpUser_password_second'], $I->new_user_yorran["password"]);
        $I->scrollTo('form input[type="submit"]', 0, -25);
        $I->click(['id' => 'btn_continue']);

        $I->wait(1); // deal with some flakiness with webdriver page loads
        $I->scrollTo(['id' => 'signUpUser_term_service_accepted']);
        $I->clickWithLeftButton('input#signUpUser_term_service_accepted');
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->click(['id' => 'btn_continue']);

        $I->saveSessionSnapshot(sqs($seed) . $I->new_user_yorran["email"]);

        if ($stage == 1) {
            return sqs($seed) . $I->new_user_yorran["email"];
        }

        $I->amOnUrl($I->getMailcatcherUrl() . '/messages/1.html');
        $I->wait(1);
        $I->click('Verify Email');
        $I->resetBaseHost();

        if ($stage == 2) {
            return sqs($seed) . $I->new_user_yorran["email"];
        }

        // if investor type is left empty, default to everyday
        // if (empty($inv_type)) {
        //     $inv_type = "userPreference_investor_type_0";
        // } else {
        //     $inv_type = "userPreference_investor_type_" . (string) $inv_type;
        // }

        $I->amOnPage('/onboarding/regulation-preference');
        $I->click('//button[text()="Next"]');
        $I->wait(1);
        // $I->clickWithLeftButton('//input[@id="' . $inv_type . '"]/parent::*');
        $I->scrollTo('//form[@name="userPreference"]//button[@type="submit"]');
        $I->click('//button[text()="Next"]');
        $I->wait(1);

        if ($inv_type == '1') {
            // Sophisticated
            $I->selectOption('input[name="user_categorisation[category]"]', 'Sophisticated');
            $I->scrollTo('form button[type="submit"]', 0, -150);
            $I->click('Continue');
            $I->wait(1);
            $I->selectOption('input[name="category_sophisticated[experience]"]', 'unlisted investments xp');
            $I->fillField('#category_sophisticated_info', 5);
            $I->scrollTo('form button[type="submit"]', 0, -150);
            $I->click('Confirm Investor Type');
        } elseif ($inv_type == '2') {
            // hnw
            $I->selectOption('input[name="user_categorisation[category]"]', 'High net worth');
            $I->scrollTo('button[type="submit"]', 0, -150);
            $I->click('Continue');
            $I->wait(1);
            $I->selectOption('input[name="category_hnw[hnwType]"]', 'income');
            $I->fillField('#category_hnw_amount', '300000');
            $I->scrollTo('button[type="submit"]', 0, -150);
            $I->click('Confirm Investor Type');
        } else {
            // restricted user
            $I->selectOption('input[name="user_categorisation[category]"]', 'Restricted');
            $I->scrollTo('form button[type="submit"]', 0, -150);
            $I->click('Continue');
            $I->wait(1);
            $I->fillField('#category_restricted_last12M', 4);
            $I->fillField('#category_restricted_next12M', 6);
            $I->scrollTo('form button[type="submit"]', 0, -150);
            $I->click('Confirm Investor Type');
        }

        if ($stage == 3) {
            return sqs($seed) . $I->new_user_yorran["email"];
        }

        $I->wait(1);
        $I->amOnPage('/onboarding/assessment');

        $I->wait(1);
        $I->click('Start Test');
        $I->selectOption('input[name="user_assessment[0][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[1][choice]"]', '0');
        $I->selectOption('input[name="user_assessment[2][choice]"]', '1');
        $I->click('Submit Answers');

        if ($stage == 4) {
            return sqs($seed) . $I->new_user_yorran["email"];
        }

        $I->click('Next');
        $I->selectOption(['id' => 'userInformation_honorific_prefix'], 'Mr');
        $I->selectOption(['id' => 'userInformation_gender'], 'MALE');
        $I->fillField(['id' => 'userInformation_firstname'], $I->assessed_user_sonnet["firstname"]);
        $I->fillField(['id' => 'userInformation_lastname'], $I->assessed_user_sonnet["lastname"]);
        $I->selectOption(['id' => 'userInformation_birthDate_day'], '08');
        $I->selectOption(['id' => 'userInformation_birthDate_month'], '12');
        $I->selectOption(['id' => 'userInformation_birthDate_year'], '1980');
        $I->selectOption(['id' => 'userInformation_nationality'], 'United Kingdom');
        $I->click('//div[@id="accordion1"]//a[text()="NEXT"]');

        $I->selectOption(['id' => 'userInformation_address_country'], 'United Kingdom');
        $I->fillField(['id' => 'userInformation_address_postal_code'], 'E1 2PT');
        $I->fillField(['id' => 'userInformation_address_address1'], '4 Kings Road');
        $I->fillField(['id' => 'userInformation_address_city'], 'London');
        $I->scrollTo('//div[@id="accordion2"]//a[text()="NEXT"]');
        $I->click('//div[@id="accordion2"]//a[text()="NEXT"]');

        // Select the French phone number prefix for SCA test phone number
        $I->clickWithLeftButton('#user-phone-number div.selected-flag');
        $I->clickWithLeftButton('#user-phone-number li[data-country-code="fr"]');
        $I->fillField(['id' => 'userInformation_phone1'], $I::SCA_TEST_PHONE_NUMBER);
        // $I->fillField(['id' => 'userInformation_phone1'], '02072054650');
        $I->fillField(['id' => 'userInformation_phone2'], '07911123456');
        $I->seeElement(['id' => 'userInformation_info_referral']);
        $I->click('//div[@id="accordion4"]//a[text()="NEXT"]');

        $I->attachFile(['id' => 'userInformation_document_proof_of_id'], 'specimen_passport.jpg');
        $I->attachFile(['id' => 'userInformation_document_proof_of_address'], 'org_img.jpg');
        $I->scrollTo('form button[type="submit"]', 0, -150);
        $I->click('//form[contains(@name, "userInformation")]//button[@type="submit"]');

        $I->waitForText("setup Strong Customer Authentication");
        $I->click("Start SCA Setup");
        $I->completeScaEnrollment(sqs($seed) . $I->new_user_yorran["email"]);
        return sqs($seed) . $I->new_user_yorran["email"];
    }

    // reusable function to simply fill in the card payin form
    public function submitCardPayinForm($card_num, $amount = false)
    {
        $I = $this;
        // if amount is given, fill in amount, otherwise leave as is
        if ($amount !== false) {
            $I->fillField(['id' => 'amount'], $amount);
        }
        $I->fillField(['id' => 'cardNumber'], $card_num);
        $I->fillField(['id' => 'cardCvx'], '787');
        $I->selectOption(['id' => 'expiryDatePicker_month'], '6');
        $I->selectOption(['id' => 'expiryDatePicker_year'], idate("Y") + 2);
        $I->scrollTo('#lblcardNumber');
        $I->click(['id' => 'addFunds']);
    }

    // this function tries both test card numbers for 3DS transactions without raising an exception on fail
    public function attemptAddFundsByCard($amount = "100")
    {
        /**
         * Attempt to add funds with the challenge card with a 3DS action expected
         * Only one test card available so no retries supported
         */
        $this->submitCardPayinForm(self::CARD_CHALLENGE, $amount);

        try {
            $this->waitForText("3D Secure Test Page");
            $this->click("Success");
            return 1;
        } catch (Exception $e) {
            // Currently no alternate challenge cards available
            return 0;

            // $this->amOnPage('/add-funds-new');
            // // try with the other card
            // $this->submitCardPayinForm(self::CARD_CHALLENGE, $amount);
            // try {
            //     $this->waitForElementVisible('#authform form button#yes', 5);
            //     $this->seeElement('#authform form button#yes');
            //     $this->click('Yes');
            //     return 2; // card 2
            // } catch (Exception $e) {
            //     // if fail again - i.e. both cards blocked, just do nothing
            //     // could add debug message here, but requires codeception to be run in debug mode: --debug
            //     return 0; // neither card worked
            // }
        }
    }

    public function addFunds($amount = 110)
    {
        $I = $this;

        $I->amOnPage('/add-funds-new');
        $I->waitForElement("#amount");
        $payin_state = 0;
        if ($amount < 20) {
            $I->submitCardPayinForm(self::CARD_FRICTIONLESS, $amount);
        } else {
            $payin_state = $I->attemptAddFundsByCard($amount);
        }

        // Only worth checking for 3DS transactions where cards can be blocked
        if ($payin_state) {
            try {
                $I->waitForText("Successfully added");
                return true;
            } catch (Exception $e) {
                // handle blocked cards but check things look okay on our end
                // wait for redirecting to finish
                $I->wait(4);
                // match regexp - the transaction ID can either be the older 8 digit or the newer longer ULIDs
                // So we'll accept any non-whitespace string with more than 8 characters for compatibility
                // Really just care whether or not the id is empty
                $I->seeCurrentUrlMatches('~^/my-profile/payin-success(\?transactionId=\S{8,})?~');
                return false;
            }
        }
    }

    public function createPayOut($amount = 50)
    {
        $I = $this;

        $I->amOnPage('/withdraw-funds');
        $I->waitForText('Create A Payout');

        $I->selectOption('#bank_account_withdrawal_account', 'bankacc_m_01HW5RPBZ3JHTXG97AEV316761');
        $I->fillField('#bank_account_withdrawal_amount', $amount);

        $I->click('Submit');

        $I->waitForText('Create payout successful');
    }

    public function investInOffering($amount, string $offering_url, string $userEmail)
    {
        $I = $this;

        /**
         * Go to url (it's mandatory!)
         */
        $I->amOnPage($offering_url);
        $I->wait(1);
        $I->waitForText('To invest in this asset');
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest', 'form.invest-steps');

        $I->waitForElement("#investment-total-value");
        $I->fillField('#investment-total-value', $amount);
        // defocus amount field to trigger js
        $I->clickWithLeftButton(['css' => '#investment_retail_numberOfShares']);
        $I->scrollTo('#total-shares-input', 0, -40);
        $I->click('Invest Now', 'form');

        try {
            // $I->waitForText('investment was successfully submitted', 5);
            $I->wait(5);
            $I->seeCurrentUrlEquals('/my-investments');
        } catch (\Throwable $th) {
            // Likely on SCA verification
            $I->completeScaVerification($userEmail);
            // $I->waitForText('investment was successfully submitted', 15);
            // Wait up to 15 seconds until redirected to /my-investments
            $I->wait(5);
            try {
                $I->seeCurrentUrlEquals('/my-investments');
            } catch (\Throwable $th) {
                $I->wait(10);
                $I->seeCurrentUrlEquals('/my-investments');
            }
        }
    }

    public function investInAsset($amount, string $assetId, string $userEmail)
    {
        $I = $this;

        /**
         * Go to url (it's mandatory!)
         */
        $I->amOnPage("/properties/{$assetId}");
        $I->wait(1);
        $I->waitForText('To invest in this asset');
        $I->clickWithLeftButton(['css' => 'label[for="docsReviewed"]']);
        $I->click('Invest', 'form.invest-steps');
        $I->wait(1);

        $I->waitForElement("#investment-total-value");
        $I->fillField('#investment-total-value', $amount);
        // defocus amount field to trigger js
        $I->clickWithLeftButton(['css' => '#investment_retail_numberOfShares']);
        $I->scrollTo('#total-shares-input', 0, -40);
        $I->click('Invest Now', 'form');

        $this->checkAndHandleInvestmentSca($userEmail);
    }

    public function completeScaVerification(string $email): void
    {
        $I = $this;

        try {
            $I->waitForText('Start your verification');
        } catch (\Throwable $th) {
            // SCA not loading - may not be required
            return;
        }

        $I->click('Start your verification');
        $I->checkForScaProxyConsent();
        $I->checkForScaPassKey();

        $I->performScaStep($I->checkScaStep(), $email);
        $I->checkForScaPassKey();
        $I->performScaStep($I->checkScaStep(), $email);
        $I->checkForScaPassKey();
        $I->performScaStep($I->checkScaStep(), $email);
        $I->checkForScaPassKey();
    }

    public function performScaStep(string $step, ?string $email = null): void
    {
        match ($step) {
            "email" => $this->completeScaEmailStep($email),
            "pin" => $this->completeScaPinStep(),
            "phone" => $this->completeScaPhoneStep(),
            default => 'no action',
        };
        if ($step == "phone") {
            $this->checkScaVerifyPhoneIssue();
        }
    }

    /**
     * Brute force identifying which SCA step
     * @return string|null
     */
    public function checkScaStep(): ?string
    {
        $this->wait(1.25);
        try {
            $this->see("email address");
            return "email";
        } catch (\Throwable $th) {
            // Not email
        }
        try {
            $this->see("Enter PIN Code");
            return "pin";
        } catch (\Throwable $th) {
            // Not pin
        }
        try {
            $this->see("Enter 6-digit");
            return "phone";
        } catch (\Throwable $th) {
            // Not phone
        }
        try {
            $this->see("Use your passkey");
            return "passkey";
        } catch (\Throwable $th) {
            // Not phone
        }
        return null;
    }

    public function completeScaEmailStep(string $email): void
    {
        $this->fillField("input[type=email]", $email);
        $this->click("Continue");
    }

    public function completeScaPinStep(): void
    {
        foreach (range(1, 6) as $digit) {
            $this->fillField("(//input[@autocomplete])[$digit]", self::SCA_PIN[$digit - 1]);
        }
    }

    public function completeScaPhoneStep(): void
    {
        foreach (range(1, 6) as $digit) {
            $this->fillField("(//input[@autocomplete])[$digit]", self::SCA_PHONE_CODE[$digit - 1]);
        }
    }

    public function checkForScaPassKey(): void
    {
        $this->wait(2);
        try {
            $this->see("Use your passkey");
            // $this->click('Skip');
            $this->click('Continue');
            $this->cancelPopup();
        } catch (\Throwable $th) {
            // Not prompted for passkey
            // echo PHP_EOL . "Skipping passkey";
        }
    }

    public function checkForScaProxyConsent(): void
    {
        $this->wait(2);
        try {
            $this->see("on your behalf");
            $proxyActions = $this->grabMultiple('input[type=checkbox]');
            foreach (range(1, count($proxyActions)) as $actionIndex) {
                $this->checkOption("(//input[@type='checkbox'])[{$actionIndex}]");
            }
            $this->click('Save & continue');
        } catch (\Throwable $th) {
            // Not prompted for sca proxy consent
            // echo PHP_EOL . "Skipping passkey";
        }
    }

    // https://gitlab.com/yielders2/crowdtek-frontend/-/issues/1248
    public function checkScaEnrollPhoneIssue(bool $reload = false): void
    {
        $this->wait(2);
        try {
            $this->see("to validate challenge in current status");
            $this->wait(33);
            $this->click('Resend code');
            $this->wait(2);
            $this->click('Continue');
            $this->waitForText('Enter 6-digit');
            $this->completeScaPhoneStep();
        } catch (\Throwable $th) {
            // No phone number code issue
        }
    }

    // https://gitlab.com/yielders2/crowdtek-frontend/-/issues/1261
    public function checkScaVerifyPhoneIssue(bool $reload = false): void
    {
        $this->wait(2);
        try {
            $this->see("to validate challenge in current status");
            $this->reloadPage();
            $this->waitForText('Start your verification');
            $this->click('Start your verification');
            $this->waitForText('Enter 6-digit');
            $this->wait(33);
            $this->click('Resend code');
            $this->wait(2);
            $this->completeScaPhoneStep();
        } catch (\Throwable $th) {
            // No phone number code issue
        }
    }

    public function checkAndHandleInvestmentSca(string $userEmail): void
    {
        try {
            // $I->waitForText('investment was successfully submitted', 5);
            $this->wait(5);
            $this->seeCurrentUrlEquals('/my-portfolio');
        } catch (\Throwable $th) {
            // Likely on SCA verification
            $this->completeScaVerification($userEmail);
            // $this->waitForText('investment was successfully submitted', 15);
            // Wait up to 15 seconds until redirected to /my-portfolio
            $this->wait(5);
            try {
                $this->seeCurrentUrlEquals('/my-portfolio');
            } catch (\Throwable $th) {
                $this->wait(10);
                $this->seeCurrentUrlEquals('/my-portfolio');
            }
        }
    }

    public function completeScaEnrollment(string $email): void
    {
        $I = $this;

        $I->waitForText('Start your verification');
        // $I->clickWithLeftButton('input[type=checkbox]');
        $I->checkOption('input[type=checkbox]');
        $I->click('Start your verification');
        $I->checkForScaProxyConsent();
        $I->checkForScaPassKey();

        $this->completeScaEmailStep($email);
        $I->checkForScaPassKey();

        $I->waitForText('Define a 6-digit PIN code');
        $I->fillField("(//input[@autocomplete])[1]", self::SCA_PIN);
        $I->fillField("(//input[@autocomplete])[2]", self::SCA_PIN);
        $I->click('Save');
        $I->checkForScaPassKey();

        $I->waitForText("Enter your mobile number");
        // Clearfield doesn't seem to work
        // $I->clearField("input[type='tel']");
        // Ensure field is clear by using key presses
        // Phone numbers are usually no longer than 10 digits
        $deleteKeyPresses = array_fill(0, 10, WebDriverKeys::DELETE);
        $I->pressKey(
            "input[type='tel']",
            WebDriverKeys::HOME,
            ...$deleteKeyPresses,
        );
        $I->fillField("input[type='tel']", self::SCA_TEST_PHONE_NUMBER);
        // Help to debug if phone number was entered correctly
        $I->seeInField("input[type='tel']", self::SCA_TEST_PHONE_NUMBER);
        $I->click('Continue');
        $I->waitForText('Enter 6-digit');
        foreach (range(1, 6) as $digit) {
            $I->fillField("(//input[@autocomplete])[$digit]", self::SCA_PHONE_CODE[$digit - 1]);
        }
        $I->checkScaEnrollPhoneIssue();
        $I->checkForScaPassKey();
    }

    public function sellShares(int $assetId, int $shares = 1): void
    {
        $I = $this;

        $I->amOnPage("/my-portfolio/positions/{$assetId}");
        $I->click("Sell", '#portfolio-info');
        $I->scrollTo("#relisting_numberOfShares", 0, -80);
        $I->fillField("#relisting_numberOfShares", $shares);
        $I->scrollTo("#relisting_acceptTerms", 0, -80);
        $I->checkOption("#relisting_acceptTerms");
        $I->click("Submit Sell Order");
        $I->waitForText("sell order was successfully submitted");
        $I->wait(1);
    }

    public function sellInvestment($amount, $investment_id = 0)
    {
        $I = $this;

        if ($investment_id) {
            //go straight to the sell page - shortcut!
            $I->amOnPage('/my-investments/sell-my-investment/' . $investment_id);
        } else {
            $I->amOnPage('/my-investments');
            $I->wait(1);
            $I->scrollTo('#pills-investments table', 0, -100);
            $I->click('#pills-investments table tbody tr:first-child td[data-label="Sell Investment"] a');
            $I->wait($I->animation_time);
            $I->clickWithLeftButton('#check_confirm_sell_investment');
            $I->click('#sell_my_invest_btn');
        }
        /**
         * Fill in $amount to sell
         * Submit
         * Don't publish, do that manually, cos you need the admin token! Avoid repeat admin token calls
         */
        $I->wait(1);
        $I->seeInCurrentUrl('/my-investments/sell-my-investment/');
        $I->scrollTo('#relisting table');
        $I->fillField(['id' => 'sale-value'], $amount);
        $I->click('Proceed & Sell my investment');
        $I->wait(1);
        $I->seeInCurrentUrl('/my-investments');
    }

    public function organizationFormFill($save_state = null)
    {
        $I = $this;

        if ($I->loadSessionSnapshot('NewOrganizationForm')) {
            return;
        }

        $I->amOnPage('/add-asset');
        $I->waitForText('Add Property');

        $I->scrollTo('.add-property-block');
        $I->waitForText('Organization Type');

        //filling organization mandatory fields
        $I->fillField('#organization_type_display_name', 'TestOrganization' . $I->getTime());
        $I->scrollTo('#organization_type_address_country_chosen > a:nth-child(1) > span:nth-child(1)');
        $I->fillField('#organization_type_info_funding_goal', '1000');
        $I->fillField('#organization_type_info_investment_term', '24');
        $I->fillField('#organization_type_info_stamp_duty_user', $I->admin_user_name);

        //saving the current fields if needed
        if ($save_state === true) {
            $I->saveSessionSnapshot('NewOrganizationForm');
        }
    }

    public function offerFormFill($save_state = null)
    {
        $I = $this;

        if ($I->loadSessionSnapshot('NewOfferForm')) {
            return;
        }

        $I->amOnPage('/add-asset');
        $I->waitForText('Add Property');
        $I->scrollTo('.banner-caption > h1:nth-child(1)');
        $I->click('Add New Offering');

        $I->scrollTo('#offering-tab');
        $I->waitForText('Choose your Organization');

        //filling organization mandatory fields
        $I->fillField('#offering_type_name', 'TestOffer' . $I->getTime());
        $I->fillField('#offering_type_funding_goal', '300');
        $I->attachFile('#offering_type_SPVFile', 'org_img.jpg'); //change file to org_img.jpg

        $I->click('#offering_type_organization_id_chosen > a:nth-child(1)');
        $I->click('li.active-result:last-child');

        //saving the current fields if needed
        if ($save_state === true) {
            $I->saveSessionSnapshot('NewOfferForm');
        }
    }

    // helpers for checking data submission to backoffice successfull
    public function getUserToken($email, $password)
    {
        $I = $this;
        // gets a JWT token for a particularly user
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => [
                'grant_type' => 'password',
                'client_id' => $I->oauth2Params["clientId"],
                'client_secret' => $I->oauth2Params["clientSecret"],
                'username' => $email,
                'password' => $password,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        return $rsp_array['access_token'] ?? false;
    }

    public function getUserInfoByAPI($token)
    {
        $I = $this;
        // get user info using token from getUserToken
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/v1/yielders/self",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        if ($rsp_array['status'] == 200) {
            return $rsp_array['data']['user'];
        } else {
            return false;
        }
    }

    public function settleInvestment($investment_id, $admintoken)
    {
        $I = $this;
        $I->wait(4);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/v1/yielders/investments/" . $investment_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => '{"life_cycle_stage":4}',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $admintoken,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        if ($rsp_array['status'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    public function getLastOfferingId($admintoken, $position = 1)
    {
        $I = $this;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/v1/yielders/offerings" . "?limit=60",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $admintoken,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        $lastOffering = $rsp_array['data']['list'][($position - 1)];

        // echo "\nThe last offering has ID: " . $lastOffering['id'] . "\n";
        return $lastOffering['id'];
    }

    public function publishOffering($offering_id, $admintoken)
    {
        $I = $this;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/v1/yielders/offerings/" . $offering_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => '{"life_cycle_stage":5}',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $admintoken,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        if ($rsp_array['status'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    public function cancelOffering($offering_id, $admintoken)
    {
        $I = $this;

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->getBackendUrl() . "/v1/yielders/offerings/" . $offering_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "PATCH",
            CURLOPT_POSTFIELDS => '{"life_cycle_stage":9}',
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $admintoken,
            ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        if ($rsp_array['status'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    // convert the user['info'] array into a easier to traverse associative array
    public function convertUserInfoToDict($user_info)
    {
        $user_info_dict = [];
        foreach ($user_info as $info) {
            $user_info_dict[$info['type']] = $info['value'];
        }
        return $user_info_dict;
    }

    public function loginToSalesforce()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://login.salesforce.com/services/oauth2/token?grant_type=refresh_token&refresh_token=' . $this->salesforce_params["refresh_token"] . '&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->salesforce_params["id"] . ":" . $this->salesforce_params["secret"]
        ]);
        $rsp = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($rsp, true);
        return $response;
    }

    /*
     * Only allow GET and DELETE so `id` is required
     *
     * We will allow tests to fail (crash) if Salesforce is down
     * We're testing the service separately
     * this allows us to identify if it's a problem with our code or Salesforce is down
     */
    public function salesforceAction($action, $object, $id)
    {
        $auth_info = $this->loginToSalesforce();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $auth_info['instance_url'] . "/services/data/v45.0/sobjects/{$object}/{$id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $action,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $auth_info['access_token'],
            ],
        ]);
        $rsp = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($rsp, true);
        return $response;
    }

    public function getTime()
    {
        $date = date_create();
        return date_timestamp_get($date);
    }

    public function generateNo($length = 3)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateRandomAddress($length = 5)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function generateRandomString($length = 500)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function selectFromDropdown($selector, $n)
    {
        $option = $this->grabTextFrom($selector . ' option:nth-child(' . $n . ')');
        $this->selectOption($selector, $option);
    }

    public function showInvestmentSummary(int $offeringId, int $amount = 1005): void
    {
        $I = $this;
        $I->amOnPage('/secondary-asset/' . $offeringId . '/overview');

        $I->scrollTo('#carouselExampleFade + *');
        $I->clickWithLeftButton('.checkClicked-1');
        $I->switchToNextTab();
        $I->closeTab();
        $I->clickWithLeftButton('.checkClicked-1');
        $I->switchToNextTab();
        $I->closeTab();

        $I->fillField('#invest_type_investment_amount', $amount);
        $I->click('#invest_click');
        $I->wait($I->animation_time);
    }

    /**
     * @return string[]
     */
    public function getUrlSegments(string $url): array
    {
        $segments = explode("/", $url);
        return array_reverse($segments);
    }

    public function submitBankAccount(
        string $accountNumber = self::MANGOPAY_TEST_BANK_ACCOUNT,
        string $sortCode = self::MANGOPAY_TEST_SORT_CODE,
        bool $gb = true,
    ): string {
        $type = $gb ? "gb" : "iban";
        $this->amOnPage("/my-profile/bank-accounts/new/{$type}");
        $this->seeLink("Cancel", '/my-profile/bank-accounts');
        $this->fillField("#bank_account_registration_accountNumber", $accountNumber);
        $this->fillField("#bank_account_registration_bic", $sortCode);
        $this->scrollTo('#bank_account_registration_bankStatement', 0, -60);
        $this->attachFile("#bank_account_registration_bankStatement", "specimen_passport.jpg");
        $this->click("Submit");

        return substr($accountNumber, -4);
    }

    public function checkWalletScaVerification(string $email): void
    {
        // Load up a fast loading page
        $this->waitForElement('a.wallet-data');
        $preCheckPage = $this->grabFromCurrentUrl();
        try {
            $this->dontSee("(£", 'a.wallet-data');
            $this->amOnPage("/my-profile/transactions");
            $this->seeElement("#sca-wallet-verification-prompt");
            $this->click("Start SCA Verification");
            $this->completeScaVerification($email);
            // Wait for redirection back to website
            $this->waitForElement('a.wallet-data');
            // Go back to the back it was on before the sca verification
            $this->amOnPage($preCheckPage);
        } catch (\Throwable $th) {
            // no wallet sca verification possible to do
        }
    }
}
