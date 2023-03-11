<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\ApplicationsModel;
use App\Model\FacultiesModel;
use App\Model\GendersModel;
use App\Model\LodgingModel;
use App\Model\ParticipantsModel;
use App\Modules\Admin\Components\Participants\CampDataGrid\CampDataGrid;
use App\Modules\Admin\Components\Participants\CampDataGrid\ICampDataGridFactory;
use App\Modules\Admin\Components\Participants\FreeLodgingGrid\FreeLodgingGrid;
use App\Modules\Admin\Components\Participants\FreeLodgingGrid\IFreeLodgingGridFactory;
use App\Modules\Admin\Components\Participants\ParticipantsGrid\IParticipantsGridFactory;
use App\Modules\Admin\Components\Participants\ParticipantsGrid\ParticipantsGrid;
use App\Modules\Admin\Components\Participants\QueuesGrid\IQueuesGridFactory;
use App\Modules\Admin\Components\Participants\QueuesGrid\QueuesGrid;
use App\Modules\Admin\Components\Participants\SwapLodgingGrid\ISwapLodgingGridFactory;
use App\Modules\Admin\Components\Participants\SwapLodgingGrid\SwapLodgingGrid;
use App\Modules\Admin\Components\Participants\ZajemciGrid\IZajemciGridFactory;
use App\Modules\Admin\Components\Participants\ZajemciGrid\ZajemciGrid;

class ParticipantsPresenter extends BasePresenter
{
    /** @var IParticipantsGridFactory @inject */
    public IParticipantsGridFactory $participantsGridFactory;

    /** @var IQueuesGridFactory @inject */
    public IQueuesGridFactory $queuesGridFactory;

    /** @var IZajemciGridFactory @inject */
    public IZajemciGridFactory $zajemciGridFactory;

    /** @var IFreeLodgingGridFactory @inject */
    public IFreeLodgingGridFactory $freeLodgingGridFactory;

    /** @var ISwapLodgingGridFactory @inject */
    public ISwapLodgingGridFactory $swapLodgingGridFactory;

    /** @var ICampDataGridFactory @inject */
    public ICampDataGridFactory $campDataGridFactory;

    /** @var ApplicationsModel @inject */
    public ApplicationsModel $applicationsModel;
    
    /** @var GendersModel @inject */
    public GendersModel $gendersModel;

    /** @var FacultiesModel @inject */
    public FacultiesModel $facultiesModel;

    /** @var ParticipantsModel @inject */
    public ParticipantsModel $participantsModel;

    /** @var LodgingModel @inject */
    public LodgingModel $lodgingModel;

    protected int $id;


    public function actionDefault()
    {
        $this->template->stats = $this->participantsModel->getParticipantsStats();
        $this->template->statsSessionNull = $this->participantsModel->getParticipantsStatsNullSession();
    }


    public function actionConfirmed()
    {
        if ($this->user->isAllowed())
        {
            $this->template->isAllowed =  true;
        }
        else
        {
            $this->template->isAllowed = false;
        }
    }

    public function actionLockLodging($id)
    {
        $this->lodgingModel->setLock(intval($id));
        $this->redirect('Participants:changeLodging',$id);
    }

    public function actionUnsetLodging($id)
    {
        $this->lodgingModel->unsetLodging(intval($id),$this->user->id);
        $this->redirect('Participants:changeLodging',$id);
    }

    public function actionChangeLodging(int $id)
    {
        if (!$this->user->isAllowed()) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }
        $this->id =$id;
        $this->template->application = $application = $this->applicationsModel->getApplication($this->id);
        $this->template->faculty = $this->facultiesModel->getSchoolCodeAndFacultyName($application['faculty']);
        $this->template->gender = $this->gendersModel->getNameById($application['gender']);
        $this->template->lodging = $this->lodgingModel->getActualLodgingById($this->id);
        $this->template->mates = $this->lodgingModel->getActualMateById($this->id);
    }

    public function actionMakeChange(int $lodId,int $userId)
    {
        $this->lodgingModel->makeChange($userId,$lodId,$this->user->id);
        $this->redirect('Participants:changeLodging',[$userId]);

    }

    public function actionMakeSwap(int $lod1Id,int $user1Id,int $user2Id,int $lod2Id,)
    {
        $this->lodgingModel->makeChange($user1Id,$lod1Id,$this->user->id);
        $this->lodgingModel->makeChange($user2Id,$lod2Id,$this->user->id);
        $this->redirect('Participants:changeLodging',[$user1Id]);

    }

    public function actionCampData()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_CAMP_DATA)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }
    }


    /**
     * @return ParticipantsGrid
     */
    public function createComponentParticipantsGrid()
    {
        return $this->participantsGridFactory->create();
    }

    /**
     * @return QueuesGrid
     */
    public function createComponentQueuesGrid()
    {
        return $this->queuesGridFactory->create();
    }

    /**
     * @return ZajemciGrid
     */
    public function createComponentZajemciGrid()
    {
        return $this->zajemciGridFactory->create();
    }

    /**
     * @return CampDataGrid
     */
    public function createComponentCampDataGrid()
    {
        return $this->campDataGridFactory->create();
    }

    /**
     * @return FreeLodgingGrid
     */
    public function createComponentFreeLodgingGrid()
    {
        return $this->freeLodgingGridFactory->create($this->id);
    }

    /**
     * @return SwapLodgingGrid
     */
    public function createComponentSwapLodgingGrid()
    {
        return $this->swapLodgingGridFactory->create($this->id);
    }


   /**
     * @secured
     */
    public function handlestartLodgingScript(int $session)
    {
        if (!$this->user->isAllowed()) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }
        $this->lodgingModel->setLodgingForAllScript($session);

        $this->redirect('Participants:confirmed');
    }

    /**
     * @secured
     */
    public function handleReset(int $session)
    {
        if (!$this->user->isAllowed()) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }
        $this->lodgingModel->unsetLodging(0,$this->user->id,1,$session);

        $this->redirect('Participants:confirmed');
    }


}
