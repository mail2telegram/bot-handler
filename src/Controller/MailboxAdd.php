<?php

/** @noinspection JsonEncodingApiUsageInspection */

namespace M2T\Controller;

use M2T\AccountManager;
use M2T\App;
use M2T\Client\ImapClient;
use M2T\Client\MailConfigClientInterface;
use M2T\Client\MessengerInterface;
use M2T\Client\SmtpClient;
use M2T\Model\Account;
use M2T\Model\Email;
use M2T\State;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MailboxAdd extends Base
{
    protected const MSG_BTN_ACCEPT_AUTOCONFIG = 'Принять автоматические настройки';
    protected const MSG_BTN_DO_NOT_ACCEPT_AUTOCONFIG = 'Внести изменения';

    protected const MSG_INSERT_EMAIL = 'Напишите email:';
    protected const MSG_INSERT_PASSWORD = 'Введите пароль от email:';
    protected const MSG_INCORRECT_EMAIL = 'Вы ввели некорректный email. Напишите email:';
    protected const MSG_EMAIL_ALREADY_EXISTS = 'Данный email уже добавлен. Изменить его настройки?';
    protected const MSG_EMAIL_AUTOCONFIG_GET
        = 'Нам удалось получить настройки автоматически. Вы можете принять их или изменить:';
    protected const MSG_ERROR = 'Произошла ошибка во время регистрации email :-( Попробуйте заново!';
    protected const MSG_ERROR_CHECK_CONNECT = 'Не удалось подключиться к вашему почтовому ящику.'
    . PHP_EOL . 'Проверьте адрес, пароль, настройки и попробуйте еще раз.';
    protected const MSG_CONFIRM_OR_TYPE_NEW = 'Подтвердите значение %value% либо введите свое';
    protected const MSG_COMPLETE
        = 'Спасибо, регистрация email завершена! Сохраненные настройки: ' . PHP_EOL . '%new_values%';
    protected const MSG_YES = 'Да';
    protected const MSG_NO = 'Нет';

    protected const ACTION_CONFIG = 'actionConfig';
    public const ACTION_REQUEST_IMAP_HOST = 'actionRequestImapHost';
    protected const ACTION_REQUEST_IMAP_PORT = 'actionRequestImapPort';
    protected const ACTION_REQUEST_IMAP_SOCKET_TYPE = 'actionRequestImapSocketType';
    protected const ACTION_REQUEST_SMTP_HOST = 'actionRequestSmtpHost';
    protected const ACTION_REQUEST_SMTP_PORT = 'actionRequestSmtpPort';
    protected const ACTION_REQUEST_SMTP_SOCKET_TYPE = 'actionRequestSmtpSocketType';
    protected const ACTION_REQUEST_PWD = 'actionRequestPwd';
    protected const ACTION_COMPLETE = 'actionComplete';

    protected MailConfigClientInterface $mailConfigClient;

    public function __construct(
        State $state,
        MessengerInterface $messenger,
        AccountManager $accountManager,
        MailConfigClientInterface $mailConfigClient
    ) {
        parent::__construct($state, $messenger, $accountManager);
        $this->mailConfigClient = $mailConfigClient;
    }

    public function actionIndex(): void
    {
        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_INSERT_EMAIL,
            json_encode(['force_reply' => true])
        );
        $this->setState(static::ACTION_CONFIG);
    }

    public function actionConfig(array $update): void
    {
        $msg = &$update['message'];
        $emailString = $msg['text'];

        if (!filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                static::MSG_INCORRECT_EMAIL,
                json_encode(['force_reply' => true])
            );
            $this->setState(static::ACTION_CONFIG);
            return;
        }

        if (!$account = $this->accountManager->load($this->state->chatId)) {
            $account = new Account($this->state->chatId);
        }

        // @todo Обработать "Данный email уже добавлен. Изменить его настройки?"
        if ($this->accountManager->mailboxExist($account, $emailString)) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                static::MSG_EMAIL_ALREADY_EXISTS,
                json_encode(
                    [
                        'keyboard' => [[static::MSG_YES], [static::MSG_NO]],
                        'one_time_keyboard' => true,
                    ]
                )
            );
            return;
        }

        $data = $this->mailConfigClient->get(explode('@', $emailString)[1]);
        $mailbox = new Email(
            $emailString,
            '',
            $data['imapHost'] ?? '',
            $data['imapPort'] ?? 0,
            $data['imapSocketType'] ?? '',
            $data['smtpHost'] ?? '',
            $data['smtpPort'] ?? 0,
            $data['smtpSocketType'] ?? ''
        );
        $this->state->mailbox = $mailbox;

        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_EMAIL_AUTOCONFIG_GET . PHP_EOL . $mailbox->getSettings(),
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
        $this->setState(static::ACTION_REQUEST_IMAP_HOST);
    }

    public function actionRequestImapHost(array $update): void
    {
        if ($update['message']['text'] === static::MSG_BTN_ACCEPT_AUTOCONFIG) {
            $this->showPasswordDialog();
            return;
        }
        if ($update['message']['text'] === MailboxEdit::MSG_NO) {
            // @todo сообщение об отмене? (чтобы скрыть кнопки)
            return;
        }
        $this->requestField($update, 'imapHost');
    }

    public function actionRequestImapPort(array $update): void
    {
        $this->state->mailbox->imapHost = $update['message']['text'];
        $this->requestField($update, 'imapPort');
    }

    public function actionRequestImapSocketType(array $update): void
    {
        $this->state->mailbox->imapPort = $update['message']['text'];
        $this->requestField($update, 'imapSocketType');
    }

    public function actionRequestSmtpHost(array $update): void
    {
        $this->state->mailbox->imapSocketType = $update['message']['text'];
        $this->requestField($update, 'smtpHost');
    }

    public function actionRequestSmtpPort(array $update): void
    {
        $this->state->mailbox->smtpHost = $update['message']['text'];
        $this->requestField($update, 'smtpPort');
    }

    public function actionRequestSmtpSocketType(array $update): void
    {
        $this->state->mailbox->smtpPort = $update['message']['text'];
        $this->requestField($update, 'smtpSocketType');
    }

    public function actionRequestPwd(array $update): void
    {
        $this->state->mailbox->smtpSocketType = $update['message']['text'];
        $this->showPasswordDialog();
    }

    protected function showPasswordDialog(): void
    {
        $this->messenger->sendMessage(
            $this->state->chatId,
            static::MSG_INSERT_PASSWORD,
            json_encode(['force_reply' => true])
        );
        $this->setState(static::ACTION_COMPLETE);
    }

    public function actionComplete(array $update): void
    {
        $msg = &$update['message'];
        $password = $msg['text'];

        $mailbox = $this->state->mailbox;
        if (!$mailbox) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                static::MSG_ERROR
            );
            return;
        }
        $mailbox->pwd = $password;

        if (
            !App::get(SmtpClient::class)->check($mailbox)
            || !App::get(ImapClient::class)->check($mailbox)
        ) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                static::MSG_ERROR_CHECK_CONNECT
            );
            return;
        }

        if (!$account = $this->accountManager->load($this->state->chatId)) {
            $account = new Account($this->state->chatId);
        }

        if ($this->accountManager->mailboxExist($account, $mailbox->email)) {
            foreach ($account->emails as &$current) {
                if ($current->email === $mailbox->email) {
                    $current = $mailbox;
                    break;
                }
            }
            unset($current);
        } else {
            $account->emails[] = $mailbox;
        }
        $this->accountManager->save($account);
        $this->state->mailbox = null;

        $this->messenger->deleteMessage($this->state->chatId, $msg['message_id']);
        $this->messenger->sendMessage(
            $this->state->chatId,
            str_replace('%new_values%', $mailbox->getSettings(), static::MSG_COMPLETE)
        );
    }

    protected function requestField(array $update, string $field): void
    {
        $msg = &$update['message']['text'];
        $mailbox = $this->state->mailbox;
        $fields = ['imapHost', 'imapPort', 'imapSocketType', 'smtpHost', 'smtpPort', 'smtpSocketType'];
        if ($mailbox === null || !in_array($field, $fields, true)) {
            $this->messenger->sendMessage(
                $this->state->chatId,
                static::MSG_ERROR
            );
            return;
        }
        $msg = '<b>' . $mailbox->email . ': ' . $field . '</b>' . PHP_EOL;
        $msg .= str_replace('%value%', '<b>' . $mailbox->$field . '</b>', static::MSG_CONFIRM_OR_TYPE_NEW);

        $this->messenger->sendMessage(
            $this->state->chatId,
            $msg,
            json_encode(
                [
                    'keyboard' => [[(string) $mailbox->$field]],
                    'one_time_keyboard' => true,
                ]
            )
        );

        $mapNext = [
            'imapHost' => static::ACTION_REQUEST_IMAP_PORT,
            'imapPort' => static::ACTION_REQUEST_IMAP_SOCKET_TYPE,
            'imapSocketType' => static::ACTION_REQUEST_SMTP_HOST,
            'smtpHost' => static::ACTION_REQUEST_SMTP_PORT,
            'smtpPort' => static::ACTION_REQUEST_SMTP_SOCKET_TYPE,
            'smtpSocketType' => static::ACTION_REQUEST_PWD,
        ];

        $this->setState($mapNext[$field]);
    }
}
