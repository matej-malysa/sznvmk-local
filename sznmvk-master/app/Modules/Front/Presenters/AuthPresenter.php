<?php
declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use App\Components\Flashes\Flashes;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\ImportantDatesModel;
use App\Model\ParticipantsModel;
use App\Model\PaymentsLimitsModel;
use App\Model\PaymentsModel;
use App\Model\GendersModel;
use App\Model\AllergiesModel;
use App\Model\ActivitiesModel;
use App\Model\GroupsModel;
use App\Modules\Front\Components\AuthEditApplicationForm\AuthEditApplicationForm;
use App\Modules\Front\Components\AuthEditApplicationForm\IAuthEditApplicationFormFactory;
use Dibi\Exception;
use Nette\Application\AbortException;

class AuthPresenter extends BasePresenter
{
    /** @var ApplicationsModel @inject */
    public ApplicationsModel $applicationsModel;

    /** @var FacultiesModel @inject */
    public FacultiesModel $facultiesModel;

    /** @var PaymentsModel @inject */
    public PaymentsModel $paymentsModel;

    /** @var PaymentsLimitsModel @inject */
    public PaymentsLimitsModel $paymentsLimitsModel;

    /** @var ImportantDatesModel @inject */
    public ImportantDatesModel $importantDatesModel;

    /** @var ParticipantsModel @inject */
    public ParticipantsModel $participantsModel;

     /** @var GendersModel @inject */
    public GendersModel $gendersModel;

    /** @var AllergiesModel @inject */
    public AllergiesModel $allergiesModel;

    /** @var ActivitiesModel @inject */
    public ActivitiesModel $activitiesModel;

     /** @var GroupsModel @inject */
    public GroupsModel $groupsModel;

    /** @var IAuthEditApplicationFormFactory @inject */
    public IAuthEditApplicationFormFactory $editApplicationFormFactory;

    /** @var int */
    public $id;

    public function startup()
    {
        $userRoles = $this->user->getRoles();
        if (in_array('instructor', $userRoles) || in_array('leader', $userRoles)) {
            $this->user->logout();
        }

        if (!$this->user->isLoggedIn()) {
            $this->redirect('Prihlaska:default');
        }

        parent::startup();
    }

    public function actionDefault()
    {
        $this->id = $this->user->getId();
        $this->template->editDeadline = $this->importantDatesModel->getById(ImportantDatesModel::KONEC_EDITACI)['deadline'];
        $this->template->application = $application = $this->applicationsModel->getApplication($this->id);
        $this->template->faculty = $this->facultiesModel->getSchoolCodeAndFacultyName($application['faculty']);
        $this->template->payments = $this->paymentsModel->getPaymentsForApplication($this->id);
        $this->template->paymentsSum = $paymentsSum = $this->paymentsModel->getPaymentsSumForApplication($this->id);
        $this->template->gender = $this->gendersModel->getNameById($application['gender']);
        $this->template->allergies = $this->allergiesModel->getAllergybyId($this->id, 'name');
        $this->template->activities = $this->activitiesModel->getActivitybyId($this->id, 'name');
        $cena = $this->paymentsLimitsModel->getAll()->fetchAssoc('id');
        $this->template->paymentsRemaining = $cena[PaymentsLimitsModel::FULL_PRICE_ID]['amount'] - $paymentsSum;
        $this->template->cena = $cena;

        $deadlines = $this->importantDatesModel->getAll()->fetchAssoc('id');
        if (date('Y-m-d') <= $deadlines[ImportantDatesModel::ZALOHA_1]['deadline']) {
            $deadlineZaloha = $deadlines[ImportantDatesModel::ZALOHA_1]['deadline'];
        } else {
            $deadlineZaloha = $deadlines[ImportantDatesModel::ZALOHA_2]['deadline'];
        }
        $this->template->deadlineZaloha = $deadlineZaloha;
        $this->template->deadlines = $deadlines;

        $participant = $this->participantsModel->getParticipantByApplicationId($application['id']);
        if ($participant) {
            $this->template->participantStatus = $participant['status'];
            $this->template->participantStatusText = $participant['statusText'];
        } else {
            $this->template->participantStatus = null;
            $this->template->participantStatusText = null;
        }
        $allAvailableSessions = $this->participantsModel->getAvailableSessions();
        unset($allAvailableSessions[$application['session']]);
        $this->template->availableSessions = $allAvailableSessions;
        $this->template->queueOrder = $this->participantsModel->getQueueOrder($application['id'], $application['session']);
        $this->template->storno = $this->paymentsModel->anyPaymentsForStorno($application['id']);

        if($application['session']) {
            $this->template->group = $this->groupsModel->getGroup($application['id']);
            $this->template->members = $this->groupsModel->getMembers($application['id']);
            $this->template->invitation = $this->groupsModel->getInvitation($application['id']);
            $this->template->ID_for_group = $this->groupsModel->getIDforGroup($application['id']);
            $this->template->create_by_name = $this->groupsModel->getNameofCreateBy($application['id']);
            $this->template->InviMems = $this->groupsModel->getInvitedMembers($application['id']);
        }

        $this->template->inv_friends = $this->applicationsModel->getInvitedFriends($application['id']);
    }

