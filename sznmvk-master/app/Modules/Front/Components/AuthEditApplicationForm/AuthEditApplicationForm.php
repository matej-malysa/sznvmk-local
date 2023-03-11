<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\AuthEditApplicationForm;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use App\Components\Flashes\Flashes;
use App\Model\ActivitiesModel;
use App\Model\AllergiesModel;
use App\Model\ApplicationsModel;
use App\Model\BonusesModel;
use App\Model\FacultiesModel;
use App\Model\FoodPreferencesModel;
use App\Model\GroupsModel;
use App\Model\GendersModel;
use App\Model\ImportantDatesModel;
use App\Model\ParticipantsModel;
use App\Model\PaymentsLimitsModel;
use App\Model\SessionsModel;
use App\Model\TransportModel;
use App\Modules\Admin\Components\Applications\EditApplicationForm\EditApplicationForm;
use Dibi\Exception;
use Dibi\Row;
use Dibi\UniqueConstraintViolationException;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class AuthEditApplicationForm extends EditApplicationForm
{
    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var GroupsModel */
    protected GroupsModel $groupsModel;

    /** @var array<int, int> */
    public array $getAllergybyId;

    protected Row|null $application;

    protected array $allAvailableSessions;

    public function __construct(ApplicationsModel $applicationsModel, FacultiesModel $facultiesModel, SessionsModel $sessionsModel, TransportModel $transportModel, BonusesModel $bonusesModel, ImportantDatesModel $importantDatesModel, PaymentsLimitsModel $paymentsLimitsModel, ParticipantsModel $participantsModel, AllergiesModel $allergiesModel, ActivitiesModel $activitiesModel, MessageCenter $messageCenter, Session $session, GendersModel $gendersModel, GroupsModel $groupsModel, FoodPreferencesModel $foodPreferencesModel, MailerSendService $mailerSendService, int $applicationId)
    {
        parent::__construct($applicationsModel, $facultiesModel, $sessionsModel, $transportModel, $bonusesModel, $importantDatesModel, $paymentsLimitsModel, $messageCenter, $session, $gendersModel, $allergiesModel, $activitiesModel, $participantsModel, $foodPreferencesModel, $mailerSendService, $groupsModel, $applicationId);
        $this->participantsModel = $participantsModel;
        $this->application = $this->applicationsModel->getApplication($this->applicationId);
        $this->allAvailableSessions = $this->participantsModel->getAvailableSessions();
        $this->allergiesModel = $allergiesModel;
        $this->getAllergybyId = $allergiesModel->getAllergybyId($applicationId, 'name');
        $this->groupsModel = $groupsModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->sessionsToSelect = $this->sessionsToSelect;
        $this->template->gendersToSelect = $this->gendersToSelect;
        $this->template->allAvailableSessions = $this->allAvailableSessions;
        $this->template->transportsToSelect = $this->transportsToSelect;
        $this->template->bonusesToSelect = $this->bonusesToSelect;
        $this->template->transportVlastni = $this->application['transport'] == TransportModel::VLASTNI;
        $this->template->status = $this->participantStatus;
        $this->template->group = $this->groupsModel->getGroup($this->applicationId);

        $all_allergies = $this->allergiesToSelect;
        $user_allergies = $this->getAllergybyId;
        $allergies_to_select = [];
        foreach($all_allergies as $allergy) {
            if(in_array($allergy['name'], $user_allergies)) {
                array_push($allergies_to_select, [
                    'id' => $allergy['id'],
                    'name' => $allergy['name'],
                    'checked' => 1]);
            } else {
                array_push($allergies_to_select, [
                    'id' => $allergy['id'],
                    'name' => $allergy['name'],
                    'checked' => 0]);
            }
        }

        $all_activities = $this->activitiesToSelect;
        $session_id = $this->applicationsModel->getApplication($this->applicationId);
        $user_activities = $this->getActivitybyId;
        $activities_to_select = [];
        foreach($all_activities as $activity) {
            if($activity['session'] == $session_id['session']) {
                if(in_array($activity['name'], $user_activities)) {
                    array_push($activities_to_select, [
                        'id' => $activity['id'],
                        'name' => $activity['name'],
                        'checked' => 1]);
                } else {
                    array_push($activities_to_select, [
                        'id' => $activity['id'],
                        'name' => $activity['name'],
                        'checked' => 0]);
                }
            }

        }
        $this->template->activitiesToSelect = $activities_to_select;
        $this->template->allergiesToSelect = $allergies_to_select;
        $this->template->render();
    }

    public function createComponentForm(): Form
    {
        $form = parent::createComponentForm();
        $form['email']->setDisabled()->setOmitted(false);
        $form['bonus']->setDisabled()->setOmitted(false);
        unset($form['sendmail']);

//        // Zkontroluj kapacitu turnusů - pokud není místo, vyřaď turnus z nabídky
//        unset($form['session']);
//        $form->addRadioList('session', null, $this->allAvailableSessions);
        $form->setDefaults($this->application);

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        $allergies = $form->getHttpData($form::DATA_TEXT, 'allergy[]');
        $activities = $form->getHttpData($form::DATA_TEXT, 'activities[]');
         if($this->application['session'] != $values['session'] and $values['session'] != null and
            $this->groupsModel->getGroup($this->applicationId))
        {
            $this->flashMessage('Neplatná data', Flashes::FLASH_DANGER);

        }
        else {
            try {
                $this->applicationsModel->editApplication($this->applicationId, $values, 1);
                if($allergies != NULL) {
                    $this->allergiesModel->editAllergies($allergies, $this->applicationId);
                }
                if($activities != NULL) {
                    $this->activitiesModel->editActivities($activities, $this->applicationId);
                }
                $this->flashMessage('Přihláška úspěšně upravena', Flashes::FLASH_SUCCESS);
            } catch (ValidationException $ex) {
                $form->addError('Neplatná data v poli ' . $ex->getMessage());
                $this->flashMessage('Neplatná data', Flashes::FLASH_DANGER);
            } catch (UniqueConstraintViolationException $ex) {
                if (substr($ex->getMessage(), -3, 2) == 'vs') {
                    $form->addError('Přihláška s tímto telefonním číslem již existuje');
                    $this->flashMessage('Přihláška s tímto telefonním číslem již existuje', Flashes::FLASH_DANGER);
                } else {
                    $form->addError('Přihláška pro tuto e-mailovou adresu již existuje');
                    $this->flashMessage('Přihláška pro tuto e-mailovou adresu již existuje', Flashes::FLASH_DANGER);
                }
            } catch (Exception $ex) {
                $this->flashMessage('Chyba při ukládání dat', Flashes::FLASH_DANGER);
            }
        }

        if (!$form->hasErrors()) {
            $this->finishHandler();
        } else {
            $this->redrawControl();
        }
    }
}
