<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\ForgottenForm;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\ApplicationsModel;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;

class ForgottenForm extends FormComponent
{
    /**
     * @var ApplicationsModel
     */
    protected ApplicationsModel $applicationsModel;

    protected Passwords $passwords;

    protected MessageCenter $messageCenter;

    protected MailerSendService $mailerSendService;

    public function __construct(ApplicationsModel $applicationsModel, Passwords $passwords, MessageCenter $messageCenter, MailerSendService $mailerSendService)
    {
        parent::__construct();
        $this->applicationsModel = $applicationsModel;
        $this->passwords = $passwords;
        $this->messageCenter = $messageCenter;
        $this->mailerSendService = $mailerSendService;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addEmail('email', 'E-mail:')->setRequired();
        $form->addSubmit('submit', 'Resetovat heslo');

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        if ($application = $this->applicationsModel->getByEmail($values['email'])) {
            $values['password'] = ApplicationsModel::generateRandomPassword();
            $hash = $this->passwords->hash($values['password']);
            $this->applicationsModel->setNewPassword($application['id'], $hash);
            $values['firstname'] = $application['firstname'];
            $this->mailerSendService->forgottenPassword($values);
//            $this->messageCenter->createForgottenPasswordMail($values);
            $this->flashMessage('E-mail s novým heslem byl úspěšně odeslán', Flashes::FLASH_SUCCESS);
            $this->finishHandler();
        } else {
            $this->flashMessage('Přihláška s tímto e-mailem nebyla nalezena', Flashes::FLASH_DANGER);
        }
    }
}