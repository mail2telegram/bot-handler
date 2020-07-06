<?php

namespace M2T;

use M2T\Model\Account;
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
    protected AccountManager $accountManager;

    public function __construct(
        LoggerInterface $logger,
        AccountManager $accountManager
    ) {
        $this->logger = $logger;
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

            $isBotCommand = isset($update['message']['entities'][0]['type'])
                && $update['message']['entities'][0]['type'] === 'bot_command';

            if (!$account = $this->accountManager->load($chatId)) {
                $account = new Account($chatId);
            }

            if ($isBotCommand) {
                $handler = STATIC::COMMANDS[$messageText] ?? Controller\Help::class;
                $account->strategy = $handler;
                $account->step = '';
            } else {
                $handler = $account->strategy ?? Controller\Help::class;
                if (!class_exists($handler)) {
                    $handler = Controller\Help::class;
                }
            }

            $action = 'actionIndex';
            if ($account->step && method_exists($handler, "action{$account->step}")) {
                $action = "action{$account->step}";
            }

            $result = App::build($handler, ['chatId' => $chatId, 'account' => $account])->$action($update);
            $this->trigger($account, $result);
        }
    }

    public function trigger(Account $account, ?string $event = null): void
    {
        $this->logger->debug('trigger event: ' . $event);
        switch ($event) {
            default:
                $account->strategy = null;
                $account->step = null;
                break;
            case 'register:emailIsNotCorrect':
            case 'register:emailInserted':
                $account->step = 'TakeAutoconfig';
                break;
            case 'register:autoconfigDetected':
            case 'edit:runEdit':
                $account->strategy = Controller\MailboxAdd::class;
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
        $this->accountManager->save($account);
    }
}
