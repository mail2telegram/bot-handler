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
            $chatId = &$update['message']['chat']['id'];
            $messageText = &$update['message']['text'];
            $messageText = trim($messageText);

            $isBotCommand = isset($update['message']['entities'][0]['type'])
                && $update['message']['entities'][0]['type'] === 'bot_command';

            $state = $this->stateManager->get($chatId);

            if ($isBotCommand) {
                $handler = static::COMMANDS[$messageText] ?? Controller\Help::class;
                $state->handler = $handler;
                $state->action = '';
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

            App::build($handler, ['state' => $state])->$action($update);
            if (!$state->changed) {
                $state->changed = false;
                $state->reset();
            }
            $this->stateManager->save($state);
        }
    }
}
