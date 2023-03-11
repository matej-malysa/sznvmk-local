<?php
declare(strict_types=1);

namespace App\Classes\Mail;


use Latte\Engine;
use Nette\FileNotFoundException;
use Nette\Mail\Mailer;
use Nette\Mail\SendException;
use Nette\Mail\Message;
use Nette\Mail\SmtpMailer;

class MailService
{
    /** @var Message */
    public Message $message;

    /** @var array */
    private array $params = [];

    /** @var Mailer */
    private Mailer $mailer;

//    /** @var SmtpMailer */
//    private SmtpMailer $smtpMailer;


    /* ABSTRACT ----------------------------------------------------------------------------------------------------- */

    /**
     * Get path for email template.
     * @param string $templateName Requested templated name.
     * @return string Path to the requested template.
     */
    public function getMailTemplatePath(string $templateName): string
    {
        if (strstr($templateName, '.latte') === false) {
            $extension = '.latte';
        } else {
            $extension = '';
        }

        return __DIR__ . "/templates/{$templateName}{$extension}";

    }

    /* PUBLIC ------------------------------------------------------------------------------------------------------- */

    /**
     * MailService constructor.
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
    {
        $this->mailer = $mailer;
//        $this->smtpMailer = new SmtpMailer([
//            'host' => 'wes1-smtp.wedos.net',
//            'username' => 'jsme@seznamovak.org',
//            'password' => 'Sznmvk-Mailly-H0H0H0',
//            'port' => 587,
//            'secure' => 'tls',
//        ]);
        $this->message = new Message();
    }

    /**
     * Get mail template contents.
     * @param string $templateName Name of the template.
     * @return string Template file contents.
     * @throws FileNotFoundException When template file does not exist.
     */
    public function getMailTemplate(string $templateName): string
    {
        $path = $this->getMailTemplatePath($templateName);

        if (file_exists($path)) {
            return file_get_contents($path);
        } else {
            throw new FileNotFoundException("Template '{$templateName}' not found");
        }
    }

    /**
     * Add parameters to mail template.
     * Remember to call before setMessageTemplate!
     * @param array $params
     */
    public function addTemplateParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Set message body to a Latte template
     * @param string $templateName Name of the template.
     */
    public function setMessageTemplate(string $templateName)
    {
        $latte = new Engine();
        $path = $this->getMailTemplatePath($templateName);

        $this->message->setHtmlBody($latte->renderToString($path, $this->params));
    }

    /**
     * Send stored message.
     */
    public function send()
    {
        try {
            $this->mailer->send($this->message);
        } catch (SendException $e) {
            throw $e;
        }

        // Remove old message
        $this->message = new Message();
    }
}
