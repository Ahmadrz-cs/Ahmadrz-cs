<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 01/08/18
 * Time: 13:10
 */

namespace ClientBundle\Service;

use AppBundle\Entity\EmailTagConstants;
use AppBundle\Entity\UserCustomInfo as UserInfo;
use AppBundle\Entity\UserEntity as User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class CrowdTekService
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UserService $userService,
        private string $network
    ) {
    }

    /**
     * Creates a CrowdTek user
     *
     * @param $user
     * @return string
     * @throws \Exception
     */
    public function createCMSUser(User $user)
    {
        $this->logger->debug("Attempting to create a CMS user ...");

        $userData = $user->getData();

        //        var_dump($userData);
        //        exit();

        $this->logger->debug("Sending request data to CMS[" . json_encode($userData) . "]");

        $sendRequest = $this->executeCMSUserCreate($userData);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");

        $response = $this->isSuccess($sendRequest);

        return $response;
    }

    /**
     * Update user data
     *
     * @param $userData
     * @return string
     * @throws \Exception
     */
    public function updateUserInfoFields(UserInfo $userData, $step)
    {
        $this->logger->debug("Attempting to update INFO Field of CMS user ...");

        //special case for setting the gdpr flag
        if (($userData->getContactViaEmail() == 1) || ($userData->getContactViaSms() == 1) || ($userData->getContactViaTele() == 1)) {
            $gdpr = 1;
        } else {
            $gdpr = 0;
        }

        switch ($step) {
            case 1:
                $userInfo = $userData->getCrmData();
                break;
            case 2:
                $userInfo = $userData->getPreferenceData();
                $this->setGDPR($userData);
                break;
            case 3:
                $userInfo = $userData->getQuestionnaireData();
                break;
            case 4:
                $userInfo = $userData->getQuestionnaireAttemptsData();
                break;
        }

        $userParameters = [
            'info' =>
            $userInfo

        ];

        $this->logger->debug("Sending request data to CMS[" . json_encode($userParameters) . "]");

        $sendRequest = $this->executeUpdateUser($userParameters);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");

        $response = $this->isSuccess($sendRequest);

        return $response;
    }

    public function generateGdprUpdateBody(UserInfo $userInfo): array
    {
        if (($userInfo->getContactViaEmail() == 1)) {
            $gdpr = 1;
        } else {
            $gdpr = 0;
        }
        return [
            'gdpr_accepted' => $gdpr,
        ];
    }


    public function setGDPR($userData)
    {
        $userParameters = $this->generateGdprUpdateBody($userData);

        $this->logger->debug("Sending request data to CMS[" . json_encode($userParameters) . "]");

        $sendRequest = $this->executeUpdateUser($userParameters);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");
    }

    /**
     * Patch user data
     *
     * @param $userData
     * @return string
     * @throws \Exception
     */
    public function patchUser(User $userData)
    {
        $this->logger->debug("Attempting to patch self user ...");

        $userParameters = $userData->getComplianceData();

        $this->logger->debug("Sending request data to CMS[" . json_encode($userParameters) . "]");

        $sendRequest = $this->executeUpdateUser($userParameters);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");

        $response = $this->isSuccess($sendRequest);

        return $response;
    }

    /**
     * Update user obStep
     *
     * @param $obStep
     * @return string
     * @throws \Exception
     */
    public function updateUserOBStep($obStep)
    {
        $this->logger->debug("Attempting to update OB Step CMS user ...");

        $userParameters = [
            'ob_step' => $obStep,
        ];

        $this->logger->debug("Sending request data to CMS[" . json_encode($userParameters) . "]");

        $sendRequest = $this->executeUpdateUser($userParameters);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");

        $response = $this->isSuccess($sendRequest);

        return $response;
    }

    /**
     * resend user verify email
     *
     * @param $userData
     * @return string
     * @throws \Exception
     */
    public function resendEmailVerification($url, $email)
    {
        $this->logger->debug("Attempting to Resend User Verify Email ...");

        $userParameters = [
            'url' => $url,
            'email' => $email,
        ];

        $this->logger->debug("Sending request data to CMS[" . json_encode($userParameters) . "]");

        $sendRequest = $this->executeResendEmailVerification($userParameters);

        $this->logger->debug("Got response from CMS[" . json_encode($sendRequest) . "]");

        $response = $this->isSuccess($sendRequest);

        return $response;
    }


    // Execute api calls from UserService

    protected function executeCMSUserCreate($userParameters)
    {
        return $this->userService->signupUser($this->network, $userParameters);
    }

    protected function executeUpdateUser($userParameters)
    {
        return $this->userService->update($this->network, $userParameters);
    }

    protected function executeResendEmailVerification($userParameters)
    {
        return $this->userService->resendVerifyEmail($userParameters);
    }

    private function isSuccess($responseArray)
    {
        if (!empty($responseArray['outcome']) && $responseArray['outcome'] == 'success') {
            return true;
        } elseif (!empty($responseArray['data']['user_message'])) {
            return $responseArray['data']['user_message'];
        } elseif (!empty($responseArray['error']['message'])) {
            return $responseArray['error']['message'];
        } else {
            return json_encode($responseArray);
        }
    }

    /**
     * review the statuses from MP and Contego and send emails
     *
     * @param $complianceResponses
     *
     */
    public function reviewUserComplianceStatus($complianceResponses)
    {
        $this->logger->info("");

        $return_status = true;

        $mp_kyc_status = null;
        $contego_status = null;
        $email_tags = ['user_email' => null, 'admin_email' => null];

        //extract the statuses
        if (isset($complianceResponses['mp_user']['data']['kyclevel'])) {
            $mp_kyc_status = $complianceResponses['mp_user']['data']['kyclevel'];
        }
        if (isset($complianceResponses['contego']['data']['ContegoScore']['rag'])) {
            $contego_status = $complianceResponses['contego']['data']['ContegoScore']['rag'];
        }

        $this->logger->info('Processing - mp:' . $mp_kyc_status . ' : contego:' . $contego_status);

        //one of more of the responses from vendors couldn't be processed so something is wrong
        if (is_null($mp_kyc_status) || is_null($contego_status)) {
            $email_tags['user_email'] = EmailTagConstants::TYPE_OB_CONTACT;
            $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_CONTACT_ADMIN;
            $return_status = false;
        } else {
            $status_key = $mp_kyc_status . ':' . $contego_status;

            switch ($status_key) {
                case 'LIGHT:GREEN':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_COMPLETE;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_COMPLETE_ADMIN;
                    break;

                case 'REGULAR:GREEN':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_COMPLETE;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_COMPLETE_ADMIN;
                    break;

                case 'LIGHT:AMBER':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_RESUBMIT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_RESUBMIT_ADMIN;
                    $return_status = false;
                    break;

                case 'REGULAR:AMBER':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_RESUBMIT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_RESUBMIT_ADMIN;
                    break;

                case 'LIGHT:RED':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_CONTACT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_CONTACT_ADMIN;
                    $return_status = false;
                    break;

                case 'REGULAR:RED':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_CONTACT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_CONTACT_ADMIN;
                    $return_status = false;
                    break;

                case 'LIGHT:WAITING':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_RESUBMIT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_RESUBMIT_ADMIN;
                    $return_status = false;
                    break;

                case 'REGULAR:WAITING':
                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_RESUBMIT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_RESUBMIT_ADMIN;
                    break;

                default:

                    $email_tags['user_email'] = EmailTagConstants::TYPE_OB_CONTACT;
                    $email_tags['admin_email'] = EmailTagConstants::TYPE_OB_CONTACT_ADMIN;

                    $this->logger->error('Could not process the results for complaince, follow up with business team');

                    $return_status = false;

                    break;
            }
        }

        $this->sendComplianceEmails($email_tags);

        return $return_status;
    }

    private function sendComplianceEmails($email_tags)
    {
        $this->logger->info("");


        $data = [
            'emailtags' => [
                'user_email' => $email_tags['user_email'],
                'admin_email' => $email_tags['admin_email'],
            ]
        ];

        return $this->userService->sendCustomClientEmail($data);
    }

    // Check if a user has completed their on boarding stages, if not redirect them to onboarding
    // Use checkUserRegistered instead, can update this function to check specific status in future:
    public function checkRegistrationStatus($status = "registration_complete")
    {
        /*$userRes = $this->userService->getUserInfo();

        if (isset($userRes['registration_complete'])) {
            if ($userRes['registration_complete']== true) {
                $this->requestStack->getSession()->set('ob_complete', true);
            } else {
                $this->requestStack->getSession()->set('ob_complete', false);
            }
        }

        if($this->requestStack->getSession()->has('ob_complete') && $this->requestStack->getSession()->get('ob_complete')==false) {
            $this->addFlash('errors','You have not completed your Sign Up, please complete your Sign Up in order to invest');
            return false;
        }*/

        return true;
    }

    public function checkUserRegistered()
    {
        $userRes = $this->userService->getUserInfo();

        if (isset($userRes['registration_complete'])) {
            if ($userRes['registration_complete'] == true) {
                $this->requestStack->getSession()->set('ob_complete', true);
            } else {
                $this->requestStack->getSession()->set('ob_complete', false);
            }
        }

        if ($this->requestStack->getSession()->has('ob_complete') && $this->requestStack->getSession()->get('ob_complete') == false) {
            /** @var Session $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add('warning', 'Please complete your registration to continue');
            return false;
        }

        return true;
    }
}
