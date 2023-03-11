<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ImportantDatesGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\ImportantDatesModel;
use Dibi\Exception;
use Nette\Forms\Container;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

class ImportantDatesGrid extends GridComponent
{
    /** @var ImportantDatesModel */
    protected ImportantDatesModel $importantDatesModel;

    /**
     * ImportantDatesGrid constructor.
     * @param ImportantDatesModel $importantDatesModel
     */
    public function __construct(ImportantDatesModel $importantDatesModel)
    {
        parent::__construct();
        $this->importantDatesModel = $importantDatesModel;
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
        $grid->setDataSource($this->importantDatesModel->getAll());
        $grid->addColumnText('name', 'Označení')->setSortable();
        $grid->addColumnDateTime('deadline', 'Termín')->setSortable()->setFormat('j.n.Y H:i');

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
                $container->addText('deadline', '');
        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item): void {
            $container->setDefaults([
                'deadline' => $item->deadline->format('j.n.Y H:i'),
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $this->importantDatesModel->editDeadline((int) $id, $values);
                $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
                $this->logger->info('Deadline edited', ['edited_by' => $this->presenter->getUser()->id, 'id' => $id]);
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