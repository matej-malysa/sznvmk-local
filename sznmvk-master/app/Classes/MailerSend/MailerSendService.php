<?php
declare(strict_types=1);

namespace App\Classes\MailerSend;

use App\Model\ImportantDatesModel;
use App\Model\PaymentsLimitsModel;
use JsonException;
use MailerSend\Exceptions\MailerSendAssertException;
use MailerSend\Exceptions\MailerSendException;
use MailerSend\Helpers\Builder\Variable;
use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;
use Nette\Utils\ArrayHash;
use Psr\Http\Client\ClientExceptionInterface;

class MailerSendService
{
    /** @var MailerSend */
    public MailerSend $mailerSend;

    /**
     * MailerSendService constructor.
     * @throws MailerSendException
     */
    public function __construct()
    {
        $this->mailerSend = new MailerSend(['api_key' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiOTAwNTdjMDIzZmU5M2Y4YzM3OWIwYWI2OGQ3NmJmZmYyMWM5MmI2ZmE4YzBjMzMxOTYzMDMxMTY2ODQyZjYzYTk5MGNiYmZhYWM3MWYxNGIiLCJpYXQiOjE2NTQwMTEyMTcuNzM5ODA5LCJuYmYiOjE2NTQwMTEyMTcuNzM5ODEyLCJleHAiOjQ4MDk2ODQ4MTcuNzI1NzIyLCJzdWIiOiIyMzM0NSIsInNjb3BlcyI6WyJlbWFpbF9mdWxsIiwiZG9tYWluc19mdWxsIiwiYWN0aXZpdHlfZnVsbCIsImFuYWx5dGljc19mdWxsIiwidG9rZW5zX2Z1bGwiLCJ3ZWJob29rc19mdWxsIiwidGVtcGxhdGVzX2Z1bGwiLCJzdXBwcmVzc2lvbnNfZnVsbCIsInNtc19mdWxsIl19.JK-Ote9ns_IvP0DghdlEuvznu6Kryru3ulAdosnfvMV3KsWnVuVjWOhhTAPtq_7oyJq3-HDJd9jCrb-EroyYeUvqAszvxdBqYZZDXJq9owj_nudYjx21FR7soNj_ktV3sCC1Rtuxic0mQvMCqFSU09ecgjSEluDsDddFTSqb96z5cKtQnqXqtS_rqBjj3qvQGNT9M61Pi_ZMGchSEZohXFk9gYxmmPvGg6DEWMDwHS1oB9eZ6xOFXYaeuGwORC4jLHbjpOReSJm8wkzeIa2E-R94iTKM2OidsfW_5x8WjKG29x3IANSsXot7pSSx1n-pYmu8HFaGziDkAZ4gK4sWuf0vs9JUdrWnWhjeR48o4gmQoz5NkTPHGnpj4Zgw2XIDqBiqfVdy8XZ2brBBTRdxD-J39d9UfzbtIwhX5uJdFgrucljpVYEzZEARTsEhMq_DPDqlD2TZk6zWUck2O5eUKvBwJlTPjSLUNgksMMtKT_4tMCc8oQdbv9XU4x7p7_c9FhFrBV2WqmDiwlVWcSS2iF1pX6OxVWPefksqbNvCxOSHvR3ZjdQBR0vVXnSYV7XrfPusQ9rEGbzk6sVneA9RNH5A4VWlDSI0SQKak5q9zjOyMCaUp2ijP4xQI7fJPF5-DMsF323vKKMyxL_1KBQUh7AQ4wiKFQNaYCHynWDY5U8']);
    }

    /**
     * @param array $variables
     * @param array $recipients
     * @param string $subject
     * @param string $templateId
     * @throws MailerSendAssertException
     * @throws JsonException
     * @throws ClientExceptionInterface
     */
    public function send(array $variables, array $recipients, string $subject, string $templateId)
    {
        $emailParams = (new EmailParams())
            ->setFrom('jsme@seznamovak.org')
            ->setFromName('Seznamovák Brno')
            ->setReplyTo('jsme@seznamovak.org')
            ->setReplyToName('Seznamovák Brno')
            ->setSubject($subject)
            ->setRecipients($recipients)
            ->setTemplateId($templateId)
            ->setVariables($variables);

        $this->mailerSend->email->send($emailParams);
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function forgottenPassword(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'nove_heslo' => $values->password
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Heslo změněno', 'neqvygm0eodl0p7w');
    }

    /**
     * @param ArrayHash $values
     * @param array $generalInfo
     * @throws MailerSendAssertException
     */
    public function createApplication(ArrayHash $values, array $generalInfo)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'email' => $values->email,
                'password' => $values->password,
                'tel_cislo' => $values->phone,
                'zaloha_eur' => strval($generalInfo['prices'][PaymentsLimitsModel::ZALOHA_EUR_ID]['amount']),
                'druhyturnus' => $generalInfo['sessions'][2]['date'],
                'prvniturnus' => $generalInfo['sessions'][1]['date'],
                'doplatek_eur' => strval($generalInfo['prices'][PaymentsLimitsModel::FULL_PRICE_EUR_ID]['amount'] - $generalInfo['prices'][PaymentsLimitsModel::ZALOHA_EUR_ID]['amount']),
                'zaloha_datum' => date_format($generalInfo['dates'][ImportantDatesModel::ZALOHA_1]['deadline'], 'd.m.Y'),
                'zaloha_kolik' => strval($generalInfo['prices'][PaymentsLimitsModel::ZALOHA_ID]['amount']),
                'doplatek_datum' => date_format($generalInfo['dates'][ImportantDatesModel::DOPLATEK]['deadline'], 'd.m.Y'),
                'doplatek_kolik' => strval($generalInfo['prices'][PaymentsLimitsModel::FULL_PRICE_ID]['amount'] - $generalInfo['prices'][PaymentsLimitsModel::ZALOHA_ID]['amount']),
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Přihláška na Seznamovák 2022!', 'z3m5jgromnzgdpyo');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function paymentReceived(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'platba_kolik' => strval($values->platba_kolik),
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Tvá platba byla v pořádku zaevidována.', '0r83ql3poxvgzw1j');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function paymentReceivedFull(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'platba_kolik' => strval($values->platba_kolik),
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Všechny platby máš úspěšně za sebou.', 'k68zxl271z34j905');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function placeConfirmed(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Úspěšně jsme ti přiřadili místo na Seznamováku', 'pr9084zvj3egw63d');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function placeFullEnqueued(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Kapacita tebou zvoleného turnusu je bohužel vyčerpána', 'ynrw7gymedng2k8e');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function placeFullWaitingForAction(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Stále nemáš vybraný turnus!', '3zxk54v8yk1ljy6v');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function stornoFull(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'datum_refundace' => $values->refundDate,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Tvé přihlášení bylo stornováno', 'z3m5jgroydxgdpyo');
    }

    public function stornoDoplatek(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'datum_refundace' => $values->refundDate,
                'deadline_zaloha' => $values->deadlineZaloha,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Tvé přihlášení bylo stornováno', '351ndgwvy9nlzqx8');
    }

    /**
     * @param ArrayHash $values
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function stornoAfterDeadline(ArrayHash $values)
    {
        $variables = [
            new Variable($values->email, [
                'name' => $values->firstname,
                'deadline_doplatek' => $values->deadlineDoplatek,
            ])
        ];

        $recipients = [
            new Recipient($values->email, $values->firstname),
        ];

        $this->send($variables, $recipients, 'Tvé přihlášení bylo stornováno', 'pq3enl6k0r742vwr');
    }
}