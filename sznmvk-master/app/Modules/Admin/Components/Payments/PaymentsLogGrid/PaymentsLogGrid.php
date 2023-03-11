<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\PaymentsLogGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\PaymentsModel;
use Ublaboo\DataGrid\DataGrid;

class PaymentsLogGrid extends GridComponent
{
    protected PaymentsModel $paymentsModel;
    protected int $paymentId;

    public function __construct(PaymentsModel $paymentsModel, int $paymentId)
    {
        parent::__construct();
        $this->paymentsModel = $paymentsModel;
        $this->paymentId = $paymentId;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('payment_id');
        $grid->setDataSource($this->paymentsModel->getPaymentsLogGrid($this->paymentId));
        $grid->setPagination(false);
        $grid->addColumnDateTime('created_at', 'Kdy')
            ->setFormat('j. n. Y H:i:s')
            ->setAlign('left');
        $grid->addColumnText('text', 'Akce');
        $grid->addColumnText('old_value', 'Původní hodnota');
        $grid->addColumnText('new_value', 'Nová hodnota');
        $grid->addColumnText('username', 'Kým')
            ->setReplacement([
                'system' => 'Automaticky/Uživatelem',
            ]);

        return $grid;
    }
}