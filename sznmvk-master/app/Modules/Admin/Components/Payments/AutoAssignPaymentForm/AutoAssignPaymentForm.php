<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\AutoAssignPaymentForm;

use App\Components\Flashes\Flashes;
use App\Components\FormComponent;
use App\Model\ApplicationsModel;
use App\Model\PaymentsModel;
use Dibi\Exception;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class AutoAssignPaymentForm extends FormComponent
{
    /** @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    protected array $payments;

    public function __construct(PaymentsModel $paymentsModel, ApplicationsModel $applicationsModel)
    {
        parent::__construct();
        $this->paymentsModel = $paymentsModel;
        $this->applicationsModel = $applicationsModel;
        $this->payments = $this->paymentsModel->getAvailableForAutoAssign();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/form.latte');
        $this->template->payments = $this->payments;
        $this->template->render();
    }

    public function createComponentForm()
    {
        $form = parent::createComponentForm();

        $checkboxList = [];
        foreach ($this->payments as $payment) {
            $checkboxList[$payment['paymentId']] = $payment['firstname'] . ' ' . $payment['lastname'] . ' - (vs: ' . $payment['vs'] . ', tel: ' . $payment['phone'] . ')';
        }

        $form->addCheckboxList('payments', null, $checkboxList);
        $form->addSubmit('send', 'Přiřadit platby');
        $form->addSubmit('cancel', 'Zrušit')
            ->setValidationScope([])
            ->onClick[] = [$this, 'cancelClicked'];


        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values)
    {
        try {
            foreach ($values->payments as $key => $paymentId) {
                $applicationId = $this->payments[$paymentId]->applicationId;
                $this->paymentsModel->assignPayment($paymentId, $applicationId, $this->presenter->getUser()->id);
            }
            $this->flashMessage('Všechny platby úspěšně přiřazeny', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage("Chyba při přiřazení platby: " . $ex->getMessage(), Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}