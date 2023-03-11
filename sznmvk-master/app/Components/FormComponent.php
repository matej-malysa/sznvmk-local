<?php
declare(strict_types=1);

namespace App\Components;

use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Forms\IFormRenderer;
use Nette\Forms\Validator;

/**
 * Class FormComponent
 * @package App\Components
 * @property bool $ajax Should for use ajax?
 */
abstract class FormComponent extends AppComponent implements IFormComponent
{
    /** @var bool */
    private $ajax = false;

    /** @var null|IFormRenderer */
    private $renderer;

    /** @var string */
    protected $cancelDest = '';

    /** @var array */
    protected $cancelArgs = [];

    public function __construct()
    {
        parent::__construct();

        Validator::$messages[Form::FILLED] = 'Toto pole je povinné';
        Validator::$messages[Form::INTEGER] = 'Prosím zadejte platné celé číslo';
        Validator::$messages[Form::FLOAT] = 'Prosím zadejte platné desetinné číslo';
        Validator::$messages[Form::EMAIL] = 'Prosím zadejte platnou e-mailovou adresu';
    }

    /**
     * @return null|IFormRenderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->ajax;
    }

    /**
     * @param bool $ajax
     */
    public function setAjax(bool $ajax = true)
    {
        $this->ajax = $ajax;
    }

    /**
     * @param string $redirectDest
     * @param array  $redirectArgs
     */
    public function setCancelRedirect(string $redirectDest, array $redirectArgs = [])
    {
        $this->cancelDest = $redirectDest;
        $this->cancelArgs = $redirectArgs;
    }

    public function createComponentForm()
    {
        $form = new Form();

        $form->setRenderer($this->renderer);
        $form->onSuccess[] = [$this, 'formSucceeded'];

        if ($this->isAjax()) {
            $form->getElementPrototype()->class[] = 'ajax';
        }

        return $form;
    }

    /**
     * @throws AbortException
     */
    public function cancelClicked()
    {
        if (empty($this->cancelDest)) {
            $this->presenter->redirect($this->redirectDest, $this->redirectArgs);
        } else {
            $this->presenter->redirect($this->cancelDest, $this->cancelArgs);
        }
    }
}
