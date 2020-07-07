<?php

namespace M2T;

use Redis;

// @todo возможно Redis не нужен и достаточно статического массива

class StateManager
{
    protected Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    protected static function getKey(int $chatId): string
    {
        return "state:{$chatId}";
    }

    public function save(State $state): bool
    {
        return $this->redis->set(static::getKey($state->chatId), serialize($state));
    }

    public function get(int $chatId): State
    {
        $data = $this->redis->get(static::getKey($chatId));
        return $data ? unserialize($data, [State::class]) : new State($chatId);
    }
}
