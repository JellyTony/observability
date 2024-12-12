<?php
declare(strict_types=1);

namespace JellyTony\Observability\Metadata;


use JellyTony\Observability\Contracts\MetadataStorageInterface;

class InMemoryMetadataStorage implements MetadataStorageInterface
{
    private $metadata = [];

    public function add(string $key, string $value): void
    {
        if (empty($key) || empty($value)) {
            return;
        }

        $key = strtolower($key);
        $this->metadata[$key][] = $value;
    }

    public function set(string $key, string $value): void
    {
        if (empty($key) || empty($value)) {
            return;
        }

        $this->metadata[strtolower($key)] = [$value];
    }

    public function get(string $key): string
    {
        if (empty($key) || empty($this->metadata[$key])) {
            return '';
        }
        $key = strtolower($key);
        return $this->metadata[$key][0] ?? '';
    }

    public function getAll(): array
    {
        return $this->metadata;
    }

    public function values(string $key): array
    {
        if (empty($key) || empty($this->metadata[$key])) {
            return [];
        }
        return $this->metadata[strtolower($key)] ?? [];
    }

    public function range(callable $f): void
    {
        foreach ($this->metadata as $key => $values) {
            if (!$f($key, $values)) {
                break;
            }
        }
    }

    public function merge(array $newMetadata): array
    {
        $merged = $this->metadata;

        foreach ($newMetadata as $key => $values) {
            if (!isset($merged[$key])) {
                $merged[$key] = $values;
            } else {
                $merged[$key] = array_merge($merged[$key], $values);
            }
        }

        return $merged;
    }

    public function clear(): void
    {
        $this->metadata = [];
    }
}

final class Metadata
{
    private static $storage = null;

    /**
     * 获取存储实例
     */
    public static function getStorage(): MetadataStorageInterface
    {
        if (self::$storage === null) {
            self::$storage = new InMemoryMetadataStorage();  // 默认为内存存储
        }

        return self::$storage;
    }

    /**
     * 设置存储实例（方便在不同场景中替换存储方式）
     */
    public static function setStorage(MetadataStorageInterface $storage): void
    {
        self::$storage = $storage;
    }

    /**
     * 新增元数据
     */
    public static function new(array $mds = []): void
    {
        $storage = self::getStorage();
        foreach ($mds as $m) {
            foreach ($m as $key => $values) {
                foreach ($values as $value) {
                    $storage->add($key, $value);
                }
            }
        }
    }

    /**
     * 获取所有元数据
     */
    public static function getAll(): array
    {
        return self::getStorage()->getAll();
    }

    /**
     * 获取指定键的第一个值
     */
    public static function get(string $key): string
    {
        return self::getStorage()->get($key);
    }

    /**
     * 设置指定键的值
     */
    public static function set(string $key, string $value): void
    {
        self::getStorage()->set($key, $value);
    }

    /**
     * 获取指定键的所有值
     */
    public static function values(string $key): array
    {
        return self::getStorage()->values($key);
    }

    /**
     * 遍历所有元数据
     */
    public static function range(callable $f): void
    {
        self::getStorage()->range($f);
    }

    /**
     * 合并新的元数据
     */
    public static function merge(array $newMetadata): array
    {
        return self::getStorage()->merge($newMetadata);
    }

    /**
     * 克隆当前的 Metadata 实例
     */
    public static function clone(): array
    {
        return self::getStorage()->getAll();
    }

    /**
     * 清空元数据
     */
    public static function clear(): void
    {
        self::getStorage()->clear();
    }
}
