<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Mail\MessageCenter;
use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\FoodPreferencesModel;
use App\Model\ParticipantsModel;
use App\Model\GendersModel;
use App\Model\AllergiesModel;
use App\Model\ActivitiesModel;
use App\Model\LodgingModel;
use App\Modules\Admin\Components\Applications\ApplicationPaymentsGrid\ApplicationPaymentsGrid;
use App\Modules\Admin\Components\Applications\ApplicationPaymentsGrid\IApplicationPaymentsGridFactory;
use App\Modules\Admin\Components\Applications\ApplicationsGrid\ApplicationsGrid;
use App\Modules\Admin\Components\Applications\ApplicationsGrid\IApplicationsGridFactory;
use App\Modules\Admin\Components\Applications\ApplicationsLogGrid\ApplicationsLogGrid;
use App\Modules\Admin\Components\Applications\ApplicationsLogGrid\IApplicationsLogGridFactory;
use App\Modules\Admin\Components\Applications\CreateApplicationForm\ICreateApplicationFormFactory;
use App\Modules\Admin\Components\Applications\CreateApplicationForm\CreateApplicationForm;
use App\Modules\Admin\Components\Applications\DeletedApplicationsGrid\DeletedApplicationsGrid;
use App\Modules\Admin\Components\Applications\DeletedApplicationsGrid\IDeletedApplicationsGridFactory;
use App\Modules\Admin\Components\Applications\EditApplicationForm\EditApplicationForm;
use App\Modules\Admin\Components\Applications\EditApplicationForm\IEditApplicationFormFactory;
use App\Modules\Admin\Components\Applications\NullGenderGrid\NullGenderGrid;
use App\Modules\Admin\Components\Applications\NullGenderGrid\INullGenderGridFactory;
use Dibi\Exception;
use Nette\Application\AbortException;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;

class ApplicationsPresenter extends BasePresenter
{
    /** @var IApplicationsGridFactory @inject */
    public IApplicationsGridFactory $applicationsGridFactory;

    /** @var IApplicationsLogGridFactory @inject */
    public IApplicationsLogGridFactory $applicationsLogGridFactory;

    /** @var ICreateApplicationFormFactory @inject */
    public ICreateApplicationFormFactory $createApplicationFormFactory;

    /** @var IDeletedApplicationsGridFactory @inject */
    public IDeletedApplicationsGridFactory $deletedApplicationsGridFactory;

    /** @var IEditApplicationFormFactory @inject */
    public IEditApplicationFormFactory $editApplicationFormFactory;

    /** @var IApplicationPaymentsGridFactory @inject */
    public IApplicationPaymentsGridFactory $applicationPaymentsGridFactory;

    /** @var INullGenderGridFactory @inject */
    public INullGenderGridFactory $nullGenderGridFactory;

    /** @var ApplicationsModel @inject */
    public ApplicationsModel $applicationsModel;

    /** @var ParticipantsModel @inject */
    public ParticipantsModel $participantsModel;

    /** @var FacultiesModel @inject */
    public FacultiesModel $facultiesModel;

     /** @var GendersModel @inject */
    public GendersModel $gendersModel;

    /** @var AllergiesModel @inject */
    public AllergiesModel $allergiesModel;

    /** @var ActivitiesModel @inject */
    public ActivitiesModel $activitiesModel;

    /** @var FoodPreferencesModel @inject*/
    public FoodPreferencesModel $foodPreferencesModel;

    /** @var LodgingModel @inject */
    public LodgingModel $lodgignModel;

    /** @var Passwords @inject */
    public Passwords $passwords;

    /** @var MessageCenter @inject */
    public MessageCenter $messageCenter;

    /** @var int */
    protected int $id;

     /** @var int */
    public int $backlink;

