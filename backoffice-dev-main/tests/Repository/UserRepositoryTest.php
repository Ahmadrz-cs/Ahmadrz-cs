<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Entity\Company;
use App\Entity\ContegoScore;
use App\Entity\Investor;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Repository\UserRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Pagerfanta\Pagerfanta;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class UserRepositoryTest extends FixtureTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->repository = static::getContainer()->get(UserRepository::class);
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->repository->findByWithAssociations([], [], 6, 2);
        $this->assertEquals(2, $actual->getCurrentPage());
        $this->assertEquals(6, $actual->getMaxPerPage());
    }

    public function testFindByWithAssociationsOrdering(): void
    {
        // Check ordering by comparing actual with manually sorted
        // default ordering: id ascending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([], [
                'id' => 'DESC',
            ]));
        rsort($expected);
        $this->assertEquals($expected, $actual);

        // multiple ordering by precedence
        $actual = $this->repository->findByWithAssociations([], [
            'isVIP' => 'DESC',
            'id' => 'ASC',
        ]);
        $t = $f = [];
        foreach ($actual as $object) {
            if ($object->getIsVIP()) {
                $t[] = $object->getId();
            } else {
                $f[] = $object->getId();
            }
        }
        sort($t);
        sort($f);
        $actual = EntityIdTestUtil::extractIds($actual);
        $this->assertEquals(array_merge($t, $f), $actual);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->repository->findByWithAssociations([
            'isVIP' => 1,
        ])->getNbResults();
        $actual = $this->repository->findByWithAssociations([
            'isVIP' => 1,
            'abc' => 1,
            'page' => 23,
        ]);
        $this->assertCount($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaProvider')]
    public function testFindByWithAssociationsCriteria(array $filters): void
    {
        /**
         * Check all results match the criteria
         * Use Symfony component PropertyAccessor for non-relational properties
         */
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        /** @var Pagerfanta<User> $results */
        $results = $this->repository->findByWithAssociations($filters);
        // echo PHP_EOL;
        // print_r($filters);
        // echo PHP_EOL . $results->getNbResults();
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                $originalKey = $key;
                if ('name' == $key) {
                    $key = 'fullname';
                }
                if ('has' === substr($key, 0, 3)) {
                    $key = lcfirst(substr($key, 3));
                }
                if (in_array($key, ['corporateInvestor', 'wordsOfOwn'])) {
                    $relation = $object->getInvestor();
                }
                if (in_array($originalKey, ['verified', 'hasVerifiedBy'])) {
                    $relation = $object->getKycProfile();
                }
                if ('companyName' == $key) {
                    $key = 'name';
                    $relation = $object->getCompany();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['username', 'email', 'fullname', 'name'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } elseif (in_array($originalKey, [
                    'hasInvestments',
                    'hasManagedUsers',
                    'hasKycProfile',
                    'hasVerifiedBy',
                    'wordsOfOwn',
                ])) {
                    if ($expected) {
                        $this->assertNotEmpty($actual);
                    } else {
                        $this->assertEmpty($actual);
                    }
                } elseif (is_iterable($expected)) {
                    $this->assertContains($actual, $expected);
                } else {
                    $this->assertEquals($expected, $actual);
                }
                unset($relation);
            }
        }
    }

    public static function findByCriteriaProvider(): \Generator
    {
        yield 'Basic equivalence field' => [['isVIP' => 1]];
        yield 'Basic equivalence field multi' => [['isVIP' => [0, 1]]];
        yield 'String match' => [['username' => 'be']];
        yield 'Status relation' => [['lifecycleStatus' => 'approved']];
        yield 'Status relation multi' => [['lifecycleStatus' => [
            'email_verified',
            'approved',
        ]]];
        yield 'Corporate' => [['corporateInvestor' => 1]];
        yield 'Top Yielder application' => [['wordsOfOwn' => 1]];
        yield 'Company Relation' => [['companyName' => 'ev']];
        yield 'Collections existence' => [['hasInvestments' => 0]];
        yield 'Managed users existence' => [['hasManagedUsers' => 1]];
        yield 'Kyc profile existence' => [['hasKycProfile' => 1]];
        yield 'Kyc profile verified' => [['verified' => 1]];
        yield 'Kyc profile has verified by' => [['hasVerifiedBy' => 1]];
        yield 'Combination 1' => [['gender' => 'MALE', 'name' => 'b']];
        yield 'Combination 2' => [[
            'isVIP' => 0,
            'name' => 'el',
            'corporateInvestor' => 0,
        ]];
        yield 'Combination 3' => [['email' => 'al', 'lifecycleStatus' => 'approved']];
        yield 'Combination 4' => [[
            'isVIP' => 0,
            'gender' => 'MALE',
            'hasInvestments' => 1,
        ]];
        yield 'Combination 5' => [[
            'lifecycleStatus' => 'email_verified',
            'hasManagedUsers' => 0,
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaRangeProvider')]
    public function testFindByWithAssociationsCriteriaRanges(
        array $filters,
        array $fieldChecks,
    ): void {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $results = $this->repository->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($fieldChecks as $fieldName => $range) {
                if (!isset($range['start']) && !isset($range['end'])) {
                    $this->fail('No expected ranges set for field ' . $fieldName);
                }
                $actual = $propertyAccessor->getValue($object, $fieldName);
                if (isset($range['start'])) {
                    $this->assertGreaterThanOrEqual($range['start'], $actual);
                }
                if (isset($range['end'])) {
                    $this->assertLessThan($range['end'], $actual);
                }
            }
        }
    }

    public static function findByCriteriaRangeProvider(): \Generator
    {
        yield 'CreatedAt Start' => [
            'filters' => [
                'createdAt_gte' => new \DateTime('-2 days'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-2 days')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt End' => [
            'filters' => [
                'createdAt_lt' => new \DateTime('-2 days'),
            ],

            'fieldChecks' => [
                'createdAt' => [
                    'end' => new \DateTime('-2 days')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt Range' => [
            'filters' => [
                'createdAt_gte' => new \DateTime('-4 days'),
                'createdAt_lt' => new \DateTime('-1 days'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-4 days')->setTime(0, 0),
                    'end' => new \DateTime('-1 days')->setTime(0, 0),
                ],
            ],
        ];
    }

    // Basic User Insert with Just basic fields and no dependencies
    // previously test_issue_878
    public function testCreateBasicUser(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        // Get User object
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        // set the onboarding step to 2
        $user->setOBStep(2);

        // Contego score
        $con_score = new ContegoScore();
        $con_score->setRAG('RED');
        $con_score->setKycScore(125);
        $con_score->setRuleMessages(str_repeat('a ', 50000)); //create a string with 1000 length
        $user->setContegoScore($con_score);

        //Save User along with Address Details
        $this->repository->save($user, true);

        /** @var User $new_user */
        $new_user = $this->repository->find($count + 1);

        $this->assertEquals($user->getUsername(), $new_user->getUsername());
        $this->assertEquals($user->getEmail(), $new_user->getEmail());

        //assert obstep
        $this->assertEquals($user->getOBStep(), $new_user->getOBStep());

        //assert the contego score
        $this->assertNotNull($new_user->getContegoScore());
        $this->assertEquals(
            100000,
            strlen($new_user->getContegoScore()->getRuleMessages()),
        );
    }

    //Basic User Insert with Just basic fields and no dependencies
    public function testCreateUserWithAllDetailsAtOnceIfValid(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        //Get User Details
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        //Get Address Details & Set Address details to User
        $address = new Address();
        $address = $this->getValidUserAddressDetails(
            $user->getUsername(),
            $randomnumber,
        );
        $user->addAddress($address);

        //Get Additional Fields and Set Additional Fields to User
        $fields1 = new UserCustomFields();
        $fields1 = $this->getValidAddFields('Name', 'Dante');
        $fields2 = new UserCustomFields();
        $fields2 = $this->getValidAddFields('Location', 'A Beautiful City');
        $user->addCustomField($fields1);
        $user->addCustomField($fields2);

        //Get Company Details and Set Company Details to User
        $Company = new Company();
        $company = $this->getValidCompanyDetails();
        $user->setCompany($company);

        //Get Investor Details and set Investor Details to User
        $investor = new Investor();
        $investor = $this->getValidInvestorDetails();
        $user->setInvestor($investor);

        //COntego score
        $con_score = new ContegoScore();
        $con_score->setRAG('RED');
        $con_score->setKycScore(125);
        $con_score->setRuleMessages('dfgfdg,dfgdfg');
        $user->setContegoScore($con_score);

        //Telephone Details
        $user->setMobile('+12377777');
        $user->setPhone1('+123777771');
        $user->setPhone2('+123777772');

        //Save User along with Address Details
        $this->repository->save($user, true);

        /** @var User $new_user */
        $new_user = $this->repository->find($count + 1);

        //Commented All get functionality as some authentication problem is stopping all get functionality
        // from working. Uncomment once the issue is resolved.
        $this->assertEquals($user->getUsername(), $new_user->getUsername());
        $this->assertEquals($user->getEmail(), $new_user->getEmail());

        //Assertions for User Object
        $this->compareUserObjects($user, $new_user);

        // Assertions for Address
        $this->assertCount(1, $new_user->getAddresses());
        $new_address = $new_user->getAddresses()->get(0);
        $this->assertNotNull($new_address);
        $this->assertEquals($address->getAddress1(), $new_address->getAddress1());

        // Assertions for Add Fields
        $this->assertCount(2, $new_user->getCustomFields());
        $newAddFields = $new_user->getCustomFields()->get(0);
        $this->assertNotNull($newAddFields);
        $this->assertEquals($fields1->getFieldKey(), $newAddFields->getFieldKey());

        $newAddFields = $new_user->getCustomFields()->get(1);
        $this->assertNotNull($newAddFields);
        $this->assertNotEquals($fields1->getFieldKey(), $newAddFields->getFieldKey());
        $this->assertEquals($fields2->getFieldKey(), $newAddFields->getFieldKey());

        // Asset for Investor
        $newInvestor = $new_user->getInvestor();
        $this->assertNotNull($newInvestor);
        $this->compareInvestorObjects($newInvestor, $investor);

        // Assert for Company Details
        // dump($new_user->getCompany());
        $this->assertNotNull($new_user->getCompany());

        // assert the contego score
        $this->assertNotNull($new_user->getContegoScore());

        $this->compareCompanyObject($company, $new_user->getCompany());
    }

    public function testCreateAddressForSavedUser(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        //Get User Details
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        //Create User without Address details
        $this->repository->save($user, true);
        //Fetch Saved User
        $savedUser = $this->repository->find($count + 1);

        //Get Address Details & Pass user information to address details
        $address = new Address();
        $address = $this->getValidUserAddressDetails(
            $user->getUsername(),
            $randomnumber,
        );

        //Save the address formulated to the User Entity
        $savedUser->addAddress($address);
        $this->repository->save($savedUser, true);

        //Call Usesr object just saved and get address details
        $addressesCollection = $this->repository->find($count + 1)->getAddresses(0);
        foreach ($addressesCollection as $address) {
            $addressFetched = $address;
            break;
        }

        foreach ($savedUser->getAddresses() as $addressSaved) {
            $addressCreated = $addressSaved;
            break;
        }

        //Assertions on the address object fetched
        $this->compareAddressObjects($addressFetched, $addressCreated);
    }

    public function testRemoveAddressForSavedUser(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        //Get User Details
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        //Create User without Address details
        $this->repository->save($user, true);
        //Fetch Saved User
        $savedUser = $this->repository->find($count + 1);

        //Get Address Details & Pass user information to address details
        $address = new Address();
        $address = $this->getValidUserAddressDetails(
            $user->getUsername(),
            $randomnumber,
        );

        //Save the address formulated to the User Entity
        $savedUser->addAddress($address);
        //get second Address
        $address = $this->getValidUserAddressDetails(
            $user->getUsername(),
            $randomnumber,
        );
        $savedUser->addAddress($address);
        $this->repository->save($savedUser, true);

        //Fetch Address Collection
        $addressesCollection = $this->repository->find($count + 1)->getAddresses();

        //count number of address for the user entity
        $addressCount = count($this->repository->find($count + 1)->getAddresses());
        $this->assertEquals(2, $addressCount);

        //Get the First Address from the collection
        foreach ($addressesCollection as $address) {
            $addressFetched = $address;
            break;
        }
        //Strip off the address and save User
        $savedUser->removeAddress($addressFetched);
        $this->repository->save($savedUser, true);

        //count number of address for the user entity after stripp of one address
        $addressCount = count($this->repository->find($count + 1)->getAddresses());
        $this->assertEquals(1, $addressCount);
    }

    public function testCreateCustomFieldsForSavedUser(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        //Get User Details
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        //Create User without Address details
        $this->repository->save($user, true);
        //Fetch Saved User
        $savedUser = $this->repository->find($count + 1);

        //Get Additional Fields and Set Additional Fields to User
        $fields1 = new UserCustomFields();
        $fields1 = $this->getValidAddFields('Name', 'Dante');
        $fields2 = new UserCustomFields();
        $fields2 = $this->getValidAddFields('Location', 'A Beautiful City');

        //Save the Customer Fields formulated to the User Entity
        $savedUser->addCustomField($fields1);
        $savedUser->addCustomField($fields2);
        $this->repository->save($savedUser, true);

        //Call Usesr object just saved and get Add Fields details
        $addFieldsCollection = $this->repository->find($count + 1)->getCustomFields(0);
        $count = 1;
        /** @var UserCustomFields $addFields */
        foreach ($addFieldsCollection as $addFields) {
            if ($count == 1) {
                $this->assertEquals('Name', $addFields->getFieldKey());
                $this->assertEquals('Dante', $addFields->getFieldValue());
            } elseif ($count == 2) {
                $this->assertEquals('Location', $addFields->getFieldKey());
                $this->assertEquals('A Beautiful City', $addFields->getFieldValue());
            } else {
                break;
            }
            $count = $count + 1;
        }
    }

    public function testRemoveAddFieldsForSavedUser(): void
    {
        $user = new \App\Entity\User();
        $count = count($this->repository->findAll());

        //Get User Details
        $randomnumber = rand(1, 200);
        $user = $this->getValidUserDetails($randomnumber);

        //Create User without Address details
        $this->repository->save($user, true);
        //Fetch Saved User
        $savedUser = $this->repository->find($count + 1);

        //Get Additional Fields and Set Additional Fields to User
        $fields1 = new UserCustomFields();
        $fields1 = $this->getValidAddFields('Name3', 'Dante3');
        $fields2 = new UserCustomFields();
        $fields2 = $this->getValidAddFields('Location3', 'A Beautiful City3');

        //Save the Custom Fields formulated to the User Entity
        $savedUser->addCustomField($fields1);
        $savedUser->addCustomField($fields2);
        $this->repository->save($savedUser, true);

        //Fetch Custom Fields Collection
        $addFieldsCollection = $this->repository->find($count + 1)->getCustomFields();

        //count number of Custom Fields for the user entity
        $addFieldsCount = count($this->repository->find($count + 1)->getCustomFields());
        $this->assertEquals(2, $addFieldsCount);

        //Get the First Address from the collection
        foreach ($addFieldsCollection as $addFields1) {
            $addFieldsFetched = $addFields1;
            break;
        }
        //Strip off the address and save User
        $savedUser->removeCustomField($addFieldsFetched);
        $this->repository->save($savedUser, true);

        //count number of address for the user entity after stripp of one address
        $addressCount = count($this->repository->find($count + 1)->getCustomFields());
        $this->assertEquals(1, $addressCount);
    }

    /**
     * @psalm-param int<1, 200> $randomnumber
     */
    public function getValidUserDetails(int $randomnumber): User
    {
        $user = new \App\Entity\User();
        $user
            ->setUsername('TestUser' . $randomnumber)
            ->setPassword('HarvestBounty!756')
            ->setEmail('TestingCrowdTek' . $randomnumber . '@Crowdtek.com')
            ->setJobTitle('job' . $randomnumber)
            ->setLocation('loc' . $randomnumber)
            ->setNationality('country' . $randomnumber)
            ->setMobile('10000' . $randomnumber)
            ->setBirthCountry('country' . $randomnumber)
            ->setBirthDate(new \DateTime())
            ->setBirthPlace('place' . $randomnumber)
            ->setDrivingLicenseNo('LicenseNO' . $randomnumber)
            ->setPassportNumber('passport' . $randomnumber)
            ->setPassportCountry('country' . $randomnumber)
            //          ->setPassportExpiry(new \DateTime())
            ->setIncomeRange('Below 50000')
            ->setFirstname('DanFirstUser' . $randomnumber)
            ->setLastname('DanLastUser' . $randomnumber)
            ->setMiddlename('DanMiddleUser' . $randomnumber)
            ->setMangoPayUserId('mango' . $randomnumber)
            ->setType('Type' . $randomnumber)
            ->setLastLoginAt(new \DateTime())
            ->setSetPasswdExpiry(true)
            ->setHonoricPrefix('HonoricPre' . $randomnumber)
            ->setHonoricSuffix('HonoricSuf' . $randomnumber)
            ->setMobile('+12377777')
            ->setPhone1('+123777771')
            ->setPhone2('+123777772')
            ->setGender('Male')
            ->setVisibility(0)
            ->setOccupation(6);
        $user->setTermServiceAccepted(true);
        $user->setGDPRAccepted(1);
        $user->setOBStep(1);
        return $user;
    }

    /**
     * @psalm-param int<1, 200> $randomnumber
     */
    public function getValidUserAddressDetails(
        string $userName,
        int $randomnumber,
    ): Address {
        $address = new Address();
        $address
            ->setAddress1($userName . 'Address1')
            ->setAddress2($userName . 'Address2')
            ->setAddress3($userName . 'Address3')
            ->setCity('London')
            ->setRegion('UK')
            ->setPostCode('123456')
            ->setCountry('UK');
        return $address;
    }

    public function getValidAddFields(string $key, string $value): UserCustomFields
    {
        $fields = new UserCustomFields();
        $fields->setFieldKey($key);
        $fields->setFieldValue($value);
        return $fields;
    }

    public function getValidInvestorDetails(): Investor
    {
        $Investor = new Investor();
        $Investor->setCxbWorthInvestor(true);
        $Investor->setCxbSophisticatedInvestor(true);
        $Investor->setCxbRestrictedUser(true);
        $Investor->setCxbLtdCompInvestor(false);
        $Investor->setAlwaysGoUp(false);
        $Investor->setIncomeEveryMonth(10000);
        $Investor->setNeverExit(true);
        $Investor->setPoiFileId('123455');
        $Investor->setWordsOfOwn('This is a text');
        $Investor->setCorporateInvestor(false);
        return $Investor;
    }

    public function getValidCompanyDetails(): Company
    {
        $Company = new Company();
        $Company->setName('Company1');
        $Company->setRegAddress1('Address1');
        $Company->setRegAddress2('Address2');
        $Company->setRegAddress3('Address3');
        $Company->setBeneficialOwners('Owner1');
        $Company->setDirectors('Director1');
        $Company->setRegCountry('Country1');
        $Company->setBusinessNature('IT Services');
        $Company->setTelephone('12345667');
        $Company->setPostCode('3456');
        $Company->setBuildingName('Building1');
        $Company->setRegistrationNumber('ABC12345');
        $Company->setOtherName('Othername1');
        return $Company;
    }

    public function compareUserObjects(User $UserCreated, User $UserFetched): void
    {
        $this->assertEquals($UserCreated->getType(), $UserFetched->getType());
        $this->assertEquals(
            $UserCreated->getLastLoginAt(),
            $UserFetched->getLastLoginAt(),
        );
        $this->assertEquals(
            $UserCreated->getSetPasswdExpiry(),
            $UserFetched->getSetPasswdExpiry(),
        );
        $this->assertEquals(
            $UserCreated->getMiddlename(),
            $UserFetched->getMiddlename(),
        );
        $this->assertEquals(
            $UserCreated->getHonoricPrefix(),
            $UserFetched->getHonoricPrefix(),
        );
        $this->assertEquals(
            $UserCreated->getHonoricSuffix(),
            $UserFetched->getHonoricSuffix(),
        );
        $this->assertEquals($UserCreated->getJobTitle(), $UserFetched->getJobTitle());
        $this->assertEquals($UserCreated->getLocation(), $UserFetched->getLocation());
        $this->assertEquals(
            $UserCreated->getNationality(),
            $UserFetched->getNationality(),
        );
        $this->assertEquals($UserCreated->getMobile(), $UserFetched->getMobile());
        $this->assertEquals(
            $UserCreated->getBirthCountry(),
            $UserFetched->getBirthCountry(),
        );
        $this->assertEquals($UserCreated->getBirthDate(), $UserFetched->getBirthDate());
        $this->assertEquals(
            $UserCreated->getBirthPlace(),
            $UserFetched->getBirthPlace(),
        );
        $this->assertEquals(
            $UserCreated->getDrivingLicenseNo(),
            $UserFetched->getDrivingLicenseNo(),
        );
        $this->assertEquals(
            $UserCreated->getPassportNumber(),
            $UserFetched->getPassportNumber(),
        );
        $this->assertEquals(
            $UserCreated->getPassportCountry(),
            $UserFetched->getPassportCountry(),
        );
        $this->assertEquals(
            $UserCreated->getPassportExpiry(),
            $UserFetched->getPassportExpiry(),
        );
        $this->assertEquals(
            $UserCreated->getIncomeRange(),
            $UserFetched->getIncomeRange(),
        );
        $this->assertEquals(
            $UserCreated->getCustomFields(),
            $UserFetched->getCustomFields(),
        );
        $this->assertEquals($UserCreated->getDocuments(), $UserFetched->getDocuments());
        $this->assertEquals($UserCreated->getAddresses(), $UserFetched->getAddresses());
        $this->assertEquals($UserCreated->getStatus(), $UserFetched->getStatus());
        $this->assertEquals($UserCreated->getLogs(), $UserFetched->getLogs());
        $this->assertEquals($UserCreated->getCompany(), $UserFetched->getCompany());
        $this->assertEquals($UserCreated->getImage(), $UserFetched->getImage());
        $this->assertEquals($UserCreated->getFirstname(), $UserFetched->getFirstname());
        $this->assertEquals($UserCreated->getLastname(), $UserFetched->getLastname());
        $this->assertEquals(
            $UserCreated->getMangoPayUserId(),
            $UserFetched->getMangoPayUserId(),
        );
        // $this->assertEquals($UserCreated->getOfferings(), $UserFetched->getOfferings());
        $this->assertEquals(
            $UserCreated->getVisibility(),
            $UserFetched->getVisibility(),
        );
        $this->assertEquals(
            $UserCreated->getOccupation(),
            $UserFetched->getOccupation(),
        );
        $this->assertEquals($UserCreated->getPhone1(), $UserFetched->getPhone1());
        $this->assertEquals($UserCreated->getPhone2(), $UserFetched->getPhone2());
        $this->assertEquals($UserCreated->getMobile(), $UserFetched->getMobile());
        $this->assertEquals(
            $UserCreated->isTermServiceAccepted(),
            $UserFetched->isTermServiceAccepted(),
        );
        $this->assertEquals(
            $UserCreated->isGDPRAccepted(),
            $UserFetched->isGDPRAccepted(),
        );
        $this->assertEquals($UserCreated->getOBStep(), $UserFetched->getOBStep());
    }

    private function compareCompanyObject(Company $company, Company $newCompany): void
    {
        $this->assertEquals($company->getName(), $newCompany->getName());
        $this->assertEquals($company->getRegAddress1(), $newCompany->getRegAddress1());
        $this->assertEquals($company->getRegAddress2(), $newCompany->getRegAddress2());
        $this->assertEquals($company->getRegAddress3(), $newCompany->getRegAddress3());
        $this->assertEquals(
            $company->getBeneficialOwners(),
            $newCompany->getBeneficialOwners(),
        );
        $this->assertEquals($company->getDirectors(), $newCompany->getDirectors());
        $this->assertEquals($company->getRegCountry(), $newCompany->getRegCountry());
        $this->assertEquals(
            $company->getBusinessNature(),
            $newCompany->getBusinessNature(),
        );
        $this->assertEquals($company->getTelephone(), $newCompany->getTelephone());
        $this->assertEquals($company->getPostCode(), $newCompany->getPostCode());
        $this->assertEquals(
            $company->getBuildingName(),
            $newCompany->getBuildingName(),
        );
        $this->assertEquals(
            $company->getRegistrationNumber(),
            $newCompany->getRegistrationNumber(),
        );
        $this->assertEquals($company->getOtherName(), $newCompany->getOtherName());
    }

    public function compareAddressObjects(
        Address $addressFetched,
        Address $addressCreated,
    ): void {
        $this->assertEquals(
            $addressFetched->getAddress1(),
            $addressCreated->getAddress1(),
        );
        $this->assertEquals(
            $addressFetched->getAddress2(),
            $addressCreated->getAddress2(),
        );
        $this->assertEquals(
            $addressFetched->getAddress3(),
            $addressCreated->getAddress3(),
        );
        $this->assertEquals($addressFetched->getCity(), $addressCreated->getCity());
        $this->assertEquals($addressFetched->getRegion(), $addressCreated->getRegion());
        $this->assertEquals(
            $addressFetched->getPostCode(),
            $addressCreated->getPostCode(),
        );
        $this->assertEquals(
            $addressFetched->getCountry(),
            $addressCreated->getCountry(),
        );
    }

    public function compareInvestorObjects(
        Investor $investorFetched,
        Investor $investorCreated,
    ): void {
        $this->assertEquals(
            $investorFetched->getCxbWorthInvestor(),
            $investorCreated->getCxbWorthInvestor(),
        );
        $this->assertEquals(
            $investorFetched->getCxbSophisticatedInvestor(),
            $investorCreated->getCxbSophisticatedInvestor(),
        );
        $this->assertEquals(
            $investorFetched->getCxbRestrictedUser(),
            $investorCreated->getCxbRestrictedUser(),
        );
        $this->assertEquals(
            $investorFetched->getCxbLtdCompInvestor(),
            $investorCreated->getCxbLtdCompInvestor(),
        );
        $this->assertEquals(
            $investorFetched->getAlwaysGoUp(),
            $investorCreated->getAlwaysGoUp(),
        );
        $this->assertEquals(
            $investorFetched->getIncomeEveryMonth(),
            $investorCreated->getIncomeEveryMonth(),
        );
        $this->assertEquals(
            $investorFetched->getNeverExit(),
            $investorCreated->getNeverExit(),
        );
        $this->assertEquals(
            $investorFetched->getPoiFileId(),
            $investorCreated->getPoiFileId(),
        );
        $this->assertEquals(
            $investorFetched->getWordsOfOwn(),
            $investorCreated->getWordsOfOwn(),
        );
        $this->assertEquals(
            $investorFetched->getCorporateInvestor(),
            $investorCreated->getCorporateInvestor(),
        );
    }
}
