<?php

namespace App\Tests\Form;

use App\Form\Type\QueryOfferingType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class QueryOfferingTypeTest extends TypeTestCase
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
            'isSecondaryMrkt' => 1,
            'isFeatured' => 0,
            'page' => 6,
            'name' => 'river',
            'assetId' => 12,
        ];
        $form = $this->factory->create(QueryOfferingType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        $defaults = [
            'perPage' => 10,
            'orderBy' => 'id',
            'orderDirection' => 'DESC',
            'offeringType' => null,
            'sell_investment' => null,
            'visibility' => null,
            'lifecycleStatus' => [],
        ];
        $actual = $form->getData();
        foreach (array_merge($formData, $defaults) as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }
    }
}
