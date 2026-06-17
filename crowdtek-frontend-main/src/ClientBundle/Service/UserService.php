<?php

namespace ClientBundle\Service;

use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Type\VoidType;
use Symfony\Component\HttpFoundation\RequestStack;

class UserService
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private CrowdTekClient $crowdTekClient,
        private ApiClient $client,
        private string $network,
    ) {
        // $this->network = $this->requestStack->getSession()->get('cv_network')
        //     ? $this->requestStack->getSession()->get('cv_network')
        //     : $this->container->getParameter('cv_network');
    }


    public function getUser()
    {
        $this->logger->info("==================IN getUser=====================");

        $uri = 'v1/' . $this->network . '/self';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function getUserInfo()
    {
        $this->logger->info("==================IN getUserInfo=====================");

        $user = $this->getUser();
        if (isset($user['data']['user'])) {
            return $user['data']['user'];
        } else {
            return $this->getUser();
        }
    }

    /**
     * Call getUserInfo and reload session userInfo on success
     */
    public function refreshUserInfo(): void
    {
        $userRes = $this->getUserInfo();
        if (isset($userRes['outcome']) && $userRes['outcome'] == 'error') {
            $this->logger->error("Could not get user info: " . $userRes['data']['user_message']);
        } else {
            $this->requestStack->getSession()->set('authenticated', true);
            $this->requestStack->getSession()->set('userInfo', $userRes);
        }
    }

    public function getUserInfoFromSession(bool $refresh = false)
    {
        $this->logger->info("==================IN getUserInfoFromSession=====================");

        if ($refresh) {
            $this->refreshUserInfo();
        }
        return $this->requestStack->getSession()->get('userInfo');
    }

    /**
     * @param $network
     * @param $parameters
     * @return mixed
     */
    public function signupUser($network, $parameters)
    {
        $this->logger->info("==================IN signupUser=====================");

        // Note that this is a public route, formerly we used 'v1/' . $network . '/users'
        $uri = 'v1/' . $network . '/public/users';
        $options = [
            'json' => $parameters,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    /**
     * @param $network
     * @param $parameters
     */
    public function update($network, $parameters)
    {
        $this->logger->info("Sending data to BACKOFFICE - [" . json_encode($parameters) . "]");

        $uri = 'v1/' . $network . '/self';
        $options = [
            'json' => $parameters,
        ];

        return $this->crowdTekClient->sendRequest('PATCH', $uri, $options);
    }

    public function getOrgs()
    {
        $this->logger->info("==================IN getOrgs=====================");

        $uri = 'v1/' . $this->network . '/self/organizations';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function changePassword($parameters)
    {
        $this->logger->info("==================IN changePassword=====================");

        $uri = 'v1/' . $this->network . '/self/changePassword';
        $options = [
            'json' => $parameters,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function contegoCheck()
    {
        $this->logger->info("==================IN contegoCheck=====================");

        $uri = 'v1/' . $this->network . '/self/contegoCheck';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function contegoCheckOnLatestRef()
    {
        $this->logger->info("==================IN contegoCheckOnLatestRef=====================");

        $uri = 'v1/' . $this->network . '/self/contego';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function contegoCheckDoc()
    {
        $this->logger->info("==================IN contegoCheckDoc=====================");

        $uri = 'v1/' . $this->network . '/self/contegoCheckPersonDoc';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function contegoCheckCompany()
    {
        $this->logger->info("==================IN contegoCheckCompany=====================");

        $uri = 'v1/' . $this->network . '/self/contegoCheckCompany';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function block()
    {
        $this->logger->info("==================IN block=====================");

        $uri = 'v1/' . $this->network . '/self/blockUser';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function approve()
    {
        $this->logger->info("==================IN approve=====================");

        $uri = 'v1/' . $this->network . '/self/approveUser';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function completeRegistration()
    {
        $this->logger->info("==================IN completeRegistration=====================");

        $uri = 'v1/' . $this->network . '/self/markRegistrationComplete';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function resendVerifyEmail($parameters)
    {
        $this->logger->info("==================IN resendVerifyEmail=====================");

        $uri = 'v1/' . $this->network . '/self/resendVerificationEmail';
        $options = [
            'json' => $parameters,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }


    public function createMangopayUser()
    {
        $this->logger->info("==================IN createMangopayUser=====================");

        //register mangopay account
        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayRegister';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function createMangopayUserSca()
    {
        $this->logger->info("==================IN mangopayRegisterSca=====================");

        //register mangopay account
        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayRegisterSca';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function createMangopayWallet()
    {
        $this->logger->info("==================IN createMangopayWallet=====================");

        //register mangopay wallet
        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayWalletRegister';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function getMangopayWalletSca(bool $initClientToken = false)
    {
        $this->logger->info("==================IN getMangopayWalletSca=====================");

        $userInfo = $this->requestStack->getSession()->get('userInfo', null);
        if ($userInfo === null) {
            $this->refreshUserInfo();
            $userInfo = $this->requestStack->getSession()->get('userInfo', null);
        }

        if ($userInfo["mangopay_user_id"] && $userInfo["mangopay_wallet_id"] && $userInfo["sca_status"] == "active") {
            $this->logger->debug("Checking if wallet SCA verification required");
            try {
                $requestOptions = ['query' => ['sca' => true]];
                if ($initClientToken) {
                    $requestOptions['headers'] = [
                        'Authorization' => 'Bearer ' . $this->requestStack->getSession()->get('jwt_token')
                    ];
                }
                $response = $this->client->mangopayWallet()->retrieveWallet($requestOptions);
                $responseBody = $this->client->getContent($response);

                $this->logger->debug("Retrieving wallet response", [
                    'statusCode' => $response->getStatusCode(),
                    'body' => $responseBody,
                ]);

                if (array_key_exists('data', $responseBody ?? [])) {
                    // Special check for SCA verification required
                    if (401 == $response->getStatusCode()) {
                        if (
                            isset($responseBody['data']['user_message'])
                            && str_contains($responseBody['data']['user_message'], "SCA required")
                        ) {
                            $this->requestStack->getSession()->set('walletScaRequired', true);
                        }
                    }

                    if (200 == $response->getStatusCode()) {
                        $this->logger->debug("Wallet retrieved");
                        $this->requestStack->getSession()->set('walletScaRequired', false);
                        return $responseBody['data'];
                    }
                }
            } catch (\Exception $e) {
                // Some other issue preventing us from checking if SCA required for wallet access
                $this->logger->error("Error retrieving transactions");
            }
        }
        return null;
    }

    public function getTransactionsSca(array $parameters = [])
    {
        $this->logger->info("==================IN getTransactions=====================");

        try {
            $requestionOptions = ['query' => array_merge($parameters, ['sca' => true])];
            $this->logger->debug("Retrieving transactions with query", $requestionOptions);

            $response = $this->client->mangopayWallet()->listWalletTransactions($requestionOptions);
            $responseBody = $this->client->getContent($response);

            $this->logger->debug("Retrieving transactions response", [
                'statusCode' => $response->getStatusCode(),
            ]);

            if (array_key_exists('data', $responseBody ?? [])) {
                // Special check for SCA verification required
                if (401 == $response->getStatusCode()) {
                    if (
                        isset($responseBody['data']['user_message'])
                        && str_contains($responseBody['data']['user_message'], "SCA required")
                    ) {
                        $this->requestStack->getSession()->set('walletScaRequired', true);
                    }
                }

                if (200 == $response->getStatusCode()) {
                    $this->logger->debug("Transactions retrieved");
                    $this->requestStack->getSession()->set('walletScaRequired', false);
                    return $responseBody['data']['transactions'];
                }
            }
        } catch (\Exception $e) {
            // Some other issue preventing us from checking loading transactions
            $this->logger->error("Error retrieving transactions");
        }
        return [];
    }

    public function getMangopayUser($userId = null)
    {
        $this->logger->info("==================IN getMangopayUser=====================");

        if (empty($userId)) {
            $user = $this->getUserInfo();
            $userId = $user['id'];
        }

        $uri = 'v1/' . $this->network . '/users/' . $userId . '/mangopayUser';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function mangopayTransfer($params)
    {
        $this->logger->info("==================IN mangopayTransfer=====================");

        $params['amount'] = $params['amount'] * 100;
        $user = $this->getUserInfo();

        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayTransfer';
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function payIn($params)
    {
        $this->logger->info("==================IN payIn=====================");

        $params['amount'] = $params['amount'] * 100;
        $user = $this->getUserInfo();

        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayWalletPayin/' . $params['wallet_id'];
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function payinBankWire($params)
    {
        $this->logger->info("==================IN payinBankWire=====================");

        $params['amount'] = $params['amount'] * 100;
        $user = $this->getUserInfo();

        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayWalletPayinBankWire/' . $params['wallet_id'];
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function checkMangopayKYC()
    {
        $this->logger->info("==================IN checkMangopayKYC=====================");

        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayKycCheck';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function refundTransfer($transferId)
    {
        $this->logger->info("==================IN refundTransfer=====================");

        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayRefundTransfer/' . $transferId;

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    /**
     * Set $initClientToken to true if you know you are calling the API in the same request as the login callback.
     * In this situation, the ApiClient will not have a jwt_token set yet.
     * $initClientToken will force getMangopayWalletSca to set the jwt_token from session for that single request instead.
     */
    public function setBalance(bool $initClientToken = false)
    {
        $this->logger->info("==================IN setBalance=====================");

        return $this->requestStack->getSession()->set(
            'balance',
            number_format(($this->getBalance($initClientToken)), 2, '.', ''),
        );
    }

    public function getBalance(bool $initClientToken = false)
    {
        $this->logger->info("==================IN getBalance=====================");

        $balance = 0;
        $wallet = $this->getMangopayWalletSca($initClientToken);
        if ($wallet) {
            $this->requestStack->getSession()->set('wallet_id', $wallet['id']);
            $balance = $wallet['balance'] / 100;
        }
        return $balance;
    }

    public function mangopayRepayment($params, $orgId)
    {
        $this->logger->info("==================IN mangopayRepayment=====================");

        $params['amount'] = $params['amount'] * 100;
        $uri = 'v1/' . $this->network . '/organizations/' . $orgId . '/mangopayRepayment';
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getUserById($id)
    {
        $this->logger->info("==================IN getUserById=====================");

        $uri = 'v1/' . $this->network . '/users/' . $id;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    /**
     * @param $parameters
     */
    public function sendCustomClientEmail($parameters)
    {
        $this->logger->info("==================IN sendCustomClientEmail=====================");

        $uri = 'v1/' . $this->network . '/messages/client-send-message';
        $options = [
            'json' => $parameters,
        ];

        $response = $this->crowdTekClient->sendRequest('POST', $uri, $options);

        $this->logger->info(json_encode($response));

        return $response;
    }

    public function getOrganizationMangopayWalletTransactions($orgId, $walletId)
    {
        $this->logger->info("==================IN getOrganizationMangopayWalletTransactions=====================");

        $uri = 'v1/' . $this->network . '/organizations/' . $orgId . '/mangopay/wallets/' . $walletId . '/transactions';

        $transactionsRes = $this->crowdTekClient->sendRequest('GET', $uri);

        if (!empty($transactionsRes['outcome']) && $transactionsRes['outcome'] == 'success') {
            return $transactionsRes['data']['transactions'];
        }
        return [];
    }

    public function createBankAccount($params)
    {
        $this->logger->info("==================IN createBankAccount=====================");

        $uri = 'v1/' . $this->network . '/self/mangopay/bankaccounts';
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getBankAccounts($userId, $page = 1, $limit = 100)
    {
        $this->logger->info("==================IN getBankAccounts=====================");

        $params = http_build_query(['page' => $page, 'limit' => $limit]);
        $uri = 'v1/' . $this->network . '/users/' . $userId . '/bankaccounts?' . $params;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function payoutBankWire($params)
    {
        $this->logger->info("==================IN payoutBankWire=====================");

        $user = $this->getUserInfo();
        $uri = 'v1/' . $this->network . '/users/' . $user['id'] . '/mangopayWalletPayoutBankWire/' . $params['wallet_id'];
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getNetworkUsers($parameters = [])
    {
        $this->logger->info("==================IN getNetworkUsers=====================");

        $inputs = http_build_query($parameters);
        $uri = 'v1/' . $this->network . '/users?' . $inputs;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function getUserFromEmail($email)
    {
        $this->logger->info("==================IN getUserFromEmail=====================");

        $inputs = [
            'email' => $email,
        ];
        $params = http_build_query($inputs);
        $uri = 'v1/yielders-admin/userFromEmail?' . $params;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function updateUserById($userId, $params)
    {
        $this->logger->info("==================IN updateUserById=====================");

        $uri = 'v1/yielders-admin/user/' . $userId;
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('PATCH', $uri, $options);
    }

    public function listNetworkUser($parameters)
    {
        $this->logger->info("==================IN listNetworkUser=====================");

        $queryParams = http_build_query($parameters);
        $uri = 'v1/' . $this->network . '/users?' . $queryParams;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function registerUserCardWithMangoPay()
    {
        $this->logger->info("==================IN registerUserCardWithMangoPay=====================");

        $uri = 'v1/' . $this->network . '/self/mangopayCardRegister';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function registerCardWithMangoPay($params)
    {
        $this->logger->info("==================IN registerCardWithMangoPay=====================");

        $uri = 'v1/' . $this->network . '/self/mangopayCards';
        $options = [
            'json' => $params,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }


    public function payinWithRegisteredCardMangoPay($card_id, $params)
    {
        $this->logger->info("==================IN payinWithRegisteredCardMangoPay=====================");

        $requestBody = [
            'userId' => $params['userId'],
            'amount' => $params['amount'] * 100,
            'ipAddress' => $params['ipAddress'],
            'secureModeReturnUrl' => $params['SecureModeReturnURL'],
            'browserInfo' => [
                'acceptHeader' => $params['browserInfo']['acceptHeader'],
                'userAgent' => $params['browserInfo']['userAgent'],
                'language' => $params['browserInfo']['language'],
                'screenWidth' => $params['browserInfo']['screenWidth'],
                'screenHeight' => $params['browserInfo']['screenHeight'],
                'colorDepth' => $params['browserInfo']['colorDepth'],
                'timeZoneOffset' => $params['browserInfo']['timeZoneOffset'],
                'javaEnabled' => $params['browserInfo']['javaEnabled'],
                'javascriptEnabled' => $params['browserInfo']['javascriptEnabled'],
            ],
        ];

        // $this->logger->debug("request body for payin", $requestBody);

        $uri = 'v1/' . $this->network . '/self/mangopayCards/' . $card_id . '/payin';
        $options = [
            'json' => $requestBody,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getComplianceCheckStatus()
    {
        $this->logger->info("==================IN getComplianceCheckStatus=====================");

        $uri = 'v1/' . $this->network . '/self/checkComplianceStatus';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }


    public function setupDirectDebitBankAccountAndMandate($formData)
    {
        $this->logger->info("==================IN setupDirectDebitBankAccountAndMandate=====================");

        $uri = 'v1/' . $this->network . '/self/mangopay/directDebitSetup';
        $options = [
            'json' => $formData,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }


    public function getDirectDebitCheck()
    {
        $this->logger->info("==================IN getDirectDebitCheck=====================");

        $uri = 'v1/' . $this->network . '/self/directDebitCheck';

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function cancelDirectDebit()
    {
        $this->logger->info("==================IN cancelDirectDebit=====================");

        $uri = 'v1/' . $this->network . '/self/cancelDirectDebit';

        return $this->crowdTekClient->sendRequest('POST', $uri);
    }

    public function updateDirectDebitStatus($formData)
    {
        $this->logger->info("==================IN updateDirectDebitStatus =====================");

        $uri = 'v1/' . $this->network . '/self/updateDirectDebitStatus';
        $options = [
            'json' => $formData,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function updateDirectDebitAmount($formData)
    {
        $this->logger->info("==================IN  updateDirectDebitAmount =====================");

        $uri = 'v1/' . $this->network . '/self/updateDirectDebitAmount';
        $options = [
            'json' => $formData,
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }
}
