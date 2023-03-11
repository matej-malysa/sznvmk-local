<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\ActivitiesGrid;

use App\Classes\Exceptions\FullSessionEditCapacityException;
use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\ActivitiesModel;
use Dibi\Exception;
use Nette\Application\AbortException;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

class ActivitiesGrid extends GridComponent
{
    /** @var ActivitiesModel */
    protected ActivitiesModel $activitiesModel;

    /**
     * AddActivityGrid constructor.
     * @param ActivitiesModel $activitiesModel
     */
    public function __construct(ActivitiesModel $activitiesModel)
    {
        parent::__construct();
        $this->activitiesModel = $activitiesModel;
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
        $grid->setDataSource($this->activitiesModel->getAll());
        $grid->addColumnText('name', 'Název aktivity')->setSortable();
        $grid->addColumnText('session', 'Turnus');
        $grid->addColumnText('count_of_participants', 'Počet účastníků');
        $grid->addColumnText('max_capacity', 'Maximální kapacita');

        $grid->addColumnText('full', 'Stav')->setSortable()
            ->setReplacement([
                '0' => 'Plno',
                '1' => 'Volno',
            ]);

        $grid->addAction('activityDetail', '', 'Settings:activityDetail', ['id' => 'id'])->setIcon('search')
            ->setTitle("Detail aktivity");

        $grid->addAction('statusChange', '', 'statusChange!', ['id', 'old' => 'full'])
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('toggle-on')
            ->setTitle('Změnit stav');

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
            $container->addInteger('max_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);

        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item): void {
            $container->setDefaults([
                'max_capacity' => $item->max_capacity
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $this->activitiesModel->editActivity((int) $id, $values['max_capacity']);
                $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
                $this->logger->info('Session edited', ['edited_by' => $this->presenter->getUser()->id, 'id' => $id]);
            } catch (ValidationException $ex) {
                $this->flashMessage('Neplatná data, změny nebyly uloženy', Flashes::FLASH_DANGER);
            } catch (FullSessionEditCapacityException $ex) {
                $this->flashMessage($ex->getMessage(), Flashes::FLASH_DANGER);
                $this->logger->error('Chyba při zmene kapacity', ['id' => $id, 'message' => $ex->getMessage()]);
            } catch (Exception $ex) {
                $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
                $this->logger->error('Database error when editing session', ['id' => $id, 'message' => $ex->getMessage()]);
            }
        };

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('Smazat')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirmation(
                new StringConfirmation('Opravdu chcete smazat aktivitu %s?', 'name')
            );

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
            $this->activitiesModel->changeEnabled($id, $new);
            $this->flashMessage('Status aktivity změněn', Flashes::FLASH_SUCCESS);
        } catch (Exception $ex) {
            $this->flashMessage('Databázová chyba, změny nebyly uloženy', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }

    /**
     * @param int $id
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        try {
            $this->activitiesModel->deleteActivity($id);
            $this->flashMessage('Aktivita smazána.', Flashes::FLASH_SUCCESS);
            $this->logger->info('Activity deleted', ['id' => $id, 'deleted_by' => $this->presenter->getUser()->id]);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při mazání aktivity. Aktivita nebyla smazána.', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}