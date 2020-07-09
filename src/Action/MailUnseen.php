<?php

namespace M2T\Action;

class MailUnseen extends MailBase
{
    public const NAME = 'unseen';

    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->flagSeenUnset($mailbox, $mailId)) {
            $replyMarkup = &$callback['message']['reply_markup'];
            $msgId = &$callback['message']['message_id'];
            $this->messenger->replaceMarkupBtn(
                $replyMarkup['inline_keyboard'],
                static::NAME,
                ['text' => 'Mark as read', 'callback_data' => MailSeen::NAME . ':' . $mailId]
            );
            $this->messenger->editMessageReplyMarkup($chatId, $msgId, $replyMarkup);
            $this->messenger->answerCallbackQuery($callback['id'], static::MSG_ERROR);
            return;
        }
        $this->replyError($callback['id']);
    }
}
