<?php

namespace App\Tests\Form;

use App\Form\Type\QueryUserType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class QueryUserTypeTest extends TypeTestCase
{
    /**
     * @return ValidatorExtension[]
     *
     * @psalm-return array{0: ValidatorExtension}
     */
    protected function getExtensions()
    {
        $validator = Validation::createValidator();
        return [new ValidatorExtension($validator)];
    }

    public function testSubmitValidData(): void
    {
        // https://symfony.com/doc/current/form/unit_testing.html
        $formData = [
            'username' => 'abc',
            'email' => 'abc',
            'name' => 'xyz',
            'isVIP' => 1,
        ];
        $form = $this->factory->create(QueryUserType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $defaults = [
            'perPage' => 10,
            'orderBy' => 'id',
            'orderDirection' => 'DESC',
            'referralCode' => null,
            'corporateInvestor' => null,
            'companyName' => null,
            'hasInvestments' => null,
            'hasManagedUsers' => null,
            'gender' => null,
            'lifecycleStatus' => [],
        ];
        $actual = $form->getData();
        foreach (array_merge($formData, $defaults) as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
