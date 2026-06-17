<?php


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
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */

    public $cmsCheck = true; //default true

    public $indietest = false; // default false
    // if enabled, allows tests to run independently for development purposes
    // Also need to enabled the db populate in functional.suite.yml

    #public $mailcatcher_url = 'localhost:1080';
    public $mailcatcher_url = 'mailcatcher.yielderverse.co.uk';

    #public $backend_url = 'http://local.qa.com';
    public $backend_url = 'https://test-back.yielderverse.co.uk';

    public $salesforce_params = [
        "id" => ($_ENV["SALESFORCE_CONSUMER_KEY"] ?? getenv("SALESFORCE_CONSUMER_KEY")) ?: "",
        "secret" => ($_ENV["SALESFORCE_CONSUMER_SECRET"] ?? getenv("SALESFORCE_CONSUMER_SECRET")) ?: "",
        "refresh_token" => ($_ENV["SALESFORCE_REFRESH_TOKEN"] ?? getenv("SALESFORCE_REFRESH_TOKEN")) ?: "",
        "user_object" => "Contact"
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
    ]; // /onboarding redirects to /email-verification

    public $verified_user_yorran = [
        "email" => "yorran_2sverified@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ]; // /onboarding redirects to /regulation-preference

    public $user_henley = [
        "email" => "henley_3declaredqs@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ]; // /onboarding redirects to /regulation-knowledge

    public $user_bryson = [
        "email" => "bryson_3declaredqf@crowdtek.co.uk",
        "password" => "Amsterdam1209",
    ]; // /onboarding redirects to /regulation-knowledge

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

    public function clearMailCatcherByApi()
    {
        $I = $this;
        //Clear mail Catcher via API - Http DELETE on /messages
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://admin:London123!@' . $I->mailcatcher_url . '/messages');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * $I is used for tests as a parameter, e.g. FunctionalTester $I
     * To keep the syntax similar, we do $I = $this; at the start of these helper functions
     * So we don't need to replace $I with $this, allowing easier exchange of code between
     * tests and functions
     */
    public function loginWithName($user)
    {
        $I = $this;
        $I->amOnPage('/login');
        $I->submitForm(['id' => 'form_sign_in'], [
            'sign_in_type' => [
                'email' => $user["email"],
                'password' => $user["password"],
            ]
        ]);
    }
    public function loginWithCredentials($email, $password)
    {
        $I = $this;
        $I->amOnPage('/login');
        $I->submitForm(['id' => 'form_sign_in'], [
            'sign_in_type' => [
                'email' => $email,
                'password' => $password,
            ]
        ]);
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
                'password' => $password
            ]
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $rsp_array = json_decode($response, true);

        if ($rsp_array['status'] == 200) {
            return $rsp_array['access_token'];
        } else {
            return false;
        }
    }

    public function getUserInfoByAPI($token)
    {
        $I = $this;
        // get user info using token from getUserToken
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $I->backend_url . "/v1/yielders/self",
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

    // convert the user['info'] array into a easier to traverse associative array
    public function convertUserInfoToDict($user_info)
    {
        $user_info_dict = [];
        foreach ($user_info as $info) {
            $user_info_dict[$info['type']] = $info['value'];
        }
        return $user_info_dict;
    }

    public function createUserAtStage($stage)
    {
        $I = $this;
        $I->clearMailCatcherByApi();
        $I->amOnPage('/onboarding');
        $I->seeCurrentUrlEquals('/onboarding/sign-up');
        $I->submitForm(['name' => 'signUpUser'], [
            'signUpUser' => [
                'firstname' => $I->new_user_yorran["firstname"],
                'lastname' => $I->new_user_yorran["lastname"],
                'email' => sqs($stage) . $I->new_user_yorran["email"],
                'password' => [
                    'first' => $I->new_user_yorran["password"],
                    'second' => $I->new_user_yorran["password"],
                ]
            ]
        ]);
        $I->submitForm(['name' => 'signUpUser'], [
            'signUpUser' => [
                'term_service_accepted' => 1,
            ]
        ]);

        if ($stage == 1) {
            return;
        }

        $I->amHttpAuthenticated('admin', 'London123!');
        $I->amOnPage('http://' . $I->mailcatcher_url . '/messages/1.html');
        $I->click('Verify Email');

        if ($stage == 2) {
            return;
        }

        $I->submitForm(['name' => 'userPreference'], [
            'userPreference' => [
                'contact_via_email' => false,
                'contact_via_tele' => false,
                'contact_via_sms' => false,
            ]
        ]);
        $I->see('Investor Declaration');
        $I->submitForm(['name' => 'userPreference'], [
            'userPreference' => [
                'investor_type' => 'cxb_worth_investor',
            ]
        ]);

        if ($stage == 3) {
            return;
        }

        $I->click('//form[contains(@name, "userKnowledge")]//button[text()="Begin Test"] ');
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question1' => 'Invest manageable amounts in diverse range of properties',
            ]
        ]);
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question2' => 'Yes',
            ]
        ]);
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question3' => 'Less than £1k',
            ]
        ]);
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question4' => 'I do not have a financial background, and Yielders will be my first investment',
            ]
        ]);
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question5' => 'The Yielders platform allows for me to sell my shares on the secondary market in the middle of an investment term. My money will only be repaid if someone buys my shares on the platform.',
            ]
        ]);
        $I->submitForm(['name' => 'userKnowledge'], [
            'userKnowledge' => [
                'question6' => 'My shareholding is diluted in proportion to the amount of new shares issued',
            ]
        ]);
        $I->click('Next');
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
        //print_r($response);
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
}
