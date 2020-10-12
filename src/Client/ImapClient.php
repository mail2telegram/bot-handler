<?php

namespace M2T\Client;

use finfo;
use M2T\Model\Email;
use M2T\Model\Mailbox;
use Psr\Log\LoggerInterface;

// @phan-file-suppress PhanTypeMismatchArgumentInternalProbablyReal, PhanTypeMismatchReturn

final class ImapClient
{
    private const FOLDER_INBOX = 'INBOX';
    private const FOLDER_SENT = 'Sent';
    private const FOLDER_TRASH = 'Trash';
    private const FOLDER_SPAM = 'Spam';

    private const FOLDER_MAP = [
        'imap.gmail.com' => [
            self::FOLDER_SENT => '[Gmail]/Sent Mail',
            self::FOLDER_TRASH => '[Gmail]/Bin',
            self::FOLDER_SPAM => '[Gmail]/Spam',
        ],
        'imap.mail.ru' => [
            self::FOLDER_SENT => '&BB4EQgQ,BEAEMAQyBDsENQQ9BD0ESwQ1-',
            self::FOLDER_TRASH => '&BBoEPgRABDcEOAQ9BDA-',
            self::FOLDER_SPAM => '&BCEEPwQwBDw-',
        ],
    ];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function debugErrors(): void
    {
        if ($errors = imap_errors()) {
            $this->logger->warning('Imap errors:', $errors);
        }
        if ($errors = imap_alerts()) {
            $this->logger->warning('Imap alerts:', $errors);
        }
    }

    private function getFolder(string $host, string $key): string
    {
        return self::FOLDER_MAP[$host][$key] ?? $key;
    }

    /**
     * @param Mailbox $mailAccount
     * @param string  $mailbox
     * @return resource|false
     */
    private function imapOpen(Mailbox $mailAccount, string $mailbox): bool
    {
        $stream = @imap_open($mailbox, $mailAccount->email, $mailAccount->getPwd());
        $this->debugErrors();
        return $stream ?: false;
    }

    private function imapList($stream, string $mailbox, string $pattern = '*'): array
    {
        $result = @imap_list($stream, $mailbox, $pattern);
        $this->debugErrors();
        return $result ?: [];
    }

    private function imapAppend($stream, string $mailbox, string $msg): bool
    {
        $result = @imap_append($stream, $mailbox, $msg);
        $this->debugErrors();
        return $result ?: false;
    }

    private function imapDelete($stream, int $mailId): bool
    {
        $result = @imap_delete($stream, $mailId, FT_UID);
        $this->debugErrors();
        return $result ?: false;
    }

    private function imapMailMove($stream, int $mailId, string $folder): bool
    {
        $result = @imap_mail_move($stream, $mailId, $folder, CP_UID);
        $this->debugErrors();
        return $result ?: false;
    }

    private function imapSetFlag($stream, int $mailId, string $flag): bool
    {
        $result = @imap_setflag_full($stream, $mailId, $flag, ST_UID);
        $this->debugErrors();
        return $result ?: false;
    }

    private function imapUnsetFlag($stream, int $mailId, string $flag): bool
    {
        $result = @imap_clearflag_full($stream, $mailId, $flag, ST_UID);
        $this->debugErrors();
        return $result ?: false;
    }

    private function imapHeaderInfo($stream, int $mailId): array
    {
        $msgNo = imap_msgno($stream, $mailId);
        $this->debugErrors();
        if (!$msgNo) {
            return [];
        }
        $result = @imap_headerinfo($stream, $msgNo);
        $this->debugErrors();
        return (array) $result;
    }

    private function getMailbox(Mailbox $mailAccount, string $folder = self::FOLDER_INBOX): string
    {
        return "{{$mailAccount->imapHost}:{$mailAccount->imapPort}/imap/{$mailAccount->imapSocketType}}$folder";
    }

    public function check(Mailbox $mailAccount): bool
    {
        $mailbox = $this->getMailbox($mailAccount);
        if (!$stream = $this->imapOpen($mailAccount, $mailbox)) {
            return false;
        }
        imap_close($stream);
        return true;
    }

    public function folderList(Mailbox $mailAccount): array
    {
        $mailbox = $this->getMailbox($mailAccount, '');
        if (!$stream = $this->imapOpen($mailAccount, $mailbox)) {
            return [];
        }
        $result = $this->imapList($stream, $mailbox);
        imap_close($stream);
        return $result;
    }

