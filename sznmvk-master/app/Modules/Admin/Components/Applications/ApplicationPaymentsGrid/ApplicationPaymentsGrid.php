<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Applications\ApplicationPaymentsGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\PaymentsModel;
use Ublaboo\DataGrid\DataGrid;

class ApplicationPaymentsGrid extends GridComponent
{
    /* @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    /** @var int */
    protected int $applicationId;

    public function __construct(PaymentsModel $paymentsModel, int $applicationId)
    {
        parent::__construct();
        $this->paymentsModel = $paymentsModel;
        $this->applicationId = $applicationId;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('application_id');
        $grid->setDataSource($this->paymentsModel->getPaymentsForApplication($this->applicationId));
        $grid->setPagination(false);

        $grid->addColumnText('id', 'ID platby');
        $grid->addColumnText('account_number', 'Číslo účtu');
        $grid->addColumnText('bank_code', 'Kód banky');
        $grid->addColumnText('account_name', 'Název účtu');
        $grid->addColumnText('amount', 'Částka');
        $grid->addColumnText('vs', 'Variabilní symbol');
        $grid->addColumnDateTime('payment_date', 'Datum transakce');


        return $grid;
    }
}