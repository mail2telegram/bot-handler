<?php


namespace M2T\Strategy;


use M2T\AccountManager;
use M2T\Client\MailConfigClient;
use M2T\Client\MailConfigClientInterface;
use M2T\Client\MessengerInterface;
use M2T\Handler;
use M2T\Model\Email;
use Psr\Log\LoggerInterface;
use M2T\Model\Account;

/**
 *
 * */
class RegisterStrategy extends BaseStrategy implements StrategyInterface
{
    protected MailConfigClientInterface $mailConfigClient;

    public const MSG_BTN_ACCEPT_AUTOCONFIG = 'Принять автоматические настройки';
    public const MSG_BTN_DO_NOT_ACCEPT_AUTOCONFIG = 'Внести изменения';

    public const MSG_INSERT_EMAIL = 'Напишите email:';
    public const MSG_INSERT_PASSWORD = 'Введите пароль от email:';
    public const MSG_INCORRECT_EMAIL = 'Вы ввели некорректный email. Напишите email:';
    public const MSG_EMAIL_ALREADY_EXISTS = 'Данный email уже добавлен. Изменить его настройки?';
    public const MSG_EMAIL_AUTOCONFIG_GET = 'Нам удалось получить настройки автоматически. Вы можете принять их или изменить:';
    public const MSG_ERROR = 'Произошла ошибка во время регистрации email :-( Попробуйте заново!';
    public const MSG_CONFIRM_OR_TYPE_NEW = 'Подтвердите значение %value% либо введите свое';
    public const MSG_REGISTRATION_COMPLETE = 'Спасибо, регистрация email завершена! Сохраненные настройки: ' . PHP_EOL . '%new_values%';
    public const MSG_YES = 'Да';
    public const MSG_NO = 'Нет';

    public function __construct(
        array $incomingData,
        LoggerInterface $logger,
        MessengerInterface $messenger,
        Account $account,
        AccountManager $accountManager,
        Handler $handler
    )
    {
        parent::__construct($incomingData, $logger, $messenger, $account, $accountManager, $handler);
        $this->mailConfigClient = new MailConfigClient($this->logger);
    }

    protected function actionIndex(): string
    {
        $msg = &$this->incomingData['message'];

        $this->logger->debug('$msg: ' . print_r($msg, true));

        $this->messenger->sendMessage(
            $this->chatId,
            static::MSG_INSERT_EMAIL,
            json_encode(['force_reply' => true])
        );

        return 'register:emailInserted';
    }

    protected function actionTakeAutoconfig(): string
    {
        $msg = &$this->incomingData['message'];

        $emailString = trim($msg['text']);

        if (!filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            $this->messenger->sendMessage($this->chatId,
                static::MSG_INCORRECT_EMAIL,
                json_encode(['force_reply' => true])
            );
            return 'register:emailIsNotCorrect';
        }

        if ($this->accountManager->checkExistEmail($this->account, $emailString)) {
            $this->messenger->sendMessage($this->chatId,
                static::MSG_EMAIL_ALREADY_EXISTS,
                json_encode(['keyboard' => [[static::MSG_YES], [static::MSG_NO],],
                    'one_time_keyboard' => true
                ])
            );
            return 'register:emailAlreadyExists';
        }

        $data = $this->mailConfigClient->get($emailString);

        $response_msg = static::MSG_EMAIL_AUTOCONFIG_GET;

        $email = new Email(
            $emailString, '',
            $data['imapHost'] ?? '',
            $data['imapPort'] ?? 0,
            $data['imapSocketType'] ?? '',
            $data['smtpHost'] ?? '',
            $data['smtpPort'] ?? 0,
            $data['smtpSocketType'] ?? '',
            true
        );
        $this->account->emails[] = $email;

        $this->messenger->sendMessage(
            $this->chatId, $response_msg . PHP_EOL . $email->getSettings(),
            json_encode([
                'keyboard' => [
                    [static::MSG_BTN_ACCEPT_AUTOCONFIG],
                    [static::MSG_BTN_DO_NOT_ACCEPT_AUTOCONFIG],
                ],
                'one_time_keyboard' => true
            ])
        );
        return 'register:autoconfigDetected';
    }

    protected function actionSetImapHost(): string
    {
        return $this->editField('imapHost');
    }

    protected function actionSetImapPort(): string
    {
        return $this->editField('imapPort', 'imapHost');
    }

    protected function actionSetImapSocketType(): string
    {
        return $this->editField('imapSocketType', 'imapPort');
    }

    protected function actionSetSmtpHost(): string
    {
        return $this->editField('smtpHost', 'imapSocketType');
    }

    protected function actionSetSmtpPort(): string
    {
        return $this->editField('smtpPort', 'smtpHost');
    }

    protected function actionSetSmtpSocketType(): string
    {
        return $this->editField('smtpSocketType', 'smtpPort');
    }

    protected function actionSmtpSocketInserted(): string
    {
        $this->editField(null,'smtpSocketType');
        return $this->showPasswordDialog();
    }

    protected function actionAddPassword(): string
    {
        return $this->showPasswordDialog();
    }

    protected function showPasswordDialog(): string
    {
        $this->messenger->sendMessage($this->chatId,
            static::MSG_INSERT_PASSWORD,
            json_encode(['force_reply' => true])
        );
        return 'register:passwordAdded';
    }

    protected function actionRegisterComplete(): string
    {
        $msg = &$this->incomingData['message'];

        $password = $msg['text'];

        $email = $this->accountManager->getSelectedEmail($this->account);

        if ($email == null) {
            $this->messenger->sendMessage($this->chatId,
                static::MSG_ERROR
            );
            return 'register:selectedNotFound';
        }

        $email->pwd = $password;
        $email->selected = false;

        $this->messenger->deleteMessage($this->chatId, $msg['message_id']);

        $this->messenger->sendMessage($this->chatId,
            str_replace('%new_values%', $email->getSettings(), static::MSG_REGISTRATION_COMPLETE)
        );
        return 'register:complete';
    }


    /**
     * Редактирует указанное поле и отправляет сообщение с просьбой ввести значение для следующего поля
     * @param null|string $field Поле, значение которого попросить указать
     * @param null|string $updateField Поле, которое требуется обновить
     * @return string
     */
    protected function editField(?string $field, ?string $updateField = null): string
    {
        $msg = &$this->incomingData['message']['text'];


        $email = $this->accountManager->getSelectedEmail($this->account);
        if ($email == null || !in_array($field, [null, 'imapHost', 'imapPort', 'imapSocketType', 'smtpHost', 'smtpPort', 'smtpSocketType'])) {
            $this->messenger->sendMessage($this->chatId,
                static::MSG_ERROR
            );
            return 'register:selectedNotFound';
        }

        if ($updateField != null) {
            $email->$updateField = $msg;
        }

        if($field != null){
            $msg = '<b>' . $email->email . ': ' . $field . '</b>' . PHP_EOL;
            $msg .= str_replace('%value%', '<b>' . (string)$email->$field . '</b>', static::MSG_CONFIRM_OR_TYPE_NEW);

            $this->messenger->sendMessage($this->chatId,
                $msg,
                json_encode(['keyboard' => [[(string)$email->$field]],
                    'one_time_keyboard' => true
                ])
            );

            return 'register:' . $field . 'Success';
        }

        return 'register:editFieldSuccess';
    }

}
