<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\PaymentsGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\PaymentsModel;
use Ublaboo\DataGrid\DataGrid;

class PaymentsGrid extends GridComponent
{
    /** @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    public function __construct(PaymentsModel $paymentsModel)
    {
        parent::__construct();
        $this->paymentsModel = $paymentsModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setDataSource($this->paymentsModel->getGrid());
        $grid->setDefaultSort(['status' => 'DESC']);

        $grid->addColumnNumber('id', 'Interní ID')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('statusName', 'Stav')->setSortable()
            ->setFilterSelect($this->paymentsModel->getStatusesForSelect(), 'status')
            ->setPrompt('');
        $grid->addColumnText('account_number', 'Číslo účtu')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('bank_code', 'Kód banky')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('account_name', 'Název účtu')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('amount', 'Částka')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('vs', 'Variabilní symbol')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnDateTime('payment_date', 'Datum transakce')->setSortable();

        $grid->addAction('assignPayment', 'Přiřadit platbu')
            ->setClass('btn btn-xs btn-warning ajax')
            ->setIcon('exchange-alt');
        $grid->allowRowsAction('assignPayment', function($item) {
            return $item->status === 1;
        });

        $grid->addAction('detail', 'Detail platby')
            ->setClass('btn btn-xs btn-info ajax')
            ->setIcon('search');
        $grid->allowRowsAction('detail', function($item) {
            return $item->status !== 1;
        });

        return $grid;
    }

    /**
     * @param int $id
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleAssignPayment(int $id)
    {
        $this->presenter->redirect('Payments:assign', $id);
    }

    /**
     * @param int $id
     * @throws \Nette\Application\AbortException
     * @secured
     */
    public function handleDetail(int $id)
    {
        $this->presenter->redirect('Payments:detail', $id);
    }
}