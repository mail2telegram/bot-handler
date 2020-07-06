<?php

namespace M2T;

use M2T\Client\MessengerInterface;
use M2T\Client\TelegramClient;
use M2T\Model\Account;
use M2T\Controller\Delete;
use M2T\Controller\Edit;
use M2T\Controller\Help;
use M2T\Controller\Register;
use M2T\Controller\StrategyInterface;
use Psr\Log\LoggerInterface;

class Handler
{
    protected LoggerInterface $logger;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;

    protected const COMMANDS = [
        'start' => 'Help',
        'help' => 'Help',
        'register' => 'Register',
        'list' => 'List',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'send' => 'Send',
    ];

    public function __construct(
        LoggerInterface $logger,
        TelegramClient $messenger,
        AccountManager $accountManager
    ) {
        $this->logger = $logger;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
    }

    /**
     * @param array $update
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function handle(array $update): void
    {
        if (isset($update['message'])) {
            $chatId = &$update['message']['chat']['id'];
            $messageText = &$update['message']['text'];

            $account = $this->accountManager->load($chatId);
            if (!$account) {
                $account = new Account($chatId);
            }

            $ctrl = $this->recognizeCommand($account, $messageText);
            if (!$ctrl) {
                $ctrl = $account->strategy ?? '';
            }

            // @todo check
            if ($ctrl) {
                $this->accountManager->setAllEmailsNotSelected($account);
            }

            $this->logger->debug('recognizeCommand ' . $messageText);
            $this->logger->debug('recognizeProcess ' . $account->strategy);
            $this->logger->debug('recognizeStep ' . $account->step);

            if ($new = $this->recognizeMessage($account, $messageText)) {
                $ctrl = $new;
            }

            $ctrl = $ctrl
                ? "M2T\\Controller\\{$ctrl}"
                : Help::class;

            $action = 'actionIndex';
            if ($account->step && method_exists($ctrl, "action{$account->step}")) {
                $action = "action{$account->step}";
            }

            $this->logger->debug("Action: {$ctrl}::{$action}");
            $this->logger->debug('$account->strategy: ' . $account->strategy);
            $this->logger->debug('$account->step: ' . $account->step);

            $ctrl = new $ctrl(
                $this->logger,
                $this->messenger,
                $this->accountManager,
                $account,
            );
            $result = $ctrl->$action($update);
            $this->trigger($account, $result);
        }
    }

    protected function recognizeCommand(Account $account, string $messageText): string
    {
        $command = ltrim($messageText, '/');
        if (!isset(STATIC::COMMANDS[$command])) {
            return '';
        }
        $ctrl = STATIC::COMMANDS[$command];
        $account->step = null;
        $account->strategy = $ctrl;
        return $ctrl;
    }

    public function recognizeMessage(Account $account, string $messageText): string
    {
        if ($account->strategy === 'Register') {
            if ($account->step === 'SetImapHost' && $messageText === Register::MSG_BTN_ACCEPT_AUTOCONFIG) {
                $account->step = 'AddPassword';
            }
            if ($account->step === 'SetImapHost' && $messageText === Edit::MSG_NO) {
                $account->step = null;
                $account->strategy = 'Help';
                return '';
            }
        }

        // @todo Обработать "Данный email уже добавлен. Изменить его настройки?" register:emailAlreadyExists
        if (
            $account->strategy === 'Delete'
            && $account->step === 'Delete'
            && $messageText === Delete::MSG_BTN_NOT_CONFIRMED
        ) {
            $account->step = 'Canceled';
        }

        return '';
    }

    public function trigger(Account $account, ?string $event = null): void
    {
        $this->logger->debug('trigger event: ' . $event);

        switch ($event) {
            default:
                $account->strategy = 'Help';
                $account->step = null;
                break;
            case 'register:emailIsNotCorrect':
            case 'register:emailInserted':
                $account->step = 'TakeAutoconfig';
                break;
            case 'register:autoconfigDetected':
            case 'edit:runEdit':
                $account->strategy = 'Register';
                $account->step = 'SetImapHost';
                break;
            case 'register:imapHostSuccess':
                $account->step = 'SetImapPort';
                break;
            case 'register:imapPortSuccess':
                $account->step = 'SetImapSocketType';
                break;
            case 'register:imapSocketTypeSuccess':
                $account->step = 'SetSmtpHost';
                break;
            case 'register:smtpHostSuccess':
                $account->step = 'SetSmtpPort';
                break;
            case 'register:smtpPortSuccess':
                $account->step = 'SetSmtpSocketType';
                break;
            case 'register:smtpSocketTypeSuccess':
                $account->step = 'SmtpSocketInserted';
                break;
            case 'register:passwordAdded':
                $account->step = 'RegisterComplete';
                break;
            case 'delete:emailChoosed':
                $account->step = 'CheckAndConfirm';
                break;
            case 'delete:confirmationRequested':
                $account->step = 'Delete';
                break;
            case 'edit:emailChoosed':
                $account->step = 'ShowCurrentSettings';
                break;
            case 'send:emailChoosed':
                $account->step = 'InsertTitle';
                break;
            case 'send:titleInserted':
                $account->step = 'InsertTo';
                break;
            case 'send:toInserted':
                $account->step = 'InsertMessage';
                break;
            case 'send:messageInserted':
                $account->step = 'Send';
                break;
        }

        $this->logger->debug('$account->strategy: ' . $account->strategy);
        $this->logger->debug('$account->step: ' . $account->step);

        $this->accountManager->save($account);
    }
}
