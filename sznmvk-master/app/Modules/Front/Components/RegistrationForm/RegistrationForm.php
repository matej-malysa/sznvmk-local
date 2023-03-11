<?php
declare(strict_types=1);


namespace App\Modules\Front\Components\RegistrationForm;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use App\Components\AppComponent;
use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\GendersModel;
use App\Model\ImportantDatesModel;
use App\Model\PaymentsLimitsModel;
use App\Model\SessionsModel;
use App\Model\TransportModel;
use Dibi\Exception;
use Dibi\UniqueConstraintViolationException;
use Nette\Application\UI\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class RegistrationForm extends FormComponent
{
    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var TransportModel */
    protected TransportModel $transportModel;

    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    /** @var ImportantDatesModel */
    protected ImportantDatesModel $importantDatesModel;

    /** @var PaymentsLimitsModel */
    protected PaymentsLimitsModel $paymentsLimitsModel;

    /** @var MessageCenter */
    protected MessageCenter $messageCenter;

    /** @var array<int, string> */
    protected array $facultiesToSelect;

    /** @var array<int, string> */
    protected array $sessionsToSelect;

    /** @var array<int, string> */
    protected array $transportsToSelect;

    /** @var array<int, string> */
    protected array $gendersToSelect;

    /** @var MailerSendService */
    public MailerSendService $mailerSendService;

    public function __construct(FacultiesModel $facultiesModel, SessionsModel $sessionsModel, TransportModel $transportModel, ApplicationsModel $applicationsModel, ImportantDatesModel $importantDatesModel, PaymentsLimitsModel $paymentsLimitsModel, MessageCenter $messageCenter, MailerSendService $mailerSendService, GendersModel $gendersModel)
    {
        parent::__construct();
        $this->facultiesModel = $facultiesModel;
        $this->sessionsModel = $sessionsModel;
        $this->transportModel = $transportModel;
        $this->applicationsModel = $applicationsModel;
        $this->importantDatesModel = $importantDatesModel;
        $this->paymentsLimitsModel = $paymentsLimitsModel;
        $this->messageCenter = $messageCenter;
        $this->mailerSendService = $mailerSendService;
        $this->facultiesToSelect = $this->facultiesModel->getActiveToSelect();
        $this->sessionsToSelect = $this->sessionsModel->getAllToRadioSelect();
        $this->transportsToSelect = $this->transportModel->getAllToRadioSelect();
        $this->gendersToSelect = $gendersModel->getAllToSelect();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->sessionsToSelect = $this->sessionsToSelect;
        $this->template->transportsToSelect = $this->transportsToSelect;
        $this->template->gendersToSelect = $this->gendersToSelect;
        if (!empty($this->sessionsToSelect)) {
            $this->template->render();
        }
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('firstname')->setRequired();
        $form->addText('lastname')->setRequired();
        $form->addText('birthdate');
        $form->addText('phone')->setRequired();
        $form->addHidden('fullPhone');
        $form->addEmail('email')->setRequired();
        $form->addPassword('password')->setRequired();
        $form->addRadioList('gender', null, $this->gendersToSelect)->setRequired('Zvolte prosím pohlaví');
        $form->addSelect('faculty', null, $this->facultiesToSelect);
        $form->addRadioList('session', null, $this->sessionsToSelect);
        $form->addRadioList('transport', null, $this->transportsToSelect);
        $form->addCheckbox('gdpr')->setRequired();
        $form->addSubmit('submit');

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            // Application is always created here by the applicant himself
            $values['created_by'] = AppComponent::SYSTEM_USER;
            $values['applicationId'] = $this->applicationsModel->createApplicationApplicant($values);
            $generalInfo['sessions'] = $this->sessionsModel->getAllToRadioSelect();
            $generalInfo['dates'] = $this->importantDatesModel->getAll()->fetchAssoc('id');
            $generalInfo['prices'] = $this->paymentsLimitsModel->getAll()->fetchAssoc('id');
            $this->mailerSendService->createApplication($values, $generalInfo);
//            $this->messageCenter->createApplicationCreatedMail($values);
            $this->flashMessage('Přihláška úspěšně založena', Flashes::FLASH_SUCCESS);
        } catch (ValidationException $ex) {
            $form->addError('Neplatná data v poli ' . $ex->getMessage());
            $this->flashMessage('Neplatná data', Flashes::FLASH_DANGER);
        } catch (UniqueConstraintViolationException $ex) {
            $form->addError('Přihláška pro daný e-mail/telefonní číslo již existuje.');
            $this->flashMessage('Přihláška pro daný e-mail/telefonní číslo již existuje. Pokud neznáš heslo, resetuj si ho skrze odkaz na stránce Tvoje přihláška.', Flashes::FLASH_DANGER);
        } catch (Exception $ex) {
            $this->flashMessage('Databázová chyba, přihláška NEBYLA založena', Flashes::FLASH_DANGER);
        }
    }
}
