<?php

namespace App\Service\Manager;

use App\Entity\Address;
use App\Entity\Enum\UserStatus;
use App\Entity\Lifecycle\LifecycleInterface;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use App\Entity\UserLog;
use App\Entity\UserMail;
use App\Entity\UserStatusLog;
use App\Service\DocumentService;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use App\Service\SalesforceService;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Class UserManager
 *
 * @package App\Service\Manager
 */
class UserManager extends BaseManager
{
    /** @var  UserLifecycle */
    protected $lifecycle;

    /** @var  MailerService */
    protected $mailservice;

    /** @var  SalesforceService */
    protected $salesforceservice;

    /** @var  string $entityClass */
    protected $entityClass = User::class;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        Security $security,
        MailerService $mailservice,
        MangoPay $mangopayService,
        DocumentManager $documentManager,
        DocumentService $documentService,
        WorkflowInterface $investmentStateMachine,
        LifecycleInterface $lifecycle,
        SalesforceService $salesforceservice,
    ) {
        $this->mailservice = $mailservice;
        $this->lifecycle = $lifecycle;
        $this->salesforceservice = $salesforceservice;
        parent::__construct(
            $logger,
            $entityManager,
            $security,
            $mailservice,
            $documentManager,
            $documentService,
            $investmentStateMachine,
        );
    }

    /**
     * Gets the User lifecycle service
     *
     * @return UserLifecycle
     */
    public function getLifecycle()
    {
        return $this->lifecycle;
    }

    public function findByQuery(array $queryParams, bool $admin = false): array
    {
        $criteria = $this->getCriteria($queryParams);
        $sort = $this->getSortPreferences($queryParams['sort']);
        $auxiliaryFilters = $this->getAuxiliaryFilters($queryParams, $admin);

        $results = $this->findBy(
            $criteria,
            $sort,
            $queryParams['limit'],
            $queryParams['offset'],
        );
        $results = $this->applyAuxiliaryFilters($results, $auxiliaryFilters);

        return $results;
    }

    /**
     * Criteria supported
     * - id
     */
    public function getCriteria(array $queryParams): array
    {
        $criteria = [];

        // criteria with matching property names
        $criteriaAllowed = ['id'];
        foreach ($queryParams as $key => $query) {
            if (in_array($key, $criteriaAllowed)) {
                if (!empty($query)) {
                    $criteria[$key] = explode(',', $query);
                }
            }
        }

        // criteria with mapped property names

        // $this->getLogger()->info('Criteria: ' . json_encode($criteria));

        return $criteria;
    }

    /**
     * Filters supported
     * - status
     */
    public function getAuxiliaryFilters(array $queryParams, bool $admin = false): array
    {
        $auxiliaryFilters = [];

        if (
            $admin
            && (!empty($queryParams['status']) || $queryParams['status'] === '0')
        ) {
            $auxiliaryFilters['status'] = explode(',', $queryParams['status']);
        }
        return $auxiliaryFilters;
    }

    /**
     * Filters supported
     * - status
     */
    public function applyAuxiliaryFilters($collection, $filters)
    {
        $filteredCollection = [];
        // $this->getLogger()->info('Auxiliary filters: ' . json_encode($filters));

        foreach ($collection as $item) {
            if (
                isset($filters['status'])
                && !in_array(
                    UserLifecycle::getConvertedLifecycleStatus(
                        $item->getLifecycleStatus(),
                    ),
                    $filters['status'],
                )
            ) {
                continue;
            }
            $filteredCollection[] = $item;
        }
        return $filteredCollection;
    }

    //Create and return user address object

    /** Builds User documents
     *
     * @param User $user
     * @param $documents
     **/
    protected function buildFromDocuments($user, $documents)
    {
        $docmgr = $this->getDocumentManager();

        //cycle through all the documents and try and map them to a object fields
        foreach ($documents as $doc) {
            $user_doc = new UserDocument();
            $user_doc->setDocument($docmgr->buildDocument(
                $doc,
                'private',
                'user/' . $user->getId(),
            ));
            $user->addDocument($user_doc);
        }
    }

    /**
     * Builds a user object from an Array
     *
     * @param $param
     * @param $existUser
     *
     */
    public function buildUser($param, $existUser = null)
    {
        if ($existUser == null) {
            $user = new User();
        } else {
            $user = $existUser;
        }

        // Address object creation
        if (!empty($param->address)) {
            $addressObj = $this->buildAddress($param->address);

            //we are enforcing that there can be only 1 address associated with a user
            if ($user->getAddresses()->count() == 0) {
                //we don't have an address simply add it
                $user->addAddress($addressObj);
            } else {
                //we have an existing address so lets just update it
                $cur_address = $user->getMainAddress();
                $cur_address->setAddress1($addressObj->getAddress1());
                $cur_address->setAddress2($addressObj->getAddress2());
                $cur_address->setAddress3($addressObj->getAddress3());
                $cur_address->setCity($addressObj->getCity());
                $cur_address->setRegion($addressObj->getRegion());
                $cur_address->setCountry($addressObj->getCountry());

                $cur_address->setPostCode($addressObj->getPostCode());
            }

            /*
             * $exists =  function($key, $element) use ($addressObj){
             * return $element->asString()=== $addressObj->asString() ;
             * };
             *
             * if ( $user->getAddresses()->exists($exists) === false)
             * {
             * $user->getAddresses()->clear();
             * $user->addAddress($addressObj);
             * }
             */
        }

        if (!empty($param->info)) {
            $this->buildFromInfo($user, $param->info);
        }

        if (!empty($param->documents)) {
            $this->buildFromDocuments($user, $param->documents);
        }

        if (!empty($param->first_name)) {
            $user->setFirstname($param->first_name);
        }
        if (!empty($param->given_name)) {
            $user->setFirstname($param->given_name);
        }

        if (!empty($param->last_name)) {
            $user->setLastname($param->last_name);
        }
        if (!empty($param->family_name)) {
            $user->setLastname($param->family_name);
        }

        if (!empty($param->email)) {
            $user->setEmail($param->email);
        }

        if (!empty($param->type)) {
            $user->setType($param->type);
        }
        if (!empty($param->last_login_at)) {
            $user->setLastLoginAt($param->last_login_at);
        }
        if (!empty($param->set_password_expiry)) {
            $user->setSetPasswdExpiry($param->set_password_expiry);
        }
        if (!empty($param->gender)) {
            $user->setGender($param->gender);
        }
        if (!empty($param->middle_name)) {
            $user->setMiddlename($param->middle_name);
        }
        if (!empty($param->honoric_prefix)) {
            $user->setHonoricPrefix($param->honoric_prefix);
        }
        if (!empty($param->honoric_suffix)) {
            $user->setHonoricSuffix($param->honoric_suffix);
        }
        if (!empty($param->job_title)) {
            $user->setJobTitle($param->job_title);
            $user->setOccupation($param->job_title);
        }
        if (!empty($param->location)) {
            $user->setLocation($param->location);
        }
        if (!empty($param->nationality)) {
            $user->setNationality(Helper::getCountryCode($param->nationality));
        }
        if (!empty($param->mobile)) {
            $user->setMobile($param->mobile);
        }
        if (!empty($param->tax_id)) {
            $user->setTaxId($param->tax_id);
        }
        if (!empty($param->birth_country)) {
            $user->setBirthCountry(Helper::getCountryCode($param->birth_country));
        }
        if (!empty($param->birth_date)) {
            $user->setBirthDate(new \DateTime($param->birth_date));
        }
        if (!empty($param->birth_place)) {
            $user->setBirthPlace($param->birth_place);
        }
        if (!empty($param->driving_license_number)) {
            $user->setDrivingLicenseNo($param->driving_license_number);
        }
        if (!empty($param->passport_number)) {
            $user->setPassportNumber($param->passport_number);
        }
        if (!empty($param->passport_country)) {
            $user->setPassportCountry(Helper::getCountryCode($param->passport_country));
        }
        if (!empty($param->passport_expiry)) {
            $user->setPassportExpiry(new \DateTime($param->passport_expiry));
        }
        if (!empty($param->income_range)) {
            $user->setIncomeRange($param->income_range);
        }

        if (!empty($param->mango_pay_user_id)) {
            $user->setMangoPayUserId($param->mango_pay_user_id);
        }

        if (!empty($param->visibility)) {
            $user->setVisibility($param->visibility);
        }
        if (!empty($param->is_vip)) {
            $user->setisVIP($param->is_vip);
        }
        if (!empty($param->additional_name)) {
            $user->setAdditionalName($param->additional_name);
        }
        if (!empty($param->additional_type)) {
            $user->setAdditionalType($param->additional_type);
        }
        if (!empty($param->referral_code)) {
            $user->setReferralCode($param->referral_code);
        }
        if (!empty($param->external_reference_id)) {
            $user->setExternalReferenceId($param->external_reference_id);
        }
        if (!empty($param->phone_1)) {
            $user->setPhone1($param->phone_1);
        }
        if (!empty($param->phone_2)) {
            $user->setPhone2($param->phone_2);
        }
        if (!empty($param->affiliate_code)) {
            $user->setAffiliateCode($param->affiliate_code);
        }
        if (!empty($param->biography)) {
            $user->setBiography($param->biography);
        }
        if (!empty($param->honorific_prefix)) {
            $user->setHonoricPrefix($param->honorific_prefix);
        }
        if (!empty($param->honorific_suffix)) {
            $user->setHonoricSuffix($param->honorific_suffix);
        }
        if (!empty($param->web_site)) {
            $user->setWebsite($param->web_site);
        }
        if (!empty($param->web_site)) {
            $user->setWebsite($param->web_site);
        }
        if (!empty($param->term_service_accepted)) {
            $user->setTermServiceAccepted($param->term_service_accepted);
        }
        if (isset($param->gdpr_accepted)) {
            $user->setGDPRAccepted($param->gdpr_accepted);
        }
        if (!empty($param->ob_step)) {
            $user->setOBStep($param->ob_step);
        }
        if (!empty($param->mifid_status)) {
            $user->setMIFIDStatus($param->mifid_status);
        }

        return $user;
    }

    public static function buildAddress($param)
    {
        $userAddress = new Address();
        if (!empty($param->address1)) {
            $userAddress->setAddress1($param->address1);
        }
        // Alternative address line 1.
        if (!empty($param->building)) {
            $userAddress->setAddress1($param->building);
        }
        if (!empty($param->address2)) {
            $userAddress->setAddress2($param->address2);
        }
        // Alternative address line 2.
        if (!empty($param->street_address)) {
            $userAddress->setAddress2($param->street_address);
        }
        if (!empty($param->address3)) {
            $userAddress->setAddress3($param->address3);
        }
        if (!empty($param->city)) {
            $userAddress->setCity($param->city);
        }
        if (!empty($param->country)) {
            $userAddress->setCountry(Helper::getCountryCode($param->country));
        }
        if (!empty($param->region)) {
            $userAddress->setRegion($param->region);
        }
        if (!empty($param->postcode)) {
            $userAddress->setPostCode($param->postcode);
        }
        if (!empty($param->postal_code)) {
            $userAddress->setPostCode($param->postal_code);
        }
        return $userAddress;
    }

    /**
     * Builds the company, investor object from the info collection
     *
     * @param User $user
     * @param $infos
     */
    protected function buildFromInfo(User $user, $infos)
    {
        //cycle through all the info and try and map them to a object fields
        foreach ($infos as $type => $value) {
            switch ($type) {
                //check for the investor fields
                case 'income_range':
                    $user->setIncomeRange($value);
                    break;
                case 'job_title':
                    $user->setOccupation($value);
                    break;
                case 'referral':
                    $user->setReferralCode($value);
                    break;
                case 'cxb_worth_investor':
                    $user->getInvestor()->setCxbWorthInvestor($value);
                    break;
                case 'cxb_sophisticated_investor':
                    $user->getInvestor()->setCxbSophisticatedInvestor($value);
                    break;
                case 'cxb_restricted_investor':
                    $user->getInvestor()->setCxbRestrictedUser($value);
                    break;
                case 'cxb_ltd_company_investor':
                    $user->getInvestor()->setCxbLtdCompInvestor($value);
                    break;
                case 'always_go_up':
                    $user->getInvestor()->setAlwaysGoUp($value);
                    break;
                case 'income_every_month':
                    $user->getInvestor()->setIncomeEveryMonth($value);
                    break;
                case 'never_exit':
                    $user->getInvestor()->setNeverExit($value);
                    break;
                case 'custom.poi_file_id':
                    $user->getInvestor()->setPoiFileId($value);
                    break;
                case 'poi_file_id':
                    $user->getInvestor()->setPoiFileId($value);
                    break;
                case 'words_of_own':
                    $user->getInvestor()->setWordsOfOwn($value);
                    break;
                case 'words_of_your_own':
                    $user->getInvestor()->setWordsOfOwn($value);
                    break;

                case 'organization_name':
                    $user->getCompany()->setName($value);
                    break;
                case 'position':
                    $user->getCompany()->setPosition($value);
                    break;

                //check for the compnay fields
                case 'corporate_investor':
                    $user->getInvestor()->setCorporateInvestor($value);
                    break;
                case 'company_beneficial_owners':
                    $user->getCompany()->setBeneficialOwners($value);
                    break;
                case 'company_directors':
                    $user->getCompany()->setDirectors($value);
                    break;

                case 'registration_country':
                    $user->getCompany()->setRegCountry($value);
                    break;
                case 'reg_country':
                    $user->getCompany()->setRegCountry($value);
                    break;
                case 'company_registration_country':
                    $user->getCompany()->setRegCountry($value);
                    break;
                case 'business_nature':
                    $user->getCompany()->setBusinessNature($value);
                    break;
                case 'company_nature_of_business':
                    $user->getCompany()->setBusinessNature($value);
                    break;
                case 'name':
                    $user->getCompany()->setName($value);
                    break;
                case 'company_name':
                    $user->getCompany()->setName($value);
                    break;
                case 'company_registered_address_1':
                    $user->getCompany()->setRegAddress1($value);
                    break;
                case 'reg_address_1':
                    $user->getCompany()->setRegAddress1($value);
                    break;
                case 'company_registered_address_2':
                    $user->getCompany()->setRegAddress2($value);
                    break;
                case 'reg_address_2':
                    $user->getCompany()->setRegAddress2($value);
                    break;
                case 'company_registered_address_3':
                    $user->getCompany()->setRegAddress3($value);
                    break;
                case 'reg_address_3':
                    $user->getCompany()->setRegAddress3($value);
                    break;
                case 'telephone':
                    $user->getCompany()->setTelephone($value);
                    break;
                case 'company_telephone':
                    $user->getCompany()->setTelephone($value);
                    break;
                case 'postcode':
                    $user->getCompany()->setPostCode($value);
                    break;
                case 'post_code':
                    $user->getCompany()->setPostCode($value);
                    break;
                case 'company_postcode':
                    $user->getCompany()->setPostCode($value);
                    break;

                case 'building_name':
                    $user->getCompany()->setBuildingName($value);
                    break;
                case 'company_website':
                    $user->getCompany()->setCompanyWebsite($value);
                    break;
                case 'operating_address':
                    $user->getCompany()->setOperatingAddress($value);
                    break;
                case 'operating_postcode':
                    $user->getCompany()->setOperatingPostCode($value);
                    break;
                case 'registration_number':
                    $user->getCompany()->setRegistrationNumber($value);
                    break;
                case 'company_registered_number':
                    $user->getCompany()->setRegistrationNumber($value);
                    break;
                case 'other_name':
                    $user->getCompany()->setOtherName($value);
                    break;
                case 'company_other_name':
                    $user->getCompany()->setOtherName($value);
                    break;
                default:
                    //lets add anything else to the customfields
                    $cf = new UserCustomFields();
                    $cf->setFieldKey($type);
                    $cf->setFieldValue($value);
                    $user->findReplaceCustomField($cf);

                    break;
            }
        }
    }

    /**
     * Update user lifecycleStatus and ob_step if not already
     */
    public function verifyEmail(User $user): User
    {
        $this->getLogger()->debug('Processing email verification');

        if ($user->getOBStep() < OB_STEP_CONSTANT::STEP2_INT) {
            $user->setOBStep(OB_STEP_CONSTANT::STEP2_INT);
            $this->getLogger()->info(
                "User #{$user->getId()} updated ob_step to "
                . OB_STEP_CONSTANT::STEP2_INT,
            );
        } else {
            $this->getLogger()->notice(
                "User #{$user->getId()} already at or passed ob_step "
                . OB_STEP_CONSTANT::STEP2_INT,
            );
        }

        if ($user->getLifecycleStatus() == UserLifecycle::STATE_EMAIL_NOT_VERIFIED) {
            $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_EMAIL_VERIFIED);
            $this->getLogger()->info("User #{$user->getId()} email verified");

            $log = new UserLog();
            $log
                ->setUser($user)
                ->setType(UserLog::TYPE_USER)
                ->setEvent(UserLifecycle::TRANSITION_EMAIL_VERIFICATION)
                ->setMessage(
                    'You verified your email ['
                    . $user->getEmailCanonical()
                    . '] on %timestamp%',
                );
            $user->addLog($log);
        } else {
            $this->getLogger()->notice("User #{$user->getId()} already verified email");
        }

        if ($user->getCurrentStatus() == UserStatus::Pending) {
            $user->addStatusLog(new UserStatusLog(status: UserStatus::Active));
        } else {
            $this->getLogger()->notice("User #{$user->getId()} already active.");
        }

        $this->getEntityManager()->flush();
        return $user;
    }

    /**
     * Marks a user's registration completed
     *
     * @param User $user
     * @return $this
     */
    public function completeRegistration(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $this->lifecycle->applyTransition(
            $user,
            UserLifecycle::TRANSITION_REGISTRATION_COMPLETE,
        );

        $log = new UserLog();

        $log
            ->setUser($user)
            ->setType(UserLog::TYPE_USER)
            ->setEvent(UserLifecycle::TRANSITION_REGISTRATION_COMPLETE)
            ->setMessage('You completed your registration on %timestamp%');

        $user->addLog($log);

        $this->getEntityManager()->flush();

        return $this;
    }

    /**
     * Approves a User
     *
     * @param User $user
     * @return $this
     */
    public function approveUser(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $this->lifecycle->applyTransition($user, UserLifecycle::TRANSITION_APPROVE);

        $this->userApprovedMail($user);
        $log = new UserLog();

        $log
            ->setUser($user)
            ->setType(UserLog::TYPE_USER)
            ->setEvent(UserLifecycle::TRANSITION_APPROVE)
            ->setMessage('Your account was approved on %timestamp%');

        $user->addLog($log);

        $this->getEntityManager()->flush();

        return $this;
    }

    public function userApprovedMail($user)
    {
        $this->getLogger()->info($user->getUsername());

        $sent = $this->mailservice->sendMail($user, MailerService::TYPE_USER_APPROVED, [
            'user' => $user,
        ]);
        if ($sent == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Blocks a User
     *
     * @param User $user
     * @return $this
     */
    public function mangoPayUserFromApprove(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $this->lifecycle->applyTransition(
            $user,
            UserLifecycle::TRANSITION_MANGOPAY_REGISTRATION,
        );

        $user->setEnabled(false);

        $log = new UserLog();

        $log
            ->setUser($user)
            ->setType(UserLog::TYPE_USER)
            ->setEvent(UserLifecycle::TRANSITION_MANGOPAY_REGISTRATION)
            ->setMessage('Your account was approved on %timestamp%');

        $user->addLog($log);

        $this->getEntityManager()->flush();

        return $this;
    }

    /**
     * Function Using for registering Email
     *
     * @Param User $user
     * @Param string $url
     */
    public function sendRegistrationMail(User $user, $url)
    {
        $this->getLogger()->info('Sending Registration Mail:' . $user->getUsername());

        $email = $this->mailservice->sendMail(
            $user,
            MailerService::TYPE_USER_REGISTRATION,
            [
                'user' => $user,
                'url' => $url,
            ],
        );

        return $email;
    }

    /**
     * Function Using for sending reject GDPR Email
     *
     * @Param User $user
     */
    public function sendGDPR_RejectMail(User $user)
    {
        $this->getLogger()->info('Sending GDPR Reject Mail:' . $user->getUsername());

        $email = $this->mailservice->adminMailEntry(
            $user,
            MailerService::TYPE_USER_REJECT_GDPR,
            ['user' => $user],
            null,
        );

        return $email;
    }

    /***
     *
     * Review the state of the user and set/trigger actions
     *
     * @param User $user
     */
    public function manageUserState(User $user)
    {
        if ($user->getContegoScore() === null) {
            return;
        }

        try {
            if (
                $user->getOBStep() == OB_STEP_CONSTANT::STEP5_INT
                and $user->getContegoScore()->getRAG() == 'GREEN'
            ) {
                $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_REGISTRATION_COMPLETE);
                $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_APPROVED);

                $this->getEntityManager()->flush();

                $this->getLogger()->info('auto registration complete');
            } else {
                $this->getLogger()->info('wasnt able to auto registration complete');
            }
        } catch (\Exception $ex) {
            $this->getLogger()->info('wasnt able to auto registration complete');
        }
    }

    public function blockUser(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $userCurrentStatus = $user->getLifecycleStatus();
        switch ($userCurrentStatus) {
            case UserLifecycle::STATE_APPROVED:
                $this->blockUserFromApprove($user);
                break;
            case UserLifecycle::STATE_REGISTRATION_COMPLETE:
                $this->blockUserFromRegistration($user);
                break;
            default:
                $this->blockUserFromOtherState($user);
                break;
        }

        return $this;
    }

    /**
     * Blocks a User
     *
     * @param User $user
     * @return $this
     */
    public function blockUserFromApprove(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $this->lifecycle->applyTransition(
            $user,
            UserLifecycle::TRANSITION_APPROVE_TO_BLOCK,
        );

        $user->setEnabled(false);

        $log = new UserLog();

        $log
            ->setUser($user)
            ->setType(UserLog::TYPE_USER)
            ->setEvent(UserLifecycle::TRANSITION_APPROVE_TO_BLOCK)
            ->setMessage('Your account was approved on %timestamp%');

        $user->addLog($log);

        $this->getEntityManager()->flush();

        return $this;
    }

    public function blockUserFromRegistration(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $this->lifecycle->applyTransition(
            $user,
            UserLifecycle::TRANSITION_REGISTRATION_TO_BLOCK,
        );

        $user->setEnabled(false);

        $log = new UserLog();

        $log
            ->setUser($user)
            ->setType(UserLog::TYPE_USER)
            ->setEvent(UserLifecycle::TRANSITION_REGISTRATION_TO_BLOCK)
            ->setMessage('Your account was approved on %timestamp%');

        $user->addLog($log);

        $this->getEntityManager()->flush();

        return $this;
    }

    public function blockUserFromOtherState(User $user)
    {
        $this->getLogger()->info($user->getUsername());

        $user->setEnabled(false);
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);

        $this->getEntityManager()->flush();

        return $this;
    }

    public function sendForgotPasswordMail(User $user, $url)
    {
        return $this->mailservice->sendMail(
            $user,
            MailerService::TYPE_USER_PASSWORD_FORGOT,
            [
                'user' => $user,
                'url' => $url,
            ],
        );
    }

    public function syncWithSalesforce(
        User $user,
        bool $createIfMissing = false,
        array $extraFields = [],
    ) {
        $this->getLogger()->debug('Attempt Salesforce sync for user#' . $user->getId());

        $sf_id = $user->findCustomFieldValue('salesforce_id');
        // $extraFields have lower priority than user sourced fields to prevent accidental invalid overwrites
        $fieldsToSync = array_merge($extraFields, $user->getSalesforceJson());
        $response = [
            'type' => 'error', // this will be changed to success if no problems
            'message' => 'User successfully synced with Salesforce', // this will be changed to error message if problems
        ];

        // If the Salesforce ID is missing and we're not creating a new record
        // Update the message to reflect the missing id
        if ($sf_id == '' && !$createIfMissing) {
            $response['message'] = 'No Salesforce id found for user';
        } else {
            try {
                // If the Salesforce ID is missing and we ARE creating a new record, do so
                // Otherwise just sync with Salesforce as normal
                if ($sf_id == '' && $createIfMissing) {
                    $sfcreateResponse = $this->salesforceservice->create(
                        'Contact',
                        $fieldsToSync,
                    );
                    $sfbody = json_decode($sfcreateResponse->getBody(), true);
                    $userCusField = new UserCustomFields();
                    $userCusField->setFieldKey('salesforce_id');
                    $userCusField->setFieldValue($sfbody['id']);
                    $user->findReplaceCustomField($userCusField);

                    // $this->entityManager->persist($userCusField);
                } else {
                    $this->salesforceservice->update('Contact', $sf_id, $fieldsToSync);
                }
                $response['type'] = 'success';
                $this->getLogger()->info(
                    'Successful Salesforce sync for user#' . $user->getId(),
                );
            } catch (ClientException $e) {
                $response['message'] =
                    'Salesforce not synced: '
                    . $e->getResponse()->getStatusCode()
                    . ' - '
                    . $e->getResponse()->getBody();
            } catch (ServerException $e) {
                $response['message'] =
                    'Salesforce not synced: '
                    . $e->getResponse()->getStatusCode()
                    . ' - Unable to reach Salesforce';
            } catch (\Exception $e) {
                $response['message'] =
                    'Salesforce not synced: Error when trying to contact Salesforce: '
                    . $e;
            }
        }
        if ($response['type'] == 'error') {
            $this->getLogger()->error($response['message']);
        }
        return $response;
    }

    public function sendPasswordChangeConfirmationMail(User $user)
    {
        try {
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_USER_PASSWORD_CHANGE_CONF,
                [
                    'user' => $user,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            $this->getLogger()->error('========================= EXCEPTION =========== '
            . $ex);
            return false;
        }
    }
}
