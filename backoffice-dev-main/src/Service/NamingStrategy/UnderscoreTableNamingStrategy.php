<?php

namespace App\Service\NamingStrategy;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

class UnderscoreTableNamingStrategy extends DefaultNamingStrategy
{
    #[\Override]
    public function classToTableName($className): string
    {
        if (str_contains($className, '\\')) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }

        return $this->underscore($className);
    }

    private function underscore(string $string): string
    {
        $string = preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $string);

        return strtolower($string);
    }
}
