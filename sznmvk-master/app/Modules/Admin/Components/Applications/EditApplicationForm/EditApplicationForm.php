<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\EditApplicationForm;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use App\Components\Flashes\Flashes;
use App\Model\ApplicationsModel;
use App\Model\ActivitiesModel;
use App\Model\BonusesModel;
use App\Model\FacultiesModel;
use App\Model\FoodPreferencesModel;
use App\Model\ImportantDatesModel;
use App\Model\PaymentsLimitsModel;
use App\Model\SessionsModel;
use App\Model\TransportModel;
use App\Model\GroupsModel;
use App\Model\GendersModel;
use App\Model\AllergiesModel;
use App\Model\ParticipantsModel;
use App\Modules\Admin\Components\Applications\CreateApplicationForm\CreateApplicationForm;
use Dibi\Exception;
use Dibi\UniqueConstraintViolationException;
use MailerSend\Exceptions\MailerSendAssertException;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Http\Session;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;

class EditApplicationForm extends CreateApplicationForm
{
    /** @var int */
    protected int $applicationId;

    /** @var int|null */
    protected ?int $participantStatus;

    /** @var array<int, int> */
    public array $getAllergybyId;

    /** @var array<int, int> */
    public array $getActivitybyId;

    /** @var array<int, int> */
    public array $getFoodPreferenceById;

    public function __construct(ApplicationsModel $applicationsModel, FacultiesModel $facultiesModel, SessionsModel $sessionsModel, TransportModel $transportModel, BonusesModel $bonusesModel, ImportantDatesModel $importantDatesModel, PaymentsLimitsModel $paymentsLimitsModel, MessageCenter $messageCenter, Session $session, GendersModel $gendersModel, AllergiesModel $allergiesModel, ActivitiesModel $activitiesModel, ParticipantsModel $participantsModel, FoodPreferencesModel $foodPreferencesModel, MailerSendService $mailerSendService, GroupsModel $groupsModel, int $applicationId)
    {
        parent::__construct($applicationsModel, $facultiesModel, $sessionsModel, $transportModel, $bonusesModel, $importantDatesModel, $paymentsLimitsModel, $messageCenter, $session, $gendersModel, $allergiesModel, $activitiesModel, $participantsModel, $mailerSendService, $groupsModel, $foodPreferencesModel);
        $this->applicationId = $applicationId;
        $this->allergiesModel = $allergiesModel;
        $this->activitiesModel = $activitiesModel;
        $this->participantsModel = $participantsModel;
        $this->foodPreferencesModel = $foodPreferencesModel;
        $this->getAllergybyId = $allergiesModel->getAllergybyId($applicationId, 'name');
        $this->getActivitybyId = $activitiesModel->getActivitybyId($applicationId, 'name');
        $this->getFoodPreferenceById = $foodPreferencesModel->getFoodPreferenceById($applicationId, 'name');
        if (!empty($participant = $this->participantsModel->getParticipantByApplicationId($applicationId))) {
            $this->participantStatus = $participant['status'];
        } else {
            $this->participantStatus = null;
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->facultiesToSelect = $this->facultiesToSelect;
        $this->template->sessionsToSelect = $this->sessionsToSelect;
        $this->template->gendersToSelect = $this->gendersToSelect;
        $this->template->transportsToSelect = $this->transportsToSelect;
        $this->template->bonusesToSelect = $this->bonusesToSelect;
        $this->template->status = $this->participantStatus;
        $this->template->group = $this->groupsModel->getGroup($this->applicationId);
        $all_activities = $this->activitiesToSelect;
        $session_id = $this->applicationsModel->getApplication($this->applicationId);
        $user_activities = $this->getActivitybyId;
        $activities_to_select = [];
        if(!empty($user_activities)) {
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
        }
        $this->template->activitiesToSelect = $activities_to_select;

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

        $this->template->allergiesToSelect = $allergies_to_select;

        $all_food_preferences = $this->foodPreferencesToSelect;
        $user_preferences = $this->getFoodPreferenceById;
        $food_preferences_to_select = [];
        foreach($all_food_preferences as $food_preference) {
            if(in_array($food_preference['name'], $user_preferences)) {
                array_push($food_preferences_to_select, [
                    'id' => $food_preference['id'],
                    'name' => $food_preference['name'],
                    'checked' => 1]);
            } else {
                array_push($food_preferences_to_select, [
                    'id' => $food_preference['id'],
                    'name' => $food_preference['name'],
                    'checked' => 0]);
            }
        }
        $this->template->foodPreferencesToSelect = $food_preferences_to_select;

        $this->template->render();
    }

    /**
     * @return Form
     */
    public function createComponentForm(): Form
    {
        $form = parent::createComponentForm();
        $form->addText('spz', 'SPZ')
            ->setMaxLength(8);
        $form->addCheckbox('sendmail', 'SendMail');
        $form->setDefaults($this->applicationsModel->getApplication($this->applicationId));

        return $form;
    }

    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws AbortException
     * @throws MailerSendAssertException
     */
    public function formSucceeded(Form $form, ArrayHash $values)
    {
        $allergies = $form->getHttpData($form::DATA_TEXT, 'allergy[]');
        $activities = $form->getHttpData($form::DATA_TEXT, 'activities[]');
        $food_preferences = $form->getHttpData($form::DATA_TEXT, 'food_preferences[]');
        if($this->applicationsModel->getApplication($this->applicationId)['session'] != $values['session'] and $values['session'] != null and $this->groupsModel->getGroup($this->applicationId)) {
            $this->flashMessage('Neplatná data', Flashes::FLASH_DANGER);
        } else {
            try {
                $resendMail = $values['sendmail'];
                unset($values['sendmail']);
                $this->applicationsModel->editApplication($this->applicationId, $values, $this->presenter->getUser()->id);
                if ($allergies != NULL) {
                    $this->allergiesModel->editAllergies($allergies, $this->applicationId);
                }
                if ($activities != NULL) {
                    $this->activitiesModel->editActivities($activities, $this->applicationId);
                }

                if ($food_preferences != NULL) {
                    $this->foodPreferencesModel->editFoodPreferences($food_preferences, $this->applicationId);
                }
                if ($resendMail) {
                    $this->applicationsModel->resendCreateApplicationMail($this->applicationId, $values);
                }
                $this->flashMessage('Přihláška úspěšně upravena', Flashes::FLASH_SUCCESS);
            } catch (ValidationException $ex) {
                $form->addError('Neplatná data v poli ' . $ex->getMessage());
                $this->flashMessage('Neplatná data. Prihláška nebyla upravena.', Flashes::FLASH_DANGER);
            } catch (UniqueConstraintViolationException $ex) {
                if (substr($ex->getMessage(), -3, 2) == 'vs') {
                    $form->addError('Přihláška s tímto telefonním číslem již existuje');
                    $this->flashMessage('Přihláška s tímto telefonním číslem již existuje', Flashes::FLASH_DANGER);
                } else {
                    $form->addError('Přihláška pro tuto e-mailovou adresu již existuje');
                    $this->flashMessage('Přihláška pro tuto e-mailovou adresu již existuje', Flashes::FLASH_DANGER);
                }
            }
        }

        if (!$form->hasErrors()) {
            $this->finishHandler();
        } else {
            $this->redrawControl();
        }
    }

}
