<?php

namespace M2T\Controller;

use M2T\Action\MailDelete;
use M2T\Action\MailSpam;

class MailReply extends BaseMail
{
    protected const MSG_ERROR = 'Произошла ошибка во время отправки';

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

        $message = '';
        $attachment = [];
        $this->parseMessageAndAttachment($update, $message, $attachment);

        if ($this->send($mailbox, $toMail, 'Re: ' . $subject, $message, $attachment)) {
            $replyMarkup = &$update['message']['reply_to_message']['reply_markup'];
            $msgId = &$update['message']['reply_to_message']['message_id'];
            $this->messenger->deleteMarkupBtn($replyMarkup['inline_keyboard'], MailSpam::NAME);
            $this->messenger->deleteMarkupBtn($replyMarkup['inline_keyboard'], MailDelete::NAME);
            $this->messenger->editMessageReplyMarkup($this->state->chatId, $msgId, $replyMarkup);
        }
    }
}
