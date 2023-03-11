<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\CampDataGrid;

use App\Components\Flashes\Flashes;
use App\Components\GridComponent\GridComponent;
use App\Model\ApplicationsModel;
use App\Model\CampDataModel;
use App\Model\SessionsModel;
use Dibi\Exception;
use Nette\Forms\Container;
use Nette\Schema\ValidationException;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

class CampDataGrid extends GridComponent
{
    /** @var CampDataModel */
    protected CampDataModel $campDataModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    public function __construct(CampDataModel $campDataModel, SessionsModel $sessionsModel)
    {
        parent::__construct();
        $this->campDataModel = $campDataModel;
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
        $grid->setPrimaryKey('app.id');
        $grid->setDataSource($this->campDataModel->getCampDataGrid());

        $grid->addColumnText('sessionName', 'Turnus')
            ->setSortable()
            ->setFilterSelect($this->sessionsModel->getAllToSelect(), 'session')
            ->setPrompt('');
        $grid->addColumnText('firstname', 'Jméno')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('lastname', 'Příjmení')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('personalId', 'Rodné číslo');
        $grid->addColumnText('cardId', 'Číslo průkazu');
        $grid->addColumnText('permanentAddress', 'Adresa trvalého pobytu');
        $grid->addColumnText('covid', 'COVID')
            ->setReplacement([
                '1' => 'Očkování',
                '2' => 'PCR',
                '3' => 'Antigen',
                '4' => 'Test na místě',
            ]);

        $grid->addInlineEdit()
            ->setTitle('Upravit')
            ->setClass('btn btn-xs btn-primary ajax')
            ->onControlAdd[] = function(Container $container): void {
                $container->addText('personalId', '');
                $container->addText('cardId', '');
                $container->addText('permanentAddress', '');
                $container->addSelect('covid', '', $this->campDataModel->getCovidSelect())->setPrompt('');
        };

        $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item): void {
            $container->setDefaults([
                'personalId' => $item->personalId,
                'cardId' => $item->cardId,
                'permanentAddress' => $item->permanentAddress,
            ]);
        };

        $grid->getInlineEdit()->onSubmit[] = function ($id, ArrayHash $values): void {
            try {
                $this->campDataModel->edit((int) $id, $values);
                $this->flashMessage('Úspěšně uloženo', Flashes::FLASH_SUCCESS);
                $this->logger->info('Camp data edited', ['edited_by' => $this->presenter->getUser()->id, 'id' => $id]);
            } catch (ValidationException $ex) {
                $this->flashMessage('Neplatná data, změny nebyly uloženy.', Flashes::FLASH_DANGER);
            } catch (Exception $ex) {
                $this->flashMessage('Databázová chyba, změny nebyly uloženy.', Flashes::FLASH_DANGER);
                $this->logger->error('Database error when editing session', ['id' => $id, 'message' => $ex->getMessage()]);
            }
        };

        return $grid;
    }
}