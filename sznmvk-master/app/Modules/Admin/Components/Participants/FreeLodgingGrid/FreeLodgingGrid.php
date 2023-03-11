<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Participants\FreeLodgingGrid;

use App\Components\GridComponent\GridComponent;
use App\Model\ParticipantsModel;
use App\Model\SessionsModel;
use App\Model\LodgingModel;
use Ublaboo\DataGrid\DataGrid;

class FreeLodgingGrid extends GridComponent
{
    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var LodgingModel */
    protected LodgingModel $lodgingModel;

    protected int $id;

    public function __construct(ParticipantsModel $participantsModel, SessionsModel $sessionsModel, LodgingModel $lodgingModel, int $id)
    {
        parent::__construct();
        $this->participantsModel = $participantsModel;
        $this->sessionsModel = $sessionsModel;
        $this->lodgingModel = $lodgingModel;
        $this->id = $id;
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/grid.latte');
        $this->template->render();
    }

    public function createComponentGrid(): Datagrid
    {
        $grid = parent::createComponentGrid();
        $grid->setPrimaryKey('mainid');
        $grid->setDataSource($this->lodgingModel->getFreeLodgingById($this->id));

        $grid->addColumnNumber('mainid', 'ID');
        $grid->addColumnText('mainname', 'Název')
            ->setSortable()
            ->setFilterText();
        $grid->addColumnText('type', 'Typ')
            ->setSortable()
            ->setFilterSelect($this->lodgingModel->getTypeToSelect2(),'type')
            ->setPrompt('');
        $grid->addColumnText('space', 'Obsadenost');
        $grid->addColumnText('mate1', 'Spolubývající');
        $grid->addColumnText('mate2', 'Spolubývající');
        $grid->addColumnText('mate3', 'Spolubývající');
        $grid->addColumnText('mate4', 'Spolubývající');
        $grid->addColumnText('mate5', 'Spolubývající');
        $grid->addColumnText('mate6', 'Spolubývající');
        $grid->addAction('makeChange', '', 'Participants:makeChange', ['lodId' => 'mainid','userId' => 'user'])
            ->setIcon('edit');
        //$grid->addAction('changeLodging', '', 'Participants:changeLodging', ['id' => 'application_id'])->setIcon('home');

        return $grid;
    }
}