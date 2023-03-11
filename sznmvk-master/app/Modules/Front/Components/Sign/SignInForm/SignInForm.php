<?php

declare(strict_types=1);


namespace App\Modules\Front\Components\Sign\SignInForm;

use App\Classes\Nette\Security\Authenticator;
use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;

class SignInForm extends FormComponent
{
    /** @var Authenticator */
    protected Authenticator $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        parent::__construct();
        $this->authenticator = $authenticator;
    }


    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->render();
    }


    public function createComponentForm(): Form
    {
        $form = parent::createComponentForm();
        $form->addText('email', 'E-mail');
        $form->addPassword('password', 'Heslo');
        $form->addSubmit('send', 'Přihlásit se');

        return $form;
    }

    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws AbortException
     */
    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        try {
            $this->presenter->user->setAuthenticator($this->authenticator);
            $this->presenter->user->login($values->email, $values->password);
            $this->flashMessage('Přihlášení proběhlo úspěšně', 'success');
        }  catch (AuthenticationException $ex) {
            $form->addError($ex->getMessage());
            $this->flashMessage($ex->getMessage(), Flashes::FLASH_DANGER);
        }

        if (!$form->hasErrors()) {
            $this->redirect($this->redirectDest, $this->redirectArgs);
        } elseif ($this->presenter->isAjax()) {
            $this->redrawControl();
        } else {
            $this->presenter->redirect('this');
        }
    }
}

