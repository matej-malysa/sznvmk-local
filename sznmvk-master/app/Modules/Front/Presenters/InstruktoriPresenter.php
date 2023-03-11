<?php

declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use App\Model\UserModel;

class InstruktoriPresenter extends BasePresenter
{
    /** @var UserModel @inject */
    public $userModel;

    public function actionDefault()
    {
        $this->template->instruktori = $instruktori = $this->userModel->getInstructorsForPage();
        $this->template->profilePics = $this->userModel->getProfilePics($instruktori);
    }
}