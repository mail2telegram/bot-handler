<?php

namespace M2T\Action;

class MailSeen extends MailBase
{
    public function __invoke(array $callback, string $email, int $mailId)
    {
        $chatId = $this->getChatId($callback);
        if (!$mailbox = $this->getEmailAccountOrReply($callback, $email)) {
            return;
        }
        if ($this->imapClient->flagSeenSet($mailbox, $mailId)) {
            $replyMarkup = &$callback['message']['reply_markup'];
            $msgId = &$callback['message']['message_id'];
            $this->messenger->replaceMarkupBtn(
                $replyMarkup['inline_keyboard'],
                'Seen',
                ['text' => 'Unseen', 'callback_data' => "unseen:$mailId"]
            );
            $this->messenger->editMessageReplyMarkup($chatId, $msgId, $replyMarkup);
            return;
        }
        $this->replyError($chatId);
    }
}
