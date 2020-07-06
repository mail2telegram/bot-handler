<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\Client\MailConfigClientInterface;
use M2T\Client\MessengerInterface;
use M2T\Model\Account;
use M2T\Model\Email;

// @todo Обработать "Данный email уже добавлен. Изменить его настройки?" register:emailAlreadyExists

class MailboxAdd
{
    public const MSG_BTN_ACCEPT_AUTOCONFIG = 'Принять автоматические настройки';
    protected const MSG_BTN_DO_NOT_ACCEPT_AUTOCONFIG = 'Внести изменения';

    protected const MSG_INSERT_EMAIL = 'Напишите email:';
    protected const MSG_INSERT_PASSWORD = 'Введите пароль от email:';
    protected const MSG_INCORRECT_EMAIL = 'Вы ввели некорректный email. Напишите email:';
    protected const MSG_EMAIL_ALREADY_EXISTS = 'Данный email уже добавлен. Изменить его настройки?';
    protected const MSG_EMAIL_AUTOCONFIG_GET = 'Нам удалось получить настройки автоматически. Вы можете принять их или изменить:';
    protected const MSG_ERROR = 'Произошла ошибка во время регистрации email :-( Попробуйте заново!';
    protected const MSG_CONFIRM_OR_TYPE_NEW = 'Подтвердите значение %value% либо введите свое';
    protected const MSG_REGISTRATION_COMPLETE = 'Спасибо, регистрация email завершена! Сохраненные настройки: ' . PHP_EOL . '%new_values%';
    protected const MSG_YES = 'Да';
    protected const MSG_NO = 'Нет';

    protected int $chatId;
    protected MessengerInterface $messenger;
    protected AccountManager $accountManager;
    protected MailConfigClientInterface $mailConfigClient;
    protected Account $account;

    public function __construct(
        int $chatId,
        MessengerInterface $messenger,
        AccountManager $accountManager,
        MailConfigClientInterface $mailConfigClient,
        Account $account
    ) {
        $this->chatId = $chatId;
        $this->messenger = $messenger;
        $this->accountManager = $accountManager;
        $this->mailConfigClient = $mailConfigClient;
        $this->account = $account;
    }

    public function actionIndex(): string
    {
        $this->messenger->sendMessage(
            $this->chatId,
            static::MSG_INSERT_EMAIL,
            json_encode(['force_reply' => true])
        );
        return 'register:emailInserted';
    }

    public function actionTakeAutoconfig(array $update): string
    {
        $msg = &$update['message'];
        $emailString = trim($msg['text']);

        if (!filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                static::MSG_INCORRECT_EMAIL,
                json_encode(['force_reply' => true])
            );
            return 'register:emailIsNotCorrect';
        }

        if ($this->accountManager->checkExistEmail($this->account, $emailString)) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                static::MSG_EMAIL_ALREADY_EXISTS,
                json_encode(
                    [
                        'keyboard' => [[static::MSG_YES], [static::MSG_NO]],
                        'one_time_keyboard' => true,
                    ]
                )
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
            $this->account->chatId,
            $response_msg . PHP_EOL . $email->getSettings(),
            json_encode(
                [
                    'keyboard' => [
                        [static::MSG_BTN_ACCEPT_AUTOCONFIG],
                        [static::MSG_BTN_DO_NOT_ACCEPT_AUTOCONFIG],
                    ],
                    'one_time_keyboard' => true,
                ]
            )
        );
        return 'register:autoconfigDetected';
    }

    public function actionSetImapHost(array $update): string
    {
        if ($update['message']['text'] === static::MSG_BTN_ACCEPT_AUTOCONFIG) {
            return $this->actionAddPassword();
        }
        if ($update['message']['text'] === MailboxEdit::MSG_NO) {
            return '';
        }
        return $this->editField($update, 'imapHost');
    }

    public function actionSetImapPort(array $update): string
    {
        return $this->editField($update, 'imapPort', 'imapHost');
    }

    public function actionSetImapSocketType(array $update): string
    {
        return $this->editField($update, 'imapSocketType', 'imapPort');
    }

    public function actionSetSmtpHost(array $update): string
    {
        return $this->editField($update, 'smtpHost', 'imapSocketType');
    }

    public function actionSetSmtpPort(array $update): string
    {
        return $this->editField($update, 'smtpPort', 'smtpHost');
    }

    public function actionSetSmtpSocketType(array $update): string
    {
        return $this->editField($update, 'smtpSocketType', 'smtpPort');
    }

    public function actionSmtpSocketInserted(): string
    {
        $this->editField(null, 'smtpSocketType');
        return $this->showPasswordDialog();
    }

    public function actionAddPassword(): string
    {
        return $this->showPasswordDialog();
    }

    protected function showPasswordDialog(): string
    {
        $this->messenger->sendMessage(
            $this->account->chatId,
            static::MSG_INSERT_PASSWORD,
            json_encode(['force_reply' => true])
        );
        return 'register:passwordAdded';
    }

    public function actionRegisterComplete(array $update): string
    {
        $msg = &$update['message'];

        $password = $msg['text'];

        $email = $this->accountManager->getSelectedEmail($this->account);

        if ($email === null) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                static::MSG_ERROR
            );
            return 'register:selectedNotFound';
        }

        $email->pwd = $password;
        $email->selected = false;

        $this->messenger->deleteMessage($this->account->chatId, $msg['message_id']);

        $this->messenger->sendMessage(
            $this->account->chatId,
            str_replace('%new_values%', $email->getSettings(), static::MSG_REGISTRATION_COMPLETE)
        );
        return 'register:complete';
    }

    /**
     * Редактирует указанное поле и отправляет сообщение с просьбой ввести значение для следующего поля
     * @param array       $update
     * @param null|string $field Поле, значение которого попросить указать
     * @param null|string $updateField Поле, которое требуется обновить
     * @return string
     */
    protected function editField(array $update, ?string $field, ?string $updateField = null): string
    {
        $msg = &$update['message']['text'];

        $email = $this->accountManager->getSelectedEmail($this->account);
        $fields = [null, 'imapHost', 'imapPort', 'imapSocketType', 'smtpHost', 'smtpPort', 'smtpSocketType'];
        if ($email === null || !in_array($field, $fields, true)) {
            $this->messenger->sendMessage(
                $this->account->chatId,
                static::MSG_ERROR
            );
            return 'register:selectedNotFound';
        }

        if ($updateField !== null) {
            $email->$updateField = $msg;
        }

        if ($field !== null) {
            $msg = '<b>' . $email->email . ': ' . $field . '</b>' . PHP_EOL;
            $msg .= str_replace('%value%', '<b>' . $email->$field . '</b>', static::MSG_CONFIRM_OR_TYPE_NEW);

            $this->messenger->sendMessage(
                $this->account->chatId,
                $msg,
                json_encode(
                    [
                        'keyboard' => [[(string) $email->$field]],
                        'one_time_keyboard' => true,
                    ]
                )
            );

            return 'register:' . $field . 'Success';
        }

        return 'register:editFieldSuccess';
    }
}
