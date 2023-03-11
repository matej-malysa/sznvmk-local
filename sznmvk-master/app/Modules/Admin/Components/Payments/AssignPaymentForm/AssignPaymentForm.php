<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\AssignPaymentForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\ApplicationsModel;
use App\Model\PaymentsModel;
use Dibi\Exception;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class AssignPaymentForm extends FormComponent
{
    /** @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    /** @var int */
    protected int $paymentId;

    /** @var array */
    protected array $applicationsToSelect;

    public function __construct(PaymentsModel $paymentsModel, ApplicationsModel $applicationsModel, int $id)
    {
        parent::__construct();
        $this->paymentsModel = $paymentsModel;
        $this->applicationsModel = $applicationsModel;
        $this->applicationsToSelect = $this->applicationsModel->getApplicationsToSelect();
        $this->paymentId = $id;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->applicationsToSelect = $this->applicationsToSelect;
        $this->template->payment = $this->paymentsModel->getDetails($this->paymentId);
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();
        $form->addSelect('application', 'Přihláška', $this->applicationsToSelect);
        $form->addSubmit('submit', 'Přiřadit platbu');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];

        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            $this->paymentsModel->assignPayment($this->paymentId, $values->application, $this->presenter->user->getId());
            $this->flashMessage('Platba úspěšně přiřazena k přihlášce', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage("Chyba při přiřazení platby: " . $ex->getMessage(), Flashes::FLASH_DANGER);
        }
        
        $this->finishHandler();
    }
}