<?php

namespace M2T;

use Redis;

class QueueLocator
{
    protected Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function lock(): string
    {
        $count = App::get('queueAmount');
        $i = 1;
        while ($i <= $count) {
            if ($this->redis->setnx("queue:$i", true)) {
                return "queue:$i";
            }
            $i++;
        }
        return '';
    }

    public function release(string $key): void
    {
        $this->redis->del($key);
    }
}
