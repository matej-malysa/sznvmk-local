<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\AddLodgingForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\LodgingModel;
use Dibi\Exception;
use mysql_xdevapi\Session;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class AddLodgingForm extends FormComponent
{
    /** @var LodgingModel */
    protected $lodgingModel;
    
    public function __construct(LodgingModel $lodgingModel)
    {
        parent::__construct();
        $this->lodgingModel = $lodgingModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->lodgingTypeToSelect = $this->lodgingModel->getTypeToSelect();
        $this->template->lodgingSession1ToSelect = [0 => 'Zamietnuté', 1 => 'Povolené'];
        $this->template->lodgingSession1UseToSelect =  $this->lodgingModel->getUseToSelect();
        $this->template->lodgingSession2ToSelect = [0 => 'Zamietnuté', 1 => 'Povolené'];
        $this->template->lodgingSession2UseToSelect =  $this->lodgingModel->getUseToSelect();
        $this->template->render();
    }
    
    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addText('name', 'Názov')->setRequired('meno');
        $form->addRadioList('type','Typ ubytování',$this->lodgingModel->getTypeToSelect())->setRequired();
        $form->addInteger('capacity', 'Kapacita')
            ->addRule(Form::MIN, 'Zadejte kladné číslo', 0)
            ->setRequired();
        $form->addRadioList('session_1','Turnus 1', [0 => 'Zamietnuté', 1 => 'Povolené'])->setRequired();
        $form->addRadioList('session_1_use','Turnus 1 využití',$this->lodgingModel->getUseToSelect())
            ->addConditionOn($form['session_1'],$form::EQUAL,1)->setRequired();
        $form->addRadioList('session_2','Turnus 2',[0 => 'Zamietnuté', 1 => 'Povolené'])->setRequired();
        $form->addRadioList('session_2_use','Turnus 2 využití',$this->lodgingModel->getUseToSelect())
            ->addConditionOn($form['session_2'],$form::EQUAL,1)->setRequired();



        $form->addSubmit('send', 'Uložit');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];
        
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            //$values['full_capacity'] = $values['first_lodging_capacity'] + $values['second_lodging_capacity'] + $values['third_lodging_capacity'];
            $this->lodgingModel->addLodging($values);
            $this->flashMessage('Turnus úspěšně přidán', Flashes::FLASH_SUCCESS);
        } catch (Exception $e) {
            $this->flashMessage('Chyba při ukládání turnusu. Turnus nebyl přidán.', Flashes::FLASH_DANGER);
        }

        if (!$form->hasErrors()) {
            $this->finishHandler();
        } else {
            $this->redrawControl();
        }
    }
}