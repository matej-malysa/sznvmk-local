<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\EditLodgingForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\LodgingModel;
use Dibi\Exception;
use App\Modules\Admin\Components\Settings\AddLodgingForm\AddLodgingForm;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class EditLodgingForm extends AddLodgingForm
{
    protected int $lodgingId;
    
    public function __construct( int $lodgingId, LodgingModel $lodgingModel)
    {
        parent::__construct($lodgingModel);
        $this->lodgingId = $lodgingId;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->lodgingTypeToSelect = $this->lodgingModel->getTypeToSelect();
        $this->template->lodgingSession1ToSelect = [0 => 'Zamietnuté', 1 => 'Povolené'];
        $this->template->lodgingSession1UseToSelect =  $this->lodgingModel->getUseToSelect();
        $this->template->lodgingSession2ToSelect = [0 => 'Zamietnuté', 1 => 'Povolené'];
        $this->template->lodgingSession2UseToSelect =  $this->lodgingModel->getUseToSelect();
        $this->template->cnt1 = $this->lodgingModel->getLodgingById($this->lodgingId)['cnt1'];
        $this->template->cnt2 = $this->lodgingModel->getLodgingById($this->lodgingId)['cnt2'];
        $this->template->render();
    }
    
    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->setDefaults($this->lodgingModel->getLodgingById($this->lodgingId));

        
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            //$values['full_capacity'] = $values['first_lodging_capacity'] + $values['second_lodging_capacity'] + $values['third_lodging_capacity'];
            $this->lodgingModel->editLodging($this->lodgingId,$values);
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