    public function actionEdit()
    {
        if (date('Y-m-d') > $this->template->editDeadline = $this->importantDatesModel->getById(ImportantDatesModel::KONEC_EDITACI)['deadline']) {
            $this->flashMessage('Už nelze upravovat přihlášku, je po termínu', Flashes::FLASH_WARNING);
            $this->redirect('Auth:default');
        }
        $this->id = $this->user->getId();
    }

    /**
     * @return AuthEditApplicationForm
     */
    public function createComponentAuthEditApplicationForm(): AuthEditApplicationForm
    {
        $form = $this->editApplicationFormFactory->create($this->id);
        $form->setRedirect('Auth:default');
        return $form;
    }

    /**
     * @throws AbortException
     * @secured
     */
    public function handleLogout()
    {
        $this->user->logout();
        $this->flashMessage('Úspěšně odhlášeno', Flashes::FLASH_SUCCESS);
        $this->redirect('Homepage:default');
    }

    /**
     * @param int $sessionId
     * @throws AbortException
     * @secured
     */
    public function handleChangeSession(int $sessionId)
    {
        try {
            $this->applicationsModel->changeSession($this->id, $sessionId);
            $this->flashMessage('Změna turnusu úspěšná', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při změně turnusu', Flashes::FLASH_DANGER);
        }

        $this->redirect('Auth:default');
    }

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function handleCreateGroup()
    {
        $this->groupsModel->createGroup($this->id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function handleDeleteGroup()
    {
        $this->groupsModel->deleteGroup($this->id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function handleDeleteFromGroup()
    {
        $this->groupsModel->deleteFromGroup($this->id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @param int $id
     * @throws AbortException
     * @throws Exception
     */
    public function handleDeleteMember(int $id)
    {
        $this->groupsModel->deleteFromGroup($id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @param array $data
     * @throws AbortException
     * @throws Exception
     */
    public function handleSendInvitation(array $data)
    {
            if (count($this->groupsModel->getInvitedMembers($this->id)) < 5)
            {

                if(($out = $this->groupsModel->createInvitation($this->id, intval($data[0]['val']))) == -1)
                {
                    $success = 3;
                }
                else if($out == -2)
                {
                    $success = 4;
                }
                else if($out == -3)
                {
                    $success = 5;
                }
                else if($out == -4)
                {
                    $success = 6;
                }
                else if($out == -5)
                {
                    $success = 7;
                }
                else
                {
                    $success = 1;
                }
            }
            else
            {
                $success = 2;
            }

        $this->sendJson((object)[
            'success' => $success,
        ]);
    }

    /**
     * @param int $id
     * @throws AbortException
     * @throws Exception
     */
    public function handleacceptInvitation(int $id)
    {
        $this->groupsModel->acceptInvitation($id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @param int $id
     * @throws AbortException
     */
    public function handledeniedInvitation(int $id)
    {
        $this->groupsModel->deniedInvitation($id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @param int $id
     * @throws AbortException
     */
    public function handleDeleteInviMember(int $id)
    {
        $this->groupsModel->deniedInvitation($id);
        $this->redirect('Auth:default#group');
    }

    /**
     * @param array $data
     * @throws AbortException
     */
    public function handleInviteFriend(array $data)
    {
        $out = $this->applicationsModel->InviteFriend($this->id, $data[0]['val']);
        $data = $this->applicationsModel->getInvitedFriends($this->id);
        $this->sendJson((object)[
            'success' => $out,
            'data' => $data
        ]);
    }
}
