<?php

namespace M2T;

use M2T\Model\DraftEmail;
use M2T\Model\Email;

class State
{
    public int $chatId;
    public string $handler;
    public string $action; // next action
    public bool $changed = false;
    public ?Email $mailbox = null;
    public ?DraftEmail $draftEmail = null;

    public function __construct(
        int $chatId,
        string $handler = '',
        string $action = ''
    ) {
        $this->chatId = $chatId;
        $this->handler = $handler;
        $this->action = $action;
    }

    public function set($handler, $action): void
    {
        $this->handler = $handler;
        $this->action = $action;
    }

    public function reset(): void
    {
        $this->handler = '';
        $this->action = '';
    }
}
