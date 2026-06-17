<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TransferType;
use OTPHP\TOTP;

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
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class FunctionalTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;

    /**
     * Define custom actions here
     */
    public const USER_SUPER_ADMIN = 'superadmin@test.yielderverse.co.uk';
    public const USER_ADMIN = 'admin.auto@test.yielderverse.co.uk';
    public const USER_OPERATIONS = 'operations.auto@test.yielderverse.co.uk';
    public const USER_ANALYST = 'analyst.auto@test.yielderverse.co.uk';
    public const USER_ADMIN_ENGINEERING = 'engineering.auto@test.yielderverse.co.uk';

    public const USER_REG1 = 'ben.auto@test.yielderverse.co.uk';
    public const USER_REG2 = 'holly.auto@test.yielderverse.co.uk';
    public const USER_REG3 = 'jim.auto@test.yielderverse.co.uk';
    public const USER_VIP = 'freya.auto@test.yielderverse.co.uk';
    public const USER_SALESFORCE = 'salesforce.sync@test.yielderverse.co.uk';
    public const USER_LOW_BALANCE = 'anne.auto@test.yielderverse.co.uk';

    public const USER_REG_KYC_GREEN = 'kycgreen.auto@test.yielderverse.co.uk';
    public const USER_REG_KYC_AMBER = 'kycamber.auto@test.yielderverse.co.uk';
    public const USER_REG_KYC_RED = 'kycred.auto@test.yielderverse.co.uk';

    public const TEST_PASSWORD = 'HarvestBounty!756';

    public const YIELDERS_FEE_WALLET = 'wlt_m_01HW3DC5APRJ5N8KWSBHMBYX48';
    public const YPML_FEE_WALLET = 'wlt_m_01J01JTNKKTZGPERV3JM93NRN0';

    public const OAUTH2_CLIENT_ID = '904c1b4d9a15529ed70ff5e686345a9f';
    public const OAUTH2_CLIENT_SECRET = '71dcf8066c14b07a772a3c8af9217c318dc4385f9fa8215ab5eb11e511fd2cbda50714463088226b8ceefed0126482f2c36f2f4fe7ec4f5ba7c47935b02a9c8e';

    public const FIXTURE_WALLETS = [
        'hold' => 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
        'settlement' => 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65',
        'deposit' => 'wlt_m_01HW3DH1PXVVGSP9634636PR48',
        'expenses' => 'wlt_m_01HW3DHG5E7750DXHJZK6JHMRA',
        'tax' => 'wlt_m_01HW3DJDVMWE4RBH8J1Z5359PK',
        'distribution' => 'wlt_m_01HW3DK5RNVTHJAFMQX34Q1WZD',
        'treasury' => 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V',
        'ben' => 'wlt_m_01HW3FBRBZF8ZMEF8WHPRA21NZ',
        'holly' => 'wlt_m_01HW3FKZR6PBD5PX8818D5GP04',
        'jim' => 'wlt_m_01HW3FF80J5F76J2SS02DQMS7H',
        'freya' => 'wlt_m_01HW3FVCWQE69TDTED8MXZZPJ1',
    ];

    public $salesforce_params = [
        'id' => ($_ENV['SALESFORCE_CONSUMER_KEY'] ?? getenv('SALESFORCE_CONSUMER_KEY')) ?: '',
        'secret' => ($_ENV['SALESFORCE_CONSUMER_SECRET'] ?? getenv('SALESFORCE_CONSUMER_SECRET')) ?: '',
        'refresh_token' => ($_ENV['SALESFORCE_REFRESH_TOKEN'] ?? getenv('SALESFORCE_REFRESH_TOKEN')) ?: '',
        'user_object' => 'Contact',
        'test_user_id' => '0034H00002aZLcFQAW',
    ];

    public function loginAdmin($role = 'super')
    {
        $I = $this;
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        $I->amOnPage('/login');

        if (in_array($role, ['admin', 'finops', 'techops', 'operations', 'analyst'])) {
            $username = $role . '.auto@test.yielderverse.co.uk';
        } else {
            $username = self::USER_SUPER_ADMIN;
        }

        $I->fillField('_username', $username);
        $I->fillField('_password', self::TEST_PASSWORD);
        $I->click('Login');
        $I->loginMfaStep();
    }

    public function loginAsUser(
        $username = self::USER_ADMIN_ENGINEERING,
        $password = 'HarvestBounty!756',
    ) {
        $I = $this;
        $mailcatcher = $I->getMailcatcherClient();
        $mailcatcher->delete('/messages');

        //Test can not edit user entity and user status
        $I->amOnPage('/logout');
        $I->amOnPage('/login');

        $I->fillField('_username', $username);
        $I->fillField('_password', $password);
        $I->click('Login');
        $I->loginMfaStep();
    }

    public function loginMfaStep()
    {
        $I = $this;
        $mailcatcher = $I->getMailcatcherClient();
        try {
            $I->see('Two-Factor Authentication');
            $mfaCode = explode(
                ':',
                (string) $mailcatcher->get('/messages/1.plain')->getBody(),
            )[1];
            $I->fillField('_auth_code', trim($mfaCode));
            $I->click('form input[type=submit]');
        } catch (\Exception $e) {
            // user does not have mfa email enabled
        }
    }

    public function vipConfirmationEmail()
    {
        $I = $this;
        $mailcatcher = $I->getMailcatcherClient();
        $email = (string) $mailcatcher->get('/messages/1.plain')->getBody();
        $I->assertStringContainsString('Top Yielder process is now complete', $email);
    }

    public function getMailcatcherClient()
    {
        return new \GuzzleHttp\Client([
            'base_uri' => $_ENV['MAILCATCHER_URL'] ?: 'http://127.0.0.1:1080',
        ]);
    }

    public function changePermission($role, $username, $superAdminDisabled = false)
    {
        $I = $this;
        $I->amOnPage('/admin/users/staff');
        $I->amOnPage('/admin/users/' . $I->getUserIdByUsername($username) . '/roles');
        // $I->click('a[href="/admin/users/' . $I->getUserIdByUsername($username) . '/roles"]');
        $I->see('Edit User Role');
        $I->see('Role Permissions');
        $I->seeLink('Go to User Dashboard');

        switch ($role) {
            case 'ROLE_SUPER_ADMIN':
                $I->selectOption('form input[type=radio]', 'ROLE_SUPER_ADMIN');
                break;
            case 'ROLE_ADMIN':
                $I->selectOption('form input[type=radio]', 'ROLE_ADMIN');
                break;
            case 'ROLE_OPERATIONS':
                $I->selectOption('form input[type=radio]', 'ROLE_OPERATIONS');
                break;
            case 'ROLE_ANALYST':
                $I->selectOption('form input[type=radio]', 'ROLE_ANALYST');
                break;
        }
        $I->click('Save Changes');
    }

    public function searchDatabaseByStatus($entityName, $status = null)
    {
        if (!in_array($entityName, ['users', 'assets', 'offerings', 'investments'])) {
            $this->fail(
                'Entity '
                . $entityName
                . ' not supported, the following are valid: users, assets, offerings, investments',
            );
            return null;
        }
        $statuses = [];
        if ($entityName == 'users') {
            $entityStatusName = 'users_statuses';
            $entityStatusColName = 'status_id';
        } else {
            $entityStatusName = $entityName . '_status';
            $entityStatusColName = substr($entityName, 0, -1) . 'Status_id';
        }
        if (!empty($status)) {
            $statuses = $this->grabColumnFromDatabase($entityStatusName, 'id', [
                'lifecycleStatus' => $status,
            ]);
        }
        if (empty($statuses)) {
            $this->fail('Unable to find suitable fixture for status: ' . $status);
            return null;
        }
        return $this->grabFromDatabase($entityName, 'id', [
            $entityStatusColName => $statuses[0],
        ]);
    }

    public function getUserStatusField(int $userId, string $statusField): ?bool
    {
        $statusId = $this->grabFromDatabase('users', 'status_id', ['id' => $userId]);
        if (empty($statusId)) {
            $this->fail('User with id: ' . $statusId . ' does not exist');
            return null;
        }
        try {
            return (bool) $this->grabFromDatabase('users_statuses', $statusField, [
                'id' => $statusId,
            ]);
        } catch (\Exception $e) {
            $this->fail('Status field ' . $statusField . ' does not exist');
            return null;
        }
    }

    public function getUserGDPRAccepted($filter)
    {
        return $this->grabFromDatabase('users', 'gdpr_accepted', ['id' => $filter]);
    }

    public function getUserIdByUsername($username)
    {
        return $this->grabFromDatabase('users', 'id', ['username' => $username]);
    }

    public function getAssetIdByName($name)
    {
        return $this->grabFromDatabase('assets', 'id', ['name' => $name]);
    }

    /**
     * Get the Id for an Investment
     */
    public function getInvestmentId($filter)
    {
        $I = $this;

        // return the first investment which has id 1, assuming the table is sorted ascending'
        switch ($filter) {
            case 'FIRST':
                return $I->grabFromDatabase('investments', 'id', ['id' => 1]);
                break;

            case 'RANDOM':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('investments', 'id', ['id' => $randid]);
                break;
        }
    }

    public function getInvestmentNameById($filter)
    {
        // return the first investment which is the 'Master Test'
        return $this->grabFromDatabase('investments', 'name', ['id' => $filter]);
    }

    /**
     * Get the Id for an User
     */
    public function getUserId($filter)
    {
        $I = $this;

        // return the first User
        switch ($filter) {
            case 'FIRST':
                return $I->grabFromDatabase('users', 'id', ['id' => 1]);
                break;

            case 'SALESFORCE':
                return $I->grabFromDatabase('users', 'id', [
                    'email' => 'salesforce.sync@test.yielderverse.co.uk',
                ]);
                break;

            case 'RANDOM':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('users', 'id', ['id' => $randid]);
                break;

            case 'notVip':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('users', 'id', ['isVIP' => 0]);
                break;
        }
    }

    /**
     * Get the Id for an Asset
     */
    public function getAssetId($filter)
    {
        $I = $this;

        // return the first investment which is the 'Master Test'
        switch ($filter) {
            case 'FIRST':
                return $I->grabFromDatabase('assets', 'id', ['id' => 1]);
                break;

            case 'RANDOM':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('assets', 'id', ['id' => $randid]);
                break;
        }
    }

    /**
     * Get the Id for an Payout
     */
    public function getPayoutId($filter)
    {
        $I = $this;
        switch ($filter) {
            case 'FIRST':
                // return the first investment which is the 'Master Test'
                return $I->grabFromDatabase('payouts', 'id', ['id' => 1]);
                break;
            case 'RANDOM':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('assets', 'id', ['id' => $randid]);
                break;
        }
    }

    /*
     * Get the Id for an Offering
     */
    public function getOfferingId($filter)
    {
        $I = $this;

        // return the first offering which is the 'Master Test'
        switch ($filter) {
            case 'FIRST':
                return $I->grabFromDatabase('offerings', 'id', ['id' => 1]);
                break;

            case 'RANDOM':
                $randid = rand(1, 10);

                return $I->grabFromDatabase('offerings', 'id', ['id' => 2]);
                break;
        }
    }

    /**
     * Get the Status for an Offering
     */
    public function getOfferingStatus($id)
    {
        $I = $this;

        return $I->grabFromDatabase('offerings_status', 'lifecycleStatus', [
            'id' => $id,
        ]);
    }

    /**
     * Get the Name for an Offering
     */
    public function getOfferingName($id)
    {
        $I = $this;

        return $I->grabFromDatabase('offerings', 'name', ['id' => $id]);
    }

    /*
     * Get the Id for an Offering
     */
    public function getSecondaryOfferingId($filter)
    {
        $I = $this;

        return $I->grabFromDatabase('offerings', 'id', [
            'name' => 'secondary offering master test',
        ]);
    }

    public function loginToSalesforce()
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL =>
                'https://login.salesforce.com/services/oauth2/token?grant_type=refresh_token&refresh_token='
                    . $this->salesforce_params['refresh_token']
                    . '&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD =>
                $this->salesforce_params['id']
                    . ':'
                    . $this->salesforce_params['secret'],
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
    public function saleForceAction($action, $object, $id)
    {
        $auth_info = $this->loginToSalesforce();

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL =>
                $auth_info['instance_url']
                    . "/services/data/v45.0/sobjects/{$object}/{$id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $action,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $auth_info['access_token'],
            ],
        ]);
        $rsp = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($rsp, true);
        return $response;
    }

    /**
     * Note that this requires the package OTPHP\TOTP;
     */
    public function generateOTP(int $timestamp, string $secret): string
    {
        $totp = TOTP::create($secret, 30, 'SHA1', 6);
        return $totp->at($timestamp);
    }

    public function loopCheckElements($elements, $locator = '')
    {
        $I = $this;

        foreach ($elements as $element) {
            $I->see($element, $locator);
        }
    }

    public function checkLifecycle($stages, $id, $object, $listView = null)
    {
        $I = $this;

        foreach ($stages as $stage => $transition) {
            $endpoint = $listView ?: $object;
            $query = '';
            if ('offering' == $object) {
                // offering list view is pre-filtered for secondary offerings, so need to clear
                $query = '?isSecondaryMrkt=';
            }
            $I->amOnPage('/admin/' . $endpoint . $query);
            // $I->click('Apply Filters');
            $I->see($stage, 'tbody tr:nth-child(1) td span.badge');
            if (null === $transition) {
                $I->seeResponseCodeIs(200);
            } else {
                $I->amOnPage('/admin/' . $object . '/' . $id . $transition);
                $I->seeResponseCodeIs(200);
            }
        }
    }

    public function createBasicOffering($name)
    {
        $this->amOnPage('/admin/offering/add');
        $this->fillField('input#offering_name', $name);
        $this->click('button#offering_submit');
    }

    public function createBasicAsset($name)
    {
        $this->amOnPage('/admin/asset/add');
        $this->fillField('input#asset_name', $name);
        $this->click('button#asset_submit');
    }

    public function addPaymentToOrder(
        float|string $amount = '1.23',
        int|string $shares = 1,
    ): void {
        $this->click('Add Payment');
        $firstOption = $this->grabAttributeFrom(
            '#payment_request_payee option:nth-child(2)',
            'value',
        );
        $this->selectOption('#payment_request_payee', ['value' => $firstOption]);
        $this->fillField('#payment_request_amount', $amount);
        if ($this->grabMultiple('#payment_request_shareholding')) {
            $this->fillField('#payment_request_shareholding', $shares);
        }
        if ($this->grabMultiple('#payment_request_tradeOrder')) {
            $this->fillField('#payment_request_tradeOrder', 1);
        }
        $this->click('Add Payment');
    }

    public function addTransferToOrder(
        float|string $amount = '1.23',
        string $description = 'Demo transfer',
        string $debitWallet = 'wlt_m_01HW3DETE9YHAGN7GEGAH1PF65',
        string $creditWallet = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
    ): void {
        $this->click('Add Transfer');
        $this->fillField('#transfer_request_debitWalletId', $debitWallet);
        $this->fillField('#transfer_request_creditWalletId', $creditWallet);
        $this->fillField('#transfer_request_description', $description);
        $this->fillField('#transfer_request_amount', $amount);
        $this->click('Add Transfer');
    }

    public function createIncomeTransferOrder(
        string $assetName = 'Royal Eversea Glades - Cambridge',
        bool $quickMode = false,
    ): int {
        $assetId = $this->grabFromDatabase('assets', 'id', ['name' => $assetName]);

        if ($quickMode) {
            $this->amOnPage("/admin/monthend/income-transfers/create/{$assetId}");
        } else {
            $this->amOnPage('/admin/monthend/income-transfers/create');
            $this->dontSeeElement('select#asset_relation_asset option[selected]');
            $this->seeLink('Back', '/admin/monthend/assets');
            $this->seeLink('Abandon', '/admin/monthend/assets');

            $this->amOnPage(
                "/admin/monthend/income-transfers/create?assetId={$assetId}",
            );
            $this->seeLink('Back', "/admin/monthend/{$assetId}");
            $this->seeLink('Abandon', "/admin/monthend/{$assetId}");
            $this->seeElement('select#asset_relation_asset');
            $this->assertEquals($assetId, $this->grabAttributeFrom(
                '#asset_relation_asset option[selected]',
                'value',
            ));
            $this->click('Create Transfer Order');
        }
        $this->see($assetName);

        // Get the newest transfer order for the asset (should be the one just created)
        $assetTransferOrders = $this->grabColumnFromDatabase('transfer_order', 'id', [
            'asset_id' => $assetId,
        ]);
        sort($assetTransferOrders);
        return array_pop($assetTransferOrders);
    }

    public function addAssetTransferToOrder(
        string $transferTo = 'Expenses',
        float|string $amount = '0.38',
        string $description = 'Test asset transfer',
    ): void {
        $this->click('Add Transfer');
        $this->selectOption('#asset_transfer_request_creditWalletId', $transferTo);
        $this->fillField('#asset_transfer_request_description', $description);
        $this->fillField('#asset_transfer_request_amount', $amount);
        $this->click('Add Transfer');
    }

    public function createPaymentOrder(
        string $assetName = 'Royal Eversea Glades - Cambridge',
        PaymentType $paymentType = PaymentType::Dividend,
        bool $quickMode = false,
    ): int {
        $assetId = $this->grabFromDatabase('assets', 'id', ['name' => $assetName]);
        $typePath = match ($paymentType) {
            PaymentType::Dividend => 'dividends',
            PaymentType::Repayment => 'repayments',
            PaymentType::Divestment => 'divestments',
            PaymentType::InvestmentExit => 'divestments',
            default => 'dividends',
        };
        if ($quickMode) {
            $this->amOnPage("/admin/monthend/{$typePath}/create/{$assetId}");
        } else {
            $this->amOnPage("/admin/monthend/{$typePath}/create");
            $this->dontSeeElement('select#asset_relation_asset option[selected]');
            $this->seeLink('Back', '/admin/monthend/assets');
            $this->seeLink('Abandon', '/admin/monthend/assets');

            $this->amOnPage("/admin/monthend/{$typePath}/create?assetId={$assetId}");
            $this->seeLink('Back', "/admin/monthend/{$assetId}");
            $this->seeLink('Abandon', "/admin/monthend/{$assetId}");
            $this->seeElement('select#asset_relation_asset');
            $this->assertEquals($assetId, $this->grabAttributeFrom(
                '#asset_relation_asset option[selected]',
                'value',
            ));
            $this->click('Create Payment Order');
        }

        // Get the newest transfer order for the asset (should be the one just created)
        $assetPaymentOrders = $this->grabColumnFromDatabase('payment_order', 'id', [
            'asset_id' => $assetId,
        ]);
        sort($assetPaymentOrders);
        return array_pop($assetPaymentOrders);
    }

    public function convertMonetaryToNumber(string $monetaryValue): string
    {
        $withoutCurrency = str_replace('£', '', $monetaryValue);
        $withoutCommas = str_replace(',', '', $withoutCurrency);
        return $withoutCommas;
    }

    public function createFeeCollectionOrder(): int
    {
        $this->amOnPage('/admin/monthend/fee-collections/create');
        $this->click('Create Transfer Order');
        $transferOrders = $this->grabColumnFromDatabase('transfer_order', 'id', [
            'transferType' => TransferType::FeeCollection->value,
        ]);
        sort($transferOrders);
        return array_pop($transferOrders);
    }

    public function createIncomeDisaggregationOrder(): int
    {
        $this->amOnPage('/admin/monthend/income-disaggregations/create');
        $this->click('Create Transfer Order');
        $transferOrders = $this->grabColumnFromDatabase('transfer_order', 'id', [
            'transferType' => TransferType::IncomeDisaggregation->value,
        ]);
        sort($transferOrders);
        return array_pop($transferOrders);
    }

    public function addTransferToIncomeDisaggregationOrder(float|string $amount = '0.04'): void
    {
        $this->click('Add Transfer');
        $this->click('Add Transfer', '#assets-list tbody tr:first-child');
        $this->fillField('#asset_transfer_request_amount', $amount);
        $this->click('Add Transfer');
    }

    public function addTransferToFeeCollectionOrder(
        float|string $amount = '0.04',
        string $feeType = 'Management',
    ): void {
        $this->click('Add Transfer');
        $this->click("{$feeType}", '#assets-list tbody tr:first-child');
        $this->fillField('#asset_transfer_request_amount', $amount);
        $this->click('Add Transfer');
    }

    public function createSettlementOrder(
        string $assetName = 'Royal Eversea Glades - Cambridge',
        bool $quickMode = false,
    ): int {
        $assetId = $this->grabFromDatabase('assets', 'id', ['name' => $assetName]);
        if ($quickMode) {
            $this->amOnPage("/admin/monthend/settlements/create/{$assetId}");
        } else {
            $this->amOnPage('/admin/monthend/settlements/create');
            $this->dontSeeElement('select#asset_relation_asset option[selected]');
            $this->seeLink('Back', '/admin/monthend/assets');
            $this->seeLink('Abandon', '/admin/monthend/assets');

            $this->amOnPage("/admin/monthend/settlements/create?assetId={$assetId}");
            $this->seeLink('Back', "/admin/monthend/{$assetId}");
            $this->seeLink('Abandon', "/admin/monthend/{$assetId}");
            $this->seeElement('select#asset_relation_asset');
            $this->assertEquals($assetId, $this->grabAttributeFrom(
                '#asset_relation_asset option[selected]',
                'value',
            ));
            $this->click('Create Settlement Order');
        }

        $this->see($assetName);

        // Get the newest transfer order for the asset (should be the one just created)
        $assetTransferOrders = $this->grabColumnFromDatabase('transfer_order', 'id', [
            'asset_id' => $assetId,
        ]);
        sort($assetTransferOrders);
        return array_pop($assetTransferOrders);
    }

    public function createQuestionChoice(
        string $text,
        bool $active = true,
        bool $correct = false,
    ): void {
        $this->click('Add Choice');
        $this->fillField('#question_choice_content', $text);
        if ($active) {
            $this->checkOption('#question_choice_active');
        } else {
            $this->uncheckOption('#question_choice_active');
        }
        if ($correct) {
            $this->checkOption('#question_choice_correct');
        } else {
            $this->uncheckOption('#question_choice_correct');
        }
        $this->click('Create Question Choice');
    }

    public function addAssetStatusLog(int $assetId, AssetStatus $status): void
    {
        $this->amOnPage("/admin/asset/{$assetId}/status-logs/create");
        $this->selectOption('#asset_status_log_status', $status->value);
        $this->click('Create New Asset Status Log');
    }
}
