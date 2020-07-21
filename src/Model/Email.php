<?php

namespace M2T\Model;

class Email
{
    public string $from;
    public array $to;
    public string $subject;
    public string $message;
    public ?Attachment $attachment = null;

    public function __construct(
        string $from = '',
        array $to = [],
        string $subject = '',
        string $message = ''
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
    }
}
