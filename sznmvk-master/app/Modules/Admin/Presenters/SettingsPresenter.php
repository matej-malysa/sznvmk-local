<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\UserModel;
use App\Model\LodgingModel;
use App\Model\ActivitiesModel;
use App\Modules\Admin\Components\Settings\ActivitiesGrid\ActivitiesGrid;
use App\Modules\Admin\Components\Settings\ActivitiesGrid\IActivitiesGridFactory;
use App\Modules\Admin\Components\Settings\ActivityDetailGrid\ActivityDetailGrid;
use App\Modules\Admin\Components\Settings\ActivityDetailGrid\IActivityDetailGridFactory;
use App\Modules\Admin\Components\Settings\AddLodgingForm\AddLodgingForm;
use App\Modules\Admin\Components\Settings\AddLodgingForm\IAddLodgingFormFactory;
use App\Modules\Admin\Components\Settings\AddSessionForm\AddSessionForm;
use App\Modules\Admin\Components\Settings\AddSessionForm\IAddSessionFormFactory;
use App\Modules\Admin\Components\Settings\BonusesGrid\BonusesGrid;
use App\Modules\Admin\Components\Settings\BonusesGrid\IBonusesGridFactory;
use App\Modules\Admin\Components\Settings\EditLodgingForm\EditLodgingForm;
use App\Modules\Admin\Components\Settings\EditLodgingForm\IEditLodgingFormFactory;
use App\Modules\Admin\Components\Settings\FacultiesGrid\FacultiesGrid;
use App\Modules\Admin\Components\Settings\FacultiesGrid\IFacultiesGridFactory;
use App\Modules\Admin\Components\Settings\ImportantDatesGrid\IImportantDatesGridFactory;
use App\Modules\Admin\Components\Settings\ImportantDatesGrid\ImportantDatesGrid;
use App\Modules\Admin\Components\Settings\LodgingGrid\ILodgingGridFactory;
use App\Modules\Admin\Components\Settings\LodgingGrid\LodgingGrid;
use App\Modules\Admin\Components\Settings\PaymentsLimitsGrid\IPaymentsLimitsGridFactory;
use App\Modules\Admin\Components\Settings\PaymentsLimitsGrid\PaymentsLimitsGrid;
use App\Modules\Admin\Components\Settings\SessionsGrid\ISessionsGridFactory;
use App\Modules\Admin\Components\Settings\SessionsGrid\SessionsGrid;
use App\Modules\Admin\Components\Settings\TransportsGrid\ITransportsGridFactory;
use App\Modules\Admin\Components\Settings\TransportsGrid\TransportsGrid;

class SettingsPresenter extends BasePresenter
{
    /** @var IAddLodgingFormFactory @inject */
    public IAddLodgingFormFactory $addLodgingFormFactory;

    /** @var IAddSessionFormFactory @inject */
    public IAddSessionFormFactory $addSessionFormFactory;

    /** @var IEditLodgingFormFactory @inject */
    public IEditLodgingFormFactory $editLodgingFormFactory;

    /** @var IFacultiesGridFactory @inject */
    public IFacultiesGridFactory $facultiesGridFactory;

    /** @var ISessionsGridFactory @inject */
    public ISessionsGridFactory $sessionsGridFactory;

    /** @var IBonusesGridFactory @inject */
    public IBonusesGridFactory $bonusesGridFactory;

    /** @var ITransportsGridFactory @inject */
    public ITransportsGridFactory $transportsGridFactory;

    /** @var IImportantDatesGridFactory @inject */
    public IImportantDatesGridFactory $importantDatesGridFactory;

    /** @var IPaymentsLimitsGridFactory @inject */
    public IPaymentsLimitsGridFactory $paymentsLimitsGridFactory;

    /** @var ILodgingGridFactory @inject */
    public ILodgingGridFactory $lodgingGridFactory;

    /** @var IActivitiesGridFactory @inject */
    public IActivitiesGridFactory $activitiesGridFactory;

    /** @var IActivityDetailGridFactory @inject */
    public IActivityDetailGridFactory $activityDetailGridFactory;

    protected int $lodgingId;

    /** @var UserModel @inject */
    public UserModel $userModel;

    /** @var LodgingModel @inject */
    public LodgingModel $lodgingModel;

    /** @var ActivitiesModel @inject */
    public ActivitiesModel $activitiesModel;

    public function startup()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_SETTINGS)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }

        parent::startup();
    }

    public function actionLodging()
    {
        $this->template->actualBungalov_1 = ($data = $this->lodgingModel->getCountofBeds())[0];
        $this->template->setBungalov_1 = $data[1];
        $this->template->actualChatka_1 = $data[2];
        $this->template->setChatka_1 = $data[3];
        $this->template->actualBungalov_2 = $data[4];
        $this->template->setBungalov_2 = $data[5];
        $this->template->actualChatka_2 = $data[6];
        $this->template->setChatka_2 = $data[7];

    }

    /**
     * @return AddLodgingForm
     */
    public function createComponentAddLodgingForm(): AddLodgingForm
    {
        $form = $this->addLodgingFormFactory->create();
        $form->setRedirect('Settings:lodging');
        return $form;
    }

    /**
     * @return AddSessionForm
     */
    public function createComponentAddSessionForm(): AddSessionForm
    {
        $form = $this->addSessionFormFactory->create();
        $form->setRedirect('Settings:sessions');
        return $form;
    }

    public function actionEditLodging(int $id = 0)
    {

        $this->lodgingId = $id;
    }

    /**
     * @return EditLodgingForm
     */
    public function createComponentEditLodgingForm(): EditLodgingForm
    {
        $form = $this->editLodgingFormFactory->create( $this->lodgingId);
        $form->setRedirect('Settings:lodging');
        return $form;
    }

    /**
     * @return FacultiesGrid
     */
    public function createComponentFacultiesGrid(): FacultiesGrid
    {
        return $this->facultiesGridFactory->create();
    }

    /**
     * @return SessionsGrid
     */
    public function createComponentSessionsGrid(): SessionsGrid
    {
        return $this->sessionsGridFactory->create();
    }

    /**
     * @return BonusesGrid
     */
    public function createComponentBonusesGrid(): BonusesGrid
    {
        return $this->bonusesGridFactory->create();
    }

    /**
     * @return TransportsGrid
     */
    public function createComponentTransportsGrid(): TransportsGrid
    {
        return $this->transportsGridFactory->create();
    }

    /**
     * @return ImportantDatesGrid
     */
    public function createComponentImportantDatesGrid(): ImportantDatesGrid
    {
        return $this->importantDatesGridFactory->create();
    }

    /**
     * @return PaymentsLimitsGrid
     */
    public function createComponentPaymentsLimitsGrid(): PaymentsLimitsGrid
    {
        return $this->paymentsLimitsGridFactory->create();
    }

      /**
     * @return LodgingGrid
     */
    public function createComponentLodgingGrid()
    {

        return $this->lodgingGridFactory->create();
    }

    /**
     * @return ActivitiesGrid
     */
    public function createComponentActivitiesGrid(): ActivitiesGrid
    {
        return $this->activitiesGridFactory->create();
    }

    /**
     * @return ActivityDetailGrid
     */
    public function createComponentActivityDetailGrid(/*$activity_id*/): ActivityDetailGrid
    {
        $request = $this->getRequest();
        return $this->activityDetailGridFactory->create((int) $request->getParameter('id'));
    }

    public function actionActivityDetail(int $id)
    {
        $this->template->id = $id;
        $this->template->activity = $this->activitiesModel->getNameById($id);
    }
}
