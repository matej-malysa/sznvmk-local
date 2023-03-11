<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\CreateApplicationForm;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\ApplicationsModel;
use App\Model\ActivitiesModel;
use App\Model\FoodPreferencesModel;
use App\Model\ParticipantsModel;
use App\Model\BonusesModel;
use App\Model\FacultiesModel;
use App\Model\ImportantDatesModel;
use App\Model\PaymentsLimitsModel;
use App\Model\SessionsModel;
use App\Model\TransportModel;
use App\Model\GendersModel;
use App\Model\GroupsModel;
use App\Model\AllergiesModel;
use Dibi\Exception;
use Dibi\UniqueConstraintViolationException;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class CreateApplicationForm extends FormComponent
{
    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    /** @var ActivitiesModel */
    protected ActivitiesModel $activitiesModel;

    /** @var FoodPreferencesModel @inject*/
    public FoodPreferencesModel $foodPreferencesModel;

    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var AllergiesModel */
    protected AllergiesModel $allergiesModel;

    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var TransportModel */
    protected TransportModel $transportModel;

    /** @var BonusesModel */
    protected BonusesModel $bonusesModel;

    /** @var ImportantDatesModel */
    protected ImportantDatesModel $importantDatesModel;

    /** @var PaymentsLimitsModel */
    protected PaymentsLimitsModel $paymentsLimitsModel;

    /** @var MessageCenter */
    protected MessageCenter $messageCenter;

    /** @var MailerSendService */
    protected MailerSendService $mailerSendService;

    /** @var array<int, string> */
    protected array $facultiesToSelect;

    /** @var array<int, string> */
    protected array $sessionsToSelect;

    /** @var array<int, string> */
    protected array $transportsToSelect;

    /** @var array<int, string> */
    protected array $bonusesToSelect;

    /** @var array<int, string> */
    public array $gendersToSelect;

    /** @var array */
    public array $allergiesToSelect;

    /** @var array */
    public array $activitiesToSelect;

    /** @var array */
    public array $foodPreferencesToSelect;

    /** @var array<int, int> */
    public array $getAllergybyId;

    /** @var array<int, int> */
    public array $getActivitybyId;

    /** @var int */
    protected int $participantsStatus;

    /** @var SessionSection */
    public SessionSection $session;

    /** @var GroupsModel */
    protected GroupsModel $groupsModel;

    public function __construct(ApplicationsModel $applicationsModel, FacultiesModel $facultiesModel, SessionsModel $sessionsModel, TransportModel $transportModel, BonusesModel $bonusesModel, ImportantDatesModel $importantDatesModel, PaymentsLimitsModel $paymentsLimitsModel, MessageCenter $messageCenter, Session $session, GendersModel $gendersModel, AllergiesModel $allergiesModel, ActivitiesModel $activitiesModel, ParticipantsModel $participantsModel, MailerSendService $mailerSendService,GroupsModel $groupsModel, FoodPreferencesModel $foodPreferencesModel)
    {
        parent::__construct();
        $this->applicationsModel = $applicationsModel;
        $this->participantsModel = $participantsModel;
        $this->facultiesModel = $facultiesModel;
        $this->sessionsModel = $sessionsModel;
        $this->transportModel = $transportModel;
        $this->bonusesModel = $bonusesModel;
        $this->importantDatesModel = $importantDatesModel;
        $this->paymentsLimitsModel = $paymentsLimitsModel;
        $this->messageCenter = $messageCenter;
        $this->mailerSendService = $mailerSendService;
        $this->facultiesToSelect = $this->facultiesModel->getAllToSelect();
        $this->sessionsToSelect = $this->sessionsModel->getAllToRadioSelect();
        $this->transportsToSelect = $this->transportModel->getAllToRadioSelect();
        $this->bonusesToSelect = $this->bonusesModel->getActiveToRadioSelect();
        $this->gendersToSelect = $gendersModel->getAllToSelect();
        $this->allergiesToSelect = $allergiesModel->getAllToSelect();
        $this->activitiesToSelect = $activitiesModel->getAllToSelect();
        $this->foodPreferencesToSelect = $foodPreferencesModel->getAllToSelect();
        $this->session = $session->getSection('school');
        $this->groupsModel = $groupsModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->sessionsToSelect = $this->sessionsToSelect;
        $this->template->gendersToSelect = $this->gendersToSelect;
        $this->template->allergiesToSelect = $this->allergiesToSelect;
        $this->template->activitiesToSelect = $this->activitiesToSelect;
        $this->template->foodPreferencesToSelect = $this->foodPreferencesToSelect;
        $this->template->transportsToSelect = $this->transportsToSelect;
        $this->template->bonusesToSelect = $this->bonusesToSelect;
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addSelect('faculty', null, $this->facultiesToSelect)->setDefaultValue($this->session['lastFaculty']);
        $form->addText('firstname')->setRequired();
        $form->addText('lastname')->setRequired();
        $form->addEmail('email')->setRequired();
        $form->addPassword('password');
        $form->addText('birthdate');
        $form->addText('phone');
        $form->addHidden('fullPhone');
        $form->addRadioList('gender', null, $this->gendersToSelect)->setRequired('Zvolte prosím pohlaví');
        $form->addRadioList('session', null, $this->sessionsToSelect);
        $form->addRadioList('transport', null, $this->transportsToSelect);
        $form->addRadioList('bonus', null, $this->bonusesToSelect);
        $form->addSubmit('submit', 'Založit přihlášku');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            $this->session['lastFaculty'] = $values['faculty'];
            $values['created_by'] = $this->presenter->getUser()->id;
            if ($values['password'] == null) {
                $values['password'] = $this->applicationsModel->generateRandomPassword();
            }
            $values['applicationId'] = $applicationId = $this->applicationsModel->createApplicationAdmin($values);
            $generalInfo['sessions'] = $this->sessionsModel->getAllToRadioSelect();
            $generalInfo['dates'] = $this->importantDatesModel->getAll()->fetchAssoc('id');
            $generalInfo['prices'] = $this->paymentsLimitsModel->getAll()->fetchAssoc('id');
            $this->mailerSendService->createApplication($values, $generalInfo);
//            $this->messageCenter->createApplicationCreatedMail($values);
            $this->flashMessage('Přihláška s ID ' . $applicationId . ' úspěšně založena', Flashes::FLASH_SUCCESS);
        } catch (ValidationException $ex) {
            $form->addError('Neplatná data v poli ' . $ex->getMessage());
            $this->flashMessage('Neplatná data. Prihláška nebyla uložena.', Flashes::FLASH_DANGER);
        } catch (UniqueConstraintViolationException $ex) {
            if (substr($ex->getMessage(), -3, 2) == 'vs') {
                $form->addError('Přihláška s tímto telefonním číslem již existuje');
                $this->flashMessage('Přihláška s tímto telefonním číslem již existuje', Flashes::FLASH_DANGER);
            } else {
                $form->addError('Přihláška pro tuto e-mailovou adresu již existuje');
                $this->flashMessage('Přihláška pro tuto e-mailovou adresu již existuje', Flashes::FLASH_DANGER);
            }
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při ukládání dat. Prihláška nebyla uložena.', Flashes::FLASH_DANGER);
        }

        if (!$form->hasErrors()) {
            $this->finishHandler();
        } else {
            $this->redrawControl();
        }
    }
}
