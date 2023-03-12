<?php

declare(strict_types=1);

namespace App\Hydrator;

interface ObjectHydratorInterface
{
    public function hydrateObject(string $targetClass, array $raw): object;

    public function hydrateObjects(string $targetClass, array $raw): array;
}
