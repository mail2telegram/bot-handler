<?php

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\MessengerInterface;
use M2T\State;
use Psr\Log\LoggerInterface;

class Reply extends Base
{
    use SendTrait;

    protected const MSG_ERROR = 'Произошла ошибка во время отправки';

    public function __construct(
        State $state,
        MessengerInterface $messenger,
        AccountManager $accountManager,
        LoggerInterface $logger
    ) {
        parent::__construct($state, $messenger, $accountManager);
        $this->logger = $logger;
    }

    public function actionIndex($update): void
    {
        $account = $this->accountManager->load($this->state->chatId);
        if (!$account) {
            $this->replyError();
            return;
        }

        $matches = $matches2 = $matches3 = [];
        preg_match('/^To: <(.+)>/m', $update['message']['reply_to_message']['text'], $matches);
        preg_match('/^From:(.+)<(.+)>/m', $update['message']['reply_to_message']['text'], $matches2);
        preg_match('/(.+)Date:/sm', $update['message']['reply_to_message']['text'], $matches3);

        if (!isset($matches[1], $matches2[2])) {
            $this->replyError();
            return;
        }

        $from = $matches[1];
        $toMail = $matches2[2];
        //$toName = $matches2[1]; // Can be used as name
        $subject = $matches3[1] ?? '';

        $mailbox = $this->accountManager->mailboxGet($account, $from);
        if ($mailbox === null) {
            $this->replyError();
            return;
        }

        $msg = &$update['message']['text'];
        $this->send($mailbox, $toMail, 'Re: ' . $subject, $msg);
    }
}
