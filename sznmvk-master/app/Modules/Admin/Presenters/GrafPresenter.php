<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\UserModel;
use App\Modules\Admin\Components\Instructors\AddInstructorForm\AddInstructorForm;
use App\Modules\Admin\Components\Instructors\AddInstructorForm\IAddUserFormFactory;
use App\Modules\Admin\Components\Instructors\EditInstructorForm\EditUserForm;
use App\Modules\Admin\Components\Instructors\EditInstructorForm\IEditUserFormFactory;
use App\Modules\Admin\Components\Graf\GrafGrid\IGrafGridFactory;
use App\Modules\Admin\Components\Graf\GrafGrid\GrafGrid;

class GrafPresenter extends BasePresenter
{
    /** @var IGrafGridFactory @inject */
    public IGrafGridFactory $grafGridFactory;


    public function startup()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_INSTRUCTORS)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }

        parent::startup();
    }


    /* COMPONENTS *************************************************************************************************** */

    /**
     * @return GrafGrid
     */
    public function createComponentGrafGrid(): GrafGrid
    {
        return $this->grafGridFactory->create();
    }



}