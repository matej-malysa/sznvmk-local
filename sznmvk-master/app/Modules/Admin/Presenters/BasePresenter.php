<?php
declare(strict_types=1);


namespace App\Modules\Admin\Presenters;

use Carrooi\Menu\UI\MenuComponent;

abstract class BasePresenter extends \App\Modules\BasePresenter
{
    const MENU_NAME = 'admin';

    public function startup()
    {
        parent::startup();

        if ($this->name != 'Admin:Sign' && !$this->user->isLoggedIn()) {
            $this->redirect(':Admin:Sign:In');
        }
    }


    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect(':Admin:Sign:in');
    }


    /**
     * @return MenuComponent
     */
    protected function createComponentMenu()
    {
        return $this->menuFactory->create(self::MENU_NAME);
    }
}
