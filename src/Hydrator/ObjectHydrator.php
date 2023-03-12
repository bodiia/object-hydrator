<?php

declare(strict_types=1);

namespace App\Hydrator;

final class ObjectHydrator implements ObjectHydratorInterface
{
    public function hydrateObject(string $targetClass, array $raw): object
    {
        return $this->mappedObjectBySetters(new \ReflectionClass($targetClass), $raw);
    }

    public function hydrateObjects(string $targetClass, array $raw): array
    {
        $objects = [];
        foreach ($raw as $row) {
            $objects[] = $this->hydrateObject($targetClass, $row);
        }
        return $objects;
    }

    private function mappedObjectBySetters(\ReflectionClass $reflection, array $raw): object
    {
        $instance = $reflection->newInstance();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            $propertyName = $property->getName();
            $propertySetter = 'set' . ucfirst($propertyName);
            $propertyRawAccessor = strtolower($reflection->getShortName()) . '.' . $propertyName;

            /** @var \ReflectionNamedType $propertyType */
            $propertyType = $property->getType();

            if ($property->hasType() && ! $propertyType->isBuiltin()) {
                $instance->{$propertySetter}($this->resolveNotBuiltinTypeProperty($propertyType, $raw));
            }

            if ($reflection->hasMethod($propertySetter) && array_key_exists($propertyRawAccessor, $raw)) {
                $instance->{$propertySetter}($raw[$propertyRawAccessor]);
            }
        }

        if (! $this->allPropertiesInitialized($instance)) {
            throw new \InvalidArgumentException(sprintf(
                "Raw data does not match target class (%s) properties",
                $reflection->getName()
            ));
        }
        return $instance;
    }

    private function resolveNotBuiltinTypeProperty(\ReflectionNamedType $propertyType, array $raw): object
    {
        if (! class_exists($propertyType->getName())) {
            throw new \InvalidArgumentException(sprintf("Class \"%s\" does not exists", $propertyType->getName()));
        }
        $childReflection = new \ReflectionClass($propertyType->getName());
        $childClassName = strtolower($childReflection->getShortName());

        return $this->mappedObjectBySetters(
            $childReflection,
            $this->pullOutDataForChild($raw, $childClassName)
        );
    }

    private function pullOutDataForChild(array $raw, string $childClassName): array
    {
        $childRawData = [];
        foreach ($raw as $key => $value) {
            if (str_starts_with($key, $childClassName)) {
                $childRawData[$key] = $value;
            }
        }
        return $childRawData;
    }

    private function allPropertiesInitialized(object $instance): bool
    {
        $reflection = new \ReflectionClass($instance);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if (! $property->isInitialized($instance)) {
                return false;
            }
        }
        return true;
    }
}
