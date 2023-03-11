<?php
declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Classes\Exceptions\AddNewPaymentException;
use App\Classes\Nette\Security\Authorizator;
use App\Components\Flashes\Flashes;
use App\Model\PaymentsModel;
use App\Modules\Admin\Components\Payments\AssignPaymentForm\AssignPaymentForm;
use App\Modules\Admin\Components\Payments\AssignPaymentForm\IAssignPaymentFormFactory;
use App\Modules\Admin\Components\Payments\AutoAssignPaymentForm\AutoAssignPaymentForm;
use App\Modules\Admin\Components\Payments\AutoAssignPaymentForm\IAutoAssignPaymentFormFactory;
use App\Modules\Admin\Components\Payments\PaymentsGrid\IPaymentsGridFactory;
use App\Modules\Admin\Components\Payments\PaymentsGrid\PaymentsGrid;
use App\Modules\Admin\Components\Payments\PaymentsLogGrid\IPaymentsLogGridFactory;
use App\Modules\Admin\Components\Payments\PaymentsLogGrid\PaymentsLogGrid;
use Dibi\Exception;
use h4kuna\Fio\Nette\FioFactory;
use Nette\Schema\ValidationException;

class PaymentsPresenter extends BasePresenter
{
    /** @var IPaymentsGridFactory @inject */
    public IPaymentsGridFactory $paymentsGridFactory;

    /** @var IAssignPaymentFormFactory @inject */
    public IAssignPaymentFormFactory $assignPaymentFormFactory;

    /** @var IAutoAssignPaymentFormFactory @inject */
    public IAutoAssignPaymentFormFactory $autoAssignPaymentFormFactory;

    /** @var IPaymentsLogGridFactory @inject */
    public IPaymentsLogGridFactory $paymentsLogGridFactory;

    /** @var FioFactory @inject */
    public FioFactory $fioFactory;

    /** @var PaymentsModel @inject */
    public PaymentsModel $paymentsModel;

    /** @var int */
    public int $paymentId;

    public function startup()
    {
        if (!$this->user->isAllowed(Authorizator::RESOURCE_PAYMENTS)) {
            $this->flashMessage('Nemáte dostatečné oprávnění pro přístup do této sekce', Flashes::FLASH_DANGER);
            $this->redirect('Dashboard:default');
        }

        parent::startup();
    }

    /**
     * @param int $id
     */
    public function actionAssign(int $id)
    {
        $this->paymentId = $id;
    }

    /**
     * @param int $id
     */
    public function actionDetail(int $id)
    {
        $this->paymentId = $id;
        $this->template->payment = $payment = $this->paymentsModel->getDetails($this->paymentId);
        $this->template->paymentStatus = $this->paymentsModel->getPaymentsStatusName($payment['status']);
    }

    /**
     * @return PaymentsGrid
     */
    public function createComponentPaymentsGrid(): PaymentsGrid
    {
        return $this->paymentsGridFactory->create();
    }

    /**
     * @return AssignPaymentForm
     */
    public function createComponentAssignPaymentForm(): AssignPaymentForm
    {
        $form = $this->assignPaymentFormFactory->create($this->paymentId);
        $form->setRedirect('Payments:default');
        return $form;
    }

    /**
     * @return AutoAssignPaymentForm
     */
    public function createComponentAutoAssignPaymentForm(): AutoAssignPaymentForm
    {
        $form = $this->autoAssignPaymentFormFactory->create();
        $form->setRedirect('Payments:default');
        return $form;
    }

    public function createComponentPaymentsLogGrid(): PaymentsLogGrid
    {
        return $this->paymentsLogGridFactory->create($this->paymentId);
    }

    /**
     * @secured
     */
    public function handleLoadPayments()
    {
        $lastMoveId = $this->paymentsModel->getLastMoveId();
        $fio = $this->fioFactory->createFioRead();
        // @TODO rework date into DB important_dates in all frontend places as well
        $all = $fio->movements('2022-04-01');
        $counter = 0;
        try {
            foreach ($all as $single) {
                if ($single->moveId <= $lastMoveId) {
                    continue;
                } elseif (is_null($single->toAccount)) {
                    continue;
                } elseif (in_array(sprintf('%s/%s', $single->toAccount, $single->bankCode), PaymentsModel::OUR_ACCOUNTS)) {
                    continue;
                } elseif ($single->volume < 0) {
                    continue;
                } else {
                    $counter++;
                    $this->paymentsModel->addNewPayment($single->getProperties());
                }
            }
            $this->flashMessage("Nahrávání nových plateb úspěšně dokončeno. Nahráno $counter nových plateb", Flashes::FLASH_SUCCESS);
        } catch (AddNewPaymentException|Exception $ex) {
            $this->flashMessage("Chyba při nahrávání nových plateb z banky - nahrávání přerušeno. Nahráno $counter nových plateb", Flashes::FLASH_DANGER);
//            @TODO logger
//            $this->logError($ex);
        } catch (ValidationException $ex) {
            $this->flashMessage("Chyba při nahrávání nových plateb z banky, nesprávný formát příchozích dat - nahrávání přerušeno. Nahráno $counter nových plateb", Flashes::FLASH_DANGER);
        }
        $this->presenter->redirect('this');
    }
}