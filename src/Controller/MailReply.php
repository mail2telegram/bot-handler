<?php

namespace M2T\Controller;

use M2T\Action\MailDelete;
use M2T\Action\MailSpam;
use M2T\Model\Email;

class MailReply extends BaseMail
{
    protected const MSG_ERROR = 'Произошла ошибка во время отправки';

    public function actionIndex(array $update): void
    {
        if (!$account = $this->getAccountOrReply()) {
            return;
        }

        $matches = $matches2 = $matches3 = [];

        preg_match('/^Email: <(.+)>/m', $update['message']['reply_to_message']['text'], $matches);
        preg_match('/^ReplyTo:.+<(.+)>/m', $update['message']['reply_to_message']['text'], $matches2);
        preg_match('/(.+)Date:/sm', $update['message']['reply_to_message']['text'], $matches3);

        if (!isset($matches[1], $matches2[1])) {
            $this->replyError();
            return;
        }

        $draftEmail = new Email();
        $draftEmail->from = $matches[1];
        $draftEmail->to = [['address' => $matches2[1]]];
        $draftEmail->subject = 'Re: ' . ($matches3[1] ?? '');

        $mailbox = $this->accountManager->mailboxGet($account, $draftEmail->from);
        if ($mailbox === null) {
            $this->replyError();
            return;
        }

        $this->parseMessageAndAttachment($update, $draftEmail);

        if ($this->send($mailbox, $draftEmail)) {
            $replyMarkup = &$update['message']['reply_to_message']['reply_markup'];
            $msgId = &$update['message']['reply_to_message']['message_id'];
            $this->messenger->deleteMarkupBtn($replyMarkup['inline_keyboard'], MailSpam::NAME);
            $this->messenger->deleteMarkupBtn($replyMarkup['inline_keyboard'], MailDelete::NAME);
            $this->messenger->editMessageReplyMarkup($this->state->chatId, $msgId, $replyMarkup);
        }
    }
}
