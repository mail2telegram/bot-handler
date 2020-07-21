<?php

namespace M2T\Action;

use M2T\AccountManager;
use M2T\Client\ImapClient;
use M2T\Client\TelegramClient;
use M2T\Controller\MailSend;
use M2T\Model\DraftEmail;
use M2T\Model\Email;
use M2T\State;
use M2T\StateManager;
use PHPMailer\PHPMailer\PHPMailer;

class MailReply extends MailBase
{
    public const NAME = 're';
    protected const MSG_INSERT_MESSAGE = 'Введите текст сообщения:';

    protected TelegramClient $messenger;
    protected AccountManager $accountManager;
    protected ImapClient $imapClient;
    protected StateManager $stateManager;

    public function __construct(
        TelegramClient $messenger,
        AccountManager $accountManager,
        ImapClient $imapClient,
        StateManager $stateManager
    ) {
        parent::__construct($messenger, $accountManager, $imapClient);
        $this->stateManager = $stateManager;
    }

    public function __invoke(array $callback, string $emailHash, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailAccount = $this->getEmailAccountOrReply($callback, $emailHash)) {
            return;
        }

        $headers = $this->imapClient->headerInfo($mailAccount, $mailId);
        if (!$headers || !$to = $this->getTo($mailAccount, $headers)) {
            $this->replyError($callback['id']);
            return;
        }

        $state = new State($chatId, MailSend::class, MailSend::ACTION_INSERT_MSG_AND_SEND);
        $state->draftEmail = new DraftEmail(
            $mailAccount->email,
            $to,
            'Re: ' . ($headers['subject'] ?? '')
        );
        $this->stateManager->save($state);

        $this->messenger->sendMessage(
            $chatId,
            static::MSG_INSERT_MESSAGE,
            ['force_reply' => true]
        );
    }

    /**
     * @param Email $mailAccount
     * @param array $headers
     * @return array [['address' => '', 'name' => ''], ...]
     * @SuppressWarnings(PHPMD)
     * @noinspection PhpUnusedParameterInspection
     */
    protected function getTo(Email $mailAccount, array $headers): array
    {
        return PHPMailer::parseAddresses($headers['reply_toaddress'] ?? $headers['fromaddress']);
    }
}
