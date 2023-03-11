<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\AddSessionForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\SessionsModel;
use Dibi\Exception;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class AddSessionForm extends FormComponent
{
    /** @var SessionsModel */
    protected $sessionsModel;
    
    public function __construct(SessionsModel $sessionsModel)
    {
        parent::__construct();
        $this->sessionsModel = $sessionsModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->render();
    }
    
    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('title', 'Označení')->setRequired();
        $form->addText('start', 'Začátek')->setRequired();
        $form->addText('end', 'Konec')->setRequired();
        $form->addInteger('first_lodging_capacity', 'Primární ubytování')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setRequired();
        $form->addInteger('second_lodging_capacity', 'Sekundární ubytování')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setRequired();
        $form->addInteger('third_lodging_capacity', 'Terciární ubytování')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setRequired();
        $form->addInteger('instructors_capacity', 'Míst pro instruktory')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setDefaultValue(0);
        $form->addInteger('guest_capacity', 'Míst pro hosty')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setDefaultValue(0);

        $form->addSubmit('send', 'Uložit');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];
        
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            $values['full_capacity'] = $values['first_lodging_capacity'] + $values['second_lodging_capacity'] + $values['third_lodging_capacity'];
            $this->sessionsModel->addSession($values);
            $this->flashMessage('Turnus úspěšně přidán', Flashes::FLASH_SUCCESS);
        } catch (Exception $e) {
            $this->flashMessage('Chyba při ukládání turnusu. Turnus nebyl přidán.', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}