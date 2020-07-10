<?php

namespace M2T\Model;

class DraftEmail
{
    public string $from;
    public array $to;
    public string $subject;
    public string $message;
    public array $attachment;

    public function __construct(
        string $from = '',
        array $to = [],
        string $subject = '',
        string $message = '',
        array $attachment = []
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->attachment = $attachment;
    }
}