    public function startup()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_APPLICATIONS)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }

        parent::startup();
    }

    public function actionDetail(int $id)
    {
        $this->id = $id;
        $this->template->user_role = $this->user->getIdentity()->roles[0];
        $this->template->backlink = 0;
        $this->template->application = $application = $this->applicationsModel->getApplication($this->id);
        $this->template->faculty = $this->facultiesModel->getSchoolCodeAndFacultyName($application['faculty']);
        $this->template->participant = $par = $this->participantsModel->getParticipantByApplicationId($this->id);
        $this->template->gender = $this->gendersModel->getNameById($application['gender']);
        $this->template->queueOrder = $this->participantsModel->getQueueOrder($application['id'], $application['session']);
        $this->template->allergies = $this->allergiesModel->getAllergybyId($id, 'name');
        if($application["session"] != null) {
            $this->template->activities = $this->activitiesModel->getActivitybyId($id,'name');
        }
        $this->template->activities = $this->activitiesModel->getActivitybyId($id, 'name');
        $this->template->food_preferences = $this->foodPreferencesModel->getFoodPreferenceById($id, 'name');

        if (!empty($par)) {
            if ($par['room']) {
                $this->template->lodging = $this->lodgignModel->getNameById($par['room']);
            }
        } else {
            $this->template->lodging = null;
        }
    }

    public function actionEdit(int $id,int $backlink = 1)
    {
        $this->id = $id;
        $this->backlink = $backlink;
        $this->template->backlink = $backlink;
        $this->template->application = $this->applicationsModel->getApplication($this->id);
    }

    public function actionGenderEdit()
    {

        $this->template->accessToScript = $this->user->isAllowed(Authorizator::RESOURCE_INSTRUCTORS);

    }

    public function handleStartGenderScript()
    {
        $data = $this->applicationsModel->getApplicationsConfirmParticipantsNullGender();
        //$data = array_slice($data, 0, 5, true);
        foreach ($data as $app){
           $gender_data =  NullGenderGrid::StartGenderScript($app);
           if($gender_data[0])
           {
               $this->applicationsModel->ScriptAddGender($gender_data[0],$gender_data[1]);
           }
        }


        $this->redirect('Applications:genderEdit');

    }
    /**
     * @return NullGenderGrid
     */
    public function createComponentNullGenderGrid(): NullGenderGrid
    {
        return $this->nullGenderGridFactory->create();
    }

    /**
     * @return ApplicationsGrid
     */
    public function createComponentApplicationsGrid(): ApplicationsGrid
    {
        return $this->applicationsGridFactory->create();
    }

    /**
     * @return ApplicationsLogGrid
     */
    public function createComponentApplicationsLogGrid(): ApplicationsLogGrid
    {
        return $this->applicationsLogGridFactory->create($this->id);
    }

    /**
     * @return DeletedApplicationsGrid
     */
    public function createComponentDeletedApplicationsGrid(): DeletedApplicationsGrid
    {
        return $this->deletedApplicationsGridFactory->create();
    }

    /**
     * @return CreateApplicationForm
     */
    public function createComponentNewApplicationForm(): CreateApplicationForm
    {
        $form = $this->createApplicationFormFactory->create();
        $form->setRedirect('Applications:default');
        return $form;
    }

    
     /**
     * @return EditApplicationForm
     */
    public function createComponentEditApplicationForm(): EditApplicationForm
    {
        $form = $this->editApplicationFormFactory->create($this->id);
        if($this->backlink)
        {
            

            $form->setRedirect('Applications:genderEdit');
        }
        else
        {
            
            $form->setRedirect('Applications:detail', [$this->id]);
        }

        return $form;
    }

    /**
     * @return ApplicationPaymentsGrid
     */
    public function createComponentApplicationPaymentsGrid(): ApplicationPaymentsGrid
    {
        return $this->applicationPaymentsGridFactory->create($this->id);
    }

    /**
     * @param int $id
     * @throws AbortException
     */
    public function handleDelete(int $id)
    {
        try {
            $this->applicationsModel->deleteApplication($id, $this->getUser()->id);
            $this->flashMessage('Přihláška úspěšně smazána', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při mazání přihlášky. Prihláška nebyla smazána.', Flashes::FLASH_DANGER);
        }

        $this->redirect('Applications:default');
    }

    /**
     * @param int $id
     * @throws AbortException
     */
    public function handleRestore(int $id): void
    {
        try {
            $this->applicationsModel->restoreApplication($id, $this->presenter->getUser()->id);
            $this->flashMessage('Přihláška úspěšně obnovena', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při obnovení přihlášky', Flashes::FLASH_DANGER);
        }

        $this->redirect('Applications:default');
    }

    /**
     * @param int $id
     */
    public function handlePasswordReset(int $id)
    {
        try {
            $values = new ArrayHash();
            $values['email'] = $this->applicationsModel->getEmailByID($id);
            $values['password'] = ApplicationsModel::generateRandomPassword();
            $hash = $this->passwords->hash($values['password']);
            $this->applicationsModel->setNewPassword($id, $hash);
            $this->messageCenter->createForgottenPasswordMail($values);
            $this->flashMessage('Reset hesla úspěšný. E-mail byl odeslán.', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Reset hesla se nezdařil', Flashes::FLASH_DANGER);
        }
    }
}