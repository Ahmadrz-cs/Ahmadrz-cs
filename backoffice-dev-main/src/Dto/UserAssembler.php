<?php

namespace App\Dto;

use App\Entity\Address;
use App\Entity\User;
use App\Entity\UserCustomFields;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * TODO Add countryOfResidence when requirments are clear
 * TODO Add getTypeOfInvestor when requirments are clear
 */

final class UserAssembler
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    /**
     * @param UserDTO $userDTO
     * @param User|null $user
     * @return User
     */
    public function readDTO(UserDTO $userDTO, ?User $user = null): User
    {
        if (!$user) {
            //Create a new User.
            //Set a random password if no password is set in $userDTO
            $user = new User();
            $encodedPassword = $this->userPasswordHasher->hashPassword(
                $user,
                $userDTO->getPassword() ?? bin2hex(random_bytes(32)),
            );
            $user->setPassword($encodedPassword);
        }

        //Returns exisiting address if present or a new address object if not
        $address = $user->getMainAddress();

        $user->setEmail($userDTO->getEmail() ?? $user->getEmail());
        $user->setUsername($user->getEmail());
        $user->setHonoricPrefix($userDTO->getTitle() ?? $user->getHonoricPrefix());
        $user->setFirstname($userDTO->getFirstName() ?? $user->getFirstname());
        $user->setLastname($userDTO->getLastName() ?? $user->getLastname());
        $user->setBirthDate($userDTO->getDateOfBirth() ?? $user->getBirthDate());
        $user->setNationality($userDTO->getNationality() ?? $user->getNationality());
        $user->setPhone1($userDTO->getPhone() ?? $user->getPhone1());
        $user->setMobile($userDTO->getMobilePhone() ?? $user->getMobile());
        $user->setReferralCode($userDTO->getReferralCode() ?? $user->getReferralCode());

        if ($userDTO->getGender()) {
            $user->setGender($this->validateGender($userDTO->getGender()));
        }

        if (null !== $userDTO->getAddress()) {
            $addressDTO = $userDTO->getAddress();
            $address->setAddress1(
                $addressDTO->getAddress1() ?? $address->getAddress1(),
            );
            $address->setAddress2(
                $addressDTO->getAddress2() ?? $address->getAddress2(),
            );
            $address->setAddress3(
                $addressDTO->getAddress3() ?? $address->getAddress3(),
            );
            $address->setRegion($addressDTO->getRegion() ?? $address->getRegion());
            $address->setCity($addressDTO->getCity() ?? $address->getCity());
            $address->setPostCode(
                $addressDTO->getPostCode() ?? $address->getPostCode(),
            );
            $address->setCountry($addressDTO->getCountry() ?? $address->getCountry());
        }

        //Add address if user does not have an address.
        //Check address1 is set to avoid not null constraint
        if (count($user->getAddresses()) == 0 && null !== $address->getAddress1()) {
            $user->addAddress($address);
        }

        if (null !== $userDTO->getMarketingPreference()) {
            $user->setGDPRAccepted(true);
            $userCusField = new UserCustomFields();
            $userCusField->setUser($user);
            switch (strtolower($userDTO->getMarketingPreference())) {
                case 'sms':
                    $userCusField->setFieldKey('contact_via_sms');
                    $userCusField->setFieldValue(1);
                    $user->findReplaceCustomField($userCusField);
                    break;
                case 'email':
                    $userCusField->setFieldKey('contact_via_email');
                    $userCusField->setFieldValue(1);
                    $user->findReplaceCustomField($userCusField);
                    break;
                case 'phone':
                    $userCusField->setFieldKey('contact_via_tele');
                    $userCusField->setFieldValue(1);
                    $user->findReplaceCustomField($userCusField);
                    break;
            }
        }

        if (null !== $userDTO->getTypeOfInvestor()) {
            switch (strtolower($userDTO->getTypeOfInvestor())) {
                case 'everyday':
                    $user->getInvestor()->setCxbRestrictedUser(1);
                    break;
                case 'sophisticated':
                    $user->getInvestor()->setCxbSophisticatedInvestor(1);
                    break;
                case 'high net worth':
                    $user->getInvestor()->setCxbWorthInvestor(1);
                    break;
                case 'institutional':
                    $user->getInvestor()->setCxbLtdCompInvestor(1);
                    break;
            }
        }

        if ($userDTO->getMarketing()) {
            $marketing = $userDTO->getMarketing();
            if ($marketing->getInvestmentIntent()) {
                $userCusField = new UserCustomFields();
                $userCusField->setUser($user);
                $userCusField->setFieldKey('question3');
                $userCusField->setFieldValue($marketing->getInvestmentIntent());
                $user->findReplaceCustomField($userCusField);
            }
        }
        return $user;
    }

    /**
     * @param User $user
     * @param UserDTO $userDTO
     * @return User
     */
    public function updateUser(User $user, UserDTO $userDTO): User
    {
        return $this->readDTO($userDTO, $user);
    }

    /**
     * @param UserDTO $userDTO
     * @return User
     */
    public function createUser(UserDTO $userDTO): User
    {
        return $this->readDTO($userDTO, null);
    }

    public function validateGender(string $gender): string
    {
        $validOptions = [
            'FEMALE',
            'MALE',
        ];

        $normalizedInput = strtoupper($gender);
        if (!in_array($normalizedInput, $validOptions)) {
            return 'OTHER';
        } else {
            return $normalizedInput;
        }
    }
}
