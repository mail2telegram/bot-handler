<?php

namespace M2T;

use Psr\Log\LoggerInterface;

class Handler
{
    protected const COMMANDS = [
        '/start' => Controller\Help::class,
        '/help' => Controller\Help::class,
        '/register' => Controller\MailboxAdd::class,
        '/list' => Controller\MailboxList::class,
        '/edit' => Controller\MailboxEdit::class,
        '/delete' => Controller\MailboxDelete::class,
        '/send' => Controller\MailSend::class,
    ];

    protected const CALLBACKS = [
        Action\MailDelete::NAME => Action\MailDelete::class,
        Action\MailSpam::NAME => Action\MailSpam::class,
        Action\MailSeen::NAME => Action\MailSeen::class,
        Action\MailUnseen::NAME => Action\MailUnseen::class,
    ];

    protected LoggerInterface $logger;
    protected StateManager $stateManager;

    public function __construct(
        LoggerInterface $logger,
        StateManager $stateManager
    ) {
        $this->logger = $logger;
        $this->stateManager = $stateManager;
    }

    public function handle(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update);
            return;
        }
        if (
            isset($update['callback_query']['data'], $update['callback_query']['message']['from']['is_bot'])
            && $update['callback_query']['message']['from']['is_bot'] === true
        ) {
            $this->handleCallback($update);
            return;
        }
    }

    public function handleMessage(array $update): void
    {
        $chatId = &$update['message']['chat']['id'];
        $messageText = &$update['message']['text'];
        $messageText = trim($messageText);

        $isGroupChat = isset($update['message']['chat']['type'])
            && $update['message']['chat']['type'] === 'group';

        $isBotCommand = isset($update['message']['entities'][0]['type'])
            && $update['message']['entities'][0]['type'] === 'bot_command';

        $state = $this->stateManager->get($chatId);

        if ($isBotCommand) {
            $botLink = '@' . App::get('botName');
            if ($isGroupChat && !strpos($messageText, $botLink)) {
                return;
            }
            $command = str_replace($botLink, '', $messageText);
            $handler = static::COMMANDS[$command] ?? Controller\Help::class;
            $state->handler = $handler;
            $state->action = '';
        } elseif (
            isset($update['message']['reply_to_message']['text'])
            && $update['message']['reply_to_message']['from']['is_bot'] === true
            && preg_match('/^To: <(.+)>/m', $update['message']['reply_to_message']['text'])
        ) {
            $handler = Controller\Reply::class;
        } else {
            $handler = $state->handler ?: Controller\Help::class;
            if (!class_exists($handler)) {
                $handler = Controller\Help::class;
            }
        }

        $action = 'actionIndex';
        if ($state->action && method_exists($handler, $state->action)) {
            $action = $state->action;
        }

        $this->logger->debug("Handler: $handler::$action");

        App::build($handler, ['state' => $state])->$action($update);
        if (!$state->changed) {
            $state->reset();
        }
        $state->changed = false;
        $this->stateManager->save($state);

        $this->logger->debug('State: ', (array) $state);
    }

    public function handleCallback(array $update): void
    {
        $matches = [];
        if (
            preg_match('/^To: <(.+)>/m', $update['callback_query']['message']['text'], $matches)
            && !empty($matches[1])
        ) {
            $this->handleCallbackMail($update['callback_query'], $matches[1]);
        }
    }

    public function handleCallbackMail(array $callback, string $email): void
    {
        [$action, $mailId] = explode(':', $callback['data']);
        if (!isset(static::CALLBACKS[$action])) {
            return;
        }
        App::build(static::CALLBACKS[$action])($callback, $email, $mailId);
    }
}
