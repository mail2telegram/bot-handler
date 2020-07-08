<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use M2T\App;
use Psr\Log\LoggerInterface;
use Throwable;

class TelegramClient implements MessengerInterface
{
    protected const BASE_URL = 'https://api.telegram.org/bot';

    protected LoggerInterface $logger;
    protected Client $client;

    public function __construct(LoggerInterface $logger, ?ClientInterface $client = null)
    {
        $this->logger = $logger;
        $this->client = $client
            ?? new Client(
                [
                    'base_uri' => static::BASE_URL . (getenv('TELEGRAM_TOKEN') ?: App::get('telegramToken')) . '/',
                    'timeout' => App::get('telegramTimeout'),
                ]
            );
    }

    protected function execute(string $method, array $data): array
    {
        $this->logger->debug($method . 'Request:', $data);

        try {
            $response = $this->client->request('POST', $method, $data);
        } catch (Throwable $e) {
            $this->logger->error('Telegram: ' . $e);
            return [];
        }

        try {
            $response = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->logger->error('Telegram: json decode error');
            return [];
        }

        if (!isset($response['ok'])) {
            $this->logger->error('Telegram: wrong response');
            return [];
        }

        if ($response['ok'] !== true) {
            $this->logger->error('Telegram: ' . ($response['description'] ?? 'no description'));
            return [];
        }

        $this->logger->debug($method . 'Response:', $response);
        return is_array($response['result'])
            ? $response['result']
            : ['result' => $response['result']];
    }

    public function sendMessage(int $chatId, string $text, string $replyMarkup = ''): bool
    {
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode(['remove_keyboard' => true]),
            ],
        ];
        if ($replyMarkup) {
            $data['form_params']['reply_markup'] = $replyMarkup;
        }
        $result = $this->execute('sendMessage', $data);
        return (bool) $result;
    }

    public function deleteMessage(int $chatId, int $messageId): bool
    {
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'message_id' => $messageId,
            ],
        ];
        $result = $this->execute('deleteMessage', $data);
        return (bool) $result;
    }

    public function editMessageReplyMarkup(int $chatId, int $messageId, array $replyMarkup): bool
    {
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'reply_markup' => json_encode($replyMarkup),
            ],
        ];
        $result = $this->execute('editMessageReplyMarkup', $data);
        return (bool) $result;
    }

    public function replaceMarkupBtn(array &$replyMarkup, string $key, array $newBtn): void
    {
        foreach ($replyMarkup as &$keyboardsList) {
            foreach ($keyboardsList as $index => $keyboard) {
                if ($keyboard['text'] === $key) {
                    $keyboardsList[$index] = $newBtn;
                }
            }
        }
        unset($keyboardsList);
    }
}
