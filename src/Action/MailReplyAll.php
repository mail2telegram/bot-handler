<?php

namespace M2T\Action;

use M2T\Model\Email;
use PHPMailer\PHPMailer\PHPMailer;

class MailReplyAll extends MailReply
{
    public const NAME = 'reall';

    protected function getTo(Email $mailAccount, array $headers): array
    {
        $from = PHPMailer::parseAddresses($headers['reply_toaddress'] ?? $headers['fromaddress']);
        $to = PHPMailer::parseAddresses($headers['toaddress']);
        $all = array_merge($from, $to);
        foreach ($all as $index => $address) {
            if ($address['address'] === $mailAccount->email) {
                unset($all[$index]);
                break;
            }
        }
        return array_values($all);
    }
}
