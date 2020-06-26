<?php

namespace App\Model;

class Account
{
    /**
     * @var Email[]
     */
    public array $emails;
    public int $chatId;

    public function __construct(
        array $emails,
        int $chatId
    ) {
        $this->emails = $emails;
        $this->chatId = $chatId;
    }
}
