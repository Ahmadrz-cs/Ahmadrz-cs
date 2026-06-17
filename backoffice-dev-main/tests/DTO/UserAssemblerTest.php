<?php

namespace App\Tests\Dto;

use App\Dto\AddressDTO;
use App\Dto\Marketing;
use App\Dto\UserAssembler;
use App\Dto\UserPatchDTO;
use App\Dto\UserPostDTO;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserAssemblerTest extends KernelTestCase
{
    /** @var \App\Dto\UserAssembler */
    private $userAssembler;

    protected function setUp(): void
    {
        self::bootKernel();
        $passwordEncoder = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->userAssembler = new UserAssembler($passwordEncoder);
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: 'FEMALE'|'MALE'|'OTHER'}, mixed, void>
     */
    public static function validateGenderProvider(): \Generator
    {
        yield 'female' => ['female', 'FEMALE'];
        yield 'male' => ['male', 'MALE'];
        yield 'mixed-case' => ['FeMAle', 'FEMALE'];
        yield 'non-binary' => ['non-binary', 'OTHER'];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: string}, mixed, void>
     */
    public static function investmentIntentProvider(): \Generator
    {
        yield 'under-1K' => ['Less thank £1k', 'Less thank £1k'];
        yield '10K-25k' => ['Between £10k-25k', 'Between £10k-25k'];
        yield '25k-£100k' => ['Between £25k-£100k', 'Between £25k-£100k'];
        yield '100k-500k' => ['£100k-500k', '£100k-500k'];
        yield '500K+' => ['£500K+', '£500K+'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateGenderProvider')]
    public function testValidateGender(string $input, string $expected): void
    {
        $result = $this->userAssembler->validateGender($input);
        $this->assertTrue(ctype_upper($result));
        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateGenderProvider')]
    public function testCreateUserInclGender(string $input, string $expected): void
    {
        $address = new AddressDTO(
            'lane 1',
            null,
            null,
            null,
            'London',
            'NW1 MQA',
            'GB',
        );
        $userDto = new UserPostDTO(
            'test1@mail.com',
            '',
            '',
            'A',
            'Bloggs',
            new \DateTime(),
            '',
            $address,
            '',
            '',
            '',
            '',
            null,
            '',
            $input,
            '',
        );
        $user = $this->userAssembler->createUser($userDto);
        $result = $user->getGender();
        $this->assertTrue(ctype_upper($result));
        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validateGenderProvider')]
    public function testUpdateUserGender(string $input, string $expected): void
    {
        $user = new User();
        $user->setGender('OTHER');
        $address = new AddressDTO(
            'lane 1',
            null,
            null,
            null,
            'London',
            'NW1 MQA',
            'GB',
        );
        $userDto = new UserPatchDTO(
            'test1@mail.com',
            '',
            '',
            'A',
            'Bloggs',
            new \DateTime(),
            '',
            $address,
            '',
            '',
            '',
            '',
            null,
            '',
            $input,
            '',
        );
        $updatedUser = $this->userAssembler->updateUser($user, $userDto);

        $result = $updatedUser->getGender();
        $this->assertTrue(ctype_upper($result));
        $this->assertEquals($expected, $result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentIntentProvider')]
    public function testUpdateInvestmentIntent(string $input, string $expected): void
    {
        $address = new AddressDTO(
            'lane 1',
            null,
            null,
            null,
            'London',
            'NW1 MQA',
            'GB',
        );
        $marketing = new Marketing($input);
        $userDto = new UserPostDTO(
            'test1@mail.com',
            '',
            '',
            'A',
            'Bloggs',
            new \DateTime(),
            '',
            $address,
            '',
            '',
            '',
            '',
            $marketing,
            '',
            $input,
            '',
        );
        $newUser = $this->userAssembler->createUser($userDto);

        $result = $newUser->findCustomFieldValue('question3');
        $this->assertEquals($expected, $result);

        $user = new User();
        $userCusField = new \App\Entity\UserCustomFields();
        $userCusField->setUser($user);
        $userCusField->setFieldKey('question3');
        $userCusField->setFieldValue('1k');
        $user->findReplaceCustomField($userCusField);

        $address = new AddressDTO(
            'lane 1',
            null,
            null,
            null,
            'London',
            'NW1 MQA',
            'GB',
        );
        $marketing = new Marketing($input);
        $userDto = new UserPatchDTO(
            'test1@mail.com',
            '',
            '',
            'A',
            'Bloggs',
            new \DateTime(),
            '',
            $address,
            '',
            '',
            '',
            '',
            $marketing,
            '',
            $input,
            '',
        );
        $updatedUser = $this->userAssembler->updateUser($user, $userDto);

        $result = $updatedUser->findCustomFieldValue('question3');
        $this->assertEquals($expected, $result);
    }
}
