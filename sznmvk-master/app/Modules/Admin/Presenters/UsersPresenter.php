<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\UserModel;
use App\Modules\Admin\Components\Users\AddUserForm\AddUserForm;
use App\Modules\Admin\Components\Users\AddUserForm\IAddUserFormFactory;
use App\Modules\Admin\Components\Users\EditUserForm\EditUserForm;
use App\Modules\Admin\Components\Users\EditUserForm\IEditUserFormFactory;
use App\Modules\Admin\Components\Users\UsersGrid\UsersGrid;
use App\Modules\Admin\Components\Users\UsersGrid\IUsersGridFactory;


class UsersPresenter extends BasePresenter
{
    /** @var IUsersGridFactory @inject */
    public IUsersGridFactory $usersGridFactory;

    /** @var IAddUserFormFactory @inject */
    public IAddUserFormFactory $addUserFormFactory;

    /** @var IEditUserFormFactory @inject */
    public IEditUserFormFactory $editUserFormFactory;

    /** @var UserModel @inject */
    public UserModel $userModel;

    /** @var int */
    public int $id;

    public function startup()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_INSTRUCTORS)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }

        parent::startup();
    }

    /**
     * @param int $id
     */
    public function actionEdit(int $id)
    {
        $this->id = $id;
        $this->template->instructor = $this->userModel->getInstructor($this->id);
    }

    /* COMPONENTS *************************************************************************************************** */

    /**
     * @return UsersGrid
     */
    public function createComponentUsersGrid(): UsersGrid
    {
        return $this->usersGridFactory->create();
    }

    /**
     * @return AddUserForm
     */
    public function createComponentAddUserForm(): AddUserForm
    {
        $form = $this->addUserFormFactory->create();
        $form->setRedirect('Users:default');
        return $form;
    }

    /**
     * @return EditUserForm
     */
    public function createComponentEditUserForm(): EditUserForm
    {
        $form = $this->editUserFormFactory->create($this->id);
        $form->setRedirect('Users:default');
        return $form;
    }
}