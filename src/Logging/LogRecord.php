<?php

namespace JellyTony\Observability\Logging;

use JellyTony\Observability\Contracts\LogRecord as API;

class LogRecord implements API
{
    private $maxDepth = 100; // 设置最大递归深度

    private $data = [];

    private $depthData = [];

    public function __construct($data = [], $depthData = [], $maxDepth = 100)
    {
        $this->data = $data;
        $this->depthData = $depthData;
        $this->maxDepth = $maxDepth;
    }


    public function set($key, $value)
    {
        if (!strpos($key, '.')) {
            $this->data[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->depthData;

        foreach ($keys as $index) {
            if (!isset($array[$index])) {
                $array[$index] = [];
            }
            $array = &$array[$index];
        }

        $array = $value;
    }

    public function get(string $key)
    {
        if (!strpos($key, '.')) {
            return !empty($this->data[$key]) ? $this->data[$key] : '';
        }

        $keys = explode('.', $key);
        $array = $this->depthData;

        foreach ($keys as $index) {
            if (!isset($array[$index])) {
                return null; // 如果字段不存在，返回 null
            }
            $array = $array[$index];
        }

        return $array;
    }

    public function has(string $key): bool
    {
        if (!strpos($key, '.')) {
            return !empty($this->data[$key]);
        }

        $keys = explode('.', $key);
        $array = $this->depthData;

        foreach ($keys as $index) {
            if (!isset($array[$index])) {
                return false;
            }
            $array = $array[$index];
        }

        return true;
    }

    public function delete(string $key)
    {
        if (!strpos($key, '.')) {
            unset($this->data[$key]);
        }

        $depth = 0;
        $keys = explode('.', $key);
        $array = &$this->depthData;


        // 通过递归找到目标字段的父层并删除目标字段
        foreach ($keys as $index) {
            if ($depth > $this->maxDepth) {
                return; // 超过最大递归深度，退出
            }

            if (isset($array[$index])) {
                // 到达最后一层并删除目标字段
                if (next($keys) === false) {
                    unset($array[$index]);
                } else {
                    $array = &$array[$index]; // 向下递归
                }
            } else {
                return; // 如果路径中的某一层字段不存在，直接退出
            }

            $depth++;
        }

        // 删除父级字段：检查父级是否为空，如果为空则删除
        $this->removeEmptyParents($this->depthData);
    }

    /**
     * 递归检查并删除空的父级字段
     *
     * @param array $array
     */
    private function removeEmptyParents(&$array)
    {
        $depth = 0;
        foreach ($array as $key => $value) {
            if ($depth > $this->maxDepth) {
                return; // 超过最大递归深度，退出
            }
            $depth++;
            if (is_array($value)) {
                // 递归调用，检查是否有空的子级
                $this->removeEmptyParents($array[$key]);
                // 如果没有内容，删除该父级
                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            }
        }
    }

    public function clear(): void
    {
        $this->data = [];
        $this->depthData = [];
    }

    public function all(): array
    {
        return array_merge($this->data, $this->depthData);
    }
}