<?php

declare(strict_types=1);

namespace Tests\Hydrator;

use App\Hydrator\ObjectHydrator;
use App\Hydrator\ObjectHydratorInterface;
use PHPUnit\Framework\TestCase;

/** @covers ObjectHydrator */
final class ObjectHydratorTest extends TestCase
{
    private ObjectHydratorInterface $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new ObjectHydrator();
    }

    public function testHydratorHydratesObject(): void
    {
        $objectData = [
            'dummy.value' => 'test',
            'nested.value' => 'test1',
        ];

        $obj = $this->hydrator->hydrateObject(Dummy::class, $objectData);

        self::assertInstanceOf(Dummy::class, $obj);
        self::assertInstanceOf(Nested::class, $obj->getNested());
        self::assertEquals($obj->getValue(), $objectData['dummy.value']);
        self::assertEquals($obj->getNested()->getValue(), $objectData['nested.value']);
    }

    public function testHydratorHydratesObjects(): void
    {
        $objectData = [
            [
                'dummy.value' => 'dummy1',
                'nested.value' => 'nested1',
            ],
            [
                'dummy.value' => 'dummy2',
                'nested.value' => 'nested2',
            ]
        ];

        [$first, $second] = $this->hydrator->hydrateObjects(Dummy::class, $objectData);

        self::assertEquals($first->getValue(), $objectData[0]['dummy.value']);
        self::assertEquals($first->getNested()->getValue(), $objectData[0]['nested.value']);

        self::assertEquals($second->getValue(), $objectData[1]['dummy.value']);
        self::assertEquals($second->getNested()->getValue(), $objectData[1]['nested.value']);
    }

    public function testHydratorThrowExceptionIfRawDataDoesNotMatchClassProperties(): void
    {
        $objectData = [
            'dummy.value' => 'test',
        ];

        self::expectException(\InvalidArgumentException::class);

        $this->hydrator->hydrateObject(Dummy::class, $objectData);
    }
}

// phpcs:ignore
class Dummy
{
    private string $value;

    private Nested $nested;

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getNested(): Nested
    {
        return $this->nested;
    }

    public function setNested(Nested $nested): void
    {
        $this->nested = $nested;
    }
}

// phpcs:ignore
class Nested
{
    private string $value;

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
