<?php

namespace M2T\Client;

interface MessengerInterface
{
    public function sendMessage(int $chatId, string $text, string $replyMarkup = ''): bool;

    public function deleteMessage(int $chatId, int $messageId): bool;

    public function answerCallbackQuery(string $callbackId, string $text = ''): bool;

    public function editMessageReplyMarkup(int $chatId, int $messageId, array $replyMarkup): bool;

    public function replaceMarkupBtn(array &$replyMarkup, string $key, array $newBtn): bool;

    public function deleteMarkupBtn(array &$replyMarkup, string $key): bool;

    public function getFile($fileId, &$file, &$fileName): bool;

    public function sendChatAction(int $chatId, string $action): bool;
}
