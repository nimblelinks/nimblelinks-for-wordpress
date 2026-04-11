<?php

class WP_REST_Request implements ArrayAccess
{
    protected array $params = [];

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function get_param(string $key)
    {
        return $this->params[$key] ?? null;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->params[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->params[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->params[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->params[$offset]);
    }
}
