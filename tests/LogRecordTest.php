<?php

namespace JellyTony\Observability\Tests;

use JellyTony\Observability\Logging\LogRecord;
use PHPUnit\Framework\TestCase;

class LogRecordTest extends TestCase
{
    private $logRecord;

    protected function setUp(): void
    {
        $this->logRecord = new LogRecord();
    }

    public function testSetAndGet()
    {
        // 设置字段
        $this->logRecord->set('user.profile.name', 'John Doe');
        $this->logRecord->set('user.profile.age', 30);

        // 获取字段
        $this->assertEquals('John Doe', $this->logRecord->get('user.profile.name'));
        $this->assertEquals(30, $this->logRecord->get('user.profile.age'));
    }

    public function testHas()
    {
        // 设置字段
        $this->logRecord->set('user.profile.name', 'John Doe');

        // 检查字段是否存在
        $this->assertTrue($this->logRecord->has('user.profile.name'));
        $this->assertFalse($this->logRecord->has('user.profile.nonexistent'));
    }

    public function testDelete()
    {
        // 设置字段
        $this->logRecord->set('user.profile.name', 'John Doe');
        $this->logRecord->set('name', 'zgy');

        // 删除字段
        $this->logRecord->delete('user.profile.name');

        // 检查字段是否被删除
        $this->assertNull($this->logRecord->get('user.profile.name'));
    }


    public function testClear()
    {
        // 设置字段
        $this->logRecord->set('user.profile.name', 'John Doe');
        $this->logRecord->set('user.profile.age', 30);

        // 清空所有字段
        $this->logRecord->clear();

        // 检查所有字段是否被清空
        $this->assertEmpty($this->logRecord->all());
    }

    public function testNestedFields()
    {
        // 设置多层级字段
        $this->logRecord->set('user.profile.address.city', 'New York');
        $this->logRecord->set('user.profile.address.zip', '10001');

        // 获取多层级字段
        $this->assertEquals('New York', $this->logRecord->get('user.profile.address.city'));
        $this->assertEquals('10001', $this->logRecord->get('user.profile.address.zip'));
    }

    public function testDeleteNestedField()
    {
        // 设置多层级字段
        $this->logRecord->set('user.profile.address.city', 'New York');

        // 删除嵌套字段
        $this->logRecord->delete('user.profile.address.city');

        // 验证字段是否被删除
        $this->assertNull($this->logRecord->get('user.profile.address.city'));
    }

    public function testAllFields()
    {
        // 设置多个字段
        $this->logRecord->set('user.profile.name', 'John Doe');
        $this->logRecord->set('user.profile.age', 30);

        // 获取所有字段
        $expected = [
            'user' => [
                'profile' => [
                    'name' => 'John Doe',
                    'age' => 30
                ]
            ]
        ];

        $this->assertEquals($expected, $this->logRecord->all());
    }
}