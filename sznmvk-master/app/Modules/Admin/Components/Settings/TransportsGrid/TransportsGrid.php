<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\TransportsGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\TransportModel;
use Dibi\Exception;
use Nette\Application\AbortException;
use Nette\Forms\Container;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

class TransportsGrid extends GridComponent
{
    /** @var TransportModel */
    protected $transportModel;

    public function __construct(TransportModel $transportModel)
    {
        parent::__construct();
        $this->transportModel = $transportModel;
    }


    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }


    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setDataSource($this->transportModel->getGrid());

        $grid->addColumnText('name', 'Název')->setSortable();
        $grid->addColumnText('enabled', 'Stav')->setSortable()
            ->setReplacement([
                '0' => 'Zakázáno',
                '1' => 'Povoleno',
            ]);

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
            $container->addText('name');
        };

        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, $item): void {
            $container->setDefaults([
                'name' => $item->name,
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $this->transportModel->editTransport((int) $id, $values);
                $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
                $this->logger->info('Transport edited', ['edited_by' => $this->presenter->getUser()->id, 'id' => $id]);
            } catch (ValidationException $ex) {
                $this->flashMessage('Neplatná data, změny nebyly uloženy', Flashes::FLASH_DANGER);
            } catch (Exception $ex) {
                $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
                $this->logger->error('Database error when editing transport type', ['id' => $id, 'message' => $ex->getMessage()]);
            }
        };

        $grid->addAction('statusChange', '', 'statusChange!', ['id', 'old' => 'enabled'])
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('toggle-on')
            ->setTitle('Změnit stav');

        return $grid;
    }

    /**
     * @param int $id
     * @param int $old
     * @throws AbortException
     */
    public function handleStatusChange(int $id, int $old): void
    {
        try {
            $new = $old == 1 ? 0 : 1;
            $this->transportModel->changeEnabled($id, $new);
            $this->flashMessage('Status způsobu dopravy změněn', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}