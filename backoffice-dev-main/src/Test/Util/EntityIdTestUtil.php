<?php

namespace App\Test\Util;

final class EntityIdTestUtil
{
    public static function extractIds(iterable $entities): array
    {
        $ids = [];
        foreach ($entities as $entity) {
            $ids[] = $entity->getId();
        }
        return $ids;
    }

    /**
     * @template T
     * @param T $entity
     * @return T
     */
    public static function setEntityId(object $entity, int|string $id = 1): object
    {
        $reflection = new \ReflectionClass($entity);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setValue($entity, $id);
        return $entity;
    }
}
