<?php

namespace M2T\Action;

class MailUnseen extends MailBase
{
    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $callback['from']['id'];
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->flagSeenUnset($mailbox, $mailId)) {
            $replyMarkup = &$callback['message']['reply_markup'];
            $msgId = &$callback['message']['message_id'];
            $this->messenger->replaceMarkupBtn(
                $replyMarkup['inline_keyboard'],
                'Unseen',
                ['text' => 'Seen', 'callback_data' => "seen:$mailId"]
            );
            $this->messenger->editMessageReplyMarkup($chatId, $msgId, $replyMarkup);
            return;
        }
        $this->messenger->sendMessage($chatId, static::MSG_ERROR);
    }
}
