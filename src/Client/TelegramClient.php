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
    protected const FILE_URL = 'https://api.telegram.org/file/bot<token>/<file_path>';

    protected LoggerInterface $logger;
    protected Client $client;

    public function __construct(LoggerInterface $logger, ?ClientInterface $client = null)
    {
        $this->logger = $logger;
        $this->client = $client
            ?? new Client(
                [
                    'base_uri' => static::BASE_URL . App::get('telegramToken') . '/',
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

    public function answerCallbackQuery(string $callbackId, string $text = ''): bool
    {
        $data = [
            'form_params' => [
                'callback_query_id' => $callbackId,
            ],
        ];
        if ($text) {
            $data['form_params']['text'] = $text;
        }
        $result = $this->execute('answerCallbackQuery', $data);
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

    public function getFile($fileId, &$file, &$fileName): bool
    {
        $data = [
            'form_params' => [
                'file_id' => $fileId,
            ],
        ];
        $fileData = $this->execute('getFile', $data);

        if (!isset($fileData['file_path'])) {
            return false;
        }

        $uri = str_replace(['<token>','<file_path>'], [App::get('telegramToken'), $fileData['file_path']], static::FILE_URL);

        $file = $this->client->request('GET', $uri)->getBody()->getContents();
        $pathInfo = pathinfo($fileData['file_path']);
        /** @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset */
        $fileName = $pathInfo['filename'] . '.' . $pathInfo['extension'];
        return true;
    }

    public function sendChatAction(int $chatId, string $action): bool
    {
        $data = [
            'form_params' => [
                'chat_id' => $chatId,
                'action' => $action,
            ],
        ];
        return (bool) $this->execute('sendChatAction', $data);
    }

    public function replaceMarkupBtn(array &$replyMarkup, string $key, array $newBtn): bool
    {
        foreach ($replyMarkup as &$keyboardsList) {
            foreach ($keyboardsList as $index => $keyboard) {
                if (0 === strpos($keyboard['callback_data'], $key)) {
                    $keyboardsList[$index] = $newBtn;
                    return true;
                }
            }
        }
        unset($keyboardsList);
        return false;
    }

    public function deleteMarkupBtn(array &$replyMarkup, string $key): bool
    {
        foreach ($replyMarkup as &$keyboardsList) {
            foreach ($keyboardsList as $index => $keyboard) {
                if (0 === strpos($keyboard['callback_data'], $key)) {
                    unset($keyboardsList[$index]);
                    return true;
                }
            }
        }
        unset($keyboardsList);
        return false;
    }
}
