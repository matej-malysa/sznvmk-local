<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\PaymentsLimitsGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\PaymentsLimitsModel;
use Dibi\Exception;
use Nette\Forms\Container;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

class PaymentsLimitsGrid extends GridComponent
{
    /** @var PaymentsLimitsModel */
    protected PaymentsLimitsModel $paymentsLimitsModel;


    public function __construct(PaymentsLimitsModel $paymentsLimitsModel)
    {
        parent::__construct();
        $this->paymentsLimitsModel = $paymentsLimitsModel;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('id');
        $grid->setDataSource($this->paymentsLimitsModel->getAll());

        $grid->addColumnText('name', 'Název')->setSortable();
        $grid->addColumnNumber('amount', 'Částka')->setSortable();

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
                $container->addText('amount', '');
        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item): void {
            $container->setDefaults([
                'amount' => $item->amount,
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $values['amount'] = (float) $values['amount'];
                $this->paymentsLimitsModel->editLimit((int) $id, $values);
                $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
                $this->logger->info('Payment limit edited', ['edited_by' => $this->presenter->getUser()->id, 'id' => $id]);
            } catch (ValidationException $ex) {
                $this->flashMessage('Neplatná data, změny nebyly uloženy', Flashes::FLASH_DANGER);
            } catch (Exception $ex) {
                $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
                $this->logger->error('Database error when editing session', ['id' => $id, 'message' => $ex->getMessage()]);
            }
        };

        return $grid;
    }
}