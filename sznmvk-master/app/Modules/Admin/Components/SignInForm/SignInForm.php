<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\SignInForm;

use App\Classes\Nette\Security\AdminAuthenticator;
use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;

class SignInForm extends FormComponent
{
    /** @var AdminAuthenticator */
    protected AdminAuthenticator $adminAuthenticator;

    public function __construct(AdminAuthenticator $adminAuthenticator)
    {
        parent::__construct();
        $this->adminAuthenticator = $adminAuthenticator;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('username', 'Uživatelské jméno')->setRequired();
        $form->addPassword('password', 'Heslo');
        $form->addSubmit('submit', 'Přihlásit se');

        return $form;
    }

    /**
     * @param Form $form
     * @param ArrayHash $values
     * @throws AbortException
     */
    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            $this->presenter->user->setAuthenticator($this->adminAuthenticator);
            $this->presenter->user->login($values->username, $values->password);
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