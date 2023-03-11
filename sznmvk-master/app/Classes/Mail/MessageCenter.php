<?php
declare(strict_types=1);

namespace App\Classes\Mail;

use Nette\Http\Request;
use Nette\Mail\Message;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;


/**
 * Class MessageCenter
 * @package App\Classes\Mail
 *
 * @property-read Message $message
 */
final class MessageCenter
{
    use SmartObject;

    /** @var MailService */
    protected MailService $mailService;

    /** @var Request */
    protected Request $request;

    /** @var string */
    protected string $fromEmail = 'jsme@seznamovak.org';

    /** @var string */
    protected string $fromName = 'Seznamovák';



    public function __construct(MailService $mailService, Request $request)
    {
        $this->mailService = $mailService;
        $this->request = $request;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->mailService->message;
    }


    /**
     * @param string $subject
     * @param string[]|string $to
     * @param string[]|string $bcc
     */
    private function setMessageHeader(string $subject, $to, $bcc = [])
    {
        if (!is_array($to)) {
            $to = [$to];
        }

        if (!is_array($bcc)) {
            $bcc = [$bcc];
        }

        foreach ($to as $email) {
            $this->message->addTo($email);
        }

        foreach ($bcc as $email) {
            $this->message->addBcc($email);
        }

        $this->message->setFrom($this->fromEmail, $this->fromName);
        $this->message->setSubject($subject);
    }

    /**
     * @param string $templateName
     * @param array $params
     * @param int|null $userId
     */
    private function setMessageTemplate(string $templateName, array $params = [], int $userId = null)
    {
        $this->mailService->addTemplateParams($params);
        $this->mailService->addTemplateParams([
            // Any params used by all templates go here
            'basePath' => $this->request->getUrl()->getHostUrl(),
        ]);

        $this->mailService->setMessageTemplate($templateName);
    }


    /** ************************************************************************************************************  */

    /**
     * @param ArrayHash $values
     */
    public function createApplicationCreatedMail(ArrayHash $values)
    {
        $this->setMessageHeader('Vítej v rodině Seznamováku - přihláška', $values['email']);
        $this->setMessageTemplate('application_create', [
            'firstname' => $values['firstname'],
            'lastname' => $values['lastname'],
            'email' => $values['email'],
            'password' => $values['password'],
            'phone' => $values['phone'],

        ]);

        $this->mailService->send();
    }

    /**
     * @param ArrayHash $values
     */
    public function createForgottenPasswordMail(ArrayHash $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - Zapomenuté heslo', $values['email']);
        $this->setMessageTemplate('forgotten_password', [
            'email' => $values['email'],
            'password' => $values['password'],
        ]);

        $this->mailService->send();
    }

    /**
     * @param iterable $values
     */
    public function createPaymentReceivedMail(iterable $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - platba přijata', $values['email']);
        $this->setMessageTemplate('payment_received', [
            'email' => $values['email'],
            'amount' => $values['amount'],
        ]);

        $this->mailService->send();
    }

    /**
     * @param iterable $values
     */
    public function createFullReceivedMail(iterable $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - vše zaplaceno', $values['email']);
        $this->setMessageTemplate('full_received', [
            'email' => $values['email'],
            'amount' => $values['amount'],
        ]);

        $this->mailService->send();
    }

    /**
     * @param iterable $values
     */
    public function createPaymentFullStornoMail(iterable $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - potvrzení žádosti o storno', $values['email']);
        $this->setMessageTemplate('payment_full_storno', [
            'refundDate' => $values['refundDate'],
        ]);

        $this->mailService->send();
    }

    /**
     * @param iterable $values
     */
    public function createPaymentZalohaStornoMail(iterable $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - potvrzení žádosti o storno', $values['email']);
        $this->setMessageTemplate('payment_zaloha_storno', [
            'fullDeadline' => $values['fullDeadline'],
            'refundDate' => $values['refundDate'],
        ]);

        $this->mailService->send();
    }

    /**
     * @param iterable $values
     */
    public function createPaymentNoRefundStornoMail(iterable $values)
    {
        $this->setMessageHeader('Seznamovák 2021 - přijetí žádosti o storno', $values['email']);
        $this->setMessageTemplate('payment_no_refund_storno', [
            'deadline' => $values['deadline'],
        ]);

        $this->mailService->send();
    }
}