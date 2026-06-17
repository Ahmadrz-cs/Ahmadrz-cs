<?php

namespace App\Service;

use App\Entity\ContegoLog;
use App\Entity\ContegoScore;
use App\Entity\KycReport;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Psr\Log\LoggerInterface;
use SameerShelavale\PhpCountriesArray\CountriesArray;

//This is what appears in the Transaction column in the contego Checks history
const TRANSACTION = 'CV Check';

class ContegoService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private DocumentService $documentService,
        private ?string $contego_organisation_uid,
        private ?string $contego_person_uid,
        private ?string $contego_company_uid,
        private ?string $contego_md5,
        private ?string $contego_url,
        private ?string $contego_transaction_ref,
    ) {}

    /**
     * Checks an organisation against contego KYC
     *
     * @param \App\Entity\User $user
     * @return string
     * @throws \Exception
     */
    public function createOrganisationKYC(\App\Entity\User $user)
    {
        //Make sure we have all the fields required for contego request
        $this->isUserValidForContego($user);

        /// ---- Set up the data for COMPANY ---- ///
        //Company phone
        $companyPhoneNumber = [
            'countryCode' => null,
            'fullNumber' => $user->getCompany()->getTelephone(),
            'lineCategory' => 'LANDLINE',
            'areaCode' => null,
            'subscriberNumber' => null,
        ];

        //Company Principle Address/Operating address
        $companyPrincipleAddress = [
            'postcode' => $user->getCompany()->getPostCode(),
            'address1' => $user->getCompany()->getRegAddress1(),
            'address2' => $user->getCompany()->getRegAddress2(),
            'address3' => $user->getCompany()->getRegAddress3(),
            'country' => $this->getCountryCode($user->getCompany()->getRegCountry()), // We need GB not United Kingdom
            // "country"=> $user->getCompany()->getRegCountry(),
            'town' => null,
            'function' => null,
            'addressType' => null,
            'address4' => null,
            'address5' => null,
            'address6' => null,
            'county' => null,
            'dps' => null,
            'state' => null,
        ];

        //Company Address
        $companyRegisteredAddress = [
            'postcode' => $user->getCompany()->getPostCode(),
            'address1' => $user->getCompany()->getRegAddress1(),
            'address2' => $user->getCompany()->getRegAddress2(),
            'address3' => $user->getCompany()->getRegAddress3(),
            //"country"=> $user->getCompany()->getRegCountry(),
            'country' => $this->getCountryCode($user->getCompany()->getRegCountry()), // We need GB not United Kingdom
            'town' => null,
            'function' => null,
            'addressType' => null,
            'address4' => null,
            'address5' => null,
            'address6' => null,
            'county' => null,
            'dps' => null,
            'state' => null,
        ];

        $company = [
            'registeredAddress' => $companyRegisteredAddress,
            'companyNumber' => $user->getCompany()->getRegistrationNumber(),
            'companyName' => $user->getCompany()->getName(),
            'website' => $user->getCompany()->getCompanyWebsite(),
            'companyType' => null,
            'principalPlace' => $companyPrincipleAddress,
            'companyPhoneNumber' => $companyPhoneNumber,
            //"registrationCountry" => $user->getCompany()->getRegCountry()
            'registrationCountry' => $this->getCountryCode($user->getCompany()->getRegCountry()), // We need GB not United Kingdom
        ];

        /// ---- Set up the data for CONTACT/USER for the company  ---- ///
        //        $companyOtherContact = array(
        //            $user->getCompany()->getBeneficialOwners(),
        //            $user->getCompany()->getDirectors(),
        //        );

        // Main contact/user phone number
        $companyMainContactPhoneNumber = [
            [
                'countryCode' => null,
                'fullNumber' => $user->getPhone1(),
                'lineCategory' => 'LANDLINE',
                'areaCode' => null,
                'subscriberNumber' => null,
            ],
        ];

        // Main contact/user address
        $companyMainContactAddress = [
            'addressType' => 'Current Address',
            'postcode' => $user->getMainAddress()->getPostCode(),
            'town' => $user->getMainAddress()->getCity(),
            'address1' => $user->getMainAddress()->getAddress1(),
            'address2' => $user->getMainAddress()->getAddress2(),
            'address3' => $user->getMainAddress()->getAddress3(),
            'address4' => null,
            'address5' => null,
            'state' => $user->getMainAddress()->getRegion(),
            'country' => $user->getMainAddress()->getCountry(),
        ];

        $utcDob = new \DateTime(
            $user->getBirthDate()->format('Y-m-d'),
            new \DateTimeZone('UTC'),
        );

        $companyMainContact = [
            'gender' => $user->getGender(),
            'forename' => $user->getFirstname(),
            'personType' => 'Person Type',
            'emailAddress' => [$user->getEmail()],
            'nationality' => $user->getNationality(),
            'natureOfBusiness' => $user->getCompany()->getBusinessNature(),
            'isOwner' => true,
            'dob' => $utcDob->getTimestamp() * 1000, //Convert to milliseconds!
            'isFullElectoralRoll' => true,
            'isDirector' => true,
            'ownerType' => null,
            'surname' => $user->getLastname(),
            'middlename' => $user->getMiddleName(),
            'title' => $user->getHonoricPrefix(),
            //"companyType"           => "Company Type",
            'phoneNumber' => $companyMainContactPhoneNumber,
            'address' => $companyMainContactAddress,
            //"passport"              => $passport,
        ];

        $credentials = [
            'organisationUID' => $this->contego_organisation_uid,
            'profileUID' => $this->contego_company_uid,
            'md5Signature' => $this->contego_md5,
        ];

        $header = [
            'transactionRef' => $this->contego_transaction_ref,
        ];

        $checkCompany = [
            'checkCompany' => [
                'companyContacts' => [
                    $companyMainContact,
                ],
                'company' => $company,
            ],
            'checkTransaction' => null,
            'checkPerson' => null,
            'credentials' => $credentials,
            'header' => $header,
        ];

        $json = json_encode($checkCompany);
        $this->logger->info("... sending json request to contego json-data = [$json]");

        //Get the response from the request to contego
        $response = $this->getContegoResponse($json);

        $this->logger->info(
            '... Data returned from contego = [' . json_encode($response) . ']',
        );

        $return_data = json_decode($response->getBody());

        //Make sure we got a valid contego response
        $this->validateContegoResponse($return_data);

        //Update the contego logs and user information
        $this->updateContegoLog($user, $return_data);
        $this->updateUserContegoScore($user, $return_data);

        //Save to database
        $this->em->flush();

        //return the response
        $result['outcome'] = 'success';
        $result['status'] = '200';
        $result['data']['ContegoScore']['score'] =
            $return_data->{'contegoScore'}->{'score'};
        $result['data']['ContegoScore']['rag'] =
            $return_data->{'contegoScore'}->{'rag'};
        $result['data']['ContegoScore']['alerts'] =
            $this->getAlertMessage($return_data);

        return $result;
    }

    /**
     * Makes sure the data returned from contego is valid
     *
     * @param $contegoData
     * @return string
     * @throws \Exception
     */
    private function validateContegoResponse($contegoData)
    {
        /*
         * See issue https://gitlab.helpmewithit.com:7055/yielders2/Phase2/issues/234
         * When contego returns a response of 0 for score doing a createUserKYCWithDoc
         * The ContegoService->validateContegoResponse was throwing an Contego API call did not contain a valid response for [score] exception
         * So have to put in an additional check with && (see below)
         */

        if (
            $contegoData->{'contegoScore'}->{'score'} != '0'
            && $contegoData->{'contegoScore'}->{'score'} == ''
        ) {
            throw new \Exception(
                'Contego API call did not contain a valid response for [score]',
            );
        } elseif ($contegoData->{'contegoScore'}->{'rag'} == '') {
            throw new \Exception(
                'Contego API call did not contain a valid response for [rag]',
            );
        } elseif ($contegoData->{'header'}->{'requestRef'} == '') {
            throw new \Exception(
                'Contego API call did not contain a valid response for [requestRef]',
            );
        } else {
            //All look good
            return;
        }

        // Sometimes we don't get a pdfreport
        // } elseif ($contegoData->{'header'}->{'pdfreport'}=='') {
        // throw new \Exception("Contego API call did not contain a valid response for [pdfreport]");
    }

    /**
     * Checks a user against contego KYC
     *
     * @param \App\Entity\User $user
     * @return string
     * @throws \Exception
     */
    public function createUserKYC(\App\Entity\User $user, bool $apiV2User = false)
    {
        //Make sure we have all the fields required for contego request

        $this->logger->info('doing service ... ');
        $this->isUserValidForContego($user);

        if ($apiV2User) {
            $mobile = $user->getMobile();
        } else {
            $mobile = $user->getPhone2();
        }

        $phoneNumber = [
            [
                'countryCode' => null,
                'fullNumber' => $mobile,
                'lineCategory' => 'MOBILE',
                'areaCode' => null,
                'subscriberNumber' => null,
            ],
        ];

        $address = [
            'addressType' => 'Current Address',
            'postcode' => $user->getMainAddress()->getPostCode(),
            'town' => $user->getMainAddress()->getCity(),
            'address1' => $user->getMainAddress()->getAddress1(),
            'address2' => $user->getMainAddress()->getAddress2(),
            'address3' => $user->getMainAddress()->getAddress3(),
            'address4' => '',
            'address5' => '',
            'state' => $user->getMainAddress()->getRegion(),
            'country' => $this->getCountryCode($user->getMainAddress()->getCountry()), // We need GB not United Kingdom
        ];

        /*
         * Passport is not used for the first contego check (as we do not upload document at this point)
         * However to get a contego RED user for test purposes we need to send a passport serial number of 12345678.
         * This value is only set if the users firstname is contego and last name is red.
         */

        $serialNumber = null;

        if ($user->getFirstname() == 'contego' && $user->getLastname() == 'red') {
            $serialNumber = 12345678;
        }

        $passport = [
            'forename' => $user->getFirstname(),
            'documentID' => null,
            'passportExpiryDate' => null,
            'dob' => null,
            'uploadedImageID' => null,
            'passportMRZ1' => null,
            'passportMRZ2' => null,
            'notes' => null,
            'visbleImage' => null,
            'irimage' => null,
            'uvimage' => null,
            'chipFacialImage' => null,
            'chipFingerprintImage' => null,
            'chipIrisImage' => null,
            'serialNumber' => $serialNumber,
            'surname' => null,
            'country' => null,
        ];

        $utcDob = new \DateTime(
            $user->getBirthDate()->format('Y-m-d'),
            new \DateTimeZone('UTC'),
        );

        $person = [
            'person' => [
                'gender' => $user->getGender(),
                'middlename' => $user->getMiddleName(),
                'title' => $user->getHonoricPrefix(),
                'forename' => $user->getFirstname(),
                'personType' => 'Person Type',
                'emailAddress' => [$user->getEmail()],
                'isFullElectoralRoll' => true,
                'surname' => $user->getLastname(),
                'phoneNumber' => $phoneNumber,
                'address' => $address,
                'passport' => $passport,
                'dob' => $utcDob->getTimestamp() * 1000, //Convert to milliseconds!
            ],
        ];

        $credentials = [
            'organisationUID' => $this->contego_organisation_uid,
            'profileUID' => $this->contego_person_uid,
            'md5Signature' => $this->contego_md5,
        ];

        $header = [
            'transactionRef' => $this->contego_transaction_ref,
        ];

        $checkPerson = [
            'checkPerson' => $person,
            'credentials' => $credentials,
            'header' => $header,
        ];

        $json = json_encode($checkPerson);

        $this->logger->info("... sending json request to contego json-data = [$json]");

        //Get the response from the request to contego
        $response = $this->getContegoResponse($json);

        $return_data = json_decode($response->getBody());

        $this->logger->info(
            '... Data returned from contego = [' . $response->getBody() . ']',
        );

        //Make sure we got a valid contego response
        $this->validateContegoResponse($return_data);
        $this->logger->info('validated');

        //Update the contego logs and user information
        $this->updateContegoLog($user, $return_data);
        $this->logger->info('contegolog updated');

        $this->updateUserContegoScore($user, $return_data);
        $this->logger->info('contegoscore updated');

        //Save to database
        $this->em->flush();
        $this->logger->info('database updated');

        //return the response
        $result['outcome'] = 'success';
        $result['status'] = '200';
        $result['data']['ContegoScore']['score'] =
            $return_data->{'contegoScore'}->{'score'};
        $result['data']['ContegoScore']['rag'] =
            $return_data->{'contegoScore'}->{'rag'};
        $result['data']['ContegoScore']['alerts'] =
            $this->getAlertMessage($return_data);

        $this->logger->info('sending response...' . json_encode($result));

        return $result;
    }

    /**
     * @param $json - json encoded parameters for contego
     * @throws \Exception
     */
    public function getContegoResponse($json): \Psr\Http\Message\ResponseInterface
    {
        try {
            $client = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);
            $url = $this->contego_url . '/rest/v2/check';

            $response = $client->post($url, ['body' => $json, 'http_errors' => true]);

            $this->logger->warning(json_encode($response->getBody()));
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            // problems with the response back from contego
            // This prints the full error rather than the truncated error from getMessage()
            //var_dump("==============" . $ce->getResponse()->getBody() . "============");
            $this->logger->error(Psr7\Message::toString($ce->getResponse()));

            //Exception should be caught in controller
            throw new \Exception($ce->getMessage());
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            // Connection failed or some other network error
            //var_dump($e->getMessage());
            $this->logger->error(Psr7\Message::toString($e->getRequest()));

            //Exception should be caught in controller
            throw new \Exception($e->getMessage());
        } catch (RequestException $re) {
            // var_dump($re->getMessage());

            $this->logger->error(Psr7\Message::toString($re->getResponse()));
            //Exception should be caught in controller
            throw new \Exception($re->getMessage());
        }

        return $response;
    }

    /**
     *
     *
     * @param $contegoData - The response recieved from contego request
     * @return string - A quoted and comma delimited concatenation of all the alert messages in the contego response.
     */
    private function getAlertMessage($contegoData)
    {
        $contegoAlertMessageArray = [];

        foreach ($contegoData->{'contegoScore'}->{'alert'} as $value) {
            //Clean up the message string
            $value->{'message'} = str_replace(["\n", "\t"], '', $value->{'message'});
            $contegoAlertMessageArray[] = $value->{'message'};
        }

        if (count($contegoAlertMessageArray) > 0) {
            $contegoAlertMessage =
                "\"" . implode("\",\"", $contegoAlertMessageArray) . "\"";
        } else {
            $contegoAlertMessage = "\"" . 'No contego messages found' . "\"";
        }

        return $contegoAlertMessage;
    }

    /**
     * Checks a user against contego KYC
     *
     * @param \App\Entity\User $user
     * @param \App\Entity\UserDocument $taggedDocument
     *
     * @return string
     * @throws \Exception
     */
    public function createUserKYCWithDoc(
        \App\Entity\User $user,
        ?\App\Entity\UserDocument $taggedDocument = null,
        bool $apiV2User = false,
    ) {
        //Make sure we have all the fields required for contego request

        $this->logger->info('doing service ... ');

        $this->isUserValidForContego($user);

        if ($apiV2User) {
            $mobile = $user->getMobile();
        } else {
            $mobile = $user->getPhone2();
        }

        $phoneNumber = [
            [
                'countryCode' => null,
                'fullNumber' => $mobile,
                'lineCategory' => 'MOBILE',
                'areaCode' => null,
                'subscriberNumber' => null,
            ],
        ];

        $address = [
            'addressType' => 'Current Address',
            'postcode' => $user->getMainAddress()->getPostCode(),
            'town' => $user->getMainAddress()->getCity(),
            'address1' => $user->getMainAddress()->getAddress1(),
            'address2' => $user->getMainAddress()->getAddress2(),
            'address3' => $user->getMainAddress()->getAddress3(),
            'address4' => '',
            'address5' => '',
            'state' => $user->getMainAddress()->getRegion(),
            'country' => $this->getCountryCode($user->getMainAddress()->getCountry()), // We need GB not United Kingdom
        ];

        $this->logger->info('doing service ... ');

        if ($taggedDocument == null) {
            //Get the Document stored in user object, we need to find the document that has the tag
            $documents = $user->getDocuments();
            foreach ($documents as $document) {
                /** @var UserDocument $document */
                $this->logger->debug('NAME =' . $document->getDocument()->getName());

                if ($document->getDocument()->getTag() === 'proof_of_identity') {
                    $taggedDocument = $document;
                }
            }
        } else {
            //check passed in doucmument has poi tag
            if (!$taggedDocument->getDocument()->getTag() === 'proof_of_identity') {
                throw new \UnexpectedValueException(
                    'User document must have tag proof_of_identity',
                );
            }
        }

        if (!isset($taggedDocument)) {
            throw new \UnexpectedValueException('User must have a document!');
        }

        $this->logger->info('doing service ... ');

        /*
         * Passport is sent during this contego check
         * However to get a contego RED user, for the second contego check for test purposes we need to send a passport serial number of 12345678.
         * This value is only set if the users firstname is contego and last name is red.
         */

        $serialNumber = null;
        $fileContent = $taggedDocument->getDocument()->getDocumentContent();

        if ($user->getFirstname() == 'contego' && $user->getLastname() == 'red') {
            $serialNumber = 12345678;
        }

        if (!$fileContent) {
            $fileContent = base64_encode($this->documentService->read(
                $taggedDocument->getDocument()->getDocumentUrl(),
                'private',
            ));

            // $fileContent = $taggedDocument->getDocument()->getBase64Encoded_DocumentContent();
        }

        $passport = [
            'forename' => $user->getFirstname(),
            'uploadedFile' => [
                [
                    'fileID' => null,
                    'fileType' => $taggedDocument->getDocument()->getType(),
                    'fileContent' => $fileContent,
                    'fileName' => $taggedDocument->getDocument()->getFilename(),
                ],
            ],
            'documentID' => null,
            'passportExpiryDate' => null,
            'dob' => null,
            'uploadedImageID' => null,
            'passportMRZ1' => null,
            'passportMRZ2' => null,
            'notes' => null,
            'visbleImage' => null,
            'irimage' => null,
            'uvimage' => null,
            'chipFacialImage' => null,
            'chipFingerprintImage' => null,
            'chipIrisImage' => null,
            'serialNumber' => $serialNumber,
            'surname' => $user->getLastname(),
            'country' => null,
        ];

        $utcDob = new \DateTime(
            $user->getBirthDate()->format('Y-m-d'),
            new \DateTimeZone('UTC'),
        );

        $person = [
            'person' => [
                'gender' => $user->getGender(),
                'middlename' => $user->getMiddleName(),
                'title' => $user->getHonoricPrefix(),
                'forename' => $user->getFirstname(),
                'personType' => 'Person Type',
                'emailAddress' => [$user->getEmail()],
                'isFullElectoralRoll' => true,
                'surname' => $user->getLastname(),
                'phoneNumber' => $phoneNumber,
                'address' => $address,
                'passport' => $passport,
                'dob' => $utcDob->getTimestamp() * 1000, //Convert to milliseconds!
            ],
        ];

        $credentials = [
            'organisationUID' => $this->contego_organisation_uid,
            'profileUID' => $this->contego_person_uid,
            'md5Signature' => $this->contego_md5,
        ];

        $header = [
            'transactionRef' => $this->contego_transaction_ref,
        ];

        $checkPerson = [
            'checkPerson' => $person,
            'credentials' => $credentials,
            'header' => $header,
        ];

        $json = json_encode($checkPerson);

        //        echo($json);
        $this->logger->info('doing service ... ');

        $this->logger->info("... sending json request to contego json-data = [$json]");

        //Get the response from the request to contego
        $response = $this->getContegoResponse($json);

        $return_data = json_decode($response->getBody());

        $this->logger->info(
            '... Data returned from contego = [' . $response->getBody() . ']',
        );

        //Make sure we got a valid contego response
        $this->validateContegoResponse($return_data);
        $this->logger->info('validated');

        //Update the contego logs and user information
        $this->updateContegoLog($user, $return_data);
        $this->logger->info('contegolog updated');

        $this->updateUserContegoScore($user, $return_data);
        $this->logger->info('contegoscore updated');

        //Save to database
        $this->em->flush();
        $this->logger->info('database updated');

        //return the response
        $result['outcome'] = 'success';
        $result['status'] = '200';
        $result['data']['ContegoScore']['score'] =
            $return_data->{'contegoScore'}->{'score'};
        $result['data']['ContegoScore']['rag'] =
            $return_data->{'contegoScore'}->{'rag'};
        $result['data']['ContegoScore']['alerts'] =
            $this->getAlertMessage($return_data);

        $this->logger->info('sending response...' . json_encode($result));

        return $result;
    }

    /**
     * Write to the contegoLog
     * @param \App\Entity\User $user
     * @param $return_data
     */

    public function updateContegoLog(\App\Entity\User $user, $return_data)
    {
        $rag = $return_data->{'contegoScore'}->{'rag'} ?? null;
        if ('WAITING' != $rag) {
            $contegoLog = new ContegoLog();
            $contegoLog->setUser($user);
            $contegoLog->setKycScore($return_data->{'contegoScore'}->{'score'});
            $contegoLog->setKycType('Person Check');
            $contegoLog->setRAG($return_data->{'contegoScore'}->{'rag'});
            $contegoLog->setProfileName('New KYC Check');
            $contegoLog->setExtReferenceId($return_data->{'header'}->{'requestRef'});

            if (empty($return_data->{'header'}->{'pdfreport'})) {
                $contegoLog->setPdfReportUrl('NOT AVAILABLE');
            } else {
                $contegoLog->setPdfReportUrl($return_data->{'header'}->{'pdfreport'});
            }
            // Create KycReport which should be equivalent to a ContegoLog
            // KycReport will eventually replace Contego logs
            $kycReport = $this->createKycReport($user, $return_data);
            $this->em->persist($kycReport);

            $this->em->persist($contegoLog);
            $this->em->flush();
        }
    }

    /**
     * Write to the contegoLog
     * @param \App\Entity\User $user
     * @param $return_data
     */

    public function updateUserContegoScore(\App\Entity\User $user, $return_data)
    {
        //Update the user entity with the contego status
        $contegoScore = new ContegoScore();
        $contegoScore->setKycScore($return_data->{'contegoScore'}->{'score'});
        $contegoScore->setRAG($return_data->{'contegoScore'}->{'rag'});
        $contegoScore->setRuleMessages($this->getAlertMessage($return_data));

        $user->setContegoScore($contegoScore);

        //$this->em->merge( $contegoScore );
    }

    public function createKycReport(User $user, $return_data): KycReport
    {
        $kycReport = new KycReport(
            $user,
            ContegoKycService::PROVIDER_NAME,
            $return_data->{'header'}->{'requestRef'},
            'Person Check',
            $return_data->{'contegoScore'}->{'rag'},
            $return_data->{'contegoScore'}->{'score'},
            $return_data->{'contegoScore'}->{'rag'} == 'GREEN' ? true : false,
            new \DateTime(),
            $return_data?->{'header'}?->{'pdfreport'},
        );
        return $kycReport;
    }

    /**
     * Validates a users details before contego request
     *
     * @param \App\Entity\User $user
     * @return boolean
     */
    public function isUserValidForContego(\App\Entity\User $user)
    {
        if (!$user->getFirstname()) {
            throw new \UnexpectedValueException('User must have a first name!');
        }

        if (!$user->getLastname()) {
            throw new \UnexpectedValueException('User must have a last name!');
        }

        if (!$user->getBirthDate()) {
            throw new \UnexpectedValueException('User must have a birth date!');
        }

        if (!$user->getNationality()) {
            throw new \UnexpectedValueException('User must have a nationality!');
        }

        // if ( ! $user->getPassportCountry() ) {
        //     throw new \UnexpectedValueException('Users passport must have a country!');
        // }

        return true;
    }

    /**
     * @param $countryData Can be either the country name (United Kingdom) or ISO country code (GB)
     * @return Country code in ISO 3166-1 alpha-2 format
     * @throws \Exception
     */
    private function getCountryCode($countryData)
    {
        // This allows us to convert country name to ISO 3166-1 alpha-2 e.g. United Kingdom to GB
        // Maybe this should not be here ???
        $countriesByName = CountriesArray::get('name', 'alpha2'); // United Kingdom -> GB
        $countriesByCode = CountriesArray::get('alpha2', 'name'); // GB -> United Kingdom

        $this->logger->debug("Looking up country code for country [$countryData] ...");

        if (in_array($countryData, $countriesByName)) {
            //If we find the variable in countriesByCode (e.g. GB) it means we have the correct ISO value already
            //Under normal circumstances this should not be the case as from the form we get the country as 'United Kingdom'
            $countryCode = $countryData;
            $this->logger->debug(" ... Found country code [$countryCode] ...");
        } elseif (in_array($countryData, $countriesByCode)) {
            //We found a country code corresponding to the the country name given
            $countryCode = $countriesByName[$countryData];
            $this->logger->debug(" ... Found country code [$countryCode] ...");
        } else {
            // We couldn't find the country by name or code
            throw new \Exception(
                'Unable to translate ['
                . $countryData
                . '] into a valid ISO 3166-1 alpha-2 country code',
            );
        }

        return $countryCode;
    }

    public function getEmptyRequest()
    {
        $phoneNumber = [
            [
                'countryCode' => null,
                'fullNumber' => 'FullMobileNumber',
                'lineCategory' => 'MOBILE',
                'areaCode' => null,
                'subscriberNumber' => null,
            ],
        ];

        $address = [
            'postcode' => 'Postcode',
            'town' => 'Town',
            'address1' => 'Address1',
            'address2' => 'Address2',
            'address3' => 'Address3',
            'address4' => 'Address4',
            'address5' => 'Address5',
            'county' => 'County',
            'state' => 'State',
            'country' => 'GB',
        ];

        $person = [
            'person' => [
                'gender' => 'MALE',
                'middlename' => 'MiddleName',
                'title' => '',
                'forename' => 'Firstname',
                'personType' => 'Person Type',
                'emailAddress' => ['email@email.com'],
                'isFullElectoralRoll' => false,
                'surname' => 'Lastname',
                'phoneNumber' => $phoneNumber,
                'address' => $address,
            ],
        ];

        $credentials = [
            'organisationUID' => '0c2ca62e-8a35-480d-b9d1-229bd0405598',
            'profileUID' => 'aae69a20-0bd7-489e-b948-a81ecedd7e37',
            'md5Signature' => '325dde2bf4f7436f08ee8110f5fc66d6',
        ];

        $header = [
            'transactionRef' => 'REST test',
        ];

        $checkPerson = [
            'checkPerson' => $person,
            'credentials' => $credentials,
            'header' => $header,
        ];

        $json = json_encode($checkPerson);

        $url = $this->contego_url . '/rest/v2/check';

        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        $response = $client->post($url, ['body' => $json]);

        //var_dump(\GuzzleHttp\json_decode($response->getBody()));
        //$request = new Request('POST', $this->url . '/rest/v2/check');
        //      var_dump($request);
        //$response = $this->requestHandler->handle($request);
        //var_dump($response);
        // return $response->getBody();
    }
}
