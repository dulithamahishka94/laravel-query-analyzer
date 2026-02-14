<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use Orchestra\Testbench\TestCase;

class CacheQueryStoragePropertyTest extends TestCase
{
    public function test_can_be_instantiated_without_store(): void
    {
        $storage = new CacheQueryStorage();

        $reflection = new \ReflectionProperty($storage, 'store');
        $this->assertNull($reflection->getValue($storage));
    }

    public function test_can_be_instantiated_with_custom_store(): void
    {
        $storage = new CacheQueryStorage('redis');

        $reflection = new \ReflectionProperty($storage, 'store');
        $this->assertEquals('redis', $reflection->getValue($storage));
    }

    public function test_can_be_instantiated_with_null_store(): void
    {
        $storage = new CacheQueryStorage(null);

        $reflection = new \ReflectionProperty($storage, 'store');
        $this->assertNull($reflection->getValue($storage));
    }

    public function test_store_property_is_declared_on_class(): void
    {
        $reflection = new \ReflectionClass(CacheQueryStorage::class);
        $this->assertTrue(
            $reflection->hasProperty('store'),
            'CacheQueryStorage must declare the $store property to avoid PHP 8.2+ deprecation'
        );

        $property = $reflection->getProperty('store');
        $this->assertTrue($property->isProtected());
        $this->assertTrue($property->hasType());
        $this->assertEquals('string', $property->getType()->getName());
        $this->assertTrue($property->getType()->allowsNull());
    }
}
