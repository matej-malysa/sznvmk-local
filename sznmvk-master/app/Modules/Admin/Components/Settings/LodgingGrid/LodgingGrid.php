<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\LodgingGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ParticipantsModel;
use App\Model\SessionsModel;
use App\Model\LodgingModel;
use Ublaboo\DataGrid\DataGrid;

class LodgingGrid extends GridComponent
{
    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var LodgingModel */
    protected LodgingModel $lodgingModel;

    public function __construct(ParticipantsModel $participantsModel, SessionsModel $sessionsModel, LodgingModel $lodgingModel)
    {
        parent::__construct();
        $this->participantsModel = $participantsModel;
        $this->sessionsModel = $sessionsModel;
        $this->lodgingModel = $lodgingModel;
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

        $grid->setDataSource($this->lodgingModel->getGrid());

        $grid->addColumnNumber('id', 'ID')
            ->setSortable();
        $grid->addColumnText('name', 'Název')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('type', 'Typ')
            ->setSortable()
            ->setFilterSelect($this->lodgingModel->getTypeToSelect2(),'type')
            ->setPrompt('');
        $grid->addColumnNumber('capacity', 'Kapacita')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('session_1', 'Turnus 1')
            ->setSortable()
            ->setReplacement([
                0 => 'Zakázáno',
                1 => 'Povoleno',
            ])
            ->setFilterSelect(['' => '', 0 => 'Zakázáno', 1 => 'Povoleno']);
        $grid->addColumnText('session_1_use', 'Turnus 1 využití')
            ->setSortable()
            ->setFilterSelect($this->lodgingModel->getUseToSelect2(),'session_1_use')
            ->setPrompt('');
        $grid->addColumnText('session_2', 'Turnus 2')
            ->setSortable()
            ->setSortable()
            ->setReplacement([
                0 => 'Zakázáno',
                1 => 'Povoleno',
            ])
            ->setFilterSelect(['' => '', 0 => 'Zakázáno', 1 => 'Povoleno']);
        $grid->addColumnText('session_2_use', 'Turnus 2 využití')
            ->setSortable()
            ->setFilterSelect($this->lodgingModel->getUseToSelect3(),'session_2_use')
            ->setPrompt('');

        $grid->setColumnsHideable();
        $grid->addAction('editLodging', '', 'Settings:editLodging', [ 'id' ])
             ->setIcon('edit')
             ->setTitle("Upravit");

        return $grid;
    }
}