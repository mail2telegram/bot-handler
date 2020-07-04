<?php

namespace M2T\Client;

use M2T\Model\Email;
use Throwable;

class ImapClient
{
    public function appendToSent(Email $mailbox, string $to, string $subject, string $text): bool
    {
        switch ($mailbox->imapHost) {
            default:
                $folder = 'Sent';
                break;
            case 'imap.gmail.com':
                $folder = '[Gmail]/Sent Mail';
                break;
            case 'imap.mail.ru':
                $folder = '&BB4EQgQ,BEAEMAQyBDsENQQ9BD0ESwQ1-';
                break;
        }
        $imapMailbox = "{{$mailbox->imapHost}:{$mailbox->imapPort}/imap/{$mailbox->imapSocketType}}$folder";
        try {
            $stream = imap_open($imapMailbox, $mailbox->email, $mailbox->pwd);
            imap_append(
                $stream,
                $imapMailbox,
                "From: {$mailbox->email}\r\nTo: $to\r\nSubject: $subject"
                . "\r\n\r\n$text\r\n"
            );
            imap_close($stream);
        } catch (Throwable $e) {
            return false;
        }
        return true;
    }

    public function delete(Email $mailbox, int $mailId): bool
    {
        $imapMailbox = "{{$mailbox->imapHost}:{$mailbox->imapPort}/imap/{$mailbox->imapSocketType}}INBOX";
        try {
            $stream = imap_open($imapMailbox, $mailbox->email, $mailbox->pwd);
            imap_delete($stream, $mailId, FT_UID);
            imap_close($stream);
        } catch (Throwable $e) {
            return false;
        }
        return true;
    }
}
