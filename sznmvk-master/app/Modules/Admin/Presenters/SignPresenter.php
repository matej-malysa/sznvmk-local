<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Modules\Admin\Components\SignInForm\ISignInFormFactory;
use App\Modules\Admin\Components\SignInForm\SignInForm;

class SignPresenter extends BasePresenter
{
    public function actionIn()
    {
        $this->template->hideMenu = true;
    }

    /** @var ISignInFormFactory @inject */
    public $signInFormFactory;

    public function createComponentSignInForm(): SignInForm
    {
        $form = $this->signInFormFactory->create();
        $form->setRedirect(':Admin:Dashboard:default');
        return $form;
    }
}