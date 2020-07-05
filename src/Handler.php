<?php

namespace M2T;


use M2T\Client\MessengerInterface;
use M2T\Client\TelegramClient;
use M2T\Model\Account;
use M2T\Strategy\DeleteStrategy;
use M2T\Strategy\EditStrategy;
use M2T\Strategy\RegisterStrategy;
use M2T\Strategy\StrategyInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Redis;

/**
 * @property array $strategies Массив доступных процессов, где ключ- название класса (процесса), а значение- массив доступных
 * команд, которыми данный процесс можно вызвать
 *
 * */
class Handler
{
    protected LoggerInterface $logger;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;
    protected Account $account;
    protected Redis $redis;

    private static array $strategies = [
        'Help' => ['start', 'help'],
        'Register',
        'List',
        'Edit',
        'Delete',
        'Send',
    ];

    public function __construct(LoggerInterface $logger, TelegramClient $messenger, Redis $redis, AccountManager $accountManager)
    {
        $this->logger = $logger;
        $this->messenger = $messenger;
        $this->redis = $redis;
        $this->accountManager = $accountManager;
    }

    /**
     * @param array $update
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function handle(array $update): void
    {
        // @todo handle updates here

        if (isset($update['message'])) {
            $chatId = &$update['message']['chat']['id'];
            $this->account = $this->accountManager->get($chatId);

            $strategyClass = $this->recognizeBaseCommand($update);

            if($strategyClass != null){
                $this->accountManager->setAllEmailsNotSelected($this->account);
            }

            if ($strategyClass == null && $this->account->strategy != null) {
                $strategyClass = "M2T\\Strategy\\" . $this->account->strategy . "Strategy";
            }elseif ($strategyClass == null){
                $strategyClass = "M2T\\Strategy\\HelpStrategy";
            }

            $strategyNewClass = $this->recognizeCommand($update);
            if ($strategyNewClass != null) {
                $strategyClass = $strategyNewClass;
            }


            $this->logger->debug('$update: ' . print_r($update, true));
            $this->logger->debug('$strategyClass: ' . $strategyClass);
            $this->logger->debug('$this->account->strategy: ' . $this->account->strategy);
            $this->logger->debug('$this->account->step: ' . $this->account->step);

            $strategy = new $strategyClass($update, $this->logger, $this->messenger, $this->account, $this->accountManager, $this);
            $strategy->run($this->account->step);


        }
    }

    protected function recognizeBaseCommand($update): ?string
    {
        $messageText = &$update['message']['text'];

        $command = str_replace('/', '', $messageText);
        foreach (static::$strategies as $strategyTitle => $selector) {
            if (is_string($selector) && mb_strtolower($selector) == $command) {
                $baseCommandClassName = $selector;
                break;
            } elseif (is_array($selector)) {
                foreach ($selector as $item) {
                    if (mb_strtolower($item) == $command) {
                        $baseCommandClassName = $strategyTitle;
                        break;
                    }
                }
            }
        }
        if (isset($baseCommandClassName)) {
            $this->account->step = null;
            $this->account->strategy = $baseCommandClassName;
            return "M2T\\Strategy\\" . $baseCommandClassName . "Strategy";
        }
        return null;
    }

    public function recognizeCommand($update): ?string
    {
        $messageText = &$update['message']['text'];

        $this->logger->debug('recognizeCommand ' . $messageText);
        $this->logger->debug('recognizeProcess ' . $this->account->strategy);
        $this->logger->debug('recognizeStep ' . $this->account->step);

        if ($this->account->strategy == 'Register') {
            if ($this->account->step == 'SetImapHost' && $messageText == RegisterStrategy::MSG_BTN_ACCEPT_AUTOCONFIG) {
                $this->account->step = 'AddPassword';
            }

            if ($this->account->step == 'SetImapHost' && $messageText == EditStrategy::MSG_NO) {
                $this->account->step = null;
                $this->account->strategy = 'Help';
                return "M2T\\Strategy\\HelpStrategy";
            }
        }

        //@todo Обработать "Данный email уже добавлен. Изменить его настройки?" register:emailAlreadyExists

        if ($this->account->strategy == 'Delete' && $this->account->step == 'Delete' && $messageText == DeleteStrategy::MSG_BTN_NOT_CONFIRMED) {
            $this->account->step = 'Canceled';
        }

        return null;
    }

    public function trigger(StrategyInterface $strategy, ?string $event = null)
    {
        $this->logger->debug('trigger event: ' . $event);

        switch ($event) {
            default:
                $this->account->strategy = 'Help';
                $this->account->step = null;
                break;

            case 'register:emailIsNotCorrect':
            case 'register:emailInserted':
                $this->account->step = 'TakeAutoconfig';
                break;
            case 'register:autoconfigDetected':
            case 'edit:runEdit':
                $this->account->strategy = 'Register';
                $this->account->step = 'SetImapHost';
                break;
            case 'register:imapHostSuccess':
                $this->account->step = 'SetImapPort';
                break;
            case 'register:imapPortSuccess':
                $this->account->step = 'SetImapSocketType';
                break;
            case 'register:imapSocketTypeSuccess':
                $this->account->step = 'SetSmtpHost';
                break;
            case 'register:smtpHostSuccess':
                $this->account->step = 'SetSmtpPort';
                break;
            case 'register:smtpPortSuccess':
                $this->account->step = 'SetSmtpSocketType';
                break;
            case 'register:smtpSocketTypeSuccess':
                $this->account->step = 'SmtpSocketInserted';
                break;
            case 'register:passwordAdded':
                $this->account->step = 'RegisterComplete';
                break;
            case 'delete:emailChoosed':
                $this->account->step = 'CheckAndConfirm';
                break;
            case 'delete:confirmationRequested':
                $this->account->step = 'Delete';
                break;
            case 'edit:emailChoosed':
                $this->account->step = 'ShowCurrentSettings';
                break;
            case 'send:emailChoosed':
                $this->account->step = 'InsertTitle';
                break;
            case 'send:titleInserted':
                $this->account->step = 'InsertTo';
                break;
            case 'send:toInserted':
                $this->account->step = 'InsertMessage';
                break;
            case 'send:messageInserted':
                $this->account->step = 'Send';
                break;
        }

        $this->logger->debug('$this->account->strategy: ' . $this->account->strategy);
        $this->logger->debug('$this->account->step: ' . $this->account->step);

        $this->accountManager->save($this->account);
    }
}