    public function headerInfo(Mailbox $mailAccount, int $mailId): array
    {
        $mailbox = $this->getMailbox($mailAccount, '');
        if (!$stream = $this->imapOpen($mailAccount, $mailbox)) {
            return [];
        }
        $result = $this->imapHeaderInfo($stream, $mailId);
        imap_close($stream);
        return $result;
    }

    public function appendToSent(Mailbox $mailAccount, Email $email): bool
    {
        $folder = $this->getFolder($mailAccount->imapHost, self::FOLDER_SENT);
        $mailbox = $this->getMailbox($mailAccount, $folder);
        if (!$stream = $this->imapOpen($mailAccount, $mailbox)) {
            return false;
        }

        $to = '';
        foreach ($email->to as $address) {
            $to = $address['address'] . ',';
        }
        $to = rtrim($to, ',');

        if ($email->attachment) {
            /** @noinspection NonSecureUniqidUsageInspection RandomApiMigrationInspection */
            $boundary = '------=' . md5(uniqid(rand()));
            $type = (new finfo(FILEINFO_MIME))->buffer($email->attachment->content);
            $msg = "From: {$mailAccount->email}"
                . "\r\nTo: $to"
                . "\r\nSubject: $email->subject"
                . "\r\nMIME-Version: 1.0"
                . "\r\nContent-Type: multipart/mixed; boundary=\"$boundary\""
                . "\r\n\r\n--$boundary"
                . "\r\nContent-Type: text/plain"
                . "\r\n\r\n$email->message\r\n"
                . "\r\n\r\n--$boundary"
                . ($type ? "\r\nContent-Type: $type; name=\"{$email->attachment->name}\"" : '')
                . "\r\nContent-Transfer-Encoding: base64"
                . "\r\nContent-Disposition: attachment; filename=\"{$email->attachment->name}\""
                . "\r\n\r\n\r\n" . chunk_split(base64_encode($email->attachment->content)) . "\r\n"
                . "\r\n\r\n--$boundary--\r\n\r\n";
        } else {
            $msg = "From: {$mailAccount->email}"
                . "\r\nTo: $to"
                . "\r\nSubject: $email->subject"
                . "\r\n\r\n$email->message\r\n";
        }

        $result = $this->imapAppend($stream, $mailbox, $msg);
        imap_close($stream);
        return $result;
    }

    public function delete(Mailbox $mailAccount, int $mailId): bool
    {
        $mailbox = $this->getMailbox($mailAccount);
        $stream = $this->imapOpen($mailAccount, $mailbox);
        if (!$stream) {
            return false;
        }
        $result = $this->imapDelete($stream, $mailId);
        imap_close($stream, CL_EXPUNGE);
        return $result;
    }

    private function moveTo(Mailbox $mailAccount, int $mailId, string $folder, string $from = self::FOLDER_INBOX): bool
    {
        if (!$stream = $this->imapOpen($mailAccount, $this->getMailbox($mailAccount, $from))) {
            return false;
        }
        $result = $this->imapMailMove($stream, $mailId, $folder);
        imap_close($stream, CL_EXPUNGE);
        return $result;
    }

    public function moveToSpam(Mailbox $mailAccount, int $mailId): bool
    {
        $folder = $this->getFolder($mailAccount->imapHost, self::FOLDER_SPAM);
        return $this->moveTo($mailAccount, $mailId, $folder);
    }

    public function moveToTrash(Mailbox $mailAccount, int $mailId): bool
    {
        $folder = $this->getFolder($mailAccount->imapHost, self::FOLDER_TRASH);
        return $this->moveTo($mailAccount, $mailId, $folder);
    }

    private function setFlag(Mailbox $mailAccount, int $mailId, string $flag): bool
    {
        if (!$stream = $this->imapOpen($mailAccount, $this->getMailbox($mailAccount))) {
            return false;
        }
        $result = $this->imapSetFlag($stream, $mailId, $flag);
        imap_close($stream, CL_EXPUNGE);
        return $result;
    }

    private function unsetFlag(Mailbox $mailAccount, int $mailId, string $flag): bool
    {
        if (!$stream = $this->imapOpen($mailAccount, $this->getMailbox($mailAccount))) {
            return false;
        }
        $result = $this->imapUnsetFlag($stream, $mailId, $flag);
        imap_close($stream, CL_EXPUNGE);
        return $result;
    }

    public function flagSeenSet(Mailbox $mailAccount, int $mailId): bool
    {
        return $this->setFlag($mailAccount, $mailId, '\Seen');
    }

    public function flagSeenUnset(Mailbox $mailAccount, int $mailId): bool
    {
        return $this->unsetFlag($mailAccount, $mailId, '\Seen');
    }
}
