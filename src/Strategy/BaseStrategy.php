<?php


namespace M2T\Strategy;


use M2T\AccountManager;
use M2T\Client\MessengerInterface;
use M2T\Client\SmtpClient;
use M2T\Client\TelegramClient;
use M2T\Handler;
use M2T\Model\Account;
use Psr\Log\LoggerInterface;

/**
 *
 * @property MessengerInterface $messenger
 * @property LoggerInterface $logger
 * @property Account $account
 * @property AccountManager $accountManager
 * @property Handler $handler
 * @property int $chatId
 * @property array $incomingData Массив входящих данных, доступных для обработки
 *
 * */
class BaseStrategy implements StrategyInterface
{
    protected int $chatId;
    protected LoggerInterface $logger;
    protected MessengerInterface $messenger;
    protected array $incomingData;
    protected Account $account;
    protected Handler $handler;

    public function __construct(
        array $incomingData,
        LoggerInterface $logger,
        MessengerInterface $messenger,
        Account $account,
        AccountManager $accountManager,
        Handler $handler
    )
    {
        $chatId = &$incomingData['message']['chat']['id'];
        $this->chatId = $chatId;
        $this->logger = $logger;
        $this->messenger = $messenger;
        $this->incomingData = $incomingData;
        $this->account = $account;
        $this->accountManager = $accountManager;
        $this->handler = $handler;
    }

    public function run($action = null)
    {

        if ($action != null && method_exists($this, 'action' . $action)) {
            $action = 'action' . $action;
        } else {
            $action = 'actionIndex';
        }

        $this->logger->debug($action);

        $result = $this->$action();
        $this->handler->trigger($this, $result);

    }

}
