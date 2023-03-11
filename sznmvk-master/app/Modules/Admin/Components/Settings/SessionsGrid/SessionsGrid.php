<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\SessionsGrid;

use App\Classes\Exceptions\FullSessionEditCapacityException;
use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\SessionsModel;
use Dibi\Exception;
use Nette\Application\AbortException;
use Nette\Forms\Container;
use Nette\Forms\Form;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;

class SessionsGrid extends GridComponent
{
    /** @var SessionsModel */
    protected $sessionsModel;

    public function __construct(SessionsModel $sessionsModel)
    {
        parent::__construct();
        $this->sessionsModel = $sessionsModel;
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
        $grid->setDataSource($this->sessionsModel->getGrid());

        $grid->addColumnText('title', 'Označení')->setSortable();
        $grid->addColumnDateTime('start', 'Začátek')->setSortable();
        $grid->addColumnDateTime('end', 'Konec')->setSortable();
        $grid->addColumnNumber('full_capacity', 'Celková kapacita')->setSortable();
        $grid->addColumnNumber('first_lodging_capacity', 'Primární ubytování')->setSortable();
        $grid->addColumnNumber('second_lodging_capacity', 'Sekundární ubytování')->setSortable();
        $grid->addColumnNumber('third_lodging_capacity', 'Terciární ubytování')->setSortable();
        $grid->addColumnNumber('instructors_capacity', 'Míst pro instruktory')->setSortable();
        $grid->addColumnNumber('guest_capacity', 'Míst pro hosty')->setSortable();
        $grid->addColumnNumber('active', 'Aktivní turnus')
            ->setReplacement([
                '0' => 'Neaktivní',
                '1' => 'Aktivní',
            ]);

        $grid->addAction('aktivitaChange', '', 'aktivitaChange!', ['id', 'old' => 'active'])
            ->setClass('btn btn-xs btn-primary')
            ->setIcon('toggle-on')
            ->setTitle('Změnit stav');

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
                $container->addText('title');
                $container->addText('start');
                $container->addText('end');
                $container->addInteger('first_lodging_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);
                $container->addInteger('second_lodging_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);
                $container->addInteger('third_lodging_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);
                $container->addInteger('instructors_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);
                $container->addInteger('guest_capacity')->addRule(Form::MIN, 'Zadejte kladné číslo', 0);

        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item): void {
            $container->setDefaults([
                'title' => $item->title,
                'start' => $item->start->format('j.n.Y'),
                'end' => $item->end->format('j.n.Y'),
                'first_lodging_capacity' => $item->first_lodging_capacity,
                'second_lodging_capacity' => $item->second_lodging_capacity,
                'third_lodging_capacity' => $item->third_lodging_capacity,
                'instructors_capacity' => $item->instructors_capacity,
                'guest_capacity' => $item->guest_capacity,
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $values['full_capacity'] = $values['first_lodging_capacity'] + $values['second_lodging_capacity'] + $values['third_lodging_capacity'];
                $this->sessionsModel->editSession((int) $id, $values);
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
                new StringConfirmation('Opravdu chcete smazat turnus %s?', 'title')
            );

        return $grid;
    }

    /**
     * @param int $id
     * @param int $old
     * @throws AbortException
     */
    public function handleAktivitaChange(int $id, int $old): void
    {
        try {
            $new = $old == 1 ? 0 : 1;
            $this->sessionsModel->changeEnabled($id, $new);
            $this->flashMessage('Aktivita turnusu změněna', Flashes::FLASH_SUCCESS);
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
            $this->sessionsModel->deleteSession($id);
            $this->flashMessage('Turnus smazán.', Flashes::FLASH_SUCCESS);
            $this->logger->info('Session deleted', ['id' => $id, 'deleted_by' => $this->presenter->getUser()->id]);
        } catch (Exception $ex) {
            $this->flashMessage('Chyba při mazání turnusu. Turnus nebyl smazán.', Flashes::FLASH_DANGER);
        }

        $this->finishHandler();
    }
}