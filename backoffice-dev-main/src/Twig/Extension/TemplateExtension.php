<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\MonthendExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TemplateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('monthend_checklist', [
                MonthendExtensionRuntime::class,
                'monthendChecklist',
            ]),
            new TwigFilter('divide_money', [$this, 'divideMoney']),
            new TwigFilter('int', function ($value) {
                return (int) $value;
            }),
            new TwigFilter('float', function ($value) {
                return (float) $value;
            }),
            new TwigFilter('string', function ($value) {
                return (string) $value;
            }),
            new TwigFilter('valueColumn', function ($value) {
                return array_column($value, 'value');
            }),
        ];
    }

    // public function getFunctions(): array
    // {
    //     return [
    //         new TwigFunction('monthend_checklist', [MonthendExtensionRuntime::class, 'monthendChecklist']),
    //     ];
    // }

    public function divideMoney(string|float|int $amount): string
    {
        return number_format((float) $amount / 100, 2, '.', '');
    }
}
