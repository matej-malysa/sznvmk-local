<?php
declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use App\Modules\Front\Components\ForgottenForm\ForgottenForm;
use App\Modules\Front\Components\ForgottenForm\IForgottenFormFactory;
use App\Modules\Front\Components\PrihlaskaRegistrationForm\IPrihlaskaRegistrationFormFactory;
use App\Modules\Front\Components\PrihlaskaRegistrationForm\PrihlaskaRegistrationForm;
use App\Modules\Front\Components\Sign\SignInForm\ISignInFormFactory;
use App\Modules\Front\Components\Sign\SignInForm\SignInForm;

class PrihlaskaPresenter extends BasePresenter
{
    /** @var ISignInFormFactory @inject */
    public ISignInFormFactory $signInFormFactory;

    /** @var IPrihlaskaRegistrationFormFactory @inject */
    public IPrihlaskaRegistrationFormFactory $prihlaskaRegistrationFormFactory;

    /** @var IForgottenFormFactory @inject */
    public IForgottenFormFactory $forgottenFormFactory;

    // ACTION -------------------------------------------------------------------------------------------------------


//    public function actionRestore(string $id)
//    {
//        $this->restoreRequest($id);
//        $this->redirect('in'); // In case restore request fails
//    }


    /**
     * @return SignInForm
     */
    public function createComponentSignInForm(): SignInForm
    {
        $form = $this->signInFormFactory->create();
        $form->setRedirect('Auth:default');
        return $form;
    }

    /**
     * @return PrihlaskaRegistrationForm
     */
    public function createComponentPrihlaskaRegistrationForm(): PrihlaskaRegistrationForm
    {
        return $this->prihlaskaRegistrationFormFactory->create();
    }

    /**
     * @return ForgottenForm
     */
    public function createComponentForgottenForm(): ForgottenForm
    {
        $form = $this->forgottenFormFactory->create();
        $form->setRedirect('Prihlaska:default');
        return $form;
    }


}