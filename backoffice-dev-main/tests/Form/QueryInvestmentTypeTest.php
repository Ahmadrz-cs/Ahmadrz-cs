<?php

namespace App\Tests\Form;

use App\Form\Type\QueryInvestmentType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class QueryInvestmentTypeTest extends TypeTestCase
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
            'userId' => 241,
            'assetId' => 14,
            'offeringId' => 67,
        ];
        $form = $this->factory->create(QueryInvestmentType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $defaults = [
            'perPage' => 10,
            'orderBy' => 'id',
            'orderDirection' => 'DESC',
            'userIsVIP' => null,
            'corporateInvestor' => null,
            'type' => [],
            'lifecycleStatus' => [],
            'hasDocuments' => null,
        ];
        $actual = $form->getData();
        foreach (array_merge($formData, $defaults) as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
