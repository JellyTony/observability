<?php

namespace JellyTony\Observability\Tests;

use JellyTony\Observability\Metadata\Metadata;
use PHPUnit\Framework\TestCase;

class MetadataTest  extends TestCase
{
    protected function setUp(): void
    {
        // 每次测试前清空所有 Metadata
        Metadata::clear();
    }

    public function testAddAndGet()
    {
        Metadata::set('key1', 'value1');
        Metadata::set('key1', 'value2');

        $this->assertEquals(['value1', 'value2'], Metadata::values('key1'));
        $this->assertEquals('value1', Metadata::get('key1'));
    }

    public function testSetAndGet()
    {
        Metadata::set('key2', 'value3');
        $this->assertEquals('value3', Metadata::get('key2'));
    }

    public function testRange()
    {
        Metadata::set('key1', 'value1');
        Metadata::set('key2', 'value2');

        $result = [];
        Metadata::range(function($key, $values) use (&$result) {
            $result[$key] = $values;
            return true;
        });

        $this->assertEquals([
            'key1' => ['value1'],
            'key2' => ['value2']
        ], $result);
    }

    public function testClone()
    {
        Metadata::set('key1', 'value1');
        $clonedMetadata = Metadata::clone();

        $this->assertEquals(['value1'], $clonedMetadata['key1']);
    }

    public function testMerge()
    {
        Metadata::set('key1', 'value1');

        $newMetadata = [
            'key1' => ['value2'],
            'key2' => ['value3']
        ];

        $mergedMetadata = Metadata::merge($newMetadata);

        $this->assertEquals([
            'key1' => ['value1', 'value2'],
            'key2' => ['value3']
        ], $mergedMetadata);
    }

    public function testClear()
    {
        Metadata::set('key1', 'value1');
        Metadata::clear();

        $this->assertEmpty(Metadata::getAll());
    }
}