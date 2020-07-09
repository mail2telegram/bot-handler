<?php

namespace M2T\Controller;

use M2T\App;
use M2T\Client\ImapClient;
use M2T\Client\MessengerInterface;
use M2T\Client\SmtpClient;
use M2T\State;
use Psr\Log\LoggerInterface;
use Throwable;

trait SendTrait
{
    protected LoggerInterface $logger;
    protected MessengerInterface $messenger;
    protected State $state;

    protected function send($mailboxFrom, $to, $subject, $msg): void
    {
        try {
            $result = App::get(SmtpClient::class)->send($mailboxFrom, $to, $subject, $msg);

            // Gmail при отправке по SMTP сам добавляет письмо в отправленные.
            // А Яндекс нет. В крайнем случае будет добавлено дважды.
            if ($mailboxFrom->smtpHost !== 'smtp.gmail.com') {
                App::get(ImapClient::class)->appendToSent($mailboxFrom, $to, $subject, $msg);
            }
        } catch (Throwable $e) {
            $this->logger->error((string) $e);
            $this->sendErrorHasOccurred();
            return;
        }
        $this->messenger->sendMessage($this->state->chatId, $result ? 'Отправлено' : 'Ошибка');
    }

    /**
     * @suppress PhanUndeclaredConstantOfClass
     */
    protected function sendErrorHasOccurred(): void
    {
        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_ERROR,
        );
    }
}
