<?php

namespace Eril\Auth\Tests;

use Eril\Auth\Support\DataObject;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DataObjectTest extends TestCase
{
    public function test_it_allows_property_access(): void
    {
        $object = new class(['name' => 'Eril']) extends DataObject {};

        $this->assertSame('Eril', $object->name);
    }

    public function test_it_allows_array_access(): void
    {
        $object = new class(['email' => 'test@example.com']) extends DataObject {};

        $this->assertSame('test@example.com', $object['email']);
    }

    public function test_it_is_read_only(): void
    {
        $object = new class(['name' => 'Eril']) extends DataObject {};

        $this->expectException(LogicException::class);

        $object['name'] = 'Other';
    }

    public function test_it_converts_to_array(): void
    {
        $object = new class(['id' => 1]) extends DataObject {};

        $this->assertSame(['id' => 1], $object->toArray());
    }
}